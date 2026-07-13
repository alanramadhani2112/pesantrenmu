<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MasterEdpmSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\BusinessFlow\BusinessFlowTestHelpers;
use Tests\TestCase;

class PesantrenAkreditasiMenuContextTest extends TestCase
{
    use BusinessFlowTestHelpers;
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
            ->assertSee('Pusat Akreditasi')
            ->assertSee('Satu tempat untuk mengajukan');
    }

    public function test_perbaikan_page_uses_repair_context(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);
        Akreditasi::create(['user_id' => $pesantren->id, 'status' => -1]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi/perbaikan')
            ->assertOk()
            ->assertSee('Pusat Akreditasi')
            ->assertSee('Bagian dari proses akreditasi');

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?focus=perbaikan')
            ->assertOk()
            ->assertSee('Pusat Akreditasi')
            ->assertSee('Bagian dari proses akreditasi');
    }

    public function test_kartu_kendali_page_uses_post_visitasi_context(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);
        Akreditasi::create(['user_id' => $pesantren->id, 'status' => 2]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi/kartu-kendali')
            ->assertOk()
            ->assertSee('Pusat Akreditasi')
            ->assertSee('bukan proses terpisah dari pengajuan');

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?focus=kartu_kendali')
            ->assertOk()
            ->assertSee('Pusat Akreditasi')
            ->assertSee('bukan proses terpisah dari pengajuan');
    }

    public function test_hasil_page_consolidates_result_certificate_and_banding_context(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);
        Akreditasi::create([
            'user_id' => $pesantren->id,
            'status' => 0,
            'nilai' => 91,
            'peringkat' => 'Unggul',
            'nomor_sk' => 'SK-001',
            'sertifikat_path' => 'sertifikat/sk-001.pdf',
        ]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi/hasil')
            ->assertOk()
            ->assertSee('Pusat Akreditasi')
            ->assertSee('Hasil akhir muncul di halaman yang sama');

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?focus=hasil')
            ->assertOk()
            ->assertSee('Pusat Akreditasi')
            ->assertSee('Hasil akhir muncul di halaman yang sama');
    }
    public function test_status_filter_shows_active_chip_and_reset_action(): void
    {
        $pesantren = User::factory()->create(['role_id' => Role::ID_PESANTREN]);
        Akreditasi::create(['user_id' => $pesantren->id, 'status' => 4]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi?statusFilter=4')
            ->assertOk()
            ->assertSee('Status: Assessment')
            ->assertSee('Reset Status');
    }

    public function test_complete_pesantren_sees_create_submission_button(): void
    {
        $this->seed(MasterEdpmSeeder::class);
        $pesantren = $this->createCompletePesantrenUser('complete-akreditasi@test.local');

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi')
            ->assertOk()
            ->assertSee('method="POST" id="createAkreditasiForm"', false)
            ->assertSee('Ajukan Akreditasi');
    }

    public function test_profile_with_provinsi_kode_can_submit_akreditasi(): void
    {
        $this->seed(MasterEdpmSeeder::class);
        $pesantren = $this->createCompletePesantrenUser('complete-kode-akreditasi@test.local');
        $pesantren->pesantren->update([
            'provinsi' => null,
            'provinsi_kode' => '61',
        ]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi')
            ->assertOk()
            ->assertSee('method="POST" id="createAkreditasiForm"', false)
            ->assertSee('Ajukan Akreditasi');

        $this->actingAs($pesantren)
            ->post('/pesantren/akreditasi/create')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('akreditasis', [
            'user_id' => $pesantren->id,
            'status' => 6,
        ]);
    }

    public function test_active_submission_hides_create_button_without_data_warning(): void
    {
        $this->seed(MasterEdpmSeeder::class);
        $pesantren = $this->createCompletePesantrenUser('active-akreditasi@test.local');
        Akreditasi::create(['user_id' => $pesantren->id, 'status' => 6]);

        $this->actingAs($pesantren)
            ->get('/pesantren/akreditasi')
            ->assertOk()
            ->assertDontSee('id="createAkreditasiForm"', false)
            ->assertDontSee('Data Belum Lengkap');
    }
}
