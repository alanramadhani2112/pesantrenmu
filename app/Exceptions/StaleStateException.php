<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an akreditasi update fails due to concurrent modification.
 *
 * Optimistic-locking guard: if a transition's UPDATE statement affects 0 rows
 * because another request changed the record's updated_at timestamp first,
 * the second request is rejected with this exception.
 *
 * Validates Requirement 1.5.
 */
class StaleStateException extends RuntimeException
{
    public function __construct(
        public readonly int $akreditasiId,
        string $message = 'Akreditasi telah dimodifikasi oleh proses lain. Silakan muat ulang dan coba kembali.',
    ) {
        parent::__construct($message);
    }
}
