<?php

namespace Tests\Feature\Trash;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\TrashService;
use Database\Seeders\RoleSeeder;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Feature: soft-delete-restore-flow
 * Property 5: Auto-purge removes only expired records.
 *
 * For any set of soft-deleted akreditasi records with varying deleted_at timestamps,
 * after running auto-purge with a given retention period, ONLY records where
 * deleted_at is older than the retention period SHALL be permanently deleted,
 * and all other trashed records SHALL remain unchanged.
 */
class AutoPurgePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected TrashService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($admin);
        $this->service = app(TrashService::class);
    }

    private function makeTrashedAkreditasiWithAge(int $daysAgo): Akreditasi
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Pesantren Purge Test']);
        $akreditasi = Akreditasi::create(['user_id' => $user->id, 'status' => 6]);
        $akreditasi->delete();

        // Manually set deleted_at to simulate age
        Akreditasi::withTrashed()->where('id', $akreditasi->id)->update([
            'deleted_at' => Carbon::now()->subDays($daysAgo),
        ]);

        return $akreditasi->fresh();
    }

    public static function purgeScenarioProvider(): array
    {
        $faker = Factory::create();
        $cases = [];
        for ($i = 0; $i < 100; $i++) {
            $retentionDays = $faker->numberBetween(10, 90);
            // Some records older than retention (should be purged)
            $expiredDays = $faker->numberBetween($retentionDays + 1, $retentionDays + 60);
            // Some records newer than retention (should NOT be purged)
            $freshDays = $faker->numberBetween(0, $retentionDays - 1);
            $cases[] = [$retentionDays, $expiredDays, $freshDays];
        }

        return $cases;
    }

    #[DataProvider('purgeScenarioProvider')]
    public function test_property_5_purge_removes_only_expired_records(
        int $retentionDays,
        int $expiredDays,
        int $freshDays
    ): void {
        $expired = $this->makeTrashedAkreditasiWithAge($expiredDays);
        $fresh = $this->makeTrashedAkreditasiWithAge($freshDays);

        $result = $this->service->purgeExpired($retentionDays);

        // Expired record should be gone
        $this->assertSame(
            0,
            Akreditasi::withTrashed()->where('id', $expired->id)->count(),
            "Expired record (deleted {$expiredDays} days ago, retention {$retentionDays}) should be purged"
        );

        // Fresh record should still exist in trash
        $this->assertSame(
            1,
            Akreditasi::onlyTrashed()->where('id', $fresh->id)->count(),
            "Fresh record (deleted {$freshDays} days ago, retention {$retentionDays}) should NOT be purged"
        );

        $this->assertSame(1, $result['purged']);
        $this->assertSame(0, $result['failed']);
    }

    public function test_purge_with_no_expired_records_purges_nothing(): void
    {
        $this->makeTrashedAkreditasiWithAge(5);
        $this->makeTrashedAkreditasiWithAge(10);

        $result = $this->service->purgeExpired(90);

        $this->assertSame(0, $result['purged']);
        $this->assertSame(2, $this->service->getTrashCount());
    }

    public function test_purge_with_all_expired_purges_all(): void
    {
        $this->makeTrashedAkreditasiWithAge(100);
        $this->makeTrashedAkreditasiWithAge(200);

        $result = $this->service->purgeExpired(90);

        $this->assertSame(2, $result['purged']);
        $this->assertSame(0, $this->service->getTrashCount());
    }
}
