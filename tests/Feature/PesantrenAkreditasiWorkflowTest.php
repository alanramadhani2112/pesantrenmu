<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Services\PesantrenService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PesantrenAkreditasiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    public function test_pengajuan_tidak_dibuat_jika_data_pesantren_belum_lengkap(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $akreditasi = $this->service()->createSubmission($user->id);

        $this->assertNull($akreditasi);
        $this->assertDatabaseCount('akreditasis', 0);
    }

    public function test_pengajuan_dibuat_dan_profil_dikunci_jika_data_lengkap(): void
    {
        $user = $this->createCompletePesantrenUser();

        $akreditasi = $this->service()->createSubmission($user->id);

        $this->assertNotNull($akreditasi);
        $this->assertSame(6, $akreditasi->status);
        $this->assertTrue($user->pesantren->fresh()->is_locked);
        $this->assertDatabaseHas('akreditasis', [
            'id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 6,
        ]);
    }

    public function test_pengajuan_aktif_tidak_bisa_diduplikasi(): void
    {
        $user = $this->createCompletePesantrenUser();

        Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $akreditasi = $this->service()->createSubmission($user->id);

        $this->assertNull($akreditasi);
        $this->assertSame(1, Akreditasi::where('user_id', $user->id)->count());
    }

    public function test_pengajuan_baru_ditolak_saat_masih_ada_proses_pasca_visitasi(): void
    {
        $user = $this->createCompletePesantrenUser();
        Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $akreditasi = $this->service()->createSubmission($user->id);

        $this->assertNull($akreditasi);
        $this->assertSame(1, Akreditasi::where('user_id', $user->id)->count());
    }

    private function service(): PesantrenService
    {
        return app(PesantrenService::class);
    }

    private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren TDD',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. Pendidikan No. 12',
            'provinsi' => 'Jawa Tengah',
            'kota_kabupaten' => 'Kota Surakarta',
            'tahun_pendirian' => '1998',
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
            'santri_l' => 120,
            'santri_p' => 95,
            'ustadz_dirosah_l' => 8,
            'ustadz_dirosah_p' => 4,
            'ustadz_tsanawiyah_l' => 3,
            'ustadz_tsanawiyah_p' => 2,
            'ustadz_aliyah_l' => 2,
            'ustadz_aliyah_p' => 1,
        ]);

        $komponen = MasterEdpmKomponen::create(['nama' => 'Standar Isi']);
        $butir = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Pesantren memiliki dokumen kurikulum.',
        ]);

        Edpm::create([
            'user_id' => $user->id,
            'butir_id' => $butir->id,
            'isian' => '4',
        ]);

        return $user->refresh();
    }
}
