<?php

namespace Tests\Feature\E2E;

use App\Models\Akreditasi;
use App\Models\User;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $asesor1;

    private User $asesor2;

    private User $pesantren;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('public');
        $this->seed(TestDataSeeder::class);
        $this->asesor1 = User::where('email', 'bf.asesor1@test.local')->firstOrFail();
        $this->asesor2 = User::where('email', 'bf.asesor2@test.local')->firstOrFail();
        $this->pesantren = User::where('email', 'bf.pesantren@test.local')->firstOrFail();
    }

    public function test_assigned_asesors_can_upload_individual_reports(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-005');

        $this->actingAs($this->asesor1)
            ->post(route('asesor.akreditasi.upload-laporan-individu'), [
                'akreditasi_id' => $akreditasi->id,
                'laporan_individu_file' => UploadedFile::fake()->create('laporan-asesor1.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->asesor2)
            ->post(route('asesor.akreditasi.upload-laporan-individu'), [
                'akreditasi_id' => $akreditasi->id,
                'laporan_individu_file' => UploadedFile::fake()->create('laporan-asesor2.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $akreditasi->fresh();
        $this->assertNotNull($fresh->laporan_visitasi_asesor1);
        $this->assertNotNull($fresh->laporan_visitasi_asesor2);
        Storage::disk('public')->assertExists($fresh->laporan_visitasi_asesor1);
        Storage::disk('public')->assertExists($fresh->laporan_visitasi_asesor2);
    }

    public function test_only_ketua_asesor_can_upload_group_report(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-005');

        $this->actingAs($this->asesor2)
            ->post(route('asesor.akreditasi.upload-laporan-kelompok'), [
                'akreditasi_id' => $akreditasi->id,
                'laporan_kelompok_file' => UploadedFile::fake()->create('laporan-kelompok-anggota.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($this->asesor1)
            ->post(route('asesor.akreditasi.upload-laporan-kelompok'), [
                'akreditasi_id' => $akreditasi->id,
                'laporan_kelompok_file' => UploadedFile::fake()->create('laporan-kelompok.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $akreditasi->fresh();
        $this->assertNotNull($fresh->laporan_visitasi_kelompok);
        Storage::disk('public')->assertExists($fresh->laporan_visitasi_kelompok);
    }

    public function test_pesantren_can_upload_kartu_kendali_for_own_akreditasi(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-005');

        $this->actingAs($this->pesantren)
            ->post(route('pesantren.akreditasi.upload-kartu-kendali'), [
                'akreditasi_id' => $akreditasi->id,
                'kartu_kendali_file' => UploadedFile::fake()->create('kartu-kendali.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $akreditasi->fresh();
        $this->assertNotNull($fresh->kartu_kendali);
        Storage::disk('public')->assertExists($fresh->kartu_kendali);
    }

    public function test_uploads_are_blocked_outside_pasca_visitasi(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-004');

        $this->actingAs($this->asesor1)
            ->post(route('asesor.akreditasi.upload-laporan-individu'), [
                'akreditasi_id' => $akreditasi->id,
                'laporan_individu_file' => UploadedFile::fake()->create('laporan-visitasi.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($this->pesantren)
            ->post(route('pesantren.akreditasi.upload-kartu-kendali'), [
                'akreditasi_id' => $akreditasi->id,
                'kartu_kendali_file' => UploadedFile::fake()->create('kartu-kendali.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_invalid_upload_type_is_rejected(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-005');

        $this->actingAs($this->asesor1)
            ->post(route('asesor.akreditasi.upload-laporan-individu'), [
                'akreditasi_id' => $akreditasi->id,
                'laporan_individu_file' => UploadedFile::fake()->create('laporan.txt', 1, 'text/plain'),
            ])
            ->assertSessionHasErrors('laporan_individu_file');
    }

    private function scenario(string $code): Akreditasi
    {
        return Akreditasi::where('catatan', 'like', "[{$code}]%")
            ->firstOrFail();
    }
}
