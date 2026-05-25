<?php

namespace Tests\Unit;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use App\Repositories\Contracts\RejectionRepositoryInterface;
use App\Services\RejectionService;
use Database\Seeders\RoleSeeder;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RejectionServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected RejectionService $rejectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->rejectionService = app(RejectionService::class);
    }

    /**
     * Helper: create a pesantren user with akreditasi at status 5 and an Asesor 1 assigned.
     */
private function createAsesor1Setup(): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test ' . $pesantrenUser->id,
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 5,
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
     * Helper: get a list of valid rejection item identifiers.
     */
private function getValidItems(): array
    {
        return ['profil', 'ipm.nsp', 'ipm.kurikulum', 'ipm.buku_ajar', 'ipm.lulus_santri', 'sdm'];
    }

    /**
     * Feature: structured-rejection-flow, Property 1: Rejection Input Validation
     *
     * For any rejection attempt where items array is empty OR explanation < 10 chars,
     * createRejection SHALL return error. Conversely, valid inputs SHALL pass validation.
     *
     * **Validates: Requirements 1.3, 1.4**
     */
public function test_property_1_rejection_input_validation(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            $setup = $this->createAsesor1Setup();
            $akreditasiId = $setup['akreditasi']->id;
            $userId = $setup['asesorUser']->id;

            // Decide whether to generate invalid or valid input
            $generateInvalid = $faker->boolean(50);

            if ($generateInvalid) {
                // Generate invalid input: either empty items OR short explanation
                $invalidType = $faker->randomElement(['empty_items', 'short_explanation', 'both']);

                $items = match ($invalidType) {
                    'empty_items', 'both' => [],
                    default => $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 3)),
                };

                $explanation = match ($invalidType) {
                    'short_explanation', 'both' => $faker->lexify(str_repeat('?', $faker->numberBetween(0, 9))),
                    default => $faker->text(50),
                };

                // Ensure explanation is valid when testing empty_items only
                if ($invalidType === 'empty_items' && strlen($explanation) < 10) {
                    $explanation = str_pad($explanation, 10, 'x');
                }

                $result = $this->rejectionService->createRejection($akreditasiId, $userId, $items, $explanation);

                $this->assertFalse(
                    $result['success'],
                    "Iteration {$i}: Invalid input should fail (type={$invalidType}, items=" . count($items) . ", explanation_len=" . strlen($explanation) . ")"
                );
                $this->assertNull($result['rejection'], "Iteration {$i}: No rejection record should be created for invalid input");

                // Verify no record was created
                $this->assertDatabaseMissing('akreditasi_rejections', [
                    'akreditasi_id' => $akreditasiId,
                    'user_id' => $userId,
                ]);
            } else {
                // Generate valid input: non-empty items AND explanation >= 10 chars
                $items = $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 4));
                $explanation = $faker->text($faker->numberBetween(20, 200));
                if (strlen($explanation) < 10) {
                    $explanation = str_pad($explanation, 10, 'x');
                }

                $result = $this->rejectionService->createRejection($akreditasiId, $userId, $items, $explanation);

                $this->assertTrue(
                    $result['success'],
                    "Iteration {$i}: Valid input should succeed (items=" . count($items) . ", explanation_len=" . strlen($explanation) . ", error=" . ($result['error'] ?? 'none') . ")"
                );

                // Clean up for next iteration to avoid rejection limit conflicts
                AkreditasiRejection::where('akreditasi_id', $akreditasiId)->delete();
            }
        }
    }

    /**
     * Feature: structured-rejection-flow, Property 2: Rejection Record Persistence (Round-Trip)
     *
     * For any valid rejection input, after createRejection succeeds, the stored record
     * SHALL contain exact same data as input.
     *
     * **Validates: Requirements 1.5**
     */
public function test_property_2_rejection_record_persistence(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            $setup = $this->createAsesor1Setup();
            $akreditasiId = $setup['akreditasi']->id;
            $userId = $setup['asesorUser']->id;

            // Generate valid input
            $items = $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 5));
            $explanation = $faker->text($faker->numberBetween(20, 200));
            if (strlen($explanation) < 10) {
                $explanation = str_pad($explanation, 10, 'x');
            }

            $result = $this->rejectionService->createRejection($akreditasiId, $userId, $items, $explanation);

            $this->assertTrue($result['success'], "Iteration {$i}: createRejection should succeed");
            $this->assertNotNull($result['rejection'], "Iteration {$i}: rejection record should not be null");

            // Verify the stored record matches input
            $stored = AkreditasiRejection::find($result['rejection']->id);
            $this->assertNotNull($stored, "Iteration {$i}: stored record should exist in DB");
            $this->assertEquals($akreditasiId, $stored->akreditasi_id, "Iteration {$i}: akreditasi_id should match");
            $this->assertEquals($userId, $stored->user_id, "Iteration {$i}: user_id should match");

            // Compare items (sort both for consistent comparison)
            $storedItems = $stored->items;
            sort($items);
            sort($storedItems);
            $this->assertEquals($items, $storedItems, "Iteration {$i}: items should match exactly");

            $this->assertEquals($explanation, $stored->explanation, "Iteration {$i}: explanation should match exactly");
            $this->assertEquals('asesor', $stored->type, "Iteration {$i}: type should be 'asesor'");

            // Clean up for next iteration
            AkreditasiRejection::where('akreditasi_id', $akreditasiId)->delete();
        }
    }

    /**
     * Feature: structured-rejection-flow, Property 3: Authorization — Only Asesor 1 Can Reject
     *
     * For any user NOT assigned as Asesor 1 (tipe=1), createRejection SHALL return
     * authorization error.
     *
     * **Validates: Requirements 1.6**
     */
public function test_property_3_authorization_only_asesor1_can_reject(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            $setup = $this->createAsesor1Setup();
            $akreditasiId = $setup['akreditasi']->id;

            // Create a user who is NOT Asesor 1 for this akreditasi
            $unauthorizedType = $faker->randomElement(['random_user', 'asesor2', 'pesantren_user', 'admin_user']);

            $unauthorizedUserId = match ($unauthorizedType) {
                'random_user' => User::factory()->create(['role_id' => $faker->randomElement([1, 2, 3])])->id,
                'asesor2' => $this->createAsesor2ForAkreditasi($setup['akreditasi'])->id,
                'pesantren_user' => $setup['pesantrenUser']->id,
                'admin_user' => User::factory()->create(['role_id' => 1])->id,
            };

            // Generate valid items and explanation
            $items = $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 3));
            $explanation = $faker->text($faker->numberBetween(20, 100));
            if (strlen($explanation) < 10) {
                $explanation = str_pad($explanation, 10, 'x');
            }

            $result = $this->rejectionService->createRejection($akreditasiId, $unauthorizedUserId, $items, $explanation);

            $this->assertFalse(
                $result['success'],
                "Iteration {$i}: Unauthorized user (type={$unauthorizedType}) should not be able to reject"
            );
            $this->assertEquals('unauthorized', $result['error'], "Iteration {$i}: Error should be 'unauthorized'");
            $this->assertNull($result['rejection'], "Iteration {$i}: No rejection record should be created");

            // Verify no record was created by this unauthorized user
            $this->assertDatabaseMissing('akreditasi_rejections', [
                'akreditasi_id' => $akreditasiId,
                'user_id' => $unauthorizedUserId,
            ]);
        }
    }

    /**
     * Feature: structured-rejection-flow, Property 4: Partial Unlock Correctness
     *
     * For any subset of valid rejection items, after creating a rejection,
     * isSectionUnlocked SHALL return true for every item in the rejected set
     * and false for every valid item NOT in the rejected set.
     *
     * **Validates: Requirements 2.1, 2.2**
     */
public function test_property_4_partial_unlock_correctness(): void
    {
        $faker = Faker::create();

        // Create some EDPM butirs for testing
        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'Standar Isi'], ['ipr' => 1]);
        $butirs = [];
        for ($b = 1; $b <= 5; $b++) {
            $butirs[] = MasterEdpmButir::firstOrCreate(
                ['komponen_id' => $komponen->id, 'nomor_butir' => "1.{$b}"],
                ['no_sk' => '1', 'butir_pernyataan' => "Butir pernyataan {$b}"]
            );
        }

        $allValidItems = array_merge(
            $this->getValidItems(),
            array_map(fn ($butir) => 'edpm.butir.' . $butir->id, $butirs)
        );

        for ($i = 0; $i < 100; $i++) {
            $setup = $this->createAsesor1Setup();
            $akreditasiId = $setup['akreditasi']->id;
            $userId = $setup['asesorUser']->id;

            // Pick a random subset of items to reject
            $subsetSize = $faker->numberBetween(1, count($allValidItems) - 1);
            $rejectedItems = $faker->randomElements($allValidItems, $subsetSize);
            $notRejectedItems = array_values(array_diff($allValidItems, $rejectedItems));

            $explanation = $faker->text($faker->numberBetween(20, 100));
            if (strlen($explanation) < 10) {
                $explanation = str_pad($explanation, 10, 'x');
            }

            $result = $this->rejectionService->createRejection($akreditasiId, $userId, $rejectedItems, $explanation);
            $this->assertTrue($result['success'], "Iteration {$i}: createRejection should succeed");

            // Verify: every rejected item is unlocked
            foreach ($rejectedItems as $item) {
                $this->assertTrue(
                    $this->rejectionService->isSectionUnlocked($akreditasiId, $item),
                    "Iteration {$i}: Item '{$item}' should be unlocked (it was rejected)"
                );
            }

            // Verify: every non-rejected item is NOT unlocked
            foreach ($notRejectedItems as $item) {
                $this->assertFalse(
                    $this->rejectionService->isSectionUnlocked($akreditasiId, $item),
                    "Iteration {$i}: Item '{$item}' should NOT be unlocked (it was not rejected)"
                );
            }

            // Also verify getUnlockedSections returns exactly the rejected items
            $unlocked = $this->rejectionService->getUnlockedSections($akreditasiId);
            sort($unlocked);
            $sortedRejected = $rejectedItems;
            sort($sortedRejected);
            $this->assertEquals($sortedRejected, $unlocked, "Iteration {$i}: getUnlockedSections should return exactly the rejected items");

            // Clean up for next iteration
            AkreditasiRejection::where('akreditasi_id', $akreditasiId)->delete();
        }
    }

    /**
     * Helper: create an Asesor 2 for a given akreditasi.
     */
private function createAsesor2ForAkreditasi(Akreditasi $akreditasi): User
    {
        $asesorUser2 = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create([
            'user_id' => $asesorUser2->id,
            'nama_dengan_gelar' => 'Dr. Asesor 2, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor 2',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        return $asesorUser2;
    }

    /**
     * Feature: structured-rejection-flow, Property 5: Perbaikan Re-locks All Sections
     *
     * For any set of previously unlocked items, after submitPerbaikan succeeds,
     * getUnlockedSections SHALL return empty array and isSectionUnlocked SHALL
     * return false for all.
     *
     * **Validates: Requirements 3.2, 3.7**
     */
public function test_property_5_perbaikan_relocks_all_sections(): void
    {
        $faker = Faker::create();

        // Create some EDPM butirs for testing
        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'Standar Isi'], ['ipr' => 1]);
        $butirs = [];
        for ($b = 1; $b <= 5; $b++) {
            $butirs[] = MasterEdpmButir::firstOrCreate(
                ['komponen_id' => $komponen->id, 'nomor_butir' => "1.{$b}"],
                ['no_sk' => '1', 'butir_pernyataan' => "Butir pernyataan {$b}"]
            );
        }

        $allValidItems = array_merge(
            $this->getValidItems(),
            array_map(fn ($butir) => 'edpm.butir.' . $butir->id, $butirs)
        );

        for ($i = 0; $i < 100; $i++) {
            $setup = $this->createAsesor1Setup();
            $akreditasiId = $setup['akreditasi']->id;
            $asesorUserId = $setup['asesorUser']->id;
            $pesantrenUserId = $setup['pesantrenUser']->id;

            // Pick a random subset of items to reject
            $subsetSize = $faker->numberBetween(1, count($allValidItems));
            $rejectedItems = $faker->randomElements($allValidItems, $subsetSize);

            $explanation = $faker->text($faker->numberBetween(20, 100));
            if (strlen($explanation) < 10) {
                $explanation = str_pad($explanation, 10, 'x');
            }

            // Create a rejection (unlocks sections)
            $result = $this->rejectionService->createRejection($akreditasiId, $asesorUserId, $rejectedItems, $explanation);
            $this->assertTrue($result['success'], "Iteration {$i}: createRejection should succeed");

            // Verify sections are unlocked before perbaikan
            $unlockedBefore = $this->rejectionService->getUnlockedSections($akreditasiId);
            $this->assertNotEmpty($unlockedBefore, "Iteration {$i}: Should have unlocked sections before perbaikan");

            // Submit perbaikan
            $perbaikanResult = $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);
            $this->assertTrue($perbaikanResult['success'], "Iteration {$i}: submitPerbaikan should succeed");

            // Verify: getUnlockedSections returns empty array
            $unlockedAfter = $this->rejectionService->getUnlockedSections($akreditasiId);
            $this->assertEmpty($unlockedAfter, "Iteration {$i}: getUnlockedSections should return empty after perbaikan");

            // Verify: isSectionUnlocked returns false for ALL items
            foreach ($allValidItems as $item) {
                $this->assertFalse(
                    $this->rejectionService->isSectionUnlocked($akreditasiId, $item),
                    "Iteration {$i}: isSectionUnlocked('{$item}') should be false after perbaikan"
                );
            }

            // Clean up for next iteration
            AkreditasiRejection::where('akreditasi_id', $akreditasiId)->delete();
        }
    }

    /**
     * Feature: structured-rejection-flow, Property 6: Status Invariant During Rejection-Correction Cycle
     *
     * For any sequence of createRejection and submitPerbaikan calls where rejection
     * count < limit, akreditasi status SHALL remain at 5.
     *
     * **Validates: Requirements 3.6**
     */
public function test_property_6_status_invariant_during_cycle(): void
    {
        $faker = Faker::create();

        // Set limit to a higher value to allow multiple cycles
        config(['akreditasi.rejection_limit' => 5]);

        for ($i = 0; $i < 100; $i++) {
            $setup = $this->createAsesor1Setup();
            $akreditasiId = $setup['akreditasi']->id;
            $asesorUserId = $setup['asesorUser']->id;
            $pesantrenUserId = $setup['pesantrenUser']->id;

            // Perform a random number of rejection-perbaikan cycles (1 to 3, below limit of 5)
            $cycles = $faker->numberBetween(1, 3);

            for ($c = 0; $c < $cycles; $c++) {
                $items = $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 3));
                $explanation = $faker->text($faker->numberBetween(20, 100));
                if (strlen($explanation) < 10) {
                    $explanation = str_pad($explanation, 10, 'x');
                }

                // Create rejection
                $result = $this->rejectionService->createRejection($akreditasiId, $asesorUserId, $items, $explanation);
                $this->assertTrue($result['success'], "Iteration {$i}, cycle {$c}: createRejection should succeed");

                // Verify status is still 5 after rejection
                $akreditasi = $this->akreditasiRepository()->find($akreditasiId);
                $this->assertEquals(5, (int) $akreditasi->status, "Iteration {$i}, cycle {$c}: Status should remain 5 after rejection");

                // Submit perbaikan
                $perbaikanResult = $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);
                $this->assertTrue($perbaikanResult['success'], "Iteration {$i}, cycle {$c}: submitPerbaikan should succeed");

                // Verify status is still 5 after perbaikan
                $akreditasi = $this->akreditasiRepository()->find($akreditasiId);
                $this->assertEquals(5, (int) $akreditasi->status, "Iteration {$i}, cycle {$c}: Status should remain 5 after perbaikan");

                // Accept perbaikan to allow next cycle
                $acceptResult = $this->rejectionService->acceptPerbaikan($akreditasiId, $asesorUserId);
                $this->assertTrue($acceptResult['success'], "Iteration {$i}, cycle {$c}: acceptPerbaikan should succeed");

                // Verify status is still 5 after accept
                $akreditasi = $this->akreditasiRepository()->find($akreditasiId);
                $this->assertEquals(5, (int) $akreditasi->status, "Iteration {$i}, cycle {$c}: Status should remain 5 after accept");
            }

            // Clean up for next iteration
            AkreditasiRejection::where('akreditasi_id', $akreditasiId)->delete();
        }
    }

    /**
     * Feature: structured-rejection-flow, Property 7: Rejection Counter Increments Correctly
     *
     * For any sequence of N successful rejections (N ≤ limit), the Nth rejection record
     * SHALL have rejection_number = N, and countByAkreditasi SHALL return N.
     *
     * **Validates: Requirements 4.2**
     */
public function test_property_7_rejection_counter_increments(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            // Set a random limit between 2 and 5
            $limit = $faker->numberBetween(2, 5);
            config(['akreditasi.rejection_limit' => $limit]);

            $setup = $this->createAsesor1Setup();
            $akreditasiId = $setup['akreditasi']->id;
            $asesorUserId = $setup['asesorUser']->id;
            $pesantrenUserId = $setup['pesantrenUser']->id;

            // Create N rejections where N is random between 1 and limit
            $n = $faker->numberBetween(1, $limit);

            for ($j = 1; $j <= $n; $j++) {
                $items = $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 3));
                $explanation = $faker->text($faker->numberBetween(20, 100));
                if (strlen($explanation) < 10) {
                    $explanation = str_pad($explanation, 10, 'x');
                }

                $result = $this->rejectionService->createRejection($akreditasiId, $asesorUserId, $items, $explanation);
                $this->assertTrue($result['success'], "Iteration {$i}, rejection {$j}: createRejection should succeed");

                // Verify rejection_number equals j
                $this->assertEquals(
                    $j,
                    $result['rejection']->rejection_number,
                    "Iteration {$i}: Rejection #{$j} should have rejection_number = {$j}"
                );

                // Verify countByAkreditasi returns j
                $count = app(RejectionRepositoryInterface::class)->countByAkreditasi($akreditasiId);
                $this->assertEquals(
                    $j,
                    $count,
                    "Iteration {$i}: countByAkreditasi should return {$j} after {$j} rejections"
                );

                // If not at limit and not the last one we want to create, submit perbaikan + accept to allow next rejection
                if ($j < $n && $j < $limit) {
                    // Only submit perbaikan if the rejection is pending (not limit_reached)
                    if ($result['rejection']->status === 'pending') {
                        $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);
                        $this->rejectionService->acceptPerbaikan($akreditasiId, $asesorUserId);
                    }
                }
            }

            // Clean up for next iteration
            AkreditasiRejection::where('akreditasi_id', $akreditasiId)->delete();
            // Reset akreditasi status in case it was changed to 2
            $setup['akreditasi']->update(['status' => 5]);
        }
    }

    /**
     * Feature: structured-rejection-flow, Property 8: Auto-Rejection at Limit
     *
     * When the Nth rejection (N = limit) is created, system SHALL change akreditasi
     * status to 2 and SHALL NOT create a partial unlock cycle (no perbaikan_deadline,
     * status='limit_reached').
     *
     * **Validates: Requirements 4.3**
     */
public function test_property_8_auto_rejection_at_limit(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            // Set a random limit between 1 and 4
            $limit = $faker->numberBetween(1, 4);
            config(['akreditasi.rejection_limit' => $limit]);

            $setup = $this->createAsesor1Setup();
            $akreditasiId = $setup['akreditasi']->id;
            $asesorUserId = $setup['asesorUser']->id;
            $pesantrenUserId = $setup['pesantrenUser']->id;

            // Create rejections up to limit - 1 (with perbaikan cycles)
            for ($j = 1; $j < $limit; $j++) {
                $items = $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 3));
                $explanation = $faker->text($faker->numberBetween(20, 100));
                if (strlen($explanation) < 10) {
                    $explanation = str_pad($explanation, 10, 'x');
                }

                $result = $this->rejectionService->createRejection($akreditasiId, $asesorUserId, $items, $explanation);
                $this->assertTrue($result['success'], "Iteration {$i}, pre-limit rejection {$j}: should succeed");

                // Submit perbaikan and accept to allow next rejection
                $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);
                $this->rejectionService->acceptPerbaikan($akreditasiId, $asesorUserId);
            }

            // Create the Nth (limit) rejection — should trigger auto-reject
            $items = $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 3));
            $explanation = $faker->text($faker->numberBetween(20, 100));
            if (strlen($explanation) < 10) {
                $explanation = str_pad($explanation, 10, 'x');
            }

            $result = $this->rejectionService->createRejection($akreditasiId, $asesorUserId, $items, $explanation);
            $this->assertTrue($result['success'], "Iteration {$i}: Limit rejection should succeed");

            // Verify: akreditasi status changed to Ditolak
            $akreditasi = $this->akreditasiRepository()->find($akreditasiId);
            $this->assertEquals(-1, (int) $akreditasi->status, "Iteration {$i}: Akreditasi status should be -1 (Ditolak) after limit reached");

            // Verify: rejection record has status 'limit_reached'
            $this->assertEquals('limit_reached', $result['rejection']->status, "Iteration {$i}: Rejection status should be 'limit_reached'");

            // Verify: no perbaikan_deadline set
            $this->assertNull($result['rejection']->perbaikan_deadline, "Iteration {$i}: perbaikan_deadline should be null for limit_reached rejection");

            // Clean up for next iteration
            AkreditasiRejection::where('akreditasi_id', $akreditasiId)->delete();
            $setup['akreditasi']->update(['status' => 5]);
        }
    }

    /**
     * Feature: structured-rejection-flow, Property 9: Auto-Rejection Unlocks Pesantren Data
     *
     * For any akreditasi auto-rejected (limit reached), pesantren's is_locked SHALL be false.
     *
     * **Validates: Requirements 4.6, 8.4**
     */
public function test_property_9_auto_rejection_unlocks_pesantren_data(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            // Set a random limit between 1 and 4
            $limit = $faker->numberBetween(1, 4);
            config(['akreditasi.rejection_limit' => $limit]);

            $setup = $this->createAsesor1Setup();
            $akreditasiId = $setup['akreditasi']->id;
            $asesorUserId = $setup['asesorUser']->id;
            $pesantrenUserId = $setup['pesantrenUser']->id;

            // Verify pesantren is locked initially
            $pesantren = Pesantren::where('user_id', $pesantrenUserId)->first();
            $this->assertTrue((bool) $pesantren->is_locked, "Iteration {$i}: Pesantren should be locked initially");

            // Create rejections up to limit - 1 (with perbaikan cycles)
            for ($j = 1; $j < $limit; $j++) {
                $items = $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 3));
                $explanation = $faker->text($faker->numberBetween(20, 100));
                if (strlen($explanation) < 10) {
                    $explanation = str_pad($explanation, 10, 'x');
                }

                $result = $this->rejectionService->createRejection($akreditasiId, $asesorUserId, $items, $explanation);
                $this->assertTrue($result['success'], "Iteration {$i}, pre-limit rejection {$j}: should succeed");

                // Submit perbaikan and accept to allow next rejection
                $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);
                $this->rejectionService->acceptPerbaikan($akreditasiId, $asesorUserId);
            }

            // Create the Nth (limit) rejection — should trigger auto-reject and unlock
            $items = $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 3));
            $explanation = $faker->text($faker->numberBetween(20, 100));
            if (strlen($explanation) < 10) {
                $explanation = str_pad($explanation, 10, 'x');
            }

            $result = $this->rejectionService->createRejection($akreditasiId, $asesorUserId, $items, $explanation);
            $this->assertTrue($result['success'], "Iteration {$i}: Limit rejection should succeed");

            // Verify: pesantren is_locked is now false
            $pesantren->refresh();
            $this->assertFalse((bool) $pesantren->is_locked, "Iteration {$i}: Pesantren is_locked should be false after auto-rejection");

            // Clean up for next iteration
            AkreditasiRejection::where('akreditasi_id', $akreditasiId)->delete();
            $setup['akreditasi']->update(['status' => 5]);
            $pesantren->update(['is_locked' => true]);
        }
    }

    /**
     * Feature: structured-rejection-flow, Property 12: Deadline Calculation Correctness
     *
     * For any rejection created at time T with configured perbaikan_deadline_days of D,
     * the stored perbaikan_deadline SHALL equal T + D days.
     *
     * **Validates: Requirements 8.2**
     */
public function test_property_12_deadline_calculation_correctness(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            // Set a random deadline days between 1 and 30
            $deadlineDays = $faker->numberBetween(1, 30);
            config(['akreditasi.perbaikan_deadline_days' => $deadlineDays]);
            // Ensure rejection limit is high enough to avoid auto-reject
            config(['akreditasi.rejection_limit' => 10]);

            $setup = $this->createAsesor1Setup();
            $akreditasiId = $setup['akreditasi']->id;
            $userId = $setup['asesorUser']->id;

            // Generate valid input
            $items = $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 3));
            $explanation = $faker->text($faker->numberBetween(20, 100));
            if (strlen($explanation) < 10) {
                $explanation = str_pad($explanation, 10, 'x');
            }

            // Freeze time at a random point
            $creationTime = now();
            \Illuminate\Support\Carbon::setTestNow($creationTime);

            $result = $this->rejectionService->createRejection($akreditasiId, $userId, $items, $explanation);

            $this->assertTrue($result['success'], "Iteration {$i}: createRejection should succeed");
            $this->assertNotNull($result['rejection'], "Iteration {$i}: rejection should not be null");

            // Verify: perbaikan_deadline equals creation time + D days
            $expectedDeadline = $creationTime->copy()->addDays($deadlineDays);
            $stored = AkreditasiRejection::find($result['rejection']->id);
            $storedDeadline = $stored->perbaikan_deadline;

            $this->assertNotNull($storedDeadline, "Iteration {$i}: perbaikan_deadline should not be null");
            $this->assertEquals(
                $expectedDeadline->format('Y-m-d H:i:s'),
                $storedDeadline->format('Y-m-d H:i:s'),
                "Iteration {$i}: perbaikan_deadline should equal creation time + {$deadlineDays} days."
            );

            // Clean up for next iteration
            AkreditasiRejection::where('akreditasi_id', $akreditasiId)->delete();
            \Illuminate\Support\Carbon::setTestNow(); // Reset time
        }
    }

    /**
     * Helper: get the AkreditasiRepository instance.
     */
private function akreditasiRepository(): \App\Repositories\Contracts\AkreditasiRepositoryInterface
    {
        return app(\App\Repositories\Contracts\AkreditasiRepositoryInterface::class);
    }

    /**
     * Feature: structured-rejection-flow, Property 10: Rejection Blocked at Status 6
     *
     * For any akreditasi at status 6, calling rejectPengajuan SHALL throw DomainException
     * and status SHALL remain unchanged.
     *
     * **Validates: Requirements 5.1, 5.3, 5.5**
     */
public function test_property_10_rejection_blocked_at_status_6(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            // Create a pesantren user with akreditasi at status 6
            $pesantrenUser = User::factory()->create(['role_id' => 3]);
            Pesantren::create([
                'user_id' => $pesantrenUser->id,
                'nama_pesantren' => 'Pesantren Status6 ' . $pesantrenUser->id,
                'is_locked' => true,
            ]);

            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => 6,
            ]);

            // Generate a random reason string
            $reason = $faker->text($faker->numberBetween(10, 200));

            $akreditasiService = app(\App\Services\AkreditasiService::class);

            // Calling rejectPengajuan SHALL throw DomainException
            $threwException = false;
            try {
                $akreditasiService->rejectPengajuan($akreditasi->id, $reason);
            } catch (\DomainException $e) {
                $threwException = true;
                $this->assertEquals(
                    'Rejection at status 6 (Pengajuan) is no longer permitted.',
                    $e->getMessage(),
                    "Iteration {$i}: DomainException message should match"
                );
            }

            $this->assertTrue($threwException, "Iteration {$i}: rejectPengajuan should throw DomainException for status 6");

            // Status SHALL remain unchanged (still 6)
            $akreditasi->refresh();
            $this->assertEquals(6, (int) $akreditasi->status, "Iteration {$i}: Akreditasi status should remain 6");
        }
    }

    /**
     * Feature: structured-rejection-flow, Property 13: Final Rejection Input Validation
     *
     * For any final rejection attempt where categories array is empty OR any category has
     * explanation < 10 chars, createFinalRejection SHALL return error. Valid inputs SHALL pass.
     *
     * **Validates: Requirements 9.1, 9.2**
     */
public function test_property_13_final_rejection_input_validation(): void
    {
        $faker = Faker::create();
        $validCategoryKeys = array_keys(config('akreditasi.final_rejection_categories'));

        for ($i = 0; $i < 100; $i++) {
            // Create admin user and akreditasi at status 3
            $adminUser = User::factory()->create(['role_id' => 1]);
            $pesantrenUser = User::factory()->create(['role_id' => 3]);
            Pesantren::create([
                'user_id' => $pesantrenUser->id,
                'nama_pesantren' => 'Pesantren Final ' . $pesantrenUser->id,
                'is_locked' => true,
            ]);

            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => 3,
            ]);

            // Decide whether to generate invalid or valid input
            $generateInvalid = $faker->boolean(50);

            if ($generateInvalid) {
                $invalidType = $faker->randomElement(['empty_categories', 'short_explanation', 'both']);

                $categories = match ($invalidType) {
                    'empty_categories' => [],
                    'short_explanation' => [
                        [
                            'category' => $faker->randomElement($validCategoryKeys),
                            'explanation' => $faker->lexify(str_repeat('?', $faker->numberBetween(0, 9))),
                        ],
                    ],
                    'both' => [],
                };

                $result = $this->rejectionService->createFinalRejection($akreditasi->id, $adminUser->id, $categories);

                $this->assertFalse(
                    $result['success'],
                    "Iteration {$i}: Invalid input should fail (type={$invalidType})"
                );

                // Verify no record was created
                $this->assertDatabaseMissing('akreditasi_rejections', [
                    'akreditasi_id' => $akreditasi->id,
                    'type' => 'admin_final',
                ]);

                // Verify status unchanged
                $akreditasi->refresh();
                $this->assertEquals(3, (int) $akreditasi->status, "Iteration {$i}: Status should remain 3 for invalid input");
            } else {
                // Generate valid input: non-empty categories, each with valid key and explanation >= 10 chars
                $numCategories = $faker->numberBetween(1, count($validCategoryKeys));
                $selectedKeys = $faker->randomElements($validCategoryKeys, $numCategories);
                $categories = array_map(function ($key) use ($faker) {
                    $explanation = $faker->text($faker->numberBetween(20, 200));
                    if (strlen($explanation) < 10) {
                        $explanation = str_pad($explanation, 10, 'x');
                    }
                    return ['category' => $key, 'explanation' => $explanation];
                }, $selectedKeys);

                $result = $this->rejectionService->createFinalRejection($akreditasi->id, $adminUser->id, $categories);

                $this->assertTrue(
                    $result['success'],
                    "Iteration {$i}: Valid input should succeed (error=" . ($result['error'] ?? 'none') . ")"
                );

                // Clean up for next iteration
                AkreditasiRejection::where('akreditasi_id', $akreditasi->id)->delete();
                $akreditasi->update(['status' => 3]);
            }
        }
    }

    /**
     * Feature: structured-rejection-flow, Property 14: Final Rejection Persistence
     *
     * For any valid final rejection input, after createFinalRejection succeeds, stored record
     * SHALL contain exact categories, akreditasi status SHALL be 2, record type SHALL be 'admin_final'.
     *
     * **Validates: Requirements 9.3**
     */
public function test_property_14_final_rejection_persistence(): void
    {
        $faker = Faker::create();
        $validCategoryKeys = array_keys(config('akreditasi.final_rejection_categories'));

        for ($i = 0; $i < 100; $i++) {
            // Create admin user and akreditasi at status 3
            $adminUser = User::factory()->create(['role_id' => 1]);
            $pesantrenUser = User::factory()->create(['role_id' => 3]);
            Pesantren::create([
                'user_id' => $pesantrenUser->id,
                'nama_pesantren' => 'Pesantren Persist ' . $pesantrenUser->id,
                'is_locked' => true,
            ]);

            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => 3,
            ]);

            // Generate valid categories
            $numCategories = $faker->numberBetween(1, count($validCategoryKeys));
            $selectedKeys = $faker->randomElements($validCategoryKeys, $numCategories);
            $categories = array_map(function ($key) use ($faker) {
                $explanation = $faker->text($faker->numberBetween(20, 200));
                if (strlen($explanation) < 10) {
                    $explanation = str_pad($explanation, 10, 'x');
                }
                return ['category' => $key, 'explanation' => $explanation];
            }, $selectedKeys);

            $result = $this->rejectionService->createFinalRejection($akreditasi->id, $adminUser->id, $categories);

            $this->assertTrue($result['success'], "Iteration {$i}: createFinalRejection should succeed");

            // Verify: stored record contains exact categories
            $stored = AkreditasiRejection::where('akreditasi_id', $akreditasi->id)
                ->where('type', 'admin_final')
                ->first();

            $this->assertNotNull($stored, "Iteration {$i}: stored record should exist");
            $this->assertEquals('admin_final', $stored->type, "Iteration {$i}: type should be 'admin_final'");
            $this->assertEquals($adminUser->id, $stored->user_id, "Iteration {$i}: user_id should match admin");
            $this->assertEquals($categories, $stored->categories, "Iteration {$i}: categories should match exactly");

            // Verify: akreditasi status SHALL be Ditolak
            $akreditasi->refresh();
            $this->assertEquals(-1, (int) $akreditasi->status, "Iteration {$i}: Akreditasi status should be -1 (Ditolak)");

            // Clean up for next iteration
            AkreditasiRejection::where('akreditasi_id', $akreditasi->id)->delete();
            $akreditasi->update(['status' => 3]);
        }
    }

    /**
     * Feature: structured-rejection-flow, Property 11: Rejection History Chronological Ordering
     *
     * For any set of rejection records created at different timestamps,
     * getRejectionStatus().history SHALL return them ordered by created_at ascending.
     *
     * **Validates: Requirements 6.1**
     */
public function test_property_11_rejection_history_chronological_ordering(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 100; $i++) {
            // Set high rejection limit to allow multiple rejections
            config(['akreditasi.rejection_limit' => 10]);

            $setup = $this->createAsesor1Setup();
            $akreditasiId = $setup['akreditasi']->id;
            $asesorUserId = $setup['asesorUser']->id;
            $pesantrenUserId = $setup['pesantrenUser']->id;

            // Create a random number of rejections (2 to 5) at different timestamps
            $numRejections = $faker->numberBetween(2, 5);
            $createdTimestamps = [];

            for ($j = 0; $j < $numRejections; $j++) {
                // Set a specific time for each rejection (increasing order with random gaps)
                $time = now()->subDays($numRejections - $j)->addMinutes($faker->numberBetween(0, 60));
                \Illuminate\Support\Carbon::setTestNow($time);

                $items = $faker->randomElements($this->getValidItems(), $faker->numberBetween(1, 3));
                $explanation = $faker->text($faker->numberBetween(20, 100));
                if (strlen($explanation) < 10) {
                    $explanation = str_pad($explanation, 10, 'x');
                }

                $result = $this->rejectionService->createRejection($akreditasiId, $asesorUserId, $items, $explanation);
                $this->assertTrue($result['success'], "Iteration {$i}, rejection {$j}: createRejection should succeed");

                $createdTimestamps[] = $time->format('Y-m-d H:i:s');

                // Submit perbaikan and accept to allow next rejection (except last)
                if ($j < $numRejections - 1) {
                    $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);
                    $this->rejectionService->acceptPerbaikan($akreditasiId, $asesorUserId);
                }
            }

            \Illuminate\Support\Carbon::setTestNow(); // Reset time

            // Get rejection status and verify history ordering
            $status = $this->rejectionService->getRejectionStatus($akreditasiId);
            $history = $status['history'];

            $this->assertCount($numRejections, $history, "Iteration {$i}: history should have {$numRejections} records");

            // Verify: records are ordered by created_at ascending
            $previousTimestamp = null;
            foreach ($history as $record) {
                $currentTimestamp = $record->created_at->format('Y-m-d H:i:s');
                if ($previousTimestamp !== null) {
                    $this->assertLessThanOrEqual(
                        $currentTimestamp,
                        $currentTimestamp,
                        "Iteration {$i}: History should be ordered by created_at ascending"
                    );
                    $this->assertGreaterThanOrEqual(
                        $previousTimestamp,
                        $currentTimestamp,
                        "Iteration {$i}: Record at position should have created_at >= previous record"
                    );
                }
                $previousTimestamp = $currentTimestamp;
            }

            // Clean up for next iteration
            AkreditasiRejection::where('akreditasi_id', $akreditasiId)->delete();
        }
    }
}
