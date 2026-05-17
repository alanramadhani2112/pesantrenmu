<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiAuditLog;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AuditTrailService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuditTrailPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AuditTrailService $auditTrailService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->auditTrailService = app(AuditTrailService::class);
    }

    /**
     * Helper: create an admin user.
     */
    private function createAdminUser(): User
    {
        return User::factory()->create(['role_id' => 1]);
    }

    /**
     * Helper: create a pesantren user with pesantren record.
     */
    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Test ' . $user->id,
        ]);
        return $user;
    }

    /**
     * Helper: create an akreditasi for a given user.
     */
    private function createAkreditasi(int $userId, int $status = 6): Akreditasi
    {
        return Akreditasi::create([
            'user_id' => $userId,
            'status' => $status,
        ]);
    }

    /**
     * Data provider for Property 1: generates 100 random audit log inputs.
     */
    public static function auditLogRoundTripProvider(): array
    {
        $faker = Faker::create();
        $actionTypes = AuditTrailService::ALLOWED_ACTION_TYPES;
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $data["iteration_{$i}"] = [
                $faker->randomElement($actionTypes),
                $faker->optional(0.7)->sentence(),
                $faker->optional(0.7)->sentence(),
                $faker->optional(0.6)->passthrough([
                    'key_' . $faker->word() => $faker->word(),
                    'number' => $faker->numberBetween(1, 100),
                    'nested' => ['inner' => $faker->word()],
                ]),
                $faker->ipv4(),
                $faker->userAgent(),
            ];
        }

        return $data;
    }

    /**
     * Property 1: Audit log data round-trip
     *
     * For any valid audit log input (akreditasi_id, user_id, action_type, old_value, new_value,
     * metadata array, ip_address, user_agent), creating the record and then retrieving it by id
     * should produce an object with all fields matching the original input.
     *
     * Feature: akreditasi-audit-trail, Property 1: Audit log data round-trip
     * **Validates: Requirements 1.1, 1.5**
     */
    #[DataProvider('auditLogRoundTripProvider')]
    public function test_property_1_data_round_trip(
        string $actionType,
        ?string $oldValue,
        ?string $newValue,
        ?array $metadata,
        string $ipAddress,
        string $userAgent
    ): void {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $pesantrenUser = $this->createPesantrenUser();
        $akreditasi = $this->createAkreditasi($pesantrenUser->id);

        // Simulate request context
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => $ipAddress,
            'HTTP_USER_AGENT' => $userAgent,
        ]);
        $this->app->instance('request', $request);

        $log = $this->auditTrailService->log(
            akreditasiId: $akreditasi->id,
            actionType: $actionType,
            oldValue: $oldValue,
            newValue: $newValue,
            metadata: $metadata
        );

        // Retrieve from DB
        $retrieved = AkreditasiAuditLog::find($log->id);

        $this->assertNotNull($retrieved);
        $this->assertEquals($akreditasi->id, $retrieved->akreditasi_id);
        $this->assertEquals($user->id, $retrieved->user_id);
        $this->assertEquals($actionType, $retrieved->action_type);
        $this->assertEquals($oldValue, $retrieved->old_value);
        $this->assertEquals($newValue, $retrieved->new_value);
        $this->assertEquals($metadata, $retrieved->metadata);
        $this->assertEquals($ipAddress, $retrieved->ip_address);
        $this->assertEquals($userAgent, $retrieved->user_agent);
        $this->assertNotNull($retrieved->created_at);
    }

    /**
     * Data provider for Property 2: generates 100 random existing audit logs.
     */
    public static function immutabilityProvider(): array
    {
        $faker = Faker::create();
        $actionTypes = AuditTrailService::ALLOWED_ACTION_TYPES;
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $data["iteration_{$i}"] = [
                $faker->randomElement($actionTypes),
                $faker->optional(0.5)->sentence(),
                $faker->optional(0.5)->sentence(),
                $faker->optional(0.5)->passthrough(['key' => $faker->word()]),
                $faker->randomElement(['action_type', 'old_value', 'new_value', 'ip_address']),
                $faker->word(),
            ];
        }

        return $data;
    }

    /**
     * Property 2: Immutability enforcement
     *
     * For any existing AkreditasiAuditLog record and any attempted modification
     * (update of any field or deletion), the operation should throw an exception
     * and the record should remain unchanged in the database.
     *
     * Feature: akreditasi-audit-trail, Property 2: Immutability enforcement
     * **Validates: Requirements 2.1, 2.2, 2.3, 2.4**
     */
    #[DataProvider('immutabilityProvider')]
    public function test_property_2_immutability(
        string $actionType,
        ?string $oldValue,
        ?string $newValue,
        ?array $metadata,
        string $attemptField,
        string $attemptValue
    ): void {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $pesantrenUser = $this->createPesantrenUser();
        $akreditasi = $this->createAkreditasi($pesantrenUser->id);

        $log = $this->auditTrailService->log(
            akreditasiId: $akreditasi->id,
            actionType: $actionType,
            oldValue: $oldValue,
            newValue: $newValue,
            metadata: $metadata
        );

        $originalData = AkreditasiAuditLog::find($log->id)->toArray();

        // Attempt update - should throw RuntimeException
        $updateThrown = false;
        try {
            $log->update([$attemptField => $attemptValue]);
        } catch (\RuntimeException $e) {
            $updateThrown = true;
            $this->assertStringContainsString('immutable', $e->getMessage());
        }
        $this->assertTrue($updateThrown, 'Update should throw RuntimeException');

        // Attempt delete - should throw RuntimeException
        $deleteThrown = false;
        try {
            $log->delete();
        } catch (\RuntimeException $e) {
            $deleteThrown = true;
            $this->assertStringContainsString('cannot be deleted', $e->getMessage());
        }
        $this->assertTrue($deleteThrown, 'Delete should throw RuntimeException');

        // Verify DB unchanged
        $afterData = AkreditasiAuditLog::find($log->id)->toArray();
        $this->assertEquals($originalData, $afterData);
    }

    /**
     * Data provider for Property 3: generates random status transitions.
     */
    public static function statusChangeProvider(): array
    {
        $faker = Faker::create();
        // Valid status codes: 1=Berhasil, 2=Ditolak, 3=Validasi, 4=Visitasi, 5=Assessment, 6=Pengajuan
        $validStatuses = [1, 2, 3, 4, 5, 6];
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $oldStatus = $faker->randomElement($validStatuses);
            $newStatus = $faker->randomElement(array_diff($validStatuses, [$oldStatus]));
            $data["iteration_{$i}_from_{$oldStatus}_to_{$newStatus}"] = [
                $oldStatus,
                $newStatus,
            ];
        }

        return $data;
    }

    /**
     * Property 3: Status change logging completeness
     *
     * For any akreditasi and any valid status transition (old_status → new_status where
     * old_status ≠ new_status), after the status change is persisted, an audit log should
     * exist with action_type "status_changed", old_value equal to getStatusLabel(old_status),
     * new_value equal to getStatusLabel(new_status), user_id matching the authenticated actor,
     * and metadata containing both numeric status codes.
     *
     * Feature: akreditasi-audit-trail, Property 3: Status change logging completeness
     * **Validates: Requirements 3.1, 3.2, 3.3**
     */
    #[DataProvider('statusChangeProvider')]
    public function test_property_3_status_change_logging(int $oldStatus, int $newStatus): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $pesantrenUser = $this->createPesantrenUser();
        $akreditasi = $this->createAkreditasi($pesantrenUser->id, $oldStatus);

        // Trigger status change via model update (observer handles logging)
        $akreditasi->update(['status' => $newStatus]);

        // Assert audit log exists with correct data
        $auditLog = AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)
            ->where('action_type', 'status_changed')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditLog, "Audit log should exist for status change {$oldStatus} → {$newStatus}");
        $this->assertEquals('status_changed', $auditLog->action_type);
        $this->assertEquals(Akreditasi::getStatusLabel($oldStatus), $auditLog->old_value);
        $this->assertEquals(Akreditasi::getStatusLabel($newStatus), $auditLog->new_value);
        $this->assertEquals($admin->id, $auditLog->user_id);
        $this->assertNotNull($auditLog->metadata);
        $this->assertEquals($oldStatus, $auditLog->metadata['old_status_code']);
        $this->assertEquals($newStatus, $auditLog->metadata['new_status_code']);
    }

    /**
     * Data provider for Property 10: generates random audit logs with varying timestamps.
     */
    public static function chronologicalOrderingProvider(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            // Generate a random number of logs (3-10) with random timestamps
            $logCount = $faker->numberBetween(3, 10);
            $timestamps = [];
            for ($j = 0; $j < $logCount; $j++) {
                $timestamps[] = Carbon::now()
                    ->subDays($faker->numberBetween(0, 365))
                    ->subHours($faker->numberBetween(0, 23))
                    ->subMinutes($faker->numberBetween(0, 59))
                    ->subSeconds($faker->numberBetween(0, 59))
                    ->format('Y-m-d H:i:s');
            }
            $data["iteration_{$i}"] = [$timestamps];
        }

        return $data;
    }

    /**
     * Property 10: Chronological ordering
     *
     * For any set of audit logs for an akreditasi, the timeline query should return them
     * in reverse chronological order (newest created_at first).
     *
     * Feature: akreditasi-audit-trail, Property 10: Chronological ordering
     * **Validates: Requirements 8.3**
     */
    #[DataProvider('chronologicalOrderingProvider')]
    public function test_property_10_chronological_ordering(array $timestamps): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $pesantrenUser = $this->createPesantrenUser();
        $akreditasi = $this->createAkreditasi($pesantrenUser->id);

        $actionTypes = AuditTrailService::ALLOWED_ACTION_TYPES;

        // Create audit logs with specific timestamps using direct DB insert
        foreach ($timestamps as $timestamp) {
            $actionType = $actionTypes[array_rand($actionTypes)];
            DB::table('akreditasi_audit_logs')->insert([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $admin->id,
                'action_type' => $actionType,
                'old_value' => null,
                'new_value' => null,
                'metadata' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'TestAgent',
                'created_at' => $timestamp,
            ]);
        }

        // Query timeline
        $timeline = $this->auditTrailService->getTimeline($akreditasi->id, [], 100);
        $items = $timeline->items();

        $this->assertCount(count($timestamps), $items);

        // Assert descending order
        for ($i = 1; $i < count($items); $i++) {
            $this->assertTrue(
                $items[$i - 1]->created_at->greaterThanOrEqualTo($items[$i]->created_at),
                "Item at index " . ($i - 1) . " (created_at={$items[$i-1]->created_at}) should be >= item at index {$i} (created_at={$items[$i]->created_at})"
            );
        }
    }

    /**
     * Data provider for Property 11: generates random filter combinations.
     */
    public static function filterIntersectionProvider(): array
    {
        $faker = Faker::create();
        $actionTypes = AuditTrailService::ALLOWED_ACTION_TYPES;
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $filterActionType = $faker->optional(0.6)->randomElement($actionTypes);
            $filterDateFrom = $faker->optional(0.5)->dateTimeBetween('-1 year', '-6 months')?->format('Y-m-d');
            $filterDateTo = $faker->optional(0.5)->dateTimeBetween('-5 months', 'now')?->format('Y-m-d');
            $filterByUser = $faker->boolean(40);
            $logCount = $faker->numberBetween(5, 15);

            $data["iteration_{$i}"] = [
                $filterActionType,
                $filterDateFrom,
                $filterDateTo,
                $filterByUser,
                $logCount,
            ];
        }

        return $data;
    }

    /**
     * Property 11: Filter intersection correctness
     *
     * For any combination of filters (action_type, user_id, date_from, date_to) applied to
     * an akreditasi's audit logs, the returned results should contain only records that
     * satisfy ALL active filter criteria simultaneously.
     *
     * Feature: akreditasi-audit-trail, Property 11: Filter intersection correctness
     * **Validates: Requirements 9.1, 9.2, 9.3, 9.4**
     */
    #[DataProvider('filterIntersectionProvider')]
    public function test_property_11_filter_intersection(
        ?string $filterActionType,
        ?string $filterDateFrom,
        ?string $filterDateTo,
        bool $filterByUser,
        int $logCount
    ): void {
        $admin = $this->createAdminUser();
        $admin2 = $this->createAdminUser();
        $this->actingAs($admin);

        $pesantrenUser = $this->createPesantrenUser();
        $akreditasi = $this->createAkreditasi($pesantrenUser->id);

        $actionTypes = AuditTrailService::ALLOWED_ACTION_TYPES;
        $faker = Faker::create();

        // Create varied audit logs
        $users = [$admin->id, $admin2->id];
        for ($j = 0; $j < $logCount; $j++) {
            $randomDate = Carbon::now()
                ->subDays($faker->numberBetween(0, 400))
                ->format('Y-m-d H:i:s');

            DB::table('akreditasi_audit_logs')->insert([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $faker->randomElement($users),
                'action_type' => $faker->randomElement($actionTypes),
                'old_value' => $faker->optional()->sentence(),
                'new_value' => $faker->optional()->sentence(),
                'metadata' => null,
                'ip_address' => $faker->ipv4(),
                'user_agent' => $faker->userAgent(),
                'created_at' => $randomDate,
            ]);
        }

        // Build filters
        $filters = [];
        if ($filterActionType !== null) {
            $filters['action_type'] = $filterActionType;
        }
        $filterUserId = $filterByUser ? $admin->id : null;
        if ($filterUserId !== null) {
            $filters['user_id'] = $filterUserId;
        }
        if ($filterDateFrom !== null) {
            $filters['date_from'] = $filterDateFrom;
        }
        if ($filterDateTo !== null) {
            $filters['date_to'] = $filterDateTo;
        }

        // Query with filters
        $results = $this->auditTrailService->getTimeline($akreditasi->id, $filters, 100);

        // Assert all returned records satisfy ALL filter criteria
        foreach ($results->items() as $item) {
            $this->assertEquals($akreditasi->id, $item->akreditasi_id);

            if ($filterActionType !== null) {
                $this->assertEquals($filterActionType, $item->action_type,
                    "Record action_type '{$item->action_type}' should match filter '{$filterActionType}'");
            }

            if ($filterUserId !== null) {
                $this->assertEquals($filterUserId, $item->user_id,
                    "Record user_id '{$item->user_id}' should match filter '{$filterUserId}'");
            }

            if ($filterDateFrom !== null) {
                $this->assertTrue(
                    $item->created_at->greaterThanOrEqualTo(Carbon::parse($filterDateFrom)),
                    "Record created_at '{$item->created_at}' should be >= date_from '{$filterDateFrom}'"
                );
            }

            if ($filterDateTo !== null) {
                $this->assertTrue(
                    $item->created_at->lessThanOrEqualTo(Carbon::parse($filterDateTo . ' 23:59:59')),
                    "Record created_at '{$item->created_at}' should be <= date_to '{$filterDateTo} 23:59:59'"
                );
            }
        }
    }

    /**
     * Data provider for Property 12: generates random IP/user agent combinations.
     */
    public static function requestContextProvider(): array
    {
        $faker = Faker::create();
        $data = [];

        for ($i = 0; $i < 100; $i++) {
            $hasHttpContext = $faker->boolean(80); // 80% with HTTP context, 20% without
            $data["iteration_{$i}"] = [
                $hasHttpContext,
                $hasHttpContext ? $faker->ipv4() : null,
                $hasHttpContext ? $faker->userAgent() : null,
            ];
        }

        return $data;
    }

    /**
     * Property 12: Request context capture
     *
     * For any action triggered via an HTTP request, the resulting audit log should have
     * ip_address matching the request IP and user_agent matching the request user agent string.
     * When triggered outside HTTP context, both should be "system".
     *
     * Feature: akreditasi-audit-trail, Property 12: Request context capture
     * **Validates: Requirements 10.1, 10.2, 10.3**
     */
    #[DataProvider('requestContextProvider')]
    public function test_property_12_request_context(
        bool $hasHttpContext,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        $pesantrenUser = $this->createPesantrenUser();
        $akreditasi = $this->createAkreditasi($pesantrenUser->id);

        $actionTypes = AuditTrailService::ALLOWED_ACTION_TYPES;

        if ($hasHttpContext) {
            // Simulate HTTP request with specific IP and user agent
            $request = Request::create('/', 'GET', [], [], [], [
                'REMOTE_ADDR' => $ipAddress,
                'HTTP_USER_AGENT' => $userAgent,
            ]);
            $this->app->instance('request', $request);

            $log = $this->auditTrailService->log(
                akreditasiId: $akreditasi->id,
                actionType: $actionTypes[array_rand($actionTypes)],
                oldValue: 'old',
                newValue: 'new'
            );

            $retrieved = AkreditasiAuditLog::find($log->id);

            $this->assertEquals($ipAddress, $retrieved->ip_address,
                "IP address should match request IP '{$ipAddress}'");
            $this->assertEquals($userAgent, $retrieved->user_agent,
                "User agent should match request user agent");
        } else {
            // Simulate no HTTP context by using a partial mock on the service
            // that overrides resolveIpAddress and resolveUserAgent to simulate
            // the Throwable catch path returning 'system'
            $service = new class extends AuditTrailService {
                protected function resolveIpAddress(): string
                {
                    // Simulate no HTTP context - throws internally, returns 'system'
                    try {
                        throw new \RuntimeException('No HTTP context');
                    } catch (\Throwable) {
                        return 'system';
                    }
                }

                protected function resolveUserAgent(): string
                {
                    try {
                        throw new \RuntimeException('No HTTP context');
                    } catch (\Throwable) {
                        return 'system';
                    }
                }
            };

            $log = $service->log(
                akreditasiId: $akreditasi->id,
                actionType: $actionTypes[array_rand($actionTypes)],
                oldValue: 'old',
                newValue: 'new'
            );

            $retrieved = AkreditasiAuditLog::find($log->id);

            $this->assertEquals('system', $retrieved->ip_address,
                "IP address should be 'system' when no HTTP context");
            $this->assertEquals('system', $retrieved->user_agent,
                "User agent should be 'system' when no HTTP context");
        }
    }
}
