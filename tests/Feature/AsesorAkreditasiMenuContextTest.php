<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
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
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_default_page_uses_general_task_context(): void
    {
        $asesor = $this->createAssignedAssessment(Akreditasi::STATUS_ASSESSMENT);

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi')
            ->assertOk()
            ->assertSee('Tugas Akreditasi')
            ->assertSee('Daftar Tugas')
            ->assertSee('Prioritas Tugas Asesor')
            ->assertSee('data-akreditasi-context="tugas"', false);
    }

    public function test_review_deeplink_redirects_to_status_filter(): void
    {
        $asesor = $this->createAssignedAssessment(Akreditasi::STATUS_ASSESSMENT);

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi?focus=review')
            ->assertRedirect(route('asesor.akreditasi', [
                'statusFilter' => 'review',
                'focus' => 'review',
            ]));

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi?statusFilter=review&focus=review')
            ->assertOk()
            ->assertSee('Tugas Akreditasi')
            ->assertSee('Daftar Tugas')
            ->assertSee('data-akreditasi-context="tugas"', false)
            ->assertSee('Lihat Detail');
    }

    public function test_input_nilai_deeplink_redirects_to_status_filter(): void
    {
        $asesor = $this->createAssignedAssessment(Akreditasi::STATUS_PASCA_VISITASI);

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi?focus=nilai')
            ->assertRedirect(route('asesor.akreditasi', [
                'statusFilter' => '2',
                'focus' => 'nilai',
            ]));

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi?statusFilter=2&focus=nilai')
            ->assertOk()
            ->assertSee('Tugas Akreditasi')
            ->assertSee('Daftar Tugas')
            ->assertSee('data-akreditasi-context="tugas"', false)
            ->assertSee('Lihat Detail');
    }

    public function test_search_results_stay_scoped_to_assigned_asesor(): void
    {
        $asesor = $this->createAssignedAssessment(Akreditasi::STATUS_ASSESSMENT, 'Pesantren Yang Ditugaskan');
        $this->createAssignedAssessment(Akreditasi::STATUS_ASSESSMENT, 'Pesantren Bocor Dari Asesor Lain');

        $this->actingAs($asesor->user)
            ->get('/asesor/akreditasi?search=Bocor')
            ->assertOk()
            ->assertDontSee('Pesantren Bocor Dari Asesor Lain');
    }

    public function test_asesor_catatan_endpoint_requires_assignment(): void
    {
        $asesor = $this->createAssignedAssessment(Akreditasi::STATUS_ASSESSMENT, 'Pesantren Yang Ditugaskan');
        $otherAsesor = $this->createAssignedAssessment(Akreditasi::STATUS_ASSESSMENT, 'Pesantren Asesor Lain');
        $otherAkreditasi = $otherAsesor->assessments()->first()->akreditasi;

        $this->actingAs($asesor->user)
            ->getJson('/asesor/akreditasi/catatan/'.$otherAkreditasi->id)
            ->assertForbidden();
    }

    private function createAssignedAssessment(int $status, string $pesantrenName = 'Pesantren Test'): Asesor
    {
        $asesorUser = User::factory()->create(['role_id' => Role::ID_ASESOR]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Test',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        $pesantrenUser = User::factory()->create([
            'name' => $pesantrenName,
            'role_id' => Role::ID_PESANTREN,
        ]);
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

        return $asesor->load('user', 'assessments.akreditasi');
    }
}
