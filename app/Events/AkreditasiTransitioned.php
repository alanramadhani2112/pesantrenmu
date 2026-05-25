<?php

namespace App\Events;

use App\Models\Akreditasi;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after any status transition on an Akreditasi record.
 *
 * Payload:
 *   $akreditasi  — the record after the transition (status = toStatus)
 *   $fromStatus  — the status before the transition
 *   $toStatus    — the status after the transition
 *   $actor       — the User who triggered the transition
 */
class AkreditasiTransitioned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Akreditasi $akreditasi,
        public readonly int $fromStatus,
        public readonly int $toStatus,
        public readonly User $actor,
    ) {}
}
