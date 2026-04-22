<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AkreditasiEdpmCatatan extends Model
{
    protected $fillable = [
        'akreditasi_id',
        'pesantren_id',
        'asesor_id',
        'komponen_id',
        'catatan',
        'nk',
    ];
}
