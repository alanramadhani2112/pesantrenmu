<?php

namespace Tests\Feature\AkreditasiWorkflow;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AkreditasiDocumentService;
use App\Services\AkreditasiWorkflowService;
use App\Services\PesantrenService;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PostVisitasiDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private AkreditasiWorkflowService $workflowService;

    private AkreditasiDocumentService $documentService;

    private PesantrenService $pesantrenService;

    /** @var array<int, MasterEdpmButir> */
    private array $butirs = [];

    /** @var array<int, MasterEdpmKomponen> */
    private array $komponens = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Notification::fake();
        Storage::fake('public');

        $this->workflowService = app(AkreditasiWorkflowService::class);
        $this->documentService = app(AkreditasiDocumentService::class);
        $this->pesantrenService = app(PesantrenService::class);

        $this->seedMasterEdpmData();
    }

    public function test_pesantren_can_upload_kartu_kendali_only_after_visitasi_is_confirmed(): void
    {
        $setup = $this->createAssignedAkreditasi(AkreditasiStateMachine::STATUS_PASCA_VISITASI);

        $path = $this->documentService->uploadKartuKendaliForPesantren(
            $setup['akreditasi']->id,
            $setup['pesantrenUser']->id,
            UploadedFile::fake()->create('kartu-kendali.pdf', 128, 'application/pdf')
        );

        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $setup['akreditasi']->fresh()->kartu_kendali);

        $visitasiSetup = $this->createAssignedAkreditasi(AkreditasiStateMachine::STATUS_VISITASI);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Kartu Kendali hanya dapat diunggah pada tahap Pasca Visitasi.');

        $this->documentService->uploadKartuKendaliForPesantren(
            $visitasiSetup['akreditasi']->id,
            $visitasiSetup['pesantrenUser']->id,
            UploadedFile::fake()->create('kartu-kendali.pdf', 128, 'application/pdf')
        );
    }

    public function test_pesantren_cannot_upload_kartu_kendali_for_other_akreditasi(): void
    {
        $setup = $this->createAssignedAkreditasi(AkreditasiStateMachine::STATUS_PASCA_VISITASI);
        $otherPesantrenUser = User::factory()->create(['role_id' => 3]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Pesantren hanya dapat mengunggah Kartu Kendali untuk pengajuan miliknya.');

        $this->documentService->uploadKartuKendaliForPesantren(
            $setup['akreditasi']->id,
            $otherPesantrenUser->id,
            UploadedFile::fake()->create('kartu-kendali.pdf', 128, 'application/pdf')
        );
    }

    public function test_asesor_can_upload_only_their_own_individual_report_after_visitasi_is_confirmed(): void
    {
        $setup = $this->createAssignedAkreditasi(AkreditasiStateMachine::STATUS_PASCA_VISITASI);

        $asesor1Path = $this->documentService->uploadLaporanIndividuForAsesor(
            $setup['akreditasi']->id,
            $setup['asesor1User']->id,
            UploadedFile::fake()->create('laporan-asesor-1.pdf', 128, 'application/pdf')
        );

        $asesor2Path = $this->documentService->uploadLaporanIndividuForAsesor(
            $setup['akreditasi']->id,
            $setup['asesor2User']->id,
            UploadedFile::fake()->create('laporan-asesor-2.pdf', 128, 'application/pdf')
        );

        $akreditasi = $setup['akreditasi']->fresh();
        $this->assertSame($asesor1Path, $akreditasi->laporan_visitasi_asesor1);
        $this->assertSame($asesor2Path, $akreditasi->laporan_visitasi_asesor2);
        Storage::disk('public')->assertExists($asesor1Path);
        Storage::disk('public')->assertExists($asesor2Path);

        $unassignedAsesor = User::factory()->create(['role_id' => 2]);
        Asesor::create([
            'user_id' => $unassignedAsesor->id,
            'nama_dengan_gelar' => 'Asesor Tidak Ditugaskan',
            'nama_tanpa_gelar' => 'Asesor Tidak Ditugaskan',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Hanya asesor yang ditugaskan yang dapat mengunggah laporan individu.');

        $this->documentService->uploadLaporanIndividuForAsesor(
            $setup['akreditasi']->id,
            $unassignedAsesor->id,
            UploadedFile::fake()->create('laporan-unassigned.pdf', 128, 'application/pdf')
        );
    }

    public function test_only_asesor_1_can_upload_group_report_after_visitasi_is_confirmed(): void
    {
        $setup = $this->createAssignedAkreditasi(AkreditasiStateMachine::STATUS_PASCA_VISITASI);

        $path = $this->documentService->uploadLaporanKelompokForAsesor1(
            $setup['akreditasi']->id,
            $setup['asesor1User']->id,
            UploadedFile::fake()->create('laporan-kelompok.pdf', 128, 'application/pdf')
        );

        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $setup['akreditasi']->fresh()->laporan_visitasi_kelompok);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Hanya Ketua Kelompok yang ditugaskan yang dapat mengunggah laporan kelompok.');

        $this->documentService->uploadLaporanKelompokForAsesor1(
            $setup['akreditasi']->id,
            $setup['asesor2User']->id,
            UploadedFile::fake()->create('laporan-kelompok-asesor-2.pdf', 128, 'application/pdf')
        );
    }

    public function test_legacy_pesantren_service_upload_kartu_kendali_uses_pascha_visitasi_status(): void
    {
        $setup = $this->createAssignedAkreditasi(AkreditasiStateMachine::STATUS_PASCA_VISITASI);

        $this->assertTrue($this->pesantrenService->uploadKartuKendali(
            $setup['akreditasi']->id,
            $setup['pesantrenUser']->id,
            'akreditasi/kartu-kendali/kartu.pdf'
        ));

        $visitasiSetup = $this->createAssignedAkreditasi(AkreditasiStateMachine::STATUS_VISITASI);

        $this->assertFalse($this->pesantrenService->uploadKartuKendali(
            $visitasiSetup['akreditasi']->id,
            $visitasiSetup['pesantrenUser']->id,
            'akreditasi/kartu-kendali/kartu-before-pasca.pdf'
        ));
    }

    public function test_finalisasi_penilaian_is_blocked_until_all_post_visitasi_documents_are_complete(): void
    {
        foreach (array_keys($this->requiredPostVisitasiDocuments()) as $missingDocument) {
            $documents = $this->requiredPostVisitasiDocuments();
            unset($documents[$missingDocument]);

            $setup = $this->createAssignedAkreditasi(
                AkreditasiStateMachine::STATUS_PASCA_VISITASI,
                $documents
            );
            $this->fillFinalAssessorScoring($setup['akreditasi'], $setup['asesor1User'], $setup['asesor2User']);

            try {
                $this->workflowService->finalizeAssessorScoring($setup['akreditasi']->id, $setup['asesor1User']->id);
                $this->fail("Finalisasi penilaian harus tertahan ketika {$missingDocument} belum diunggah.");
            } catch (\DomainException $e) {
                $this->assertStringContainsString($missingDocument, $e->getMessage());
            }

            $this->assertSame(
                AkreditasiStateMachine::STATUS_PASCA_VISITASI,
                (int) $setup['akreditasi']->fresh()->status
            );
        }
    }

    public function test_finalisasi_penilaian_succeeds_when_scores_and_post_visitasi_documents_are_complete(): void
    {
        $setup = $this->createAssignedAkreditasi(
            AkreditasiStateMachine::STATUS_PASCA_VISITASI,
            $this->requiredPostVisitasiDocuments()
        );
        $this->fillFinalAssessorScoring($setup['akreditasi'], $setup['asesor1User'], $setup['asesor2User']);

        $this->workflowService->finalizeAssessorScoring($setup['akreditasi']->id, $setup['asesor1User']->id);

        $this->assertSame(
            AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
            (int) $setup['akreditasi']->fresh()->status
        );
    }

    public function test_issue_sk_is_blocked_if_post_visitasi_documents_are_missing_even_when_nv_is_complete(): void
    {
        foreach (array_keys($this->requiredPostVisitasiDocuments()) as $missingDocument) {
            $documents = $this->requiredPostVisitasiDocuments();
            unset($documents[$missingDocument]);

            $setup = $this->createAssignedAkreditasi(
                AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
                $documents
            );
            $this->fillFinalNvScoring($setup['akreditasi'], $setup['admin']);

            try {
                $this->workflowService->issueSK($setup['akreditasi']->id, $setup['admin']->id, $this->validSkData());
                $this->fail("Penerbitan SK harus tertahan ketika {$missingDocument} belum diunggah.");
            } catch (\DomainException $e) {
                $this->assertStringContainsString($missingDocument, $e->getMessage());
            }

            $this->assertSame(
                AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
                (int) $setup['akreditasi']->fresh()->status
            );
        }
    }

    public function test_issue_sk_succeeds_when_nv_and_post_visitasi_documents_are_complete(): void
    {
        $setup = $this->createAssignedAkreditasi(
            AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
            $this->requiredPostVisitasiDocuments()
        );
        $this->fillFinalNvScoring($setup['akreditasi'], $setup['admin']);

        $this->workflowService->issueSK($setup['akreditasi']->id, $setup['admin']->id, $this->validSkData());

        $akreditasi = $setup['akreditasi']->fresh();
        $this->assertSame(AkreditasiStateMachine::STATUS_SELESAI, (int) $akreditasi->status);
        $this->assertSame('SK/LP2M/001/2026', $akreditasi->nomor_sk);
        $this->assertNotNull($akreditasi->nilai);
        $this->assertNotNull($akreditasi->peringkat);
    }

    private function seedMasterEdpmData(): void
    {
        $komponenConfig = [
            ['nama' => 'MUTU LULUSAN', 'count' => 8, 'ipr' => null],
            ['nama' => 'PROSES PEMBELAJARAN', 'count' => 10, 'ipr' => null],
            ['nama' => 'MUTU USTAZ', 'count' => 10, 'ipr' => null],
            ['nama' => 'MANAJEMEN PESANTREN', 'count' => 12, 'ipr' => null],
            ['nama' => 'IPR', 'count' => 22, 'ipr' => 1],
        ];

        $sequence = 1;
        foreach ($komponenConfig as $config) {
            $komponen = MasterEdpmKomponen::create([
                'nama' => $config['nama'],
                'ipr' => $config['ipr'],
            ]);
            $this->komponens[] = $komponen;

            for ($i = 1; $i <= $config['count']; $i++) {
                $butir = MasterEdpmButir::create([
                    'komponen_id' => $komponen->id,
                    'no_sk' => (string) $sequence,
                    'nomor_butir' => "{$sequence}.{$i}",
                    'butir_pernyataan' => "Butir {$sequence}.{$i}",
                ]);

                $this->butirs[] = $butir;
                $sequence++;
            }
        }
    }

    /**
     * @return array{
     *     admin: User,
     *     pesantrenUser: User,
     *     asesor1User: User,
     *     asesor2User: User,
     *     akreditasi: Akreditasi
     * }
     */
    private function createAssignedAkreditasi(int $status, array $documents = []): array
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Dokumen Pasca Visitasi',
        ]);

        $asesor1User = User::factory()->create(['role_id' => 2]);
        $asesor1 = Asesor::create([
            'user_id' => $asesor1User->id,
            'nama_dengan_gelar' => 'Asesor 1',
            'nama_tanpa_gelar' => 'Asesor 1',
        ]);

        $asesor2User = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Asesor 2',
            'nama_tanpa_gelar' => 'Asesor 2',
        ]);

        $akreditasi = Akreditasi::create(array_merge([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ], $documents));

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(3),
            'tanggal_berakhir' => now()->addDays(27),
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now()->subDays(3),
            'tanggal_berakhir' => now()->addDays(27),
        ]);

        return compact('admin', 'pesantrenUser', 'asesor1User', 'asesor2User', 'akreditasi');
    }

    private function fillFinalAssessorScoring(Akreditasi $akreditasi, User $asesor1User, User $asesor2User): void
    {
        $asesor1Id = Asesor::where('user_id', $asesor1User->id)->value('id');
        $asesor2Id = Asesor::where('user_id', $asesor2User->id)->value('id');

        foreach ($this->butirs as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $akreditasi->user_id,
                'asesor_id' => $asesor1Id,
                'butir_id' => $butir->id,
                'isian' => 3,
                'nk' => 3,
                'catatan' => "Catatan {$butir->id}",
                'is_final' => true,
            ]);

            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $akreditasi->user_id,
                'asesor_id' => $asesor2Id,
                'butir_id' => $butir->id,
                'isian' => 3,
                'is_final' => true,
            ]);
        }

        foreach (array_slice($this->komponens, 0, 4) as $komponen) {
            AkreditasiEdpmCatatan::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $akreditasi->user_id,
                'asesor_id' => $asesor1Id,
                'komponen_id' => $komponen->id,
                'catatan' => "Rekomendasi {$komponen->nama}",
                'rekomendasi' => "Rekomendasi {$komponen->nama}",
                'nk' => 3,
            ]);
        }
    }

    private function fillFinalNvScoring(Akreditasi $akreditasi, User $admin): void
    {
        $asesorId = Asesor::query()->value('id');

        foreach ($this->butirs as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'pesantren_id' => $akreditasi->user_id,
                'asesor_id' => $asesorId,
                'butir_id' => $butir->id,
                'nv' => 3,
                'is_final' => true,
            ]);
        }
    }

    private function requiredPostVisitasiDocuments(): array
    {
        return [
            'laporan_visitasi_asesor1' => 'laporan/asesor-1.pdf',
            'laporan_visitasi_asesor2' => 'laporan/asesor-2.pdf',
            'laporan_visitasi_kelompok' => 'laporan/kelompok.pdf',
            'kartu_kendali' => 'kartu/kendali.pdf',
        ];
    }

    private function validSkData(): array
    {
        return [
            'nomor_sk' => 'SK/LP2M/001/2026',
            'masa_berlaku' => now()->format('Y-m-d'),
            'masa_berlaku_akhir' => now()->addYears(5)->format('Y-m-d'),
            'sertifikat_path' => 'sertifikat/sk-lp2m-001.pdf',
            'catatan_rekomendasi_admin' => 'Layak diterbitkan berdasarkan hasil validasi akhir.',
        ];
    }
}
