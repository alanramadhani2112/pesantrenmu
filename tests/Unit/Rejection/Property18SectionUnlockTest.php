<?php

namespace Tests\Unit\Rejection;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\RejectionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Property-Based Test: Property 18 — Rejection-Based Section Unlock
 *
 * For any document rejection at status 4 specifying a set of sections,
 * only those specific sections SHALL become editable for Pesantren,
 * and all other sections SHALL remain locked.
 *
 * **Validates: Requirements 4.4, 4.6**
 */
#[Group('akreditasi-workflow-redesign')]
class Property18SectionUnlockTest extends TestCase
{
    use RefreshDatabase;

    protected RejectionService $rejectionService;

    /** All possible section identifiers that can appear in a rejection. */
    private array $allSections = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->rejectionService = app(RejectionService::class);

        // Seed EDPM komponen and butirs for testing
        $this->seedEdpmData();

        // Build the full list of valid section identifiers
        $this->allSections = $this->buildAllSections();
    }

    // =========================================================================
    // Property 18 — Main property test (≥100 iterations)
    // =========================================================================

    /**
     * Property 18 — Section Unlock Exactness:
     *
     * For any randomly chosen subset S of valid section identifiers,
     * after createDocumentRejection with items=S:
     *   - isSectionUnlocked(section) === true  for every section ∈ S
     *   - isSectionUnlocked(section) === false for every section ∉ S
     *   - getUnlockedSections() returns exactly S (same elements, any order)
     *
     * **Validates: Requirements 4.4, 4.6**
     */
    public function test_property18_only_rejected_sections_become_unlocked(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $setup = $this->createAsesor1SetupAtStatus4();
            $akreditasiId = $setup['akreditasi']->id;
            $asesor1UserId = $setup['asesorUser']->id;

            // Generate a random non-empty subset of sections to reject
            $subsetSize = random_int(1, max(1, count($this->allSections) - 1));
            $rejectedSections = $this->randomSubset($this->allSections, $subsetSize);
            $notRejectedSections = array_values(array_diff($this->allSections, $rejectedSections));

            $explanation = $this->randomExplanation(10, 200);

            // Act: create document rejection
            $result = $this->rejectionService->createDocumentRejection(
                $akreditasiId,
                $asesor1UserId,
                $rejectedSections,
                $explanation
            );

            $this->assertTrue(
                $result['success'],
                "Iteration {$i}: createDocumentRejection should succeed. Error: ".($result['error'] ?? 'none')
            );

            // Assert: every rejected section IS unlocked
            foreach ($rejectedSections as $section) {
                $this->assertTrue(
                    $this->rejectionService->isSectionUnlocked($akreditasiId, $section),
                    "Iteration {$i}: Section '{$section}' should be unlocked (it was rejected)"
                );
            }

            // Assert: every non-rejected section is NOT unlocked
            foreach ($notRejectedSections as $section) {
                $this->assertFalse(
                    $this->rejectionService->isSectionUnlocked($akreditasiId, $section),
                    "Iteration {$i}: Section '{$section}' should NOT be unlocked (it was not rejected)"
                );
            }

            // Assert: getUnlockedSections returns exactly the rejected set
            $unlocked = $this->rejectionService->getUnlockedSections($akreditasiId);
            sort($unlocked);
            $sortedRejected = $rejectedSections;
            sort($sortedRejected);
            $this->assertEquals(
                $sortedRejected,
                $unlocked,
                "Iteration {$i}: getUnlockedSections must return exactly the rejected sections"
            );
        }
    }

    /**
     * Property 18 — Single-section unlock:
     *
     * When exactly one section is rejected, only that one section is unlocked.
     * All other sections remain locked.
     *
     * **Validates: Requirements 4.4**
     */
    public function test_property18_single_section_unlock_exactness(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $setup = $this->createAsesor1SetupAtStatus4();
            $akreditasiId = $setup['akreditasi']->id;
            $asesor1UserId = $setup['asesorUser']->id;

            // Pick exactly one random section
            $targetSection = $this->allSections[random_int(0, count($this->allSections) - 1)];
            $otherSections = array_values(array_filter(
                $this->allSections,
                fn ($s) => $s !== $targetSection
            ));

            $explanation = $this->randomExplanation(10, 200);

            $result = $this->rejectionService->createDocumentRejection(
                $akreditasiId,
                $asesor1UserId,
                [$targetSection],
                $explanation
            );

            $this->assertTrue(
                $result['success'],
                "Iteration {$i}: createDocumentRejection should succeed for single section '{$targetSection}'"
            );

            // The one rejected section must be unlocked
            $this->assertTrue(
                $this->rejectionService->isSectionUnlocked($akreditasiId, $targetSection),
                "Iteration {$i}: Section '{$targetSection}' should be unlocked"
            );

            // All other sections must remain locked
            foreach ($otherSections as $other) {
                $this->assertFalse(
                    $this->rejectionService->isSectionUnlocked($akreditasiId, $other),
                    "Iteration {$i}: Section '{$other}' should remain locked when only '{$targetSection}' was rejected"
                );
            }
        }
    }

    /**
     * Property 18 — Re-lock after perbaikan:
     *
     * After submitPerbaikan, ALL sections SHALL be locked again regardless
     * of which sections were previously unlocked.
     *
     * **Validates: Requirements 4.6**
     */
    public function test_property18_all_sections_relocked_after_perbaikan(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $setup = $this->createAsesor1SetupAtStatus4();
            $akreditasiId = $setup['akreditasi']->id;
            $asesor1UserId = $setup['asesorUser']->id;
            $pesantrenUserId = $setup['pesantrenUser']->id;

            // Reject a random subset of sections
            $subsetSize = random_int(1, count($this->allSections));
            $rejectedSections = $this->randomSubset($this->allSections, $subsetSize);
            $explanation = $this->randomExplanation(10, 200);

            $result = $this->rejectionService->createDocumentRejection(
                $akreditasiId,
                $asesor1UserId,
                $rejectedSections,
                $explanation
            );

            $this->assertTrue($result['success'], "Iteration {$i}: createDocumentRejection should succeed");

            // Verify sections are unlocked before perbaikan
            $unlockedBefore = $this->rejectionService->getUnlockedSections($akreditasiId);
            $this->assertNotEmpty($unlockedBefore, "Iteration {$i}: Should have unlocked sections before perbaikan");

            // Submit perbaikan
            $perbaikanResult = $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);
            $this->assertTrue($perbaikanResult['success'], "Iteration {$i}: submitPerbaikan should succeed");

            // After perbaikan: ALL sections must be locked
            $unlockedAfter = $this->rejectionService->getUnlockedSections($akreditasiId);
            $this->assertEmpty(
                $unlockedAfter,
                "Iteration {$i}: getUnlockedSections should return empty after perbaikan"
            );

            foreach ($this->allSections as $section) {
                $this->assertFalse(
                    $this->rejectionService->isSectionUnlocked($akreditasiId, $section),
                    "Iteration {$i}: Section '{$section}' should be locked after perbaikan"
                );
            }
        }
    }

    /**
     * Property 18 — No unlock without rejection:
     *
     * When no rejection exists for an akreditasi, ALL sections SHALL be locked.
     *
     * **Validates: Requirements 4.4**
     */
    public function test_property18_no_sections_unlocked_without_rejection(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $setup = $this->createAsesor1SetupAtStatus4();
            $akreditasiId = $setup['akreditasi']->id;

            // No rejection created — all sections must be locked
            $unlocked = $this->rejectionService->getUnlockedSections($akreditasiId);
            $this->assertEmpty(
                $unlocked,
                "Iteration {$i}: No sections should be unlocked when no rejection exists"
            );

            foreach ($this->allSections as $section) {
                $this->assertFalse(
                    $this->rejectionService->isSectionUnlocked($akreditasiId, $section),
                    "Iteration {$i}: Section '{$section}' should be locked when no rejection exists"
                );
            }
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a pesantren user with akreditasi at status 4 and an Asesor_1 assigned.
     */
    private function createAsesor1SetupAtStatus4(): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test '.$pesantrenUser->id,
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 4,
        ]);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Test, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        return [
            'pesantrenUser' => $pesantrenUser,
            'akreditasi' => $akreditasi,
            'asesorUser' => $asesorUser,
            'asesor' => $asesor,
        ];
    }

    /**
     * Seed EDPM komponen and butirs for testing.
     */
    private function seedEdpmData(): void
    {
        $komponens = [
            ['nama' => 'Standar Isi', 'ipr' => 0],
            ['nama' => 'Standar Proses', 'ipr' => 0],
        ];

        foreach ($komponens as $k) {
            $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => $k['nama']], ['ipr' => $k['ipr']]);
            for ($b = 1; $b <= 3; $b++) {
                MasterEdpmButir::firstOrCreate(
                    ['komponen_id' => $komponen->id, 'nomor_butir' => $k['nama'][0].".{$b}"],
                    ['no_sk' => '1', 'butir_pernyataan' => "Butir {$b} dari {$k['nama']}"]
                );
            }
        }
    }

    /**
     * Build the complete list of valid section identifiers.
     */
    private function buildAllSections(): array
    {
        $sections = [
            'profil',
            'ipm.nsp',
            'ipm.kurikulum',
            'ipm.buku_ajar',
            'ipm.lulus_santri',
            'sdm',
        ];

        // Add EDPM butir identifiers
        $butirs = MasterEdpmButir::all();
        foreach ($butirs as $butir) {
            $sections[] = 'edpm.butir.'.$butir->id;
        }

        return $sections;
    }

    /**
     * Return a random subset of $array with exactly $size elements.
     */
    private function randomSubset(array $array, int $size): array
    {
        $size = min($size, count($array));
        $keys = array_rand($array, $size);
        if (! is_array($keys)) {
            $keys = [$keys];
        }

        return array_values(array_map(fn ($k) => $array[$k], $keys));
    }

    /**
     * Generate a random explanation string of length between $min and $max.
     */
    private function randomExplanation(int $min, int $max): string
    {
        $length = random_int($min, $max);
        $chars = 'abcdefghijklmnopqrstuvwxyz ';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }

        // Ensure no leading/trailing spaces that might reduce length
        return str_pad(trim($result), $min, 'x');
    }
}
