<?php

namespace Tests\Unit\Resubmission;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\ResubmissionService;
use App\StateMachine\AkreditasiStateMachine;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Property-Based Test: Property 13 — Resubmission Chain Count
 *
 * For any akreditasi chain (connected via parent_id links), the total count
 * of resubmission records SHALL never exceed 3, and resubmission SHALL be
 * rejected when the count equals 3.
 *
 * **Validates: Requirements 13.5, 13.10**
 *
 */
#[Group('akreditasi-workflow-redesign')]
class Property13ResubmissionChainCountTest extends TestCase
{
    use RefreshDatabase;

    protected ResubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = app(ResubmissionService::class);
    }

    // =========================================================================
    // Property 13 — Main property test (≥100 iterations)
    // =========================================================================

    /**
     * Property 13 — Chain Count Never Exceeds 3:
     *
     * For any akreditasi chain of arbitrary depth, getChainCount SHALL return
     * the correct number of resubmissions (records with non-null parent), and
     * canResubmit SHALL return can=false when the count equals or exceeds 3.
     *
     * **Validates: Requirements 13.5, 13.10**
     */
public function test_property13_chain_count_never_exceeds_3_and_resubmission_rejected_at_3(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Generate a random chain depth (0 to 5 resubmissions)
            $resubmissionCount = random_int(0, 5);

            $user = User::factory()->create(['role_id' => 3]);

            // Create the root akreditasi (no parent)
            $root = Akreditasi::create([
                'user_id' => $user->id,
                'status' => AkreditasiStateMachine::STATUS_DITOLAK,
            ]);

            // Set rejection date to well past 30 days to avoid cooling period interference
            DB::table('akreditasis')
                ->where('id', $root->id)
                ->update(['updated_at' => Carbon::now()->subDays(60)]);

            $current = $root;

            // Build the chain with $resubmissionCount resubmissions
            for ($j = 0; $j < $resubmissionCount; $j++) {
                $child = Akreditasi::create([
                    'user_id' => $user->id,
                    'parent' => $root->id, // All point to root per spec (Req 13.7)
                    'status' => AkreditasiStateMachine::STATUS_DITOLAK,
                ]);
                DB::table('akreditasis')
                    ->where('id', $child->id)
                    ->update(['updated_at' => Carbon::now()->subDays(60)]);
                $current = $child;
            }

            // Verify getChainCount returns the correct count
            $count = $this->service->getChainCount($current->id);
            $this->assertSame(
                $resubmissionCount,
                $count,
                "Iteration {$i}: getChainCount should return {$resubmissionCount} for a chain with {$resubmissionCount} resubmissions"
            );

            // Verify canResubmit behavior based on count
            $canResult = $this->service->canResubmit($current->id);

            if ($resubmissionCount >= 3) {
                $this->assertFalse(
                    $canResult['can'],
                    "Iteration {$i}: canResubmit should return can=false when chain count is {$resubmissionCount} (>= 3)"
                );
                $this->assertNotNull(
                    $canResult['reason'],
                    "Iteration {$i}: canResubmit should provide a reason when rejected"
                );
                $this->assertNull(
                    $canResult['days_remaining'],
                    "Iteration {$i}: days_remaining should be null when rejected due to count limit"
                );
            } else {
                // Count < 3 and cooling period elapsed (60 days ago)
                $this->assertTrue(
                    $canResult['can'],
                    "Iteration {$i}: canResubmit should return can=true when chain count is {$resubmissionCount} (< 3) and cooling elapsed. Reason: " . ($canResult['reason'] ?? 'none')
                );
            }
        }
    }

    /**
     * Property 13 — Resubmission rejected exactly at count=3:
     *
     * When the chain already has exactly 3 resubmissions, canResubmit SHALL
     * return can=false. When it has exactly 2, canResubmit SHALL return
     * can=true (assuming cooling period elapsed).
     *
     * **Validates: Requirements 13.5, 13.10**
     */
public function test_property13_boundary_at_exactly_3_resubmissions(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create(['role_id' => 3]);

            // Create root
            $root = Akreditasi::create([
                'user_id' => $user->id,
                'status' => AkreditasiStateMachine::STATUS_DITOLAK,
            ]);
            DB::table('akreditasis')
                ->where('id', $root->id)
                ->update(['updated_at' => Carbon::now()->subDays(60)]);

            // Create exactly 2 resubmissions (count = 2, should be allowed)
            for ($j = 0; $j < 2; $j++) {
                $child = Akreditasi::create([
                    'user_id' => $user->id,
                    'parent' => $root->id,
                    'status' => AkreditasiStateMachine::STATUS_DITOLAK,
                ]);
                DB::table('akreditasis')
                    ->where('id', $child->id)
                    ->update(['updated_at' => Carbon::now()->subDays(60)]);
            }

            // Get the last child
            $lastChild = Akreditasi::where('parent', $root->id)
                ->orderBy('id', 'desc')
                ->first();

            // At count=2, should be allowed
            $count2 = $this->service->getChainCount($lastChild->id);
            $this->assertSame(2, $count2, "Iteration {$i}: Chain count should be 2");

            $canAt2 = $this->service->canResubmit($lastChild->id);
            $this->assertTrue(
                $canAt2['can'],
                "Iteration {$i}: canResubmit should be true at count=2. Reason: " . ($canAt2['reason'] ?? 'none')
            );

            // Add the 3rd resubmission (count = 3, should be rejected)
            $thirdChild = Akreditasi::create([
                'user_id' => $user->id,
                'parent' => $root->id,
                'status' => AkreditasiStateMachine::STATUS_DITOLAK,
            ]);
            DB::table('akreditasis')
                ->where('id', $thirdChild->id)
                ->update(['updated_at' => Carbon::now()->subDays(60)]);

            $count3 = $this->service->getChainCount($thirdChild->id);
            $this->assertSame(3, $count3, "Iteration {$i}: Chain count should be 3");

            $canAt3 = $this->service->canResubmit($thirdChild->id);
            $this->assertFalse(
                $canAt3['can'],
                "Iteration {$i}: canResubmit should be false at count=3"
            );
        }
    }

    /**
     * Property 13 — createResubmission creates record at status 6 with root parent:
     *
     * When createResubmission is called, the new Akreditasi SHALL be at
     * status 6 (Pengajuan) with parent_id set to the root akreditasi.
     *
     * **Validates: Requirements 13.7, 13.8**
     */
public function test_property13_create_resubmission_sets_correct_status_and_parent(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create(['role_id' => 3]);

            // Create pesantren for the user
            Pesantren::create([
                'user_id' => $user->id,
                'nama_pesantren' => 'Pesantren Test ' . $user->id,
                'is_locked' => true,
            ]);

            // Create a chain of random depth (0 to 2 resubmissions)
            $chainDepth = random_int(0, 2);

            $root = Akreditasi::create([
                'user_id' => $user->id,
                'status' => AkreditasiStateMachine::STATUS_DITOLAK,
            ]);

            $current = $root;
            for ($j = 0; $j < $chainDepth; $j++) {
                $current = Akreditasi::create([
                    'user_id' => $user->id,
                    'parent' => $root->id,
                    'status' => AkreditasiStateMachine::STATUS_DITOLAK,
                ]);
            }

            // Create resubmission
            $newAkreditasi = $this->service->createResubmission($current->id, $user->id);

            // Assert: new akreditasi is at status 6
            $this->assertSame(
                AkreditasiStateMachine::STATUS_PENGAJUAN,
                (int) $newAkreditasi->status,
                "Iteration {$i}: New akreditasi should be at status 6 (Pengajuan)"
            );

            // Assert: parent_id is set to root
            $this->assertSame(
                $root->id,
                (int) $newAkreditasi->parent,
                "Iteration {$i}: New akreditasi parent should be the root akreditasi (id={$root->id})"
            );

            // Assert: pesantren data is unlocked
            $pesantren = Pesantren::where('user_id', $user->id)->first();
            $this->assertFalse(
                (bool) $pesantren->is_locked,
                "Iteration {$i}: Pesantren data should be unlocked after resubmission"
            );
        }
    }

    /**
     * Property 13 — Chain count is consistent regardless of which chain member is queried:
     *
     * For any akreditasi in a chain, getChainCount SHALL return the same
     * value regardless of which member of the chain is used as the input.
     *
     * **Validates: Requirements 13.5**
     */
public function test_property13_chain_count_consistent_across_chain_members(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $user = User::factory()->create(['role_id' => 3]);

            $chainLength = random_int(2, 4); // 1 root + 1-3 resubmissions

            $root = Akreditasi::create([
                'user_id' => $user->id,
                'status' => AkreditasiStateMachine::STATUS_DITOLAK,
            ]);

            $chainMembers = [$root];

            for ($j = 0; $j < $chainLength - 1; $j++) {
                $child = Akreditasi::create([
                    'user_id' => $user->id,
                    'parent' => $root->id,
                    'status' => AkreditasiStateMachine::STATUS_DITOLAK,
                ]);
                $chainMembers[] = $child;
            }

            $expectedCount = $chainLength - 1; // root has no parent, rest do

            // Query from each chain member — all should return the same count
            foreach ($chainMembers as $member) {
                $count = $this->service->getChainCount($member->id);
                $this->assertSame(
                    $expectedCount,
                    $count,
                    "Iteration {$i}: getChainCount from member #{$member->id} should return {$expectedCount}"
                );
            }
        }
    }

    /**
     * Property 13 — 30-day cooling period enforced:
     *
     * When the rejection is less than 30 days ago, canResubmit SHALL return
     * can=false with days_remaining > 0.
     *
     * **Validates: Requirements 13.6, 13.9**
     */
public function test_property13_cooling_period_enforced(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Random days ago within the cooling period (1 to 29 days)
            $daysAgo = random_int(1, 29);

            $user = User::factory()->create(['role_id' => 3]);

            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => AkreditasiStateMachine::STATUS_DITOLAK,
            ]);

            // Set rejection date to $daysAgo days ago (within 30-day cooling period)
            DB::table('akreditasis')
                ->where('id', $akreditasi->id)
                ->update(['updated_at' => Carbon::now()->subDays($daysAgo)]);

            $result = $this->service->canResubmit($akreditasi->id);

            $this->assertFalse(
                $result['can'],
                "Iteration {$i}: canResubmit should return can=false when only {$daysAgo} days have passed (< 30)"
            );

            $this->assertNotNull(
                $result['reason'],
                "Iteration {$i}: canResubmit should provide a reason"
            );

            $this->assertNotNull(
                $result['days_remaining'],
                "Iteration {$i}: days_remaining should be provided when cooling period is active"
            );

            $this->assertGreaterThan(
                0,
                $result['days_remaining'],
                "Iteration {$i}: days_remaining should be > 0 when cooling period is active"
            );
        }
    }

    /**
     * Property 13 — Cooling period elapsed allows resubmission:
     *
     * When the rejection is 30 or more days ago and chain count < 3,
     * canResubmit SHALL return can=true.
     *
     * **Validates: Requirements 13.4, 13.6**
     */
public function test_property13_cooling_period_elapsed_allows_resubmission(): void
    {
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            // Random days ago past the cooling period (30 to 365 days)
            $daysAgo = random_int(30, 365);

            $user = User::factory()->create(['role_id' => 3]);

            $akreditasi = Akreditasi::create([
                'user_id' => $user->id,
                'status' => AkreditasiStateMachine::STATUS_DITOLAK,
            ]);

            // Set rejection date to $daysAgo days ago (past 30-day cooling period)
            DB::table('akreditasis')
                ->where('id', $akreditasi->id)
                ->update(['updated_at' => Carbon::now()->subDays($daysAgo)]);

            $result = $this->service->canResubmit($akreditasi->id);

            $this->assertTrue(
                $result['can'],
                "Iteration {$i}: canResubmit should return can=true when {$daysAgo} days have passed (>= 30) and count < 3. Reason: " . ($result['reason'] ?? 'none')
            );

            $this->assertNull(
                $result['reason'],
                "Iteration {$i}: reason should be null when resubmission is allowed"
            );

            $this->assertNull(
                $result['days_remaining'],
                "Iteration {$i}: days_remaining should be null when resubmission is allowed"
            );
        }
    }
}
