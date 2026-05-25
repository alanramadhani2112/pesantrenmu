<?php

namespace App\Services;

use InvalidArgumentException;

class ScoreCalculationService
{
    /**
     * Komponen configuration: name => [butir_count, bobot]
     * Total IK butir: 8 + 10 + 10 + 12 = 40
     * Total bobot: 35 + 29 + 18 + 18 = 100
     */
    public const KOMPONEN_CONFIG = [
        'MUTU LULUSAN'        => ['butir_count' => 8,  'bobot' => 35],
        'PROSES PEMBELAJARAN' => ['butir_count' => 10, 'bobot' => 29],
        'MUTU USTAZ'          => ['butir_count' => 10, 'bobot' => 18],
        'MANAJEMEN PESANTREN' => ['butir_count' => 12, 'bobot' => 18],
    ];

    public const IPR_BUTIR_COUNT = 22;
    public const IPR_CMAKS = 88; // 22 * 4

    public const TOTAL_BUTIR = 62; // 40 IK + 22 IPR
    public const SCORE_MIN = 1;
    public const SCORE_MAX = 4;

    // -------------------------------------------------------------------------
    // Task 4.2 — Skor_Komponen = (Ci / Cmaks) × Bobot, rounded to 2dp
    // -------------------------------------------------------------------------

    /**
     * Calculate Skor_Komponen for a single komponen.
     *
     * Formula: (Ci / Cmaks) × Bobot
     *   Ci    = sum of NV values for all butir in this komponen
     *   Cmaks = butir_count × 4
     *   Bobot = weight from KOMPONEN_CONFIG
     *
     * @param  array<int>  $nvValues     NV values for each butir in this komponen (each must be 1–4)
     * @param  string      $komponenName Key in KOMPONEN_CONFIG
     * @return float                     Rounded to 2 decimal places
     *
     * @throws InvalidArgumentException  If komponenName is unknown or any NV value is outside 1–4
     */
    public function calculateSkorKomponen(array $nvValues, string $komponenName): float
    {
        if (!array_key_exists($komponenName, self::KOMPONEN_CONFIG)) {
            throw new InvalidArgumentException(
                "Unknown komponen name: '{$komponenName}'. Valid names: " .
                implode(', ', array_keys(self::KOMPONEN_CONFIG))
            );
        }

        foreach ($nvValues as $nv) {
            if (!is_int($nv) || $nv < self::SCORE_MIN || $nv > self::SCORE_MAX) {
                throw new InvalidArgumentException(
                    "NV value must be an integer in range 1–4, got: " . var_export($nv, true)
                );
            }
        }

        $config   = self::KOMPONEN_CONFIG[$komponenName];
        $bobot    = $config['bobot'];
        $butirCount = $config['butir_count'];
        $cmaks    = $butirCount * 4;

        $ci = array_sum($nvValues);

        return round(($ci / $cmaks) * $bobot, 2);
    }

    // -------------------------------------------------------------------------
    // Task 4.3 — Total_Skor_IK = sum of all 4 Skor_Komponen, rounded to 2dp
    // -------------------------------------------------------------------------

    /**
     * Calculate Total_Skor_IK as the sum of all 4 Skor_Komponen values.
     *
     * @param  array<float>  $skorKomponens  Array of 4 Skor_Komponen values
     * @return float                         Rounded to 2 decimal places
     */
    public function calculateTotalSkorIK(array $skorKomponens): float
    {
        return round(array_sum($skorKomponens), 2);
    }

    // -------------------------------------------------------------------------
    // Task 4.4 — Skor_IPR = (Ci_IPR / 88) × 100, rounded to 2dp
    // -------------------------------------------------------------------------

    /**
     * Calculate Skor_IPR for the 22 IPR butir.
     *
     * Formula: (Ci_IPR / 88) × 100
     *   Ci_IPR = sum of NV for all 22 IPR butir
     *   88     = IPR_CMAKS (22 × 4)
     *
     * @param  array<int>  $iprNvValues  NV values for each of the 22 IPR butir (each must be 1–4)
     * @return float                     Rounded to 2 decimal places
     *
     * @throws InvalidArgumentException  If any NV value is outside 1–4
     */
    public function calculateSkorIPR(array $iprNvValues): float
    {
        foreach ($iprNvValues as $nv) {
            if (!is_int($nv) || $nv < self::SCORE_MIN || $nv > self::SCORE_MAX) {
                throw new InvalidArgumentException(
                    "IPR NV value must be an integer in range 1–4, got: " . var_export($nv, true)
                );
            }
        }

        $ciIpr = array_sum($iprNvValues);

        return round(($ciIpr / self::IPR_CMAKS) * 100, 2);
    }

    // -------------------------------------------------------------------------
    // Task 4.5 — Nilai_Akhir = (0.7 × Total_Skor_IK) + (0.3 × Skor_IPR), rounded to 2dp
    // -------------------------------------------------------------------------

    /**
     * Calculate Nilai_Akhir (final accreditation score).
     *
     * Formula: (0.7 × Total_Skor_IK) + (0.3 × Skor_IPR)
     *
     * @param  float  $totalSkorIK  Total IK score (sum of 4 Skor_Komponen)
     * @param  float  $skorIPR      IPR score
     * @return float                Rounded to 2 decimal places
     */
    public function calculateNilaiAkhir(float $totalSkorIK, float $skorIPR): float
    {
        return round((0.7 * $totalSkorIK) + (0.3 * $skorIPR), 2);
    }

    // -------------------------------------------------------------------------
    // Task 4.6 — determinePeringkat: A (≥86), B (≥71 and <86), C (<71)
    // -------------------------------------------------------------------------

    /**
     * Determine Peringkat (grade) from Nilai_Akhir.
     *
     * Thresholds:
     *   A / Unggul / Mumtaz  : Nilai_Akhir >= 86.00
     *   B / Baik  / Jayyid   : 71.00 <= Nilai_Akhir < 86.00
     *   C / Cukup            : Nilai_Akhir < 71.00
     *
     * @param  float   $nilaiAkhir  Final accreditation score
     * @return string               'A', 'B', or 'C'
     */
    public function determinePeringkat(float $nilaiAkhir): string
    {
        if ($nilaiAkhir >= 86.0) {
            return 'A';
        }

        if ($nilaiAkhir >= 71.0) {
            return 'B';
        }

        return 'C';
    }

    // -------------------------------------------------------------------------
    // Task 4.7 — calculateAll() and calculateDelta()
    // -------------------------------------------------------------------------

    /**
     * Orchestrate all score calculations from raw NV values.
     *
     * @param  array{ik: array<string, array<int>>, ipr: array<int>}  $allNvValues
     *         'ik'  => associative array keyed by komponen name, each value is an array of NV ints
     *         'ipr' => flat array of 22 NV ints for IPR butir
     *
     * @return array{
     *   skor_komponen: array<string, float>,
     *   total_skor_ik: float,
     *   skor_ipr: float,
     *   nilai_akhir: float,
     *   peringkat: string
     * }
     */
    public function calculateAll(array $allNvValues): array
    {
        $skorKomponens = [];

        foreach (self::KOMPONEN_CONFIG as $komponenName => $config) {
            $nvValues = $allNvValues['ik'][$komponenName] ?? [];
            $skorKomponens[$komponenName] = $this->calculateSkorKomponen($nvValues, $komponenName);
        }

        $totalSkorIK = $this->calculateTotalSkorIK(array_values($skorKomponens));
        $skorIPR     = $this->calculateSkorIPR($allNvValues['ipr'] ?? []);
        $nilaiAkhir  = $this->calculateNilaiAkhir($totalSkorIK, $skorIPR);
        $peringkat   = $this->determinePeringkat($nilaiAkhir);

        return [
            'skor_komponen' => $skorKomponens,
            'total_skor_ik' => $totalSkorIK,
            'skor_ipr'      => $skorIPR,
            'nilai_akhir'   => $nilaiAkhir,
            'peringkat'     => $peringkat,
        ];
    }

    /**
     * Calculate Delta = |NA1 - NA2|.
     *
     * @param  int  $na1  Nilai Asesor 1 (1–4)
     * @param  int  $na2  Nilai Asesor 2 (1–4)
     * @return int        Absolute difference (0–3)
     */
    public function calculateDelta(int $na1, int $na2): int
    {
        return abs($na1 - $na2);
    }
}
