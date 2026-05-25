<?php

namespace App\Events;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a perbaikan deadline is approaching (within the configured reminder window).
 *
 * Dispatched by RejectionService::processDeadlines() when a pending rejection's
 * deadline is within the reminder threshold (default: 3 days).
 *
 * Payload:
 *   $akreditasi    — the akreditasi record
 *   $rejection     — the pending AkreditasiRejection with the approaching deadline
 *   $daysRemaining — number of days remaining until the deadline
 */
class PerbaikanDeadlineApproaching
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Akreditasi $akreditasi,
        public readonly AkreditasiRejection $rejection,
        public readonly int $daysRemaining,
    ) {}
}
