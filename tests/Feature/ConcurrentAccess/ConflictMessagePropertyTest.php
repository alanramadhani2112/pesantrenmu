<?php

namespace Tests\Feature\ConcurrentAccess;

use App\Exceptions\ConflictException;
use App\Models\Akreditasi;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Property-Based Tests for Conflict Message Status Labels.
 */
#[Group('Feature:concurrent-access-handling')]
#[Group('Property3')]
class ConflictMessagePropertyTest extends TestCase
{
    /**
     * Data provider for all 6 valid status values with their expected labels.
     */
    public static function statusLabelProvider(): array
    {
        return [
            'status_1_validasi_admin' => [1, 'Validasi Admin'],
            'status_2_pasca_visitasi' => [2, 'Penilaian Pasca Visitasi'],
            'status_3_visitasi' => [3, 'Visitasi'],
            'status_4_assessment' => [4, 'Review Asesor'],
            'status_5_verifikasi' => [5, 'Verifikasi Berkas'],
            'status_6_pengajuan' => [6, 'Pengajuan'],
        ];
    }

    /**
     * Property 3: Conflict message includes correct status label for all 6 status values.
     *
     * For any akreditasi status value (1-6), when a ConflictException is raised,
     * the resulting conflict message SHALL contain the human-readable status label
     * corresponding to that status value.
     *
     * **Validates: Requirements 2.2**
     */
    #[DataProvider('statusLabelProvider')]
    public function test_conflict_exception_returns_correct_status_label(int $status, string $expectedLabel): void
    {
        $exception = new ConflictException(1, $status);

        $this->assertEquals(
            $expectedLabel,
            $exception->getStatusLabel(),
            "ConflictException for status {$status} should return label '{$expectedLabel}'"
        );
    }

    /**
     * Property 3 (variant): Status label from ConflictException matches Akreditasi::getStatusLabel().
     *
     * The ConflictException::getStatusLabel() must be consistent with the model's static method.
     *
     * **Validates: Requirements 2.2**
     */
    #[DataProvider('statusLabelProvider')]
    public function test_conflict_exception_label_matches_model_label(int $status, string $expectedLabel): void
    {
        $exception = new ConflictException(42, $status);

        $modelLabel = Akreditasi::getStatusLabel($status);

        $this->assertEquals(
            $modelLabel,
            $exception->getStatusLabel(),
            "ConflictException::getStatusLabel() should match Akreditasi::getStatusLabel() for status {$status}"
        );

        $this->assertEquals(
            $expectedLabel,
            $modelLabel,
            "Akreditasi::getStatusLabel({$status}) should return '{$expectedLabel}'"
        );
    }

    /**
     * Property 3 (variant): ConflictException message can be used to build user-facing conflict message.
     *
     * Verifies that the status label can be embedded in a conflict message string.
     *
     * **Validates: Requirements 2.2, 2.3**
     */
    #[DataProvider('statusLabelProvider')]
    public function test_conflict_message_contains_status_label(int $status, string $expectedLabel): void
    {
        $exception = new ConflictException(10, $status);

        $conflictMessage = "Akreditasi telah dimodifikasi oleh pengguna lain. Status saat ini: {$exception->getStatusLabel()}.";

        $this->assertStringContainsString(
            $expectedLabel,
            $conflictMessage,
            "Conflict message should contain the status label '{$expectedLabel}' for status {$status}"
        );
    }
}
