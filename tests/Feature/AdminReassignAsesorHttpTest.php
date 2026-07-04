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

class AdminReassignAsesorHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reassign_asesor(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);
        
        $oldAsesorUser = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $oldAsesor = Asesor::create(['user_id' => $oldAsesorUser->id, 'nama_dengan_gelar' => 'Old Asesor', 'nama_tanpa_gelar' => 'Old Asesor']);
        
        $newAsesorUser = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $newAsesor = Asesor::create(['user_id' => $newAsesorUser->id, 'nama_dengan_gelar' => 'New Asesor', 'nama_tanpa_gelar' => 'New Asesor']);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_ASSESSMENT,
        ]);

        $assessment = Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $oldAsesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(10),
            'tanggal_berakhir' => now()->subDays(3),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.akreditasi-detail.reassign-asesor', $akreditasi->uuid), [
            'reassignAsesorId' => $newAsesor->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Asesor berhasil diganti. Deadline baru telah ditetapkan.');
        
        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $newAsesor->id,
        ]);
        $this->assertDatabaseMissing('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $oldAsesor->id,
        ]);
    }

    public function test_admin_reassign_requires_valid_asesor(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_ASSESSMENT,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.akreditasi-detail.reassign-asesor', $akreditasi->uuid), [
            'reassignAsesorId' => 99999,
        ]);

        $response->assertSessionHasErrors('reassignAsesorId');
    }
}