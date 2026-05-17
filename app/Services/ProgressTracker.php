<?php

namespace App\Services;

use App\Models\AkreditasiEdpm;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;

class ProgressTracker
{
    /**
     * Calculate completion for a given asesor and field on an akreditasi.
     *
     * Counts AkreditasiEdpm records where the specified field is not null/empty
     * for the given akreditasi + asesor combination, against the total
     * MasterEdpmButir count.
     *
     * @return array{filled: int, total: int, percentage: float}
     */
    public function getCompletion(int $akreditasiId, int $asesorId, string $field = 'isian'): array
    {
        $total = MasterEdpmButir::count();

        if ($total === 0) {
            return ['filled' => 0, 'total' => 0, 'percentage' => 0.0];
        }

        $filled = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->where('asesor_id', $asesorId)
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->count();

        $percentage = round(($filled / $total) * 100, 2);

        return [
            'filled'     => $filled,
            'total'      => $total,
            'percentage' => $percentage,
        ];
    }

    /**
     * Get progress for all asesors on an akreditasi.
     *
     * Looks up Asesor 1 (tipe=1) and Asesor 2 (tipe=2) from Assessment records,
     * then calculates:
     *   - asesor1_na: Asesor 1 isian completion
     *   - asesor1_nk: Asesor 1 nk completion
     *   - asesor2_na: Asesor 2 isian completion (null if no Asesor 2 assigned)
     *
     * @return array{
     *   asesor1_na: array{filled: int, total: int, percentage: float}|null,
     *   asesor1_nk: array{filled: int, total: int, percentage: float}|null,
     *   asesor2_na: array{filled: int, total: int, percentage: float}|null,
     * }
     */
    public function getAkreditasiProgress(int $akreditasiId): array
    {
        $asesor1Assessment = Assessment::where('akreditasi_id', $akreditasiId)
            ->where('tipe', 1)
            ->first();

        $asesor2Assessment = Assessment::where('akreditasi_id', $akreditasiId)
            ->where('tipe', 2)
            ->first();

        $asesor1Na = null;
        $asesor1Nk = null;

        if ($asesor1Assessment !== null) {
            $asesor1Na = $this->getCompletion($akreditasiId, $asesor1Assessment->asesor_id, 'isian');
            $asesor1Nk = $this->getCompletion($akreditasiId, $asesor1Assessment->asesor_id, 'nk');
        }

        $asesor2Na = null;

        if ($asesor2Assessment !== null) {
            $asesor2Na = $this->getCompletion($akreditasiId, $asesor2Assessment->asesor_id, 'isian');
        }

        return [
            'asesor1_na' => $asesor1Na,
            'asesor1_nk' => $asesor1Nk,
            'asesor2_na' => $asesor2Na,
        ];
    }

    /**
     * Determine the Tailwind color class for a given completion percentage.
     *
     * - 'red'   for 0–49%
     * - 'amber' for 50–99%
     * - 'green' for 100%
     */
    public function getColorClass(float $percentage): string
    {
        if ($percentage >= 100.0) {
            return 'green';
        }

        if ($percentage >= 50.0) {
            return 'amber';
        }

        return 'red';
    }

    /**
     * Determine which asesor(s) are blocking finalization for an akreditasi.
     *
     * A blocker exists when the relevant completion percentage is below 100%.
     * Possible blockers: 'asesor1_na', 'asesor1_nk', 'asesor2_na'.
     *
     * @return array{blocked: bool, blockers: string[]}
     */
    public function getBlockingStatus(int $akreditasiId): array
    {
        $progress = $this->getAkreditasiProgress($akreditasiId);
        $blockers = [];

        if ($progress['asesor1_na'] !== null && $progress['asesor1_na']['percentage'] < 100.0) {
            $blockers[] = 'asesor1_na';
        }

        if ($progress['asesor1_nk'] !== null && $progress['asesor1_nk']['percentage'] < 100.0) {
            $blockers[] = 'asesor1_nk';
        }

        if ($progress['asesor2_na'] !== null && $progress['asesor2_na']['percentage'] < 100.0) {
            $blockers[] = 'asesor2_na';
        }

        return [
            'blocked'  => count($blockers) > 0,
            'blockers' => $blockers,
        ];
    }
}
