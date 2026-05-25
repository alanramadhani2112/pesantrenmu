<?php

namespace App\Events;

use App\Models\Akreditasi;
use App\Models\Banding;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a Pesantren submits a banding (appeal) for a rejected akreditasi.
 *
 * Payload:
 *   $akreditasi  — the akreditasi record (status = -2 after submission)
 *   $banding     — the newly created Banding record
 */
class BandingSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Akreditasi $akreditasi,
        public readonly Banding $banding,
    ) {}
}
