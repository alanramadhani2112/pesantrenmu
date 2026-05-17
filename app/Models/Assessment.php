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
        'last_reminder_sent_at',
        'last_escalation_sent_at',
    ];

    protected $casts = [
        'tanggal_mulai' => 'datetime',
        'tanggal_berakhir' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'last_escalation_sent_at' => 'datetime',
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
