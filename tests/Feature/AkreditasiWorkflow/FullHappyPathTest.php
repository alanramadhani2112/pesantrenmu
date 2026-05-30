<?php

namespace Tests\Feature\AkreditasiWorkflow;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
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
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Task 14.1 — Integration test for full happy path end-to-end.
 *
 * Validates the complete workflow:
 *   Pengajuan (6) → Verifikasi Berkas (5) → Assessment (4) → Visitasi (3)
 *   → Pasca Visitasi (2) → Validasi Admin (1) → Selesai (0)
 *
 * Validates Requirements 2, 3, 5, 6, 7, 8, 9, 10, 11.
 */
class FullHappyPathTest extends TestCase
{
    use RefreshDatabase;

    private AkreditasiWorkflowService $workflowService;

    /** @var array<int, MasterEdpmButir> */
    private array $butirs = [];

    /** @var array<int, MasterEdpmKomponen> */
    private array $komponens = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->workflowService = app(AkreditasiWorkflowService::class);
        $this->seedMasterEdpmData();
    }

    // =========================================================================
    // Master data seeding
    // =========================================================================

    /**
     * Seed 62 butir across 5 komponens:
     *   IK: Mutu Lulusan (8), Proses Pembelajaran (10), Mutu Ustaz (10), Manajemen Pesantren (12)
     *   IPR: 22 butir (ipr=1)
     */
    private function seedMasterEdpmData(): void
    {
        $komponenConfig = [
            ['nama' => 'MUTU LULUSAN',        'count' => 8,  'ipr' => null],
            ['nama' => 'PROSES PEMBELAJARAN', 'count' => 10, 'ipr' => null],
            ['nama' => 'MUTU USTAZ',          'count' => 10, 'ipr' => null],
            ['nama' => 'MANAJEMEN PESANTREN', 'count' => 12, 'ipr' => null],
            ['nama' => 'IPR',                 'count' => 22, 'ipr' => 1],
        ];

        $butirSeq = 1;
        foreach ($komponenConfig as $kConfig) {
            $komponen = MasterEdpmKomponen::create([
                'nama' => $kConfig['nama'],
                'ipr' => $kConfig['ipr'],
            ]);
            $this->komponens[] = $komponen;

            for ($i = 1; $i <= $kConfig['count']; $i++) {
                $butir = MasterEdpmButir::create([
                    'komponen_id' => $komponen->id,
                    'no_sk' => (string) $butirSeq,
                    'nomor_butir' => $butirSeq.'.'.$i,
                    'butir_pernyataan' => "Butir {$butirSeq}.{$i} pernyataan.",
                ]);
                $this->butirs[] = $butir;
                $butirSeq++;
            }
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Happy Path',
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

        // Seed EDPM data for the pesantren (at least one butir)
        foreach ($this->butirs as $butir) {
            Edpm::create([
                'user_id' => $user->id,
                'butir_id' => $butir->id,
                'isian' => '3',
            ]);
        }

        return $user->refresh();
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
     * Save all 62 NA values as Final for a given asesor.
     * Also saves NK and catatan_butir for Asesor_1 (tipe=1).
     *
     * @param  int  $asesorUserId  The user_id of the asesor
     */
    private function saveAllNaAsFinal(
        int $akreditasiId,
        int $asesorUserId,
        int $tipe,
        int $naValue = 3,
        int $pesantrenUserId = 0
    ): void {
        // Resolve pesantren_id from the akreditasi if not provided
        if ($pesantrenUserId === 0) {
            $akreditasi = Akreditasi::withTrashed()->find($akreditasiId);
            $pesantrenUserId = $akreditasi ? $akreditasi->user_id : $asesorUserId;
        }

        $now = now()->toDateTimeString();
        $asesorModelId = Asesor::where('user_id', $asesorUserId)->value('id') ?? $asesorUserId;

        foreach ($this->butirs as $butir) {
            $data = [
                'akreditasi_id' => $akreditasiId,
                'pesantren_id' => $pesantrenUserId,
                'asesor_id' => $asesorModelId,
                'butir_id' => $butir->id,
                'isian' => $naValue,
                'is_final' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($tipe === 1) {
                $data['nk'] = $naValue;
                $data['catatan'] = 'Catatan butir '.$butir->id;
                $data['delta'] = 0;
            }

            DB::table('akreditasi_edpms')->insert($data);
        }
    }

    /**
     * Save all 4 catatan_rekomendasi for Asesor_1.
     */
    private function saveAllCatatanRekomendasi(int $akreditasiId, int $asesor1UserId): void
    {
        $now = now()->toDateTimeString();

        // Resolve pesantren_id from the akreditasi
        $akreditasi = Akreditasi::withTrashed()->find($akreditasiId);
        $pesantrenUserId = $akreditasi ? $akreditasi->user_id : $asesor1UserId;
        $asesorModelId = Asesor::where('user_id', $asesor1UserId)->value('id') ?? $asesor1UserId;

        // Use the first 4 komponens (IK komponens)
        $ikKomponens = array_slice($this->komponens, 0, 4);
        foreach ($ikKomponens as $komponen) {
            DB::table('akreditasi_edpm_catatans')->insert([
                'akreditasi_id' => $akreditasiId,
                'pesantren_id' => $pesantrenUserId,
                'asesor_id' => $asesorModelId,
                'komponen_id' => $komponen->id,
                'catatan' => 'Catatan rekomendasi untuk komponen '.$komponen->nama,
                'rekomendasi' => 'Rekomendasi untuk komponen '.$komponen->nama,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Save all 62 NV values as Final for Admin.
     */
    private function saveAllNvAsFinal(int $akreditasiId, int $nvValue = 3): void
    {
        AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->update(['nv' => $nvValue, 'is_final' => true]);
    }

    // =========================================================================
    // Full happy path test
    // =========================================================================

    /**
     * Full happy path: Pengajuan (6) → Verifikasi Berkas (5) → Assessment (4)
     * → Visitasi (3) → Pasca Visitasi (2) → Validasi Admin (1) → Selesai (0).
     *
     * Validates Requirements 2, 3, 5, 6, 7, 8, 9, 10, 11.
     */
    public function test_full_happy_path_end_to_end(): void
    {
        // Create asesors FIRST so their user_ids match asesors.id
        // (service uses user_id to query asesor_id in akreditasi_edpms)
        $asesor1User = $this->createAsesor();
        $asesor2User = $this->createAsesor();
        $pesantrenUser = $this->createCompletePesantrenUser();
        $admin = $this->createAdmin();

        // =====================================================================
        // Step 1: Submit pengajuan → status=6
        // =====================================================================
        $akreditasi = $this->workflowService->submitPengajuan($pesantrenUser->id);

        $this->assertNotNull($akreditasi);
        $this->assertSame(6, (int) $akreditasi->status);
        $this->assertDatabaseHas('akreditasis', ['id' => $akreditasi->id, 'status' => 6]);

        // Pesantren data should be locked
        $pesantren = Pesantren::where('user_id', $pesantrenUser->id)->first();
        $this->assertTrue((bool) $pesantren->is_locked);

        // =====================================================================
        // Step 2: Admin opens for review → status=5
        // =====================================================================
        $this->workflowService->openForReview($akreditasi->id, $admin->id);
        $akreditasi->refresh();

        $this->assertSame(5, (int) $akreditasi->status);

        // =====================================================================
        // Step 3: Admin approves berkas with 2 asesors → status=4
        // =====================================================================
        $this->workflowService->approveBerkas(
            $akreditasi->id,
            $admin->id,
            $asesor1User->id,
            $asesor2User->id
        );
        $akreditasi->refresh();

        $this->assertSame(4, (int) $akreditasi->status);

        // Verify assessments created
        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'tipe' => 1,
        ]);
        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'tipe' => 2,
        ]);

        // =====================================================================
        // Step 4: Asesor_1 schedules visitasi → status=3
        // =====================================================================
        $tanggalMulai = now()->addDays(10)->format('Y-m-d');
        $tanggalAkhir = now()->addDays(12)->format('Y-m-d');

        $this->workflowService->scheduleVisitasi($akreditasi->id, $asesor1User->id, [
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_akhir' => $tanggalAkhir,
            'catatan_visitasi' => 'Visitasi akan dilaksanakan di lokasi pesantren.',
        ]);
        $akreditasi->refresh();

        $this->assertSame(3, (int) $akreditasi->status);
        $this->assertSame($tanggalMulai, $akreditasi->tgl_visitasi);
        $this->assertSame($tanggalAkhir, $akreditasi->tgl_visitasi_akhir);
        $this->assertNotEmpty($akreditasi->catatan_visitasi);

        // =====================================================================
        // Step 5: Asesor_1 confirms visitasi selesai → status=2
        // =====================================================================
        // Travel to tanggal_mulai so confirmation is allowed
        $this->travelTo(now()->addDays(10));

        $this->workflowService->confirmVisitasiSelesai($akreditasi->id, $asesor1User->id);
        $akreditasi->refresh();

        $this->assertSame(2, (int) $akreditasi->status);
        $this->assertNotNull($akreditasi->visitasi_confirmed_at);

        $this->travelBack();

        // =====================================================================
        // Step 6: Both asesors save all 62 NA values as Final
        //         Asesor_1 also saves NK, catatan_butir, catatan_rekomendasi
        // =====================================================================
        $this->saveAllNaAsFinal($akreditasi->id, $asesor1User->id, tipe: 1, naValue: 3);
        $this->saveAllNaAsFinal($akreditasi->id, $asesor2User->id, tipe: 2, naValue: 3);
        $this->saveAllCatatanRekomendasi($akreditasi->id, $asesor1User->id);

        // Verify 62 NA1 records exist and are Final
        $na1Count = AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)
            ->where('asesor_id', $asesor1User->id)
            ->where('is_final', true)
            ->whereNotNull('isian')
            ->count();
        $this->assertSame(62, $na1Count);

        // Verify 62 NA2 records exist and are Final
        $na2Count = AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)
            ->where('asesor_id', $asesor2User->id)
            ->where('is_final', true)
            ->whereNotNull('isian')
            ->count();
        $this->assertSame(62, $na2Count);

        // =====================================================================
        // Step 7: Upload all required documents
        // =====================================================================
        $akreditasi->update([
            'laporan_visitasi_asesor1' => 'laporan/asesor1.pdf',
            'laporan_visitasi_asesor2' => 'laporan/asesor2.pdf',
            'laporan_visitasi_kelompok' => 'laporan/kelompok.pdf',
            'kartu_kendali' => 'kartu/kendali.pdf',
        ]);

        // =====================================================================
        // Step 8: Asesor_1 finalizes scoring → status=1
        // =====================================================================
        $this->workflowService->finalizeAssessorScoring($akreditasi->id, $asesor1User->id);
        $akreditasi->refresh();

        $this->assertSame(1, (int) $akreditasi->status);
        $this->assertTrue((bool) $akreditasi->is_nilai_asesor_final);
        $this->assertTrue((bool) $akreditasi->is_nilai_asesor2_final);

        // =====================================================================
        // Step 9: Admin saves all 62 NV as Final
        // =====================================================================
        $this->saveAllNvAsFinal($akreditasi->id, nvValue: 3);

        $nvFinalCount = AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)
            ->whereNotNull('nv')
            ->where('is_final', true)
            ->count();
        $this->assertGreaterThanOrEqual(62, $nvFinalCount);

        // =====================================================================
        // Step 10: Admin issues SK → status=0, nilai_akhir and peringkat set
        // =====================================================================
        $this->workflowService->issueSK($akreditasi->id, $admin->id, [
            'nomor_sk' => 'SK/001/2026',
            'masa_berlaku' => now()->format('Y-m-d'),
            'masa_berlaku_akhir' => now()->addYears(5)->format('Y-m-d'),
            'sertifikat_path' => 'sertifikat/sk_001.pdf',
            'catatan_rekomendasi_admin' => 'Pesantren telah memenuhi standar akreditasi.',
        ]);
        $akreditasi->refresh();

        $this->assertSame(0, (int) $akreditasi->status);
        $this->assertNotNull($akreditasi->nilai);
        $this->assertNotNull($akreditasi->peringkat);
        $this->assertSame('SK/001/2026', $akreditasi->nomor_sk);
        $this->assertContains($akreditasi->peringkat, ['A', 'B', 'C']);

        // Nilai akhir should be in valid range [0, 100]
        $this->assertGreaterThanOrEqual(0, (float) $akreditasi->nilai);
        $this->assertLessThanOrEqual(100, (float) $akreditasi->nilai);

        // =====================================================================
        // Final assertions
        // =====================================================================
        $this->assertDatabaseHas('akreditasis', [
            'id' => $akreditasi->id,
            'status' => 0,
            'nomor_sk' => 'SK/001/2026',
        ]);
    }

    /**
     * Verify that the score calculation produces correct nilai_akhir and peringkat
     * when all NV values are 4 (maximum score → should yield peringkat A).
     */
    public function test_maximum_nv_values_yield_peringkat_a(): void
    {
        $pesantrenUser = $this->createCompletePesantrenUser();
        $admin = $this->createAdmin();
        $asesor1User = $this->createAsesor();
        $asesor2User = $this->createAsesor();

        // Fast-track to status 1 (Validasi Admin)
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 1,
            'is_nilai_asesor_final' => true,
            'is_nilai_asesor2_final' => true,
            'laporan_visitasi_asesor1' => 'laporan/asesor1.pdf',
            'laporan_visitasi_asesor2' => 'laporan/asesor2.pdf',
            'laporan_visitasi_kelompok' => 'laporan/kelompok.pdf',
            'kartu_kendali' => 'kartu/kendali.pdf',
        ]);

        // Create assessments
        $asesor1 = Asesor::where('user_id', $asesor1User->id)->first();
        $asesor2 = Asesor::where('user_id', $asesor2User->id)->first();
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor1->id, 'tipe' => 1, 'tanggal_mulai' => now(), 'tanggal_berakhir' => now()->addDays(30)]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor2->id, 'tipe' => 2, 'tanggal_mulai' => now(), 'tanggal_berakhir' => now()->addDays(30)]);

        // Save all 62 NV = 4 as Final (using asesors.id for FK compliance)
        foreach ($this->butirs as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $pesantrenUser->id,
                'asesor_id' => $asesor1->id,
                'butir_id' => $butir->id,
                'isian' => 4,
                'nk' => 4,
                'nv' => 4,
                'is_final' => true,
                'catatan' => 'Catatan butir '.$butir->id,
            ]);
        }

        // Issue SK
        $this->workflowService->issueSK($akreditasi->id, $admin->id, [
            'nomor_sk' => 'SK/MAX/2026',
            'masa_berlaku' => now()->format('Y-m-d'),
            'masa_berlaku_akhir' => now()->addYears(5)->format('Y-m-d'),
            'sertifikat_path' => 'sertifikat/max.pdf',
            'catatan_rekomendasi_admin' => 'Nilai maksimum.',
        ]);

        $akreditasi->refresh();
        $this->assertSame(0, (int) $akreditasi->status);
        $this->assertSame('A', $akreditasi->peringkat);
        $this->assertSame(100.0, (float) $akreditasi->nilai);
    }

    /**
     * Verify that the score calculation produces correct nilai_akhir and peringkat
     * when all NV values are 1 (minimum score → should yield peringkat C).
     */
    public function test_minimum_nv_values_yield_peringkat_c(): void
    {
        $pesantrenUser = $this->createCompletePesantrenUser();
        $admin = $this->createAdmin();
        $asesor1User = $this->createAsesor();
        $asesor2User = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 1,
            'is_nilai_asesor_final' => true,
            'is_nilai_asesor2_final' => true,
            'laporan_visitasi_asesor1' => 'laporan/asesor1.pdf',
            'laporan_visitasi_asesor2' => 'laporan/asesor2.pdf',
            'laporan_visitasi_kelompok' => 'laporan/kelompok.pdf',
            'kartu_kendali' => 'kartu/kendali.pdf',
        ]);

        $asesor1 = Asesor::where('user_id', $asesor1User->id)->first();
        $asesor2 = Asesor::where('user_id', $asesor2User->id)->first();
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor1->id, 'tipe' => 1, 'tanggal_mulai' => now(), 'tanggal_berakhir' => now()->addDays(30)]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor2->id, 'tipe' => 2, 'tanggal_mulai' => now(), 'tanggal_berakhir' => now()->addDays(30)]);

        // Save all 62 NV = 1 as Final (using asesors.id for FK compliance)
        foreach ($this->butirs as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $pesantrenUser->id,
                'asesor_id' => $asesor1->id,
                'butir_id' => $butir->id,
                'isian' => 1,
                'nk' => 1,
                'nv' => 1,
                'is_final' => true,
                'catatan' => 'Catatan butir '.$butir->id,
            ]);
        }

        $this->workflowService->issueSK($akreditasi->id, $admin->id, [
            'nomor_sk' => 'SK/MIN/2026',
            'masa_berlaku' => now()->format('Y-m-d'),
            'masa_berlaku_akhir' => now()->addYears(5)->format('Y-m-d'),
            'sertifikat_path' => 'sertifikat/min.pdf',
            'catatan_rekomendasi_admin' => 'Nilai minimum.',
        ]);

        $akreditasi->refresh();
        $this->assertSame(0, (int) $akreditasi->status);
        $this->assertSame('C', $akreditasi->peringkat);
        $this->assertSame(25.0, (float) $akreditasi->nilai);
    }

    /**
     * Verify that finalizeAssessorScoring fails when documents are missing.
     *
     * Validates Requirement 8.10.
     */
    public function test_finalize_scoring_fails_when_documents_missing(): void
    {
        // Create asesors FIRST so their user_ids match asesors.id
        $asesor1User = $this->createAsesor();
        $asesor2User = $this->createAsesor();
        $pesantrenUser = $this->createCompletePesantrenUser();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 2,
            // No documents uploaded
        ]);

        $asesor1 = Asesor::where('user_id', $asesor1User->id)->first();
        $asesor2 = Asesor::where('user_id', $asesor2User->id)->first();
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor1->id, 'tipe' => 1, 'tanggal_mulai' => now(), 'tanggal_berakhir' => now()->addDays(30)]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor2->id, 'tipe' => 2, 'tanggal_mulai' => now(), 'tanggal_berakhir' => now()->addDays(30)]);

        $this->saveAllNaAsFinal($akreditasi->id, $asesor1User->id, tipe: 1);
        $this->saveAllNaAsFinal($akreditasi->id, $asesor2User->id, tipe: 2);
        $this->saveAllCatatanRekomendasi($akreditasi->id, $asesor1User->id);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/dokumen/i');

        $this->workflowService->finalizeAssessorScoring($akreditasi->id, $asesor1User->id);
    }

    /**
     * Verify that issueSK fails when NV values are not all Final.
     *
     * Validates Requirement 11.1.
     */
    public function test_issue_sk_fails_when_nv_not_all_final(): void
    {
        $pesantrenUser = $this->createCompletePesantrenUser();
        $admin = $this->createAdmin();
        $asesor1User = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 1,
            'laporan_visitasi_asesor1' => 'laporan/asesor1.pdf',
            'laporan_visitasi_asesor2' => 'laporan/asesor2.pdf',
            'laporan_visitasi_kelompok' => 'laporan/kelompok.pdf',
            'kartu_kendali' => 'kartu/kendali.pdf',
        ]);

        $asesor1 = Asesor::where('user_id', $asesor1User->id)->first();

        // Only save 30 NV values (not all 62)
        $butirSubset = array_slice($this->butirs, 0, 30);
        foreach ($butirSubset as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $pesantrenUser->id,
                'asesor_id' => $asesor1->id,
                'butir_id' => $butir->id,
                'nv' => 3,
                'is_final' => true,
            ]);
        }

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/NV/');

        $this->workflowService->issueSK($akreditasi->id, $admin->id, [
            'nomor_sk' => 'SK/FAIL/2026',
            'masa_berlaku' => now()->format('Y-m-d'),
            'masa_berlaku_akhir' => now()->addYears(5)->format('Y-m-d'),
            'sertifikat_path' => 'sertifikat/fail.pdf',
        ]);
    }
}
