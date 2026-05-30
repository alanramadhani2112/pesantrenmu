<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Banding;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesantrenAkreditasiMenuContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_pengajuan_page_uses_default_submission_context(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);
        Akreditasi::create(['user_id' => $pesantren->id, 'status' => 6]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi')
            ->assertOk()
            ->assertSee('Pengajuan Akreditasi')
            ->assertSee('Daftar Pengajuan')
            ->assertSee('data-ui-table="metronic"', false)
            ->assertDontSee('Alur Menu Ini');
    }

    public function test_perbaikan_page_uses_repair_context(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);
        Akreditasi::create(['user_id' => $pesantren->id, 'status' => -1]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?focus=perbaikan')
            ->assertOk()
            ->assertSee('Status Perbaikan')
            ->assertSee('Daftar Perbaikan')
            ->assertSee('Bagian Perbaikan')
            ->assertDontSee('Ruang Kendali Pengajuan')
            ->assertDontSee('Alur Menu Ini');
    }

    public function test_kartu_kendali_page_uses_post_visitasi_context(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);
        Akreditasi::create(['user_id' => $pesantren->id, 'status' => 2]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?focus=kartu_kendali')
            ->assertOk()
            ->assertSee('Kartu Kendali Visitasi')
            ->assertSee('Daftar Kartu Kendali')
            ->assertSee('Status Kartu')
            ->assertDontSee('Lihat Semua Pengajuan');
    }

    public function test_hasil_page_consolidates_result_certificate_and_banding_context(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);
        $selesai = Akreditasi::create([
            'user_id' => $pesantren->id,
            'status' => 0,
            'nilai' => 91,
            'peringkat' => 'Unggul',
            'nomor_sk' => 'SK-001',
            'sertifikat_path' => 'sertifikat/sk-001.pdf',
        ]);
        $banding = Akreditasi::create(['user_id' => $pesantren->id, 'status' => -2]);
        Banding::create([
            'akreditasi_id' => $banding->id,
            'user_id' => $pesantren->id,
            'status' => 'pending',
            'alasan' => 'Nilai perlu ditinjau ulang.',
        ]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?focus=hasil')
            ->assertOk()
            ->assertSee('Hasil Akhir Akreditasi')
            ->assertSee('Rekomendasi')
            ->assertSee('Sertifikat')
            ->assertSee('Status Banding')
            ->assertSee('Unduh Sertifikat')
            ->assertSee('Nilai perlu ditinjau ulang.')
            ->assertSee('spm-table-shell--pesantren-hasil', false)
            ->assertDontSee('Lihat Semua Pengajuan');

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?statusFilter=0&focus=sertifikat')
            ->assertRedirect(route('pesantren.akreditasi', ['focus' => 'hasil']));

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?statusFilter=-2&focus=banding')
            ->assertRedirect(route('pesantren.akreditasi', ['focus' => 'hasil']));

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?statusFilter=0&focus=hasil')
            ->assertRedirect(route('pesantren.akreditasi', ['focus' => 'hasil']));
    }
}
