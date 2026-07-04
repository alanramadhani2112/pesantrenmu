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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AsesorUploadLaporanHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_asesor_can_upload_laporan_individu(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $asesorUser = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor = Asesor::create(['user_id' => $asesorUser->id, 'nama_dengan_gelar' => 'Asesor 1', 'nama_tanpa_gelar' => 'Asesor 1']);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => AkreditasiStateMachine::STATUS_PASCA_VISITASI]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor->id, 'tipe' => 1, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);

        $file = UploadedFile::fake()->create('laporan.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($asesorUser)->post(route('asesor.akreditasi.upload-laporan-individu'), [
            'akreditasi_id' => $akreditasi->id,
            'laporan_individu_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Laporan individu berhasil diunggah.');
        Storage::disk('public')->assertExists($akreditasi->fresh()->laporan_individu_asesor1);
    }

    public function test_asesor_cannot_upload_laporan_individu_before_post_visitasi(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $asesorUser = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor = Asesor::create(['user_id' => $asesorUser->id, 'nama_dengan_gelar' => 'Asesor 1', 'nama_tanpa_gelar' => 'Asesor 1']);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => AkreditasiStateMachine::STATUS_VISITASI]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor->id, 'tipe' => 1, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);

        $file = UploadedFile::fake()->create('laporan.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($asesorUser)->post(route('asesor.akreditasi.upload-laporan-individu'), [
            'akreditasi_id' => $akreditasi->id,
            'laporan_individu_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Upload hanya diperbolehkan pada tahap pasca visitasi.');
    }

    public function test_asesor1_can_upload_laporan_kelompok(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $asesorUser = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor = Asesor::create(['user_id' => $asesorUser->id, 'nama_dengan_gelar' => 'Asesor 1', 'nama_tanpa_gelar' => 'Asesor 1']);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => AkreditasiStateMachine::STATUS_PASCA_VISITASI]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor->id, 'tipe' => 1, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);

        $file = UploadedFile::fake()->create('laporan.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($asesorUser)->post(route('asesor.akreditasi.upload-laporan-kelompok'), [
            'akreditasi_id' => $akreditasi->id,
            'laporan_kelompok_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Laporan kelompok berhasil diunggah.');
        Storage::disk('public')->assertExists($akreditasi->fresh()->laporan_kelompok_asesor1);
    }

    public function test_asesor2_cannot_upload_laporan_kelompok(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $asesor1User = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor1 = Asesor::create(['user_id' => $asesor1User->id, 'nama_dengan_gelar' => 'Asesor 1', 'nama_tanpa_gelar' => 'Asesor 1']);
        
        $asesor2User = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor2 = Asesor::create(['user_id' => $asesor2User->id, 'nama_dengan_gelar' => 'Asesor 2', 'nama_tanpa_gelar' => 'Asesor 2']);
        
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => AkreditasiStateMachine::STATUS_PASCA_VISITASI]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor1->id, 'tipe' => 1, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor2->id, 'tipe' => 2, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);

        $file = UploadedFile::fake()->create('laporan.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($asesor2User)->post(route('asesor.akreditasi.upload-laporan-kelompok'), [
            'akreditasi_id' => $akreditasi->id,
            'laporan_kelompok_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Hanya Asesor 1 yang dapat mengunggah laporan kelompok.');
    }

    public function test_upload_laporan_individu_requires_valid_file(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $asesorUser = User::factory()->create(['role_id' => 2, 'email_verified_at' => now()]);
        $asesor = Asesor::create(['user_id' => $asesorUser->id, 'nama_dengan_gelar' => 'Asesor 1', 'nama_tanpa_gelar' => 'Asesor 1']);
        $pesantrenUser = User::factory()->create(['role_id' => 3, 'email_verified_at' => now()]);
        Pesantren::create(['user_id' => $pesantrenUser->id, 'nama_pesantren' => 'Pesantren Test']);

        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => AkreditasiStateMachine::STATUS_PASCA_VISITASI]);
        Assessment::create(['akreditasi_id' => $akreditasi->id, 'asesor_id' => $asesor->id, 'tipe' => 1, 'tanggal_mulai' => now()->subDay(), 'tanggal_berakhir' => now()->addDay()]);

        $response = $this->actingAs($asesorUser)->post(route('asesor.akreditasi.upload-laporan-individu'), [
            'akreditasi_id' => $akreditasi->id,
            'laporan_individu_file' => null,
        ]);

        $response->assertSessionHasErrors('laporan_individu_file');
    }
}