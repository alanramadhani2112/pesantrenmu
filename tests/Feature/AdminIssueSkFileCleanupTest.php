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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminIssueSkFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_issue_sk_cleans_up_uploaded_certificate_file(): void
    {
        Storage::fake('public');

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

        $response = $this->actingAs($admin)->post(route('admin.akreditasi-detail.approve', $akreditasi->uuid), [
            'nomor_sk' => 'SK-001',
            'masa_berlaku' => now()->toDateString(),
            'masa_berlaku_akhir' => now()->addYear()->toDateString(),
            'sertifikat_file' => UploadedFile::fake()->create('sertifikat.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHas('error');
        Storage::disk('public')->assertDirectoryEmpty('akreditasi/sertifikat');
        $this->assertNull($akreditasi->fresh()->sertifikat_path);
    }
}
