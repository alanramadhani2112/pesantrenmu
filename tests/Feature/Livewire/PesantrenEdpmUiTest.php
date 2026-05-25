<?php

namespace Tests\Feature\Livewire;

use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PesantrenEdpmUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_pesantren_edpm_page_separates_edpm_and_ipr_components(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren UI',
            'is_locked' => false,
        ]);

        $edpm = MasterEdpmKomponen::create(['nama' => 'MUTU LULUSAN', 'ipr' => null]);
        MasterEdpmButir::create([
            'komponen_id' => $edpm->id,
            'no_sk' => '1',
            'nomor_butir' => '1',
            'butir_pernyataan' => 'Santri berakhlak mulia.',
        ]);

        $ipr = MasterEdpmKomponen::create(['nama' => 'B. INDIKATOR PEMENUHAN RELATIF', 'ipr' => 1]);
        MasterEdpmButir::create([
            'komponen_id' => $ipr->id,
            'no_sk' => '',
            'nomor_butir' => '1',
            'butir_pernyataan' => 'Kualifikasi akademik guru minimum sarjana.',
        ]);

        $this->actingAs($user);

        $component = Volt::test('pages.pesantren.edpm')
            ->assertSee('Komponen EDPM')
            ->assertSee('Komponen IPR')
            ->assertSee('MUTU LULUSAN')
            ->assertDontSee('B. INDIKATOR PEMENUHAN RELATIF')
            ->assertSee('Komponen')
            ->assertSee('Butir Pernyataan');

        $component
            ->call('setGroup', 'ipr')
            ->assertSet('activeGroup', 'ipr')
            ->assertSee('B. INDIKATOR PEMENUHAN RELATIF')
            ->assertDontSee('MUTU LULUSAN')
            ->assertSee('Komponen');
    }
}
