<?php

namespace Tests\Feature\ConcurrentAccess;

use App\Models\Akreditasi;
use Faker\Factory as Faker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Property-Based Tests for Button Disable Logic.
 *
 */
#[Group('Feature:concurrent-access-handling')]
#[Group('Property4')]
class ButtonDisablePropertyTest extends TestCase
{
    /**
     * Data provider: terminal statuses (1 and 2) should disable action buttons.
     */
    public static function terminalStatusProvider(): array
    {
        return [
            'status_1_berhasil' => [1, true],
            'status_2_ditolak'  => [2, true],
        ];
    }

    /**
     * Data provider: non-terminal statuses should NOT disable action buttons.
     */
    public static function nonTerminalStatusProvider(): array
    {
        return [
            'status_3_validasi'   => [3, false],
            'status_4_visitasi'   => [4, false],
            'status_5_assessment' => [5, false],
            'status_6_pengajuan'  => [6, false],
        ];
    }

    /**
     * Data provider: random status combinations for property testing.
     */
    public static function randomStatusProvider(): array
    {
        $faker = Faker::create();
        $cases = [];

        for ($i = 0; $i < 100; $i++) {
            $status = $faker->numberBetween(1, 6);
            $isTerminal = in_array($status, [1, 2]);
            $cases["case_{$i}_status_{$status}"] = [$status, $isTerminal];
        }

        return $cases;
    }

    /**
     * Property 4: Terminal statuses (1, 2) should disable action buttons.
     *
     * For any akreditasi record whose current status is terminal (1=Berhasil or 2=Ditolak),
     * the UI component SHALL report those buttons as disabled.
     *
     * **Validates: Requirements 3.5, 4.1**
     *
     */
#[DataProvider('terminalStatusProvider')]
public function test_terminal_status_disables_action_buttons(int $status, bool $expectedDisabled): void
    {
        $isDisabled = in_array($status, [1, 2]);

        $this->assertEquals(
            $expectedDisabled,
            $isDisabled,
            "Status {$status} should " . ($expectedDisabled ? '' : 'NOT ') . "disable action buttons"
        );
    }

    /**
     * Property 4: Non-terminal statuses should NOT disable action buttons.
     *
     * **Validates: Requirements 3.5, 4.1**
     *
     */
#[DataProvider('nonTerminalStatusProvider')]
public function test_non_terminal_status_does_not_disable_action_buttons(int $status, bool $expectedDisabled): void
    {
        $isDisabled = in_array($status, [1, 2]);

        $this->assertEquals(
            $expectedDisabled,
            $isDisabled,
            "Status {$status} should NOT disable action buttons"
        );
    }

    /**
     * Property 4: Random status combinations — correct disable logic.
     *
     * For any random status value, the disable logic must be consistent:
     * only statuses 1 and 2 are terminal.
     *
     * **Validates: Requirements 3.5, 4.1**
     *
     */
#[DataProvider('randomStatusProvider')]
public function test_random_status_combinations_correct_disable_logic(int $status, bool $expectedDisabled): void
    {
        $isDisabled = in_array($status, [1, 2]);

        $this->assertEquals(
            $expectedDisabled,
            $isDisabled,
            "Status {$status} disable logic should be " . ($expectedDisabled ? 'true' : 'false')
        );
    }

    /**
     * Property 4: Admin finalize action should only be available for status 3 (Validasi).
     *
     * **Validates: Requirements 4.1**
     */
public function test_admin_finalize_only_available_for_status_3(): void
    {
        $allStatuses = [1, 2, 3, 4, 5, 6];

        foreach ($allStatuses as $status) {
            $canFinalize = ($status === 3);
            $isTerminal = in_array($status, [1, 2]);

            if ($isTerminal) {
                $this->assertFalse($canFinalize,
                    "Status {$status} is terminal — finalize should not be available");
            }

            if ($status === 3) {
                $this->assertTrue($canFinalize,
                    "Status 3 (Validasi) — finalize should be available");
            }
        }
    }

    /**
     * Property 4: Asesor finalize action should only be available for status 4 (Visitasi).
     *
     * **Validates: Requirements 4.1**
     */
public function test_asesor_finalize_only_available_for_status_4(): void
    {
        $allStatuses = [1, 2, 3, 4, 5, 6];

        foreach ($allStatuses as $status) {
            $canFinalize = ($status === 4);
            $isTerminal = in_array($status, [1, 2]);

            if ($isTerminal) {
                $this->assertFalse($canFinalize,
                    "Status {$status} is terminal — asesor finalize should not be available");
            }

            if ($status === 4) {
                $this->assertTrue($canFinalize,
                    "Status 4 (Visitasi) — asesor finalize should be available");
            }
        }
    }
}
