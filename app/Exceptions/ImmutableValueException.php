<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an attempt is made to modify a value that has been marked as Final (is_final=true).
 *
 * Validates Requirements 7.6, 9.6
 */
class ImmutableValueException extends RuntimeException
{
    public function __construct(string $message = 'Nilai sudah Final dan tidak dapat diubah.')
    {
        parent::__construct($message);
    }
}
