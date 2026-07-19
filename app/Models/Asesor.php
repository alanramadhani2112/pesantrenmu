<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asesor extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * Audit fix C-2 (P0): explicit allowlist replaces $guarded = [].
     * Callers MUST set `user_id` from `Auth::id()`, never from request input.
     */
    protected $fillable = [
        'user_id',
        'foto',
        'nama_dengan_gelar',
        'nama_tanpa_gelar',
        'nbm_nia',
        'nomor_induk_asesor_pm',
        'whatsapp',
        'nik',
        'tempat_lahir',
        'tanggal_lahir',
        'unit_kerja',
        'jabatan_utama',
        'jenis_kelamin',
        'alamat_kantor',
        'telp_kantor',
        'tahun_terbit_sertifikat',
        'alamat_rumah',
        'provinsi',
        'kota_kabupaten',
        'status_perkawinan',
        'profesi',
        'pendidikan_terakhir',
        'email_pribadi',
        'layanan_satuan_pendidikan',
        'rombel_sd',
        'rombel_mi',
        'rombel_smp',
        'rombel_mts',
        'rombel_sma',
        'rombel_ma',
        'rombel_smk',
        'rombel_spm',
        'luas_tanah',
        'luas_bangunan',
        'ktp_file',
        'ijazah_file',
        'kartu_nbm_file',
        'riwayat_pendidikan',
        'pengalaman_pelatihan',
        'pengalaman_bekerja',
        'pengalaman_berorganisasi',
        'karya_publikasi',
    ];

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
