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

class AsesorSaveEdpmHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_asesor_can_save_edpm_evaluation_without_nk(): void
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

        // Send only evaluasi (no NK) — NK requires NA to be final first
        $response = $this->actingAs($asesorUser)->post(route('asesor.akreditasi.save-edpm'), [
            'akreditasi_id' => $akreditasi->id,
            'asesorEvaluasis' => [$butir->id => 3],
            'asesorNks' => [],
            'asesorButirCatatans' => [$butir->id => 'Catatan evaluasi'],
            'asesorCatatans' => [],
            'asesorCatatanNks' => [],
            'is_final' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Penilaian berhasil disimpan.');
        $this->assertDatabaseHas('akreditasi_edpms', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'butir_id' => $butir->id,
            'isian' => 3,
        ]);
    }

    public function test_asesor_cannot_save_edpm_before_post_visitasi(): void
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

        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => AkreditasiStateMachine::STATUS_ASSESSMENT]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor->id, 'tipe' => 1, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);

        $response = $this->actingAs($asesorUser)->post(route('asesor.akreditasi.save-edpm'), [
            'akreditasi_id' => $akreditasi->id,
            'asesorEvaluasis' => [$butir->id => 3],
            'asesorNks' => [],
            'asesorButirCatatans' => [],
            'asesorCatatans' => [],
            'asesorCatatanNks' => [],
            'is_final' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Nilai asesor hanya dapat diisi setelah visitasi dikonfirmasi selesai.');
        $this->assertDatabaseMissing('akreditasi_edpms', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'butir_id' => $butir->id,
        ]);
    }
}
