<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AkreditasiEdpmCatatan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'akreditasi_id',
        'pesantren_id',
        'asesor_id',
        'komponen_id',
        'catatan',
        'nk',
    ];
}
