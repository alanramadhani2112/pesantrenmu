<?php

namespace App\Console\Commands;

use App\Services\RejectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * CheckPerbaikanDeadlines
 *
 * Artisan command: akreditasi:check-perbaikan-deadlines
 *
 * Calls RejectionService::processDeadlines() to auto-reject akreditasi records
 * whose perbaikan deadline has expired. Logs the results.
 *
 * Task 13.1 — Req: 4.9, 4.10
 */
class CheckPerbaikanDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'akreditasi:check-perbaikan-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-reject akreditasi records whose perbaikan deadline has expired';

    /**
     * Execute the console command.
     */
    public function handle(RejectionService $rejectionService): int
    {
        $this->info('Checking perbaikan deadlines for auto-rejection...');

        try {
            $result = $rejectionService->processDeadlines();

            $autoRejected = $result['auto_rejected'];
            $remindersSent = $result['reminders_sent'];

            $this->info("Auto-rejected: {$autoRejected}");
            $this->info("Reminders sent: {$remindersSent}");

            Log::info('akreditasi:check-perbaikan-deadlines completed', [
                'auto_rejected' => $autoRejected,
                'reminders_sent' => $remindersSent,
            ]);
        } catch (\Throwable $e) {
            $this->error('Failed to process perbaikan deadlines: '.$e->getMessage());

            Log::error('akreditasi:check-perbaikan-deadlines failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
