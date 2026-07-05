<?php

namespace Tests\Feature\Pesantren;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\Ipm;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\PesantrenService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for PesantrenService IPM-related methods.
 *
 * Covers:
 *   - getIpm: auto-creates record if missing
 *   - updateIpm: happy path, lock guard, partial unlock via rejection
 *   - checkDataCompleteness: IPM file completeness
 */
class IpmServiceTest extends TestCase
{
    use RefreshDatabase;

    private PesantrenService $service;

    private User $user;

    private Pesantren $pesantren;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create(['role_id' => 3]);
        $this->pesantren = Pesantren::create([
            'user_id' => $this->user->id,
            'nama_pesantren' => 'Pesantren Test',
            'is_locked' => false,
        ]);

        $this->service = app(PesantrenService::class);
    }

    // ─── getIpm ──────────────────────────────────────────────────────────────

    public function test_get_ipm_returns_existing_record(): void
    {
        $ipm = Ipm::create(['user_id' => $this->user->id, 'nsp_file' => 'existing.pdf']);

        $result = $this->service->getIpm($this->user->id);

        $this->assertEquals($ipm->id, $result->id);
        $this->assertEquals('existing.pdf', $result->nsp_file);
    }

    public function test_get_ipm_creates_record_when_missing(): void
    {
        $this->assertDatabaseCount('ipms', 0);

        $result = $this->service->getIpm($this->user->id);

        $this->assertNotNull($result);
        $this->assertEquals($this->user->id, $result->user_id);
        $this->assertDatabaseCount('ipms', 1);
    }

    // ─── updateIpm: happy path ────────────────────────────────────────────────

    public function test_update_ipm_persists_file_paths_when_unlocked(): void
    {
        Ipm::create(['user_id' => $this->user->id]);

        $result = $this->service->updateIpm($this->user->id, [
            'nsp_file' => 'ipm_docs/nsp_abc123.pdf',
            'lulus_santri_file' => 'ipm_docs/lulus_abc123.pdf',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('ipms', [
            'user_id' => $this->user->id,
            'nsp_file' => 'ipm_docs/nsp_abc123.pdf',
            'lulus_santri_file' => 'ipm_docs/lulus_abc123.pdf',
        ]);
    }

    public function test_update_ipm_updates_only_provided_fields(): void
    {
        Ipm::create([
            'user_id' => $this->user->id,
            'nsp_file' => 'old_nsp.pdf',
            'kurikulum_file' => 'old_kurikulum.pdf',
        ]);

        $this->service->updateIpm($this->user->id, [
            'nsp_file' => 'new_nsp.pdf',
        ]);

        $this->assertDatabaseHas('ipms', [
            'user_id' => $this->user->id,
            'nsp_file' => 'new_nsp.pdf',
            'kurikulum_file' => 'old_kurikulum.pdf', // unchanged
        ]);
    }

    // ─── updateIpm: lock guard ────────────────────────────────────────────────

    public function test_update_ipm_returns_false_when_locked_and_no_active_rejection(): void
    {
        $this->pesantren->update(['is_locked' => true]);
        Ipm::create(['user_id' => $this->user->id]);

        $result = $this->service->updateIpm($this->user->id, [
            'nsp_file' => 'new_nsp.pdf',
        ]);

        $this->assertFalse($result);
    }

    public function test_update_ipm_does_not_modify_db_when_locked(): void
    {
        $this->pesantren->update(['is_locked' => true]);
        Ipm::create([
            'user_id' => $this->user->id,
            'nsp_file' => 'original_nsp.pdf',
        ]);

        $this->service->updateIpm($this->user->id, [
            'nsp_file' => 'attempted_override.pdf',
        ]);

        $this->assertDatabaseHas('ipms', [
            'user_id' => $this->user->id,
            'nsp_file' => 'original_nsp.pdf',
        ]);
    }

    public function test_update_ipm_allows_only_unlocked_rejection_sections_when_locked_in_assessment(): void
    {
        $this->pesantren->update(['is_locked' => true]);
        Ipm::create([
            'user_id' => $this->user->id,
            'nsp_file' => 'old_nsp.pdf',
            'kurikulum_file' => 'old_kurikulum.pdf',
        ]);
        $akreditasi = Akreditasi::create([
            'user_id' => $this->user->id,
            'status' => Akreditasi::STATUS_ASSESSMENT,
        ]);
        $asesor = User::factory()->create(['role_id' => 2]);
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesor->id,
            'type' => 'asesor',
            'items' => ['ipm.nsp'],
            'explanation' => 'NSP perlu diperbaiki',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'status' => 'pending',
        ]);

        $result = $this->service->updateIpm($this->user->id, [
            'nsp_file' => 'new_nsp.pdf',
            'kurikulum_file' => 'new_kurikulum.pdf',
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('ipms', [
            'user_id' => $this->user->id,
            'nsp_file' => 'new_nsp.pdf',
            'kurikulum_file' => 'old_kurikulum.pdf',
        ]);
    }

    // ─── checkDataCompleteness: IPM files ────────────────────────────────────

    public function test_completeness_reports_missing_nsp_file(): void
    {
        Ipm::create([
            'user_id' => $this->user->id,
            'nsp_file' => null,
            'lulus_santri_file' => 'lulus.pdf',
            'kurikulum_file' => 'kurikulum.pdf',
            'buku_ajar_file' => 'buku.pdf',
        ]);

        $missing = $this->service->checkDataCompleteness($this->user->id);

        $this->assertTrue(collect($missing)->contains(fn ($m) => str_contains($m, 'NSP')));
    }

    public function test_completeness_reports_all_four_missing_ipm_files(): void
    {
        Ipm::create(['user_id' => $this->user->id]); // all files null

        $missing = $this->service->checkDataCompleteness($this->user->id);

        $ipmMissing = collect($missing)->filter(fn ($m) => str_contains($m, 'IPM') || str_contains($m, 'NSP') || str_contains($m, 'Santri') || str_contains($m, 'Kurikulum') || str_contains($m, 'Buku'));
        $this->assertGreaterThanOrEqual(4, $ipmMissing->count());
    }

    public function test_completeness_passes_ipm_check_when_all_files_present(): void
    {
        // Fill profile so profile check passes
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

        // IPM-specific items should not appear in missing list
        $ipmMissing = collect($missing)->filter(fn ($m) => str_contains($m, 'NSP') ||
            str_contains($m, 'Santri') ||
            str_contains($m, 'Kurikulum') ||
            str_contains($m, 'Buku Ajar')
        );
        $this->assertEmpty($ipmMissing);
    }
}
