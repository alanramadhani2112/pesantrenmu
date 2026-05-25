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
 * Property 1: Search filtering returns only matching results.
 *
 * For any search query string and any set of soft-deleted akreditasi records,
 * all records returned by the trash listing query SHALL have a pesantren name
 * containing the search string (case-insensitive).
 */
class SearchFilterPropertyTest extends TestCase
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

    private function makeTrashedAkreditasi(string $pesantrenName): Akreditasi
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create(['user_id' => $user->id, 'nama_pesantren' => $pesantrenName]);
        $akreditasi = Akreditasi::create(['user_id' => $user->id, 'status' => 6]);
        $akreditasi->delete();
        return $akreditasi->fresh();
    }

    public static function searchDataProvider(): array
    {
        $faker = \Faker\Factory::create('id_ID');
        $cases = [];
        for ($i = 0; $i < 100; $i++) {
            $names = [
                $faker->company . ' ' . $faker->city,
                $faker->company . ' ' . $faker->city,
                $faker->company . ' ' . $faker->city,
            ];
            // Pick a full word from one of the names as the search term
            // to guarantee the search term is actually in the name.
            $target = $names[array_rand($names)];
            $words = preg_split('/\s+/', $target);
            // Use a word with at least 4 chars to avoid ambiguous short matches
            $longWords = array_values(array_filter($words, fn ($w) => mb_strlen($w) >= 4));
            $searchTerm = $longWords ? $longWords[array_rand($longWords)] : $words[0];
            $cases[] = [$names, $searchTerm];
        }
        return $cases;
    }

    /**     */
#[DataProvider('searchDataProvider')]
public function test_property_1_search_returns_only_matching_results(array $names, string $searchTerm): void
    {
        foreach ($names as $name) {
            $this->makeTrashedAkreditasi($name);
        }

        $results = $this->service->getPaginatedTrashed($searchTerm);

        foreach ($results->items() as $item) {
            $pesantrenName = $item->user?->pesantren?->nama_pesantren ?? $item->user?->name ?? '';
            $this->assertStringContainsStringIgnoringCase(
                $searchTerm,
                $pesantrenName,
                "Result '{$pesantrenName}' does not contain search term '{$searchTerm}'"
            );
        }
    }

    public function test_empty_search_returns_all_trashed(): void
    {
        $this->makeTrashedAkreditasi('Pesantren Alpha');
        $this->makeTrashedAkreditasi('Pesantren Beta');
        $this->makeTrashedAkreditasi('Pesantren Gamma');

        $results = $this->service->getPaginatedTrashed(null);
        $this->assertSame(3, $results->total());
    }

    public function test_no_match_returns_empty(): void
    {
        $this->makeTrashedAkreditasi('Pesantren Alpha');
        $results = $this->service->getPaginatedTrashed('ZZZNOMATCH999');
        $this->assertSame(0, $results->total());
    }
}
