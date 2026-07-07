<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNvUiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_validasi_page_renders_nv_inputs_and_reason_fields(): void
    {
        $setup = $this->createValidasiSetup(nk: 3, nv: null);

        $this->actingAs($setup['admin'])
            ->get(route('admin.akreditasi-detail', ['uuid' => $setup['akreditasi']->uuid, 'tab' => 'instrumen']))
            ->assertOk()
            ->assertSee('name="adminNvs['.$setup['butir']->id.']"', false)
            ->assertSee('name="nvReasons['.$setup['butir']->id.']"', false)
            ->assertDontSee('type="hidden" name="adminNvs['.$setup['butir']->id.']"', false)
            ->assertSee('<option value="" selected>Pilih NV</option>', false)
            ->assertSee('NK saat ini: 3', false)
            ->assertDontSee('<option value="3" selected>3</option>', false)
            ->assertDontSee("aria-label=\"Nilai Validasi butir 1\"\n                                        required", false)
            ->assertSee('Menunggu NV lengkap', false)
            ->assertDontSee('(3 / 4) x 35', false)
            ->assertSee(route('admin.akreditasi-detail.save-nv', $setup['akreditasi']->uuid), false)
            ->assertSee(route('admin.akreditasi-detail.finalize-nv', $setup['akreditasi']->uuid), false)
            ->assertSee('onclick="return confirm(\'Finalisasi semua NV? NV yang sudah final tidak dapat diubah.\')"', false)
            ->assertDontSee('data-spm-confirm=', false);
    }

    public function test_final_nv_page_shows_final_decision_actions_not_berkas_actions(): void
    {
        $setup = $this->createValidasiSetup(nk: 3, nv: 3);
        $setup['akreditasi']->update(['is_nv_final' => true]);

        $this->actingAs($setup['admin'])
            ->get(route('admin.akreditasi-detail', ['uuid' => $setup['akreditasi']->uuid, 'tab' => 'instrumen']))
            ->assertOk()
            ->assertSee('approve-final-modal', false)
            ->assertSee('reject-final-modal', false)
            ->assertDontSee("open-modal', 'approve-berkas-modal", false)
            ->assertDontSee("open-modal', 'reject-berkas-modal", false)
            ->assertSee(route('admin.akreditasi-detail.approve', $setup['akreditasi']->uuid), false)
            ->assertSee(route('admin.akreditasi-detail.reject', $setup['akreditasi']->uuid), false);
    }

    public function test_admin_can_save_nv_draft_from_ui_field_names(): void
    {
        $setup = $this->createValidasiSetup(nk: 3, nv: null);

        $this->actingAs($setup['admin'])
            ->post(route('admin.akreditasi-detail.save-nv', $setup['akreditasi']->uuid), [
                'adminNvs' => [$setup['butir']->id => 4],
                'nvReasons' => [$setup['butir']->id => 'Draft koreksi admin'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Nilai Verifikasi berhasil disimpan.')
            ->assertSessionHasInput('nvReasons.'.$setup['butir']->id, 'Draft koreksi admin');

        $this->assertDatabaseHas('akreditasi_edpms', [
            'akreditasi_id' => $setup['akreditasi']->id,
            'butir_id' => $setup['butir']->id,
            'nk' => 3,
            'nv' => 4,
            'is_final' => false,
        ]);
    }

    private function createValidasiSetup(int $nk, ?int $nv): array
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren NV UI']);
        $asesorUser = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor NV',
            'nama_tanpa_gelar' => 'Asesor NV',
        ]);

        $komponen = MasterEdpmKomponen::create(['nama' => 'MUTU LULUSAN', 'ipr' => null]);
        $butir = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Butir NV UI',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
            'laporan_visitasi_asesor1' => 'dummy.pdf',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDay(),
            'tanggal_berakhir' => now()->addDay(),
        ]);

        AkreditasiEdpm::create([
            'akreditasi_id' => $akreditasi->id,
            'pesantren_id' => $akreditasi->user_id,
            'asesor_id' => $asesor->id,
            'butir_id' => $butir->id,
            'isian' => 3,
            'nk' => $nk,
            'nv' => $nv,
            'is_final' => false,
        ]);

        return compact('admin', 'akreditasi', 'butir');
    }
}
