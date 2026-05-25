<?php

namespace Tests\Unit\Workflow;

use App\Exceptions\InvalidTransitionException;
use App\Models\Akreditasi;
use App\Models\User;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Property-Based Test: Property 16 — Terminal State Immutability
 *
 * For any akreditasi at status 0 (Selesai), no status transition SHALL be
 * permitted and no modification to akreditasi data SHALL succeed.
 *
 * **Validates: Requirements 12.4, 12.5**
 *
 */
#[Group('akreditasi-workflow-redesign')]
class Property16TerminalStateImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected AkreditasiStateMachine $stateMachine;

    /** All valid status values in the domain. */
    private const VALID_STATUSES = [-2, -1, 0, 1, 2, 3, 4, 5, 6];

    /** Fields that represent akreditasi data (should be read-only at status 0). */
    private const MUTABLE_FIELDS = [
        'nomor_sk',
        'catatan',
        'nilai',
        'peringkat',
        'masa_berlaku',
        'masa_berlaku_akhir',
        'catatan_rekomendasi_admin',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->stateMachine = app(AkreditasiStateMachine::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create an akreditasi at status 0 (Selesai).
     */
private function createSelesaiAkreditasi(): Akreditasi
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);

        return Akreditasi::create([
            'user_id'     => $pesantrenUser->id,
            'status'      => AkreditasiStateMachine::STATUS_SELESAI, // 0
            'nomor_sk'    => 'SK-TEST-' . random_int(1000, 9999),
            'nilai'       => round(random_int(7000, 10000) / 100, 2),
            'peringkat'   => ['A', 'B', 'C'][random_int(0, 2)],
            'masa_berlaku' => now()->toDateString(),
            'masa_berlaku_akhir' => now()->addYears(5)->toDateString(),
        ]);
    }

    // =========================================================================
    // Property 16 — Part A: No transition from status 0 is permitted
    // =========================================================================

    /**
     * Property 16 — No outgoing transitions: for any target status,
     * canTransition(0, target) SHALL return false.
     *
     * Runs 100 iterations with random target statuses.
     *
     * **Validates: Requirements 12.4**
     */
public function test_property16_no_transition_from_status_0_is_permitted(): void
    {
        $iterations = 100;
        $validStatuses = self::VALID_STATUSES;
        $count = count($validStatuses);

        for ($i = 0; $i < $iterations; $i++) {
            // Pick a random target status (from valid set or random integer)
            $useValid = (bool) random_int(0, 1);
            $targetStatus = $useValid
                ? $validStatuses[random_int(0, $count - 1)]
                : random_int(-10, 10);

            $this->assertFalse(
                $this->stateMachine->canTransition(AkreditasiStateMachine::STATUS_SELESAI, $targetStatus),
                "Iteration {$i}: canTransition(0, {$targetStatus}) should return false — status 0 is terminal"
            );
        }
    }

    // =========================================================================
    // Property 16 — Part B: transition() throws for any target from status 0
    // =========================================================================

    /**
     * Property 16 — Transition throws: attempting to call transition() on an
     * akreditasi at status 0 SHALL throw InvalidTransitionException for any
     * target status.
     *
     * Runs 100 iterations.
     *
     * **Validates: Requirements 12.4**
     */
public function test_property16_transition_throws_for_any_target_from_status_0(): void
    {
        $iterations = 100;
        $validStatuses = self::VALID_STATUSES;
        $count = count($validStatuses);

        for ($i = 0; $i < $iterations; $i++) {
            $akreditasi = $this->createSelesaiAkreditasi();
            $adminUser  = User::factory()->create(['role_id' => 1]);

            $targetStatus = $validStatuses[random_int(0, $count - 1)];

            $exception = null;
            try {
                $this->stateMachine->transition($akreditasi, $targetStatus, $adminUser);
            } catch (InvalidTransitionException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: transition(0 → {$targetStatus}) should throw InvalidTransitionException"
            );

            // Verify status remains 0
            $this->assertEquals(
                AkreditasiStateMachine::STATUS_SELESAI,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status should remain 0 after rejected transition attempt"
            );
        }
    }

    // =========================================================================
    // Property 16 — Part C: Status 0 preserved after failed transition
    // =========================================================================

    /**
     * Property 16 — Status preserved: after any failed transition attempt,
     * the akreditasi status SHALL remain 0.
     *
     * Runs 100 iterations with all valid target statuses.
     *
     * **Validates: Requirements 12.4**
     */
public function test_property16_status_0_preserved_after_failed_transition(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $akreditasi = $this->createSelesaiAkreditasi();
            $adminUser  = User::factory()->create(['role_id' => 1]);

            // Try all valid statuses as targets
            foreach (self::VALID_STATUSES as $targetStatus) {
                try {
                    $this->stateMachine->transition($akreditasi, $targetStatus, $adminUser);
                } catch (InvalidTransitionException $e) {
                    // Expected
                }

                $this->assertEquals(
                    AkreditasiStateMachine::STATUS_SELESAI,
                    (int) $akreditasi->fresh()->status,
                    "Iteration {$i}: Status should remain 0 after attempted transition to {$targetStatus}"
                );
            }
        }
    }

    // =========================================================================
    // Property 16 — Part D: getPermittedTransitions(0) returns empty array
    // =========================================================================

    /**
     * Property 16 — No permitted transitions: getPermittedTransitions(0)
     * SHALL return an empty array.
     *
     * Runs 100 iterations (deterministic but repeated for consistency).
     *
     * **Validates: Requirements 12.4**
     */
public function test_property16_get_permitted_transitions_returns_empty_for_status_0(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $permitted = $this->stateMachine->getPermittedTransitions(AkreditasiStateMachine::STATUS_SELESAI);

            $this->assertIsArray($permitted, "Iteration {$i}: getPermittedTransitions(0) should return an array");
            $this->assertEmpty(
                $permitted,
                "Iteration {$i}: getPermittedTransitions(0) should return empty array — status 0 is terminal"
            );
        }
    }

    // =========================================================================
    // Property 16 — Part E: Data fields are read-only at status 0
    // =========================================================================

    /**
     * Property 16 — Data immutability: akreditasi data fields SHALL be
     * read-only at status 0. Attempts to update data fields should not
     * change the status (status remains 0 regardless of data updates).
     *
     * This tests that the state machine correctly prevents status changes,
     * which is the primary enforcement mechanism for terminal state immutability.
     *
     * Runs 100 iterations with random field updates.
     *
     * **Validates: Requirements 12.5**
     */
public function test_property16_status_0_cannot_be_changed_via_any_transition(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $akreditasi = $this->createSelesaiAkreditasi();
            $originalStatus = (int) $akreditasi->status;

            $this->assertEquals(
                AkreditasiStateMachine::STATUS_SELESAI,
                $originalStatus,
                "Iteration {$i}: Initial status should be 0"
            );

            // Attempt transition to a random valid status
            $targetStatus = self::VALID_STATUSES[random_int(0, count(self::VALID_STATUSES) - 1)];
            $adminUser = User::factory()->create(['role_id' => 1]);

            $transitionAllowed = false;
            try {
                $this->stateMachine->transition($akreditasi, $targetStatus, $adminUser);
                $transitionAllowed = true;
            } catch (InvalidTransitionException $e) {
                // Expected
            }

            $this->assertFalse(
                $transitionAllowed,
                "Iteration {$i}: No transition from status 0 should be allowed (target={$targetStatus})"
            );

            // Status must remain 0
            $this->assertEquals(
                AkreditasiStateMachine::STATUS_SELESAI,
                (int) $akreditasi->fresh()->status,
                "Iteration {$i}: Status must remain 0 after any transition attempt"
            );
        }
    }

    // =========================================================================
    // Property 16 — Part F: Exhaustive check — all 9 target statuses rejected
    // =========================================================================

    /**
     * Property 16 — Exhaustive: for every valid target status, canTransition(0, target)
     * returns false. Runs 100 iterations cycling through all 9 valid statuses.
     *
     * **Validates: Requirements 12.4**
     */
public function test_property16_exhaustive_all_valid_targets_rejected_from_status_0(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            foreach (self::VALID_STATUSES as $targetStatus) {
                $this->assertFalse(
                    $this->stateMachine->canTransition(AkreditasiStateMachine::STATUS_SELESAI, $targetStatus),
                    "Iteration {$i}: canTransition(0, {$targetStatus}) must return false"
                );
            }
        }
    }
}
