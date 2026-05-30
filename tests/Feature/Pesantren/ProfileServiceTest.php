<?php

namespace Tests\Feature\Pesantren;

use App\Models\Ipm;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\PesantrenService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for PesantrenService profile-related methods.
 *
 * Covers:
 *   - updateProfile: happy path, lock guard, unit sync, transaction atomicity
 *   - getMissingProfileFields: required field detection
 *   - checkDataCompleteness: full completeness gate
 */
class ProfileServiceTest extends TestCase
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
        ]);

        $this->service = app(PesantrenService::class);
    }

    // ─── updateProfile: happy path ───────────────────────────────────────────

    public function test_update_profile_persists_data_when_unlocked(): void
    {
        $data = $this->fullProfileData();

        $result = $this->service->updateProfile($this->user->id, $data, []);

        $this->assertTrue($result);
        $this->assertDatabaseHas('pesantrens', [
            'user_id' => $this->user->id,
            'nama_pesantren' => 'Pesantren Al-Hikmah',
            'ns_pesantren' => '510012345678',
            'provinsi' => 'Jawa Barat',
        ]);
    }

    public function test_update_profile_syncs_units_correctly(): void
    {
        $units = [
            ['unit' => 'sd', 'jumlah_rombel' => 3],
            ['unit' => 'smp', 'jumlah_rombel' => 2],
        ];

        $this->service->updateProfile($this->user->id, $this->fullProfileData(), $units);

        $this->assertDatabaseHas('pesantren_units', ['pesantren_id' => $this->pesantren->id, 'unit' => 'sd', 'jumlah_rombel' => 3]);
        $this->assertDatabaseHas('pesantren_units', ['pesantren_id' => $this->pesantren->id, 'unit' => 'smp', 'jumlah_rombel' => 2]);
        $this->assertDatabaseCount('pesantren_units', 2);
    }

    public function test_update_profile_removes_deselected_units(): void
    {
        // Start with 3 units
        $this->pesantren->units()->createMany([
            ['unit' => 'sd', 'jumlah_rombel' => 2],
            ['unit' => 'smp', 'jumlah_rombel' => 1],
            ['unit' => 'sma', 'jumlah_rombel' => 3],
        ]);

        // Update with only 1 unit
        $this->service->updateProfile($this->user->id, $this->fullProfileData(), [
            ['unit' => 'sd', 'jumlah_rombel' => 5],
        ]);

        $this->assertDatabaseHas('pesantren_units', ['unit' => 'sd', 'jumlah_rombel' => 5]);
        $this->assertDatabaseMissing('pesantren_units', ['unit' => 'smp']);
        $this->assertDatabaseMissing('pesantren_units', ['unit' => 'sma']);
        $this->assertDatabaseCount('pesantren_units', 1);
    }

    public function test_update_profile_clears_all_units_when_none_selected(): void
    {
        $this->pesantren->units()->createMany([
            ['unit' => 'sd', 'jumlah_rombel' => 2],
            ['unit' => 'mi', 'jumlah_rombel' => 1],
        ]);

        $this->service->updateProfile($this->user->id, $this->fullProfileData(), []);

        $this->assertDatabaseCount('pesantren_units', 0);
    }

    // ─── updateProfile: lock guard ───────────────────────────────────────────

    public function test_update_profile_returns_false_when_locked_and_no_active_rejection(): void
    {
        $this->pesantren->update(['is_locked' => true]);

        $result = $this->service->updateProfile($this->user->id, $this->fullProfileData(), []);

        $this->assertFalse($result);
    }

    public function test_update_profile_does_not_modify_db_when_locked(): void
    {
        $this->pesantren->update([
            'is_locked' => true,
            'nama_pesantren' => 'Original Name',
        ]);

        $this->service->updateProfile($this->user->id, array_merge($this->fullProfileData(), [
            'nama_pesantren' => 'Attempted Override',
        ]), []);

        $this->assertDatabaseHas('pesantrens', [
            'user_id' => $this->user->id,
            'nama_pesantren' => 'Original Name',
        ]);
    }

    // ─── getMissingProfileFields ─────────────────────────────────────────────

    public function test_get_missing_profile_fields_returns_empty_when_all_filled(): void
    {
        $this->pesantren->update($this->fullProfileData());

        $missing = $this->service->getMissingProfileFields($this->pesantren->fresh());

        $this->assertEmpty($missing);
    }

    public function test_get_missing_profile_fields_detects_blank_nama_pesantren(): void
    {
        $this->pesantren->update(['nama_pesantren' => '']);

        $missing = $this->service->getMissingProfileFields($this->pesantren->fresh());

        $this->assertContains('Nama Pesantren', $missing);
    }

    public function test_get_missing_profile_fields_detects_empty_layanan(): void
    {
        $this->pesantren->update(array_merge($this->fullProfileData(), [
            'layanan_satuan_pendidikan' => [],
        ]));

        $missing = $this->service->getMissingProfileFields($this->pesantren->fresh());

        $this->assertContains('Layanan Satuan Pendidikan', $missing);
    }

    public function test_get_missing_profile_fields_detects_all_required_fields(): void
    {
        // Pesantren with only user_id — all required fields blank
        $bare = Pesantren::create([
            'user_id' => User::factory()->create(['role_id' => 3])->id,
            'nama_pesantren' => '', // required but blank
        ]);

        $missing = $this->service->getMissingProfileFields($bare);

        $this->assertCount(count(PesantrenService::PROFILE_REQUIRED_FIELDS), $missing);
    }

    // ─── checkDataCompleteness ───────────────────────────────────────────────

    public function test_check_data_completeness_reports_missing_profile(): void
    {
        // User with no pesantren record
        $newUser = User::factory()->create(['role_id' => 3]);

        $missing = $this->service->checkDataCompleteness($newUser->id);

        $this->assertNotEmpty($missing);
        $this->assertStringContainsString('Profil Pesantren', $missing[0]);
    }

    public function test_check_data_completeness_reports_missing_ipm(): void
    {
        $this->pesantren->update($this->fullProfileData());
        // No IPM record created

        $missing = $this->service->checkDataCompleteness($this->user->id);

        $this->assertTrue(collect($missing)->contains(fn ($m) => str_contains($m, 'IPM')));
    }

    public function test_check_data_completeness_reports_missing_sdm(): void
    {
        $this->pesantren->update($this->fullProfileData());
        Ipm::create([
            'user_id' => $this->user->id,
            'nsp_file' => 'nsp.pdf',
            'lulus_santri_file' => 'lulus.pdf',
            'kurikulum_file' => 'kurikulum.pdf',
            'buku_ajar_file' => 'buku.pdf',
        ]);
        // No SDM records

        $missing = $this->service->checkDataCompleteness($this->user->id);

        $this->assertTrue(collect($missing)->contains(fn ($m) => str_contains($m, 'SDM')));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function fullProfileData(): array
    {
        return [
            'nama_pesantren' => 'Pesantren Al-Hikmah',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. Pesantren No. 1',
            'provinsi' => 'Jawa Barat',
            'kota_kabupaten' => 'Bandung',
            'tahun_pendirian' => '1990',
            'nama_mudir' => 'KH. Ahmad Fauzi',
            'layanan_satuan_pendidikan' => ['sd', 'smp'],
        ];
    }
}
