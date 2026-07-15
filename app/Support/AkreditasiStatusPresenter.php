<?php

namespace App\Support;

use App\Models\Akreditasi;

class AkreditasiStatusPresenter
{
    public static function label(int|string|null $status): string
    {
        return Akreditasi::getStatusLabel($status);
    }

    public static function variant(int|string|null $status): string
    {
        return match ((int) $status) {
            Akreditasi::STATUS_SELESAI => 'success',
            Akreditasi::STATUS_DITOLAK => 'danger',
            Akreditasi::STATUS_BANDING => 'warning',
            Akreditasi::STATUS_VALIDASI_ADMIN => 'warning',
            Akreditasi::STATUS_PASCA_VISITASI => 'info',
            Akreditasi::STATUS_VISITASI => 'info',
            Akreditasi::STATUS_ASSESSMENT => 'info',
            Akreditasi::STATUS_VERIFIKASI_BERKAS => 'warning',
            Akreditasi::STATUS_PENGAJUAN => 'primary',
            default => 'secondary',
        };
    }

    /**
     * @return array{label: string, variant: string}
     */
    public static function for(int|string|null $status): array
    {
        return [
            'label' => self::label($status),
            'variant' => self::variant($status),
        ];
    }

    /**
     * @return array{label: string, variant: string}
     */
    public static function stage(int|string|null $status): array
    {
        return match ((int) $status) {
            Akreditasi::STATUS_PENGAJUAN => ['label' => 'Pengajuan', 'variant' => 'primary'],
            Akreditasi::STATUS_VERIFIKASI_BERKAS => ['label' => 'Verifikasi Berkas', 'variant' => 'warning'],
            Akreditasi::STATUS_ASSESSMENT => ['label' => 'Review Asesor', 'variant' => 'info'],
            Akreditasi::STATUS_VISITASI => ['label' => 'Visitasi', 'variant' => 'info'],
            Akreditasi::STATUS_PASCA_VISITASI => ['label' => 'Penilaian Pasca Visitasi', 'variant' => 'info'],
            Akreditasi::STATUS_VALIDASI_ADMIN => ['label' => 'Validasi Admin', 'variant' => 'warning'],
            Akreditasi::STATUS_SELESAI => ['label' => 'Selesai', 'variant' => 'success'],
            Akreditasi::STATUS_DITOLAK => ['label' => 'Ditolak', 'variant' => 'danger'],
            Akreditasi::STATUS_BANDING => ['label' => 'Banding', 'variant' => 'warning'],
            default => ['label' => 'Unknown', 'variant' => 'secondary'],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function tahapanOptions(): array
    {
        return [
            'pengajuan' => 'Pengajuan',
            'verifikasi' => 'Verifikasi',
            'visitasi' => 'Visitasi',
            'penilaian' => 'Penilaian',
            'hasil' => 'Hasil',
        ];
    }
}
