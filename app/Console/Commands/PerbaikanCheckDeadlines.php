<?php

namespace App\Console\Commands;

use App\Services\RejectionService;
use Illuminate\Console\Command;

class PerbaikanCheckDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'perbaikan:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check perbaikan deadlines, send reminders, and auto-reject expired';

    /**
     * Execute the console command.
     */
    public function handle(RejectionService $rejectionService): int
    {
        $result = $rejectionService->processDeadlines();

        $this->info("Reminders sent: {$result['reminders_sent']}");
        $this->info("Auto-rejected: {$result['auto_rejected']}");

        return Command::SUCCESS;
    }
}
