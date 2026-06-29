<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOpenForReviewHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_pengajuan_for_review(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_PENGAJUAN,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.akreditasi-detail.open-for-review', $akreditasi->uuid));

        $response->assertSessionHas('success');
        $this->assertSame(AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS, (int) $akreditasi->fresh()->status);
    }
}
