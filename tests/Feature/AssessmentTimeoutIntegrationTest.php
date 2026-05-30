<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Services\AkreditasiWorkflowService;
use App\Services\DeadlineService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * End-to-end integration tests for the assessment/visitasi timeout feature.
 *
 * Covers:
 *  - Task 9.2: Complete flow from approval → deadline approach → reminder → overdue → escalation → reassignment
 *  - Task 9.3: Command handles edge cases: no assessments, all completed, mixed states
 *  - Task 9.4: Notification deduplication works across multiple command runs on same day
 */
#[Group('Feature: assessment-visitasi-timeout')]
class AssessmentTimeoutIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected DeadlineService $deadlineService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->deadlineService = app(DeadlineService::class);

        // Log in as admin so audit trail observer has a user_id
        $admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($admin);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createPesantrenUser(string $pesantrenName = 'Pesantren Test'): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => $pesantrenName,
        ]);

        return $user;
    }

    private function createAsesorWithUser(string $name = 'Asesor Test'): array
    {
        $user = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => $name,
            'nama_tanpa_gelar' => $name,
        ]);

        return [$asesor, $user];
    }

    // =========================================================================
    // Task 9.2: End-to-end integration test: complete flow
    // =========================================================================

    /**
     * Task 9.2: Complete flow from approval → deadline approach → reminder → overdue → escalation → reassignment.
     *
     * This test simulates the full lifecycle:
     * 1. Admin approves pengajuan, assigns asesor with a deadline
     * 2. Time advances to within reminder threshold → reminder sent
     * 3. Time advances past deadline → escalation sent to admin
     * 4. Admin reassigns asesor → new deadline set, notifications sent
     */
    public function test_complete_flow_approval_to_reassignment(): void
    {
        Notification::fake();

        config([
            'akreditasi-timeout.assessment.default_duration_days' => 30,
            'akreditasi-timeout.reminder.days_before_deadline' => 3,
            'akreditasi-timeout.escalation.interval_days' => 1,
        ]);

        // ---- Step 1: Setup ----
        $adminUser = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createPesantrenUser('Pesantren E2E Test');
        [$asesor, $asesorUser] = $this->createAsesorWithUser('Asesor E2E');
        [$asesor2] = $this->createAsesorWithUser('Asesor 2 E2E');
        [$newAsesor, $newAsesorUser] = $this->createAsesorWithUser('Asesor Baru E2E');
        [$newAsesor2] = $this->createAsesorWithUser('Asesor Baru 2 E2E');

        // Create akreditasi at status 6 (pengajuan)
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 6]);

        // ---- Step 2: Open review and approve berkas with canonical workflow ----
        $startDate = Carbon::create(2025, 9, 1);
        Carbon::setTestNow($startDate);

        $workflowService = app(AkreditasiWorkflowService::class);
        $workflowService->openForReview($akreditasi->id, $adminUser->id);
        $workflowService->approveBerkas($akreditasi->id, $adminUser->id, $asesor->user_id, $asesor2->user_id);

        $akreditasi->refresh();
        $this->assertEquals(4, $akreditasi->status, 'After berkas approval, status should be 4 (Review Asesor)');

        $assessment = Assessment::where('akreditasi_id', $akreditasi->id)->where('tipe', 1)->first();
        $assessment2 = Assessment::where('akreditasi_id', $akreditasi->id)->where('tipe', 2)->first();
        $this->assertNotNull($assessment, 'Assessment record should be created');
        $this->assertNotNull($assessment2, 'Second assessor record should be created');

        $expectedDeadline = $startDate->copy()->addDays(30)->toDateString();
        $this->assertEquals($expectedDeadline, $assessment->tanggal_berakhir->toDateString(),
            'Deadline should be start date + 30 days');

        // ---- Step 3: Advance to reminder threshold (2 days before deadline) ----
        $reminderDate = $startDate->copy()->addDays(28); // 2 days before deadline
        Carbon::setTestNow($reminderDate);

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Reminder should be sent to asesor
        Notification::assertSentTo(
            $asesorUser,
            AkreditasiNotification::class,
            fn ($n) => in_array($n->type, ['deadline_reminder', 'deadline_today'])
        );

        // No escalation yet (not overdue)
        Notification::assertNotSentTo(
            $adminUser,
            AkreditasiNotification::class,
            fn ($n) => $n->type === 'deadline_overdue_escalation'
        );

        // ---- Step 4: Advance past deadline (overdue) ----
        $overdueDate = $startDate->copy()->addDays(35); // 5 days past deadline
        Carbon::setTestNow($overdueDate);

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Escalation should be sent to admin
        Notification::assertSentTo(
            $adminUser,
            AkreditasiNotification::class,
            fn ($n) => $n->type === 'deadline_overdue_escalation'
        );

        // Verify overdue status
        $assessment->refresh();
        $this->assertTrue($this->deadlineService->isOverdue($assessment), 'Assessment should be overdue');
        $this->assertEquals(5, $this->deadlineService->getDaysOverdue($assessment), 'Should be 5 days overdue');

        // ---- Step 5: Admin reassigns asesor ----
        $this->deadlineService->reassignAsesor($assessment, $newAsesor->id);
        $this->deadlineService->reassignAsesor($assessment2, $newAsesor2->id);

        $assessment->refresh();
        $this->assertEquals($newAsesor->id, $assessment->asesor_id, 'Asesor should be updated');

        $newExpectedDeadline = $overdueDate->copy()->addDays(30)->toDateString();
        $this->assertEquals($newExpectedDeadline, $assessment->tanggal_berakhir->toDateString(),
            'New deadline should be today + 30 days');

        $this->assertNull($assessment->last_reminder_sent_at, 'Reminder tracking should be cleared');
        $this->assertNull($assessment->last_escalation_sent_at, 'Escalation tracking should be cleared');

        // Notifications sent to both asesors
        Notification::assertSentTo(
            $newAsesorUser,
            AkreditasiNotification::class,
            fn ($n) => $n->type === 'asesor_reassigned_new'
        );

        Notification::assertSentTo(
            $asesorUser,
            AkreditasiNotification::class,
            fn ($n) => $n->type === 'asesor_reassigned_old'
        );

        // ---- Step 6: After reassignment, no more escalations for old overdue state ----
        // Run command again - should not escalate since deadline was reset
        Carbon::setTestNow($overdueDate->copy()->addDays(1));
        Notification::fake(); // Reset notification tracking

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // No escalation since new deadlines are in the future
        Notification::assertNotSentTo(
            $adminUser,
            AkreditasiNotification::class,
            fn ($n) => $n->type === 'deadline_overdue_escalation'
        );

        Carbon::setTestNow();
    }

    // =========================================================================
    // Task 9.3: Command handles edge cases
    // =========================================================================

    /**
     * Task 9.3: Command handles edge case — no assessments at all.
     */
    public function test_command_handles_no_assessments(): void
    {
        Notification::fake();

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    /**
     * Task 9.3: Command handles edge case — all akreditasi are completed (status 1 or 2).
     */
    public function test_command_handles_all_completed_akreditasi(): void
    {
        Notification::fake();

        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        // Create completed akreditasi with overdue assessments
        $pesantrenUser1 = $this->createPesantrenUser('Pesantren Selesai 1');
        $akreditasi1 = Akreditasi::create(['user_id' => $pesantrenUser1->id, 'status' => 1]);
        [$asesor1] = $this->createAsesorWithUser('Asesor 1');
        Assessment::create([
            'akreditasi_id' => $akreditasi1->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(40),
            'tanggal_berakhir' => $today->copy()->subDays(10), // overdue but status=1
        ]);

        $pesantrenUser2 = $this->createPesantrenUser('Pesantren Ditolak');
        $akreditasi2 = Akreditasi::create(['user_id' => $pesantrenUser2->id, 'status' => 2]);
        [$asesor2] = $this->createAsesorWithUser('Asesor 2');
        Assessment::create([
            'akreditasi_id' => $akreditasi2->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(40),
            'tanggal_berakhir' => $today->copy()->subDays(10), // overdue but status=2
        ]);

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // No notifications should be sent for completed akreditasi
        Notification::assertNothingSent();

        Carbon::setTestNow();
    }

    /**
     * Task 9.3: Command handles mixed states correctly.
     *
     * Mix of: completed, approaching deadline, overdue, future deadline.
     * Only approaching and overdue should trigger notifications.
     */
    public function test_command_handles_mixed_states(): void
    {
        Notification::fake();

        $today = Carbon::create(2025, 10, 15, 0, 0, 0);
        Carbon::setTestNow($today);

        config([
            'akreditasi-timeout.reminder.days_before_deadline' => 3,
            'akreditasi-timeout.escalation.interval_days' => 1,
        ]);

        $adminUser = User::factory()->create(['role_id' => 1]);

        // 1. Completed (status 1) with overdue assessment → no notification
        $pesantrenUser1 = $this->createPesantrenUser('Pesantren Selesai');
        $akreditasi1 = Akreditasi::create(['user_id' => $pesantrenUser1->id, 'status' => 1]);
        [$asesor1, $asesorUser1] = $this->createAsesorWithUser('Asesor Selesai');
        Assessment::create([
            'akreditasi_id' => $akreditasi1->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(40),
            'tanggal_berakhir' => $today->copy()->subDays(5),
        ]);

        // 2. Assessment (status 5) approaching deadline → reminder
        $pesantrenUser2 = $this->createPesantrenUser('Pesantren Approaching');
        $akreditasi2 = Akreditasi::create(['user_id' => $pesantrenUser2->id, 'status' => 5]);
        [$asesor2, $asesorUser2] = $this->createAsesorWithUser('Asesor Approaching');
        Assessment::create([
            'akreditasi_id' => $akreditasi2->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(28),
            'tanggal_berakhir' => $today->copy()->addDays(2), // 2 days from now
        ]);

        // 3. Visitasi (status 4) overdue → escalation
        $pesantrenUser3 = $this->createPesantrenUser('Pesantren Overdue');
        $akreditasi3 = Akreditasi::create(['user_id' => $pesantrenUser3->id, 'status' => 4]);
        [$asesor3, $asesorUser3] = $this->createAsesorWithUser('Asesor Overdue');
        Assessment::create([
            'akreditasi_id' => $akreditasi3->id,
            'asesor_id' => $asesor3->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(20),
            'tanggal_berakhir' => $today->copy()->subDays(5),
        ]);

        // 4. Assessment (status 5) with future deadline → no notification
        $pesantrenUser4 = $this->createPesantrenUser('Pesantren Future');
        $akreditasi4 = Akreditasi::create(['user_id' => $pesantrenUser4->id, 'status' => 5]);
        [$asesor4, $asesorUser4] = $this->createAsesorWithUser('Asesor Future');
        Assessment::create([
            'akreditasi_id' => $akreditasi4->id,
            'asesor_id' => $asesor4->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(5),
            'tanggal_berakhir' => $today->copy()->addDays(25),
        ]);

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Scenario 1: No notification for completed
        Notification::assertNotSentTo($asesorUser1, AkreditasiNotification::class);

        // Scenario 2: Reminder for approaching deadline
        Notification::assertSentTo(
            $asesorUser2,
            AkreditasiNotification::class,
            fn ($n) => in_array($n->type, ['deadline_reminder', 'deadline_today'])
        );

        // Scenario 3: Escalation for overdue
        Notification::assertSentTo(
            $adminUser,
            AkreditasiNotification::class,
            fn ($n) => $n->type === 'deadline_overdue_escalation'
        );

        // Scenario 4: No notification for future deadline
        Notification::assertNotSentTo($asesorUser4, AkreditasiNotification::class);

        Carbon::setTestNow();
    }

    /**
     * Task 9.3: Command handles assessments with null tanggal_berakhir gracefully.
     *
     * Note: tanggal_berakhir is NOT NULL in the database, so we test with a very
     * far future date that won't trigger any notifications.
     */
    public function test_command_handles_assessments_with_null_deadline(): void
    {
        Notification::fake();

        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $pesantrenUser = $this->createPesantrenUser('Pesantren Far Future');
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);
        [$asesor] = $this->createAsesorWithUser('Asesor Far Future');

        // Assessment with a far future deadline (won't trigger any notifications)
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(10)->toDateString(),
            'tanggal_berakhir' => $today->copy()->addDays(365)->toDateString(), // far future
        ]);

        // Should not throw, should exit successfully
        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        Notification::assertNothingSent();

        Carbon::setTestNow();
    }

    // =========================================================================
    // Task 9.4: Notification deduplication across multiple command runs
    // =========================================================================

    /**
     * Task 9.4: Reminder deduplication — only one reminder per day per assessment.
     *
     * Running the command multiple times on the same day should only send
     * one reminder notification per assessment.
     */
    public function test_reminder_deduplication_across_multiple_runs_same_day(): void
    {
        Notification::fake();

        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        config(['akreditasi-timeout.reminder.days_before_deadline' => 3]);

        $pesantrenUser = $this->createPesantrenUser('Pesantren Reminder Dedup');
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);
        [$asesor, $asesorUser] = $this->createAsesorWithUser('Asesor Reminder Dedup');

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(28),
            'tanggal_berakhir' => $today->copy()->addDays(2), // within reminder threshold
            'last_reminder_sent_at' => null,
        ]);

        // Run command 3 times on the same day
        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);
        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);
        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Only ONE reminder should have been sent
        Notification::assertSentToTimes($asesorUser, AkreditasiNotification::class, 1);

        Carbon::setTestNow();
    }

    /**
     * Task 9.4: Escalation deduplication — only one escalation per interval per assessment.
     *
     * Running the command multiple times on the same day should only send
     * one escalation notification per assessment (when interval is 1 day).
     */
    public function test_escalation_deduplication_across_multiple_runs_same_day(): void
    {
        Notification::fake();

        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        config(['akreditasi-timeout.escalation.interval_days' => 1]);

        $adminUser = User::factory()->create(['role_id' => 1]);

        $pesantrenUser = $this->createPesantrenUser('Pesantren Escalation Dedup');
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);
        [$asesor] = $this->createAsesorWithUser('Asesor Escalation Dedup');

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(20),
            'tanggal_berakhir' => $today->copy()->subDays(5), // overdue
            'last_escalation_sent_at' => null,
        ]);

        // Run command 3 times on the same day
        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);
        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);
        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Only ONE escalation should have been sent
        Notification::assertSentToTimes($adminUser, AkreditasiNotification::class, 1);

        Carbon::setTestNow();
    }

    /**
     * Task 9.4: Reminder IS sent again the next day (deduplication is per-day).
     */
    public function test_reminder_sent_again_next_day(): void
    {
        Notification::fake();

        config(['akreditasi-timeout.reminder.days_before_deadline' => 5]);

        $pesantrenUser = $this->createPesantrenUser('Pesantren Daily Reminder');
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);
        [$asesor, $asesorUser] = $this->createAsesorWithUser('Asesor Daily Reminder');

        // Day 1: within reminder threshold
        $day1 = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($day1);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $day1->copy()->subDays(25),
            'tanggal_berakhir' => $day1->copy()->addDays(3), // 3 days from now (within 5-day threshold)
            'last_reminder_sent_at' => null,
        ]);

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Day 2: advance to next day
        $day2 = $day1->copy()->addDay();
        Carbon::setTestNow($day2);

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Should have sent 2 reminders (one per day)
        Notification::assertSentToTimes($asesorUser, AkreditasiNotification::class, 2);

        Carbon::setTestNow();
    }

    /**
     * Task 9.4: Escalation IS sent again after the configured interval.
     */
    public function test_escalation_sent_again_after_interval(): void
    {
        Notification::fake();

        config(['akreditasi-timeout.escalation.interval_days' => 1]);

        $adminUser = User::factory()->create(['role_id' => 1]);

        $pesantrenUser = $this->createPesantrenUser('Pesantren Escalation Interval');
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);
        [$asesor] = $this->createAsesorWithUser('Asesor Escalation Interval');

        $day1 = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($day1);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $day1->copy()->subDays(20),
            'tanggal_berakhir' => $day1->copy()->subDays(5), // overdue
            'last_escalation_sent_at' => null,
        ]);

        // Day 1: first escalation
        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Day 2: interval passed (1 day), should escalate again
        $day2 = $day1->copy()->addDay();
        Carbon::setTestNow($day2);

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Should have sent 2 escalations (one per day, interval=1)
        Notification::assertSentToTimes($adminUser, AkreditasiNotification::class, 2);

        Carbon::setTestNow();
    }

    /**
     * Task 9.4: Escalation NOT sent again before the configured interval.
     */
    public function test_escalation_not_sent_before_interval(): void
    {
        Notification::fake();

        config(['akreditasi-timeout.escalation.interval_days' => 3]); // 3-day interval

        $adminUser = User::factory()->create(['role_id' => 1]);

        $pesantrenUser = $this->createPesantrenUser('Pesantren Escalation Interval 3');
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);
        [$asesor] = $this->createAsesorWithUser('Asesor Escalation Interval 3');

        $day1 = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($day1);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $day1->copy()->subDays(20),
            'tanggal_berakhir' => $day1->copy()->subDays(5), // overdue
            'last_escalation_sent_at' => null,
        ]);

        // Day 1: first escalation
        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Day 2: interval NOT passed (only 1 day, interval=3), should NOT escalate
        $day2 = $day1->copy()->addDay();
        Carbon::setTestNow($day2);

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Day 3: still not passed
        $day3 = $day1->copy()->addDays(2);
        Carbon::setTestNow($day3);

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Day 4: interval passed (3 days), should escalate again
        $day4 = $day1->copy()->addDays(3);
        Carbon::setTestNow($day4);

        $this->artisan('akreditasi:check-deadlines')->assertExitCode(0);

        // Should have sent exactly 2 escalations (day 1 and day 4)
        Notification::assertSentToTimes($adminUser, AkreditasiNotification::class, 2);

        Carbon::setTestNow();
    }
}
