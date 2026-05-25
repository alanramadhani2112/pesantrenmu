<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AkreditasiBandingEdpm extends Model
{
    protected $fillable = [
        'akreditasi_id',
        'banding_id',
        'asesor_id',
        'butir_id',
        'isian',
        'nk',
        'nv',
        'catatan_butir',
        'is_final',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'isian' => 'integer',
        'nk' => 'integer',
        'nv' => 'integer',
        'butir_id' => 'integer',
    ];

    public function akreditasi()
    {
        return $this->belongsTo(Akreditasi::class);
    }

    public function banding()
    {
        return $this->belongsTo(Banding::class);
    }

    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }
}
