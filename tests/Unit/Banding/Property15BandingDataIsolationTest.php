<?php

namespace Tests\Unit\Banding;

use App\Models\Akreditasi;
use App\Models\AkreditasiBandingEdpm;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\Banding;
use App\Models\User;
use App\Services\BandingService;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Property-Based Test: Property 15 — Banding Data Isolation
 *
 * For any scoring input during post-banding assessment (status 2 with an
 * accepted banding), data SHALL be stored in banding tables
 * (akreditasi_banding_edpms) and the original assessment data in
 * akreditasi_edpms SHALL remain unchanged.
 *
 * **Validates: Requirements 14.7, 14.8**
 */
#[Group('akreditasi-workflow-redesign')]
class Property15BandingDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected BandingService $bandingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->bandingService = app(BandingService::class);
    }

    // =========================================================================
    // Property 15 — Main property test (≥100 iterations)
    // =========================================================================

    /**
     * Property 15 — Banding Data Isolation:
     *
     * For any scoring input (NA1, NA2, NK, NV, catatan) during post-banding
     * assessment (status 2 with an accepted banding), data SHALL be stored in
     * akreditasi_banding_edpms and the original akreditasi_edpms SHALL remain
     * unchanged.
     *
     * **Validates: Requirements 14.7, 14.8**
     */
    public function test_property15_scoring_stored_in_banding_tables_not_original(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $setup = $this->createPostBandingSetup();
            $akreditasiId = $setup['akreditasi']->id;
            $bandingId = $setup['banding']->id;
            $asesorId = $setup['asesorUser']->id;

            // Record original akreditasi_edpms state
            $originalEdpmCount = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)->count();
            $originalEdpmData = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->get()
                ->keyBy('id')
                ->toArray();

            // Generate random scoring data
            $butirId = random_int(1, 62);
            $isian = random_int(1, 4);
            $nk = random_int(1, 4);
            $nv = random_int(1, 4);
            $catatanButir = 'Catatan butir '.$butirId.' - '.str_repeat('x', random_int(5, 50));
            $isFinal = (bool) random_int(0, 1);

            // Verify shouldUseBandingTables returns true for this setup
            $this->assertTrue(
                $this->bandingService->shouldUseBandingTables($akreditasiId),
                "Iteration {$i}: shouldUseBandingTables should return true for status 2 with accepted banding"
            );

            // Store scoring data via BandingService
            $bandingEdpm = $this->bandingService->storeBandingEdpm($akreditasiId, $bandingId, [
                'asesor_id' => $asesorId,
                'butir_id' => $butirId,
                'isian' => $isian,
                'nk' => $nk,
                'nv' => $nv,
                'catatan_butir' => $catatanButir,
                'is_final' => $isFinal,
            ]);

            // Assert: data was stored in akreditasi_banding_edpms
            $this->assertNotNull(
                $bandingEdpm,
                "Iteration {$i}: storeBandingEdpm should return a record"
            );

            $this->assertDatabaseHas('akreditasi_banding_edpms', [
                'akreditasi_id' => $akreditasiId,
                'banding_id' => $bandingId,
                'asesor_id' => $asesorId,
                'butir_id' => $butirId,
                'isian' => $isian,
            ]);

            // Assert: original akreditasi_edpms is UNCHANGED
            $currentEdpmCount = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)->count();
            $this->assertSame(
                $originalEdpmCount,
                $currentEdpmCount,
                "Iteration {$i}: akreditasi_edpms count should not change after banding scoring"
            );

            // Verify each original record is unchanged
            $currentEdpmData = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->get()
                ->keyBy('id')
                ->toArray();

            foreach ($originalEdpmData as $id => $original) {
                $this->assertArrayHasKey(
                    $id,
                    $currentEdpmData,
                    "Iteration {$i}: Original EDPM record #{$id} should still exist"
                );
                $this->assertSame(
                    $original['isian'],
                    $currentEdpmData[$id]['isian'],
                    "Iteration {$i}: Original EDPM record #{$id} isian should be unchanged"
                );
                $this->assertSame(
                    $original['nk'],
                    $currentEdpmData[$id]['nk'],
                    "Iteration {$i}: Original EDPM record #{$id} nk should be unchanged"
                );
                $this->assertSame(
                    $original['nv'],
                    $currentEdpmData[$id]['nv'],
                    "Iteration {$i}: Original EDPM record #{$id} nv should be unchanged"
                );
            }
        }
    }

    /**
     * Property 15 — Catatan isolation:
     *
     * For any catatan input during post-banding assessment, data SHALL be
     * stored in akreditasi_banding_edpm_catatans and the original
     * akreditasi_edpm_catatans SHALL remain unchanged.
     *
     * **Validates: Requirements 14.7, 14.8**
     */
    public function test_property15_catatan_stored_in_banding_tables_not_original(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $setup = $this->createPostBandingSetup();
            $akreditasiId = $setup['akreditasi']->id;
            $bandingId = $setup['banding']->id;

            // Record original catatan count
            $originalCatatanCount = AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasiId)->count();

            // Generate random catatan data
            $komponenId = random_int(1, 4);
            $catatan = 'Catatan komponen '.$komponenId.' - '.str_repeat('y', random_int(5, 50));
            $rekomendasi = 'Rekomendasi - '.str_repeat('z', random_int(5, 30));

            // Store catatan via BandingService
            $bandingCatatan = $this->bandingService->storeBandingEdpmCatatan($akreditasiId, $bandingId, [
                'komponen_id' => $komponenId,
                'catatan' => $catatan,
                'rekomendasi' => $rekomendasi,
            ]);

            // Assert: data was stored in akreditasi_banding_edpm_catatans
            $this->assertNotNull(
                $bandingCatatan,
                "Iteration {$i}: storeBandingEdpmCatatan should return a record"
            );

            $this->assertDatabaseHas('akreditasi_banding_edpm_catatans', [
                'akreditasi_id' => $akreditasiId,
                'banding_id' => $bandingId,
                'komponen_id' => $komponenId,
            ]);

            // Assert: original akreditasi_edpm_catatans is UNCHANGED
            $currentCatatanCount = AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasiId)->count();
            $this->assertSame(
                $originalCatatanCount,
                $currentCatatanCount,
                "Iteration {$i}: akreditasi_edpm_catatans count should not change after banding catatan storage"
            );
        }
    }

    /**
     * Property 15 — shouldUseBandingTables returns false without accepted banding:
     *
     * When akreditasi is at status 2 but has NO accepted banding, scoring
     * should NOT use banding tables.
     *
     * **Validates: Requirements 14.7**
     */
    public function test_property15_should_not_use_banding_tables_without_accepted_banding(): void
    {
        $iterations = 100;

        $bandingStatuses = ['pending', 'under_review', 'rejected'];

        for ($i = 0; $i < $iterations; $i++) {
            $pesantrenUser = User::factory()->create(['role_id' => 3]);

            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => AkreditasiStateMachine::STATUS_PASCA_VISITASI,
            ]);

            // Optionally create a non-accepted banding
            $hasBanding = (bool) random_int(0, 1);
            if ($hasBanding) {
                $status = $bandingStatuses[array_rand($bandingStatuses)];
                Banding::create([
                    'akreditasi_id' => $akreditasi->id,
                    'user_id' => $pesantrenUser->id,
                    'status' => $status,
                    'alasan' => 'Banding dengan status '.$status,
                ]);
            }

            $result = $this->bandingService->shouldUseBandingTables($akreditasi->id);

            $this->assertFalse(
                $result,
                "Iteration {$i}: shouldUseBandingTables should return false when no accepted banding exists ".
                '(hasBanding: '.($hasBanding ? 'yes' : 'no').')'
            );
        }
    }

    /**
     * Property 15 — shouldUseBandingTables returns false for non-status-2 akreditasi:
     *
     * When akreditasi is NOT at status 2, shouldUseBandingTables SHALL return
     * false even if an accepted banding exists.
     *
     * **Validates: Requirements 14.7**
     */
    public function test_property15_should_not_use_banding_tables_for_non_status_2(): void
    {
        $iterations = 100;

        // All statuses except 2 (Pasca Visitasi)
        $nonStatus2 = [-2, -1, 0, 1, 3, 4, 5, 6];

        for ($i = 0; $i < $iterations; $i++) {
            $status = $nonStatus2[array_rand($nonStatus2)];

            $pesantrenUser = User::factory()->create(['role_id' => 3]);

            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => $status,
            ]);

            // Create an accepted banding
            Banding::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $pesantrenUser->id,
                'status' => 'accepted',
                'alasan' => 'Banding diterima.',
                'keputusan' => 'Diterima.',
                'decided_at' => now(),
            ]);

            $result = $this->bandingService->shouldUseBandingTables($akreditasi->id);

            $this->assertFalse(
                $result,
                "Iteration {$i}: shouldUseBandingTables should return false for status {$status} ".
                '(only status 2 with accepted banding should return true)'
            );
        }
    }

    /**
     * Property 15 — shouldUseBandingTables returns true for status 2 with accepted banding:
     *
     * When akreditasi is at status 2 AND has an accepted banding,
     * shouldUseBandingTables SHALL return true.
     *
     * **Validates: Requirements 14.7**
     */
    public function test_property15_should_use_banding_tables_for_status_2_with_accepted_banding(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $setup = $this->createPostBandingSetup();
            $akreditasiId = $setup['akreditasi']->id;

            $result = $this->bandingService->shouldUseBandingTables($akreditasiId);

            $this->assertTrue(
                $result,
                "Iteration {$i}: shouldUseBandingTables should return true for status 2 with accepted banding"
            );
        }
    }

    /**
     * Property 15 — Banding EDPM data is isolated per banding_id:
     *
     * Multiple banding scoring entries for different butir_ids are stored
     * independently in akreditasi_banding_edpms without affecting each other
     * or the original akreditasi_edpms.
     *
     * **Validates: Requirements 14.7, 14.8**
     */
    public function test_property15_multiple_banding_edpm_entries_are_isolated(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $setup = $this->createPostBandingSetup();
            $akreditasiId = $setup['akreditasi']->id;
            $bandingId = $setup['banding']->id;
            $asesorId = $setup['asesorUser']->id;

            // Store multiple scoring entries for different butir_ids
            $butirCount = random_int(2, 10);
            $butirIds = array_unique(array_map(fn () => random_int(1, 62), range(1, $butirCount * 2)));
            $butirIds = array_slice($butirIds, 0, $butirCount);

            $storedData = [];
            foreach ($butirIds as $butirId) {
                $isian = random_int(1, 4);
                $this->bandingService->storeBandingEdpm($akreditasiId, $bandingId, [
                    'asesor_id' => $asesorId,
                    'butir_id' => $butirId,
                    'isian' => $isian,
                ]);
                $storedData[$butirId] = $isian;
            }

            // Verify each entry is stored correctly in banding tables
            foreach ($storedData as $butirId => $isian) {
                $this->assertDatabaseHas('akreditasi_banding_edpms', [
                    'akreditasi_id' => $akreditasiId,
                    'banding_id' => $bandingId,
                    'asesor_id' => $asesorId,
                    'butir_id' => $butirId,
                    'isian' => $isian,
                ]);
            }

            // Verify original akreditasi_edpms has no new entries
            $this->assertSame(
                0,
                AkreditasiEdpm::where('akreditasi_id', $akreditasiId)->count(),
                "Iteration {$i}: No entries should be in akreditasi_edpms after banding scoring"
            );

            // Verify banding table has exactly the right number of entries
            $bandingEdpmCount = AkreditasiBandingEdpm::where('akreditasi_id', $akreditasiId)
                ->where('banding_id', $bandingId)
                ->count();
            $this->assertSame(
                count($butirIds),
                $bandingEdpmCount,
                "Iteration {$i}: akreditasi_banding_edpms should have exactly {$butirCount} entries"
            );
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create an akreditasi at status 2 (Pasca Visitasi) with an accepted banding.
     * This represents the post-banding assessment scenario.
     */
    private function createPostBandingSetup(): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        $asesorUser = User::factory()->create(['role_id' => 2]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_PASCA_VISITASI,
        ]);

        // Create an accepted banding
        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'accepted',
            'alasan' => 'Banding diterima untuk post-banding assessment.',
            'keputusan' => 'Diterima.',
            'decided_at' => now()->subDays(1),
        ]);

        return [
            'pesantrenUser' => $pesantrenUser,
            'asesorUser' => $asesorUser,
            'akreditasi' => $akreditasi,
            'banding' => $banding,
        ];
    }
}
