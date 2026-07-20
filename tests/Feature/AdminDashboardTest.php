<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AkreditasiService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected AkreditasiService $akreditasiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->akreditasiService = app(AkreditasiService::class);
    }

    /**
     * Helper: create a pesantren user with an akreditasi at the given status.
     */
    private function createAkreditasiWithStatus(int $status): Akreditasi
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Dashboard Test '.$pesantrenUser->id,
        ]);

        return Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ]);
    }

    /**
     * Task 6.2: getStatusCounts() visitasi/pasca_visitasi split.
     *
     * 'visitasi' counts status 3 only.
     * 'pasca_visitasi' counts status 2 only.
     *
     * Requirements: 2.25
     */
    public function test_get_status_counts_visitasi_only_counts_status_3_and_2_separate(): void
    {
        $this->createAkreditasiWithStatus(1); // Validasi Admin — must NOT be counted
        $this->createAkreditasiWithStatus(0); // Selesai — must NOT be counted
        $this->createAkreditasiWithStatus(3); // Visitasi — must be counted in visitasi, not pasca_visitasi
        $this->createAkreditasiWithStatus(2); // Pasca Visitasi — must be counted in pasca_visitasi, not visitasi

        $counts = $this->akreditasiService->getStatusCounts();

        $this->assertEquals(1, $counts['visitasi'],
            'visitasi count should only include status 3'
        );
        $this->assertEquals(1, $counts['pasca_visitasi'],
            'pasca_visitasi count should only include status 2'
        );
    }

    /**
     * Complementary: verify non-visitasi statuses are truly excluded.
     */
    public function test_get_status_counts_visitasi_is_zero_when_only_non_visitasi_statuses_exist(): void
    {
        $this->createAkreditasiWithStatus(1); // Validasi Admin
        $this->createAkreditasiWithStatus(0); // Selesai

        $counts = $this->akreditasiService->getStatusCounts();

        $this->assertEquals(0, $counts['visitasi'],
            'visitasi count should be 0 when only status 1 and 0 exist'
        );
        $this->assertEquals(0, $counts['pasca_visitasi'],
            'pasca_visitasi count should be 0 when only status 1 and 0 exist'
        );
    }

    public function test_admin_dashboard_action_cards_link_to_filtered_akreditasi_lists(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.akreditasi', ['statusFilter' => 'verifikasi']), false);
        $response->assertSee(route('admin.akreditasi', ['statusFilter' => 'assessment']), false);
        $response->assertSee(route('admin.akreditasi', ['statusFilter' => 'visitasi']), false);
    }
}
