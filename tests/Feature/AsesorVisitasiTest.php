<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AsesorService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AsesorVisitasiTest extends TestCase
{
    use RefreshDatabase;

    protected AsesorService $asesorService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->asesorService = app(AsesorService::class);
        Notification::fake();
    }

    /**
     * Helper: create a pesantren user with an akreditasi at status 3 (Validasi).
     */
    private function createAkreditasi(int $status = 3): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Visitasi Test',
        ]);

        // Admin for notifications
        User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ]);

        return [$pesantrenUser, $akreditasi];
    }

    /**
     * Helper: create an asesor user and assign them to an akreditasi with a given tipe.
     */
    private function createAndAssignAsesor(int $akreditasiId, int $tipe): array
    {
        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => "Asesor Tipe {$tipe} Test",
            'nama_tanpa_gelar' => "Asesor Tipe {$tipe} Test",
        ]);
        Assessment::create([
            'akreditasi_id' => $akreditasiId,
            'asesor_id' => $asesor->id,
            'tipe' => $tipe,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addDays(30)->toDateString(),
        ]);

        return [$asesorUser, $asesor];
    }

    /**
     * Task 4.3: Asesor 2 (tipe=2) cannot call processVisitasi — returns false.
     *
     * Only asesor 1 (tipe=1) is authorised to process visitasi.
     */
    public function test_asesor_2_cannot_call_process_visitasi(): void
    {
        [, $akreditasi] = $this->createAkreditasi();

        // Assign asesor 1 (required to exist for the akreditasi to be valid)
        $this->createAndAssignAsesor($akreditasi->id, 1);

        // Assign asesor 2 — this is the user who will attempt the action
        [$asesor2User] = $this->createAndAssignAsesor($akreditasi->id, 2);

        $result = $this->asesorService->processVisitasi(
            $akreditasi->id,
            $asesor2User->id,
            ['tanggal' => now()->addDays(7)->toDateString(), 'tanggal_akhir' => now()->addDays(7)->toDateString(), 'catatan' => ''],
            'terima'
        );

        $this->assertFalse($result);

        // Status must remain unchanged
        $this->assertEquals(3, $akreditasi->fresh()->status);
    }

    /**
     * Task 4.3: An asesor not assigned to the akreditasi cannot call processVisitasi — returns false.
     */
    public function test_asesor_not_assigned_cannot_call_process_visitasi(): void
    {
        [, $akreditasi] = $this->createAkreditasi();

        // Assign a legitimate asesor 1 to the akreditasi
        $this->createAndAssignAsesor($akreditasi->id, 1);

        // Create a completely unrelated asesor (not assigned to this akreditasi)
        $unrelatedAsesorUser = User::factory()->create(['role_id' => 2]);
        Asesor::create([
            'user_id' => $unrelatedAsesorUser->id,
            'nama_dengan_gelar' => 'Asesor Tidak Ditugaskan',
            'nama_tanpa_gelar' => 'Asesor Tidak Ditugaskan',
        ]);

        $result = $this->asesorService->processVisitasi(
            $akreditasi->id,
            $unrelatedAsesorUser->id,
            ['tanggal' => now()->addDays(7)->toDateString(), 'tanggal_akhir' => now()->addDays(7)->toDateString(), 'catatan' => ''],
            'terima'
        );

        $this->assertFalse($result);

        // Status must remain unchanged
        $this->assertEquals(3, $akreditasi->fresh()->status);
    }
}
