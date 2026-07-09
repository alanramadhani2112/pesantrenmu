<?php

namespace Tests\Feature\E2E;

use App\Models\Akreditasi;
use App\Models\AkreditasiAuditLog;
use App\Models\AkreditasiEdpm;
use App\Models\Assessment;
use App\Models\Pesantren;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\BusinessFlow\BusinessFlowTestHelpers;
use Tests\TestCase;

class HybridAccreditationFlowTest extends TestCase
{
    use BusinessFlowTestHelpers;
    use RefreshDatabase;

    public function test_http_e2e_canonical_accreditation_flow_reaches_final_result(): void
    {
        Storage::fake('public');
        $this->seedBusinessFlowBase();

        $pesantren = $this->createCompletePesantrenUser('hybrid.e2e.pesantren@test.local');
        $admin = $this->createUser('hybrid.e2e.admin@test.local', 1, 'Hybrid E2E Admin');
        $asesor1 = $this->createAsesorUser('hybrid.e2e.asesor1@test.local', 'Hybrid E2E Asesor 1');
        $asesor2 = $this->createAsesorUser('hybrid.e2e.asesor2@test.local', 'Hybrid E2E Asesor 2');

        $this->actingAs($pesantren)
            ->post(route('pesantren.akreditasi.create'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $akreditasi = Akreditasi::where('user_id', $pesantren->id)->firstOrFail();
        $this->assertSame(6, (int) $akreditasi->status);
        $this->assertTrue((bool) Pesantren::where('user_id', $pesantren->id)->value('is_locked'));

        $this->actingAs($admin)
            ->post(route('admin.akreditasi-detail.open-for-review', $akreditasi->uuid))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(5, (int) $akreditasi->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.akreditasi-detail.approve-berkas', $akreditasi->uuid), [
                'asesor1Id' => $asesor1->id,
                'asesor2Id' => $asesor2->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(4, (int) $akreditasi->fresh()->status);
        $this->assertSame(2, Assessment::where('akreditasi_id', $akreditasi->id)->count());

        $visitasiStart = now()->addDays(8);
        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.schedule-visitasi'), [
                'akreditasi_id' => $akreditasi->id,
                'tanggal_mulai' => $visitasiStart->toDateString(),
                'tanggal_akhir' => $visitasiStart->copy()->addDays(2)->toDateString(),
                'catatan' => '[HYBRID-E2E] Visitasi scheduled via HTTP.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(3, (int) $akreditasi->fresh()->status);

        $this->travelTo($visitasiStart);
        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.confirm-visitasi-selesai'), [
                'akreditasi_id' => $akreditasi->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->travelBack();
        $this->assertSame(2, (int) $akreditasi->fresh()->status);

        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.upload-laporan-individu'), [
                'akreditasi_id' => $akreditasi->id,
                'laporan_individu_file' => UploadedFile::fake()->create('laporan-asesor1.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($asesor2)
            ->post(route('asesor.akreditasi.upload-laporan-individu'), [
                'akreditasi_id' => $akreditasi->id,
                'laporan_individu_file' => UploadedFile::fake()->create('laporan-asesor2.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.upload-laporan-kelompok'), [
                'akreditasi_id' => $akreditasi->id,
                'laporan_kelompok_file' => UploadedFile::fake()->create('laporan-kelompok.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($pesantren)
            ->post(route('pesantren.akreditasi.upload-kartu-kendali'), [
                'akreditasi_id' => $akreditasi->id,
                'kartu_kendali_file' => UploadedFile::fake()->create('kartu-kendali.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $documents = $akreditasi->fresh();
        $this->assertSame(2, (int) $documents->status);
        foreach (['laporan_visitasi_asesor1', 'laporan_visitasi_asesor2', 'laporan_visitasi_kelompok', 'kartu_kendali'] as $field) {
            $this->assertNotNull($documents->{$field});
            Storage::disk('public')->assertExists($documents->{$field});
        }
        $this->assertDatabaseHas('akreditasis', [
            'id' => $akreditasi->id,
            'status' => 2,
            'laporan_visitasi_asesor1' => $documents->laporan_visitasi_asesor1,
            'laporan_visitasi_asesor2' => $documents->laporan_visitasi_asesor2,
            'laporan_visitasi_kelompok' => $documents->laporan_visitasi_kelompok,
            'kartu_kendali' => $documents->kartu_kendali,
        ]);

        $this->seedCompleteScoring($akreditasi->fresh(), $asesor1, $asesor2);

        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.finalize-scoring'), [
                'akreditasi_id' => $akreditasi->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(1, (int) $akreditasi->fresh()->status);

        $adminNvs = AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)
            ->whereNotNull('nk')
            ->orderBy('butir_id')
            ->pluck('nk', 'butir_id')
            ->map(fn ($value) => (int) $value)
            ->all();
        $changedButirId = array_key_first($adminNvs);
        $adminNvs[$changedButirId] = 4;

        $this->actingAs($admin)
            ->post(route('admin.akreditasi-detail.save-nv', $akreditasi->uuid), [
                'adminNvs' => [$changedButirId => 4],
                'nvReasons' => [$changedButirId => 'Draft koreksi Hybrid E2E'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseHas('akreditasi_edpms', [
            'akreditasi_id' => $akreditasi->id,
            'butir_id' => $changedButirId,
            'nv' => 4,
            'is_final' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.akreditasi-detail.finalize-nv', $akreditasi->uuid), [
                'adminNvs' => $adminNvs,
                'nvReasons' => [$changedButirId => 'Nilai dinaikkan berdasarkan validasi admin Hybrid E2E.'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertTrue((bool) $akreditasi->fresh()->is_nv_final);

        $this->actingAs($admin)
            ->post(route('admin.akreditasi-detail.approve', $akreditasi->uuid), [
                'nomor_sk' => 'HYBRID/E2E/SK/001',
                'masa_berlaku' => now()->toDateString(),
                'masa_berlaku_akhir' => now()->addYears(5)->toDateString(),
                'sertifikat_file' => UploadedFile::fake()->create('sertifikat.pdf', 10, 'application/pdf'),
                'catatan_admin' => 'Rekomendasi Hybrid E2E.',
            ])
            ->assertRedirect(route('admin.akreditasi'))
            ->assertSessionHas('success');

        $final = $akreditasi->fresh();
        $this->assertSame(0, (int) $final->status);
        $this->assertSame('HYBRID/E2E/SK/001', $final->nomor_sk);
        $this->assertNotNull($final->sertifikat_path);
        $this->assertNotNull($final->nilai);
        $this->assertNotNull($final->peringkat);
        Storage::disk('public')->assertExists($final->sertifikat_path);

        $this->actingAs($pesantren)
            ->get(route('pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid, 'tab' => 'hasil']))
            ->assertOk()
            ->assertSeeText('Nomor SK')
            ->assertSeeText($final->nomor_sk)
            ->assertSeeText('Peringkat')
            ->assertSeeText((string) $final->peringkat)
            ->assertSeeText('Sertifikat')
            ->assertSeeText('Unduh Sertifikat')
            ->assertDontSeeText('NV')
            ->assertDontSeeText('NK')
            ->assertDontSee(route('admin.akreditasi-detail.approve', $akreditasi->uuid), false)
            ->assertDontSee(route('admin.akreditasi-detail.finalize-nv', $akreditasi->uuid), false);

        $this->assertGreaterThanOrEqual(
            6,
            AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)
                ->where('action_type', 'status_changed')
                ->count()
        );
    }

    public function test_http_e2e_negative_path_blocks_invalid_actors_and_premature_transitions(): void
    {
        Storage::fake('public');
        $this->seedBusinessFlowBase();

        $pesantren = $this->createCompletePesantrenUser('hybrid.negative.pesantren@test.local');
        $otherPesantren = $this->createCompletePesantrenUser('hybrid.negative.other@test.local');
        $admin = $this->createUser('hybrid.negative.admin@test.local', 1, 'Hybrid Negative Admin');
        $asesor1 = $this->createAsesorUser('hybrid.negative.asesor1@test.local', 'Hybrid Negative Asesor 1');
        $asesor2 = $this->createAsesorUser('hybrid.negative.asesor2@test.local', 'Hybrid Negative Asesor 2');

        $this->actingAs($pesantren)
            ->post(route('pesantren.akreditasi.create'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $akreditasi = Akreditasi::where('user_id', $pesantren->id)->firstOrFail();
        $this->assertSame(6, (int) $akreditasi->status);

        $this->actingAs($asesor1)
            ->post(route('admin.akreditasi-detail.open-for-review', $akreditasi->uuid))
            ->assertForbidden();
        $this->assertNoStatusChange($akreditasi, 6);
        $this->assertNoTransitionAudit($akreditasi, 5);

        $this->actingAs($admin)
            ->post(route('admin.akreditasi-detail.approve-berkas', $akreditasi->uuid), [
                'asesor1Id' => $asesor1->id,
                'asesor2Id' => $asesor2->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNoStatusChange($akreditasi, 6);
        $this->assertSame(0, Assessment::where('akreditasi_id', $akreditasi->id)->count());

        $this->actingAs($admin)
            ->post(route('admin.akreditasi-detail.open-for-review', $akreditasi->uuid))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(5, (int) $akreditasi->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.akreditasi-detail.approve-berkas', $akreditasi->uuid), [
                'asesor1Id' => $asesor1->id,
                'asesor2Id' => $asesor1->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('asesor2Id');
        $this->assertNoStatusChange($akreditasi, 5);
        $this->assertSame(0, Assessment::where('akreditasi_id', $akreditasi->id)->count());

        $this->actingAs($admin)
            ->post(route('admin.akreditasi-detail.approve-berkas', $akreditasi->uuid), [
                'asesor1Id' => $asesor1->id,
                'asesor2Id' => $asesor2->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(4, (int) $akreditasi->fresh()->status);

        $visitasiStart = now()->addDays(8);
        $schedulePayload = [
            'akreditasi_id' => $akreditasi->id,
            'tanggal_mulai' => $visitasiStart->toDateString(),
            'tanggal_akhir' => $visitasiStart->copy()->addDay()->toDateString(),
            'catatan' => '[HYBRID-NEGATIVE] Invalid actor schedule attempt.',
        ];

        $this->actingAs($asesor2)
            ->post(route('asesor.akreditasi.schedule-visitasi'), $schedulePayload)
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNoStatusChange($akreditasi, 4);
        $this->assertNull($akreditasi->fresh()->tgl_visitasi);

        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.schedule-visitasi'), $schedulePayload)
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame(3, (int) $akreditasi->fresh()->status);

        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.confirm-visitasi-selesai'), [
                'akreditasi_id' => $akreditasi->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNoStatusChange($akreditasi, 3);
        $this->assertNull($akreditasi->fresh()->visitasi_confirmed_at);

        $this->travelTo($visitasiStart);
        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.confirm-visitasi-selesai'), [
                'akreditasi_id' => $akreditasi->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->travelBack();
        $this->assertSame(2, (int) $akreditasi->fresh()->status);

        $this->actingAs($otherPesantren)
            ->post(route('pesantren.akreditasi.upload-kartu-kendali'), [
                'akreditasi_id' => $akreditasi->id,
                'kartu_kendali_file' => UploadedFile::fake()->create('wrong-owner-kartu-kendali.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNull($akreditasi->fresh()->kartu_kendali);
        $this->assertSame([], Storage::disk('public')->allFiles('kartu_kendali'));

        $this->actingAs($asesor1)
            ->post(route('asesor.akreditasi.finalize-scoring'), [
                'akreditasi_id' => $akreditasi->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertNoStatusChange($akreditasi, 2);

        $this->actingAs($admin)
            ->post(route('admin.akreditasi-detail.approve', $akreditasi->uuid), [
                'nomor_sk' => 'HYBRID/NEGATIVE/SK/001',
                'masa_berlaku' => now()->toDateString(),
                'masa_berlaku_akhir' => now()->addYears(5)->toDateString(),
                'sertifikat_file' => UploadedFile::fake()->create('premature-sertifikat.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('warning');
        $this->assertNoStatusChange($akreditasi, 2);
        $this->assertNull($akreditasi->fresh()->sertifikat_path);
        $this->assertSame([], Storage::disk('public')->allFiles('akreditasi/sertifikat'));
    }
}
