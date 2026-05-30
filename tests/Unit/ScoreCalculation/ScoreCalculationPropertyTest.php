<?php

namespace Tests\Unit\ScoreCalculation;

use App\Services\ScoreCalculationService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Property-Based Tests for ScoreCalculationService
 *
 * Covers Properties 2, 4, 5, 6, 7, 8 from the design document.
 */
#[Group('akreditasi-workflow-redesign')]
class ScoreCalculationPropertyTest extends TestCase
{
    private ScoreCalculationService $svc;

    /** Valid NV values per the domain. */
    private const VALID_NV = [1, 2, 3, 4];

    /** Komponen names in order. */
    private const KOMPONEN_NAMES = [
        'MUTU LULUSAN',
        'PROSES PEMBELAJARAN',
        'MUTU USTAZ',
        'MANAJEMEN PESANTREN',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new ScoreCalculationService;
    }

    // =========================================================================
    // Helpers / Generators
    // =========================================================================

    /**
     * Generate a random valid NV value (1–4).
     */
    private function randomNv(): int
    {
        return self::VALID_NV[random_int(0, 3)];
    }

    /**
     * Generate a random integer strictly outside 1–4.
     * Samples from [-100, 0] ∪ [5, 100].
     */
    private function randomInvalidNv(): int
    {
        if (random_int(0, 1) === 0) {
            return random_int(-100, 0);
        }

        return random_int(5, 100);
    }

    /**
     * Build a full set of 62 valid NV values structured for calculateAll().
     *
     * @return array{ik: array<string, array<int>>, ipr: array<int>}
     */
    private function buildRandomAllNvValues(): array
    {
        $ik = [];
        foreach (ScoreCalculationService::KOMPONEN_CONFIG as $name => $config) {
            $ik[$name] = array_map(fn () => $this->randomNv(), range(1, $config['butir_count']));
        }

        $ipr = array_map(fn () => $this->randomNv(), range(1, ScoreCalculationService::IPR_BUTIR_COUNT));

        return ['ik' => $ik, 'ipr' => $ipr];
    }

    /**
     * Compute Nilai_Akhir directly from raw NV values using the reference formula.
     * Mirrors the service's step-by-step rounding exactly:
     *   1. Each Skor_Komponen = round((Ci / Cmaks) × Bobot, 2)
     *   2. Total_Skor_IK      = round(sum of Skor_Komponen, 2)
     *   3. Skor_IPR           = round((Ci_IPR / 88) × 100, 2)
     *   4. Nilai_Akhir        = round((0.7 × Total_Skor_IK) + (0.3 × Skor_IPR), 2)
     *
     * @param  array{ik: array<string, array<int>>, ipr: array<int>}  $allNvValues
     */
    private function referenceNilaiAkhir(array $allNvValues): float
    {
        $skorKomponens = [];

        foreach (ScoreCalculationService::KOMPONEN_CONFIG as $name => $config) {
            $nvs = $allNvValues['ik'][$name];
            $ci = array_sum($nvs);
            $cmaks = $config['butir_count'] * 4;
            $bobot = $config['bobot'];
            // Round each Skor_Komponen to 2dp (same as service)
            $skorKomponens[] = round(($ci / $cmaks) * $bobot, 2);
        }

        $totalSkorIK = round(array_sum($skorKomponens), 2);

        $ciIpr = array_sum($allNvValues['ipr']);
        $skorIPR = round(($ciIpr / ScoreCalculationService::IPR_CMAKS) * 100, 2);

        return round((0.7 * $totalSkorIK) + (0.3 * $skorIPR), 2);
    }

    // =========================================================================
    // Property 2 — Score Range Validation
    //
    // For any integer value submitted as NA1, NA2, NK, or NV, the system SHALL
    // accept the value if and only if it is in {1, 2, 3, 4}.
    //
    // Tested via calculateSkorKomponen() which validates NV values.
    //
    // **Validates: Requirements 7.1, 7.2, 7.10, 9.2, 9.3**
    // =========================================================================

    /**
     * Property 2 — Valid values (1–4) are accepted by calculateSkorKomponen.
     *
     * Run 100+ iterations with random valid inputs.
     *
     * **Validates: Requirements 7.1, 7.2, 7.10, 9.2, 9.3**
     */
    public function test_property2_valid_nv_values_are_accepted(): void
    {
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            // Pick a random komponen
            $komponenName = self::KOMPONEN_NAMES[random_int(0, 3)];
            $config = ScoreCalculationService::KOMPONEN_CONFIG[$komponenName];
            $butirCount = $config['butir_count'];

            // Build array of valid NV values
            $nvValues = array_map(fn () => $this->randomNv(), range(1, $butirCount));

            // Must not throw
            $result = $this->svc->calculateSkorKomponen($nvValues, $komponenName);

            $this->assertIsFloat($result, "Iteration {$i}: calculateSkorKomponen must return float for valid inputs.");
        }
    }

    /**
     * Property 2 — Values outside 1–4 are rejected by calculateSkorKomponen.
     *
     * Run 100+ iterations with random invalid inputs.
     *
     * **Validates: Requirements 7.1, 7.2, 7.10, 9.2, 9.3**
     */
    public function test_property2_invalid_nv_values_are_rejected(): void
    {
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            $komponenName = self::KOMPONEN_NAMES[random_int(0, 3)];
            $config = ScoreCalculationService::KOMPONEN_CONFIG[$komponenName];
            $butirCount = $config['butir_count'];

            // Build array with one invalid NV value injected at a random position
            $nvValues = array_map(fn () => $this->randomNv(), range(1, $butirCount));
            $invalidPos = random_int(0, $butirCount - 1);
            $nvValues[$invalidPos] = $this->randomInvalidNv();

            $this->expectException(InvalidArgumentException::class);
            $this->svc->calculateSkorKomponen($nvValues, $komponenName);
        }
    }

    /**
     * Property 2 — Exhaustive: every value in {1,2,3,4} is accepted;
     * boundary values 0 and 5 are rejected.
     *
     * **Validates: Requirements 7.1, 7.2, 7.10, 9.2, 9.3**
     */
    public function test_property2_exhaustive_boundary_values(): void
    {
        $komponenName = 'MUTU LULUSAN';
        $butirCount = ScoreCalculationService::KOMPONEN_CONFIG[$komponenName]['butir_count'];

        // All valid values must be accepted
        foreach ([1, 2, 3, 4] as $validNv) {
            $nvValues = array_fill(0, $butirCount, $validNv);
            $result = $this->svc->calculateSkorKomponen($nvValues, $komponenName);
            $this->assertIsFloat($result, "Value {$validNv} should be accepted.");
        }

        // Boundary invalids: 0 and 5
        foreach ([0, 5] as $invalidNv) {
            $nvValues = array_fill(0, $butirCount, 1); // all valid
            $nvValues[0] = $invalidNv;                    // inject one invalid

            try {
                $this->svc->calculateSkorKomponen($nvValues, $komponenName);
                $this->fail("Value {$invalidNv} should have been rejected.");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('1–4', $e->getMessage());
            }
        }
    }

    // =========================================================================
    // Property 4 — Formula Correctness
    //
    // For any set of 62 valid NV values (each 1–4), Nilai_Akhir equals the
    // formula: (0.7 × Σ((Ci_k / Cmaks_k) × Bobot_k)) + (0.3 × (Ci_IPR / 88) × 100)
    // rounded to 2 decimal places.
    //
    // **Validates: Requirements 10.1, 10.3, 10.4, 10.5**
    // =========================================================================

    /**
     * Property 4 — Formula correctness: service output matches reference formula.
     *
     * Run 200 iterations with random valid inputs.
     *
     * **Validates: Requirements 10.1, 10.3, 10.4, 10.5**
     */
    public function test_property4_formula_correctness(): void
    {
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            $allNvValues = $this->buildRandomAllNvValues();

            $result = $this->svc->calculateAll($allNvValues);
            $expected = $this->referenceNilaiAkhir($allNvValues);

            $this->assertSame(
                $expected,
                $result['nilai_akhir'],
                "Iteration {$i}: Nilai_Akhir from service must match reference formula."
            );
        }
    }

    // =========================================================================
    // Property 5 — Score Calculation Range Invariant
    //
    // For any valid combination of 62 NV values (each 1–4), Nilai_Akhir is
    // within [0.00, 100.00].
    //
    // **Validates: Requirements 10.7**
    // =========================================================================

    /**
     * Property 5 — Range invariant: Nilai_Akhir is always in [0.00, 100.00].
     *
     * Run 200 iterations with random valid inputs.
     *
     * **Validates: Requirements 10.7**
     */
    public function test_property5_nilai_akhir_range_invariant(): void
    {
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            $allNvValues = $this->buildRandomAllNvValues();
            $result = $this->svc->calculateAll($allNvValues);
            $nilaiAkhir = $result['nilai_akhir'];

            $this->assertGreaterThanOrEqual(
                0.0,
                $nilaiAkhir,
                "Iteration {$i}: Nilai_Akhir must be >= 0.00, got {$nilaiAkhir}."
            );

            $this->assertLessThanOrEqual(
                100.0,
                $nilaiAkhir,
                "Iteration {$i}: Nilai_Akhir must be <= 100.00, got {$nilaiAkhir}."
            );
        }
    }

    /**
     * Property 5 — Boundary check: all-1 inputs produce minimum, all-4 inputs produce maximum.
     *
     * **Validates: Requirements 10.7**
     */
    public function test_property5_boundary_min_max(): void
    {
        // All NV = 1 → minimum score
        $allMin = [
            'ik' => [
                'MUTU LULUSAN' => array_fill(0, 8, 1),
                'PROSES PEMBELAJARAN' => array_fill(0, 10, 1),
                'MUTU USTAZ' => array_fill(0, 10, 1),
                'MANAJEMEN PESANTREN' => array_fill(0, 12, 1),
            ],
            'ipr' => array_fill(0, 22, 1),
        ];

        $minResult = $this->svc->calculateAll($allMin);
        $this->assertGreaterThanOrEqual(0.0, $minResult['nilai_akhir']);
        $this->assertLessThanOrEqual(100.0, $minResult['nilai_akhir']);

        // All NV = 4 → maximum score (should be 100.00)
        $allMax = [
            'ik' => [
                'MUTU LULUSAN' => array_fill(0, 8, 4),
                'PROSES PEMBELAJARAN' => array_fill(0, 10, 4),
                'MUTU USTAZ' => array_fill(0, 10, 4),
                'MANAJEMEN PESANTREN' => array_fill(0, 12, 4),
            ],
            'ipr' => array_fill(0, 22, 4),
        ];

        $maxResult = $this->svc->calculateAll($allMax);
        $this->assertSame(100.0, $maxResult['nilai_akhir'], 'All-4 inputs must produce Nilai_Akhir = 100.00.');
    }

    // =========================================================================
    // Property 6 — Score Calculation Idempotence
    //
    // Calculating with identical inputs produces identical Nilai_Akhir.
    //
    // **Validates: Requirements 10.8**
    // =========================================================================

    /**
     * Property 6 — Idempotence: same inputs always produce same Nilai_Akhir.
     *
     * Run 200 iterations with random valid inputs.
     *
     * **Validates: Requirements 10.8**
     */
    public function test_property6_idempotence(): void
    {
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            $allNvValues = $this->buildRandomAllNvValues();

            $result1 = $this->svc->calculateAll($allNvValues);
            $result2 = $this->svc->calculateAll($allNvValues);

            $this->assertSame(
                $result1['nilai_akhir'],
                $result2['nilai_akhir'],
                "Iteration {$i}: Identical inputs must produce identical Nilai_Akhir."
            );
        }
    }

    // =========================================================================
    // Property 7 — Peringkat Classification
    //
    // For any Nilai_Akhir in [0, 100]:
    //   A (≥86), B (≥71 and <86), C (<71)
    //
    // **Validates: Requirements 10.6**
    // =========================================================================

    /**
     * Property 7 — Peringkat classification: random float in [0, 100].
     *
     * Run 200 iterations with random Nilai_Akhir values.
     *
     * **Validates: Requirements 10.6**
     */
    public function test_property7_peringkat_classification_random(): void
    {
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random Nilai_Akhir in [0.00, 100.00] with 2dp precision
            $nilaiAkhir = round(random_int(0, 10000) / 100, 2);

            $peringkat = $this->svc->determinePeringkat($nilaiAkhir);

            if ($nilaiAkhir >= 86.0) {
                $this->assertSame('A', $peringkat, "Iteration {$i}: NA={$nilaiAkhir} should be Peringkat A.");
            } elseif ($nilaiAkhir >= 71.0) {
                $this->assertSame('B', $peringkat, "Iteration {$i}: NA={$nilaiAkhir} should be Peringkat B.");
            } else {
                $this->assertSame('C', $peringkat, "Iteration {$i}: NA={$nilaiAkhir} should be Peringkat C.");
            }
        }
    }

    /**
     * Property 7 — Peringkat classification: exact boundary values.
     *
     * **Validates: Requirements 10.6**
     */
    public function test_property7_peringkat_exact_boundaries(): void
    {
        $cases = [
            [0.0,   'C'],
            [70.99, 'C'],
            [71.0,  'B'],
            [85.99, 'B'],
            [86.0,  'A'],
            [100.0, 'A'],
        ];

        foreach ($cases as [$nilaiAkhir, $expected]) {
            $actual = $this->svc->determinePeringkat($nilaiAkhir);
            $this->assertSame(
                $expected,
                $actual,
                "Nilai_Akhir={$nilaiAkhir} should produce Peringkat {$expected}."
            );
        }
    }

    /**
     * Property 7 — Peringkat from calculateAll() matches determinePeringkat() directly.
     *
     * Run 200 iterations.
     *
     * **Validates: Requirements 10.6**
     */
    public function test_property7_peringkat_consistent_with_calculate_all(): void
    {
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            $allNvValues = $this->buildRandomAllNvValues();
            $result = $this->svc->calculateAll($allNvValues);

            $expectedPeringkat = $this->svc->determinePeringkat($result['nilai_akhir']);

            $this->assertSame(
                $expectedPeringkat,
                $result['peringkat'],
                "Iteration {$i}: Peringkat in calculateAll() must match determinePeringkat(Nilai_Akhir)."
            );
        }
    }

    // =========================================================================
    // Property 8 — Delta Calculation
    //
    // For any NA1, NA2 in {1,2,3,4}, Delta = |NA1 - NA2|.
    // Exhaustive check of all 16 combinations.
    //
    // **Validates: Requirements 7.12**
    // =========================================================================

    /**
     * Property 8 — Delta: exhaustive check of all 4×4 = 16 combinations.
     *
     * **Validates: Requirements 7.12**
     */
    public function test_property8_delta_exhaustive_all_16_combinations(): void
    {
        $checkedCount = 0;

        foreach (self::VALID_NV as $na1) {
            foreach (self::VALID_NV as $na2) {
                $expected = abs($na1 - $na2);
                $actual = $this->svc->calculateDelta($na1, $na2);

                $this->assertSame(
                    $expected,
                    $actual,
                    "calculateDelta({$na1}, {$na2}) should return {$expected}, got {$actual}."
                );

                $checkedCount++;
            }
        }

        // Sanity: exactly 16 combinations checked
        $this->assertSame(16, $checkedCount, 'Expected exactly 16 combinations (4×4) to be checked.');
    }

    /**
     * Property 8 — Delta: result is always in [0, 3] for valid inputs.
     *
     * **Validates: Requirements 7.12**
     */
    public function test_property8_delta_range_is_0_to_3(): void
    {
        foreach (self::VALID_NV as $na1) {
            foreach (self::VALID_NV as $na2) {
                $delta = $this->svc->calculateDelta($na1, $na2);

                $this->assertGreaterThanOrEqual(0, $delta, "Delta({$na1},{$na2}) must be >= 0.");
                $this->assertLessThanOrEqual(3, $delta, "Delta({$na1},{$na2}) must be <= 3.");
            }
        }
    }

    /**
     * Property 8 — Delta: symmetry — Delta(NA1, NA2) == Delta(NA2, NA1).
     *
     * Run 200 iterations.
     *
     * **Validates: Requirements 7.12**
     */
    public function test_property8_delta_symmetry(): void
    {
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            $na1 = $this->randomNv();
            $na2 = $this->randomNv();

            $this->assertSame(
                $this->svc->calculateDelta($na1, $na2),
                $this->svc->calculateDelta($na2, $na1),
                "Iteration {$i}: Delta must be symmetric — Delta({$na1},{$na2}) == Delta({$na2},{$na1})."
            );
        }
    }
}
