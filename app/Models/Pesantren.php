<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Pesantren extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * Audit fix C-2 (P0): explicit allowlist replaces $guarded = [] to prevent
     * mass-assignment of unintended columns (e.g. id, timestamps).
     *
     * IMPORTANT: callers MUST set `user_id` from `Auth::id()`, never from
     * request input. Mass-assignment of user_id from untrusted input would
     * allow a pesantren to claim ownership of another tenant's record.
     */
    protected $fillable = [
        'user_id',
        'nama_pesantren',
        'nspp',
        'ns_pesantren',
        'alamat',
        'kota_kabupaten',
        'kabupaten',
        'kabupaten_kode',
        'kecamatan',
        'kelurahan',
        'provinsi',
        'provinsi_kode',
        'tahun_pendirian',
        'nama_mudir',
        'jenjang_pendidikan_mudir',
        'telp_pesantren',
        'hp_wa',
        'email_pesantren',
        'persyarikatan',
        'visi',
        'misi',
        'luas_tanah',
        'luas_bangunan',
        'layanan_satuan_pendidikan',
        'status_kepemilikan_tanah',
        'sertifikat_nsp',
        'rk_anggaran',
        'silabus_rpp',
        'peraturan_kepegawaian',
        'file_lk_iapm',
        'laporan_tahunan',
        'dok_profil',
        'dok_nsp',
        'dok_renstra',
        'dok_rk_anggaran',
        'dok_kurikulum',
        'dok_silabus_rpp',
        'dok_kepengasuhan',
        'dok_peraturan_kepegawaian',
        'dok_sarpras',
        'dok_laporan_tahunan',
        'dok_sop',
        'is_locked',
    ];

    protected $casts = [
        'layanan_satuan_pendidikan' => 'array',
        'is_locked' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($pesantren) {
            $pesantren->units()->delete();

            // Audit fix PM-3: delete uploaded files when pesantren is deleted
            // to prevent unbounded orphan accumulation in storage.
            $fileColumns = [
                'status_kepemilikan_tanah',
                'sertifikat_nsp',
                'rk_anggaran',
                'silabus_rpp',
                'peraturan_kepegawaian',
                'file_lk_iapm',
                'laporan_tahunan',
                'dok_profil',
                'dok_nsp',
                'dok_renstra',
                'dok_rk_anggaran',
                'dok_kurikulum',
                'dok_silabus_rpp',
                'dok_kepengasuhan',
                'dok_peraturan_kepegawaian',
                'dok_sarpras',
                'dok_laporan_tahunan',
                'dok_sop',
            ];

            $paths = collect($fileColumns)
                ->map(fn ($col) => $pesantren->$col)
                ->filter()
                ->values()
                ->all();

            if ($paths) {
                Storage::disk('public')->delete($paths);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function units()
    {
        return $this->hasMany(PesantrenUnit::class);
    }
}
