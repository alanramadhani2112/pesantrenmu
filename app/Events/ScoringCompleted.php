<?php

namespace App\Events;

use App\Models\Akreditasi;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when assessor scoring is finalized and the akreditasi transitions 2 → 1.
 *
 * Payload:
 *   $akreditasi  — the akreditasi record (status = 1 after finalization)
 */
class ScoringCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Akreditasi $akreditasi,
    ) {}
}
