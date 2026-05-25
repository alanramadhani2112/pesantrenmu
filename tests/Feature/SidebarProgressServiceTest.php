<?php

namespace Tests\Feature;

use App\Models\Ipm;
use App\Models\Edpm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Services\SidebarProgressService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for SidebarProgressService.
 *
 * Tests edge cases and specific examples for progress status calculation.
 *
 * Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5
 */
class SidebarProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SidebarProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = app(SidebarProgressService::class);
    }

    // ─── Edge Case: User with no Pesantren record → all 'not_started' ───────────

    public function test_user_with_no_pesantren_record_returns_all_not_started(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $progress = $this->service->getProgressForUser($user->id);

        $this->assertSame('not_started', $progress['profil']);
        $this->assertSame('not_started', $progress['ipm']);
        $this->assertSame('not_started', $progress['sdm']);
        $this->assertSame('not_started', $progress['edpm']);
    }

    public function test_user_with_no_data_profil_section_returns_correct_counts(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $result = $this->service->getSectionProgress($user->id, 'profil');

        $this->assertSame('not_started', $result['status']);
        $this->assertSame(0, $result['filled']);
        $this->assertSame(8, $result['total']);
    }

    public function test_user_with_no_data_ipm_section_returns_correct_counts(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $result = $this->service->getSectionProgress($user->id, 'ipm');

        $this->assertSame('not_started', $result['status']);
        $this->assertSame(0, $result['filled']);
        $this->assertSame(4, $result['total']);
    }

    public function test_user_with_no_data_sdm_section_returns_correct_counts(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $result = $this->service->getSectionProgress($user->id, 'sdm');

        $this->assertSame('not_started', $result['status']);
        $this->assertSame(0, $result['filled']);
        $this->assertSame(1, $result['total']);
    }

    // ─── Edge Case: User with complete data → all 'complete' ────────────────────

    public function test_user_with_complete_data_returns_all_complete(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        // Create complete Pesantren record
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Al-Hikmah',
            'ns_pesantren' => '123456789',
            'alamat' => 'Jl. Raya No. 1',
            'provinsi' => 'Jawa Timur',
            'kota_kabupaten' => 'Surabaya',
            'tahun_pendirian' => '1998',
            'nama_mudir' => 'Ahmad Mudir',
            'layanan_satuan_pendidikan' => ['spm'],
        ]);

        // Create complete IPM record
        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'uploads/nsp_file.pdf',
            'lulus_santri_file' => 'uploads/lulus_santri.pdf',
            'kurikulum_file' => 'uploads/kurikulum.pdf',
            'buku_ajar_file' => 'uploads/buku_ajar.pdf',
        ]);

        // Create SDM record
        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'spm',
        ]);

        $progress = $this->service->getProgressForUser($user->id);

        $this->assertSame('complete', $progress['profil']);
        $this->assertSame('complete', $progress['ipm']);
        $this->assertSame('complete', $progress['sdm']);
        $this->assertSame('not_started', $progress['edpm']);
    }

    public function test_user_with_complete_profil_returns_correct_counts(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Al-Hikmah',
            'ns_pesantren' => '123456789',
            'alamat' => 'Jl. Raya No. 1',
            'provinsi' => 'Jawa Timur',
            'kota_kabupaten' => 'Surabaya',
            'tahun_pendirian' => '1998',
            'nama_mudir' => 'Ahmad Mudir',
            'layanan_satuan_pendidikan' => ['spm'],
        ]);

        $result = $this->service->getSectionProgress($user->id, 'profil');

        $this->assertSame('complete', $result['status']);
        $this->assertSame(8, $result['filled']);
        $this->assertSame(8, $result['total']);
    }

    public function test_user_with_complete_ipm_returns_correct_counts(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'uploads/nsp_file.pdf',
            'lulus_santri_file' => 'uploads/lulus_santri.pdf',
            'kurikulum_file' => 'uploads/kurikulum.pdf',
            'buku_ajar_file' => 'uploads/buku_ajar.pdf',
        ]);

        $result = $this->service->getSectionProgress($user->id, 'ipm');

        $this->assertSame('complete', $result['status']);
        $this->assertSame(4, $result['filled']);
        $this->assertSame(4, $result['total']);
    }

    public function test_user_with_sdm_record_returns_complete(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'spm',
        ]);

        $result = $this->service->getSectionProgress($user->id, 'sdm');

        $this->assertSame('complete', $result['status']);
        $this->assertSame(1, $result['filled']);
        $this->assertSame(1, $result['total']);
    }

    public function test_user_with_complete_edpm_returns_correct_counts(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $komponen = MasterEdpmKomponen::create(['nama' => 'MUTU LULUSAN']);
        $butir1 = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1',
            'butir_pernyataan' => 'Butir 1',
        ]);
        $butir2 = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '2',
            'nomor_butir' => '2',
            'butir_pernyataan' => 'Butir 2',
        ]);

        Edpm::create(['user_id' => $user->id, 'butir_id' => $butir1->id, 'isian' => 'A']);
        Edpm::create(['user_id' => $user->id, 'butir_id' => $butir2->id, 'isian' => 'B']);

        $result = $this->service->getSectionProgress($user->id, 'edpm');

        $this->assertSame('complete', $result['status']);
        $this->assertSame(2, $result['filled']);
        $this->assertSame(2, $result['total']);
    }

    // ─── Specific Example: User with Profil filled but no IPM → mixed statuses ─

    public function test_user_with_profil_filled_but_no_ipm_returns_mixed_statuses(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        // Create complete Pesantren record (profil filled)
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Al-Hikmah',
            'ns_pesantren' => '123456789',
            'alamat' => 'Jl. Raya No. 1',
            'provinsi' => 'Jawa Timur',
            'kota_kabupaten' => 'Surabaya',
            'tahun_pendirian' => '1998',
            'nama_mudir' => 'Ahmad Mudir',
            'layanan_satuan_pendidikan' => ['spm'],
        ]);

        // No IPM record, no SDM record

        $progress = $this->service->getProgressForUser($user->id);

        $this->assertSame('complete', $progress['profil']);
        $this->assertSame('not_started', $progress['ipm']);
        $this->assertSame('not_started', $progress['sdm']);
        $this->assertSame('not_started', $progress['edpm']);
    }

    public function test_user_with_partial_profil_and_partial_ipm(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        // Create partial Pesantren record (only some fields filled)
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Al-Hikmah',
            'ns_pesantren' => '123456789',
            'alamat' => '',
            'provinsi' => '',
            'kota_kabupaten' => '',
            'tahun_pendirian' => '',
            'nama_mudir' => '',
            'layanan_satuan_pendidikan' => [],
        ]);

        // Create partial IPM record (only some files uploaded)
        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'uploads/nsp_file.pdf',
            'lulus_santri_file' => null,
            'kurikulum_file' => null,
            'buku_ajar_file' => null,
        ]);

        $progress = $this->service->getProgressForUser($user->id);

        $this->assertSame('incomplete', $progress['profil']);
        $this->assertSame('incomplete', $progress['ipm']);
        $this->assertSame('not_started', $progress['sdm']);

        // Verify specific counts
        $profilResult = $this->service->getSectionProgress($user->id, 'profil');
        $this->assertSame(2, $profilResult['filled']); // nama_pesantren + ns_pesantren
        $this->assertSame(8, $profilResult['total']);

        $ipmResult = $this->service->getSectionProgress($user->id, 'ipm');
        $this->assertSame(1, $ipmResult['filled']); // nsp_file only
        $this->assertSame(4, $ipmResult['total']);
    }

    // ─── Additional edge cases ──────────────────────────────────────────────────

    public function test_invalid_section_returns_not_started_with_zero_counts(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $result = $this->service->getSectionProgress($user->id, 'invalid_section');

        $this->assertSame('not_started', $result['status']);
        $this->assertSame(0, $result['filled']);
        $this->assertSame(0, $result['total']);
    }

    public function test_pesantren_with_empty_string_fields_treated_as_not_filled(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        // All fields are empty strings — should be treated as not filled
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => '',
            'ns_pesantren' => '',
            'alamat' => '',
            'provinsi' => '',
            'kota_kabupaten' => '',
            'tahun_pendirian' => '',
            'nama_mudir' => '',
            'layanan_satuan_pendidikan' => [],
        ]);

        $result = $this->service->getSectionProgress($user->id, 'profil');

        $this->assertSame('not_started', $result['status']);
        $this->assertSame(0, $result['filled']);
        $this->assertSame(8, $result['total']);
    }

    public function test_multiple_sdm_records_still_returns_complete(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        SdmPesantren::create(['user_id' => $user->id, 'tingkat' => 'spm']);
        SdmPesantren::create(['user_id' => $user->id, 'tingkat' => 'ma']);

        $result = $this->service->getSectionProgress($user->id, 'sdm');

        $this->assertSame('complete', $result['status']);
        $this->assertSame(1, $result['filled']);
        $this->assertSame(1, $result['total']);
    }
}
