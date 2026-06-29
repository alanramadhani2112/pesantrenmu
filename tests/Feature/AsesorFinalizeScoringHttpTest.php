<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsesorFinalizeScoringHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_ketua_cannot_finalize_scoring(): void
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

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_PASCA_VISITASI,
        ]);

        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor1->id, 'tipe' => 1, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor2->id, 'tipe' => 2, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);

        $response = $this->actingAs($asesorUser2)->post(route('asesor.akreditasi.finalize-scoring'), [
            'akreditasi_id' => $akreditasi->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(AkreditasiStateMachine::STATUS_PASCA_VISITASI, (int) $akreditasi->fresh()->status);
    }
}
