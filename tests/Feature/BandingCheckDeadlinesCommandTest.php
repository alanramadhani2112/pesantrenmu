<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Banding;
use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BandingCheckDeadlinesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Helper: create a pesantren user with basic data.
     */
    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Command Test '.$user->id,
        ]);

        return $user;
    }

    /**
     * Test: command calls BandingService::processDeadlines() and outputs summary.
     */
    public function test_command_calls_process_deadlines_and_outputs_summary(): void
    {
        Notification::fake();

        config(['akreditasi.banding_reminder_days_before' => 3]);

        $user = $this->createPesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        // Create a banding with deadline 2 days from now (within 3-day reminder threshold)
        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Kami merasa penilaian tidak adil.',
            'review_deadline' => now()->addDays(2),
        ]);

        // Create a banding with deadline in the past (overdue)
        $akreditasi2 = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        Banding::create([
            'akreditasi_id' => $akreditasi2->id,
            'user_id' => $user->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Banding kedua yang sudah overdue.',
            'review_deadline' => now()->subDays(2),
        ]);

        // Run the artisan command
        $this->artisan('banding:check-deadlines')
            ->expectsOutput('Reminders sent: 1')
            ->expectsOutput('Escalations sent: 1')
            ->assertExitCode(0);
    }

    /**
     * Test: command exits with success code when no deadlines to process.
     */
    public function test_command_exits_successfully_with_no_pending_deadlines(): void
    {
        Notification::fake();

        $this->artisan('banding:check-deadlines')
            ->expectsOutput('Reminders sent: 0')
            ->expectsOutput('Escalations sent: 0')
            ->assertExitCode(0);
    }
}
