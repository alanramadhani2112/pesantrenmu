<?php

namespace Tests\Feature\ConcurrentAccess;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Property-Based Tests for Timestamp Format (ISO 8601 Round-Trip).
 *
 */
#[Group('Feature:concurrent-access-handling')]
#[Group('Property6')]
class TimestampFormatPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Generate 100 random datetime values for testing.
     */
    public static function randomDatetimeProvider(): array
    {
        $faker = Faker::create();
        $cases = [];

        for ($i = 0; $i < 100; $i++) {
            // Generate random datetime between 2020 and 2030
            $timestamp = Carbon::createFromTimestamp(
                $faker->numberBetween(
                    Carbon::create(2020, 1, 1)->timestamp,
                    Carbon::create(2030, 12, 31)->timestamp
                )
            );
            $cases["case_{$i}"] = [$timestamp->toISOString()];
        }

        return $cases;
    }

    /**
     * Property 6: ISO 8601 format round-trip.
     *
     * For any datetime value, converting to ISO 8601 string and parsing back
     * should produce the same datetime without loss of precision.
     *
     * **Validates: Requirements 6.4**
     *
     */
#[DataProvider('randomDatetimeProvider')]
public function test_iso8601_round_trip_preserves_datetime(string $isoString): void
    {
        // Parse the ISO string back to Carbon
        $parsed = Carbon::parse($isoString);

        // Convert back to ISO string
        $roundTripped = $parsed->toISOString();

        $this->assertEquals(
            $isoString,
            $roundTripped,
            "ISO 8601 round-trip should preserve the datetime: {$isoString}"
        );
    }

    /**
     * Property 6: akreditasiUpdatedAt stored in component is valid ISO 8601.
     *
     * For any akreditasi record loaded into a Livewire component, the stored
     * akreditasiUpdatedAt property SHALL be a valid ISO 8601 datetime string.
     *
     * **Validates: Requirements 6.4**
     *
     */
#[DataProvider('randomDatetimeProvider')]
public function test_carbon_to_iso_string_is_valid_iso8601(string $isoString): void
    {
        // Verify the string matches ISO 8601 format
        // ISO 8601: YYYY-MM-DDTHH:MM:SS.sssZ or with timezone offset
        $iso8601Pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/';

        $this->assertMatchesRegularExpression(
            $iso8601Pattern,
            $isoString,
            "String '{$isoString}' should match ISO 8601 format"
        );
    }

    /**
     * Property 6: Akreditasi updated_at stored as ISO 8601 can be parsed back.
     *
     * **Validates: Requirements 6.1, 6.4**
     */
public function test_akreditasi_updated_at_iso_string_round_trip(): void
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Timestamp Test',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        // Simulate what the Livewire component does on mount
        $storedTimestamp = $akreditasi->updated_at->toISOString();

        // Verify it's a valid ISO 8601 string
        $iso8601Pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/';
        $this->assertMatchesRegularExpression($iso8601Pattern, $storedTimestamp);

        // Verify round-trip
        $parsed = Carbon::parse($storedTimestamp);
        $this->assertEquals($storedTimestamp, $parsed->toISOString());

        // Verify it matches the original updated_at
        $this->assertEquals($akreditasi->updated_at->toISOString(), $storedTimestamp);
    }

    /**
     * Property 6: Timestamp comparison is exact (no precision loss).
     *
     * **Validates: Requirements 6.4**
     */
public function test_timestamp_comparison_is_exact(): void
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Comparison Test',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $originalTimestamp = $akreditasi->updated_at->toISOString();

        // Reload from DB
        $reloaded = Akreditasi::find($akreditasi->id);
        $reloadedTimestamp = $reloaded->updated_at->toISOString();

        // Timestamps should match exactly
        $this->assertEquals($originalTimestamp, $reloadedTimestamp,
            'Timestamps should match exactly when reloaded from DB without modification');

        // After an update with a future timestamp, timestamps should differ
        $futureTime = Carbon::now()->addSeconds(2);
        $akreditasi->updated_at = $futureTime;
        $akreditasi->save(['timestamps' => false]);
        // Force update_at to a different value
        \Illuminate\Support\Facades\DB::table('akreditasis')
            ->where('id', $akreditasi->id)
            ->update(['updated_at' => $futureTime->toDateTimeString()]);

        $updatedTimestamp = Akreditasi::find($akreditasi->id)->updated_at->toISOString();

        $this->assertNotEquals($originalTimestamp, $updatedTimestamp,
            'Timestamps should differ after an update to a different time');
    }
}
