<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asesor extends Model
{
    protected $guarded = [];

    protected $casts = [
        'layanan_satuan_pendidikan' => 'array',
        'riwayat_pendidikan' => 'array',
        'pengalaman_pelatihan' => 'array',
        'pengalaman_bekerja' => 'array',
        'pengalaman_berorganisasi' => 'array',
        'karya_publikasi' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }
}
