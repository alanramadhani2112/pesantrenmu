<?php

namespace Tests\Feature\BusinessFlow;

use App\Models\AkreditasiAuditLog;
use App\Models\AkreditasiEdpm;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Services\AkreditasiWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessFlowHappyPathTest extends TestCase
{
    use RefreshDatabase;
    use BusinessFlowTestHelpers;

    private AkreditasiWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBusinessFlowBase();
        $this->workflow = app(AkreditasiWorkflowService::class);
    }

    public function test_business_flow_happy_path_reaches_selesai(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.happy.pesantren@test.local');
        $admin = $this->createUser('bf.happy.admin@test.local', 1, 'BF Happy Admin');
        $asesor1 = $this->createAsesorUser('bf.happy.asesor1@test.local', 'BF Happy Asesor 1');
        $asesor2 = $this->createAsesorUser('bf.happy.asesor2@test.local', 'BF Happy Asesor 2');

        $akreditasi = $this->workflow->submitPengajuan($pesantren->id);
        $this->assertSame(6, (int) $akreditasi->status);
        $this->assertTrue((bool) Pesantren::where('user_id', $pesantren->id)->value('is_locked'));

        $this->workflow->openForReview($akreditasi->id, $admin->id);
        $this->assertSame(5, (int) $akreditasi->fresh()->status);

        $this->workflow->approveBerkas($akreditasi->id, $admin->id, $asesor1->id, $asesor2->id);
        $this->assertSame(4, (int) $akreditasi->fresh()->status);
        $this->assertSame(2, Assessment::where('akreditasi_id', $akreditasi->id)->count());

        $this->workflow->scheduleVisitasi($akreditasi->id, $asesor1->id, [
            'tanggal_mulai' => now()->addDays(8)->toDateString(),
            'tanggal_akhir' => now()->addDays(10)->toDateString(),
            'catatan_visitasi' => '[BF-HAPPY-003] Visitasi scheduled',
        ]);
        $this->assertSame(3, (int) $akreditasi->fresh()->status);

        $this->travelTo(now()->addDays(8));
        $this->workflow->confirmVisitasiSelesai($akreditasi->id, $asesor1->id);
        $this->travelBack();
        $this->assertSame(2, (int) $akreditasi->fresh()->status);
        $this->assertNotNull($akreditasi->fresh()->visitasi_confirmed_at);

        $akreditasi->refresh()->update([
            'laporan_visitasi_asesor1' => 'bf/laporan/asesor1.pdf',
            'laporan_visitasi_asesor2' => 'bf/laporan/asesor2.pdf',
            'laporan_visitasi_kelompok' => 'bf/laporan/kelompok.pdf',
            'kartu_kendali' => 'bf/kartu/kendali.pdf',
        ]);
        $this->seedCompleteScoring($akreditasi->fresh(), $asesor1, $asesor2);

        $this->workflow->finalizeAssessorScoring($akreditasi->id, $asesor1->id);
        $this->assertSame(1, (int) $akreditasi->fresh()->status);
        $this->assertTrue((bool) $akreditasi->fresh()->is_nilai_asesor_final);
        $this->assertTrue((bool) $akreditasi->fresh()->is_nilai_asesor2_final);

        AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)->update(['nv' => 3, 'is_final' => true]);

        $this->workflow->issueSK($akreditasi->id, $admin->id, [
            'nomor_sk' => 'BF/SK/HAPPY/001',
            'masa_berlaku' => now()->toDateString(),
            'masa_berlaku_akhir' => now()->addYears(5)->toDateString(),
            'sertifikat_path' => 'bf/sertifikat/happy.pdf',
            'catatan_rekomendasi_admin' => '[BF-HAPPY-008] SK issued',
        ]);

        $final = $akreditasi->fresh();
        $this->assertSame(0, (int) $final->status);
        $this->assertSame('BF/SK/HAPPY/001', $final->nomor_sk);
        $this->assertNotNull($final->nilai);
        $this->assertNotNull($final->peringkat);
        $this->assertDatabaseHas('akreditasis', ['id' => $akreditasi->id, 'status' => 0]);
        $this->assertGreaterThanOrEqual(6, AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)->where('action_type', 'status_changed')->count());
    }
}
