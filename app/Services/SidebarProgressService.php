<?php

namespace App\Services;

use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\Pesantren;
use App\Models\SdmPesantren;

class SidebarProgressService
{
    /**
     * Required fields for Profil Pesantren section.
     */
    private const PROFIL_REQUIRED_FIELDS = [
        'nama_pesantren',
        'ns_pesantren',
        'alamat',
        'provinsi_kode',
        'kota_kabupaten',
        'tahun_pendirian',
        'luas_tanah',
        'luas_bangunan',
        'nama_mudir',
        'jenjang_pendidikan_mudir',
        'telp_pesantren',
        'hp_wa',
        'email_pesantren',
        'persyarikatan',
        'visi',
        'misi',
    ];

    private const PROFIL_DOCUMENT_FIELDS = [
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

    /**
     * Required fields for IPM section.
     */
    private const IPM_REQUIRED_FIELDS = [
        'nsp_file',
        'lulus_santri_file',
        'kurikulum_file',
        'buku_ajar_file',
    ];

    /**
     * Returns progress status for each trackable section.
     * Status: 'complete', 'incomplete', 'not_started'
     *
     * @return array<string, string>
     */
    public function getProgressForUser(int $userId): array
    {
        return [
            'profil' => $this->getSectionProgress($userId, 'profil')['status'],
            'ipm' => $this->getSectionProgress($userId, 'ipm')['status'],
            'sdm' => $this->getSectionProgress($userId, 'sdm')['status'],
            'edpm' => $this->getSectionProgress($userId, 'edpm')['status'],
        ];
    }

    /**
     * Calculate completion status for a specific section.
     *
     * @param  string  $section  One of: 'profil', 'ipm', 'sdm', 'edpm'
     * @return array{status: string, filled: int, total: int}
     */
    public function getSectionProgress(int $userId, string $section): array
    {
        return match ($section) {
            'profil' => $this->calculateProfilProgress($userId),
            'ipm' => $this->calculateIpmProgress($userId),
            'sdm' => $this->calculateSdmProgress($userId),
            'edpm' => $this->calculateEdpmProgress($userId),
            default => ['status' => 'not_started', 'filled' => 0, 'total' => 0],
        };
    }

    /**
     * Calculate progress for Profil Pesantren section.
     */
    private function calculateProfilProgress(int $userId): array
    {
        $pesantren = Pesantren::with('units')->where('user_id', $userId)->first();
        $unitCount = $pesantren?->units->count() ?? 0;
        $total = count(self::PROFIL_REQUIRED_FIELDS) + count(self::PROFIL_DOCUMENT_FIELDS) + 1 + $unitCount;

        if (! $pesantren) {
            return ['status' => 'not_started', 'filled' => 0, 'total' => $total];
        }

        $filled = 0;
        foreach (self::PROFIL_REQUIRED_FIELDS as $field) {
            if (filled($pesantren->{$field})) {
                $filled++;
            }
        }

        foreach (self::PROFIL_DOCUMENT_FIELDS as $field) {
            if (filled($pesantren->{$field})) {
                $filled++;
            }
        }

        $layanan = $pesantren->layanan_satuan_pendidikan ?? [];
        if (is_array($layanan) && $layanan !== []) {
            $filled++;
        }

        $filled += $pesantren->units->filter(fn ($unit) => (int) $unit->jumlah_rombel > 0)->count();

        return [
            'status' => $this->determineStatus($filled, $total),
            'filled' => $filled,
            'total' => $total,
        ];
    }

    /**
     * Calculate progress for IPM section.
     */
    private function calculateIpmProgress(int $userId): array
    {
        $total = count(self::IPM_REQUIRED_FIELDS);
        $ipm = Ipm::where('user_id', $userId)->first();

        if (! $ipm) {
            return ['status' => 'not_started', 'filled' => 0, 'total' => $total];
        }

        $filled = 0;
        foreach (self::IPM_REQUIRED_FIELDS as $field) {
            if (! empty($ipm->{$field})) {
                $filled++;
            }
        }

        return [
            'status' => $this->determineStatus($filled, $total),
            'filled' => $filled,
            'total' => $total,
        ];
    }

    /**
     * Calculate progress for SDM section.
     */
    private function calculateSdmProgress(int $userId): array
    {
        $pesantren = Pesantren::with('units')->where('user_id', $userId)->first();
        $levels = $pesantren?->units->pluck('unit')->all() ?? [];
        $total = count($levels);

        if ($total === 0) {
            return ['status' => 'not_started', 'filled' => 0, 'total' => 0];
        }

        $filled = SdmPesantren::query()
            ->where('user_id', $userId)
            ->whereIn('tingkat', $levels)
            ->distinct('tingkat')
            ->count('tingkat');

        return [
            'status' => $this->determineStatus(min($filled, $total), $total),
            'filled' => min($filled, $total),
            'total' => $total,
        ];
    }

    /**
     * Calculate progress for EDPM/IPR section.
     */
    private function calculateEdpmProgress(int $userId): array
    {
        $total = MasterEdpmButir::count();

        if ($total === 0) {
            return ['status' => 'not_started', 'filled' => 0, 'total' => 0];
        }

        $filled = Edpm::query()
            ->where('user_id', $userId)
            ->distinct('butir_id')
            ->count('butir_id');

        $filled = min($filled, $total);

        return [
            'status' => $this->determineStatus($filled, $total),
            'filled' => $filled,
            'total' => $total,
        ];
    }

    /**
     * Determine status based on filled count vs total.
     */
    private function determineStatus(int $filled, int $total): string
    {
        if ($filled === 0) {
            return 'not_started';
        }

        if ($filled >= $total) {
            return 'complete';
        }

        return 'incomplete';
    }
}
