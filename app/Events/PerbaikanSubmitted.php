<?php

namespace App\Events;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a Pesantren submits perbaikan (corrections) for a document rejection.
 *
 * Payload:
 *   $akreditasi  — the akreditasi record
 *   $rejection   — the AkreditasiRejection that was resolved by this perbaikan
 */
class PerbaikanSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Akreditasi $akreditasi,
        public readonly AkreditasiRejection $rejection,
    ) {}
}
