<?php

namespace App\Console\Commands;

use App\Services\TrashService;
use Illuminate\Console\Command;

class PurgeTrashCommand extends Command
{
    protected $signature = 'trash:purge {--days= : Override retention period in days}';

    protected $description = 'Permanently delete soft-deleted akreditasi records older than the retention window.';

    public function handle(TrashService $trashService): int
    {
        $days = $this->option('days');
        $retentionDays = $days !== null
            ? (int) $days
            : (int) config('akreditasi.trash.retention_days', 90);

        if ($retentionDays < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $this->info("Purging trashed akreditasi older than {$retentionDays} day(s).");

        $result = $trashService->purgeExpired($retentionDays);

        $this->info(sprintf(
            'Purged: %d, Failed: %d',
            $result['purged'],
            $result['failed']
        ));

        if ($result['failed'] > 0) {
            $this->warn('Failures:');
            foreach ($result['errors'] as $error) {
                $this->line("  - akreditasi_id={$error['id']}: {$error['error']}");
            }
        }

        return self::SUCCESS;
    }
}
