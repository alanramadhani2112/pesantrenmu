<?php

namespace Tests\Feature\Property;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Banding;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Property 2: Badge count equals filtered record count
 *
 * For any set of records (Akreditasi with various statuses, Banding with various statuses,
 * or Assessments with various statuses), the badge count returned by the sidebar badge
 * component SHALL equal the count of records matching the target status filter
 * (status=6 for pending akreditasi, status='pending' for banding, status in [4,5] for
 * asesor tasks). When the count is zero, the badge SHALL not be displayed.
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 4.1, 4.2**
 */
class BadgeCountPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Possible Banding statuses used in the system.
     */
    private const BANDING_STATUSES = ['pending', 'under_review', 'accepted', 'rejected'];

    /**
     * Generate random data provider with 120 iterations of randomized record combinations.
     */
    public static function randomBadgeCountCombinationsProvider(): array
    {
        $cases = [];
        $seed = crc32('badge_count_property_test');
        mt_srand($seed);

        for ($i = 0; $i < 120; $i++) {
            // Random number of Akreditasi records (0-10)
            $akreditasiCount = mt_rand(0, 10);
            // Random statuses for each Akreditasi (1-6)
            $akreditasiStatuses = [];
            for ($j = 0; $j < $akreditasiCount; $j++) {
                $akreditasiStatuses[] = mt_rand(1, 6);
            }

            // Random number of Banding records (0-8)
            $bandingCount = mt_rand(0, 8);
            // Random statuses for each Banding
            $bandingStatuses = [];
            for ($j = 0; $j < $bandingCount; $j++) {
                $bandingStatuses[] = self::BANDING_STATUSES[mt_rand(0, 3)];
            }

            // Random number of Akreditasi for asesor tasks (0-8)
            $asesorAkreditasiCount = mt_rand(0, 8);
            // Random statuses for asesor akreditasi (1-6)
            $asesorAkreditasiStatuses = [];
            for ($j = 0; $j < $asesorAkreditasiCount; $j++) {
                $asesorAkreditasiStatuses[] = mt_rand(1, 6);
            }
            // Which of these are assigned to the asesor (bitmask)
            $assignedBitmask = mt_rand(0, (1 << $asesorAkreditasiCount) - 1);

            $cases["iteration_{$i}"] = [
                $akreditasiStatuses,
                $bandingStatuses,
                $asesorAkreditasiStatuses,
                $assignedBitmask,
            ];
        }

        return $cases;
    }

    /**
     * Property 2: Badge count equals filtered record count (Admin badges)
     *
     * **Validates: Requirements 3.1, 3.2, 3.3**
     *
     * @dataProvider randomBadgeCountCombinationsProvider
     */
    public function test_property_2_admin_badge_count_equals_filtered_record_count(
        array $akreditasiStatuses,
        array $bandingStatuses,
        array $asesorAkreditasiStatuses,
        int $assignedBitmask
    ): void {
        // Create admin user
        $admin = User::factory()->create(['role_id' => 1]);

        // Create a pesantren user to own akreditasi records
        $pesantrenUser = User::factory()->create(['role_id' => 3]);

        // Create Akreditasi records with random statuses
        foreach ($akreditasiStatuses as $status) {
            Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'status' => $status,
            ]);
        }

        // Create Banding records with random statuses
        // Each banding needs an akreditasi_id, so create one if needed
        if (!empty($bandingStatuses)) {
            $bandingAkreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'status' => 1,
            ]);

            foreach ($bandingStatuses as $status) {
                Banding::create([
                    'akreditasi_id' => $bandingAkreditasi->id,
                    'user_id' => $pesantrenUser->id,
                    'status' => $status,
                    'alasan' => 'Test alasan banding',
                ]);
            }
        }

        // Calculate expected counts
        $expectedPendingAkreditasi = count(array_filter($akreditasiStatuses, fn($s) => $s === 6));
        $expectedPendingBanding = count(array_filter($bandingStatuses, fn($s) => $s === 'pending'));

        // Test the component as admin
        $this->actingAs($admin);

        $component = Livewire::test('layout.sidebar-badges');

        // Verify badge counts match filtered record counts
        $component->assertSet('pendingAkreditasiCount', $expectedPendingAkreditasi);
        $component->assertSet('pendingBandingCount', $expectedPendingBanding);

        // Verify zero count results in badge showing "0" (badge not displayed in UI)
        if ($expectedPendingAkreditasi === 0) {
            $component->assertSeeHtml('data-pending-akreditasi="0"');
        } else {
            $component->assertSeeHtml('data-pending-akreditasi="' . $expectedPendingAkreditasi . '"');
        }
        if ($expectedPendingBanding === 0) {
            $component->assertSeeHtml('data-pending-banding="0"');
        } else {
            $component->assertSeeHtml('data-pending-banding="' . $expectedPendingBanding . '"');
        }
    }

    /**
     * Property 2: Badge count equals filtered record count (Asesor badges)
     *
     * **Validates: Requirements 4.1, 4.2**
     *
     * @dataProvider randomBadgeCountCombinationsProvider
     */
    public function test_property_2_asesor_badge_count_equals_filtered_record_count(
        array $akreditasiStatuses,
        array $bandingStatuses,
        array $asesorAkreditasiStatuses,
        int $assignedBitmask
    ): void {
        // Create asesor user with asesor profile
        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Test Asesor',
            'nama_tanpa_gelar' => 'Test Asesor',
        ]);

        // Create a pesantren user to own akreditasi records
        $pesantrenUser = User::factory()->create(['role_id' => 3]);

        // Create another asesor (to test that only assigned tasks count)
        $otherAsesorUser = User::factory()->create(['role_id' => 2]);
        $otherAsesor = Asesor::create([
            'user_id' => $otherAsesorUser->id,
            'nama_dengan_gelar' => 'Dr. Other Asesor',
            'nama_tanpa_gelar' => 'Other Asesor',
        ]);

        // Create Akreditasi records for asesor task testing
        $expectedActiveTaskCount = 0;

        foreach ($asesorAkreditasiStatuses as $index => $status) {
            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'status' => $status,
            ]);

            $isAssigned = ($assignedBitmask & (1 << $index)) !== 0;

            if ($isAssigned) {
                // Assign to our test asesor
                Assessment::create([
                    'akreditasi_id' => $akreditasi->id,
                    'asesor_id' => $asesor->id,
                    'tipe' => 1,
                    'tanggal_mulai' => now()->toDateString(),
                    'tanggal_berakhir' => now()->addDays(30)->toDateString(),
                ]);

                // Count if status is 4 (Visitasi) or 5 (Assessment)
                if (in_array($status, [4, 5])) {
                    $expectedActiveTaskCount++;
                }
            } else {
                // Assign to other asesor (should not count)
                Assessment::create([
                    'akreditasi_id' => $akreditasi->id,
                    'asesor_id' => $otherAsesor->id,
                    'tipe' => 1,
                    'tanggal_mulai' => now()->toDateString(),
                    'tanggal_berakhir' => now()->addDays(30)->toDateString(),
                ]);
            }
        }

        // Test the component as asesor
        $this->actingAs($asesorUser);

        $component = Livewire::test('layout.sidebar-badges');

        // Verify badge count matches filtered record count
        $component->assertSet('activeTaskCount', $expectedActiveTaskCount);

        // Verify zero count results in badge showing "0" (badge not displayed in UI)
        if ($expectedActiveTaskCount === 0) {
            $component->assertSeeHtml('data-active-tasks="0"');
        } else {
            $component->assertSeeHtml('data-active-tasks="' . $expectedActiveTaskCount . '"');
        }
    }
}
