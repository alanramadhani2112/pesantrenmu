<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
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

class AsesorSaveNaHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_asesor_can_save_na_value(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $asesorUser = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor = Asesor::create(['user_id' => $asesorUser->id, 'nama_dengan_gelar' => 'Asesor 1', 'nama_tanpa_gelar' => 'Asesor 1']);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);
        $komponen = MasterEdpmKomponen::create(['nama' => 'Komponen', 'ipr' => null]);
        $butir = MasterEdpmButir::create(['komponen_id' => $komponen->id, 'no_sk' => '1', 'nomor_butir' => '1.1', 'butir_pernyataan' => 'Butir']);

        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => AkreditasiStateMachine::STATUS_PASCA_VISITASI]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor->id, 'tipe' => 1, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);

        $response = $this->actingAs($asesorUser)->postJson(route('asesor.akreditasi.save-na'), [
            'akreditasi_id' => $akreditasi->id,
            'butir_id' => $butir->id,
            'value' => 3,
            'is_final' => false,
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('akreditasi_edpms', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'butir_id' => $butir->id,
            'isian' => 3,
        ]);
    }
}
