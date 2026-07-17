<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Akreditasi extends Model
{
    use SoftDeletes;

    public const STATUS_PENGAJUAN = 6;

    public const STATUS_VERIFIKASI_BERKAS = 5;

    public const STATUS_ASSESSMENT = 4;

    public const STATUS_VISITASI = 3;

    public const STATUS_PASCA_VISITASI = 2;

    public const STATUS_VALIDASI_ADMIN = 1;

    public const STATUS_SELESAI = 0;

    public const STATUS_DITOLAK = -1;

    public const STATUS_BANDING = -2;

    public const ACTIVE_STATUSES = [
        self::STATUS_PENGAJUAN,
        self::STATUS_VERIFIKASI_BERKAS,
        self::STATUS_ASSESSMENT,
        self::STATUS_VISITASI,
        self::STATUS_PASCA_VISITASI,
        self::STATUS_VALIDASI_ADMIN,
    ];

    /**
     * Fields writable by pesantren (submission + kartu kendali only).
     * Admin-only fields (nilai, peringkat, sertifikat_path, dll) are excluded
     * to prevent mass-assignment privilege escalation (audit fix PM-23).
     */
    public const PESANTREN_FILLABLE = [
        'user_id',
        'uuid',
        'catatan',
        'kartu_kendali',
    ];

    /**
     * Full fillable list used by admin/service layer.
     * Never pass $request->all() directly — always build explicit arrays.
     */
    protected $fillable = [
        'user_id',
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
        'catatan_visitasi',
        'visitasi_confirmed_at',
        'is_nilai_asesor_final',
        'is_nilai_asesor2_final',
        'is_nv_final',
        'laporan_visitasi_kelompok',
        'catatan_rekomendasi_admin',
        'laporan_visitasi_asesor1',
        'laporan_visitasi_asesor2',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'visitasi_confirmed_at' => 'datetime',
            'is_nilai_asesor_final' => 'boolean',
            'is_nilai_asesor2_final' => 'boolean',
            'is_nv_final' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::deleting(function ($akreditasi) {
            DB::transaction(function () use ($akreditasi) {
                $akreditasi->assessments()->delete();
                AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)->delete();
                AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasi->id)->delete();
            });

            // Audit fix PM-3: delete uploaded files when akreditasi is deleted
            // (soft or force) to prevent orphan accumulation in storage.
            $fileCols = [
                'sertifikat_path',
                'kartu_kendali',
                'laporan_visitasi_file',
                'laporan_visitasi_file_2',
                'laporan_visitasi_asesor1',
                'laporan_visitasi_asesor2',
            ];

            $paths = collect($fileCols)
                ->map(fn ($col) => $akreditasi->$col)
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

    public static function getStatusLabel($status): string
    {
        return match ((int) $status) {
            self::STATUS_PENGAJUAN => 'Pengajuan',
            self::STATUS_VERIFIKASI_BERKAS => 'Verifikasi Berkas',
            self::STATUS_ASSESSMENT => 'Review Asesor',
            self::STATUS_VISITASI => 'Visitasi',
            self::STATUS_PASCA_VISITASI => 'Penilaian Pasca Visitasi',
            self::STATUS_VALIDASI_ADMIN => 'Validasi Admin',
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_DITOLAK => 'Ditolak',
            self::STATUS_BANDING => 'Banding',
            default => 'Unknown',
        };
    }

    public static function activeStatuses(): array
    {
        return self::ACTIVE_STATUSES;
    }

    public static function terminalStatuses(): array
    {
        return [
            self::STATUS_SELESAI,
            self::STATUS_DITOLAK,
        ];
    }

    public static function isActiveStatus(int|string|null $status): bool
    {
        return in_array((int) $status, self::activeStatuses(), true);
    }

    public static function getStatusBadgeClass($status): string
    {
        return match ((int) $status) {
            0 => 'bg-green-100 text-green-800',
            -1, -2 => 'bg-red-100 text-red-800',
            1 => 'bg-indigo-100 text-indigo-800',
            2 => 'bg-purple-100 text-purple-800',
            3 => 'bg-amber-100 text-amber-800',
            4 => 'bg-blue-100 text-blue-800',
            5 => 'bg-yellow-100 text-yellow-800',
            6 => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get all rejections for this akreditasi.
     */
    public function rejections(): HasMany
    {
        return $this->hasMany(AkreditasiRejection::class);
    }

    /**
     * Get the active rejection (asesor type, pending status, latest).
     */
    public function activeRejection(): HasOne
    {
        return $this->hasOne(AkreditasiRejection::class)
            ->where('type', 'asesor')
            ->where('status', 'pending')
            ->latest();
    }

    /**
     * Get all bandings (appeals) for this akreditasi.
     */
    public function bandings(): HasMany
    {
        return $this->hasMany(Banding::class);
    }

    /**
     * Get the active banding (pending or under_review, latest).
     */
    public function activeBanding(): HasOne
    {
        return $this->hasOne(Banding::class)->whereIn('status', ['pending', 'under_review'])->latest();
    }

    /**
     * Get all banding EDPM records for this akreditasi.
     */
    public function bandingEdpms(): HasMany
    {
        return $this->hasMany(AkreditasiBandingEdpm::class);
    }

    /**
     * Get all banding EDPM catatan records for this akreditasi.
     */
    public function bandingEdpmCatatans(): HasMany
    {
        return $this->hasMany(AkreditasiBandingEdpmCatatan::class);
    }
}
