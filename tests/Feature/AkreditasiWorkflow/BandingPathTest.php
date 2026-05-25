<?php

namespace Tests\Feature\AkreditasiWorkflow;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Banding;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\BandingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Task 14.3 — Integration test for banding path (accepted and rejected).
 *
 * Validates Requirements 14.1-14.10.
 *
 * Paths:
 *   Banding accepted: rejection (-1) → banding submitted (-2) → admin accepts → status=1
 *   Banding rejected: rejection (-1) → banding submitted (-2) → admin rejects → status=-1
 */
class BandingPathTest extends TestCase
{
    use RefreshDatabase;

    private BandingService $bandingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->bandingService = app(BandingService::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Banding Test',
        ]);
        return $user;
    }

    private function createAdmin(): User
    {
        return User::factory()->create(['role_id' => 1]);
    }

    private function createAsesor(): User
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
     * Create a rejected akreditasi that had assessors assigned (eligible for banding).
     */
private function createRejectedAkreditasiWithAssessors(User $pesantrenUser): array
    {
        $asesor1User = $this->createAsesor();
        $asesor2User = $this->createAsesor();

        $asesor1 = Asesor::where('user_id', $asesor1User->id)->first();
        $asesor2 = Asesor::where('user_id', $asesor2User->id)->first();

        // Create akreditasi at status -1 (Ditolak) — soft-deleted as per workflow
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -1,
            'deleted_at' => now(),
        ]);

        // Assessors were assigned (akreditasi reached status 4 before rejection)
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(30),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now()->subDays(30),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        return [$akreditasi, $asesor1User, $asesor2User];
    }

    // =========================================================================
    // Banding submission tests
    // =========================================================================

    /**
     * Pesantren submits banding → status transitions from -1 to -2.
     *
     * Validates Requirements 14.1, 14.2.
     */
public function test_submit_banding_transitions_status_to_minus_two(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        [$akreditasi] = $this->createRejectedAkreditasiWithAssessors($pesantrenUser);

        $result = $this->bandingService->submitBanding(
            $akreditasi->id,
            $pesantrenUser->id,
            'Kami keberatan dengan hasil penilaian karena dokumen sudah lengkap.'
        );

        $this->assertTrue($result['success'], 'Banding submission should succeed');
        $this->assertNotNull($result['banding']);

        $akreditasi->refresh();
        $this->assertSame(-2, (int) $akreditasi->status);
    }

    /**
     * Banding record is created with correct data.
     *
     * Validates Requirement 14.1.
     */
public function test_banding_record_created_on_submission(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        [$akreditasi] = $this->createRejectedAkreditasiWithAssessors($pesantrenUser);

        $alasan = 'Kami keberatan dengan hasil penilaian karena dokumen sudah lengkap.';
        $result = $this->bandingService->submitBanding($akreditasi->id, $pesantrenUser->id, $alasan);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('bandings', [
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'pending',
            'alasan' => $alasan,
        ]);
    }

    /**
     * Only 1 banding is permitted per akreditasi.
     *
     * Validates Requirement 14.3.
     */
public function test_second_banding_submission_is_rejected(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        [$akreditasi] = $this->createRejectedAkreditasiWithAssessors($pesantrenUser);

        // First banding
        $result1 = $this->bandingService->submitBanding(
            $akreditasi->id,
            $pesantrenUser->id,
            'Pertama kali mengajukan banding.'
        );
        $this->assertTrue($result1['success']);

        // Revert status to -1 to simulate trying again
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $akreditasi->id)
            ->update(['status' => -1]);

        // Second banding — should fail
        $result2 = $this->bandingService->submitBanding(
            $akreditasi->id,
            $pesantrenUser->id,
            'Mencoba banding kedua kali.'
        );
        $this->assertFalse($result2['success']);
        $this->assertStringContainsString('1 banding', $result2['error']);
    }

    /**
     * Banding not available when rejection was at berkas level (no assessors assigned).
     *
     * Validates Requirement 13.3.
     */
public function test_banding_not_available_when_no_assessors_assigned(): void
    {
        $pesantrenUser = $this->createPesantrenUser();

        // Akreditasi rejected at berkas level — no assessors
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -1,
            'deleted_at' => now(),
        ]);

        $result = $this->bandingService->submitBanding(
            $akreditasi->id,
            $pesantrenUser->id,
            'Mencoba banding tanpa asesor.'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Verifikasi Berkas', $result['error']);
    }

    /**
     * Banding submission rejected after 14-day window.
     *
     * Validates Requirement 14.10.
     */
public function test_banding_rejected_after_14_day_window(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        [$akreditasi] = $this->createRejectedAkreditasiWithAssessors($pesantrenUser);

        // Simulate rejection happened 15 days ago
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $akreditasi->id)
            ->update(['updated_at' => now()->subDays(15)]);

        $result = $this->bandingService->submitBanding(
            $akreditasi->id,
            $pesantrenUser->id,
            'Mencoba banding setelah 14 hari.'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('14 hari', $result['error']);
    }

    // =========================================================================
    // Banding accepted path
    // =========================================================================

    /**
     * Admin accepts banding → status transitions from -2 to 1 (Validasi Akhir Admin).
     *
     * Validates Requirements 14.4, 14.5.
     */
public function test_banding_accepted_transitions_to_validasi_admin(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $admin = $this->createAdmin();
        [$akreditasi] = $this->createRejectedAkreditasiWithAssessors($pesantrenUser);

        // Submit banding
        $result = $this->bandingService->submitBanding(
            $akreditasi->id,
            $pesantrenUser->id,
            'Kami keberatan dengan hasil penilaian.'
        );
        $this->assertTrue($result['success']);
        $banding = $result['banding'];

        // Admin accepts banding
        $decideResult = $this->bandingService->decideBanding($banding->id, $admin->id, 'diterima');

        $this->assertTrue($decideResult['success'], 'Banding decision should succeed: ' . ($decideResult['error'] ?? ''));

        // Status should be 1 (Validasi Akhir Admin)
        $akreditasi->refresh();
        $this->assertSame(1, (int) $akreditasi->status);
    }

    /**
     * Banding record is updated to 'accepted' when admin accepts.
     *
     * Validates Requirement 14.5.
     */
public function test_banding_record_updated_to_accepted(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $admin = $this->createAdmin();
        [$akreditasi] = $this->createRejectedAkreditasiWithAssessors($pesantrenUser);

        $result = $this->bandingService->submitBanding(
            $akreditasi->id,
            $pesantrenUser->id,
            'Kami keberatan dengan hasil penilaian.'
        );
        $banding = $result['banding'];

        $this->bandingService->decideBanding($banding->id, $admin->id, 'diterima');

        $banding->refresh();
        $this->assertSame('accepted', $banding->status);
        $this->assertSame($admin->id, $banding->reviewer_id);
        $this->assertNotNull($banding->decided_at);
    }

    /**
     * Assessors are reassigned when banding is accepted.
     *
     * Validates Requirement 14.5.
     */
public function test_assessors_reassigned_when_banding_accepted(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $admin = $this->createAdmin();
        [$akreditasi, $asesor1User, $asesor2User] = $this->createRejectedAkreditasiWithAssessors($pesantrenUser);

        $result = $this->bandingService->submitBanding(
            $akreditasi->id,
            $pesantrenUser->id,
            'Kami keberatan dengan hasil penilaian.'
        );
        $banding = $result['banding'];

        $this->bandingService->decideBanding($banding->id, $admin->id, 'diterima');

        // Assessments should be restored (not soft-deleted)
        $assessmentCount = Assessment::where('akreditasi_id', $akreditasi->id)->count();
        $this->assertGreaterThanOrEqual(2, $assessmentCount);
    }

    // =========================================================================
    // Banding rejected path
    // =========================================================================

    /**
     * Admin rejects banding → status transitions from -2 to -1 (Ditolak).
     *
     * Validates Requirements 14.4, 14.6.
     */
public function test_banding_rejected_transitions_to_ditolak(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $admin = $this->createAdmin();
        [$akreditasi] = $this->createRejectedAkreditasiWithAssessors($pesantrenUser);

        // Submit banding
        $result = $this->bandingService->submitBanding(
            $akreditasi->id,
            $pesantrenUser->id,
            'Kami keberatan dengan hasil penilaian.'
        );
        $this->assertTrue($result['success']);
        $banding = $result['banding'];

        // Admin rejects banding
        $decideResult = $this->bandingService->decideBanding($banding->id, $admin->id, 'ditolak');

        $this->assertTrue($decideResult['success'], 'Banding rejection should succeed: ' . ($decideResult['error'] ?? ''));

        // Status should be -1 (Ditolak)
        $akreditasi->refresh();
        $this->assertSame(-1, (int) $akreditasi->status);
    }

    /**
     * Banding record is updated to 'rejected' when admin rejects.
     *
     * Validates Requirement 14.6.
     */
public function test_banding_record_updated_to_rejected(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $admin = $this->createAdmin();
        [$akreditasi] = $this->createRejectedAkreditasiWithAssessors($pesantrenUser);

        $result = $this->bandingService->submitBanding(
            $akreditasi->id,
            $pesantrenUser->id,
            'Kami keberatan dengan hasil penilaian.'
        );
        $banding = $result['banding'];

        $this->bandingService->decideBanding($banding->id, $admin->id, 'ditolak');

        $banding->refresh();
        $this->assertSame('rejected', $banding->status);
        $this->assertSame($admin->id, $banding->reviewer_id);
        $this->assertNotNull($banding->decided_at);
    }

    /**
     * Invalid banding decision value is rejected.
     *
     * Validates Requirement 14.4.
     */
public function test_invalid_banding_decision_is_rejected(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $admin = $this->createAdmin();
        [$akreditasi] = $this->createRejectedAkreditasiWithAssessors($pesantrenUser);

        $result = $this->bandingService->submitBanding(
            $akreditasi->id,
            $pesantrenUser->id,
            'Kami keberatan dengan hasil penilaian.'
        );
        $banding = $result['banding'];

        $decideResult = $this->bandingService->decideBanding($banding->id, $admin->id, 'invalid_value');

        $this->assertFalse($decideResult['success']);
    }
}
