<?php

namespace Tests\Feature\Livewire;

use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PesantrenProfileFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_edit_mode_shows_cancel_draft_and_submit_actions(): void
    {
        $user = $this->createPesantrenUser();

        $this->actingAs($user);

        Volt::test('pages.pesantren.profile')
            ->call('toggleEdit')
            ->assertSet('isEditing', true)
            ->assertSee('Batal')
            ->assertSee('Submit Draft')
            ->assertSee('Submit');
    }

    public function test_submit_draft_allows_incomplete_profile_data(): void
    {
        $user = $this->createPesantrenUser();

        $this->actingAs($user);

        Volt::test('pages.pesantren.profile')
            ->call('toggleEdit')
            ->set('nama_pesantren', '')
            ->set('alamat', '')
            ->set('provinsi', '')
            ->call('saveDraft')
            ->assertHasNoErrors()
            ->assertDispatched('notification-received');

        $this->assertDatabaseHas('pesantrens', [
            'user_id' => $user->id,
            'nama_pesantren' => '',
            'alamat' => '',
            'provinsi' => '',
        ]);
    }

    public function test_submit_final_requires_core_profile_fields_and_shows_danger_alert(): void
    {
        $user = $this->createPesantrenUser();

        $this->actingAs($user);

        Volt::test('pages.pesantren.profile')
            ->call('toggleEdit')
            ->set('nama_pesantren', '')
            ->set('ns_pesantren', '')
            ->set('alamat', '')
            ->set('provinsi', '')
            ->set('kota_kabupaten', '')
            ->set('tahun_pendirian', '')
            ->set('nama_mudir', '')
            ->set('layanan_satuan_pendidikan', [])
            ->call('save')
            ->assertHasErrors([
                'nama_pesantren' => 'required',
                'ns_pesantren' => 'required',
                'alamat' => 'required',
                'provinsi' => 'required',
                'kota_kabupaten' => 'required',
                'tahun_pendirian' => 'required',
                'nama_mudir' => 'required',
                'layanan_satuan_pendidikan' => 'required',
            ])
            ->assertSee('Data profil belum lengkap')
            ->assertSee('Nama Pesantren wajib diisi.')
            ->assertSee('id="nama_pesantren"', false)
            ->assertSee('class="form-control form-control-solid is-invalid"', false);
    }

    public function test_profile_completeness_requires_layanan_satuan_pendidikan(): void
    {
        $user = $this->createPesantrenUser();

        $user->pesantren->update([
            'nama_pesantren' => 'Pesantren Tertata',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. Pendidikan No. 12',
            'provinsi' => 'Jawa Tengah',
            'kota_kabupaten' => 'Kota Surakarta',
            'tahun_pendirian' => '1998',
            'nama_mudir' => 'Ahmad Mudir',
            'layanan_satuan_pendidikan' => [],
        ]);

        $missing = app(\App\Services\PesantrenService::class)->checkDataCompleteness($user->id);

        $this->assertContains('Profil Pesantren belum lengkap: Layanan Satuan Pendidikan', $missing);
    }

    public function test_edit_mode_uses_reusable_checkbox_and_entangled_wilayah_selector(): void
    {
        $user = $this->createPesantrenUser();

        $this->actingAs($user);

        Volt::test('pages.pesantren.profile')
            ->call('toggleEdit')
            ->assertSee("selectedProvinsiKode: \$wire.entangle('provinsi_kode')", false)
            ->assertSee("selectedProvinsiNama: \$wire.entangle('provinsi')", false)
            ->assertSee("selectedKabupatenKode: \$wire.entangle('kabupaten_kode')", false)
            ->assertSee("selectedKabupatenNama: \$wire.entangle('kota_kabupaten')", false)
            ->assertSee(':disabled="!currentProvinsiKode"', false)
            ->assertSee('data-ui-checkbox="metronic"', false)
            ->assertDontSee("entangle('provinsi_kode')=\"\"", false);
    }

    public function test_submit_final_saves_complete_profile_and_exits_edit_mode(): void
    {
        $user = $this->createPesantrenUser();

        $this->actingAs($user);

        Volt::test('pages.pesantren.profile')
            ->call('toggleEdit')
            ->set('nama_pesantren', 'Pesantren Tertata')
            ->set('ns_pesantren', '510012345678')
            ->set('alamat', 'Jl. Pendidikan No. 12')
            ->set('provinsi', 'Jawa Tengah')
            ->set('kota_kabupaten', 'Kota Surakarta')
            ->set('tahun_pendirian', '1998')
            ->set('nama_mudir', 'Ahmad Mudir')
            ->set('layanan_satuan_pendidikan', ['smp'])
            ->set('units_data.smp.jumlah_rombel', 3)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('isEditing', false)
            ->assertDispatched('notification-received');

        $this->assertDatabaseHas('pesantrens', [
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Tertata',
            'ns_pesantren' => '510012345678',
            'alamat' => 'Jl. Pendidikan No. 12',
            'provinsi' => 'Jawa Tengah',
            'kota_kabupaten' => 'Kota Surakarta',
        ]);

        $this->assertDatabaseHas('pesantren_units', [
            'unit' => 'smp',
            'jumlah_rombel' => 3,
        ]);
    }

    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Draft',
            'is_locked' => false,
        ]);

        return $user->refresh();
    }
}
