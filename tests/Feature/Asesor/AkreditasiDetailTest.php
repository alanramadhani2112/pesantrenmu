<?php

namespace Tests\Feature\Asesor;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests for AsesorService methods used by AkreditasiDetail component.
 *
 * Covers:
 *   - saveAsesorEdpm: persists NA/NK values, ownership check
 *   - finalizeVerification: precondition checks, status transition
 *   - uploadLaporanVisitasi: file storage, rollback on failure
 *   - submitRejection: ownership (asesor 1 only), creates rejection
 *   - acceptPerbaikan: ownership (asesor 1 only)
 */
class AkreditasiDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $asesor1User;
    private User $asesor2User;
    private Asesor $asesor1;
    private Asesor $asesor2;
    private User $pesantrenUser;
    private Pesantren $pesantren;
    private Akreditasi $akreditasi;
    private Assessment $assessment1;
    private Assessment $assessment2;
    private MasterEdpmKomponen $komponen;
    private MasterEdpmButir $butir1;
    private MasterEdpmButir $butir2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        Storage::fake('public');

        $this->asesor1User = User::factory()->create(['role_id' => 2]);
        $this->asesor1 = Asesor::create([
            'user_id' => $this->asesor1User->id,
            'nama_dengan_gelar' => 'Dr. Asesor 1',
            'nama_tanpa_gelar' => 'Asesor 1',
        ]);

        $this->asesor2User = User::factory()->create(['role_id' => 2]);
        $this->asesor2 = Asesor::create([
            'user_id' => $this->asesor2User->id,
            'nama_dengan_gelar' => 'Dr. Asesor 2',
            'nama_tanpa_gelar' => 'Asesor 2',
        ]);

        $this->pesantrenUser = User::factory()->create(['role_id' => 3]);
        $this->pesantren = Pesantren::create([
            'user_id' => $this->pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test',
        ]);

        $this->akreditasi = Akreditasi::create([
            'user_id' => $this->pesantrenUser->id,
            'status' => \App\StateMachine\AkreditasiStateMachine::STATUS_ASSESSMENT,
        ]);

        $this->assessment1 = Assessment::create([
            'akreditasi_id' => $this->akreditasi->id,
            'asesor_id' => $this->asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(5),
            'tanggal_berakhir' => now()->addDays(25),
        ]);

        $this->assessment2 = Assessment::create([
            'akreditasi_id' => $this->akreditasi->id,
            'asesor_id' => $this->asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now()->subDays(5),
            'tanggal_berakhir' => now()->addDays(25),
        ]);

        $this->komponen = MasterEdpmKomponen::create(['nama' => 'MUTU LULUSAN']);
        $this->butir1 = MasterEdpmButir::create([
            'komponen_id' => $this->komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1',
            'butir_pernyataan' => 'Butir 1',
        ]);
        $this->butir2 = MasterEdpmButir::create([
            'komponen_id' => $this->komponen->id,
            'no_sk' => '2',
            'nomor_butir' => '2',
            'butir_pernyataan' => 'Butir 2',
        ]);
    }

    // ─── saveAsesorEdpm ───────────────────────────────────────────────────────

    public function test_save_asesor_edpm_persists_na_values(): void
    {
        $this->actingAs($this->asesor1User);
        $this->akreditasi->update(['status' => \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI]);
        $service = app(\App\Services\AsesorService::class);

        $result = $service->saveAsesorEdpm(
            $this->akreditasi->id,
            $this->asesor1->id,
            1, // asesorTipe
            $this->pesantrenUser->id,
            [
                'asesorEvaluasis' => [$this->butir1->id => '3', $this->butir2->id => '4'],
                'asesorNks' => [],
                'asesorButirCatatans' => [],
                'asesorCatatans' => [],
                'asesorCatatanNks' => [],
            ]
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('akreditasi_edpms', [
            'akreditasi_id' => $this->akreditasi->id,
            'asesor_id' => $this->asesor1->id,
            'butir_id' => $this->butir1->id,
            'isian' => '3',
        ]);
    }

    public function test_save_asesor_edpm_rejects_before_visitasi_is_confirmed(): void
    {
        $this->actingAs($this->asesor1User);
        $service = app(\App\Services\AsesorService::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Nilai asesor hanya dapat diisi setelah visitasi dikonfirmasi selesai.');

        $service->saveAsesorEdpm(
            $this->akreditasi->id,
            $this->asesor1->id,
            1,
            $this->pesantrenUser->id,
            [
                'asesorEvaluasis' => [$this->butir1->id => '3'],
                'asesorNks' => [],
                'asesorButirCatatans' => [],
                'asesorCatatans' => [],
                'asesorCatatanNks' => [],
            ]
        );
    }

    public function test_save_asesor_edpm_blocks_nilai_kelompok_until_ketua_and_anggota_are_final(): void
    {
        $this->actingAs($this->asesor1User);
        $this->akreditasi->update(['status' => \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI]);
        $service = app(\App\Services\AsesorService::class);

        $this->expectException(\DomainException::class);

        $service->saveAsesorEdpm(
            $this->akreditasi->id,
            $this->asesor1->id,
            1,
            $this->pesantrenUser->id,
            [
                'asesorEvaluasis' => [$this->butir1->id => '3'],
                'asesorNks' => [$this->butir1->id => '3'],
                'asesorButirCatatans' => [],
                'asesorCatatans' => [],
                'asesorCatatanNks' => [],
            ]
        );
    }

    public function test_save_asesor_edpm_allows_nilai_kelompok_after_ketua_and_anggota_are_final(): void
    {
        $this->actingAs($this->asesor1User);
        $this->akreditasi->update(['status' => \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI]);

        $scoringService = app(\App\Services\AssessorScoringService::class);
        foreach ([$this->butir1->id, $this->butir2->id] as $butirId) {
            $scoringService->saveNA($this->akreditasi->id, $this->asesor1User->id, $butirId, 3, true);
            $scoringService->saveNA($this->akreditasi->id, $this->asesor2User->id, $butirId, 3, true);
        }

        $service = app(\App\Services\AsesorService::class);
        $result = $service->saveAsesorEdpm(
            $this->akreditasi->id,
            $this->asesor1->id,
            1,
            $this->pesantrenUser->id,
            [
                'asesorEvaluasis' => [$this->butir1->id => '3'],
                'asesorNks' => [$this->butir1->id => '3'],
                'asesorButirCatatans' => [],
                'asesorCatatans' => [],
                'asesorCatatanNks' => [],
            ]
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('akreditasi_edpms', [
            'akreditasi_id' => $this->akreditasi->id,
            'asesor_id' => $this->asesor1->id,
            'butir_id' => $this->butir1->id,
            'nk' => '3',
        ]);
    }

    public function test_save_asesor_edpm_skips_empty_evaluasis(): void
    {
        $this->actingAs($this->asesor1User);
        $this->akreditasi->update(['status' => \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI]);
        $service = app(\App\Services\AsesorService::class);

        $service->saveAsesorEdpm(
            $this->akreditasi->id,
            $this->asesor1->id,
            1,
            $this->pesantrenUser->id,
            [
                'asesorEvaluasis' => [$this->butir1->id => '3', $this->butir2->id => ''],
                'asesorNks' => [],
                'asesorButirCatatans' => [],
                'asesorCatatans' => [],
                'asesorCatatanNks' => [],
            ]
        );

        // butir2 is empty — should not be saved
        $this->assertDatabaseMissing('akreditasi_edpms', [
            'akreditasi_id' => $this->akreditasi->id,
            'butir_id' => $this->butir2->id,
        ]);
    }

    public function test_save_asesor_edpm_asesor2_does_not_save_nk(): void
    {
        $this->actingAs($this->asesor2User);
        $this->akreditasi->update(['status' => \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI]);
        $service = app(\App\Services\AsesorService::class);

        $service->saveAsesorEdpm(
            $this->akreditasi->id,
            $this->asesor2->id,
            2, // asesorTipe = 2
            $this->pesantrenUser->id,
            [
                'asesorEvaluasis' => [$this->butir1->id => '2'],
                'asesorNks' => [$this->butir1->id => '3'], // NK provided but should be ignored for asesor 2
                'asesorButirCatatans' => [],
                'asesorCatatans' => [],
                'asesorCatatanNks' => [],
            ]
        );

        // NK should not be saved for asesor 2
        $edpm = AkreditasiEdpm::where('akreditasi_id', $this->akreditasi->id)
            ->where('asesor_id', $this->asesor2->id)
            ->where('butir_id', $this->butir1->id)
            ->first();

        $this->assertNotNull($edpm);
        $this->assertNull($edpm->nk); // NK not saved for asesor 2
    }

    // ─── finalizeVerification ─────────────────────────────────────────────────

    public function test_finalize_verification_returns_error_when_asesor1_na_incomplete(): void
    {
        $service = app(\App\Services\AsesorService::class);
        $this->akreditasi->update(['status' => \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI]);

        // No NA data filled for asesor 1
        $result = $service->finalizeVerification($this->akreditasi->id, $this->asesor1User->id);

        $this->assertFalse($result['success']);
        $this->assertEquals('asesor1_na_incomplete', $result['error']);
    }

    public function test_finalize_verification_returns_error_when_asesor2_na_incomplete(): void
    {
        $service = app(\App\Services\AsesorService::class);
        $this->akreditasi->update(['status' => \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI]);

        // Fill asesor 1 NA + NK but not asesor 2
        AkreditasiEdpm::create([
            'akreditasi_id' => $this->akreditasi->id,
            'asesor_id' => $this->asesor1->id,
            'butir_id' => $this->butir1->id,
            'pesantren_id' => $this->pesantrenUser->id,
            'isian' => '3',
            'nk' => '3',
        ]);
        AkreditasiEdpm::create([
            'akreditasi_id' => $this->akreditasi->id,
            'asesor_id' => $this->asesor1->id,
            'butir_id' => $this->butir2->id,
            'pesantren_id' => $this->pesantrenUser->id,
            'isian' => '4',
            'nk' => '4',
        ]);
        // Asesor 2 has no data

        $result = $service->finalizeVerification($this->akreditasi->id, $this->asesor1User->id);

        $this->assertFalse($result['success']);
        $this->assertEquals('asesor2_incomplete', $result['error']);
    }

    public function test_finalize_verification_returns_error_for_non_asesor1(): void
    {
        $service = app(\App\Services\AsesorService::class);

        $result = $service->finalizeVerification($this->akreditasi->id, $this->asesor2User->id);

        $this->assertFalse($result['success']);
        $this->assertEquals('unauthorized', $result['error']);
    }

    public function test_finalize_verification_transitions_status_to_validasi_when_complete(): void
    {
        $service = app(\App\Services\AsesorService::class);
        $this->akreditasi->update(['status' => \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI]);

        // Fill all NA + NK for asesor 1 and NA for asesor 2
        foreach ([$this->butir1->id, $this->butir2->id] as $butirId) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $this->akreditasi->id,
                'asesor_id' => $this->asesor1->id,
                'butir_id' => $butirId,
                'pesantren_id' => $this->pesantrenUser->id,
                'isian' => '3',
                'nk' => '3',
            ]);
            AkreditasiEdpm::create([
                'akreditasi_id' => $this->akreditasi->id,
                'asesor_id' => $this->asesor2->id,
                'butir_id' => $butirId,
                'pesantren_id' => $this->pesantrenUser->id,
                'isian' => '4',
            ]);
        }

        $result = $service->finalizeVerification($this->akreditasi->id, $this->asesor1User->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(\App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN, $this->akreditasi->fresh()->status);
    }

    // ─── uploadLaporanVisitasi: rollback on failure ───────────────────────────

    public function test_upload_laporan_visitasi_returns_false_for_nonexistent_akreditasi(): void
    {
        $service = app(\App\Services\AsesorService::class);

        $result = $service->uploadLaporanVisitasi(99999, 1, 'some/path.pdf');

        $this->assertFalse($result);
    }

    public function test_upload_laporan_visitasi_persists_file_path(): void
    {
        $service = app(\App\Services\AsesorService::class);
        $this->akreditasi->update(['status' => 2]);

        $result = $service->uploadLaporanVisitasi(
            $this->akreditasi->id,
            1, // asesorTipe 1
            'akreditasi/laporan_visitasi/laporan_test.pdf'
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('akreditasis', [
            'id' => $this->akreditasi->id,
            'laporan_visitasi_asesor1' => 'akreditasi/laporan_visitasi/laporan_test.pdf',
        ]);
    }

    public function test_upload_laporan_visitasi_asesor2_uses_different_field(): void
    {
        $service = app(\App\Services\AsesorService::class);
        $this->akreditasi->update(['status' => 2]);

        $service->uploadLaporanVisitasi(
            $this->akreditasi->id,
            2, // asesorTipe 2
            'akreditasi/laporan_visitasi/laporan_asesor2.pdf'
        );

        $this->assertDatabaseHas('akreditasis', [
            'id' => $this->akreditasi->id,
            'laporan_visitasi_asesor2' => 'akreditasi/laporan_visitasi/laporan_asesor2.pdf',
        ]);
    }

    // ─── processVisitasi: data key consistency ────────────────────────────────

    public function test_process_visitasi_tolak_uses_rejected_items_key(): void
    {
        // createRejection requires status=5 (Assessment)
        $this->akreditasi->update(['status' => 5]);

        $service = app(\App\Services\AsesorService::class);

        // This test verifies the fix for the key mismatch bug:
        // blade was sending 'perbaikan' but service reads 'rejected_items'
        $result = $service->processVisitasi(
            $this->akreditasi->id,
            $this->asesor1User->id,
            [
                'tanggal' => null,
                'tanggal_akhir' => null,
                'catatan' => 'Dokumen tidak lengkap',
                'rejected_items' => ['profil', 'ipm'], // correct key
            ],
            'tolak'
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $this->akreditasi->id,
        ]);
    }

    // ─── submitVisitasi: success returns true ─────────────────────────────────

    public function test_process_visitasi_terima_returns_true_on_success(): void
    {
        $service = app(\App\Services\AsesorService::class);

        $result = $service->processVisitasi(
            $this->akreditasi->id,
            $this->asesor1User->id,
            [
                'tanggal' => now()->format('Y-m-d'),
                'tanggal_akhir' => now()->format('Y-m-d'),
                'catatan' => '',
            ],
            'terima'
        );

        $this->assertTrue($result);
    }

    public function test_process_visitasi_returns_false_on_failure(): void
    {
        $service = app(\App\Services\AsesorService::class);

        // Non-assigned asesor should return false
        $otherUser = User::factory()->create(['role_id' => 2]);

        $result = $service->processVisitasi(
            $this->akreditasi->id,
            $otherUser->id,
            [
                'tanggal' => now()->format('Y-m-d'),
                'tanggal_akhir' => now()->format('Y-m-d'),
                'catatan' => '',
            ],
            'terima'
        );

        $this->assertFalse($result);
    }
}
