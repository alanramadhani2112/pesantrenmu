<?php

namespace Tests\Feature\Trash;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\TrashService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Feature: soft-delete-restore-flow
 * Property 6: Trash count reflects actual state.
 *
 * For any sequence of soft-delete, restore, and force-delete operations on
 * akreditasi records, the trash count returned by getTrashCount() SHALL equal
 * the actual number of records in the akreditasis table where deleted_at IS NOT NULL.
 */
class TrashCountPropertyTest extends TestCase
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

    private function makeAkreditasi(): Akreditasi
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => 'Pesantren Count Test']);
        return Akreditasi::create(['user_id' => $user->id, 'status' => 6]);
    }

    private function assertCountMatchesDb(): void
    {
        $serviceCount = $this->service->getTrashCount();
        $dbCount = Akreditasi::onlyTrashed()->count();
        $this->assertSame($dbCount, $serviceCount, "getTrashCount() ({$serviceCount}) does not match DB count ({$dbCount})");
    }

    public static function operationSequenceProvider(): array
    {
        $faker = \Faker\Factory::create();
        $cases = [];
        for ($i = 0; $i < 100; $i++) {
            // Random sequence: how many to create, delete, restore, force-delete
            $total = $faker->numberBetween(1, 5);
            $toDelete = $faker->numberBetween(1, $total);
            $toRestore = $faker->numberBetween(0, $toDelete);
            $toForceDelete = $faker->numberBetween(0, $toDelete - $toRestore);
            $cases[] = [$total, $toDelete, $toRestore, $toForceDelete];
        }
        return $cases;
    }

    /**     */
#[DataProvider('operationSequenceProvider')]
public function test_property_6_trash_count_reflects_actual_state(
        int $total,
        int $toDelete,
        int $toRestore,
        int $toForceDelete
    ): void {
        // Create records
        $akreditasis = [];
        for ($i = 0; $i < $total; $i++) {
            $akreditasis[] = $this->makeAkreditasi();
        }
        $this->assertCountMatchesDb();

        // Soft-delete some
        $deleted = [];
        for ($i = 0; $i < $toDelete; $i++) {
            $akreditasis[$i]->delete();
            $deleted[] = $akreditasis[$i]->fresh();
        }
        $this->assertCountMatchesDb();

        // Restore some
        for ($i = 0; $i < $toRestore; $i++) {
            $this->service->restore($deleted[$i]->id);
        }
        $this->assertCountMatchesDb();

        // Force-delete some (from remaining deleted)
        $remaining = array_slice($deleted, $toRestore);
        for ($i = 0; $i < $toForceDelete && $i < count($remaining); $i++) {
            $this->service->forceDelete($remaining[$i]->id);
        }
        $this->assertCountMatchesDb();
    }

    public function test_initial_count_is_zero(): void
    {
        $this->assertSame(0, $this->service->getTrashCount());
    }

    public function test_count_increments_on_delete(): void
    {
        $a = $this->makeAkreditasi();
        $this->assertSame(0, $this->service->getTrashCount());
        $a->delete();
        $this->assertSame(1, $this->service->getTrashCount());
    }

    public function test_count_decrements_on_restore(): void
    {
        $a = $this->makeAkreditasi();
        $a->delete();
        $this->assertSame(1, $this->service->getTrashCount());
        $this->service->restore($a->id);
        $this->assertSame(0, $this->service->getTrashCount());
    }

    public function test_count_decrements_on_force_delete(): void
    {
        $a = $this->makeAkreditasi();
        $a->delete();
        $this->assertSame(1, $this->service->getTrashCount());
        $this->service->forceDelete($a->id);
        $this->assertSame(0, $this->service->getTrashCount());
    }
}
