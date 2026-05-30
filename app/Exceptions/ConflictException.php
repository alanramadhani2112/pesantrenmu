<?php

namespace App\Exceptions;

use App\Models\Akreditasi;

class ConflictException extends \DomainException
{
    public function __construct(
        public readonly int $akreditasiId,
        public readonly int $currentStatus,
        string $message = 'Record telah dimodifikasi oleh pengguna lain.'
    ) {
        parent::__construct($message);
    }

    public function getStatusLabel(): string
    {
        return Akreditasi::getStatusLabel($this->currentStatus);
    }
}
