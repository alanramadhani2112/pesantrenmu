<?php

namespace App\Console\Commands;

use App\Services\RejectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SendPerbaikanReminders
 *
 * Artisan command: akreditasi:send-perbaikan-reminders
 *
 * Sends reminder notifications to pesantren users whose perbaikan deadline
 * is approaching (within the configured reminder window, default: 3 days).
 *
 * Delegates to RejectionService::processDeadlines() which handles both
 * auto-rejection of expired deadlines and reminder dispatch for approaching ones.
 *
 * Task 13.2 — Req: 4.9, 4.10
 */
class SendPerbaikanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'akreditasi:send-perbaikan-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications for perbaikan deadlines approaching within 3 days';

    /**
     * Execute the console command.
     */
    public function handle(RejectionService $rejectionService): int
    {
        $this->info('Sending perbaikan deadline reminder notifications...');

        try {
            $result = $rejectionService->processDeadlines();

            $remindersSent = $result['reminders_sent'];
            $autoRejected = $result['auto_rejected'];

            $this->info("Reminders sent: {$remindersSent}");

            if ($autoRejected > 0) {
                $this->info("Also auto-rejected {$autoRejected} expired perbaikan(s) during this run.");
            }

            Log::info('akreditasi:send-perbaikan-reminders completed', [
                'reminders_sent' => $remindersSent,
                'auto_rejected' => $autoRejected,
            ]);
        } catch (\Throwable $e) {
            $this->error('Failed to send perbaikan reminders: '.$e->getMessage());

            Log::error('akreditasi:send-perbaikan-reminders failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
