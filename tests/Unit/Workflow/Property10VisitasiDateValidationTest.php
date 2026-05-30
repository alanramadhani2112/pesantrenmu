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
 * Property-Based Test: Property 10 — Visitasi Date Validation
 *
 * For any tanggal_mulai and tanggal_akhir pair submitted for visitasi scheduling,
 * the system SHALL accept the schedule if and only if:
 *   - tanggal_mulai ≥ today + 7 days
 *   - tanggal_akhir ≥ tanggal_mulai
 *   - (tanggal_akhir - tanggal_mulai) ≤ 14 days
 *
 * **Validates: Requirements 5.2, 5.3, 5.7**
 */
#[Group('akreditasi-workflow-redesign')]
class Property10VisitasiDateValidationTest extends TestCase
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
     * Create a pesantren user, asesor user, akreditasi at status 4, and assessment.
     * Returns ['akreditasi' => Akreditasi, 'asesor1UserId' => int]
     */
    private function createAssessmentScenario(): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        $asesor1User = User::factory()->create(['role_id' => 2]);

        $asesor = Asesor::create([
            'user_id' => $asesor1User->id,
            'nama_dengan_gelar' => 'Dr. Asesor Satu',
            'nama_tanpa_gelar' => 'Asesor Satu',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_ASSESSMENT, // 4
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        return [
            'akreditasi' => $akreditasi,
            'asesor1UserId' => $asesor1User->id,
        ];
    }

    /**
     * Determine whether a date pair is valid per the visitasi date rules.
     */
    private function isValidDatePair(Carbon $tanggalMulai, Carbon $tanggalAkhir, Carbon $today): bool
    {
        $minMulai = $today->copy()->addDays(7);

        if ($tanggalMulai->lt($minMulai)) {
            return false;
        }

        if ($tanggalAkhir->lt($tanggalMulai)) {
            return false;
        }

        $duration = $tanggalMulai->diffInDays($tanggalAkhir);
        if ($duration > 14) {
            return false;
        }

        return true;
    }

    // =========================================================================
    // Property 10 — Part A: Valid date pairs always succeed
    // =========================================================================

    /**
     * Property 10 — Valid dates: for any tanggal_mulai ≥ today+7 and
     * tanggal_akhir ∈ [tanggal_mulai, tanggal_mulai+14], scheduleVisitasi
     * SHALL succeed and transition status to 3.
     *
     * Runs 100 iterations with randomly generated valid date pairs.
     *
     * **Validates: Requirements 5.2, 5.4**
     */
    public function test_property10_valid_date_pairs_always_succeed(): void
    {
        $iterations = 100;
        $succeeded = 0;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] = $this->createAssessmentScenario();

            // Generate a valid tanggal_mulai: today + 7..today + 60
            $daysAhead = rand(7, 60);
            $tanggalMulai = $today->copy()->addDays($daysAhead);

            // Generate a valid tanggal_akhir: tanggal_mulai + 0..14
            $durationDays = rand(0, 14);
            $tanggalAkhir = $tanggalMulai->copy()->addDays($durationDays);

            $scheduleData = [
                'tanggal_mulai' => $tanggalMulai->toDateString(),
                'tanggal_akhir' => $tanggalAkhir->toDateString(),
                'catatan_visitasi' => 'Visitasi iteration '.$i,
            ];

            $exception = null;

            try {
                $this->workflowService->scheduleVisitasi($akreditasi->id, $asesor1UserId, $scheduleData);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNull(
                $exception,
                "Iteration {$i}: Valid dates (mulai={$tanggalMulai->toDateString()}, ".
                "akhir={$tanggalAkhir->toDateString()}) should succeed, but threw: ".
                ($exception ? $exception->getMessage() : 'no exception')
            );

            // Verify status transitioned to 3 (Visitasi)
            $fresh = $akreditasi->fresh();
            $this->assertEquals(
                AkreditasiStateMachine::STATUS_VISITASI,
                (int) $fresh->status,
                "Iteration {$i}: Status should be 3 (Visitasi) after successful scheduling"
            );

            // Verify dates were saved
            $this->assertEquals(
                $tanggalMulai->toDateString(),
                Carbon::parse($fresh->tgl_visitasi)->toDateString(),
                "Iteration {$i}: tgl_visitasi should match tanggal_mulai"
            );

            $this->assertEquals(
                $tanggalAkhir->toDateString(),
                Carbon::parse($fresh->tgl_visitasi_akhir)->toDateString(),
                "Iteration {$i}: tgl_visitasi_akhir should match tanggal_akhir"
            );

            $succeeded++;
        }

        $this->assertEquals($iterations, $succeeded, "All {$iterations} valid date iterations should succeed");
    }

    // =========================================================================
    // Property 10 — Part B: tanggal_mulai < today+7 always fails
    // =========================================================================

    /**
     * Property 10 — Too-soon tanggal_mulai: for any tanggal_mulai < today+7,
     * scheduleVisitasi SHALL throw DomainException.
     *
     * Runs 100 iterations with tanggal_mulai ranging from today-30 to today+6.
     *
     * **Validates: Requirements 5.2, 5.3**
     */
    public function test_property10_tanggal_mulai_too_soon_always_fails(): void
    {
        $iterations = 100;
        $failed = 0;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] = $this->createAssessmentScenario();

            // Generate an invalid tanggal_mulai: today-30 to today+6 (all < today+7)
            $daysOffset = rand(-30, 6);
            $tanggalMulai = $today->copy()->addDays($daysOffset);

            // tanggal_akhir is valid relative to tanggal_mulai
            $tanggalAkhir = $tanggalMulai->copy()->addDays(rand(0, 7));

            $scheduleData = [
                'tanggal_mulai' => $tanggalMulai->toDateString(),
                'tanggal_akhir' => $tanggalAkhir->toDateString(),
                'catatan_visitasi' => '',
            ];

            $exception = null;

            try {
                $this->workflowService->scheduleVisitasi($akreditasi->id, $asesor1UserId, $scheduleData);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: tanggal_mulai={$tanggalMulai->toDateString()} (< today+7) should throw DomainException"
            );

            // Status should remain at 4 (Assessment)
            $this->assertEquals(
                AkreditasiStateMachine::STATUS_ASSESSMENT,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status should remain 4 when date validation fails"
            );

            $failed++;
        }

        $this->assertEquals($iterations, $failed, "All {$iterations} too-soon date iterations should fail");
    }

    // =========================================================================
    // Property 10 — Part C: tanggal_akhir < tanggal_mulai always fails
    // =========================================================================

    /**
     * Property 10 — End before start: for any tanggal_akhir < tanggal_mulai,
     * scheduleVisitasi SHALL throw DomainException.
     *
     * Runs 100 iterations with valid tanggal_mulai but tanggal_akhir before it.
     *
     * **Validates: Requirements 5.2, 5.3**
     */
    public function test_property10_tanggal_akhir_before_mulai_always_fails(): void
    {
        $iterations = 100;
        $failed = 0;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] = $this->createAssessmentScenario();

            // Valid tanggal_mulai (≥ today+7)
            $daysAhead = rand(7, 60);
            $tanggalMulai = $today->copy()->addDays($daysAhead);

            // Invalid tanggal_akhir: 1 to 30 days BEFORE tanggal_mulai
            $daysBefore = rand(1, 30);
            $tanggalAkhir = $tanggalMulai->copy()->subDays($daysBefore);

            $scheduleData = [
                'tanggal_mulai' => $tanggalMulai->toDateString(),
                'tanggal_akhir' => $tanggalAkhir->toDateString(),
                'catatan_visitasi' => '',
            ];

            $exception = null;

            try {
                $this->workflowService->scheduleVisitasi($akreditasi->id, $asesor1UserId, $scheduleData);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: tanggal_akhir={$tanggalAkhir->toDateString()} before ".
                "tanggal_mulai={$tanggalMulai->toDateString()} should throw DomainException"
            );

            $this->assertEquals(
                AkreditasiStateMachine::STATUS_ASSESSMENT,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status should remain 4 when date validation fails"
            );

            $failed++;
        }

        $this->assertEquals($iterations, $failed, "All {$iterations} end-before-start iterations should fail");
    }

    // =========================================================================
    // Property 10 — Part D: Duration > 14 days always fails
    // =========================================================================

    /**
     * Property 10 — Duration exceeded: for any (tanggal_akhir - tanggal_mulai) > 14 days,
     * scheduleVisitasi SHALL throw DomainException.
     *
     * Runs 100 iterations with valid tanggal_mulai but duration 15-60 days.
     *
     * **Validates: Requirements 5.2, 5.3**
     */
    public function test_property10_duration_exceeds_14_days_always_fails(): void
    {
        $iterations = 100;
        $failed = 0;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] = $this->createAssessmentScenario();

            // Valid tanggal_mulai (≥ today+7)
            $daysAhead = rand(7, 60);
            $tanggalMulai = $today->copy()->addDays($daysAhead);

            // Invalid duration: 15 to 60 days
            $durationDays = rand(15, 60);
            $tanggalAkhir = $tanggalMulai->copy()->addDays($durationDays);

            $scheduleData = [
                'tanggal_mulai' => $tanggalMulai->toDateString(),
                'tanggal_akhir' => $tanggalAkhir->toDateString(),
                'catatan_visitasi' => '',
            ];

            $exception = null;

            try {
                $this->workflowService->scheduleVisitasi($akreditasi->id, $asesor1UserId, $scheduleData);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: Duration {$durationDays} days (> 14) should throw DomainException"
            );

            $this->assertEquals(
                AkreditasiStateMachine::STATUS_ASSESSMENT,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status should remain 4 when date validation fails"
            );

            $failed++;
        }

        $this->assertEquals($iterations, $failed, "All {$iterations} excessive-duration iterations should fail");
    }

    // =========================================================================
    // Property 10 — Part E: Biconditional — accept iff all three rules pass
    // =========================================================================

    /**
     * Property 10 — Biconditional: scheduleVisitasi succeeds if and only if
     * all three date rules are satisfied simultaneously.
     *
     * Randomly generates date pairs (valid or invalid) and verifies the
     * outcome matches the expected result based on the rules.
     *
     * Runs 100 iterations.
     *
     * **Validates: Requirements 5.2, 5.3**
     */
    public function test_property10_biconditional_accept_iff_all_rules_pass(): void
    {
        $iterations = 100;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] = $this->createAssessmentScenario();

            // Randomly generate a date pair that may or may not be valid
            $mulaiOffset = rand(-5, 60);  // -5 to 60 days from today
            $akhirOffset = rand(-10, 30); // relative to tanggal_mulai

            $tanggalMulai = $today->copy()->addDays($mulaiOffset)->startOfDay();
            $tanggalAkhir = $tanggalMulai->copy()->addDays($akhirOffset)->startOfDay();

            $expectedValid = $this->isValidDatePair($tanggalMulai, $tanggalAkhir, $today);

            $scheduleData = [
                'tanggal_mulai' => $tanggalMulai->toDateString(),
                'tanggal_akhir' => $tanggalAkhir->toDateString(),
                'catatan_visitasi' => '',
            ];

            $exception = null;

            try {
                $this->workflowService->scheduleVisitasi($akreditasi->id, $asesor1UserId, $scheduleData);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            if ($expectedValid) {
                $this->assertNull(
                    $exception,
                    "Iteration {$i}: Valid pair (mulai={$tanggalMulai->toDateString()}, ".
                    "akhir={$tanggalAkhir->toDateString()}) should succeed, but threw: ".
                    ($exception ? $exception->getMessage() : 'no exception')
                );

                $this->assertEquals(
                    AkreditasiStateMachine::STATUS_VISITASI,
                    (int) $akreditasi->fresh()->status,
                    "Iteration {$i}: Status should be 3 after valid scheduling"
                );
            } else {
                $this->assertNotNull(
                    $exception,
                    "Iteration {$i}: Invalid pair (mulai={$tanggalMulai->toDateString()}, ".
                    "akhir={$tanggalAkhir->toDateString()}) should throw DomainException but succeeded"
                );

                $this->assertEquals(
                    AkreditasiStateMachine::STATUS_ASSESSMENT,
                    (int) $akreditasi->fresh()->status,
                    "Iteration {$i}: Status should remain 4 when date validation fails"
                );
            }
        }
    }

    // =========================================================================
    // Property 10 — Part F: Boundary — exactly today+7 is valid
    // =========================================================================

    /**
     * Property 10 — Boundary: tanggal_mulai = today+7 (exactly) SHALL be accepted.
     *
     * Runs 50 iterations to confirm the boundary is inclusive.
     *
     * **Validates: Requirement 5.2**
     */
    public function test_property10_boundary_exactly_7_days_ahead_is_valid(): void
    {
        $iterations = 50;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] = $this->createAssessmentScenario();

            $tanggalMulai = $today->copy()->addDays(7);
            $tanggalAkhir = $tanggalMulai->copy()->addDays(rand(0, 14));

            $scheduleData = [
                'tanggal_mulai' => $tanggalMulai->toDateString(),
                'tanggal_akhir' => $tanggalAkhir->toDateString(),
                'catatan_visitasi' => '',
            ];

            $exception = null;

            try {
                $this->workflowService->scheduleVisitasi($akreditasi->id, $asesor1UserId, $scheduleData);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNull(
                $exception,
                "Iteration {$i}: tanggal_mulai = today+7 (boundary) should be accepted"
            );

            $this->assertEquals(
                AkreditasiStateMachine::STATUS_VISITASI,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status should be 3 at boundary"
            );
        }
    }

    // =========================================================================
    // Property 10 — Part G: Boundary — today+6 is invalid
    // =========================================================================

    /**
     * Property 10 — Boundary: tanggal_mulai = today+6 (one day short) SHALL be rejected.
     *
     * Runs 50 iterations to confirm the boundary is exclusive on the invalid side.
     *
     * **Validates: Requirement 5.2**
     */
    public function test_property10_boundary_6_days_ahead_is_invalid(): void
    {
        $iterations = 50;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] = $this->createAssessmentScenario();

            $tanggalMulai = $today->copy()->addDays(6);
            $tanggalAkhir = $tanggalMulai->copy()->addDays(rand(0, 7));

            $scheduleData = [
                'tanggal_mulai' => $tanggalMulai->toDateString(),
                'tanggal_akhir' => $tanggalAkhir->toDateString(),
                'catatan_visitasi' => '',
            ];

            $exception = null;

            try {
                $this->workflowService->scheduleVisitasi($akreditasi->id, $asesor1UserId, $scheduleData);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: tanggal_mulai = today+6 (one day short) should be rejected"
            );

            $this->assertEquals(
                AkreditasiStateMachine::STATUS_ASSESSMENT,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status should remain 4 when today+6 is rejected"
            );
        }
    }

    // =========================================================================
    // Property 10 — Part H: Boundary — duration exactly 14 days is valid
    // =========================================================================

    /**
     * Property 10 — Boundary: duration = exactly 14 days SHALL be accepted.
     *
     * Runs 50 iterations to confirm the 14-day boundary is inclusive.
     *
     * **Validates: Requirement 5.2**
     */
    public function test_property10_boundary_exactly_14_day_duration_is_valid(): void
    {
        $iterations = 50;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            ['akreditasi' => $akreditasi, 'asesor1UserId' => $asesor1UserId] = $this->createAssessmentScenario();

            $daysAhead = rand(7, 60);
            $tanggalMulai = $today->copy()->addDays($daysAhead);
            $tanggalAkhir = $tanggalMulai->copy()->addDays(14); // exactly 14 days

            $scheduleData = [
                'tanggal_mulai' => $tanggalMulai->toDateString(),
                'tanggal_akhir' => $tanggalAkhir->toDateString(),
                'catatan_visitasi' => '',
            ];

            $exception = null;

            try {
                $this->workflowService->scheduleVisitasi($akreditasi->id, $asesor1UserId, $scheduleData);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNull(
                $exception,
                "Iteration {$i}: Duration = 14 days (boundary) should be accepted"
            );

            $this->assertEquals(
                AkreditasiStateMachine::STATUS_VISITASI,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status should be 3 at 14-day boundary"
            );
        }
    }

    // =========================================================================
    // Property 10 — Part I: Reschedule date validation uses same rules
    // =========================================================================

    /**
     * Property 10 — Reschedule: rescheduleVisitasi applies the same date
     * validation rules as scheduleVisitasi.
     *
     * Runs 100 iterations alternating valid/invalid date pairs for reschedule.
     *
     * **Validates: Requirements 5.7, 5.9**
     */
    public function test_property10_reschedule_applies_same_date_rules(): void
    {
        $iterations = 100;
        $today = Carbon::today();

        for ($i = 0; $i < $iterations; $i++) {
            $pesantrenUser = User::factory()->create(['role_id' => 3]);
            $asesor1User = User::factory()->create(['role_id' => 2]);

            $asesor = Asesor::create([
                'user_id' => $asesor1User->id,
                'nama_dengan_gelar' => 'Dr. Asesor',
                'nama_tanpa_gelar' => 'Asesor',
            ]);

            // Create akreditasi at status 3 (Visitasi) with a current schedule far in the future
            // so the H-7 window is open
            $currentMulai = $today->copy()->addDays(30);
            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => AkreditasiStateMachine::STATUS_VISITASI, // 3
                'tgl_visitasi' => $currentMulai->toDateString(),
                'tgl_visitasi_akhir' => $currentMulai->copy()->addDays(3)->toDateString(),
            ]);

            Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor->id,
                'tipe' => 1,
                'tanggal_mulai' => now(),
                'tanggal_berakhir' => now()->addDays(30),
            ]);

            // Generate new date pair (may be valid or invalid)
            $mulaiOffset = rand(-5, 60);
            $akhirOffset = rand(-10, 30);

            $newMulai = $today->copy()->addDays($mulaiOffset)->startOfDay();
            $newAkhir = $newMulai->copy()->addDays($akhirOffset)->startOfDay();

            $expectedValid = $this->isValidDatePair($newMulai, $newAkhir, $today);

            $scheduleData = [
                'tanggal_mulai' => $newMulai->toDateString(),
                'tanggal_akhir' => $newAkhir->toDateString(),
                'catatan_visitasi' => '',
            ];

            $exception = null;

            try {
                $this->workflowService->rescheduleVisitasi($akreditasi->id, $asesor1User->id, $scheduleData);
            } catch (\DomainException $e) {
                $exception = $e;
            }

            if ($expectedValid) {
                $this->assertNull(
                    $exception,
                    "Reschedule iteration {$i}: Valid pair should succeed, but threw: ".
                    ($exception ? $exception->getMessage() : 'no exception')
                );
            } else {
                $this->assertNotNull(
                    $exception,
                    "Reschedule iteration {$i}: Invalid pair should throw DomainException but succeeded"
                );
            }
        }
    }
}
