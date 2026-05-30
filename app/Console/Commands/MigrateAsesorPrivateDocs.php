<?php

namespace App\Console\Commands;

use App\Models\Asesor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateAsesorPrivateDocs extends Command
{
    protected $signature = 'asesor:migrate-private-docs {--dry-run : Preview changes without moving files}';

    protected $description = 'Move KTP / ijazah / kartu_nbm files from public disk to local (private) disk';

    private array $privateFields = ['ktp_file', 'ijazah_file', 'kartu_nbm_file'];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no files will be moved.');
        }

        $asesors = Asesor::whereNotNull('ktp_file')
            ->orWhereNotNull('ijazah_file')
            ->orWhereNotNull('kartu_nbm_file')
            ->get();

        if ($asesors->isEmpty()) {
            $this->info('No asesor records with private documents found.');

            return self::SUCCESS;
        }

        $moved = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($asesors as $asesor) {
            foreach ($this->privateFields as $field) {
                $path = $asesor->$field;

                if (! $path) {
                    continue;
                }

                // Already on local disk — skip
                if (Storage::disk('local')->exists($path)) {
                    $this->line("  SKIP (already private): asesor #{$asesor->id} {$field}");
                    $skipped++;

                    continue;
                }

                // Must exist on public disk
                if (! Storage::disk('public')->exists($path)) {
                    $this->warn("  MISSING on public disk: asesor #{$asesor->id} {$field} → {$path}");
                    $errors++;

                    continue;
                }

                $newPath = 'asesor_private_docs/'.basename($path);

                $this->line("  MOVE: {$path} → {$newPath} (asesor #{$asesor->id} {$field})");

                if (! $dryRun) {
                    $contents = Storage::disk('public')->get($path);
                    Storage::disk('local')->put($newPath, $contents);
                    Storage::disk('public')->delete($path);

                    $asesor->update([$field => $newPath]);
                }

                $moved++;
            }
        }

        $this->newLine();
        $this->info("Done. Moved: {$moved} | Skipped (already private): {$skipped} | Errors: {$errors}");

        if ($dryRun && $moved > 0) {
            $this->warn('Re-run without --dry-run to apply changes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
