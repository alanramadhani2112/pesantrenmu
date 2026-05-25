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
            'nama_pesantren' => 'Pesantren Dashboard Test ' . $pesantrenUser->id,
        ]);

        return Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ]);
    }

    /**
     * Task 6.2: getStatusCounts()['visitasi'] only counts status 3 and 2,
     * not status 1 (Validasi Admin) or 0 (Selesai).
     *
     * Service counts 'visitasi' as status [3 (Visitasi), 2 (Pasca Visitasi)].
     *
     * Requirements: 2.25
     */
    public function test_get_status_counts_visitasi_only_counts_status_3_and_2(): void
    {
        $this->createAkreditasiWithStatus(1); // Validasi Admin — must NOT be counted
        $this->createAkreditasiWithStatus(0); // Selesai — must NOT be counted
        $this->createAkreditasiWithStatus(3); // Visitasi — must be counted
        $this->createAkreditasiWithStatus(2); // Pasca Visitasi — must be counted

        $counts = $this->akreditasiService->getStatusCounts();

        $this->assertEquals(2, $counts['visitasi'],
            'visitasi count should only include status 3 and 2, not 1 or 0'
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
    }
}
