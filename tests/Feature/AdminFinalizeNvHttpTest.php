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

class AdminFinalizeNvHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalize_nv_does_not_mark_global_final_when_reason_is_missing(): void
    {
        $setup = $this->createSetup();

        $this->actingAs($setup['admin'])->post(route('admin.akreditasi-detail.finalize-nv', $setup['akreditasi']->uuid), [
            'adminNvs' => [
                $setup['butir']->id => 4,
            ],
            'nvReason' => '',
        ]);

        $this->assertDatabaseHas('akreditasi_edpms', [
            'akreditasi_id' => $setup['akreditasi']->id,
            'butir_id' => $setup['butir']->id,
            'nv' => null,
            'is_final' => false,
        ]);
        $this->assertFalse((bool) $setup['akreditasi']->fresh()->is_nv_final);
    }

    private function createSetup(): array
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);
        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create(['user_id' => $asesorUser->id, 'nama_dengan_gelar' => 'Asesor', 'nama_tanpa_gelar' => 'Asesor']);

        $komponen = MasterEdpmKomponen::create(['nama' => 'Komponen', 'ipr' => null]);
        $butir = MasterEdpmButir::create(['komponen_id' => $komponen->id, 'no_sk' => '1', 'nomor_butir' => '1.1', 'butir_pernyataan' => 'Butir']);

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
            'nk' => 3,
            'is_final' => false,
        ]);

        return compact('admin', 'akreditasi', 'butir');
    }
}
