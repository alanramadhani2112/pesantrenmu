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
            ->assertSee('Ruang Kendali Pengajuan')
            ->assertSee('Daftar Pengajuan');
    }

    public function test_perbaikan_page_uses_repair_context(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);
        Akreditasi::create(['user_id' => $pesantren->id, 'status' => -1]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?statusFilter=-1&focus=perbaikan')
            ->assertOk()
            ->assertSee('Status Perbaikan')
            ->assertSee('Daftar Perbaikan')
            ->assertSee('Bagian Perbaikan')
            ->assertDontSee('Ruang Kendali Pengajuan');
    }

    public function test_kartu_kendali_page_uses_post_visitasi_context(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);
        Akreditasi::create(['user_id' => $pesantren->id, 'status' => 2]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?statusFilter=2&focus=kartu_kendali')
            ->assertOk()
            ->assertSee('Kartu Kendali Visitasi')
            ->assertSee('Daftar Kartu Kendali')
            ->assertSee('Status Kartu');
    }

    public function test_hasil_sertifikat_and_banding_pages_use_distinct_contexts(): void
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
            ->get('/pesantren/akreditasi?statusFilter=0&focus=hasil')
            ->assertOk()
            ->assertSee('Hasil Akhir Akreditasi')
            ->assertSee('Nilai Akhir')
            ->assertSee('Rekomendasi');

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?statusFilter=0&focus=sertifikat')
            ->assertOk()
            ->assertSee('Sertifikat Akreditasi')
            ->assertSee('Nomor SK')
            ->assertSee('Unduh Sertifikat');

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?statusFilter=-2&focus=banding')
            ->assertOk()
            ->assertSee('Banding Akreditasi')
            ->assertSee('Status Banding')
            ->assertSee('Alasan Banding');
    }
}
