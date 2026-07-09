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

class AdminRescheduleVisitasiHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reschedule_requires_valid_dates(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_VISITASI,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.akreditasi-detail.reschedule-visitasi', $akreditasi->uuid), [
            'tgl_visitasi' => '2026-07-10',
            'tgl_visitasi_akhir' => '2026-07-05',
        ]);

        $response->assertSessionHasErrors('tgl_visitasi_akhir');
    }

    public function test_admin_reschedule_requires_both_dates(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_VISITASI,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.akreditasi-detail.reschedule-visitasi', $akreditasi->uuid), [
            'tgl_visitasi' => '',
            'tgl_visitasi_akhir' => '',
        ]);

        $response->assertSessionHasErrors(['tgl_visitasi', 'tgl_visitasi_akhir']);
    }

    public function test_admin_reschedule_fails_without_asesor_assignment(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_VISITASI,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.akreditasi-detail.reschedule-visitasi', $akreditasi->uuid), [
            'tgl_visitasi' => '2026-08-10',
            'tgl_visitasi_akhir' => '2026-08-12',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Ketua Kelompok tidak ditemukan.');
    }
}
