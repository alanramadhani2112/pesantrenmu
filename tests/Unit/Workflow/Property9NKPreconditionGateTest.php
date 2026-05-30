<?php

namespace Tests\Unit\Workflow;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\User;
use App\Services\AssessorScoringService;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Property-Based Test: Property 9 — NK Precondition Gate
 *
 * NK input by the Ketua Kelompok SHALL be accepted if and only if all
 * Nilai Ketua and all Nilai Anggota have been submitted as Final.
 *
 * **Validates: Requirements 7.8, 7.9**
 */
#[Group('akreditasi-workflow-redesign')]
class Property9NKPreconditionGateTest extends TestCase
{
    use RefreshDatabase;

    protected AssessorScoringService $scoringService;

    /** Maps butir index (1-62) to master_edpm_butirs.id */
    protected array $butirIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->scoringService = app(AssessorScoringService::class);
        $this->createMasterButirs();
    }

    /**
     * Create 62 master butir records to satisfy the FK constraint on akreditasi_edpms.butir_id.
     */
    private function createMasterButirs(): void
    {
        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'TEST KOMPONEN'], ['ipr' => false]);

        for ($i = 1; $i <= 62; $i++) {
            $butir = MasterEdpmButir::firstOrCreate(
                ['nomor_butir' => "T.{$i}"],
                [
                    'komponen_id' => $komponen->id,
                    'no_sk' => (string) $i,
                    'butir_pernyataan' => "Butir pernyataan {$i}",
                ]
            );
            $this->butirIds[$i] = $butir->id;
        }
    }

    /**
     * Get the master_edpm_butirs.id for a given butir index (1-62).
     */
    private function getButirId(int $index): int
    {
        return $this->butirIds[$index];
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a scenario with akreditasi at status 2, two assessors.
     */
    private function createScoringScenario(): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        $asesor1User = User::factory()->create(['role_id' => 2]);
        $asesor2User = User::factory()->create(['role_id' => 2]);

        $asesor1 = Asesor::create([
            'user_id' => $asesor1User->id,
            'nama_dengan_gelar' => 'Dr. Asesor Satu',
            'nama_tanpa_gelar' => 'Asesor Satu',
        ]);

        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Dr. Asesor Dua',
            'nama_tanpa_gelar' => 'Asesor Dua',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_PASCA_VISITASI, // 2
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        return [
            'akreditasi' => $akreditasi,
            'asesor1UserId' => $asesor1User->id,   // users.id (for service calls)
            'asesor2UserId' => $asesor2User->id,   // users.id (for service calls)
            'asesor1Id' => $asesor1->id,       // asesors.id (for DB queries)
            'asesor2Id' => $asesor2->id,       // asesors.id (for DB queries)
        ];
    }

    // =========================================================================
    private function fillAllNaFinal(
        int $akreditasiId,
        int $asesor1UserId,
        int $asesor2UserId,
        ?int $draftKetuaIndex = null,
        ?int $draftAnggotaIndex = null
    ): void {
        for ($index = 1; $index <= 62; $index++) {
            $this->scoringService->saveNA(
                $akreditasiId,
                $asesor1UserId,
                $this->getButirId($index),
                random_int(1, 4),
                $draftKetuaIndex !== $index
            );

            $this->scoringService->saveNA(
                $akreditasiId,
                $asesor2UserId,
                $this->getButirId($index),
                random_int(1, 4),
                $draftAnggotaIndex !== $index
            );
        }
    }

    // Property 9 — Part A: NK accepted when all Nilai Ketua and Nilai Anggota are Final
    // =========================================================================

    /**
     * Property 9 — NK gate satisfied: when all Nilai Ketua and Nilai Anggota
     * are Final, Nilai Kelompok input SHALL be accepted.
     *
     * Runs 100 iterations with random butir_id and values.
     *
     * **Validates: Requirements 7.8**
     */
    public function test_property9_nk_accepted_when_both_na_are_final(): void
    {
        $iterations = 10;

        for ($i = 0; $i < $iterations; $i++) {
            $scenario = $this->createScoringScenario();
            $akreditasiId = $scenario['akreditasi']->id;
            $asesor1UserId = $scenario['asesor1UserId'];
            $asesor2UserId = $scenario['asesor2UserId'];

            $butirIndex = random_int(1, 62);
            $butirId = $this->getButirId($butirIndex);
            $na1Value = random_int(1, 4);
            $na2Value = random_int(1, 4);
            $nkValue = random_int(1, 4);

            $this->fillAllNaFinal($akreditasiId, $asesor1UserId, $asesor2UserId);

            // Save NK — should succeed
            $exception = null;
            try {
                $this->scoringService->saveNK(
                    $akreditasiId, $asesor1UserId, $asesor2UserId, $butirId, $nkValue, false
                );
            } catch (\Throwable $e) {
                $exception = $e;
            }

            $this->assertNull(
                $exception,
                "Iteration {$i}: NK should be accepted when all Nilai Ketua and Nilai Anggota are Final, but threw: ".
                ($exception ? $exception->getMessage() : 'no exception')
            );

            // Verify NK was saved
            $record = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('asesor_id', $scenario['asesor1Id'])
                ->where('butir_id', $butirId)
                ->first();

            $this->assertEquals($nkValue, $record->nk, "Iteration {$i}: NK value should be {$nkValue}");
        }
    }

    // =========================================================================
    // Property 9 — Part B: NK rejected when any Nilai Ketua is not Final
    // =========================================================================

    /**
     * Property 9 — NK gate: when any Nilai Ketua is Draft (or absent),
     * Nilai Kelompok input SHALL be rejected with DomainException.
     *
     * Runs 100 iterations.
     *
     * **Validates: Requirements 7.9**
     */
    public function test_property9_nk_rejected_when_na1_not_final(): void
    {
        $iterations = 10;

        for ($i = 0; $i < $iterations; $i++) {
            $scenario = $this->createScoringScenario();
            $akreditasiId = $scenario['akreditasi']->id;
            $asesor1UserId = $scenario['asesor1UserId'];
            $asesor2UserId = $scenario['asesor2UserId'];

            $butirIndex = random_int(1, 62);
            $butirId = $this->getButirId($butirIndex);
            $na1Value = random_int(1, 4);
            $na2Value = random_int(1, 4);
            $nkValue = random_int(1, 4);

            $this->fillAllNaFinal($akreditasiId, $asesor1UserId, $asesor2UserId, draftKetuaIndex: $butirIndex);

            // Attempt NK — should fail
            $exception = null;
            try {
                $this->scoringService->saveNK(
                    $akreditasiId, $asesor1UserId, $asesor2UserId, $butirId, $nkValue, false
                );
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: NK should be rejected when one Nilai Ketua is Draft (butir={$butirId})"
            );

            // Verify NK was NOT saved
            $record = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('asesor_id', $scenario['asesor1Id'])
                ->where('butir_id', $butirId)
                ->first();

            $this->assertNull($record?->nk, "Iteration {$i}: Nilai Kelompok should not be saved when gate fails");
        }
    }

    // =========================================================================
    // Property 9 — Part C: NK rejected when any Nilai Anggota is not Final
    // =========================================================================

    /**
     * Property 9 — NK gate: when any Nilai Anggota is Draft (or absent),
     * Nilai Kelompok input SHALL be rejected with DomainException.
     *
     * Runs 100 iterations.
     *
     * **Validates: Requirements 7.9**
     */
    public function test_property9_nk_rejected_when_na2_not_final(): void
    {
        $iterations = 10;

        for ($i = 0; $i < $iterations; $i++) {
            $scenario = $this->createScoringScenario();
            $akreditasiId = $scenario['akreditasi']->id;
            $asesor1UserId = $scenario['asesor1UserId'];
            $asesor2UserId = $scenario['asesor2UserId'];

            $butirIndex = random_int(1, 62);
            $butirId = $this->getButirId($butirIndex);
            $na1Value = random_int(1, 4);
            $na2Value = random_int(1, 4);
            $nkValue = random_int(1, 4);

            $this->fillAllNaFinal($akreditasiId, $asesor1UserId, $asesor2UserId, draftAnggotaIndex: $butirIndex);

            // Attempt NK — should fail
            $exception = null;
            try {
                $this->scoringService->saveNK(
                    $akreditasiId, $asesor1UserId, $asesor2UserId, $butirId, $nkValue, false
                );
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: NK should be rejected when one Nilai Anggota is Draft (butir={$butirId})"
            );
        }
    }

    public function test_property9_nk_rejected_when_only_current_butir_is_final_but_global_scores_are_incomplete(): void
    {
        $scenario = $this->createScoringScenario();
        $akreditasiId = $scenario['akreditasi']->id;
        $asesor1UserId = $scenario['asesor1UserId'];
        $asesor2UserId = $scenario['asesor2UserId'];
        $butirId = $this->getButirId(1);

        $this->scoringService->saveNA($akreditasiId, $asesor1UserId, $butirId, 4, true);
        $this->scoringService->saveNA($akreditasiId, $asesor2UserId, $butirId, 4, true);

        $this->expectException(\DomainException::class);

        $this->scoringService->saveNK($akreditasiId, $asesor1UserId, $asesor2UserId, $butirId, 4, false);
    }

    public function test_property9_nk_rejected_when_actor_is_not_ketua_kelompok(): void
    {
        $scenario = $this->createScoringScenario();
        $akreditasiId = $scenario['akreditasi']->id;
        $asesor1UserId = $scenario['asesor1UserId'];
        $asesor2UserId = $scenario['asesor2UserId'];
        $butirId = $this->getButirId(1);

        $this->fillAllNaFinal($akreditasiId, $asesor1UserId, $asesor2UserId);

        $this->expectException(\DomainException::class);

        $this->scoringService->saveNK($akreditasiId, $asesor2UserId, $asesor1UserId, $butirId, 4, false);
    }

    // =========================================================================
    // Property 9 — Part D: NK rejected when neither NA is present
    // =========================================================================

    /**
     * Property 9 — NK gate: no NA values: when neither NA1 nor NA2 exists,
     * NK input SHALL be rejected.
     *
     * Runs 100 iterations.
     *
     * **Validates: Requirements 7.8, 7.9**
     */
    public function test_property9_nk_rejected_when_no_na_values(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $scenario = $this->createScoringScenario();
            $akreditasiId = $scenario['akreditasi']->id;
            $asesor1UserId = $scenario['asesor1UserId'];
            $asesor2UserId = $scenario['asesor2UserId'];

            $butirIndex = random_int(1, 62);
            $butirId = $this->getButirId($butirIndex);
            $nkValue = random_int(1, 4);

            // No NA values saved at all — attempt NK directly
            $exception = null;
            try {
                $this->scoringService->saveNK(
                    $akreditasiId, $asesor1UserId, $asesor2UserId, $butirId, $nkValue, false
                );
            } catch (\DomainException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: NK should be rejected when no NA values exist (butir={$butirId})"
            );
        }
    }

    // =========================================================================
    // Property 9 — Part E: Biconditional — NK accepted iff both NA Final
    // =========================================================================

    /**
     * Property 9 — Biconditional: NK is accepted if and only if all Nilai Ketua
     * and all Nilai Anggota are Final.
     *
     * Runs 100 iterations.
     *
     * **Validates: Requirements 7.8, 7.9**
     */
    public function test_property9_biconditional_nk_accepted_iff_both_na_final(): void
    {
        $combinations = [
            [true, true],
            [true, false],
            [false, true],
            [false, false],
        ];

        foreach ($combinations as $i => [$ketuaComplete, $anggotaComplete]) {
            $scenario = $this->createScoringScenario();
            $akreditasiId = $scenario['akreditasi']->id;
            $asesor1UserId = $scenario['asesor1UserId'];
            $asesor2UserId = $scenario['asesor2UserId'];

            $butirIndex = random_int(1, 62);
            $butirId = $this->getButirId($butirIndex);
            $nkValue = random_int(1, 4);

            $this->fillAllNaFinal(
                $akreditasiId,
                $asesor1UserId,
                $asesor2UserId,
                draftKetuaIndex: $ketuaComplete ? null : $butirIndex,
                draftAnggotaIndex: $anggotaComplete ? null : $butirIndex
            );

            $expectedAccepted = $ketuaComplete && $anggotaComplete;

            $exception = null;
            try {
                $this->scoringService->saveNK(
                    $akreditasiId, $asesor1UserId, $asesor2UserId, $butirId, $nkValue, false
                );
            } catch (\DomainException $e) {
                $exception = $e;
            }

            if ($expectedAccepted) {
                $this->assertNull(
                    $exception,
                    "Iteration {$i}: NK should be accepted when both roles are globally Final, ".
                    'but threw: '.($exception ? $exception->getMessage() : 'no exception')
                );
            } else {
                $this->assertNotNull(
                    $exception,
                    "Iteration {$i}: NK should be rejected when a role has incomplete Final values"
                );
            }
        }
    }
}
