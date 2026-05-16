<?php

namespace App\Console\Commands;

use App\Services\BandingService;
use Illuminate\Console\Command;

class BandingCheckDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'banding:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check banding review deadlines and send reminders/escalations';

    /**
     * Execute the console command.
     */
    public function handle(BandingService $bandingService): int
    {
        $result = $bandingService->processDeadlines();

        $this->info("Reminders sent: {$result['reminders_sent']}");
        $this->info("Escalations sent: {$result['escalations_sent']}");

        return Command::SUCCESS;
    }
}
