<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Banding;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Services\BandingService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BandingDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected BandingService $bandingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->bandingService = app(BandingService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Always reset time
        parent::tearDown();
    }

    /**
     * Helper: create a pesantren user with basic data.
     */
    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Deadline Test '.$user->id,
        ]);

        return $user;
    }

    /**
     * Integration test 5.4: processDeadlines sends reminder to reviewer
     * when deadline is within reminder threshold.
     *
     * Scenario: A banding has review_deadline 2 days from now, and the
     * reminder threshold is 3 days. processDeadlines() should send a
     * reminder notification to the assigned reviewer.
     */
    public function test_process_deadlines_sends_reminder_to_reviewer_within_threshold(): void
    {
        Notification::fake();

        // Config: reminder threshold is 3 days before deadline
        config(['akreditasi.banding_reminder_days_before' => 3]);

        $user = $this->createPesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        // Create a banding with deadline 2 days from now (within 3-day reminder threshold)
        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Kami merasa penilaian tidak adil.',
            'review_deadline' => now()->addDays(2),
        ]);

        // Call processDeadlines
        $result = $this->bandingService->processDeadlines();

        // Verify reminder was sent
        $this->assertEquals(1, $result['reminders_sent']);
        $this->assertEquals(0, $result['escalations_sent']);

        // Verify notification was sent to the reviewer
        Notification::assertSentTo(
            $reviewer,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'banding_reminder'
                    && str_contains($notification->message, 'Deadline review banding');
            }
        );
    }

    /**
     * Integration test 5.4 (additional): processDeadlines does NOT send reminder
     * when deadline is outside the reminder threshold.
     */
    public function test_process_deadlines_no_reminder_when_outside_threshold(): void
    {
        Notification::fake();

        // Config: reminder threshold is 3 days before deadline
        config(['akreditasi.banding_reminder_days_before' => 3]);

        $user = $this->createPesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        // Create a banding with deadline 5 days from now (outside 3-day reminder threshold)
        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Kami merasa penilaian tidak adil.',
            'review_deadline' => now()->addDays(5),
        ]);

        // Call processDeadlines
        $result = $this->bandingService->processDeadlines();

        // Verify no reminder was sent
        $this->assertEquals(0, $result['reminders_sent']);
        $this->assertEquals(0, $result['escalations_sent']);

        Notification::assertNotSentTo($reviewer, AkreditasiNotification::class);
    }

    /**
     * Integration test 5.5: processDeadlines sends escalation to all admins
     * when deadline has passed.
     *
     * Scenario: A banding has review_deadline in the past. processDeadlines()
     * should send an escalation notification to all admin users.
     */
    public function test_process_deadlines_sends_escalation_to_admins_when_overdue(): void
    {
        Notification::fake();

        $user = $this->createPesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        // Create additional admin users to verify all admins receive escalation
        $admin2 = User::factory()->create(['role_id' => 1]);
        $admin3 = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        // Create a banding with deadline in the past (overdue)
        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Kami merasa penilaian tidak adil.',
            'review_deadline' => now()->subDays(3),
        ]);

        // Call processDeadlines
        $result = $this->bandingService->processDeadlines();

        // Verify escalation was sent
        $this->assertEquals(0, $result['reminders_sent']);
        $this->assertEquals(1, $result['escalations_sent']);

        // Verify escalation notification was sent to all admin users
        Notification::assertSentTo(
            $reviewer,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) use ($banding) {
                return $notification->type === 'banding_escalation'
                    && str_contains($notification->message, 'Banding #'.$banding->id);
            }
        );

        Notification::assertSentTo(
            $admin2,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'banding_escalation';
            }
        );

        Notification::assertSentTo(
            $admin3,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'banding_escalation';
            }
        );
    }

    /**
     * Integration test 5.5 (additional): processDeadlines handles multiple
     * overdue bandings and sends escalation for each.
     */
    public function test_process_deadlines_handles_multiple_overdue_bandings(): void
    {
        Notification::fake();

        $user1 = $this->createPesantrenUser();
        $user2 = $this->createPesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        $akreditasi1 = Akreditasi::create([
            'user_id' => $user1->id,
            'status' => 3,
        ]);

        $akreditasi2 = Akreditasi::create([
            'user_id' => $user2->id,
            'status' => 3,
        ]);

        // Create two overdue bandings
        Banding::create([
            'akreditasi_id' => $akreditasi1->id,
            'user_id' => $user1->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Banding pertama.',
            'review_deadline' => now()->subDays(5),
        ]);

        Banding::create([
            'akreditasi_id' => $akreditasi2->id,
            'user_id' => $user2->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Banding kedua.',
            'review_deadline' => now()->subDays(2),
        ]);

        // Call processDeadlines
        $result = $this->bandingService->processDeadlines();

        // Verify both escalations were counted
        $this->assertEquals(0, $result['reminders_sent']);
        $this->assertEquals(2, $result['escalations_sent']);
    }

    /**
     * Integration test: processDeadlines does not send escalation for
     * bandings that are not in under_review status even if deadline passed.
     */
    public function test_process_deadlines_ignores_non_under_review_bandings(): void
    {
        Notification::fake();

        $user = $this->createPesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        // Create bandings with past deadlines but NOT in under_review status
        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'accepted',
            'alasan' => 'Banding accepted.',
            'keputusan' => 'Diterima setelah review.',
            'review_deadline' => now()->subDays(5),
            'decided_at' => now()->subDays(3),
        ]);

        // Call processDeadlines
        $result = $this->bandingService->processDeadlines();

        // Verify no notifications sent
        $this->assertEquals(0, $result['reminders_sent']);
        $this->assertEquals(0, $result['escalations_sent']);

        Notification::assertNothingSent();
    }
}
