<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'akreditasi_id',
        'asesor_id',
        'tipe',
        'tanggal_mulai',
        'tanggal_berakhir',
    ];

    public function akreditasi()
    {
        return $this->belongsTo(Akreditasi::class);
    }

    public function asesor()
    {
        return $this->belongsTo(Asesor::class);
    }
}
