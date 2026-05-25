<?php

namespace Tests\Feature\AkreditasiWorkflow;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Services\AkreditasiWorkflowService;
use App\Services\ResubmissionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Task 14.2 — Integration test for rejection and resubmission path.
 *
 * Validates Requirements 3.6-3.8, 13.4-13.10.
 *
 * Path:
 *   Submit pengajuan (6) → Admin opens (5) → Admin rejects berkas (-1)
 *   → 30-day cooling period blocks resubmission
 *   → Fast-forward 30 days → resubmission allowed
 *   → New akreditasi at status 6 with parent_id set
 */
class RejectionResubmissionPathTest extends TestCase
{
    use RefreshDatabase;

    private AkreditasiWorkflowService $workflowService;
    private ResubmissionService $resubmissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->workflowService = app(AkreditasiWorkflowService::class);
        $this->resubmissionService = app(ResubmissionService::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Rejection Test',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. Pendidikan No. 1',
            'provinsi' => 'Jawa Tengah',
            'kota_kabupaten' => 'Kota Solo',
            'tahun_pendirian' => '2000',
            'nama_mudir' => 'Ahmad Mudir',
            'layanan_satuan_pendidikan' => ['spm'],
            'is_locked' => false,
        ]);

        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'ipm/nsp.pdf',
            'lulus_santri_file' => 'ipm/lulus.pdf',
            'kurikulum_file' => 'ipm/kurikulum.pdf',
            'buku_ajar_file' => 'ipm/buku-ajar.pdf',
        ]);

        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'spm',
            'santri_l' => 10,
            'santri_p' => 8,
            'ustadz_dirosah_l' => 3,
            'ustadz_dirosah_p' => 2,
        ]);

        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'Standar Isi']);
        $butir = MasterEdpmButir::firstOrCreate(
            ['komponen_id' => $komponen->id, 'nomor_butir' => '1.1'],
            ['no_sk' => '1', 'butir_pernyataan' => 'Pesantren memiliki dokumen kurikulum.']
        );

        Edpm::create([
            'user_id' => $user->id,
            'butir_id' => $butir->id,
            'isian' => '4',
        ]);

        return $user->refresh();
    }

    private function createAdmin(): User
    {
        return User::factory()->create(['role_id' => 1]);
    }

    private function createAsesor(int $tipe = 1): User
    {
        $user = User::factory()->create(['role_id' => 2]);
        Asesor::create(['user_id' => $user->id]);
        return $user;
    }

    // =========================================================================
    // Tests
    // =========================================================================

    /**
     * Full rejection path: submit → open → reject berkas → status=-1, soft-deleted.
     *
     * Validates Requirements 3.6, 3.7, 3.8.
     */
public function test_admin_rejects_berkas_sets_status_minus_one_and_soft_deletes(): void
    {
        $pesantrenUser = $this->createCompletePesantrenUser();
        $admin = $this->createAdmin();

        // Submit pengajuan
        $akreditasi = $this->workflowService->submitPengajuan($pesantrenUser->id);
        $this->assertSame(6, (int) $akreditasi->status);

        // Admin opens for review
        $this->workflowService->openForReview($akreditasi->id, $admin->id);
        $akreditasi->refresh();
        $this->assertSame(5, (int) $akreditasi->status);

        // Admin rejects berkas
        $this->workflowService->rejectBerkas($akreditasi->id, $admin->id, [
            'sections' => ['profil', 'sdm'],
            'catatan' => 'Dokumen profil dan SDM tidak lengkap dan perlu diperbaiki.',
        ]);

        // Verify status = -1 and soft-deleted
        $fresh = Akreditasi::withTrashed()->find($akreditasi->id);
        $this->assertSame(-1, (int) $fresh->status);
        $this->assertNotNull($fresh->deleted_at, 'Akreditasi should be soft-deleted after berkas rejection');
    }

    /**
     * 30-day cooling period blocks resubmission immediately after rejection.
     *
     * Validates Requirements 13.6, 13.9.
     */
public function test_cooling_period_blocks_resubmission_immediately_after_rejection(): void
    {
        $pesantrenUser = $this->createCompletePesantrenUser();
        $admin = $this->createAdmin();

        // Create a rejected akreditasi (simulate rejection happened just now)
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -1,
            'deleted_at' => now(),
        ]);

        // Cooling period check — should block
        $result = $this->resubmissionService->canResubmit($akreditasi->id);

        $this->assertFalse($result['can']);
        $this->assertNotNull($result['days_remaining']);
        $this->assertGreaterThan(0, $result['days_remaining']);
    }

    /**
     * After 30 days, resubmission is allowed.
     *
     * Validates Requirements 13.4, 13.6.
     */
public function test_resubmission_allowed_after_30_day_cooling_period(): void
    {
        $pesantrenUser = $this->createCompletePesantrenUser();

        // Create a rejected akreditasi with updated_at 31 days ago
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -1,
            'deleted_at' => now()->subDays(31),
        ]);

        // Manually set updated_at to 31 days ago to simulate cooling period elapsed
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $akreditasi->id)
            ->update(['updated_at' => now()->subDays(31)]);

        $result = $this->resubmissionService->canResubmit($akreditasi->id);

        $this->assertTrue($result['can']);
        $this->assertNull($result['reason']);
    }

    /**
     * Creating a resubmission produces a new akreditasi at status 6 with parent_id set.
     *
     * Validates Requirements 13.7, 13.8.
     */
public function test_resubmission_creates_new_akreditasi_at_status_6_with_parent_id(): void
    {
        $pesantrenUser = $this->createCompletePesantrenUser();

        // Create a rejected akreditasi (cooling period elapsed)
        $originalAkreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -1,
            'deleted_at' => now()->subDays(31),
        ]);

        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $originalAkreditasi->id)
            ->update(['updated_at' => now()->subDays(31)]);

        // Create resubmission
        $newAkreditasi = $this->resubmissionService->createResubmission(
            $originalAkreditasi->id,
            $pesantrenUser->id
        );

        $this->assertNotNull($newAkreditasi);
        $this->assertSame(6, (int) $newAkreditasi->status);
        $this->assertSame($originalAkreditasi->id, $newAkreditasi->parent);
        $this->assertSame($pesantrenUser->id, $newAkreditasi->user_id);

        $this->assertDatabaseHas('akreditasis', [
            'id' => $newAkreditasi->id,
            'status' => 6,
            'parent' => $originalAkreditasi->id,
            'user_id' => $pesantrenUser->id,
        ]);
    }

    /**
     * Resubmission unlocks pesantren data sections.
     *
     * Validates Requirement 13.8.
     */
public function test_resubmission_unlocks_pesantren_data(): void
    {
        $pesantrenUser = $this->createCompletePesantrenUser();

        // Lock the pesantren data (as would happen after submission)
        Pesantren::where('user_id', $pesantrenUser->id)->update(['is_locked' => true]);

        $originalAkreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -1,
            'deleted_at' => now()->subDays(31),
        ]);

        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $originalAkreditasi->id)
            ->update(['updated_at' => now()->subDays(31)]);

        $this->resubmissionService->createResubmission($originalAkreditasi->id, $pesantrenUser->id);

        // Pesantren data should be unlocked
        $pesantren = Pesantren::where('user_id', $pesantrenUser->id)->first();
        $this->assertFalse((bool) $pesantren->is_locked);
    }

    /**
     * Resubmission chain count is enforced — max 3 resubmissions.
     *
     * Validates Requirements 13.5, 13.10.
     */
public function test_resubmission_blocked_after_3_resubmissions(): void
    {
        $pesantrenUser = $this->createCompletePesantrenUser();

        // Root akreditasi
        $root = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -1,
            'deleted_at' => now()->subDays(100),
        ]);
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $root->id)
            ->update(['updated_at' => now()->subDays(100)]);

        // Create 3 resubmissions (chain count = 3)
        for ($i = 0; $i < 3; $i++) {
            Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => -1,
                'parent' => $root->id,
                'deleted_at' => now()->subDays(31 * (3 - $i)),
            ]);
        }

        // 4th resubmission should be blocked
        $result = $this->resubmissionService->canResubmit($root->id);

        $this->assertFalse($result['can']);
        $this->assertStringContainsString('3', $result['reason']);
    }

    /**
     * Full rejection and resubmission path end-to-end.
     *
     * Validates Requirements 3.6-3.8, 13.4-13.10.
     */
public function test_full_rejection_and_resubmission_path(): void
    {
        $pesantrenUser = $this->createCompletePesantrenUser();
        $admin = $this->createAdmin();

        // Step 1: Submit pengajuan
        $akreditasi = $this->workflowService->submitPengajuan($pesantrenUser->id);
        $this->assertSame(6, (int) $akreditasi->status);

        // Step 2: Admin opens for review
        $this->workflowService->openForReview($akreditasi->id, $admin->id);
        $akreditasi->refresh();
        $this->assertSame(5, (int) $akreditasi->status);

        // Step 3: Admin rejects berkas
        $this->workflowService->rejectBerkas($akreditasi->id, $admin->id, [
            'sections' => ['profil'],
            'catatan' => 'Profil pesantren tidak lengkap.',
        ]);

        $fresh = Akreditasi::withTrashed()->find($akreditasi->id);
        $this->assertSame(-1, (int) $fresh->status);
        $this->assertNotNull($fresh->deleted_at);

        // Step 4: Cooling period blocks resubmission
        $canResult = $this->resubmissionService->canResubmit($akreditasi->id);
        $this->assertFalse($canResult['can']);

        // Step 5: Fast-forward 30 days
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $akreditasi->id)
            ->update(['updated_at' => now()->subDays(31)]);

        // Step 6: Resubmission now allowed
        $canResult = $this->resubmissionService->canResubmit($akreditasi->id);
        $this->assertTrue($canResult['can']);

        // Step 7: Create resubmission
        $newAkreditasi = $this->resubmissionService->createResubmission($akreditasi->id, $pesantrenUser->id);

        $this->assertSame(6, (int) $newAkreditasi->status);
        $this->assertSame($akreditasi->id, $newAkreditasi->parent);
    }
}
