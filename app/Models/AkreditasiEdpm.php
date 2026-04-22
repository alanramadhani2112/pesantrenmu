<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AkreditasiEdpm extends Model
{
    protected $fillable = [
        'akreditasi_id',
        'pesantren_id',
        'asesor_id',
        'butir_id',
        'isian',
        'nk',
        'nv',
        'catatan',
    ];
}
