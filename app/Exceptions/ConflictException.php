<?php

namespace App\Exceptions;

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
        return \App\Models\Akreditasi::getStatusLabel($this->currentStatus);
    }
}
