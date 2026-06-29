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

class AsesorSaveNkHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_ketua_can_save_nk_when_ketua_and_anggota_na_are_final(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $asesorUser1 = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor1 = Asesor::create(['user_id' => $asesorUser1->id, 'nama_dengan_gelar' => 'Asesor 1', 'nama_tanpa_gelar' => 'Asesor 1']);
        $asesorUser2 = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor2 = Asesor::create(['user_id' => $asesorUser2->id, 'nama_dengan_gelar' => 'Asesor 2', 'nama_tanpa_gelar' => 'Asesor 2']);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);
        $komponen = MasterEdpmKomponen::create(['nama' => 'Komponen', 'ipr' => null]);
        $butir = MasterEdpmButir::create(['komponen_id' => $komponen->id, 'no_sk' => '1', 'nomor_butir' => '1.1', 'butir_pernyataan' => 'Butir']);

        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => AkreditasiStateMachine::STATUS_PASCA_VISITASI]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor1->id, 'tipe' => 1, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor2->id, 'tipe' => 2, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);

        AkreditasiEdpm::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor1->id, 'butir_id' => $butir->id, 'isian' => 3, 'is_final' => true, 'pesantren_id' => $akreditasi->user_id]);
        AkreditasiEdpm::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor2->id, 'butir_id' => $butir->id, 'isian' => 4, 'is_final' => true, 'pesantren_id' => $akreditasi->user_id]);

        $response = $this->actingAs($asesorUser1)->postJson(route('asesor.akreditasi.save-nk'), [
            'akreditasi_id' => $akreditasi->id,
            'butir_id' => $butir->id,
            'value' => 4,
            'is_final' => false,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('akreditasi_edpms', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'butir_id' => $butir->id,
            'nk' => 4,
        ]);
    }
}
