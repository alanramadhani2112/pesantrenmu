<?php

namespace Tests\Unit\StateMachine;

use App\StateMachine\AkreditasiStateMachine;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Property-Based Test: Property 1 — State Machine Transition Validity
 *
 * For any current status value and target status value pair, the state machine
 * SHALL accept the transition if and only if the pair exists in the defined
 * permitted transitions map {6→5, 5→4, 5→-1, 4→3, 4→-1, 3→2, 2→1, 1→0,
 * 1→-1, -1→-2, -2→1, -2→-1}, and SHALL reject all other pairs with the
 * current status preserved unchanged.
 *
 * **Validates: Requirements 1.2, 1.3**
 */
#[Group('akreditasi-workflow-redesign')]
class Property1TransitionValidityTest extends TestCase
{
    private AkreditasiStateMachine $sm;

    /** All valid status values in the domain. */
    private const VALID_STATUSES = [-2, -1, 0, 1, 2, 3, 4, 5, 6];

    protected function setUp(): void
    {
        parent::setUp();
        $this->sm = new AkreditasiStateMachine;
    }

    // -------------------------------------------------------------------------
    // Helper: build the complete set of permitted (from, to) pairs from the
    // TRANSITIONS constant so the test is derived from the single source of
    // truth rather than hard-coded expectations.
    // -------------------------------------------------------------------------

    /**
     * Build the complete set of permitted (from, to) pairs from TRANSITIONS.
     *
     * @return array<array{int, int}>
     */
    private function buildPermittedPairs(): array
    {
        $pairs = [];
        foreach (AkreditasiStateMachine::TRANSITIONS as $from => $targets) {
            foreach ($targets as $to) {
                $pairs[] = [$from, $to];
            }
        }

        return $pairs;
    }

    /**
     * Return true if the given (from, to) pair is in the permitted set.
     */
    private function isPermitted(int $from, int $to): bool
    {
        foreach ($this->buildPermittedPairs() as [$f, $t]) {
            if ($f === $from && $t === $to) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // Part A — Exhaustive check of all 9×9 = 81 combinations of valid statuses
    // =========================================================================

    /**
     * Property 1 — Exhaustive: for every (from, to) pair drawn from the 9
     * valid status values, canTransition returns true iff the pair is in
     * TRANSITIONS, and false otherwise.
     *
     * This covers all 81 combinations deterministically.
     *
     * **Validates: Requirements 1.2, 1.3**
     */
    public function test_property1_exhaustive_all_valid_status_combinations(): void
    {
        $permittedPairs = $this->buildPermittedPairs();
        $permittedCount = count($permittedPairs);

        $checkedTotal = 0;
        $permittedChecked = 0;
        $rejectedChecked = 0;

        foreach (self::VALID_STATUSES as $from) {
            foreach (self::VALID_STATUSES as $to) {
                $expected = $this->isPermitted($from, $to);
                $actual = $this->sm->canTransition($from, $to);

                $this->assertSame(
                    $expected,
                    $actual,
                    sprintf(
                        'canTransition(%d, %d) should return %s (pair is %s in TRANSITIONS map).',
                        $from,
                        $to,
                        $expected ? 'true' : 'false',
                        $expected ? 'present' : 'absent'
                    )
                );

                $checkedTotal++;
                $expected ? $permittedChecked++ : $rejectedChecked++;
            }
        }

        // Sanity: we checked exactly 9×9 = 81 combinations.
        $this->assertSame(81, $checkedTotal, 'Expected exactly 81 combinations to be checked.');

        // Sanity: the number of permitted pairs found matches the TRANSITIONS map.
        $this->assertSame(
            $permittedCount,
            $permittedChecked,
            'Number of permitted pairs found in exhaustive check must equal TRANSITIONS map size.'
        );

        // Sanity: rejected pairs = 81 - permitted pairs.
        $this->assertSame(
            81 - $permittedCount,
            $rejectedChecked,
            'Number of rejected pairs must equal 81 minus the permitted count.'
        );
    }

    // =========================================================================
    // Part B — Property-based: random (from, to) pairs from valid status range
    //          over at least 100 iterations
    // =========================================================================

    /**
     * Property 1 — Random valid-range: for at least 100 randomly generated
     * (from, to) pairs drawn from the valid status set {-2..6}, canTransition
     * returns true iff the pair is in TRANSITIONS.
     *
     * **Validates: Requirements 1.2, 1.3**
     */
    public function test_property1_random_valid_range_at_least_100_iterations(): void
    {
        $iterations = 200; // well above the 100-iteration minimum
        $validStatuses = self::VALID_STATUSES;
        $count = count($validStatuses);

        for ($i = 0; $i < $iterations; $i++) {
            $from = $validStatuses[random_int(0, $count - 1)];
            $to = $validStatuses[random_int(0, $count - 1)];

            $expected = $this->isPermitted($from, $to);
            $actual = $this->sm->canTransition($from, $to);

            $this->assertSame(
                $expected,
                $actual,
                sprintf(
                    'Iteration %d: canTransition(%d, %d) should return %s.',
                    $i,
                    $from,
                    $to,
                    $expected ? 'true' : 'false'
                )
            );
        }
    }

    // =========================================================================
    // Part C — Property-based: random integers OUTSIDE the valid status range
    //          always return false from canTransition
    // =========================================================================

    /**
     * Property 1 — Out-of-range: for at least 100 randomly generated integer
     * pairs where at least one value is outside the valid status range
     * (-2..6), canTransition SHALL always return false.
     *
     * Generators sample from [-100, -3] ∪ [7, 100] to guarantee out-of-range
     * values.
     *
     * **Validates: Requirements 1.2, 1.3**
     */
    public function test_property1_out_of_range_values_always_return_false(): void
    {
        $iterations = 200; // well above the 100-iteration minimum

        for ($i = 0; $i < $iterations; $i++) {
            // Generate a random out-of-range integer: pick from [-100..-3] or [7..100]
            $outOfRange = $this->randomOutOfRangeStatus();

            // Randomly decide which position (from, to, or both) gets the out-of-range value
            $scenario = random_int(0, 2);

            [$from, $to] = match ($scenario) {
                // out-of-range as 'from', random valid or invalid 'to'
                0 => [$outOfRange, $this->randomAnyStatus()],
                // valid 'from', out-of-range as 'to'
                1 => [$this->randomValidStatus(), $outOfRange],
                // both out-of-range
                default => [$outOfRange, $this->randomOutOfRangeStatus()],
            };

            $this->assertFalse(
                $this->sm->canTransition($from, $to),
                sprintf(
                    'Iteration %d: canTransition(%d, %d) should return false — at least one value is outside valid status range.',
                    $i,
                    $from,
                    $to
                )
            );
        }
    }

    // =========================================================================
    // Part D — Explicit permitted-pair assertions (documentation / regression)
    // =========================================================================

    /**
     * Property 1 — Permitted pairs: every pair in the TRANSITIONS map returns
     * true from canTransition.
     *
     * This is a deterministic regression guard that documents the exact
     * permitted set from the design specification.
     *
     * **Validates: Requirements 1.2**
     */
    public function test_property1_all_permitted_pairs_return_true(): void
    {
        $expectedPermitted = [
            [6,  5],   // Pengajuan → Verifikasi Berkas
            [5,  4],   // Verifikasi Berkas → Assessment
            [5, -1],   // Verifikasi Berkas → Ditolak
            [4,  3],   // Assessment → Visitasi
            [4, -1],   // Assessment → Ditolak
            [3,  2],   // Visitasi → Pasca Visitasi
            [2,  1],   // Pasca Visitasi → Validasi Admin
            [1,  0],   // Validasi Admin → Selesai
            [1, -1],   // Validasi Admin → Ditolak
            [-1, -2],  // Ditolak → Banding
            [-2,  1],  // Banding → Validasi Admin (banding diterima)
            [-2, -1],  // Banding → Ditolak (banding ditolak)
        ];

        foreach ($expectedPermitted as [$from, $to]) {
            $this->assertTrue(
                $this->sm->canTransition($from, $to),
                "canTransition({$from}, {$to}) must return true — this pair is in the permitted transitions map."
            );
        }

        // Also verify the TRANSITIONS constant encodes exactly these 12 pairs.
        $this->assertSame(
            count($expectedPermitted),
            count($this->buildPermittedPairs()),
            'TRANSITIONS map must encode exactly '.count($expectedPermitted).' permitted pairs.'
        );
    }

    /**
     * Property 1 — Terminal state: status 0 (Selesai) has no outgoing
     * transitions; canTransition(0, *) always returns false for every valid
     * status.
     *
     * **Validates: Requirements 1.2, 1.3**
     */
    public function test_property1_terminal_state_selesai_has_no_outgoing_transitions(): void
    {
        foreach (self::VALID_STATUSES as $to) {
            $this->assertFalse(
                $this->sm->canTransition(0, $to),
                "canTransition(0, {$to}) must return false — status 0 (Selesai) is terminal."
            );
        }
    }

    // =========================================================================
    // Random value generators
    // =========================================================================

    /**
     * Return a random integer from the valid status set {-2, -1, 0, 1, 2, 3, 4, 5, 6}.
     */
    private function randomValidStatus(): int
    {
        $statuses = self::VALID_STATUSES;

        return $statuses[random_int(0, count($statuses) - 1)];
    }

    /**
     * Return a random integer strictly outside the valid status range.
     * Samples from [-100, -3] ∪ [7, 100].
     */
    private function randomOutOfRangeStatus(): int
    {
        // Two equally-likely bands: low [-100..-3] and high [7..100]
        if (random_int(0, 1) === 0) {
            return random_int(-100, -3);
        }

        return random_int(7, 100);
    }

    /**
     * Return a random integer from the full test range [-100, 100].
     * May be in-range or out-of-range.
     */
    private function randomAnyStatus(): int
    {
        return random_int(-100, 100);
    }
}
