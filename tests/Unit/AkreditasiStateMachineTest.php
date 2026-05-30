<?php

namespace Tests\Unit;

use App\StateMachine\AkreditasiStateMachine;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for AkreditasiStateMachine::canTransition.
 *
 * Validates Requirement 1.2 — only permitted transitions are accepted.
 */
class AkreditasiStateMachineTest extends TestCase
{
    private AkreditasiStateMachine $sm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sm = new AkreditasiStateMachine;
    }

    #[DataProvider('permittedTransitionsProvider')]
    public function test_can_transition_returns_true_for_permitted_transitions(int $from, int $to): void
    {
        $this->assertTrue(
            $this->sm->canTransition($from, $to),
            "Expected transition {$from} -> {$to} to be permitted."
        );
    }

    public static function permittedTransitionsProvider(): array
    {
        return [
            'Pengajuan -> Verifikasi Berkas' => [6, 5],
            'Verifikasi Berkas -> Assessment' => [5, 4],
            'Verifikasi Berkas -> Ditolak' => [5, -1],
            'Assessment -> Visitasi' => [4, 3],
            'Assessment -> Ditolak' => [4, -1],
            'Visitasi -> Pasca Visitasi' => [3, 2],
            'Pasca Visitasi -> Validasi Admin' => [2, 1],
            'Validasi Admin -> Selesai' => [1, 0],
            'Validasi Admin -> Ditolak' => [1, -1],
            'Ditolak -> Banding' => [-1, -2],
            'Banding -> Validasi Admin' => [-2, 1],
            'Banding -> Ditolak' => [-2, -1],
        ];
    }

    #[DataProvider('unpermittedTransitionsProvider')]
    public function test_can_transition_returns_false_for_unpermitted_transitions(int $from, int $to): void
    {
        $this->assertFalse(
            $this->sm->canTransition($from, $to),
            "Expected transition {$from} -> {$to} to be rejected."
        );
    }

    public static function unpermittedTransitionsProvider(): array
    {
        return [
            'Skip ahead 6 -> 4' => [6, 4],
            'Backwards 5 -> 6' => [5, 6],
            'Selesai is terminal 0 -> 1' => [0, 1],
            'Selesai is terminal 0 -> -1' => [0, -1],
            'Selesai is terminal 0 -> 0' => [0, 0],
            'Pengajuan -> Ditolak (not permitted)' => [6, -1],
            'Visitasi -> Ditolak (not permitted)' => [3, -1],
            'Pasca Visitasi -> Ditolak' => [2, -1],
            'Validasi Admin -> Banding' => [1, -2],
            'Self transition 4 -> 4' => [4, 4],
            'Banding -> Visitasi' => [-2, 3],
            'Banding -> Pasca Visitasi' => [-2, 2],
        ];
    }

    public function test_can_transition_returns_false_for_unknown_source_status(): void
    {
        // Status values outside the defined set must not be permitted as sources.
        $this->assertFalse($this->sm->canTransition(99, 5));
        $this->assertFalse($this->sm->canTransition(-99, -1));
        $this->assertFalse($this->sm->canTransition(7, 6));
    }

    public function test_can_transition_returns_false_for_terminal_state_as_source(): void
    {
        // Status 0 (Selesai) has no outgoing transitions in the TRANSITIONS map.
        foreach ([6, 5, 4, 3, 2, 1, 0, -1, -2] as $to) {
            $this->assertFalse(
                $this->sm->canTransition(0, $to),
                "Expected Selesai (0) -> {$to} to be rejected (terminal state)."
            );
        }
    }

    // -------------------------------------------------------------------------
    // getPermittedTransitions tests
    // -------------------------------------------------------------------------

    #[DataProvider('permittedTransitionListProvider')]
    public function test_get_permitted_transitions_returns_correct_targets(int $status, array $expected): void
    {
        $this->assertSame(
            $expected,
            $this->sm->getPermittedTransitions($status),
            "getPermittedTransitions({$status}) did not return the expected target list."
        );
    }

    public static function permittedTransitionListProvider(): array
    {
        return [
            'Pengajuan (6) -> [5]' => [6,  [5]],
            'Verifikasi Berkas (5) -> [4, -1]' => [5,  [4, -1]],
            'Assessment (4) -> [3, -1]' => [4,  [3, -1]],
            'Visitasi (3) -> [2]' => [3,  [2]],
            'Pasca Visitasi (2) -> [1]' => [2,  [1]],
            'Validasi Admin (1) -> [0, -1]' => [1,  [0, -1]],
            'Ditolak (-1) -> [-2]' => [-1, [-2]],
            'Banding (-2) -> [1, -1]' => [-2, [1, -1]],
        ];
    }

    public function test_get_permitted_transitions_returns_empty_for_terminal_selesai(): void
    {
        // Status 0 (Selesai) is terminal — no outgoing transitions.
        $this->assertSame([], $this->sm->getPermittedTransitions(0));
    }

    public function test_get_permitted_transitions_returns_empty_for_unknown_status(): void
    {
        $this->assertSame([], $this->sm->getPermittedTransitions(99));
        $this->assertSame([], $this->sm->getPermittedTransitions(-99));
        $this->assertSame([], $this->sm->getPermittedTransitions(7));
    }
}
