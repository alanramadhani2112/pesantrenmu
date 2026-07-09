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

class AdminRejectValidasiHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reject_at_validasi_with_categories(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.akreditasi-detail.reject', $akreditasi->uuid), [
            'rejectionCategories' => [
                [
                    'category' => 'Dokumen Tidak Lengkap',
                    'explanation' => 'Laporan visitasi belum mencakup seluruh komponen yang diperlukan.',
                ],
                [
                    'category' => 'Nilai NV Bermasalah',
                    'explanation' => 'Beberapa nilai NV tidak memiliki alasan perubahan yang jelas dari NK.',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.akreditasi'));
        $response->assertSessionHas('success', 'Akreditasi telah ditolak.');
        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $akreditasi->id,
            'type' => 'admin_final',
            'status' => 'final',
        ]);
    }

    public function test_admin_reject_requires_categories(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role_id' => 1, 'email_verified_at' => now()]);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.akreditasi-detail.reject', $akreditasi->uuid), [
            'rejectionCategories' => [],
        ]);

        $response->assertSessionHasErrors('rejectionCategories');
        $this->assertDatabaseMissing('akreditasi_rejections', [
            'akreditasi_id' => $akreditasi->id,
        ]);
    }
}
