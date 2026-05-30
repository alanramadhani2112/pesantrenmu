<?php

namespace Tests\Feature\Livewire;

use App\Models\Pesantren;
use App\Models\PesantrenUnit;
use App\Models\SdmPesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PesantrenSdmFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_sdm_requires_non_negative_integer_values(): void
    {
        $user = $this->createPesantrenUserWithUnits(['smp']);

        $this->actingAs($user);

        Volt::test('pages.pesantren.sdm')
            ->set('data.smp.santri_l', -1)
            ->set('data.smp.santri_p', 'abc')
            ->call('save')
            ->assertHasErrors([
                'data.smp.santri_l' => 'min',
                'data.smp.santri_p' => 'integer',
            ])
            ->assertSee('Data SDM belum valid');
    }

    public function test_sdm_saves_valid_values_per_pesantren_unit(): void
    {
        $user = $this->createPesantrenUserWithUnits(['smp', 'ma']);

        $this->actingAs($user);

        Volt::test('pages.pesantren.sdm')
            ->set('data.smp.santri_l', 12)
            ->set('data.smp.santri_p', 10)
            ->set('data.ma.ustadz_dirosah_l', 4)
            ->set('data.ma.ustadz_dirosah_p', 3)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('notification-received');

        $smpUnitId = $user->pesantren->units()->where('unit', 'smp')->value('id');
        $maUnitId = $user->pesantren->units()->where('unit', 'ma')->value('id');

        $this->assertDatabaseHas('sdm_pesantrens', [
            'user_id' => $user->id,
            'pesantren_unit_id' => $smpUnitId,
            'tingkat' => 'smp',
            'santri_l' => 12,
            'santri_p' => 10,
        ]);

        $this->assertDatabaseHas('sdm_pesantrens', [
            'user_id' => $user->id,
            'pesantren_unit_id' => $maUnitId,
            'tingkat' => 'ma',
            'ustadz_dirosah_l' => 4,
            'ustadz_dirosah_p' => 3,
        ]);
    }

    public function test_sdm_without_profile_units_does_not_save_empty_rows(): void
    {
        $user = $this->createPesantrenUserWithUnits([]);

        $this->actingAs($user);

        Volt::test('pages.pesantren.sdm')
            ->call('save')
            ->assertDispatched('show-metronic-alert');

        $this->assertSame(0, SdmPesantren::where('user_id', $user->id)->count());
    }

    public function test_sdm_uses_reusable_metronic_table_and_inputs_without_emoji_locks(): void
    {
        $user = $this->createPesantrenUserWithUnits(['smp']);

        $this->actingAs($user);

        Volt::test('pages.pesantren.sdm')
            ->assertSee('data-ui-simple-table="metronic"', false)
            ->assertSee('data-ui-input="metronic"', false)
            ->assertSee('Simpan Rekap SDM')
            ->assertDontSee('🔒')
            ->assertDontSee('🔓');
    }

    /**
     * @param  list<string>  $units
     */
    private function createPesantrenUserWithUnits(array $units): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        $pesantren = Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren SDM',
            'layanan_satuan_pendidikan' => $units,
            'is_locked' => false,
        ]);

        foreach ($units as $unit) {
            PesantrenUnit::create([
                'pesantren_id' => $pesantren->id,
                'unit' => $unit,
                'jumlah_rombel' => 1,
            ]);
        }

        return $user->refresh();
    }
}
