<?php

namespace Tests\Unit\Workflow;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\User;
use App\Services\AkreditasiWorkflowService;
use App\StateMachine\AkreditasiStateMachine;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Property-Based Test: Property 11 — Visitasi Confirmation Date Gate
 *
 * For any attempt to confirm "visitasi selesai", the system SHALL accept
 * if and only if the current date ≥ tanggal_mulai of the scheduled visitasi.
 *
 * **Validates: Requirements 6.2, 6.6**
 */
#[Group('akreditasi-workflow-redesign')]
class Property11VisitasiConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected AkreditasiWorkflowService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->workflowService = app(AkreditasiWorkflowService::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a visitasi scenario: akreditasi at status 3 with a given tgl_visitasi.
     *
     * @param  string  $tglVisitasi  Date string for tgl_visitasi (tanggal_mulai)
     * @return array{akreditasi: Akreditasi, asesor1UserId: int, asesor2UserId: int}
     */
    private function createVisitasiScenario(string $tglVisitasi): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        $asesor1User = User::factory()->create(['role_id' => 2]);
        $asesor2User = User::factory()->create(['role_id' => 2]);

        $asesor1 = Asesor::create([
            'user_id' => $asesor1User->id,
            'nama_dengan_gelar' => 'Dr. Asesor Satu',
            'nama_tanpa_gelar' => 'Asesor Satu',
        ]);

        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Dr. Asesor Dua',
            'nama_tanpa_gelar' => 'Asesor Dua',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_VISITASI, // 3
            'tgl_visitasi' => $tglVisitasi,
            'tgl_visitasi_akhir' => Carbon::parse($tglVisitasi)->addDays(3)->toDateString(),
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        return [
            'akreditasi' => $akreditasi,
            'asesor1UserId' => $asesor1User->id,
            'asesor2UserId' => $asesor2User->id,
        ];
    }

    // =========================================================================
    // Property 11 — Part A: Confirmation on or after tanggal_mulai always succeeds
    // =========================================================================

    /**
     * Property 11 — On/after tanggal_mulai: for any tgl_visitasi ≤ today,
     * confirmVisitasiSelesai SHALL succeed and transition status to 2.
     *
     * Runs 100 iterations with tgl_visitasi ranging from today-60 to today.
     *
     * **Validates: Requirements 6.2, 6.3**
     */
    public function test_property11_confirmation_on_or_after_tanggal_mulai_succeeds(): void
    {
        $iterations = 100;
        $succeeded = 0;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            // tgl_visitasi is today or in the past (0 to 60 days ago)
            $daysAgo = rand(0, 60);
            $tglVisitasi = $today->copy()->subDays($daysAgo)->toDateString();

            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] =
                $this->createVisitasiScenario($tglVisitasi);

            $exception = null;

            try {
                $this->workflowService->confirmVisitasiSelesai($akreditasi->id, $asesor1UserId);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNull(
                $exception,
                "Iteration {$i}: tgl_visitasi={$tglVisitasi} (≤ today) should succeed, but threw: ".
                ($exception ? $exception->getMessage() : 'no exception')
            );

            // Verify status transitioned to 2 (Pasca Visitasi)
            $fresh = $akreditasi->fresh();
            $this->assertEquals(
                AkreditasiStateMachine::STATUS_PASCA_VISITASI,
                (int) $fresh->status,
                "Iteration {$i}: Status should be 2 (Pasca Visitasi) after confirmation"
            );

            // Verify visitasi_confirmed_at was recorded
            $this->assertNotNull(
                $fresh->visitasi_confirmed_at,
                "Iteration {$i}: visitasi_confirmed_at should be recorded"
            );

            $succeeded++;
        }

        $this->assertEquals($iterations, $succeeded, "All {$iterations} on/after-date iterations should succeed");
    }

    // =========================================================================
    // Property 11 — Part B: Confirmation before tanggal_mulai always fails
    // =========================================================================

    /**
     * Property 11 — Before tanggal_mulai: for any tgl_visitasi > today,
     * confirmVisitasiSelesai SHALL throw DomainException.
     *
     * Runs 100 iterations with tgl_visitasi ranging from tomorrow to today+60.
     *
     * **Validates: Requirements 6.6**
     */
    public function test_property11_confirmation_before_tanggal_mulai_fails(): void
    {
        $iterations = 100;
        $failed = 0;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            // tgl_visitasi is in the future (1 to 60 days from now)
            $daysAhead = rand(1, 60);
            $tglVisitasi = $today->copy()->addDays($daysAhead)->toDateString();

            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] =
                $this->createVisitasiScenario($tglVisitasi);

            $exception = null;

            try {
                $this->workflowService->confirmVisitasiSelesai($akreditasi->id, $asesor1UserId);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: tgl_visitasi={$tglVisitasi} (> today) should throw DomainException"
            );

            // Status should remain at 3 (Visitasi)
            $this->assertEquals(
                AkreditasiStateMachine::STATUS_VISITASI,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status should remain 3 when confirmation is rejected"
            );

            // visitasi_confirmed_at should NOT be set
            $this->assertNull(
                $akreditasi->fresh()->visitasi_confirmed_at,
                "Iteration {$i}: visitasi_confirmed_at should not be set when confirmation fails"
            );

            $failed++;
        }

        $this->assertEquals($iterations, $failed, "All {$iterations} before-date iterations should fail");
    }

    // =========================================================================
    // Property 11 — Part C: Biconditional — accept iff today ≥ tanggal_mulai
    // =========================================================================

    /**
     * Property 11 — Biconditional: confirmVisitasiSelesai succeeds if and only if
     * today ≥ tgl_visitasi.
     *
     * Randomly generates tgl_visitasi values (past or future) and verifies
     * the outcome matches the expected result.
     *
     * Runs 100 iterations.
     *
     * **Validates: Requirements 6.2, 6.6**
     */
    public function test_property11_biconditional_accept_iff_today_gte_tanggal_mulai(): void
    {
        $iterations = 100;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            // Randomly generate tgl_visitasi: -60 to +60 days from today
            $daysOffset = rand(-60, 60);
            $tglVisitasi = $today->copy()->addDays($daysOffset)->toDateString();

            // Expected: accept if tgl_visitasi ≤ today (daysOffset ≤ 0)
            $expectedSuccess = ($daysOffset <= 0);

            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] =
                $this->createVisitasiScenario($tglVisitasi);

            $exception = null;

            try {
                $this->workflowService->confirmVisitasiSelesai($akreditasi->id, $asesor1UserId);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            if ($expectedSuccess) {
                $this->assertNull(
                    $exception,
                    "Iteration {$i}: tgl_visitasi={$tglVisitasi} (≤ today) should succeed, but threw: ".
                    ($exception ? $exception->getMessage() : 'no exception')
                );

                $this->assertEquals(
                    AkreditasiStateMachine::STATUS_PASCA_VISITASI,
                    (int) $akreditasi->fresh()->status,
                    "Iteration {$i}: Status should be 2 after successful confirmation"
                );

                $this->assertNotNull(
                    $akreditasi->fresh()->visitasi_confirmed_at,
                    "Iteration {$i}: visitasi_confirmed_at should be recorded"
                );
            } else {
                $this->assertNotNull(
                    $exception,
                    "Iteration {$i}: tgl_visitasi={$tglVisitasi} (> today) should throw DomainException but succeeded"
                );

                $this->assertEquals(
                    AkreditasiStateMachine::STATUS_VISITASI,
                    (int) $akreditasi->fresh()->status,
                    "Iteration {$i}: Status should remain 3 when confirmation is rejected"
                );

                $this->assertNull(
                    $akreditasi->fresh()->visitasi_confirmed_at,
                    "Iteration {$i}: visitasi_confirmed_at should not be set when confirmation fails"
                );
            }
        }
    }

    // =========================================================================
    // Property 11 — Part D: Boundary — tgl_visitasi = today is valid
    // =========================================================================

    /**
     * Property 11 — Boundary: tgl_visitasi = today (same day) SHALL be accepted.
     *
     * Runs 50 iterations to confirm the boundary is inclusive.
     *
     * **Validates: Requirement 6.2**
     */
    public function test_property11_boundary_today_equals_tanggal_mulai_is_valid(): void
    {
        $iterations = 50;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            $tglVisitasi = $today->toDateString();

            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] =
                $this->createVisitasiScenario($tglVisitasi);

            $exception = null;

            try {
                $this->workflowService->confirmVisitasiSelesai($akreditasi->id, $asesor1UserId);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNull(
                $exception,
                "Iteration {$i}: tgl_visitasi = today (boundary) should be accepted"
            );

            $this->assertEquals(
                AkreditasiStateMachine::STATUS_PASCA_VISITASI,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status should be 2 at boundary"
            );
        }
    }

    // =========================================================================
    // Property 11 — Part E: Boundary — tgl_visitasi = tomorrow is invalid
    // =========================================================================

    /**
     * Property 11 — Boundary: tgl_visitasi = tomorrow (one day in future) SHALL be rejected.
     *
     * Runs 50 iterations to confirm the boundary is exclusive on the invalid side.
     *
     * **Validates: Requirement 6.6**
     */
    public function test_property11_boundary_tomorrow_is_invalid(): void
    {
        $iterations = 50;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            $tglVisitasi = $today->copy()->addDay()->toDateString();

            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] =
                $this->createVisitasiScenario($tglVisitasi);

            $exception = null;

            try {
                $this->workflowService->confirmVisitasiSelesai($akreditasi->id, $asesor1UserId);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: tgl_visitasi = tomorrow should be rejected"
            );

            $this->assertEquals(
                AkreditasiStateMachine::STATUS_VISITASI,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status should remain 3 when tomorrow is rejected"
            );
        }
    }

    // =========================================================================
    // Property 11 — Part F: Non-Asesor_1 actor always fails
    // =========================================================================

    /**
     * Property 11 — Actor validation: only the assigned Asesor_1 can confirm
     * visitasi selesai. Any other user SHALL be rejected.
     *
     * Runs 100 iterations with random non-Asesor_1 actors.
     *
     * **Validates: Requirement 6.7**
     */
    public function test_property11_non_asesor1_actor_always_fails(): void
    {
        $iterations = 100;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            // tgl_visitasi is today or in the past (so date gate passes)
            $daysAgo = rand(0, 30);
            $tglVisitasi = $today->copy()->subDays($daysAgo)->toDateString();

            ['akreditasi' => $akreditasi, 'asesor2UserId' => $asesor2UserId] =
                $this->createVisitasiScenario($tglVisitasi);

            // Use Asesor_2 as the actor (not Asesor_1)
            $exception = null;

            try {
                $this->workflowService->confirmVisitasiSelesai($akreditasi->id, $asesor2UserId);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: Asesor_2 should not be able to confirm visitasi selesai"
            );

            $this->assertEquals(
                AkreditasiStateMachine::STATUS_VISITASI,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status should remain 3 when non-Asesor_1 attempts confirmation"
            );

            $this->assertNull(
                $akreditasi->fresh()->visitasi_confirmed_at,
                "Iteration {$i}: visitasi_confirmed_at should not be set when actor is invalid"
            );
        }
    }

    // =========================================================================
    // Property 11 — Part G: visitasi_confirmed_at is always recorded on success
    // =========================================================================

    /**
     * Property 11 — Timestamp recording: for any successful confirmation,
     * visitasi_confirmed_at SHALL be set to a non-null timestamp.
     *
     * Runs 100 iterations to verify the timestamp is always recorded.
     *
     * **Validates: Requirement 6.3**
     */
    public function test_property11_visitasi_confirmed_at_always_recorded_on_success(): void
    {
        $iterations = 100;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            $daysAgo = rand(0, 60);
            $tglVisitasi = $today->copy()->subDays($daysAgo)->toDateString();

            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] =
                $this->createVisitasiScenario($tglVisitasi);

            $beforeConfirm = now();

            try {
                $this->workflowService->confirmVisitasiSelesai($akreditasi->id, $asesor1UserId);
            } catch (\DomainException $e) {
                $this->fail("Iteration {$i}: Unexpected DomainException: ".$e->getMessage());
            }

            $fresh = $akreditasi->fresh();

            $this->assertNotNull(
                $fresh->visitasi_confirmed_at,
                "Iteration {$i}: visitasi_confirmed_at must be set after successful confirmation"
            );

            // Verify the timestamp is recent (within the test execution window)
            $confirmedAt = Carbon::parse($fresh->visitasi_confirmed_at);
            $this->assertTrue(
                $confirmedAt->gte($beforeConfirm->subSecond()),
                "Iteration {$i}: visitasi_confirmed_at should be approximately now()"
            );
        }
    }
}
