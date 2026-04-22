<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;

class Akreditasi extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'parent',
        'uuid',
        'nomor_sk',
        'catatan',
        'status',
        'tgl_visitasi',
        'tgl_visitasi_akhir',
        'nilai',
        'peringkat',
        'na1',
        'na2',
        'nk',
        'nv',
        'sertifikat_path',
        'kartu_kendali',
        'laporan_visitasi_file',
        'laporan_visitasi_file_2',
        'masa_berlaku',
        'masa_berlaku_akhir',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::deleting(function ($akreditasi) {
            $akreditasi->assessments()->delete();
            AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)->delete();
            AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasi->id)->delete();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function assessment1()
    {
        return $this->hasOne(Assessment::class)->where('tipe', 1);
    }

    public function assessment2()
    {
        return $this->hasOne(Assessment::class)->where('tipe', 2);
    }
    public function catatans()
    {
        return $this->hasMany(AkreditasiCatatan::class);
    }
    public static function getStatusLabel($status)
    {
        return match ($status) {
            1 => 'Berhasil',
            2 => 'Di Tolak',
            3 => 'Validasi',
            4 => 'Visitasi',
            5 => 'Assessment',
            6 => 'Pengajuan',
            default => 'Unknown',
        };
    }

    public static function getStatusBadgeClass($status)
    {
        return match ($status) {
            1 => 'bg-green-100 text-green-800',
            2 => 'bg-red-100 text-red-800',
            3 => 'bg-indigo-100 text-indigo-800',
            4 => 'bg-purple-100 text-purple-800',
            5 => 'bg-amber-100 text-amber-800',
            6 => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
