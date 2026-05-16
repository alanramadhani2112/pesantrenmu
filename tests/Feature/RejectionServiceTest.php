<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AsesorService;
use App\Services\RejectionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RejectionServiceTest extends TestCase
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
            'nama_pesantren' => 'Pesantren Test ' . $pesantrenUser->id,
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 5,
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

    /**
     * Integration test: processDeadlines sends reminders for approaching deadlines.
     *
     * Validates: Requirements 8.3
     */
    public function test_process_deadlines_sends_reminders_for_approaching_deadlines(): void
    {
        config(['akreditasi.perbaikan_reminder_days_before' => 3]);
        config(['akreditasi.rejection_limit' => 10]);

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;

        // Create a rejection with deadline approaching within reminder threshold (2 days from now)
        Carbon::setTestNow(Carbon::now());

        // Create rejection with a deadline that is 2 days from now (within 3-day reminder threshold)
        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil', 'sdm'],
            'explanation' => 'This needs to be fixed urgently',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(2), // 2 days from now, within 3-day reminder window
            'status' => 'pending',
        ]);

        // Call processDeadlines
        $result = $this->rejectionService->processDeadlines();

        // Verify reminders_sent count is correct
        $this->assertEquals(1, $result['reminders_sent'], 'Should send 1 reminder for approaching deadline');
        $this->assertEquals(0, $result['auto_rejected'], 'Should not auto-reject any');

        // Verify the rejection status is still pending (not changed)
        $rejection->refresh();
        $this->assertEquals('pending', $rejection->status, 'Rejection status should remain pending');

        Carbon::setTestNow();
    }

    /**
     * Integration test: processDeadlines does NOT send reminders for deadlines outside threshold.
     *
     * Validates: Requirements 8.3
     */
    public function test_process_deadlines_does_not_send_reminders_outside_threshold(): void
    {
        config(['akreditasi.perbaikan_reminder_days_before' => 3]);
        config(['akreditasi.rejection_limit' => 10]);

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;

        Carbon::setTestNow(Carbon::now());

        // Create rejection with deadline 10 days from now (outside 3-day reminder window)
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'This needs to be fixed',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(10),
            'status' => 'pending',
        ]);

        $result = $this->rejectionService->processDeadlines();

        $this->assertEquals(0, $result['reminders_sent'], 'Should not send reminders for deadlines outside threshold');
        $this->assertEquals(0, $result['auto_rejected'], 'Should not auto-reject any');

        Carbon::setTestNow();
    }

    /**
     * Integration test: processDeadlines auto-rejects expired rejections and unlocks pesantren.
     *
     * Validates: Requirements 8.4, 8.5
     */
    public function test_process_deadlines_auto_rejects_expired_rejections_and_unlocks_pesantren(): void
    {
        config(['akreditasi.perbaikan_reminder_days_before' => 3]);
        config(['akreditasi.rejection_limit' => 10]);

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;
        $pesantrenUserId = $setup['pesantrenUser']->id;

        Carbon::setTestNow(Carbon::now());

        // Verify pesantren is locked initially
        $pesantren = Pesantren::where('user_id', $pesantrenUserId)->first();
        $this->assertTrue((bool) $pesantren->is_locked, 'Pesantren should be locked initially');

        // Create a rejection with deadline in the past (expired)
        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil', 'ipm.kurikulum'],
            'explanation' => 'This needs to be fixed but deadline passed',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->subDays(2), // 2 days in the past
            'status' => 'pending',
        ]);

        // Call processDeadlines
        $result = $this->rejectionService->processDeadlines();

        // Verify auto_rejected count is correct
        $this->assertEquals(0, $result['reminders_sent'], 'Should not send reminders');
        $this->assertEquals(1, $result['auto_rejected'], 'Should auto-reject 1 expired rejection');

        // Verify: akreditasi status changed to 2
        $akreditasi = Akreditasi::find($akreditasiId);
        $this->assertEquals(2, (int) $akreditasi->status, 'Akreditasi status should be changed to 2 (Ditolak)');

        // Verify: pesantren is_locked is false
        $pesantren->refresh();
        $this->assertFalse((bool) $pesantren->is_locked, 'Pesantren is_locked should be false after auto-rejection');

        // Verify: rejection status is 'expired'
        $rejection->refresh();
        $this->assertEquals('expired', $rejection->status, 'Rejection status should be expired');

        Carbon::setTestNow();
    }

    /**
     * Integration test: processDeadlines handles multiple expired rejections correctly.
     *
     * Validates: Requirements 8.4, 8.5
     */
    public function test_process_deadlines_handles_multiple_expired_rejections(): void
    {
        config(['akreditasi.perbaikan_reminder_days_before' => 3]);
        config(['akreditasi.rejection_limit' => 10]);

        Carbon::setTestNow(Carbon::now());

        // Create two separate setups with expired rejections
        $setup1 = $this->createAsesor1Setup();
        $setup2 = $this->createAsesor1Setup();

        // Create expired rejection for setup 1
        AkreditasiRejection::create([
            'akreditasi_id' => $setup1['akreditasi']->id,
            'user_id' => $setup1['asesorUser']->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Expired rejection 1',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->subDays(1),
            'status' => 'pending',
        ]);

        // Create expired rejection for setup 2
        AkreditasiRejection::create([
            'akreditasi_id' => $setup2['akreditasi']->id,
            'user_id' => $setup2['asesorUser']->id,
            'type' => 'asesor',
            'items' => ['sdm'],
            'explanation' => 'Expired rejection 2',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->subDays(5),
            'status' => 'pending',
        ]);

        $result = $this->rejectionService->processDeadlines();

        $this->assertEquals(2, $result['auto_rejected'], 'Should auto-reject 2 expired rejections');

        // Verify both akreditasi statuses changed
        $this->assertEquals(2, (int) Akreditasi::find($setup1['akreditasi']->id)->status);
        $this->assertEquals(2, (int) Akreditasi::find($setup2['akreditasi']->id)->status);

        // Verify both pesantrens unlocked
        $pesantren1 = Pesantren::where('user_id', $setup1['pesantrenUser']->id)->first();
        $pesantren2 = Pesantren::where('user_id', $setup2['pesantrenUser']->id)->first();
        $this->assertFalse((bool) $pesantren1->is_locked);
        $this->assertFalse((bool) $pesantren2->is_locked);

        Carbon::setTestNow();
    }

    /**
     * Integration test: processDeadlines skips already-submitted perbaikan.
     *
     * Validates: Requirements 8.4
     */
    public function test_process_deadlines_skips_submitted_perbaikan(): void
    {
        config(['akreditasi.perbaikan_reminder_days_before' => 3]);
        config(['akreditasi.rejection_limit' => 10]);

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;

        Carbon::setTestNow(Carbon::now());

        // Create a rejection with deadline in the past but status is 'submitted' (not 'pending')
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Already submitted perbaikan',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->subDays(2),
            'status' => 'submitted', // Already submitted, should be skipped
        ]);

        $result = $this->rejectionService->processDeadlines();

        $this->assertEquals(0, $result['auto_rejected'], 'Should not auto-reject submitted rejections');
        $this->assertEquals(0, $result['reminders_sent'], 'Should not send reminders for submitted rejections');

        // Verify akreditasi status unchanged
        $akreditasi = Akreditasi::find($akreditasiId);
        $this->assertEquals(5, (int) $akreditasi->status, 'Akreditasi status should remain 5');

        Carbon::setTestNow();
    }

    // =========================================================================
    // Task 10: Integration tests for AsesorService + RejectionService
    // =========================================================================

    /**
     * Integration test: processVisitasi with action='tolak' creates structured rejection record.
     *
     * Validates: Requirements 1.1, 1.5
     */
    public function test_process_visitasi_tolak_creates_structured_rejection_record(): void
    {
        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;

        $asesorService = app(AsesorService::class);

        $data = [
            'rejected_items' => ['profil', 'ipm.kurikulum', 'sdm'],
            'catatan' => 'Data profil tidak lengkap dan kurikulum perlu diperbaiki',
        ];

        $result = $asesorService->processVisitasi($akreditasiId, $asesorUserId, $data, 'tolak');

        // Verify the call succeeded
        $this->assertTrue($result, 'processVisitasi with action=tolak should return true');

        // Verify rejection record was created
        $rejection = AkreditasiRejection::where('akreditasi_id', $akreditasiId)
            ->where('type', 'asesor')
            ->first();

        $this->assertNotNull($rejection, 'A rejection record should be created');
        $this->assertEquals($asesorUserId, $rejection->user_id);
        $this->assertEquals(['profil', 'ipm.kurikulum', 'sdm'], $rejection->items);
        $this->assertEquals('Data profil tidak lengkap dan kurikulum perlu diperbaiki', $rejection->explanation);
        $this->assertEquals(1, $rejection->rejection_number);
        $this->assertEquals('pending', $rejection->status);
        $this->assertNotNull($rejection->perbaikan_deadline);

        // Verify akreditasi status remains at 5
        $akreditasi = Akreditasi::find($akreditasiId);
        $this->assertEquals(5, (int) $akreditasi->status);
    }

    /**
     * Integration test: processVisitasi with action='tolak' fails for unauthorized user.
     *
     * Validates: Requirements 1.6
     */
    public function test_process_visitasi_tolak_fails_for_unauthorized_user(): void
    {
        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;

        // Create a different user who is NOT Asesor 1
        $unauthorizedUser = User::factory()->create(['role_id' => 2]);

        $asesorService = app(AsesorService::class);

        $data = [
            'rejected_items' => ['profil'],
            'catatan' => 'This should fail because user is not authorized',
        ];

        $result = $asesorService->processVisitasi($akreditasiId, $unauthorizedUser->id, $data, 'tolak');

        // Should return false because user is not assigned Asesor 1
        $this->assertFalse($result, 'processVisitasi should return false for unauthorized user');

        // Verify no rejection record was created
        $rejectionCount = AkreditasiRejection::where('akreditasi_id', $akreditasiId)->count();
        $this->assertEquals(0, $rejectionCount, 'No rejection record should be created');
    }

    /**
     * Integration test: processVisitasi with action='accept_perbaikan' clears rejection and enables visitasi.
     *
     * Validates: Requirements 7.1, 7.2
     */
    public function test_process_visitasi_accept_perbaikan_clears_rejection(): void
    {
        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;

        // Create a rejection that has been submitted (perbaikan submitted by pesantren)
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil', 'sdm'],
            'explanation' => 'Data profil dan SDM perlu diperbaiki',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'perbaikan_submitted_at' => now()->subDays(1),
            'status' => 'submitted',
        ]);

        $asesorService = app(AsesorService::class);

        $result = $asesorService->processVisitasi($akreditasiId, $asesorUserId, [], 'accept_perbaikan');

        // Verify the call succeeded
        $this->assertTrue($result, 'processVisitasi with action=accept_perbaikan should return true');

        // Verify rejection status changed to 'accepted'
        $rejection = AkreditasiRejection::where('akreditasi_id', $akreditasiId)->first();
        $this->assertEquals('accepted', $rejection->status, 'Rejection status should be accepted');

        // Verify akreditasi status remains at 5 (ready for visitasi scheduling)
        $akreditasi = Akreditasi::find($akreditasiId);
        $this->assertEquals(5, (int) $akreditasi->status, 'Akreditasi status should remain 5');
    }

    /**
     * Integration test: processVisitasi with action='accept_perbaikan' fails when no submitted rejection exists.
     *
     * Validates: Requirements 7.1
     */
    public function test_process_visitasi_accept_perbaikan_fails_without_submitted_rejection(): void
    {
        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;

        $asesorService = app(AsesorService::class);

        // No rejection exists, so accept_perbaikan should fail
        $result = $asesorService->processVisitasi($akreditasiId, $asesorUserId, [], 'accept_perbaikan');

        $this->assertFalse($result, 'processVisitasi with action=accept_perbaikan should return false when no submitted rejection exists');
    }

    /**
     * Integration test: full rejection-perbaikan-accept lifecycle.
     *
     * Validates: Requirements 1.1, 3.1, 3.2, 7.1, 7.2
     */
    public function test_full_rejection_perbaikan_accept_lifecycle(): void
    {
        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;
        $pesantrenUserId = $setup['pesantrenUser']->id;

        $asesorService = app(AsesorService::class);

        // Step 1: Asesor 1 rejects via processVisitasi
        $rejectData = [
            'rejected_items' => ['profil', 'ipm.nsp'],
            'catatan' => 'Profil pesantren tidak lengkap dan NSP perlu diperbarui',
        ];

        $result = $asesorService->processVisitasi($akreditasiId, $asesorUserId, $rejectData, 'tolak');
        $this->assertTrue($result, 'Step 1: Rejection should succeed');

        // Verify rejection record created
        $rejection = AkreditasiRejection::where('akreditasi_id', $akreditasiId)
            ->where('type', 'asesor')
            ->first();
        $this->assertNotNull($rejection);
        $this->assertEquals('pending', $rejection->status);
        $this->assertEquals(['profil', 'ipm.nsp'], $rejection->items);

        // Verify sections are unlocked
        $this->assertTrue($this->rejectionService->isSectionUnlocked($akreditasiId, 'profil'));
        $this->assertTrue($this->rejectionService->isSectionUnlocked($akreditasiId, 'ipm.nsp'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($akreditasiId, 'sdm'));

        // Step 2: Pesantren submits perbaikan
        $perbaikanResult = $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);
        $this->assertTrue($perbaikanResult['success'], 'Step 2: Perbaikan submission should succeed');

        // Verify sections are re-locked (no unlocked sections)
        $this->assertEmpty($this->rejectionService->getUnlockedSections($akreditasiId));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($akreditasiId, 'profil'));

        // Verify rejection status is 'submitted'
        $rejection->refresh();
        $this->assertEquals('submitted', $rejection->status);
        $this->assertNotNull($rejection->perbaikan_submitted_at);

        // Step 3: Asesor 1 accepts perbaikan via processVisitasi
        $result = $asesorService->processVisitasi($akreditasiId, $asesorUserId, [], 'accept_perbaikan');
        $this->assertTrue($result, 'Step 3: Accept perbaikan should succeed');

        // Verify rejection status is 'accepted'
        $rejection->refresh();
        $this->assertEquals('accepted', $rejection->status);

        // Verify akreditasi status remains at 5 (ready for visitasi scheduling)
        $akreditasi = Akreditasi::find($akreditasiId);
        $this->assertEquals(5, (int) $akreditasi->status);

        // Verify no active rejection remains
        $activeRejection = $this->rejectionService->getRejectionStatus($akreditasiId);
        $this->assertNull($activeRejection['active'], 'No active rejection should remain after acceptance');
        $this->assertEquals(1, $activeRejection['count'], 'Rejection count should be 1');
    }
}
