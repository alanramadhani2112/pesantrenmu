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

class AdminApproveBerkasHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_berkas_and_assign_two_distinct_asesor(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $asesorUser1 = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor1 = Asesor::create(['user_id' => $asesorUser1->id, 'nama_dengan_gelar' => 'Asesor 1', 'nama_tanpa_gelar' => 'Asesor 1']);
        $asesorUser2 = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor2 = Asesor::create(['user_id' => $asesorUser2->id, 'nama_dengan_gelar' => 'Asesor 2', 'nama_tanpa_gelar' => 'Asesor 2']);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.akreditasi-detail.approve-berkas', $akreditasi->uuid), [
            'asesor1Id' => $asesorUser1->id,
            'asesor2Id' => $asesorUser2->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertSame(AkreditasiStateMachine::STATUS_ASSESSMENT, (int) $akreditasi->fresh()->status);
        $this->assertDatabaseHas('assessments', ['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor1->id, 'tipe' => 1]);
        $this->assertDatabaseHas('assessments', ['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor2->id, 'tipe' => 2]);
    }
}
