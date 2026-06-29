<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesantrenHasilAkhirVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pesantren_hasil_tab_shows_final_fields_without_raw_nk_nv_labels(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Pesantren Hasil']);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'uuid' => 'test-hasil-uuid',
            'status' => AkreditasiStateMachine::STATUS_SELESAI,
            'nilai' => 91,
            'peringkat' => 'A - Unggul',
            'nomor_sk' => 'SK-001',
            'sertifikat_path' => 'sertifikat/sk-001.pdf',
            'masa_berlaku_akhir' => '2030-01-01',
            'catatan_rekomendasi_admin' => 'Pertahankan mutu pembelajaran.',
        ]);

        $response = $this->actingAs($user)->get(route('pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid, 'tab' => 'hasil']));

        $response->assertOk();
        $response->assertSee('Nilai Akhir');
        $response->assertSee('Peringkat');
        $response->assertSee('Nomor SK');
        $response->assertSee('Unduh Sertifikat');
        $response->assertDontSee('NK');
        $response->assertDontSee('NV');
        $response->assertDontSee('NA1');
        $response->assertDontSee('NA2');
    }
}
