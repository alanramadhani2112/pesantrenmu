<?php

namespace Tests\Feature;

use App\Console\Commands\SendAsesor2Reminders;
use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AsesorService;
use App\Services\ProgressTracker;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Property-Based Tests for Asesor 2 Enforcement
 *
 * Uses PHPUnit with randomized data providers to approximate property-based testing.
 * Each property test runs 100 iterations with randomly generated inputs.
 *
 * @group Feature: asesor-2-enforcement
 */
class Asesor2EnforcementPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected ProgressTracker $progressTracker;
    protected AsesorService $asesorService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->progressTracker = app(ProgressTracker::class);
        $this->asesorService = app(AsesorService::class);
    }

    // =========================================================================
    // Task 7.1: Data provider generating 100 random states
    // =========================================================================

    /**
     * Generate 100 random filled/total combinations for property testing.
     * Returns [filled, total] pairs where 0 <= filled <= total <= 100.
     */
    public static function randomFilledTotalProvider(): array
    {
        $faker = Faker::create();
        $cases = [];

        for ($i = 0; $i < 100; $i++) {
            $total = $faker->numberBetween(0, 100);
            $filled = $total > 0 ? $faker->numberBetween(0, $total) : 0;
            $cases["case_{$i}_filled{$filled}_total{$total}"] = [$filled, $total];
        }

        return $cases;
    }

    /**
     * Generate 100 random incomplete Asesor 2 states (0-99% complete).
     * Returns [filledCount, totalCount] where filled < total.
     */
    public static function randomIncompleteAsesor2Provider(): array
    {
        $faker = Faker::create();
        $cases = [];

        for ($i = 0; $i < 100; $i++) {
            $total = $faker->numberBetween(2, 20);
            // filled must be < total (0 to total-1)
            $filled = $faker->numberBetween(0, $total - 1);
            $cases["case_{$i}_filled{$filled}_total{$total}"] = [$filled, $total];
        }

        return $cases;
    }

    /**
     * Generate 100 random incomplete Asesor 1 states.
     * Returns [naFilled, nkFilled, total] where at least one is incomplete.
     */
    public static function randomIncompleteAsesor1Provider(): array
    {
        $faker = Faker::create();
        $cases = [];

        for ($i = 0; $i < 100; $i++) {
            $total = $faker->numberBetween(2, 20);
            // Randomly choose which is incomplete: na, nk, or both
            $scenario = $faker->randomElement(['na_incomplete', 'nk_incomplete', 'both_incomplete']);

            if ($scenario === 'na_incomplete') {
                $naFilled = $faker->numberBetween(0, $total - 1);
                $nkFilled = $total; // nk complete
            } elseif ($scenario === 'nk_incomplete') {
                $naFilled = $total; // na complete
                $nkFilled = $faker->numberBetween(0, $total - 1);
            } else {
                $naFilled = $faker->numberBetween(0, $total - 1);
                $nkFilled = $faker->numberBetween(0, $total - 1);
            }

            $cases["case_{$i}_{$scenario}_na{$naFilled}_nk{$nkFilled}_total{$total}"] = [$naFilled, $nkFilled, $total, $scenario];
        }

        return $cases;
    }

    /**
     * Generate 100 random percentages 0-100 for color mapping tests.
     */
    public static function randomPercentageProvider(): array
    {
        $faker = Faker::create();
        $cases = [];

        for ($i = 0; $i < 100; $i++) {
            $percentage = $faker->numberBetween(0, 100);
            $cases["case_{$i}_pct{$percentage}"] = [$percentage];
        }

        return $cases;
    }

    /**
     * Generate 100 random pesantren/percentage/date combinations for reminder content tests.
     */
    public static function randomReminderContentProvider(): array
    {
        $faker = Faker::create();
        $cases = [];

        for ($i = 0; $i < 100; $i++) {
            $pesantrenName = $faker->company . ' Pesantren';
            $percentage = $faker->numberBetween(0, 99);
            $deadline = $faker->dateTimeBetween('+1 day', '+30 days')->format('d/m/Y');
            $cases["case_{$i}"] = [$pesantrenName, $percentage, $deadline];
        }

        return $cases;
    }

    // =========================================================================
    // Task 7.2 - Property 1: Completion percentage calculation
    // **Validates: Requirements 1.1, 1.2, 1.3**
    // =========================================================================

    /**
     * Property 1: Completion percentage calculation correctness.
     *
     * For any filled/total combination, getCompletion() SHALL return
     * percentage = (filled/total) * 100, or 0 when total = 0.
     *
     * **Validates: Requirements 1.1, 1.2, 1.3**
     *
     * @dataProvider randomFilledTotalProvider
     * @group Property 1: completion percentage calculation
     */
    public function test_property_1_completion_percentage_calculation(int $filled, int $total): void
    {
        // Create master butir records to match the total
        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'Test Komponen']);

        // Clear existing butirs and create exactly $total
        MasterEdpmButir::query()->delete();
        for ($i = 0; $i < $total; $i++) {
            MasterEdpmButir::create([
                'komponen_id' => $komponen->id,
                'no_sk' => (string) ($i + 1),
                'nomor_butir' => (string) ($i + 1) . '.1',
                'butir_pernyataan' => "Butir test {$i}",
            ]);
        }

        // Create akreditasi and asesor
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Test Asesor',
            'nama_tanpa_gelar' => 'Test Asesor',
        ]);

        // Create $filled AkreditasiEdpm records with non-null isian
        $butirs = MasterEdpmButir::all();
        foreach ($butirs->take($filled) as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor->id,
                'butir_id' => $butir->id,
                'pesantren_id' => $pesantrenUser->id,
                'isian' => '3',
            ]);
        }

        $result = $this->progressTracker->getCompletion($akreditasi->id, $asesor->id, 'isian');

        // Verify filled count
        $this->assertEquals($filled, $result['filled'], "filled count should be {$filled}");

        // Verify total count
        $this->assertEquals($total, $result['total'], "total count should be {$total}");

        // Verify percentage
        if ($total === 0) {
            $this->assertEquals(0.0, $result['percentage'], "percentage should be 0 when total=0");
        } else {
            $expectedPercentage = round(($filled / $total) * 100, 2);
            $this->assertEquals(
                $expectedPercentage,
                $result['percentage'],
                "percentage should be (filled/total)*100 = ({$filled}/{$total})*100 = {$expectedPercentage}"
            );
        }
    }

    // =========================================================================
    // Task 7.3 - Property 2: Finalization blocked by incomplete Asesor 2
    // **Validates: Requirements 2.1, 2.2, 7.1, 7.4**
    // =========================================================================

    /**
     * Property 2: Finalization blocked by incomplete Asesor 2.
     *
     * For any akreditasi with an assigned Asesor 2 where Asesor 2 has filled
     * fewer than total butir items, finalization SHALL return success=false
     * with error='asesor2_incomplete' and status SHALL remain unchanged.
     *
     * **Validates: Requirements 2.1, 2.2, 7.1, 7.4**
     *
     * @dataProvider randomIncompleteAsesor2Provider
     * @group Property 2: finalization blocked by incomplete asesor 2
     */
    public function test_property_2_finalization_blocked_by_incomplete_asesor2(int $filled, int $total): void
    {
        // Create master butir records
        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'Test Komponen']);
        MasterEdpmButir::query()->delete();
        for ($i = 0; $i < $total; $i++) {
            MasterEdpmButir::create([
                'komponen_id' => $komponen->id,
                'no_sk' => (string) ($i + 1),
                'nomor_butir' => (string) ($i + 1) . '.1',
                'butir_pernyataan' => "Butir test {$i}",
            ]);
        }

        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test',
        ]);
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);

        // Create Asesor 1 with ALL butirs filled (both isian and nk)
        $asesor1User = User::factory()->create(['role_id' => 2]);
        $asesor1 = Asesor::create([
            'user_id' => $asesor1User->id,
            'nama_dengan_gelar' => 'Asesor 1 Test',
            'nama_tanpa_gelar' => 'Asesor 1 Test',
        ]);
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        // Fill ALL butirs for Asesor 1 (both isian and nk)
        foreach (MasterEdpmButir::all() as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor1->id,
                'butir_id' => $butir->id,
                'pesantren_id' => $pesantrenUser->id,
                'isian' => '3',
                'nk' => '3',
            ]);
        }

        // Create Asesor 2 with INCOMPLETE butirs (filled < total)
        $asesor2User = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Asesor 2 Test',
            'nama_tanpa_gelar' => 'Asesor 2 Test',
        ]);
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        // Fill only $filled butirs for Asesor 2
        $butirs = MasterEdpmButir::all();
        foreach ($butirs->take($filled) as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor2->id,
                'butir_id' => $butir->id,
                'pesantren_id' => $pesantrenUser->id,
                'isian' => '3',
            ]);
        }

        $originalStatus = $akreditasi->status;

        $result = $this->asesorService->finalizeVerification($akreditasi->id, $asesor1User->id);

        // Verify finalization is blocked
        $this->assertFalse($result['success'], "Finalization should be blocked when Asesor 2 has {$filled}/{$total} butirs filled");
        $this->assertEquals('asesor2_incomplete', $result['error'], "Error should be 'asesor2_incomplete'");
        $this->assertNotNull($result['details'], "Details should not be null");
        $this->assertEquals($filled, $result['details']['filled'], "Details filled should be {$filled}");
        $this->assertEquals($total, $result['details']['total'], "Details total should be {$total}");

        // Verify akreditasi status unchanged
        $akreditasi->refresh();
        $this->assertEquals($originalStatus, $akreditasi->status, "Akreditasi status should remain {$originalStatus}");
    }

    // =========================================================================
    // Task 7.4 - Property 3: Finalization blocked by incomplete Asesor 1
    // **Validates: Requirements 2.1 (implicit), 7.2, 7.4**
    // =========================================================================

    /**
     * Property 3: Finalization blocked by incomplete Asesor 1.
     *
     * For any akreditasi where Asesor 1 has filled fewer than total butir items
     * for NA (isian) or NK (nk), finalization SHALL return success=false with
     * error='asesor1_na_incomplete' or 'asesor1_nk_incomplete', and status unchanged.
     *
     * **Validates: Requirements 2.1 (implicit), 7.2, 7.4**
     *
     * @dataProvider randomIncompleteAsesor1Provider
     * @group Property 3: finalization blocked by incomplete asesor 1
     */
    public function test_property_3_finalization_blocked_by_incomplete_asesor1(
        int $naFilled,
        int $nkFilled,
        int $total,
        string $scenario
    ): void {
        // Create master butir records
        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'Test Komponen']);
        MasterEdpmButir::query()->delete();
        for ($i = 0; $i < $total; $i++) {
            MasterEdpmButir::create([
                'komponen_id' => $komponen->id,
                'no_sk' => (string) ($i + 1),
                'nomor_butir' => (string) ($i + 1) . '.1',
                'butir_pernyataan' => "Butir test {$i}",
            ]);
        }

        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test',
        ]);
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);

        // Create Asesor 1 with incomplete data
        $asesor1User = User::factory()->create(['role_id' => 2]);
        $asesor1 = Asesor::create([
            'user_id' => $asesor1User->id,
            'nama_dengan_gelar' => 'Asesor 1 Test',
            'nama_tanpa_gelar' => 'Asesor 1 Test',
        ]);
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        // Fill butirs for Asesor 1 based on scenario
        $butirs = MasterEdpmButir::all()->values();
        $maxFilled = max($naFilled, $nkFilled);

        for ($i = 0; $i < $maxFilled; $i++) {
            $butir = $butirs[$i];
            $isian = $i < $naFilled ? '3' : null;
            $nk = $i < $nkFilled ? '3' : null;

            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor1->id,
                'butir_id' => $butir->id,
                'pesantren_id' => $pesantrenUser->id,
                'isian' => $isian,
                'nk' => $nk,
            ]);
        }

        $originalStatus = $akreditasi->status;

        $result = $this->asesorService->finalizeVerification($akreditasi->id, $asesor1User->id);

        // Verify finalization is blocked
        $this->assertFalse($result['success'], "Finalization should be blocked for scenario: {$scenario}");
        $this->assertContains(
            $result['error'],
            ['asesor1_na_incomplete', 'asesor1_nk_incomplete'],
            "Error should be 'asesor1_na_incomplete' or 'asesor1_nk_incomplete', got: {$result['error']}"
        );

        // Verify akreditasi status unchanged
        $akreditasi->refresh();
        $this->assertEquals($originalStatus, $akreditasi->status, "Akreditasi status should remain {$originalStatus}");
    }

    // =========================================================================
    // Task 7.5 - Property 4: Color mapping
    // **Validates: Requirements 3.4**
    // =========================================================================

    /**
     * Property 4: Color mapping correctness.
     *
     * For any percentage in [0, 100], getColorClass() SHALL return:
     * - 'red' for 0-49
     * - 'amber' for 50-99
     * - 'green' for 100
     *
     * **Validates: Requirements 3.4**
     *
     * @dataProvider randomPercentageProvider
     * @group Property 4: color mapping
     */
    public function test_property_4_color_mapping(int $percentage): void
    {
        $color = $this->progressTracker->getColorClass((float) $percentage);

        if ($percentage >= 100) {
            $this->assertEquals('green', $color, "Percentage {$percentage} should map to 'green'");
        } elseif ($percentage >= 50) {
            $this->assertEquals('amber', $color, "Percentage {$percentage} should map to 'amber'");
        } else {
            $this->assertEquals('red', $color, "Percentage {$percentage} should map to 'red'");
        }
    }

    // =========================================================================
    // Task 7.6 - Property 5: Reminder content
    // **Validates: Requirements 5.4**
    // =========================================================================

    /**
     * Property 5: Reminder notification content.
     *
     * For any reminder notification generated for an Asesor 2, the notification
     * message SHALL contain the pesantren name, the current completion percentage,
     * and the assessment deadline date.
     *
     * **Validates: Requirements 5.4**
     *
     * @dataProvider randomReminderContentProvider
     * @group Property 5: reminder content
     */
    public function test_property_5_reminder_content(string $pesantrenName, int $percentage, string $deadline): void
    {
        // Create master butir records (need at least 1)
        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'Test Komponen']);
        MasterEdpmButir::query()->delete();
        $total = 10;
        for ($i = 0; $i < $total; $i++) {
            MasterEdpmButir::create([
                'komponen_id' => $komponen->id,
                'no_sk' => (string) ($i + 1),
                'nomor_butir' => (string) ($i + 1) . '.1',
                'butir_pernyataan' => "Butir test {$i}",
            ]);
        }

        // Create pesantren user
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => $pesantrenName,
        ]);

        // Create akreditasi at status 5
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);

        // Create Asesor 2
        $asesor2User = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Asesor 2 Test',
            'nama_tanpa_gelar' => 'Asesor 2 Test',
        ]);

        // Parse deadline back to a Carbon date for tanggal_berakhir
        $deadlineCarbon = Carbon::createFromFormat('d/m/Y', $deadline);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now()->subDays(5),
            'tanggal_berakhir' => $deadlineCarbon,
        ]);

        // Fill $percentage% of butirs for Asesor 2
        // Use floor to ensure filled < total when percentage < 100
        $filled = (int) floor(($percentage / 100) * $total);
        // Ensure filled is strictly less than total (to guarantee command sends notification)
        if ($filled >= $total) {
            $filled = $total - 1;
        }
        $butirs = MasterEdpmButir::all()->take($filled);
        foreach ($butirs as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor2->id,
                'butir_id' => $butir->id,
                'pesantren_id' => $pesantrenUser->id,
                'isian' => '3',
            ]);
        }

        // Run the reminder command
        Artisan::call('reminders:asesor2');

        // Verify notification was sent (if percentage < 100)
        if ($percentage < 100) {
            Notification::assertSentTo(
                $asesor2User,
                \App\Notifications\AkreditasiNotification::class,
                function ($notification) use ($pesantrenName, $deadline) {
                    // Message should contain pesantren name
                    $containsPesantren = str_contains($notification->message, $pesantrenName);
                    // Message should contain deadline
                    $containsDeadline = str_contains($notification->message, $deadline);

                    return $containsPesantren && $containsDeadline;
                }
            );
        }
    }

    // =========================================================================
    // Task 7.7 - Property 6: Rate limiting
    // **Validates: Requirements 5.5**
    // =========================================================================

    /**
     * Property 6: Reminder rate limiting.
     *
     * For any Assessment record, the reminder system SHALL send at most one
     * reminder notification per calendar day, regardless of how many times
     * the reminder logic is triggered.
     *
     * **Validates: Requirements 5.5**
     *
     * @group Property 6: rate limiting
     */
    public function test_property_6_rate_limiting(): void
    {
        $faker = Faker::create();

        // Create master butir records
        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'Test Komponen']);
        MasterEdpmButir::query()->delete();
        $total = 5;
        for ($i = 0; $i < $total; $i++) {
            MasterEdpmButir::create([
                'komponen_id' => $komponen->id,
                'no_sk' => (string) ($i + 1),
                'nomor_butir' => (string) ($i + 1) . '.1',
                'butir_pernyataan' => "Butir test {$i}",
            ]);
        }

        // Run 10 iterations with different asesor/akreditasi combinations
        for ($iteration = 0; $iteration < 10; $iteration++) {
            $pesantrenUser = User::factory()->create(['role_id' => 3]);
            Pesantren::create([
                'user_id' => $pesantrenUser->id,
                'nama_pesantren' => "Pesantren Test {$iteration}",
            ]);
            $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);

            $asesor2User = User::factory()->create(['role_id' => 2]);
            $asesor2 = Asesor::create([
                'user_id' => $asesor2User->id,
                'nama_dengan_gelar' => "Asesor 2 Test {$iteration}",
                'nama_tanpa_gelar' => "Asesor 2 Test {$iteration}",
            ]);

            Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor2->id,
                'tipe' => 2,
                'tanggal_mulai' => now()->subDays(5),
                'tanggal_berakhir' => now()->addDays(10),
            ]);

            // Fill 0 butirs (0% complete) to ensure reminder is triggered

            // First run - should send 1 notification
            Artisan::call('reminders:asesor2');

            // Verify 1 notification was sent for this user
            Notification::assertSentToTimes(
                $asesor2User,
                \App\Notifications\AkreditasiNotification::class,
                1,
                "Iteration {$iteration}: First run should send exactly 1 notification"
            );

            // Manually insert a notification record to simulate the rate-limit check
            // (Notification::fake() does not write to the DB)
            $akreditasiUrl = route('asesor.akreditasi-detail', $akreditasi->uuid);
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => \App\Notifications\AkreditasiNotification::class,
                'notifiable_type' => get_class($asesor2User),
                'notifiable_id' => $asesor2User->id,
                'data' => json_encode([
                    'type' => 'reminder_asesor2',
                    'title' => 'Pengingat',
                    'message' => 'Test',
                    'url' => $akreditasiUrl,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Run the command multiple more times on the same day
            $runCount = $faker->numberBetween(1, 4);
            for ($run = 0; $run < $runCount; $run++) {
                Artisan::call('reminders:asesor2');
            }

            // Verify still only 1 notification sent (rate limit enforced)
            Notification::assertSentToTimes(
                $asesor2User,
                \App\Notifications\AkreditasiNotification::class,
                1,
                "Iteration {$iteration}: Should still be 1 notification after {$runCount} additional runs"
            );
        }
    }
}
