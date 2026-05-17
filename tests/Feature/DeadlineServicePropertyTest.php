<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Services\DeadlineService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Property-Based Tests for DeadlineService
 *
 * Uses PHPUnit with randomized data to approximate property-based testing.
 * Each property test runs 100 iterations with randomly generated inputs.
 *
 * @group Feature: assessment-visitasi-timeout
 */
class DeadlineServicePropertyTest extends TestCase
{
    use RefreshDatabase;

    protected DeadlineService $deadlineService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->deadlineService = app(DeadlineService::class);

        // Log in as admin so audit trail observer has a user_id when deleting records
        $admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($admin);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create an Asesor with an associated User (role_id=2).
     * Returns [$asesor, $user].
     */
    private function createAsesorWithUser(): array
    {
        $user = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => 'Asesor ' . $user->id,
            'nama_tanpa_gelar' => 'Asesor ' . $user->id,
        ]);
        return [$asesor, $user];
    }

    /**
     * Create an Akreditasi with a pesantren user (role_id=3).
     * Returns [$akreditasi, $pesantrenUser].
     */
    private function createAkreditasiWithUser(int $status = 5): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test ' . $pesantrenUser->id,
        ]);
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ]);
        return [$akreditasi, $pesantrenUser];
    }

    // =========================================================================
    // Property 1: Deadline calculation correctness
    // **Validates: Requirements 1.2**
    // =========================================================================

    /**
     * Property 1: Deadline calculation correctness.
     *
     * For 100 random tanggal_berakhir dates (past or future):
     * - isOverdue() SHALL return true when tanggal_berakhir < today, false otherwise
     * - getDaysOverdue() SHALL return the correct number of days past deadline (0 if not overdue)
     *
     * **Validates: Requirements 1.2**
     */
    public function test_property_1_deadline_calculation_correctness(): void
    {
        $faker = Faker::create();

        // Fix "today" so comparisons are deterministic within the loop
        $today = Carbon::create(2025, 6, 15, 0, 0, 0);
        Carbon::setTestNow($today);

        [$akreditasi] = $this->createAkreditasiWithUser(5);
        [$asesor] = $this->createAsesorWithUser();

        for ($i = 0; $i < 100; $i++) {
            // Random offset: negative = past, positive = future, 0 = today
            $daysOffset = $faker->numberBetween(-60, 60);
            $tanggalBerakhir = $today->copy()->addDays($daysOffset);

            $assessment = Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor->id,
                'tipe' => 1,
                'tanggal_mulai' => $today->copy()->subDays(10),
                'tanggal_berakhir' => $tanggalBerakhir,
            ]);

            $isOverdue = $this->deadlineService->isOverdue($assessment);
            $daysOverdue = $this->deadlineService->getDaysOverdue($assessment);

            if ($daysOffset < 0) {
                // Deadline is in the past → overdue
                $this->assertTrue(
                    $isOverdue,
                    "Iteration {$i}: isOverdue() should be true when tanggal_berakhir ({$tanggalBerakhir->toDateString()}) < today ({$today->toDateString()})"
                );
                $expectedDays = abs($daysOffset);
                $this->assertEquals(
                    $expectedDays,
                    $daysOverdue,
                    "Iteration {$i}: getDaysOverdue() should be {$expectedDays} when {$daysOffset} days past deadline"
                );
                $this->assertGreaterThan(0, $daysOverdue, "Iteration {$i}: getDaysOverdue() should be > 0 when overdue");
            } else {
                // Deadline is today or in the future → not overdue
                $this->assertFalse(
                    $isOverdue,
                    "Iteration {$i}: isOverdue() should be false when tanggal_berakhir ({$tanggalBerakhir->toDateString()}) >= today ({$today->toDateString()})"
                );
                $this->assertEquals(
                    0,
                    $daysOverdue,
                    "Iteration {$i}: getDaysOverdue() should be 0 when not overdue (offset={$daysOffset})"
                );
            }

            // Soft-delete the assessment (no observer, safe to delete)
            $assessment->delete();
        }

        Carbon::setTestNow();
    }

    // =========================================================================
    // Property 2: Timeout checker identification and categorization
    // **Validates: Requirements 2.2, 2.3**
    // =========================================================================

    /**
     * Property 2: Timeout checker identification and categorization.
     *
     * For 100 random akreditasi with mixed statuses (1–6) and dates:
     * - getOverdueAkreditasi() SHALL only return akreditasi with status 4 or 5
     *   where tanggal_berakhir has passed
     *
     * **Validates: Requirements 2.2, 2.3**
     */
    public function test_property_2_overdue_akreditasi_identification(): void
    {
        $faker = Faker::create();

        $today = Carbon::create(2025, 7, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        // We'll create all records first, then verify the overdue list at the end.
        // Track expected overdue IDs vs expected non-overdue IDs.
        $expectedOverdueIds = [];
        $expectedNonOverdueIds = [];

        // Reuse a single asesor for all assessments to avoid creating too many users
        [$asesor] = $this->createAsesorWithUser();

        for ($i = 0; $i < 100; $i++) {
            // Create a random akreditasi with a random status (1–6)
            $status = $faker->numberBetween(1, 6);
            [$akreditasi] = $this->createAkreditasiWithUser($status);

            // Random date: past or future
            $daysOffset = $faker->numberBetween(-30, 30);
            $tanggalBerakhir = $today->copy()->addDays($daysOffset);

            Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor->id,
                'tipe' => 1,
                'tanggal_mulai' => $today->copy()->subDays(10),
                'tanggal_berakhir' => $tanggalBerakhir,
            ]);

            // Should be overdue only if status is 4 or 5 AND deadline has passed
            $shouldBeOverdue = in_array($status, [4, 5]) && $daysOffset < 0;

            if ($shouldBeOverdue) {
                $expectedOverdueIds[] = $akreditasi->id;
            } else {
                $expectedNonOverdueIds[] = $akreditasi->id;
            }
        }

        // Now query the service once
        $overdueIds = $this->deadlineService->getOverdueAkreditasi()->pluck('id')->toArray();

        // Every expected overdue ID must be in the result
        foreach ($expectedOverdueIds as $id) {
            $this->assertContains(
                $id,
                $overdueIds,
                "Akreditasi id={$id} should be in overdue list (status 4/5 with past deadline)"
            );
        }

        // Every expected non-overdue ID must NOT be in the result
        foreach ($expectedNonOverdueIds as $id) {
            $this->assertNotContains(
                $id,
                $overdueIds,
                "Akreditasi id={$id} should NOT be in overdue list"
            );
        }

        Carbon::setTestNow();
    }

    // =========================================================================
    // Property 3: Reminder notification content completeness
    // **Validates: Requirements 3.3**
    // =========================================================================

    /**
     * Property 3: Reminder notification content completeness.
     *
     * For 100 random pesantren names, phases (status 4 or 5), and dates:
     * - Create approaching-deadline assessments
     * - Run processReminders()
     * - Verify notification message contains pesantren name, phase, and deadline date
     *
     * **Validates: Requirements 3.3**
     */
    public function test_property_3_reminder_notification_content_completeness(): void
    {
        $faker = Faker::create();

        \Illuminate\Support\Facades\Notification::fake();

        $today = Carbon::create(2025, 8, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        // Use reminder threshold of 3 days
        config(['akreditasi-timeout.reminder.days_before_deadline' => 3]);

        for ($i = 0; $i < 100; $i++) {
            // Random pesantren name
            $pesantrenName = $faker->company . ' Pesantren ' . $i;

            // Random phase: status 4 (Visitasi) or 5 (Assessment)
            $status = $faker->randomElement([4, 5]);
            $expectedPhase = $status === 4 ? 'Visitasi' : 'Assessment';

            // Deadline within reminder threshold (1–3 days from now)
            $daysUntilDeadline = $faker->numberBetween(1, 3);
            $deadline = $today->copy()->addDays($daysUntilDeadline);
            $expectedDeadlineStr = $deadline->format('d/m/Y');

            // Create pesantren user
            $pesantrenUser = User::factory()->create(['role_id' => 3]);
            Pesantren::create([
                'user_id' => $pesantrenUser->id,
                'nama_pesantren' => $pesantrenName,
            ]);

            // Create akreditasi
            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => $status,
            ]);

            // Create asesor
            [$asesor, $asesorUser] = $this->createAsesorWithUser();

            // Create assessment with approaching deadline
            $assessment = Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor->id,
                'tipe' => 1,
                'tanggal_mulai' => $today->copy()->subDays(10),
                'tanggal_berakhir' => $deadline,
                'last_reminder_sent_at' => null,
            ]);

            // Run processReminders
            $this->deadlineService = app(DeadlineService::class);
            $this->deadlineService->processReminders();

            // Verify notification was sent and contains required fields
            \Illuminate\Support\Facades\Notification::assertSentTo(
                $asesorUser,
                \App\Notifications\AkreditasiNotification::class,
                function ($notification) use ($pesantrenName, $expectedPhase, $expectedDeadlineStr, $i) {
                    $message = $notification->message;
                    $containsPesantren = str_contains($message, $pesantrenName);
                    $containsPhase = str_contains($message, $expectedPhase);
                    $containsDeadline = str_contains($message, $expectedDeadlineStr);

                    $this->assertTrue(
                        $containsPesantren,
                        "Iteration {$i}: Message should contain pesantren name '{$pesantrenName}'. Got: {$message}"
                    );
                    $this->assertTrue(
                        $containsPhase,
                        "Iteration {$i}: Message should contain phase '{$expectedPhase}'. Got: {$message}"
                    );
                    $this->assertTrue(
                        $containsDeadline,
                        "Iteration {$i}: Message should contain deadline '{$expectedDeadlineStr}'. Got: {$message}"
                    );

                    return $containsPesantren && $containsPhase && $containsDeadline;
                }
            );

            // Clean up for next iteration (bypass observer to avoid audit log user_id constraint)
            $assessment->forceDelete();
            \Illuminate\Support\Facades\DB::table('akreditasis')->where('id', $akreditasi->id)->delete();
            \Illuminate\Support\Facades\DB::table('users')->where('id', $pesantrenUser->id)->delete();
            \Illuminate\Support\Facades\Notification::fake(); // reset
        }

        Carbon::setTestNow();
    }

    // =========================================================================
    // Property 4: Reminder deduplication
    // **Validates: Requirements 3.5, 7.3**
    // =========================================================================

    /**
     * Property 4: Reminder deduplication.
     *
     * For 100 scenarios where last_reminder_sent_at is set to today:
     * - Run processReminders()
     * - Verify no new notification is sent (at most one reminder per day)
     *
     * **Validates: Requirements 3.5, 7.3**
     */
    public function test_property_4_reminder_deduplication(): void
    {
        $faker = Faker::create();

        \Illuminate\Support\Facades\Notification::fake();

        $today = Carbon::create(2025, 8, 15, 0, 0, 0);
        Carbon::setTestNow($today);

        config(['akreditasi-timeout.reminder.days_before_deadline' => 3]);

        for ($i = 0; $i < 100; $i++) {
            // Random phase
            $status = $faker->randomElement([4, 5]);

            // Deadline within reminder threshold
            $daysUntilDeadline = $faker->numberBetween(0, 3);
            $deadline = $today->copy()->addDays($daysUntilDeadline);

            // Create pesantren user
            $pesantrenUser = User::factory()->create(['role_id' => 3]);
            Pesantren::create([
                'user_id' => $pesantrenUser->id,
                'nama_pesantren' => 'Pesantren Dedup ' . $i,
            ]);

            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => $status,
            ]);

            [$asesor, $asesorUser] = $this->createAsesorWithUser();

            // Set last_reminder_sent_at to today (already sent today)
            $assessment = Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor->id,
                'tipe' => 1,
                'tanggal_mulai' => $today->copy()->subDays(10),
                'tanggal_berakhir' => $deadline,
                'last_reminder_sent_at' => $today->copy(), // already sent today
            ]);

            // Run processReminders
            $this->deadlineService = app(DeadlineService::class);
            $this->deadlineService->processReminders();

            // Verify NO notification was sent (deduplication)
            \Illuminate\Support\Facades\Notification::assertNotSentTo(
                $asesorUser,
                \App\Notifications\AkreditasiNotification::class
            );

            // Clean up (bypass observer to avoid audit log user_id constraint)
            $assessment->forceDelete();
            \Illuminate\Support\Facades\DB::table('akreditasis')->where('id', $akreditasi->id)->delete();
            \Illuminate\Support\Facades\DB::table('users')->where('id', $pesantrenUser->id)->delete();
        }

        Carbon::setTestNow();
    }

    // =========================================================================
    // Property 5: Escalation notification content completeness
    // **Validates: Requirements 4.2**
    // =========================================================================

    /**
     * Property 5: Escalation notification content completeness.
     *
     * For 100 random escalation scenarios:
     * - Create overdue assessments
     * - Run processEscalations()
     * - Verify notification contains pesantren name, asesor name, phase, deadline, and days overdue
     *
     * **Validates: Requirements 4.2**
     */
    public function test_property_5_escalation_notification_content_completeness(): void
    {
        $faker = Faker::create();

        $today = Carbon::create(2025, 9, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        config(['akreditasi-timeout.escalation.interval_days' => 1]);

        for ($i = 0; $i < 100; $i++) {
            \Illuminate\Support\Facades\Notification::fake();

            // Create admin user
            $adminUser = User::factory()->create(['role_id' => 1]);

            // Random phase
            $status = $faker->randomElement([4, 5]);
            $expectedPhase = $status === 4 ? 'Visitasi' : 'Assessment';

            // Random days overdue (1–30)
            $daysOverdue = $faker->numberBetween(1, 30);
            $deadline = $today->copy()->subDays($daysOverdue);
            $expectedDeadlineStr = $deadline->format('d/m/Y');

            // Create pesantren user
            $pesantrenUser = User::factory()->create(['role_id' => 3]);
            $pesantrenName = 'Pesantren Eskalasi ' . $i;
            Pesantren::create([
                'user_id' => $pesantrenUser->id,
                'nama_pesantren' => $pesantrenName,
            ]);

            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => $status,
            ]);

            // Create asesor
            $asesorUser = User::factory()->create(['role_id' => 2]);
            $asesorName = 'Asesor Eskalasi ' . $i;
            $asesor = Asesor::create([
                'user_id' => $asesorUser->id,
                'nama_dengan_gelar' => $asesorName,
                'nama_tanpa_gelar' => $asesorName,
            ]);

            // Create overdue assessment
            $assessment = Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor->id,
                'tipe' => 1,
                'tanggal_mulai' => $today->copy()->subDays($daysOverdue + 10),
                'tanggal_berakhir' => $deadline,
                'last_escalation_sent_at' => null,
            ]);

            // Run processEscalations
            $this->deadlineService = app(DeadlineService::class);
            $this->deadlineService->processEscalations();

            // Verify notification was sent to admin and contains required fields
            \Illuminate\Support\Facades\Notification::assertSentTo(
                $adminUser,
                \App\Notifications\AkreditasiNotification::class,
                function ($notification) use ($pesantrenName, $asesorName, $expectedPhase, $expectedDeadlineStr, $daysOverdue, $i) {
                    $message = $notification->message;
                    $containsPesantren = str_contains($message, $pesantrenName);
                    $containsAsesor = str_contains($message, $asesorName);
                    $containsPhase = str_contains($message, $expectedPhase);
                    $containsDeadline = str_contains($message, $expectedDeadlineStr);
                    $containsDaysOverdue = str_contains($message, (string) $daysOverdue);

                    $this->assertTrue($containsPesantren, "Iteration {$i}: Message should contain pesantren name. Got: {$message}");
                    $this->assertTrue($containsAsesor, "Iteration {$i}: Message should contain asesor name. Got: {$message}");
                    $this->assertTrue($containsPhase, "Iteration {$i}: Message should contain phase. Got: {$message}");
                    $this->assertTrue($containsDeadline, "Iteration {$i}: Message should contain deadline. Got: {$message}");
                    $this->assertTrue($containsDaysOverdue, "Iteration {$i}: Message should contain days overdue. Got: {$message}");

                    return $containsPesantren && $containsAsesor && $containsPhase && $containsDeadline && $containsDaysOverdue;
                }
            );

            // Clean up (bypass observer to avoid audit log user_id constraint)
            $assessment->forceDelete();
            \Illuminate\Support\Facades\DB::table('akreditasis')->where('id', $akreditasi->id)->delete();
            \Illuminate\Support\Facades\DB::table('users')->where('id', $pesantrenUser->id)->delete();
            \Illuminate\Support\Facades\DB::table('users')->where('id', $adminUser->id)->delete();
        }

        Carbon::setTestNow();
    }

    // =========================================================================
    // Property 6: Escalation interval enforcement
    // **Validates: Requirements 4.4, 7.4**
    // =========================================================================

    /**
     * Property 6: Escalation interval enforcement.
     *
     * For 100 random last_escalation_sent_at values and intervals:
     * - Verify correct send/skip decision based on escalation interval
     *
     * **Validates: Requirements 4.4, 7.4**
     */
    public function test_property_6_escalation_interval_enforcement(): void
    {
        $faker = Faker::create();

        $today = Carbon::create(2025, 9, 15, 0, 0, 0);
        Carbon::setTestNow($today);

        for ($i = 0; $i < 100; $i++) {
            \Illuminate\Support\Facades\Notification::fake();

            // Random escalation interval (1–7 days)
            $intervalDays = $faker->numberBetween(1, 7);
            config(['akreditasi-timeout.escalation.interval_days' => $intervalDays]);

            // Random days since last escalation (0–10)
            $daysSinceLastEscalation = $faker->numberBetween(0, 10);
            $lastEscalationSentAt = $today->copy()->subDays($daysSinceLastEscalation);

            // Should send if days since last >= interval
            $shouldSend = $daysSinceLastEscalation >= $intervalDays;

            // Create admin user
            $adminUser = User::factory()->create(['role_id' => 1]);

            // Create pesantren user
            $pesantrenUser = User::factory()->create(['role_id' => 3]);
            Pesantren::create([
                'user_id' => $pesantrenUser->id,
                'nama_pesantren' => 'Pesantren Interval ' . $i,
            ]);

            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => 5,
            ]);

            [$asesor, $asesorUser] = $this->createAsesorWithUser();

            // Create overdue assessment with last_escalation_sent_at set
            $assessment = Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor->id,
                'tipe' => 1,
                'tanggal_mulai' => $today->copy()->subDays(20),
                'tanggal_berakhir' => $today->copy()->subDays(5), // overdue
                'last_escalation_sent_at' => $lastEscalationSentAt,
            ]);

            // Run processEscalations
            $this->deadlineService = app(DeadlineService::class);
            $this->deadlineService->processEscalations();

            if ($shouldSend) {
                \Illuminate\Support\Facades\Notification::assertSentTo(
                    $adminUser,
                    \App\Notifications\AkreditasiNotification::class,
                    function ($notification) {
                        return $notification->type === 'deadline_overdue_escalation';
                    }
                );
            } else {
                \Illuminate\Support\Facades\Notification::assertNotSentTo(
                    $adminUser,
                    \App\Notifications\AkreditasiNotification::class
                );
            }

            // Clean up (bypass observer to avoid audit log user_id constraint)
            $assessment->forceDelete();
            \Illuminate\Support\Facades\DB::table('akreditasis')->where('id', $akreditasi->id)->delete();
            \Illuminate\Support\Facades\DB::table('users')->where('id', $pesantrenUser->id)->delete();
            \Illuminate\Support\Facades\DB::table('users')->where('id', $adminUser->id)->delete();
        }

        Carbon::setTestNow();
    }

    // =========================================================================
    // Unit Test 3.7: Reminder notification uses correct channels
    // **Validates: Requirements 3.4**
    // =========================================================================

    /**
     * Unit Test 3.7: Reminder notification uses correct channels (database).
     *
     * Verify that the AkreditasiNotification used for reminders includes
     * the 'database' channel.
     *
     * **Validates: Requirements 3.4**
     */
    public function test_unit_3_7_reminder_notification_uses_database_channel(): void
    {
        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        config(['akreditasi-timeout.reminder.days_before_deadline' => 3]);

        \Illuminate\Support\Facades\Notification::fake();

        // Create pesantren user
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Channel Test',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 5,
        ]);

        [$asesor, $asesorUser] = $this->createAsesorWithUser();

        // Deadline tomorrow (within 3-day threshold)
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(10),
            'tanggal_berakhir' => $today->copy()->addDays(1),
            'last_reminder_sent_at' => null,
        ]);

        $this->deadlineService->processReminders();

        // Verify notification was sent
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $asesorUser,
            \App\Notifications\AkreditasiNotification::class,
            function ($notification) use ($asesorUser) {
                // Verify the notification's via() includes 'database'
                $channels = $notification->via($asesorUser);
                $this->assertContains('database', $channels, "Reminder notification should use 'database' channel");
                return true;
            }
        );

        Carbon::setTestNow();
    }

    // =========================================================================
    // Unit Test 3.8: Escalation stops when akreditasi status changes away from 4/5
    // **Validates: Requirements 4.5**
    // =========================================================================

    /**
     * Unit Test 3.8: Escalation stops when akreditasi status changes away from 4/5.
     *
     * When an akreditasi's status is changed to something other than 4 or 5,
     * processEscalations() should NOT send escalation notifications for it.
     *
     * **Validates: Requirements 4.5**
     */
    public function test_unit_3_8_escalation_stops_when_status_changes(): void
    {
        $today = Carbon::create(2025, 10, 15, 0, 0, 0);
        Carbon::setTestNow($today);

        config(['akreditasi-timeout.escalation.interval_days' => 1]);

        \Illuminate\Support\Facades\Notification::fake();

        // Create admin user
        $adminUser = User::factory()->create(['role_id' => 1]);

        // Create pesantren user
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Status Change Test',
        ]);

        [$asesor] = $this->createAsesorWithUser();

        // Create akreditasi with status 1 (Berhasil) — not 4 or 5
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 1,
        ]);

        // Create overdue assessment
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(20),
            'tanggal_berakhir' => $today->copy()->subDays(5), // overdue
            'last_escalation_sent_at' => null,
        ]);

        // Run processEscalations
        $this->deadlineService->processEscalations();

        // Verify NO escalation was sent (status is not 4 or 5)
        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            $adminUser,
            \App\Notifications\AkreditasiNotification::class
        );

        // Now change status to 5 (Assessment) and verify escalation IS sent
        $akreditasi->update(['status' => 5]);
        \Illuminate\Support\Facades\Notification::fake(); // reset

        $this->deadlineService->processEscalations();

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $adminUser,
            \App\Notifications\AkreditasiNotification::class
        );

        // Now change status back to 1 (completed) and verify escalation stops
        $akreditasi->update(['status' => 1]);
        \Illuminate\Support\Facades\Notification::fake(); // reset

        $this->deadlineService->processEscalations();

        \Illuminate\Support\Facades\Notification::assertNotSentTo(
            $adminUser,
            \App\Notifications\AkreditasiNotification::class
        );

        Carbon::setTestNow();
    }

    // =========================================================================
    // Property 7: Available asesors excludes current
    // **Validates: Requirements 6.2**
    // =========================================================================

    /**
     * Property 7: Available asesors for reassignment excludes the currently assigned asesor.
     *
     * For 100 random asesor sets:
     * - Create multiple asesors, assign one to an assessment
     * - getAvailableAsesorsForReassignment() SHALL never contain the currently assigned asesor
     *
     * **Validates: Requirements 6.2**
     */
    public function test_property_7_available_asesors_excludes_current(): void
    {
        $faker = Faker::create();

        $today = Carbon::create(2025, 7, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        // Reuse a single akreditasi for all iterations to avoid observer issues on delete
        [$akreditasi] = $this->createAkreditasiWithUser(5);

        for ($i = 0; $i < 100; $i++) {
            // Create a random number of asesors (2–5)
            $asesorCount = $faker->numberBetween(2, 5);
            $asesors = [];
            for ($j = 0; $j < $asesorCount; $j++) {
                [$asesor] = $this->createAsesorWithUser();
                $asesors[] = $asesor;
            }

            // Pick a random asesor as the assigned one
            $assignedIndex = $faker->numberBetween(0, $asesorCount - 1);
            $assignedAsesor = $asesors[$assignedIndex];

            $assessment = Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $assignedAsesor->id,
                'tipe' => 1,
                'tanggal_mulai' => $today->copy()->subDays(10),
                'tanggal_berakhir' => $today->copy()->subDays(1), // overdue
            ]);

            $availableAsesors = $this->deadlineService->getAvailableAsesorsForReassignment($assessment);
            $availableIds = $availableAsesors->pluck('id')->toArray();

            // The assigned asesor must NOT be in the available list
            $this->assertNotContains(
                $assignedAsesor->id,
                $availableIds,
                "Iteration {$i}: Assigned asesor (id={$assignedAsesor->id}) should not be in available list"
            );

            // All other asesors created in this iteration SHOULD be in the available list
            foreach ($asesors as $idx => $asesor) {
                if ($idx === $assignedIndex) {
                    continue;
                }
                $this->assertContains(
                    $asesor->id,
                    $availableIds,
                    "Iteration {$i}: Asesor (id={$asesor->id}) should be in available list"
                );
            }

            // Soft-delete the assessment so next iteration starts fresh
            $assessment->delete();
        }

        Carbon::setTestNow();
    }
}
