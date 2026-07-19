<?php

namespace Tests\Feature\E2E;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\MasterEdpmButir;
use App\Models\User;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AsesorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $asesor1;

    private User $asesor2;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(TestDataSeeder::class);
        $this->asesor1 = User::where('email', 'bf.asesor1@test.local')->firstOrFail();
        $this->asesor2 = User::where('email', 'bf.asesor2@test.local')->firstOrFail();
    }

    public function test_ketua_asesor_can_schedule_visitasi(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-003');
        $start = now()->addDays(8);

        $this->actingAs($this->asesor1)
            ->post(route('asesor.akreditasi.schedule-visitasi'), [
                'akreditasi_id' => $akreditasi->id,
                'tanggal_mulai' => $start->toDateString(),
                'tanggal_akhir' => $start->copy()->addDays(2)->toDateString(),
                'catatan' => 'Jadwal visitasi E2E asesor siap dilaksanakan.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $akreditasi->fresh();
        $this->assertSame(Akreditasi::STATUS_VISITASI, (int) $fresh->status);
        $this->assertSame($start->toDateString(), (string) $fresh->tgl_visitasi);
    }

    public function test_anggota_asesor_cannot_schedule_visitasi(): void
    {
        $akreditasi = $this->scenario('BF-NEG-007');
        $start = now()->addDays(8);

        $this->actingAs($this->asesor2)
            ->post(route('asesor.akreditasi.schedule-visitasi'), [
                'akreditasi_id' => $akreditasi->id,
                'tanggal_mulai' => $start->toDateString(),
                'tanggal_akhir' => $start->copy()->addDays(2)->toDateString(),
                'catatan' => 'Anggota asesor tidak boleh menjadwalkan visitasi.',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(Akreditasi::STATUS_ASSESSMENT, (int) $akreditasi->fresh()->status);
    }

    public function test_asesor_can_reject_document_for_correction(): void
    {
        $akreditasi = $this->scenario('BF-NEG-003');

        $this->actingAs($this->asesor1)
            ->post(route('asesor.akreditasi.reject-document'), [
                'akreditasi_id' => $akreditasi->id,
                'perbaikan' => ['profil', 'ipm'],
                'catatan' => 'Dokumen profil dan IPM perlu diperbaiki sebelum visitasi.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $this->asesor1->id,
            'type' => 'asesor',
            'status' => 'pending',
        ]);
    }

    public function test_ketua_asesor_can_confirm_visitasi_selesai(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-004');
        $start = now()->subDays(2);
        $akreditasi->update([
            'tgl_visitasi' => $start,
            'tgl_visitasi_akhir' => $start->copy()->addDay(),
        ]);

        $this->actingAs($this->asesor1)
            ->post(route('asesor.akreditasi.confirm-visitasi-selesai'), [
                'akreditasi_id' => $akreditasi->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(Akreditasi::STATUS_PASCA_VISITASI, (int) $akreditasi->fresh()->status);
    }

    public function test_asesor_can_save_na_score(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-005');
        $butir = MasterEdpmButir::firstOrFail();
        $asesor = Asesor::where('user_id', $this->asesor1->id)->firstOrFail();

        $this->actingAs($this->asesor1)
            ->postJson(route('asesor.akreditasi.save-na'), [
                'akreditasi_id' => $akreditasi->id,
                'butir_id' => $butir->id,
                'value' => 4,
                'is_final' => false,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('akreditasi_edpms', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'butir_id' => $butir->id,
            'isian' => 4,
            'is_final' => false,
        ]);
    }

    public function test_invalid_na_score_is_rejected_without_mutation(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-005');
        $butir = MasterEdpmButir::firstOrFail();
        $beforeCount = AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)->count();

        $this->actingAs($this->asesor1)
            ->postJson(route('asesor.akreditasi.save-na'), [
                'akreditasi_id' => $akreditasi->id,
                'butir_id' => $butir->id,
                'value' => 5,
                'is_final' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('value');

        $this->assertSame($beforeCount, AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)->count());
    }

    public function test_anggota_asesor_cannot_finalize_scoring(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-005');

        $this->actingAs($this->asesor2)
            ->post(route('asesor.akreditasi.finalize-scoring'), [
                'akreditasi_id' => $akreditasi->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(Akreditasi::STATUS_PASCA_VISITASI, (int) $akreditasi->fresh()->status);
    }

    private function scenario(string $code): Akreditasi
    {
        return Akreditasi::where('catatan', 'like', "[{$code}]%")
            ->firstOrFail();
    }
}
