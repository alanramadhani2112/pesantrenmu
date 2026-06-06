<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsesorAkreditasiMenuContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_default_page_uses_general_task_context(): void
    {
        $asesor = $this->createAssignedAssessment(Akreditasi::STATUS_ASSESSMENT);

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi')
            ->assertOk()
            ->assertSee('Daftar Tugas')
            ->assertSee('Pengajuan Akreditasi')
            ->assertSee('spm-table-shell--asesor-tugas', false)
            ->assertSee('data-ui-table="metronic"', false);
    }

    public function test_review_menu_context_redirects_to_review_filter(): void
    {
        $asesor = $this->createAssignedAssessment(Akreditasi::STATUS_ASSESSMENT);

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi?focus=review')
            ->assertRedirect(route('asesor.akreditasi', [
                'statusFilter' => 'belum',
                'focus' => 'review',
            ]));

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi?statusFilter=belum&focus=review')
            ->assertOk()
            ->assertSee('Review Berkas')
            ->assertSee('Daftar Review Berkas')
            ->assertSee('spm-table-shell--asesor-review', false)
            ->assertDontSee('Daftar umum seluruh pengajuan');
    }

    public function test_input_nilai_menu_context_targets_post_visitasi_filter(): void
    {
        $asesor = $this->createAssignedAssessment(Akreditasi::STATUS_PASCA_VISITASI);

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi?focus=nilai')
            ->assertRedirect(route('asesor.akreditasi', [
                'statusFilter' => 'penilaian',
                'focus' => 'nilai',
            ]));

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi?statusFilter=penilaian&focus=nilai')
            ->assertOk()
            ->assertSee('Input Nilai Visitasi')
            ->assertSee('Daftar Input Nilai')
            ->assertSee('spm-table-shell--asesor-nilai', false)
            ->assertSee('Buka detail instrumen');
    }

    private function createAssignedAssessment(int $status): Asesor
    {
        $asesorUser = User::factory()->create(['role_id' => Role::ID_ASESOR]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Test',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        $pesantrenUser = User::factory()->create(['role_id' => Role::ID_PESANTREN]);
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addDays(7)->toDateString(),
        ]);

        return $asesor->load('user');
    }
}
