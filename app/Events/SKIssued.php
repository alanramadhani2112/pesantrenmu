<?php

namespace App\Events;

use App\Models\Akreditasi;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an SK (Surat Keputusan) is issued and the akreditasi transitions 1 → 0.
 *
 * Payload:
 *   $akreditasi  — the akreditasi record (status = 0 after issuance)
 *   $nilaiAkhir  — the final calculated score
 *   $peringkat   — the grade/rank (A, B, or C)
 *   $nomorSk     — the SK document number
 */
class SKIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Akreditasi $akreditasi,
        public readonly float $nilaiAkhir,
        public readonly string $peringkat,
        public readonly string $nomorSk,
    ) {}
}
