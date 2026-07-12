<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\PesantrenUnit;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DocumentCategorySeeder;
use Database\Seeders\MasterEdpmSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesantrenFormMetronicPolishTest extends TestCase
{
    use RefreshDatabase;

    private User $pesantrenUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->seed(DocumentCategorySeeder::class);

        $this->pesantrenUser = User::factory()->create([
            'role_id' => Role::ID_PESANTREN,
            'email_verified_at' => now(),
        ]);

        $pesantren = Pesantren::create([
            'user_id' => $this->pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Polish',
            'ns_pesantren' => 'NSP-001',
            'alamat' => 'Jl. Metronic No. 1',
            'tahun_pendirian' => 2000,
            'layanan_satuan_pendidikan' => ['sd', 'smp'],
            'is_locked' => false,
        ]);

        PesantrenUnit::create([
            'pesantren_id' => $pesantren->id,
            'unit' => 'sd',
            'jumlah_rombel' => 3,
        ]);
    }

    public function test_profile_page_exposes_metrionic_form_polish_hooks(): void
    {
        $this->actingAs($this->pesantrenUser)
            ->get('/pesantren/profile')
            ->assertOk()
            ->assertSee('data-module-page="pesantren-profile"', false)
            ->assertSee('spm-pesantren-form-page', false)
            ->assertSee('spm-profile-upload-card', false)
            ->assertSee('spm-pesantren-form-actions', false);
    }

    public function test_ipm_page_exposes_metrionic_upload_polish_hooks(): void
    {
        $this->actingAs($this->pesantrenUser)
            ->get('/pesantren/ipm')
            ->assertOk()
            ->assertSee('data-module-page="pesantren-ipm"', false)
            ->assertSee('spm-pesantren-form-page', false)
            ->assertSee('spm-ipm-criteria-card', false)
            ->assertSee('spm-pesantren-file-control', false);
    }

    public function test_sdm_page_exposes_metrionic_table_polish_hooks(): void
    {
        $this->actingAs($this->pesantrenUser)
            ->get('/pesantren/sdm')
            ->assertOk()
            ->assertSee('data-module-page="pesantren-sdm"', false)
            ->assertSee('spm-pesantren-form-page', false)
            ->assertSee('spm-sdm-table-card', false)
            ->assertSee('spm-sdm-number-input', false);
    }

    public function test_edpm_page_exposes_metrionic_stepper_polish_hooks(): void
    {
        $this->seed(MasterEdpmSeeder::class);

        $this->actingAs($this->pesantrenUser)
            ->get('/pesantren/edpm')
            ->assertOk()
            ->assertSee('data-module-page="pesantren-edpm"', false)
            ->assertSee('edpmCount: 4', false)
            ->assertSee('iprCount: 1', false)
            ->assertSee('spm-pesantren-form-page', false)
            ->assertSee('spm-edpm-stepper-card', false)
            ->assertSee('spm-edpm-input-table', false);
    }
}
