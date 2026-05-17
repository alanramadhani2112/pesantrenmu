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
     * Task 6.2: getStatusCounts()['visitasi'] only counts status 3 and 4,
     * not status 1 (Berhasil) or 2 (Ditolak).
     *
     * Requirements: 2.25
     */
    public function test_get_status_counts_visitasi_excludes_status_1_and_2(): void
    {
        // Create akreditasis at each status
        $this->createAkreditasiWithStatus(1); // Berhasil — must NOT be counted
        $this->createAkreditasiWithStatus(2); // Ditolak — must NOT be counted
        $this->createAkreditasiWithStatus(3); // Validasi — must be counted
        $this->createAkreditasiWithStatus(4); // Visitasi — must be counted

        $counts = $this->akreditasiService->getStatusCounts();

        // visitasi should be exactly 2 (status 3 + status 4)
        $this->assertEquals(2, $counts['visitasi'],
            'visitasi count should only include status 3 and 4, not 1 or 2'
        );
    }

    /**
     * Complementary: verify status 1 and 2 are truly excluded even when they exist.
     */
    public function test_get_status_counts_visitasi_is_zero_when_only_status_1_and_2_exist(): void
    {
        $this->createAkreditasiWithStatus(1);
        $this->createAkreditasiWithStatus(2);

        $counts = $this->akreditasiService->getStatusCounts();

        $this->assertEquals(0, $counts['visitasi'],
            'visitasi count should be 0 when only status 1 and 2 exist'
        );
    }
}
