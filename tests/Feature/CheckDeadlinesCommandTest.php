<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Tests for CheckDeadlinesCommand (akreditasi:check-deadlines)
 */
#[Group('Feature: assessment-visitasi-timeout')]
class CheckDeadlinesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Helper: create a pesantren user with a Pesantren record.
     */
    private function createPesantrenUser(string $pesantrenName = 'Pesantren Test'): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => $pesantrenName,
        ]);

        return $user;
    }

    /**
     * Helper: create an Asesor with an associated User.
     */
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
    // Task 4.4: Command is registered and scheduled daily
    // =========================================================================

    /**
     * Task 4.4: Verify the command is registered and scheduled daily.
     */
    public function test_command_is_registered(): void
    {
        // Verify the command exists and can be called
        $this->artisan('akreditasi:check-deadlines')
            ->assertExitCode(0);
    }

    /**
     * Task 4.4: Verify the command is scheduled daily in routes/console.php.
     */
    public function test_command_is_scheduled_daily(): void
    {
        // Read the console routes file and verify the schedule entry exists
        $consoleRoutesPath = base_path('routes/console.php');
        $this->assertFileExists($consoleRoutesPath);

        $content = file_get_contents($consoleRoutesPath);
        $this->assertStringContainsString(
            "Schedule::command('akreditasi:check-deadlines')->daily()",
            $content,
            'The akreditasi:check-deadlines command should be scheduled daily in routes/console.php'
        );
    }

    // =========================================================================
    // Task 4.5: Integration test — full command run with mixed data
    // =========================================================================

    /**
     * Task 4.5: Integration test — full command run with mixed data dispatches
     * correct notifications to correct recipients.
     *
     * Scenario:
     * - One assessment approaching deadline → reminder sent to asesor
     * - One assessment overdue → escalation sent to admin
     * - One assessment with future deadline (not approaching) → no notification
     * - One akreditasi with status 1 (completed) → no notification
     */
    public function test_full_command_run_with_mixed_data_dispatches_correct_notifications(): void
    {
        Notification::fake();

        $today = Carbon::create(2025, 11, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        config([
            'akreditasi-timeout.reminder.days_before_deadline' => 3,
            'akreditasi-timeout.escalation.interval_days' => 1,
        ]);

        // Create admin user
        $adminUser = User::factory()->create(['role_id' => 1]);

        // --- Scenario 1: Approaching deadline → reminder to asesor ---
        $pesantrenUser1 = $this->createPesantrenUser('Pesantren Approaching');
        $akreditasi1 = Akreditasi::create(['user_id' => $pesantrenUser1->id, 'status' => 5]);
        [$asesor1, $asesorUser1] = $this->createAsesorWithUser('Asesor Approaching');
        Assessment::create([
            'akreditasi_id' => $akreditasi1->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(10),
            'tanggal_berakhir' => $today->copy()->addDays(2), // 2 days from now (within 3-day threshold)
            'last_reminder_sent_at' => null,
        ]);

        // --- Scenario 2: Overdue → escalation to admin ---
        $pesantrenUser2 = $this->createPesantrenUser('Pesantren Overdue');
        $akreditasi2 = Akreditasi::create(['user_id' => $pesantrenUser2->id, 'status' => 4]);
        [$asesor2, $asesorUser2] = $this->createAsesorWithUser('Asesor Overdue');
        Assessment::create([
            'akreditasi_id' => $akreditasi2->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(20),
            'tanggal_berakhir' => $today->copy()->subDays(5), // 5 days overdue
            'last_escalation_sent_at' => null,
        ]);

        // --- Scenario 3: Future deadline (not approaching) → no notification ---
        $pesantrenUser3 = $this->createPesantrenUser('Pesantren Future');
        $akreditasi3 = Akreditasi::create(['user_id' => $pesantrenUser3->id, 'status' => 5]);
        [$asesor3, $asesorUser3] = $this->createAsesorWithUser('Asesor Future');
        Assessment::create([
            'akreditasi_id' => $akreditasi3->id,
            'asesor_id' => $asesor3->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(5),
            'tanggal_berakhir' => $today->copy()->addDays(20), // 20 days from now (outside threshold)
            'last_reminder_sent_at' => null,
        ]);

        // --- Scenario 4: Completed akreditasi (status 1) → no notification ---
        $pesantrenUser4 = $this->createPesantrenUser('Pesantren Completed');
        $akreditasi4 = Akreditasi::create(['user_id' => $pesantrenUser4->id, 'status' => 1]);
        [$asesor4, $asesorUser4] = $this->createAsesorWithUser('Asesor Completed');
        Assessment::create([
            'akreditasi_id' => $akreditasi4->id,
            'asesor_id' => $asesor4->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(20),
            'tanggal_berakhir' => $today->copy()->subDays(5), // overdue but status is 1
            'last_escalation_sent_at' => null,
        ]);

        // Run the command
        $this->artisan('akreditasi:check-deadlines')
            ->assertExitCode(0);

        // Scenario 1: Reminder sent to asesor1
        Notification::assertSentTo(
            $asesorUser1,
            AkreditasiNotification::class,
            function ($notification) {
                return in_array($notification->type, ['deadline_reminder', 'deadline_today']);
            }
        );

        // Scenario 2: Escalation sent to admin
        Notification::assertSentTo(
            $adminUser,
            AkreditasiNotification::class,
            function ($notification) {
                return $notification->type === 'deadline_overdue_escalation';
            }
        );

        // Scenario 3: No notification for asesor3 (future deadline)
        Notification::assertNotSentTo($asesorUser3, AkreditasiNotification::class);

        // Scenario 4: No escalation for completed akreditasi
        // (asesor4 should not get a reminder since deadline is past, not approaching)
        // Admin should not get escalation for status=1 akreditasi
        // We verify by checking the escalation notification count for admin is exactly 1
        Notification::assertSentToTimes($adminUser, AkreditasiNotification::class, 1);

        Carbon::setTestNow();
    }

    /**
     * Task 4.5: Command handles edge case — no assessments, exits successfully.
     */
    public function test_command_exits_successfully_with_no_assessments(): void
    {
        Notification::fake();

        $this->artisan('akreditasi:check-deadlines')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }
}
