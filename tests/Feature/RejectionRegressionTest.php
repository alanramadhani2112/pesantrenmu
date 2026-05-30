<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Services\AkreditasiWorkflowService;
use App\Services\RejectionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RejectionRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected RejectionService $rejectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->rejectionService = app(RejectionService::class);
    }

    /**
     * Helper: create a pesantren user with akreditasi at status 5 and an Asesor 1 assigned.
     */
    private function createAsesor1Setup(): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test '.$pesantrenUser->id,
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 4,
        ]);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Test, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        return [
            'pesantrenUser' => $pesantrenUser,
            'akreditasi' => $akreditasi,
            'asesorUser' => $asesorUser,
            'asesor' => $asesor,
        ];
    }

    // =========================================================================
    // 19.2: Full rejection-perbaikan-accept lifecycle end-to-end
    // =========================================================================

    /**
     * Integration test: full rejection-perbaikan-accept lifecycle end-to-end.
     *
     * Validates: Requirements 1.1, 2.1, 3.1, 7.1
     */
    public function test_full_rejection_perbaikan_accept_lifecycle_end_to_end(): void
    {
        Notification::fake();

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;
        $pesantrenUserId = $setup['pesantrenUser']->id;

        // Step 1: Asesor 1 creates rejection with items
        $result = $this->rejectionService->createDocumentRejection(
            $akreditasiId,
            $asesorUserId,
            ['profil', 'ipm.kurikulum', 'sdm'],
            'Data profil tidak lengkap, kurikulum perlu diperbaiki, dan SDM belum sesuai'
        );

        $this->assertTrue($result['success'], 'Rejection creation should succeed');
        $this->assertNotNull($result['rejection'], 'Rejection record should be returned');
        $this->assertEquals('pending', $result['rejection']->status);
        $this->assertEquals(1, $result['rejection']->rejection_number);

        // Verify rejection record persisted correctly
        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'status' => 'pending',
            'rejection_number' => 1,
        ]);

        // Step 2: Verify sections are unlocked
        $this->assertTrue($this->rejectionService->isSectionUnlocked($akreditasiId, 'profil'));
        $this->assertTrue($this->rejectionService->isSectionUnlocked($akreditasiId, 'ipm.kurikulum'));
        $this->assertTrue($this->rejectionService->isSectionUnlocked($akreditasiId, 'sdm'));
        // Non-rejected sections should remain locked
        $this->assertFalse($this->rejectionService->isSectionUnlocked($akreditasiId, 'ipm.nsp'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($akreditasiId, 'ipm.buku_ajar'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($akreditasiId, 'edpm.butir.1'));

        // Verify akreditasi status remains at 5
        $akreditasi = Akreditasi::find($akreditasiId);
        $this->assertEquals(4, (int) $akreditasi->status);

        // Step 3: Pesantren submits perbaikan
        $perbaikanResult = $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);
        $this->assertTrue($perbaikanResult['success'], 'Perbaikan submission should succeed');

        // Step 4: Verify sections are re-locked
        $this->assertFalse($this->rejectionService->isSectionUnlocked($akreditasiId, 'profil'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($akreditasiId, 'ipm.kurikulum'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($akreditasiId, 'sdm'));
        $this->assertEmpty($this->rejectionService->getUnlockedSections($akreditasiId));

        // Verify rejection status marks the assessor review queue after pesantren submits perbaikan.
        $rejection = AkreditasiRejection::where('akreditasi_id', $akreditasiId)->first();
        $this->assertEquals('submitted', $rejection->status);
        $this->assertNotNull($rejection->perbaikan_submitted_at);

        // Verify akreditasi status still at 5
        $akreditasi->refresh();
        $this->assertEquals(4, (int) $akreditasi->status);

        // Step 5: Asesor 1 accepts perbaikan
        $acceptResult = $this->rejectionService->acceptPerbaikan($akreditasiId, $asesorUserId);
        $this->assertTrue($acceptResult['success'], 'Accept perbaikan should succeed');

        // Step 6: Verify rejection status is 'accepted'
        $rejection->refresh();
        $this->assertEquals('accepted', $rejection->status);

        // Verify akreditasi status remains at 5 (ready for visitasi scheduling)
        $akreditasi->refresh();
        $this->assertEquals(4, (int) $akreditasi->status);

        // Verify no active rejection remains
        $status = $this->rejectionService->getRejectionStatus($akreditasiId);
        $this->assertNull($status['active']);
        $this->assertEquals(1, $status['count']);

        // Verify notifications were sent throughout the lifecycle
        Notification::assertSentTo(
            $setup['pesantrenUser'],
            AkreditasiNotification::class,
            function ($notification) {
                return $notification->type === 'document_rejection_created';
            }
        );
    }

    // =========================================================================
    // 19.3: Rejection limit reached triggers auto-rejection
    // =========================================================================

    /**
     * Integration test: rejection limit reached triggers auto-rejection with
     * the banding path still available.
     *
     * Validates: Requirements 4.3, 4.6, 4.8
     */
    public function test_rejection_limit_reached_triggers_auto_rejection_with_banding_available(): void
    {
        Notification::fake();

        // Set rejection_limit to 2 for faster testing
        config(['akreditasi.rejection_limit' => 2]);

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;
        $pesantrenUserId = $setup['pesantrenUser']->id;

        // Step 1: Create first rejection and accept it
        $result1 = $this->rejectionService->createDocumentRejection(
            $akreditasiId,
            $asesorUserId,
            ['profil'],
            'Data profil tidak lengkap, perlu diperbaiki segera'
        );
        $this->assertTrue($result1['success']);
        $this->assertEquals('pending', $result1['rejection']->status);
        $this->assertEquals(1, $result1['rejection']->rejection_number);

        // Pesantren submits perbaikan
        $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);

        // Asesor accepts perbaikan
        $this->rejectionService->acceptPerbaikan($akreditasiId, $asesorUserId);

        // Verify akreditasi still at status 5
        $akreditasi = Akreditasi::find($akreditasiId);
        $this->assertEquals(4, (int) $akreditasi->status);

        // Step 2: Create second rejection (should trigger auto-reject since limit=2)
        $result2 = $this->rejectionService->createDocumentRejection(
            $akreditasiId,
            $asesorUserId,
            ['sdm'],
            'SDM data masih belum sesuai dengan standar yang ditetapkan'
        );
        $this->assertTrue($result2['success']);
        $this->assertEquals('limit_reached', $result2['rejection']->status);
        $this->assertEquals(2, $result2['rejection']->rejection_number);

        // Step 3: Verify akreditasi status = Ditolak
        $akreditasi->refresh();
        $this->assertEquals(-1, (int) $akreditasi->status);

        $this->assertDatabaseHas('akreditasis', [
            'id' => $akreditasiId,
            'status' => -1,
        ]);

        // Step 4: Verify pesantren is unlocked
        $pesantren = Pesantren::where('user_id', $pesantrenUserId)->first();
        $this->assertFalse((bool) $pesantren->is_locked, 'Pesantren should be unlocked after auto-rejection');

        // Step 5: Verify the banding path is still available
        $this->assertEquals(-1, (int) $akreditasi->status, 'Status -1 allows banding');

        // Verify the rejection record has no perbaikan_deadline (no further correction cycle)
        $this->assertNull($result2['rejection']->perbaikan_deadline, 'No deadline should be set for limit_reached rejection');

        // Verify notifications were sent for auto-rejection
        Notification::assertSentTo(
            $setup['pesantrenUser'],
            AkreditasiNotification::class,
            function ($notification) {
                return $notification->type === 'rejection_limit_reached';
            }
        );
    }

    // =========================================================================
    // 19.4: Perbaikan deadline expiry triggers auto-rejection
    // =========================================================================

    /**
     * Integration test: perbaikan deadline expiry triggers auto-rejection.
     *
     * Validates: Requirements 8.4, 8.5
     */
    public function test_perbaikan_deadline_expiry_triggers_auto_rejection(): void
    {
        Notification::fake();

        config(['akreditasi.perbaikan_reminder_days_before' => 3]);
        config(['akreditasi.rejection_limit' => 10]);

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;
        $pesantrenUserId = $setup['pesantrenUser']->id;

        // Verify pesantren is locked initially
        $pesantren = Pesantren::where('user_id', $pesantrenUserId)->first();
        $this->assertTrue((bool) $pesantren->is_locked);

        // Step 1: Create rejection with deadline in the past
        Carbon::setTestNow(Carbon::now());

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil', 'ipm.kurikulum'],
            'explanation' => 'Data profil dan kurikulum perlu diperbaiki segera',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->subDays(3), // 3 days in the past (expired)
            'status' => 'pending',
        ]);

        // Step 2: Call processDeadlines()
        $result = $this->rejectionService->processDeadlines();

        $this->assertEquals(1, $result['auto_rejected'], 'Should auto-reject 1 expired rejection');
        $this->assertEquals(0, $result['reminders_sent']);

        // Step 3: Verify rejection status = 'expired'
        $rejection->refresh();
        $this->assertEquals('expired', $rejection->status);

        $this->assertDatabaseHas('akreditasi_rejections', [
            'id' => $rejection->id,
            'status' => 'expired',
        ]);

        // Step 4: Verify akreditasi status = Ditolak
        $akreditasi = Akreditasi::withTrashed()->find($akreditasiId);
        $this->assertNotNull($akreditasi);
        $this->assertEquals(-1, (int) $akreditasi->status);

        $this->assertDatabaseHas('akreditasis', [
            'id' => $akreditasiId,
            'status' => -1,
        ]);

        // Step 5: Verify pesantren is unlocked
        $pesantren->refresh();
        $this->assertFalse((bool) $pesantren->is_locked, 'Pesantren should be unlocked after deadline expiry');

        // Step 6: Verify notifications sent
        Notification::assertSentTo(
            $setup['pesantrenUser'],
            AkreditasiNotification::class,
            function ($notification) {
                return $notification->type === 'rejection_deadline_expired';
            }
        );

        Carbon::setTestNow();
    }

    // =========================================================================
    // 19.5: Admin final rejection stores categories and changes status
    // =========================================================================

    /**
     * Integration test: admin final rejection stores categories and changes status.
     *
     * Validates: Requirements 9.1, 9.2, 9.3
     */
    public function test_admin_final_rejection_stores_categories_and_changes_status(): void
    {
        Notification::fake();

        // Step 1: Create akreditasi at Validasi Admin
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Final Test',
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 1,
        ]);

        $adminUser = User::factory()->create(['role_id' => 1]);

        $categories = [
            [
                'category' => 'nilai_tidak_memenuhi',
                'explanation' => 'Nilai akreditasi tidak memenuhi standar minimum yang ditetapkan',
            ],
            [
                'category' => 'inkonsistensi_data',
                'explanation' => 'Terdapat inkonsistensi antara data laporan dan hasil visitasi',
            ],
        ];

        // Step 2: Admin calls canonical final rejection workflow
        $reason = collect($categories)
            ->map(fn ($category) => $category['category'].': '.$category['explanation'])
            ->implode('; ');

        app(AkreditasiWorkflowService::class)
            ->rejectAtValidasi($akreditasi->id, $adminUser->id, $reason, '', $categories);

        // Step 3: Verify rejection record stored with correct categories
        $rejectionRecord = AkreditasiRejection::where('akreditasi_id', $akreditasi->id)
            ->where('type', 'admin_final')
            ->first();

        $this->assertNotNull($rejectionRecord, 'Final rejection record should exist');
        $this->assertEquals('admin_final', $rejectionRecord->type);
        $this->assertEquals($adminUser->id, $rejectionRecord->user_id);
        $this->assertEquals('final', $rejectionRecord->status);

        // Verify categories are stored correctly
        $storedCategories = $rejectionRecord->categories;
        $this->assertCount(2, $storedCategories);
        $this->assertEquals('nilai_tidak_memenuhi', $storedCategories[0]['category']);
        $this->assertEquals('Nilai akreditasi tidak memenuhi standar minimum yang ditetapkan', $storedCategories[0]['explanation']);
        $this->assertEquals('inkonsistensi_data', $storedCategories[1]['category']);
        $this->assertEquals('Terdapat inkonsistensi antara data laporan dan hasil visitasi', $storedCategories[1]['explanation']);

        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $adminUser->id,
            'type' => 'admin_final',
            'status' => 'final',
        ]);

        // Step 4: Verify akreditasi status = Ditolak
        $akreditasi->refresh();
        $this->assertEquals(-1, (int) $akreditasi->status);

        $this->assertDatabaseHas('akreditasis', [
            'id' => $akreditasi->id,
            'status' => -1,
        ]);

        // Step 5: Verify pesantren is unlocked
        $pesantren = Pesantren::where('user_id', $pesantrenUser->id)->first();
        $this->assertFalse((bool) $pesantren->is_locked, 'Pesantren should be unlocked after final rejection');

        // Verify notification sent to pesantren
        Notification::assertSentTo(
            $pesantrenUser,
            AkreditasiNotification::class,
            function ($notification) {
                return $notification->type === 'final_rejection';
            }
        );
    }
}
