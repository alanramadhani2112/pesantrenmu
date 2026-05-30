<?php

namespace App\Events;

use App\Models\Akreditasi;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when admin assigns Ketua Kelompok and Anggota Kelompok to an akreditasi.
 *
 * Payload:
 *   $akreditasi  - the akreditasi record
 *   $asesor1User - the User assigned as Ketua Kelompok (Asesor 1)
 *   $asesor2User - the User assigned as Anggota Kelompok (Asesor 2)
 */
class AsesorAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Akreditasi $akreditasi,
        public readonly User $asesor1User,
        public readonly User $asesor2User,
    ) {}
}
