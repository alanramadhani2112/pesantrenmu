<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PerbaikanCheckDeadlinesTest extends TestCase
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
            'nama_pesantren' => 'Pesantren Perbaikan Test ' . $user->id,
        ]);
        return $user;
    }

    /**
     * Test: command calls RejectionService::processDeadlines() and outputs summary
     * with reminders sent and auto-rejected count.
     */
public function test_command_calls_process_deadlines_and_outputs_summary(): void
    {
        Notification::fake();

        config(['akreditasi.perbaikan_reminder_days_before' => 3]);

        $user = $this->createPesantrenUser();
        $asesor = User::factory()->create(['role_id' => 2]);

        // Create akreditasi with a rejection approaching deadline (within 3-day reminder threshold)
        $akreditasi1 = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi1->id,
            'user_id' => $asesor->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Profil perlu diperbaiki segera.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(2),
            'status' => 'pending',
        ]);

        // Create akreditasi with an expired rejection (past deadline)
        $user2 = $this->createPesantrenUser();
        $akreditasi2 = Akreditasi::create([
            'user_id' => $user2->id,
            'status' => 5,
        ]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi2->id,
            'user_id' => $asesor->id,
            'type' => 'asesor',
            'items' => ['sdm'],
            'explanation' => 'SDM data tidak lengkap.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->subDays(2),
            'status' => 'pending',
        ]);

        // Run the artisan command
        $this->artisan('perbaikan:check-deadlines')
            ->expectsOutput('Reminders sent: 1')
            ->expectsOutput('Auto-rejected: 1')
            ->assertExitCode(0);
    }

    /**
     * Test: command exits successfully with no pending rejections.
     */
public function test_command_exits_successfully_with_no_pending_deadlines(): void
    {
        Notification::fake();

        $this->artisan('perbaikan:check-deadlines')
            ->expectsOutput('Reminders sent: 0')
            ->expectsOutput('Auto-rejected: 0')
            ->assertExitCode(0);
    }
}
