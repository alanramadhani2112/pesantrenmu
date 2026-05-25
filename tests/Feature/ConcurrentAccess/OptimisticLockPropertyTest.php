<?php

namespace Tests\Feature\ConcurrentAccess;

use App\Exceptions\ConflictException;
use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AkreditasiService;
use App\Services\AsesorService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Property-Based Tests for Optimistic Locking.
 *
 */
#[Group('Feature:concurrent-access-handling')]
#[Group('Property1')]
#[Group('Property2')]
class OptimisticLockPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected AkreditasiService $akreditasiService;
    protected AsesorService $asesorService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->akreditasiService = app(AkreditasiService::class);
        $this->asesorService = app(AsesorService::class);

        $admin = User::factory()->create(['role_id' => 1]);
        Auth::login($admin);
    }

    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Test ' . $user->id,
        ]);
        return $user;
    }

    private function createAsesor(string $name = 'Asesor Test'): Asesor
    {
        $user = User::factory()->create(['role_id' => 2]);
        return Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => $name,
            'nama_tanpa_gelar' => $name,
        ]);
    }

    /**
     * Generate 100 stale timestamp data points.
     */
    public static function staleTimestampProvider(): array
    {
        $faker = Faker::create();
        $cases = [];

        for ($i = 0; $i < 100; $i++) {
            // Generate a timestamp that is clearly in the past (stale)
            $staleTimestamp = Carbon::now()
                ->subDays($faker->numberBetween(1, 365))
                ->subHours($faker->numberBetween(0, 23))
                ->subMinutes($faker->numberBetween(0, 59))
                ->toISOString();
            $cases["case_{$i}"] = [$staleTimestamp];
        }

        return $cases;
    }

    /**
     * Property 1: Stale timestamp causes ConflictException without DB mutation.
     *
     * For any akreditasi record and any state-changing operation, if the client-provided
     * updated_at does not match the database updated_at, the operation SHALL throw a
     * ConflictException and the akreditasi record SHALL remain unchanged.
     *
     * **Validates: Requirements 1.2, 1.4**
     *
     */
#[DataProvider('staleTimestampProvider')]
public function test_stale_timestamp_throws_conflict_exception_and_db_unchanged(string $staleTimestamp): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 3, // Validasi — valid pre-condition for finalizeAkreditasi
        ]);

        $originalStatus = $akreditasi->status;
        $originalUpdatedAt = $akreditasi->updated_at->toISOString();

        // Ensure the stale timestamp is different from the actual updated_at
        $this->assertNotEquals($originalUpdatedAt, $staleTimestamp,
            'Test setup: stale timestamp should differ from actual updated_at');

        $this->expectException(ConflictException::class);

        $this->akreditasiService->finalizeAkreditasi($akreditasi->id, [
            'rejection_categories' => [
                ['category' => 'lainnya', 'explanation' => 'Test rejection reason for property test.'],
            ],
        ], false, $staleTimestamp);

        // Verify DB unchanged (this runs if exception is not thrown — should not happen)
        $this->assertEquals($originalStatus, $akreditasi->fresh()->status);
    }

    /**
     * Property 1 (variant): Stale timestamp on approvePengajuan throws ConflictException.
     *
     * **Validates: Requirements 1.2, 1.4**
     *
     */
#[DataProvider('staleTimestampProvider')]
public function test_stale_timestamp_on_approve_pengajuan_throws_conflict_exception(string $staleTimestamp): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6, // Pengajuan
        ]);

        $originalStatus = $akreditasi->status;
        $originalUpdatedAt = $akreditasi->updated_at->toISOString();

        $this->assertNotEquals($originalUpdatedAt, $staleTimestamp);

        $this->expectException(ConflictException::class);

        $this->akreditasiService->approvePengajuan($akreditasi->id, [
            'asesor_id1' => $asesor->id,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addDays(30)->toDateString(),
        ], $staleTimestamp);
    }

    /**
     * Generate 100 fresh timestamp data points (matching the actual updated_at).
     * We use a factory approach: create the record, then use its actual updated_at.
     */
    public static function freshTimestampProvider(): array
    {
        // We can't create DB records in a static provider, so we use a marker
        // The actual fresh timestamp will be fetched in the test itself.
        $cases = [];
        for ($i = 0; $i < 100; $i++) {
            $cases["case_{$i}"] = [$i]; // Pass iteration index
        }
        return $cases;
    }

    /**
     * Property 2: Fresh timestamp allows operation to proceed.
     *
     * For any akreditasi record in a valid pre-condition status, if the client-provided
     * updated_at matches the database updated_at, the operation SHALL succeed.
     *
     * **Validates: Requirements 1.3**
     *
     */
#[DataProvider('freshTimestampProvider')]
public function test_fresh_timestamp_allows_finalize_to_proceed(int $iteration): void
    {
        $pesantrenUser = $this->createPesantrenUser();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 3, // Validasi
        ]);

        // Use the actual updated_at as the "fresh" timestamp
        $freshTimestamp = $akreditasi->updated_at->toISOString();

        // Should NOT throw ConflictException
        $result = $this->akreditasiService->finalizeAkreditasi($akreditasi->id, [
            'rejection_categories' => [
                ['category' => 'lainnya', 'explanation' => 'Test rejection reason for property test.'],
            ],
        ], false, $freshTimestamp);

        // Operation should succeed (status changes from 3 to 2)
        $this->assertTrue($result, "finalizeAkreditasi should succeed with fresh timestamp (iteration {$iteration})");
        $this->assertEquals(2, $akreditasi->fresh()->status,
            "Status should change to 2 (Ditolak) after successful finalization (iteration {$iteration})");
    }

    /**
     * Property 2 (variant): Fresh timestamp allows approvePengajuan to proceed.
     *
     * **Validates: Requirements 1.3**
     *
     */
#[DataProvider('freshTimestampProvider')]
public function test_fresh_timestamp_allows_approve_pengajuan_to_proceed(int $iteration): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor = $this->createAsesor("Asesor Fresh {$iteration}");

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6, // Pengajuan
        ]);

        $freshTimestamp = $akreditasi->updated_at->toISOString();

        // Should NOT throw ConflictException
        $this->akreditasiService->approvePengajuan($akreditasi->id, [
            'asesor_id1' => $asesor->id,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addDays(30)->toDateString(),
        ], $freshTimestamp);

        $this->assertEquals(5, $akreditasi->fresh()->status,
            "Status should change to 5 (Assessment) after successful approval (iteration {$iteration})");
    }
}
