<?php

namespace App\Events;

use App\Models\Akreditasi;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when admin returns an akreditasi for administrative revision.
 *
 * Payload:
 *   $akreditasi - the akreditasi record
 *   $catatan    - admin's notes on what needs to be fixed
 */
class PerbaikanRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Akreditasi $akreditasi,
        public readonly ?string $catatan = null,
    ) {}
}
