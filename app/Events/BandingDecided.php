<?php

namespace App\Events;

use App\Models\Banding;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an Admin decides on a banding (appeal).
 *
 * Payload:
 *   $banding  — the Banding record after the decision
 *   $result   — 'diterima' or 'ditolak'
 */
class BandingDecided
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Banding $banding,
        public readonly string $result,
    ) {}
}
