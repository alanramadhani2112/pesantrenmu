<?php

namespace Tests\Feature\AkreditasiWorkflow;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\RejectionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Task 14.5 — Integration test for perbaikan deadline expiry auto-rejection.
 *
 * Validates Requirements 4.9 — when the 14-day perbaikan deadline expires
 * without a submission, the akreditasi is soft-deleted and status set to -1.
 */
class PerbaikanDeadlineExpiryTest extends TestCase
{
    use RefreshDatabase;

    private RejectionService $rejectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->rejectionService = app(RejectionService::class);

        // Create an admin user so autoRejectOnDeadlineExpiry has a system actor
        User::factory()->create(['role_id' => 1]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Deadline Test',
        ]);

        return $user;
    }

    private function createAsesor1User(): User
    {
        $user = User::factory()->create(['role_id' => 2]);
        Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => 'Asesor Test, S.Pd.',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        return $user;
    }

    /**
     * Create an akreditasi at status 4 with an expired perbaikan rejection.
     */
    private function createAkreditasiWithExpiredRejection(
        User $pesantrenUser,
        User $asesor1User,
        int $daysExpiredAgo = 1
    ): array {
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 4,
        ]);

        $asesor = Asesor::where('user_id', $asesor1User->id)->first();
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(30),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesor1User->id,
            'type' => 'asesor',
            'items' => ['profil', 'sdm'],
            'explanation' => 'Dokumen profil dan SDM perlu diperbaiki.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->subDays($daysExpiredAgo),
            'status' => 'pending',
        ]);

        return [$akreditasi, $rejection];
    }

    // =========================================================================
    // Tests
    // =========================================================================

    /**
     * When processDeadlines() is called and a perbaikan deadline has expired,
     * the akreditasi should be soft-deleted and status set to -1.
     *
     * Validates Requirement 4.9.
     */
    public function test_expired_perbaikan_deadline_auto_rejects_akreditasi(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor1User = $this->createAsesor1User();

        [$akreditasi, $rejection] = $this->createAkreditasiWithExpiredRejection($pesantrenUser, $asesor1User);

        $this->assertSame(4, (int) $akreditasi->status);
        $this->assertNull($akreditasi->deleted_at);

        // Run deadline processing
        $result = $this->rejectionService->processDeadlines();

        $this->assertSame(1, $result['auto_rejected']);

        // Akreditasi should be soft-deleted
        $akreditasi->refresh();
        $this->assertNotNull(
            Akreditasi::withTrashed()->find($akreditasi->id)->deleted_at,
            'Akreditasi should be soft-deleted after deadline expiry'
        );

        // Status should be -1 (Ditolak)
        $this->assertSame(-1, (int) Akreditasi::withTrashed()->find($akreditasi->id)->status);
    }

    /**
     * The rejection record should be marked as 'expired' after auto-rejection.
     *
     * Validates Requirement 4.9.
     */
    public function test_rejection_status_set_to_expired_after_auto_rejection(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor1User = $this->createAsesor1User();

        [$akreditasi, $rejection] = $this->createAkreditasiWithExpiredRejection($pesantrenUser, $asesor1User);

        $this->rejectionService->processDeadlines();

        $rejection->refresh();
        $this->assertSame('expired', $rejection->status);
    }

    /**
     * Running the artisan command triggers auto-rejection for expired deadlines.
     *
     * Validates Requirement 4.9.
     */
    public function test_check_perbaikan_deadlines_command_auto_rejects_expired(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor1User = $this->createAsesor1User();

        [$akreditasi] = $this->createAkreditasiWithExpiredRejection($pesantrenUser, $asesor1User);

        $this->artisan('akreditasi:check-perbaikan-deadlines')
            ->assertExitCode(0);

        // Akreditasi should be soft-deleted and at status -1
        $fresh = Akreditasi::withTrashed()->find($akreditasi->id);
        $this->assertNotNull($fresh->deleted_at);
        $this->assertSame(-1, (int) $fresh->status);
    }

    /**
     * Non-expired pending rejections should NOT be auto-rejected.
     *
     * Validates Requirement 4.9.
     */
    public function test_non_expired_rejection_is_not_auto_rejected(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor1User = $this->createAsesor1User();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 4,
        ]);

        $asesor = Asesor::where('user_id', $asesor1User->id)->first();
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(5),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        // Deadline is still in the future
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesor1User->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Profil perlu diperbaiki.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(5),
            'status' => 'pending',
        ]);

        $result = $this->rejectionService->processDeadlines();

        $this->assertSame(0, $result['auto_rejected']);

        // Akreditasi should still be at status 4 and not deleted
        $akreditasi->refresh();
        $this->assertSame(4, (int) $akreditasi->status);
        $this->assertNull($akreditasi->deleted_at);
    }

    /**
     * Multiple expired rejections across different akreditasi are all processed.
     *
     * Validates Requirement 4.9.
     */
    public function test_multiple_expired_rejections_all_auto_rejected(): void
    {
        $asesor1User = $this->createAsesor1User();

        $user1 = $this->createPesantrenUser();
        $user2 = $this->createPesantrenUser();

        [$akreditasi1] = $this->createAkreditasiWithExpiredRejection($user1, $asesor1User, 2);
        [$akreditasi2] = $this->createAkreditasiWithExpiredRejection($user2, $asesor1User, 3);

        $result = $this->rejectionService->processDeadlines();

        $this->assertSame(2, $result['auto_rejected']);

        foreach ([$akreditasi1->id, $akreditasi2->id] as $id) {
            $fresh = Akreditasi::withTrashed()->find($id);
            $this->assertNotNull($fresh->deleted_at, "Akreditasi #{$id} should be soft-deleted");
            $this->assertSame(-1, (int) $fresh->status, "Akreditasi #{$id} should be at status -1");
        }
    }

    /**
     * Reminder notifications are sent for rejections approaching deadline (within 3 days).
     *
     * Validates Requirement 4.10.
     */
    public function test_reminder_sent_for_rejection_approaching_deadline(): void
    {
        config(['akreditasi.perbaikan_reminder_days_before' => 3]);

        $pesantrenUser = $this->createPesantrenUser();
        $asesor1User = $this->createAsesor1User();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 4,
        ]);

        $asesor = Asesor::where('user_id', $asesor1User->id)->first();
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(10),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        // Deadline is 2 days away (within 3-day reminder window)
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesor1User->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Profil perlu diperbaiki segera.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(2),
            'status' => 'pending',
        ]);

        $result = $this->rejectionService->processDeadlines();

        $this->assertSame(1, $result['reminders_sent']);
        $this->assertSame(0, $result['auto_rejected']);

        // Akreditasi should still be at status 4
        $akreditasi->refresh();
        $this->assertSame(4, (int) $akreditasi->status);
    }
}
