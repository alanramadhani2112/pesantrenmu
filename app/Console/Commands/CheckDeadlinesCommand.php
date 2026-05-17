<?php

namespace App\Console\Commands;

use App\Services\DeadlineService;
use Illuminate\Console\Command;

class CheckDeadlinesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'akreditasi:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for approaching and overdue assessment/visitasi deadlines and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(DeadlineService $deadlineService): int
    {
        $this->info('Checking assessment/visitasi deadlines...');

        $deadlineService->processReminders();
        $this->info('Reminders processed.');

        $deadlineService->processEscalations();
        $this->info('Escalations processed.');

        return Command::SUCCESS;
    }
}
