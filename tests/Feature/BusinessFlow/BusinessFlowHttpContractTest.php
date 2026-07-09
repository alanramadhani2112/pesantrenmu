<?php

namespace Tests\Feature\BusinessFlow;

use App\StateMachine\AkreditasiStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessFlowHttpContractTest extends TestCase
{
    use BusinessFlowTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBusinessFlowBase();
    }

    public function test_asesor_detail_dynamic_post_helper_includes_akreditasi_id(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.http.detail.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.http.detail.asesor1@test.local', 'BF HTTP Detail Asesor 1');
        $asesor2 = $this->createAsesorUser('bf.http.detail.asesor2@test.local', 'BF HTTP Detail Asesor 2');
        $akreditasi = $this->createAkreditasi($pesantren, AkreditasiStateMachine::STATUS_VISITASI, 'BF-HTTP-DETAIL');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);

        $this->actingAs($asesor1)
            ->get(route('asesor.akreditasi-detail', $akreditasi->uuid))
            ->assertOk()
            ->assertSee('name="akreditasi_id"', false)
            ->assertSee('value="'.$akreditasi->id.'"', false);
    }

    public function test_asesor_can_confirm_visitasi_selesai_through_http_route(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.http.confirm.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.http.confirm.asesor1@test.local', 'BF HTTP Confirm Asesor 1');
        $asesor2 = $this->createAsesorUser('bf.http.confirm.asesor2@test.local', 'BF HTTP Confirm Asesor 2');
        $akreditasi = $this->createAkreditasi($pesantren, AkreditasiStateMachine::STATUS_VISITASI, 'BF-HTTP-CONFIRM');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);
        $akreditasi->update([
            'tgl_visitasi' => now()->subDay()->toDateString(),
            'tgl_visitasi_akhir' => now()->toDateString(),
        ]);

        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.confirm-visitasi-selesai'), [
                'akreditasi_id' => $akreditasi->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Visitasi dikonfirmasi selesai. Tahap penilaian pasca visitasi dimulai.');

        $this->assertSame(AkreditasiStateMachine::STATUS_PASCA_VISITASI, (int) $akreditasi->fresh()->status);
        $this->assertNotNull($akreditasi->fresh()->visitasi_confirmed_at);
    }

    public function test_asesor_can_finalize_scoring_through_http_route(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.http.finalize.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.http.finalize.asesor1@test.local', 'BF HTTP Finalize Asesor 1');
        $asesor2 = $this->createAsesorUser('bf.http.finalize.asesor2@test.local', 'BF HTTP Finalize Asesor 2');
        $akreditasi = $this->createAkreditasi($pesantren, AkreditasiStateMachine::STATUS_PASCA_VISITASI, 'BF-HTTP-FINALIZE');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);
        $akreditasi->update([
            'laporan_visitasi_asesor1' => 'bf/laporan/asesor1.pdf',
            'laporan_visitasi_asesor2' => 'bf/laporan/asesor2.pdf',
            'laporan_visitasi_kelompok' => 'bf/laporan/kelompok.pdf',
            'kartu_kendali' => 'bf/kartu/kendali.pdf',
        ]);
        $this->seedCompleteScoring($akreditasi->fresh(), $asesor1, $asesor2);

        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.finalize-scoring'), [
                'akreditasi_id' => $akreditasi->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Penilaian difinalisasi. Akreditasi masuk tahap Validasi Admin.');

        $final = $akreditasi->fresh();
        $this->assertSame(AkreditasiStateMachine::STATUS_VALIDASI_ADMIN, (int) $final->status);
        $this->assertTrue((bool) $final->is_nilai_asesor_final);
        $this->assertTrue((bool) $final->is_nilai_asesor2_final);
    }
}
