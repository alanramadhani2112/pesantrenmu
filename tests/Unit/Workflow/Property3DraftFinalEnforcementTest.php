<?php

namespace Tests\Unit\Workflow;

use App\Exceptions\ImmutableValueException;
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
use InvalidArgumentException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Property-Based Test: Property 3 — Draft/Final Mode Enforcement
 *
 * For any assessment value (NA1, NA2, NK, NV) saved with is_final=true,
 * subsequent modification SHALL be rejected.
 * For any value with is_final=false, modification SHALL be permitted.
 *
 * **Validates: Requirements 7.5, 7.6, 9.4, 9.6**
 *
 */
#[Group('akreditasi-workflow-redesign')]
class Property3DraftFinalEnforcementTest extends TestCase
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
     * Stores the mapping from butir index (1-62) to master_edpm_butirs.id.
     */
private function createMasterButirs(): void
    {
        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'TEST KOMPONEN'], ['ipr' => false]);

        for ($i = 1; $i <= 62; $i++) {
            $butir = MasterEdpmButir::firstOrCreate(
                ['nomor_butir' => "T.{$i}"],
                [
                    'komponen_id'      => $komponen->id,
                    'no_sk'            => (string) $i,
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
     * Create a scenario with akreditasi at status 2 (Pasca Visitasi),
     * two assessors, and an assessment record.
     */
private function createScoringScenario(): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        $asesor1User   = User::factory()->create(['role_id' => 2]);
        $asesor2User   = User::factory()->create(['role_id' => 2]);

        $asesor1 = Asesor::create([
            'user_id'           => $asesor1User->id,
            'nama_dengan_gelar' => 'Dr. Asesor Satu',
            'nama_tanpa_gelar'  => 'Asesor Satu',
        ]);

        $asesor2 = Asesor::create([
            'user_id'           => $asesor2User->id,
            'nama_dengan_gelar' => 'Dr. Asesor Dua',
            'nama_tanpa_gelar'  => 'Asesor Dua',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status'  => AkreditasiStateMachine::STATUS_PASCA_VISITASI, // 2
        ]);

        Assessment::create([
            'akreditasi_id'   => $akreditasi->id,
            'asesor_id'       => $asesor1->id,
            'tipe'            => 1,
            'tanggal_mulai'   => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        Assessment::create([
            'akreditasi_id'   => $akreditasi->id,
            'asesor_id'       => $asesor2->id,
            'tipe'            => 2,
            'tanggal_mulai'   => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        return [
            'akreditasi'      => $akreditasi,
            'asesor1UserId'   => $asesor1User->id,   // users.id (for service calls)
            'asesor2UserId'   => $asesor2User->id,   // users.id (for service calls)
            'asesor1Id'       => $asesor1->id,       // asesors.id (for DB queries)
            'asesor2Id'       => $asesor2->id,       // asesors.id (for DB queries)
            'pesantrenUserId' => $pesantrenUser->id,
        ];
    }

    // =========================================================================
    // Property 3 — Part A: Final NA1 cannot be modified
    // =========================================================================

    /**
     * Property 3 — NA1 Final immutability: for any NA1 value saved as Final,
     * a subsequent save attempt SHALL throw ImmutableValueException.
     *
     * Runs 100 iterations with random butir_id and values.
     *
     * **Validates: Requirements 7.5, 7.6**
     */
public function test_property3_final_na1_cannot_be_modified(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $scenario = $this->createScoringScenario();
            $akreditasiId  = $scenario['akreditasi']->id;
            $asesor1UserId = $scenario['asesor1UserId'];

            $butirIndex    = random_int(1, 62);
            $butirId       = $this->getButirId($butirIndex);
            $initialValue  = random_int(1, 4);
            $modifiedValue = random_int(1, 4);

            // Save as Final
            $this->scoringService->saveNA($akreditasiId, $asesor1UserId, $butirId, $initialValue, true);

            // Attempt to modify — must throw ImmutableValueException
            $exception = null;
            try {
                $this->scoringService->saveNA($akreditasiId, $asesor1UserId, $butirId, $modifiedValue, false);
            } catch (ImmutableValueException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: Modifying Final NA1 (butir={$butirId}) should throw ImmutableValueException"
            );

            // Verify value unchanged
            $record = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('asesor_id', $scenario['asesor1Id'])
                ->where('butir_id', $butirId)
                ->first();

            $this->assertEquals(
                $initialValue,
                $record->isian,
                "Iteration {$i}: NA1 value should remain {$initialValue} after rejected modification"
            );
            $this->assertTrue($record->is_final, "Iteration {$i}: is_final should remain true");
        }
    }

    // =========================================================================
    // Property 3 — Part B: Draft NA1 can be modified
    // =========================================================================

    /**
     * Property 3 — NA1 Draft mutability: for any NA1 value saved as Draft,
     * a subsequent save SHALL succeed and update the value.
     *
     * Runs 100 iterations with random butir_id and values.
     *
     * **Validates: Requirements 7.5**
     */
public function test_property3_draft_na1_can_be_modified(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $scenario = $this->createScoringScenario();
            $akreditasiId  = $scenario['akreditasi']->id;
            $asesor1UserId = $scenario['asesor1UserId'];

            $butirIndex    = random_int(1, 62);
            $butirId       = $this->getButirId($butirIndex);
            $initialValue  = random_int(1, 4);
            $modifiedValue = random_int(1, 4);

            // Save as Draft
            $this->scoringService->saveNA($akreditasiId, $asesor1UserId, $butirId, $initialValue, false);

            // Modify — must succeed
            $exception = null;
            try {
                $this->scoringService->saveNA($akreditasiId, $asesor1UserId, $butirId, $modifiedValue, false);
            } catch (\Throwable $e) {
                $exception = $e;
            }

            $this->assertNull(
                $exception,
                "Iteration {$i}: Modifying Draft NA1 (butir={$butirId}) should succeed, but threw: " .
                ($exception ? $exception->getMessage() : 'no exception')
            );

            // Verify value updated
            $record = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('asesor_id', $scenario['asesor1Id'])
                ->where('butir_id', $butirId)
                ->first();

            $this->assertEquals(
                $modifiedValue,
                $record->isian,
                "Iteration {$i}: NA1 value should be updated to {$modifiedValue}"
            );
            $this->assertFalse($record->is_final, "Iteration {$i}: is_final should remain false");
        }
    }

    // =========================================================================
    // Property 3 — Part C: Final NA2 cannot be modified
    // =========================================================================

    /**
     * Property 3 — NA2 Final immutability: for any NA2 value saved as Final,
     * a subsequent save attempt SHALL throw ImmutableValueException.
     *
     * Runs 100 iterations.
     *
     * **Validates: Requirements 7.5, 7.6**
     */
public function test_property3_final_na2_cannot_be_modified(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $scenario = $this->createScoringScenario();
            $akreditasiId  = $scenario['akreditasi']->id;
            $asesor2UserId = $scenario['asesor2UserId'];

            $butirIndex    = random_int(1, 62);
            $butirId       = $this->getButirId($butirIndex);
            $initialValue  = random_int(1, 4);
            $modifiedValue = random_int(1, 4);

            // Save NA2 as Final
            $this->scoringService->saveNA($akreditasiId, $asesor2UserId, $butirId, $initialValue, true);

            // Attempt to modify — must throw ImmutableValueException
            $exception = null;
            try {
                $this->scoringService->saveNA($akreditasiId, $asesor2UserId, $butirId, $modifiedValue, false);
            } catch (ImmutableValueException $e) {
                $exception = $e;
            }

            $this->assertNotNull(
                $exception,
                "Iteration {$i}: Modifying Final NA2 (butir={$butirId}) should throw ImmutableValueException"
            );

            // Verify value unchanged
            $record = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('asesor_id', $scenario['asesor2Id'])
                ->where('butir_id', $butirId)
                ->first();

            $this->assertEquals($initialValue, $record->isian, "Iteration {$i}: NA2 value should remain unchanged");
            $this->assertTrue($record->is_final, "Iteration {$i}: is_final should remain true");
        }
    }

    // =========================================================================
    // Property 3 — Part D: Biconditional — Final rejects, Draft permits
    // =========================================================================

    /**
     * Property 3 — Biconditional: modification is rejected if and only if
     * is_final=true; modification is permitted if and only if is_final=false.
     *
     * Randomly chooses whether to save as Final or Draft, then verifies
     * the subsequent modification outcome matches the expected behavior.
     *
     * Runs 100 iterations.
     *
     * **Validates: Requirements 7.5, 7.6**
     */
public function test_property3_biconditional_final_rejects_draft_permits(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $scenario = $this->createScoringScenario();
            $akreditasiId  = $scenario['akreditasi']->id;
            $asesor1UserId = $scenario['asesor1UserId'];

            $butirIndex   = random_int(1, 62);
            $butirId      = $this->getButirId($butirIndex);
            $initialValue = random_int(1, 4);
            $newValue     = random_int(1, 4);
            $saveAsFinal  = (bool) random_int(0, 1);

            // Save with chosen finality
            $this->scoringService->saveNA($akreditasiId, $asesor1UserId, $butirId, $initialValue, $saveAsFinal);

            // Attempt modification
            $exception = null;
            try {
                $this->scoringService->saveNA($akreditasiId, $asesor1UserId, $butirId, $newValue, false);
            } catch (ImmutableValueException $e) {
                $exception = $e;
            }

            if ($saveAsFinal) {
                $this->assertNotNull(
                    $exception,
                    "Iteration {$i}: Final value (butir={$butirId}) modification should be rejected"
                );
            } else {
                $this->assertNull(
                    $exception,
                    "Iteration {$i}: Draft value (butir={$butirId}) modification should be permitted, but threw: " .
                    ($exception ? $exception->getMessage() : 'no exception')
                );
            }
        }
    }

    // =========================================================================
    // Property 3 — Part E: Draft can be promoted to Final (one-way)
    // =========================================================================

    /**
     * Property 3 — Draft to Final promotion: a Draft value can be saved as
     * Final (promotion is allowed), but once Final it cannot be changed back.
     *
     * Runs 100 iterations.
     *
     * **Validates: Requirements 7.5, 7.6**
     */
public function test_property3_draft_can_be_promoted_to_final(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $scenario = $this->createScoringScenario();
            $akreditasiId  = $scenario['akreditasi']->id;
            $asesor1UserId = $scenario['asesor1UserId'];

            $butirIndex   = random_int(1, 62);
            $butirId      = $this->getButirId($butirIndex);
            $draftValue   = random_int(1, 4);
            $finalValue   = random_int(1, 4);

            // Step 1: Save as Draft
            $this->scoringService->saveNA($akreditasiId, $asesor1UserId, $butirId, $draftValue, false);

            // Step 2: Promote to Final — should succeed
            $exception = null;
            try {
                $this->scoringService->saveNA($akreditasiId, $asesor1UserId, $butirId, $finalValue, true);
            } catch (\Throwable $e) {
                $exception = $e;
            }

            $this->assertNull(
                $exception,
                "Iteration {$i}: Promoting Draft to Final should succeed, but threw: " .
                ($exception ? $exception->getMessage() : 'no exception')
            );

            // Step 3: Attempt to modify Final — should fail
            $exception2 = null;
            try {
                $this->scoringService->saveNA($akreditasiId, $asesor1UserId, $butirId, random_int(1, 4), false);
            } catch (ImmutableValueException $e) {
                $exception2 = $e;
            }

            $this->assertNotNull(
                $exception2,
                "Iteration {$i}: Modifying Final value after promotion should throw ImmutableValueException"
            );

            // Verify final value is stored
            $record = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('asesor_id', $scenario['asesor1Id'])
                ->where('butir_id', $butirId)
                ->first();

            $this->assertEquals($finalValue, $record->isian, "Iteration {$i}: Final value should be {$finalValue}");
            $this->assertTrue($record->is_final, "Iteration {$i}: is_final should be true after promotion");
        }
    }
}
