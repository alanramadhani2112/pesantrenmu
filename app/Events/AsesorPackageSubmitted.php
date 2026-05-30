<?php

namespace App\Events;

use App\Models\Akreditasi;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when Ketua Kelompok submits the final asesor package.
 *
 * Payload:
 *   $akreditasi - the akreditasi record with completed assessment
 */
class AsesorPackageSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Akreditasi $akreditasi,
    ) {}
}
