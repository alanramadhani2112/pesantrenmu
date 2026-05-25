<?php

namespace App\Events;

use App\Models\Akreditasi;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a visitasi is scheduled or rescheduled by Asesor_1.
 *
 * Payload:
 *   $akreditasi    — the akreditasi record (status = 3 after scheduling)
 *   $schedule      — ['tanggal_mulai' => 'Y-m-d', 'tanggal_akhir' => 'Y-m-d', 'catatan_visitasi' => '...']
 *   $isReschedule  — true when this is a reschedule of an existing visitasi
 */
class VisitasiScheduled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Akreditasi $akreditasi,
        public readonly array $schedule,
        public readonly bool $isReschedule = false,
    ) {}
}
