<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AkreditasiEdpm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'akreditasi_id',
        'pesantren_id',
        'asesor_id',
        'butir_id',
        'isian',
        'nk',
        'nv',
        'catatan',
        'is_final',
        'delta',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'delta' => 'integer',
    ];
}
