<?php

namespace Tests\Feature\Pesantren;

use App\Models\Akreditasi;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\PesantrenUnit;
use App\Models\User;
use App\Services\PesantrenService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests for PesantrenService pengajuan (submission) methods.
 *
 * Covers:
 *   - createSubmission: data completeness gate, active submission guard, lock on create
 *   - deleteSubmission: ownership check, status guard, unlock on delete
 *   - cancelSubmission: status guard, unlock on cancel
 *   - checkDataCompleteness: full gate (profile + IPM + SDM + EDPM)
 */
class PengajuanServiceTest extends TestCase
{
    use RefreshDatabase;

    private PesantrenService $service;
    private User $user;
    private Pesantren $pesantren;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();

        $this->user = User::factory()->create(['role_id' => 3]);
        $this->pesantren = Pesantren::create([
            'user_id' => $this->user->id,
            'nama_pesantren' => 'Pesantren Test',
            'is_locked' => false,
        ]);

        $this->service = app(PesantrenService::class);
    }

    // ─── createSubmission: data completeness gate ────────────────────────────

    public function test_create_submission_returns_null_when_data_incomplete(): void
    {
        // No IPM, SDM, EDPM — data incomplete
        $result = $this->service->createSubmission($this->user->id);

        $this->assertNull($result);
        $this->assertDatabaseCount('akreditasis', 0);
    }

    public function test_create_submission_creates_akreditasi_when_data_complete(): void
    {
        $this->seedCompleteData();

        $result = $this->service->createSubmission($this->user->id);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Akreditasi::class, $result);
        $this->assertEquals(6, $result->status); // status 6 = Pengajuan
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertDatabaseCount('akreditasis', 1);
    }

    public function test_create_submission_locks_pesantren_data(): void
    {
        $this->seedCompleteData();
        $this->assertFalse($this->pesantren->is_locked);

        $this->service->createSubmission($this->user->id);

        $this->assertTrue($this->pesantren->fresh()->is_locked);
    }

    public function test_create_submission_returns_null_when_active_submission_exists(): void
    {
        $this->seedCompleteData();

        // Create first submission
        $this->service->createSubmission($this->user->id);

        // Try to create second — should fail
        $result = $this->service->createSubmission($this->user->id);

        $this->assertNull($result);
        $this->assertDatabaseCount('akreditasis', 1);
    }

    public function test_create_submission_sets_status_to_pengajuan(): void
    {
        $this->seedCompleteData();

        $result = $this->service->createSubmission($this->user->id);

        $this->assertEquals(6, $result->status);
    }

    // ─── deleteSubmission ────────────────────────────────────────────────────

    public function test_delete_submission_returns_false_for_wrong_owner(): void
    {
        $otherUser = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $otherUser->id,
            'status' => 6,
        ]);

        $result = $this->service->deleteSubmission($akreditasi->id, $this->user->id);

        $this->assertFalse($result);
        $this->assertDatabaseHas('akreditasis', ['id' => $akreditasi->id]);
    }

    public function test_delete_submission_returns_false_when_status_not_pengajuan(): void
    {
        $akreditasi = Akreditasi::create([
            'user_id' => $this->user->id,
            'status' => 5, // Assessment — not deletable
        ]);

        $result = $this->service->deleteSubmission($akreditasi->id, $this->user->id);

        $this->assertFalse($result);
        $this->assertDatabaseHas('akreditasis', ['id' => $akreditasi->id]);
    }

    public function test_delete_submission_soft_deletes_when_valid(): void
    {
        $akreditasi = Akreditasi::create([
            'user_id' => $this->user->id,
            'status' => 6,
        ]);

        $result = $this->service->deleteSubmission($akreditasi->id, $this->user->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('akreditasis', ['id' => $akreditasi->id]);
    }

    public function test_delete_submission_unlocks_pesantren_when_no_other_active(): void
    {
        $this->pesantren->update(['is_locked' => true]);
        $akreditasi = Akreditasi::create([
            'user_id' => $this->user->id,
            'status' => 6,
        ]);

        $this->service->deleteSubmission($akreditasi->id, $this->user->id);

        $this->assertFalse($this->pesantren->fresh()->is_locked);
    }

    public function test_delete_submission_keeps_lock_when_other_active_exists(): void
    {
        $this->pesantren->update(['is_locked' => true]);

        // Two active submissions
        $akreditasi1 = Akreditasi::create(['user_id' => $this->user->id, 'status' => 6]);
        $akreditasi2 = Akreditasi::create(['user_id' => $this->user->id, 'status' => 5]);

        $this->service->deleteSubmission($akreditasi1->id, $this->user->id);

        // Lock should remain because akreditasi2 (status 5) is still active
        $this->assertTrue($this->pesantren->fresh()->is_locked);
    }

    public function test_delete_submission_returns_false_when_child_resubmission_exists(): void
    {
        $parent = Akreditasi::create(['user_id' => $this->user->id, 'status' => 6]);
        // Child references parent
        Akreditasi::create(['user_id' => $this->user->id, 'status' => 6, 'parent' => $parent->id]);

        $result = $this->service->deleteSubmission($parent->id, $this->user->id);

        $this->assertFalse($result);
    }

    // ─── cancelSubmission ────────────────────────────────────────────────────

    public function test_cancel_submission_soft_deletes_pengajuan(): void
    {
        $akreditasi = Akreditasi::create([
            'user_id' => $this->user->id,
            'status' => 6,
        ]);

        $result = $this->service->cancelSubmission($akreditasi->id, $this->user->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('akreditasis', ['id' => $akreditasi->id]);
    }

    public function test_cancel_submission_unlocks_pesantren_when_no_other_active(): void
    {
        $this->pesantren->update(['is_locked' => true]);
        $akreditasi = Akreditasi::create([
            'user_id' => $this->user->id,
            'status' => 6,
        ]);

        $result = $this->service->cancelSubmission($akreditasi->id, $this->user->id);

        $this->assertTrue($result);
        $this->assertFalse($this->pesantren->fresh()->is_locked);
    }

    public function test_cancel_submission_returns_false_when_not_found(): void
    {
        $result = $this->service->cancelSubmission(99999, $this->user->id);
        $this->assertFalse($result);
    }

    public function test_cancel_submission_returns_false_on_double_cancel(): void
    {
        $akreditasi = Akreditasi::create([
            'user_id' => $this->user->id,
            'status' => 6,
        ]);

        $this->service->cancelSubmission($akreditasi->id, $this->user->id);
        // Second call — already deleted, should return false gracefully
        $result = $this->service->cancelSubmission($akreditasi->id, $this->user->id);
        $this->assertFalse($result);
    }

    // ─── checkDataCompleteness: full gate ────────────────────────────────────

    public function test_completeness_returns_empty_when_all_data_present(): void
    {
        $this->seedCompleteData();

        $missing = $this->service->checkDataCompleteness($this->user->id);

        $this->assertEmpty($missing);
    }

    public function test_completeness_reports_all_four_sections_when_nothing_filled(): void
    {
        // Seed at least one master butir so EDPM check is meaningful
        $komponen = MasterEdpmKomponen::create(['nama' => 'Test Komponen']);
        MasterEdpmButir::create(['komponen_id' => $komponen->id, 'no_sk' => '1', 'nomor_butir' => '1', 'butir_pernyataan' => 'Butir 1']);

        $newUser = User::factory()->create(['role_id' => 3]);

        $missing = $this->service->checkDataCompleteness($newUser->id);

        // Should report: profile, IPM, SDM, EDPM
        $this->assertGreaterThanOrEqual(4, count($missing));
    }

    public function test_completeness_reports_only_missing_sections(): void
    {
        // Seed master butir so EDPM check is meaningful
        $komponen = MasterEdpmKomponen::create(['nama' => 'Test Komponen']);
        MasterEdpmButir::create(['komponen_id' => $komponen->id, 'no_sk' => '1', 'nomor_butir' => '1', 'butir_pernyataan' => 'Butir 1']);
        // Fill profile and IPM but not SDM and EDPM
        $this->pesantren->update([
            'nama_pesantren' => 'Pesantren Test',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. Test',
            'provinsi' => 'Jawa Barat',
            'kota_kabupaten' => 'Bandung',
            'tahun_pendirian' => '1990',
            'nama_mudir' => 'KH. Test',
            'layanan_satuan_pendidikan' => ['sd'],
        ]);
        Ipm::create([
            'user_id' => $this->user->id,
            'nsp_file' => 'nsp.pdf',
            'lulus_santri_file' => 'lulus.pdf',
            'kurikulum_file' => 'kurikulum.pdf',
            'buku_ajar_file' => 'buku.pdf',
        ]);

        $missing = $this->service->checkDataCompleteness($this->user->id);

        // Profile and IPM should pass, SDM and EDPM should fail
        $profileMissing = collect($missing)->filter(fn ($m) => str_contains($m, 'Profil'));
        $ipmMissing = collect($missing)->filter(fn ($m) => str_contains($m, 'IPM') || str_contains($m, 'NSP'));
        $sdmMissing = collect($missing)->filter(fn ($m) => str_contains($m, 'SDM'));
        $edpmMissing = collect($missing)->filter(fn ($m) => str_contains($m, 'EDPM'));

        $this->assertEmpty($profileMissing);
        $this->assertEmpty($ipmMissing);
        $this->assertNotEmpty($sdmMissing);
        $this->assertNotEmpty($edpmMissing);
    }

    // ─── Tenant isolation ────────────────────────────────────────────────────

    public function test_create_submission_is_scoped_to_user(): void
    {
        $this->seedCompleteData();

        $otherUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $otherUser->id, 'nama_pesantren' => 'Other']);

        $this->service->createSubmission($this->user->id);

        // Other user should have no submissions
        $this->assertDatabaseMissing('akreditasis', ['user_id' => $otherUser->id]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function seedCompleteData(): void
    {
        // Complete profile
        $this->pesantren->update([
            'nama_pesantren' => 'Pesantren Al-Hikmah',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. Pesantren No. 1',
            'provinsi' => 'Jawa Barat',
            'kota_kabupaten' => 'Bandung',
            'tahun_pendirian' => '1990',
            'nama_mudir' => 'KH. Ahmad Fauzi',
            'layanan_satuan_pendidikan' => ['sd'],
        ]);

        // IPM with all files
        Ipm::create([
            'user_id' => $this->user->id,
            'nsp_file' => 'nsp.pdf',
            'lulus_santri_file' => 'lulus.pdf',
            'kurikulum_file' => 'kurikulum.pdf',
            'buku_ajar_file' => 'buku.pdf',
        ]);

        // SDM
        $unit = PesantrenUnit::create([
            'pesantren_id' => $this->pesantren->id,
            'unit' => 'sd',
            'jumlah_rombel' => 3,
        ]);
        SdmPesantren::create([
            'user_id' => $this->user->id,
            'pesantren_unit_id' => $unit->id,
            'tingkat' => 'sd',
            'santri_l' => 50,
        ]);

        // EDPM — fill all butirs
        $komponen = MasterEdpmKomponen::create(['nama' => 'MUTU LULUSAN']);
        $butir = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1',
            'butir_pernyataan' => 'Butir 1',
        ]);
        Edpm::create([
            'user_id' => $this->user->id,
            'butir_id' => $butir->id,
            'isian' => '3',
            'link' => 'https://example.com',
        ]);
    }
}
