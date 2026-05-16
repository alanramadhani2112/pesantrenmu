<?php

namespace Tests\Unit;

use App\Models\Akreditasi;
use App\Models\User;
use App\Services\ResubmissionService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ResubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ResubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = app(ResubmissionService::class);
    }

    /**
     * Property 1: Chain count accuracy
     * For any akreditasi chain of arbitrary depth (including chains with soft-deleted entries),
     * countChainResubmissions SHALL return exactly the number of records in the chain
     * that have a non-null parent field.
     *
     * **Validates: Requirements 1.3, 3.1, 3.2, 3.3**
     *
     * @dataProvider chainCountDataProvider
     */
    public function test_property_chain_count_accuracy(int $depth, array $softDeleteIndices): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        // Create chain of given depth
        $root = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => null,
        ]);

        $chain = [$root];
        $current = $root;

        for ($i = 1; $i <= $depth; $i++) {
            $child = Akreditasi::create([
                'user_id' => $user->id,
                'status' => $i === $depth ? 6 : 2,
                'parent' => $current->id,
            ]);
            $chain[] = $child;
            $current = $child;
        }

        // Soft-delete specified indices
        foreach ($softDeleteIndices as $index) {
            if ($index > 0 && $index < count($chain)) {
                $chain[$index]->delete();
            }
        }

        // The last item in the chain is the "current" submission's parent
        // We pass the parent of the potential next submission
        $lastId = $chain[count($chain) - 1]->id;

        $count = $this->service->countChainResubmissions($lastId);

        // Expected: number of records with non-null parent = depth
        $this->assertSame($depth, $count);
    }

    public static function chainCountDataProvider(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $depth = $faker->numberBetween(1, 10);
            // Generate random soft-delete indices (some entries in the chain)
            $softDeleteCount = $faker->numberBetween(0, max(0, $depth - 1));
            $softDeleteIndices = [];
            if ($softDeleteCount > 0 && $depth > 1) {
                $possibleIndices = range(1, $depth - 1);
                shuffle($possibleIndices);
                $softDeleteIndices = array_slice($possibleIndices, 0, $softDeleteCount);
            }

            $data["iteration_$i depth=$depth deletes=" . count($softDeleteIndices)] = [
                $depth,
                $softDeleteIndices,
            ];
        }

        return $data;
    }

    /**
     * Property 2: Limit enforcement
     * For any akreditasi chain where the resubmission count >= configured limit,
     * checkResubmissionEligibility SHALL return allowed=false with error_code='limit_reached'.
     *
     * **Validates: Requirements 1.4, 1.5**
     *
     * @dataProvider limitEnforcementDataProvider
     */
    public function test_property_limit_enforcement(int $chainDepth, int $limit): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        // Set the limit config
        config(['akreditasi.resubmission_limit' => $limit]);
        // Disable cooling period to isolate limit testing
        config(['akreditasi.cooling_period_days' => 0]);

        // Create chain of given depth
        $root = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => null,
        ]);

        $current = $root;
        for ($i = 1; $i <= $chainDepth; $i++) {
            $child = Akreditasi::create([
                'user_id' => $user->id,
                'status' => 2,
                'parent' => $current->id,
            ]);
            $current = $child;
        }

        $result = $this->service->checkResubmissionEligibility($current->id);

        // chainDepth = number of resubmissions (records with non-null parent)
        if ($chainDepth >= $limit) {
            $this->assertFalse($result['allowed']);
            $this->assertSame('limit_reached', $result['error_code']);
            $this->assertSame($chainDepth, $result['error_data']['count']);
            $this->assertSame($limit, $result['error_data']['limit']);
        } else {
            $this->assertTrue($result['allowed']);
            $this->assertNull($result['error_code']);
        }
    }

    public static function limitEnforcementDataProvider(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $limit = $faker->numberBetween(0, 5);
            // Generate chain depths that are both above and below the limit
            $chainDepth = $faker->numberBetween(0, 8);

            $data["iteration_$i depth=$chainDepth limit=$limit"] = [
                $chainDepth,
                $limit,
            ];
        }

        return $data;
    }

    /**
     * Property 3: Cooling period enforcement
     * For any rejected akreditasi where current date < rejection_date + cooling_period_days
     * (and cooling_period_days > 0), checkResubmissionEligibility SHALL return
     * allowed=false with error_code='cooling_period'.
     *
     * **Validates: Requirements 2.4, 2.6**
     *
     * @dataProvider coolingPeriodDataProvider
     */
    public function test_property_cooling_period_enforcement(int $daysAgoRejected, int $coolingDays): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        // Set high limit so we only test cooling
        config(['akreditasi.resubmission_limit' => 100]);
        config(['akreditasi.cooling_period_days' => $coolingDays]);

        $now = Carbon::now()->startOfDay();
        Carbon::setTestNow($now);

        $rejectionDate = $now->copy()->subDays($daysAgoRejected);

        $parent = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => null,
        ]);
        // Force updated_at using query builder to bypass Eloquent timestamps
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $parent->id)
            ->update(['updated_at' => $rejectionDate]);

        $result = $this->service->checkResubmissionEligibility($parent->id);

        if ($coolingDays === 0) {
            // Cooling period disabled — should be allowed
            $this->assertTrue($result['allowed']);
            $this->assertNull($result['error_code']);
        } elseif ($daysAgoRejected < $coolingDays) {
            // Cooling period not elapsed
            $this->assertFalse($result['allowed']);
            $this->assertSame('cooling_period', $result['error_code']);
            $this->assertGreaterThan(0, $result['error_data']['remaining_days']);
        } else {
            // Cooling period elapsed
            $this->assertTrue($result['allowed']);
            $this->assertNull($result['error_code']);
        }

        Carbon::setTestNow();
    }

    public static function coolingPeriodDataProvider(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $coolingDays = $faker->numberBetween(0, 90);
            $daysAgoRejected = $faker->numberBetween(0, 120);

            $data["iteration_$i rejected={$daysAgoRejected}d_ago cooling={$coolingDays}d"] = [
                $daysAgoRejected,
                $coolingDays,
            ];
        }

        return $data;
    }

    /**
     * Property 4: Remaining days calculation
     * For any rejected akreditasi with a known rejection date and configured cooling period,
     * getResubmissionStatus SHALL return cooling_remaining_days equal to
     * max(0, (rejection_date + cooling_period_days) - current_date) in days.
     *
     * **Validates: Requirements 2.5**
     *
     * @dataProvider remainingDaysDataProvider
     */
    public function test_property_remaining_days_calculation(int $daysAgoRejected, int $coolingDays): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        config(['akreditasi.resubmission_limit' => 100]);
        config(['akreditasi.cooling_period_days' => $coolingDays]);

        // Fix "now" to avoid timing issues
        $now = Carbon::now()->startOfDay();
        Carbon::setTestNow($now);

        $rejectionDate = $now->copy()->subDays($daysAgoRejected)->startOfDay();

        $parent = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => null,
        ]);
        // Force updated_at using query builder to bypass Eloquent timestamps
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $parent->id)
            ->update(['updated_at' => $rejectionDate]);

        $result = $this->service->getResubmissionStatus($parent->id);

        $expectedRemaining = max(0, $coolingDays - $daysAgoRejected);

        $this->assertSame($expectedRemaining, $result['cooling_remaining_days']);
        $this->assertSame(100, $result['limit']);
        $this->assertSame(0, $result['count']); // root has no resubmissions yet

        if ($expectedRemaining > 0) {
            $expectedEndDate = $rejectionDate->copy()->addDays($coolingDays)->format('Y-m-d');
            $this->assertSame($expectedEndDate, $result['cooling_end_date']);
            $this->assertFalse($result['can_resubmit']);
        } else {
            $this->assertNull($result['cooling_end_date']);
            $this->assertTrue($result['can_resubmit']);
        }

        Carbon::setTestNow(); // Reset
    }

    public static function remainingDaysDataProvider(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $coolingDays = $faker->numberBetween(1, 90);
            $daysAgoRejected = $faker->numberBetween(0, 120);

            $data["iteration_$i rejected={$daysAgoRejected}d_ago cooling={$coolingDays}d"] = [
                $daysAgoRejected,
                $coolingDays,
            ];
        }

        return $data;
    }

    /**
     * Property 5: Limit error message formatting
     * For any resubmission count and limit where count >= limit, the returned error message
     * SHALL contain the exact count and limit values interpolated in the format:
     * "Batas pengajuan ulang telah tercapai ({count}/{limit})".
     *
     * **Validates: Requirements 6.1**
     *
     * @dataProvider limitErrorMessageDataProvider
     */
    public function test_property_limit_error_message_formatting(int $count, int $limit): void
    {
        $errorData = [
            'count' => $count,
            'limit' => $limit,
        ];

        $message = $this->service->getErrorMessage('limit_reached', $errorData);

        // Verify message contains the exact count/limit substring
        $this->assertStringContainsString(
            "{$count}/{$limit}",
            $message,
            "Message should contain '{$count}/{$limit}'"
        );

        // Verify the full expected message format
        $expected = "Batas pengajuan ulang telah tercapai ({$count}/{$limit}). Anda tidak dapat mengajukan ulang untuk akreditasi ini.";
        $this->assertSame($expected, $message);
    }

    public static function limitErrorMessageDataProvider(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $limit = $faker->numberBetween(1, 100);
            $count = $faker->numberBetween($limit, $limit + 50); // count >= limit

            $data["iteration_$i count={$count} limit={$limit}"] = [
                $count,
                $limit,
            ];
        }

        return $data;
    }

    /**
     * Property 6: Cooling error message formatting
     * For any rejection date and cooling period where the cooling period has not elapsed,
     * the returned error message SHALL contain the correct cooling end date and remaining days
     * interpolated in the expected Indonesian format.
     *
     * **Validates: Requirements 6.2**
     *
     * @dataProvider coolingErrorMessageDataProvider
     */
    public function test_property_cooling_error_message_formatting(int $daysAgoRejected, int $coolingDays): void
    {
        // Calculate expected values
        $now = Carbon::now()->startOfDay();
        $rejectionDate = $now->copy()->subDays($daysAgoRejected)->startOfDay();
        $coolingEndDate = $rejectionDate->copy()->addDays($coolingDays);
        $remainingDays = (int) $now->diffInDays($coolingEndDate);

        $errorData = [
            'cooling_end_date' => $coolingEndDate->format('Y-m-d'),
            'remaining_days' => $remainingDays,
        ];

        $message = $this->service->getErrorMessage('cooling_period', $errorData);

        // Verify message contains the correct date
        $this->assertStringContainsString(
            $coolingEndDate->format('Y-m-d'),
            $message,
            "Message should contain the cooling end date"
        );

        // Verify message contains the correct remaining days
        $this->assertStringContainsString(
            "{$remainingDays} hari lagi",
            $message,
            "Message should contain '{$remainingDays} hari lagi'"
        );

        // Verify the full expected message format
        $expected = "Masa tunggu pengajuan ulang belum berakhir. Anda dapat mengajukan ulang pada tanggal {$coolingEndDate->format('Y-m-d')} ({$remainingDays} hari lagi).";
        $this->assertSame($expected, $message);
    }

    public static function coolingErrorMessageDataProvider(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            // Ensure cooling has NOT elapsed: daysAgoRejected < coolingDays
            $coolingDays = $faker->numberBetween(5, 120);
            $daysAgoRejected = $faker->numberBetween(0, $coolingDays - 1);

            $data["iteration_$i rejected={$daysAgoRejected}d_ago cooling={$coolingDays}d"] = [
                $daysAgoRejected,
                $coolingDays,
            ];
        }

        return $data;
    }

    /**
     * Unit test: Circular reference detection
     * Create chain with circular parent, verify traversal halts and logs error.
     */
    public function test_circular_reference_detection_halts_and_logs_error(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $a = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => null,
        ]);

        $b = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => $a->id,
        ]);

        // Create circular reference: a -> b -> a
        $a->update(['parent' => $b->id]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Circular reference detected');
            });

        $count = $this->service->countChainResubmissions($b->id);

        // Should return 0 when circular reference detected
        $this->assertSame(0, $count);
    }

    /**
     * Unit test: getChainTimeline returns ordered collection from root to leaf.
     */
    public function test_get_chain_timeline_returns_ordered_collection(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $root = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => null,
        ]);

        $child1 = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => $root->id,
        ]);

        $child2 = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 6,
            'parent' => $child1->id,
        ]);

        $timeline = $this->service->getChainTimeline($child2->id);

        $this->assertCount(3, $timeline);
        $this->assertSame($root->id, $timeline[0]->id);
        $this->assertSame($child1->id, $timeline[1]->id);
        $this->assertSame($child2->id, $timeline[2]->id);
    }

    /**
     * Unit test: getChainTimeline includes soft-deleted entries.
     */
    public function test_get_chain_timeline_includes_soft_deleted(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $root = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => null,
        ]);

        $child1 = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => $root->id,
        ]);

        $child2 = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 6,
            'parent' => $child1->id,
        ]);

        // Soft-delete the middle entry
        $child1->delete();

        $timeline = $this->service->getChainTimeline($child2->id);

        $this->assertCount(3, $timeline);
    }

    /**
     * Unit test: checkResubmissionEligibility returns allowed when both conditions met.
     */
    public function test_eligibility_allowed_when_under_limit_and_cooling_elapsed(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        config(['akreditasi.resubmission_limit' => 3]);
        config(['akreditasi.cooling_period_days' => 30]);

        $now = Carbon::now()->startOfDay();
        Carbon::setTestNow($now);

        $parent = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => null,
        ]);
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $parent->id)
            ->update(['updated_at' => $now->copy()->subDays(31)]);

        $result = $this->service->checkResubmissionEligibility($parent->id);

        $this->assertTrue($result['allowed']);
        $this->assertNull($result['error_code']);
        $this->assertEmpty($result['error_data']);

        Carbon::setTestNow();
    }

    /**
     * Unit test: getResubmissionStatus with limit=0 returns can_resubmit=false.
     */
    public function test_status_with_zero_limit_disallows_resubmission(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        config(['akreditasi.resubmission_limit' => 0]);
        config(['akreditasi.cooling_period_days' => 0]);

        $parent = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'parent' => null,
        ]);

        $result = $this->service->getResubmissionStatus($parent->id);

        $this->assertFalse($result['can_resubmit']);
        $this->assertSame(0, $result['limit']);
    }
}
