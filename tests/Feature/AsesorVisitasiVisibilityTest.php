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

class AsesorVisitasiVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_asesor_satu_can_schedule_visitasi_at_assessment_status(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $asesorUser = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor Satu',
            'nama_tanpa_gelar' => 'Asesor Satu',
        ]);

        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_ASSESSMENT,
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDay(),
            'tanggal_berakhir' => now()->addDay(),
        ]);

        $this->actingAs($asesorUser)->post(route('asesor.akreditasi.schedule-visitasi'), [
            'akreditasi_id' => $akreditasi->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_akhir' => now()->addDays(8)->toDateString(),
            'catatan' => 'Jadwal visitasi test',
        ]);

        $fresh = $akreditasi->fresh();
        $this->assertSame(AkreditasiStateMachine::STATUS_VISITASI, (int) $fresh->status);
        $this->assertNotNull($fresh->tgl_visitasi);
    }
}
