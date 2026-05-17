<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AkreditasiService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAkreditasiTest extends TestCase
{
    use RefreshDatabase;

    protected AkreditasiService $akreditasiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->akreditasiService = app(AkreditasiService::class);
        Notification::fake();
    }

    /**
     * Helper: create a pesantren user with an akreditasi at the given status.
     */
    private function createAkreditasiWithStatus(int $status): Akreditasi
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Admin Test',
        ]);

        return Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ]);
    }

    /**
     * Task 5.3: finalizeAkreditasi returns false when status != 3 (Validasi).
     *
     * Requirements: 2.20
     */
    public function test_admin_cannot_finalize_akreditasi_not_in_status_validasi(): void
    {
        $adminUser = User::factory()->create(['role_id' => 1]);
        $this->actingAs($adminUser);

        // Test with status 5 (Assessment) — not status 3
        $akreditasi = $this->createAkreditasiWithStatus(5);

        $result = $this->akreditasiService->finalizeAkreditasi(
            $akreditasi->id,
            [
                'nomor_sk' => 'SK/001/2024',
                'masa_berlaku' => now()->toDateString(),
                'masa_berlaku_akhir' => now()->addYears(5)->toDateString(),
                'nilai' => 90,
                'peringkat' => 'A',
            ],
            true // isApprove
        );

        $this->assertFalse($result);

        // Status must remain unchanged
        $this->assertEquals(5, $akreditasi->fresh()->status);
    }

    /**
     * Task 8.4: rejectPengajuan throws DomainException when status != 6 (Pengajuan).
     *
     * Requirements: 2.21
     */
    public function test_admin_cannot_reject_pengajuan_not_in_status_pengajuan(): void
    {
        $adminUser = User::factory()->create(['role_id' => 1]);
        $this->actingAs($adminUser);

        // Create akreditasi at status 5 (Assessment) — not status 6
        $akreditasi = $this->createAkreditasiWithStatus(5);

        $this->expectException(\DomainException::class);

        $this->akreditasiService->rejectPengajuan($akreditasi->id, 'Alasan penolakan');
    }
}
