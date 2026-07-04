<?php

namespace Tests\Feature\AkreditasiWorkflow;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
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
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkflowE2ESmokeTest extends TestCase
{
    use RefreshDatabase;

    private AkreditasiWorkflowService $workflowService;

    /** @var array<int, MasterEdpmButir> */
    private array $butirs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        Notification::fake();
        $this->workflowService = app(AkreditasiWorkflowService::class);
        $this->seedMasterEdpmData();
    }

    public function test_e2e_happy_path_reaches_selesai(): void
    {
        $pesantren = $this->createCompletePesantrenUser();
        $admin = $this->createAdmin();
        $asesor1 = $this->createAsesor();
        $asesor2 = $this->createAsesor();

        $akreditasi = $this->workflowService->submitPengajuan($pesantren->id);
        $this->workflowService->openForReview($akreditasi->id, $admin->id);
        $this->workflowService->approveBerkas($akreditasi->id, $admin->id, $asesor1->id, $asesor2->id);

        $primary = Assessment::where('akreditasi_id', $akreditasi->id)->where('tipe', 1)->firstOrFail();
        $this->workflowService->scheduleVisitasi($akreditasi->id, $asesor1->id, ['tanggal_mulai' => now()->addDays(8)->toDateString(), 'tanggal_akhir' => now()->addDays(9)->toDateString(), 'catatan_visitasi' => 'Jadwal visitasi e2e']);
        $akreditasi->fresh()->update(['tgl_visitasi' => now()->subDay()->toDateString(), 'tgl_visitasi_akhir' => now()->toDateString()]);
        $this->workflowService->confirmVisitasiSelesai($akreditasi->id, $asesor1->id);

        $this->saveAllNaAsFinal($akreditasi->id, $asesor1->id, 1, 4, $pesantren->id);
        $this->saveAllNaAsFinal($akreditasi->id, $asesor2->id, 2, 4, $pesantren->id);
        $this->saveAllNkAndNv($akreditasi->id, $pesantren->id, $asesor1->id, 4);
        $this->saveAllCatatanRekomendasi($akreditasi->id, $pesantren->id, $asesor1->id);

        $akreditasi->update([
            'laporan_visitasi_asesor1' => 'laporan/asesor1.pdf',
            'laporan_visitasi_asesor2' => 'laporan/asesor2.pdf',
            'laporan_visitasi_kelompok' => 'laporan/kelompok.pdf',
            'kartu_kendali' => 'kartu/kendali.pdf',
        ]);

        $this->workflowService->finalizeAssessorScoring($akreditasi->id, $asesor1->id);
        $this->workflowService->issueSK($akreditasi->fresh()->id, $admin->id, [
            'nomor_sk' => 'SK/E2E/001/2026',
            'masa_berlaku' => now()->toDateString(),
            'masa_berlaku_akhir' => now()->addYears(5)->toDateString(),
            'sertifikat_path' => 'sertifikat/e2e.pdf',
            'catatan_rekomendasi_admin' => 'Lulus happy path e2e.',
        ]);

        $akreditasi->refresh();
        $primary->refresh();

        $this->assertSame(0, (int) $akreditasi->status);
        $this->assertTrue((bool) $akreditasi->is_nv_final);
        $this->assertSame('SK/E2E/001/2026', $akreditasi->nomor_sk);
        $this->assertNotNull($akreditasi->visitasi_confirmed_at);
    }

    public function test_e2e_negative_path_blocks_sk_when_post_visitasi_documents_missing(): void
    {
        $pesantren = $this->createCompletePesantrenUser();
        $admin = $this->createAdmin();
        $asesor1 = $this->createAsesor();
        $asesor2 = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantren->id,
            'status' => 1,
        ]);

        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => Asesor::where('user_id', $asesor1->id)->value('id'), 'tipe' => 1, 'tanggal_mulai' => now(), 'tanggal_berakhir' => now()->addDays(9)]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => Asesor::where('user_id', $asesor2->id)->value('id'), 'tipe' => 2, 'tanggal_mulai' => now(), 'tanggal_berakhir' => now()->addDays(9)]);

        $this->saveAllNkAndNv($akreditasi->id, $pesantren->id, $asesor1->id, 3);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/dokumen/i');

        $this->workflowService->issueSK($akreditasi->id, $admin->id, [
            'nomor_sk' => 'SK/E2E/FAIL/2026',
            'masa_berlaku' => now()->toDateString(),
            'masa_berlaku_akhir' => now()->addYears(5)->toDateString(),
            'sertifikat_path' => 'sertifikat/e2e-fail.pdf',
        ]);
    }

    private function seedMasterEdpmData(): void
    {
        foreach ([8, 10, 10, 12, 22] as $index => $count) {
            $komponen = MasterEdpmKomponen::create([
                'nama' => 'Komponen '.($index + 1),
                'ipr' => $index === 4 ? 1 : null,
            ]);

            for ($i = 1; $i <= $count; $i++) {
                $this->butirs[] = MasterEdpmButir::create([
                    'komponen_id' => $komponen->id,
                    'no_sk' => (string) count($this->butirs + [1]),
                    'nomor_butir' => ($index + 1).'.'.$i,
                    'butir_pernyataan' => 'Butir '.($index + 1).'.'.$i,
                ]);
            }
        }
    }

    private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren E2E',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. E2E',
            'provinsi' => 'Jawa Tengah',
            'kota_kabupaten' => 'Solo',
            'tahun_pendirian' => '2001',
            'nama_mudir' => 'Mudir E2E',
            'layanan_satuan_pendidikan' => ['spm'],
            'is_locked' => false,
        ]);

        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'ipm/nsp.pdf',
            'lulus_santri_file' => 'ipm/lulus.pdf',
            'kurikulum_file' => 'ipm/kurikulum.pdf',
            'buku_ajar_file' => 'ipm/buku.pdf',
        ]);

        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'spm',
            'santri_l' => 10,
            'santri_p' => 10,
            'ustadz_dirosah_l' => 3,
            'ustadz_dirosah_p' => 3,
        ]);

        foreach ($this->butirs as $butir) {
            Edpm::create([
                'user_id' => $user->id,
                'butir_id' => $butir->id,
                'isian' => '4',
            ]);
        }

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
            'nama_dengan_gelar' => 'Asesor E2E, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor E2E',
        ]);

        return $user;
    }

    private function saveAllNaAsFinal(int $akreditasiId, int $asesorUserId, int $tipe, int $naValue, int $pesantrenUserId): void
    {
        $asesorId = Asesor::where('user_id', $asesorUserId)->value('id');

        foreach ($this->butirs as $butir) {
            AkreditasiEdpm::updateOrCreate(
                [
                    'akreditasi_id' => $akreditasiId,
                    'butir_id' => $butir->id,
                    'asesor_id' => $asesorId,
                ],
                [
                    'pesantren_id' => $pesantrenUserId,
                    'isian' => $naValue,
                    'catatan' => 'NA final',
                    'is_final' => true,
                ]
            );
        }
    }

    private function saveAllCatatanRekomendasi(int $akreditasiId, int $pesantrenUserId, int $asesorUserId): void
    {
        $asesorId = Asesor::where('user_id', $asesorUserId)->value('id');

        foreach (MasterEdpmKomponen::whereNull('ipr')->take(4)->get() as $komponen) {
            AkreditasiEdpmCatatan::updateOrCreate(
                [
                    'akreditasi_id' => $akreditasiId,
                    'komponen_id' => $komponen->id,
                    'asesor_id' => $asesorId,
                ],
                [
                    'pesantren_id' => $pesantrenUserId,
                    'catatan' => 'Catatan rekomendasi final',
                    'rekomendasi' => 'Rekomendasi final',
                ]
            );
        }
    }

    private function saveAllNkAndNv(int $akreditasiId, int $pesantrenUserId, int $asesorUserId, int $value): void
    {
        $asesorId = Asesor::where('user_id', $asesorUserId)->value('id');

        foreach ($this->butirs as $butir) {
            AkreditasiEdpm::updateOrCreate(
                [
                    'akreditasi_id' => $akreditasiId,
                    'butir_id' => $butir->id,
                    'asesor_id' => $asesorId,
                ],
                [
                    'pesantren_id' => $pesantrenUserId,
                    'isian' => $value,
                    'nk' => $value,
                    'nv' => $value,
                    'is_final' => true,
                    'catatan' => 'NK NV final',
                ]
            );
        }
    }
}


