<?php

namespace App\Exceptions;

use App\Models\Akreditasi;
use RuntimeException;

/**
 * Thrown when an attempted akreditasi status transition is not permitted by
 * the state machine.
 *
 * Validates Requirement 1.3.
 */
class InvalidTransitionException extends RuntimeException
{
    public function __construct(
        public readonly int $from,
        public readonly int $to,
    ) {
        $fromLabel = Akreditasi::getStatusLabel($from);
        $toLabel = Akreditasi::getStatusLabel($to);

        parent::__construct(
            "Invalid akreditasi status transition: {$from} ({$fromLabel}) -> {$to} ({$toLabel}) is not permitted by the state machine."
        );
    }
}
