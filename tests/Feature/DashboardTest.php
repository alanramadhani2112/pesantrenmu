<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_pesantren_dashboard_uses_user_owned_readiness_data(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Dashboard',
            'alamat' => 'Jl. Dashboard',
        ]);

        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'ipm/nsp.pdf',
        ]);

        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'spm',
        ]);

        $komponen = MasterEdpmKomponen::create(['nama' => 'Standar Isi']);
        $butir = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Pesantren memiliki dokumen kurikulum.',
        ]);

        Edpm::create([
            'user_id' => $user->id,
            'butir_id' => $butir->id,
            'isian' => '4',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Data SDM')
            ->assertSee('EDPM/IPR');
    }

    public function test_locked_pesantren_readiness_links_to_active_akreditasi_detail(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Locked',
            'alamat' => 'Jl. Locked',
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => Akreditasi::STATUS_PENGAJUAN,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('pesantren.akreditasi-detail', $akreditasi->uuid), false)
            ->assertSee('Lihat data', false)
            ->assertDontSee('Lengkapi →', false);
    }
    public function test_pesantren_status_cards_link_to_filtered_akreditasi_center(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Status Links',
            'alamat' => 'Jl. Status',
        ]);

        Akreditasi::create(['user_id' => $user->id, 'status' => Akreditasi::STATUS_ASSESSMENT]);
        Akreditasi::create(['user_id' => $user->id, 'status' => Akreditasi::STATUS_VISITASI]);
        Akreditasi::create(['user_id' => $user->id, 'status' => Akreditasi::STATUS_DITOLAK]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('pesantren.akreditasi'), false)
            ->assertSee(route('pesantren.akreditasi', ['statusFilter' => 4]), false)
            ->assertSee(route('pesantren.akreditasi', ['statusFilter' => 3]), false)
            ->assertSee(route('pesantren.akreditasi', ['statusFilter' => -1]), false);
    }
}
