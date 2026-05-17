<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use App\Notifications\AkreditasiNotification;
use App\Services\ProgressTracker;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendAsesor2Reminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:asesor2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications to Asesor 2 who have not completed their NA2 assessment';

    /**
     * Execute the console command.
     */
    public function handle(ProgressTracker $progressTracker): int
    {
        // Task 5.2: Query Assessment tipe=2 with akreditasi status=5
        $assessments = Assessment::with(['akreditasi.user.pesantren', 'asesor.user'])
            ->where('tipe', 2)
            ->whereHas('akreditasi', fn ($q) => $q->where('status', 5))
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($assessments as $assessment) {
            $akreditasi = $assessment->akreditasi;
            $asesorUser = $assessment->asesor?->user;

            if (! $asesorUser || ! $akreditasi) {
                continue;
            }

            // Calculate completion
            $completion = $progressTracker->getCompletion($akreditasi->id, $assessment->asesor_id, 'isian');

            // Skip if already 100% complete
            if ($completion['percentage'] >= 100.0) {
                $skipped++;
                continue;
            }

            // Task 5.3: Rate limiting — skip if a reminder_asesor2 notification was already sent
            // today for this user+akreditasi combination (matched via the notification URL).
            $akreditasiUrl = route('asesor.akreditasi-detail', $akreditasi->uuid);
            $alreadySentToday = DB::table('notifications')
                ->where('notifiable_type', get_class($asesorUser))
                ->where('notifiable_id', $asesorUser->id)
                ->where('data', 'like', '%reminder_asesor2%')
                ->where('data', 'like', '%' . $akreditasi->uuid . '%')
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if ($alreadySentToday) {
                $skipped++;
                continue;
            }

            // Task 5.2: Determine notification type based on days since assignment
            $daysSinceAssignment = (int) Carbon::parse($assessment->created_at)->diffInDays(Carbon::now());
            $deadline = $assessment->tanggal_berakhir
                ? Carbon::parse($assessment->tanggal_berakhir)->format('d/m/Y')
                : '-';

            $pesantrenName = $akreditasi->user?->pesantren?->nama_pesantren ?? $akreditasi->user?->name ?? 'Pesantren';
            $percentage = number_format($completion['percentage'], 0);
            $filled = $completion['filled'];
            $total = $completion['total'];

            if ($daysSinceAssignment >= 7) {
                // Urgency reminder (>= 7 days since assignment)
                $title = 'Segera Selesaikan Penilaian NA2';
                $message = "Mendesak: Anda belum menyelesaikan penilaian NA2 untuk {$pesantrenName} ({$filled}/{$total} butir, {$percentage}%). Batas waktu: {$deadline}.";
            } else {
                // Initial reminder (< 7 days since assignment)
                $title = 'Pengingat: Penilaian NA2 Belum Selesai';
                $message = "Anda belum menyelesaikan penilaian NA2 untuk {$pesantrenName} ({$filled}/{$total} butir, {$percentage}%). Batas waktu: {$deadline}.";
            }

            // Task 5.4: Send notification via AkreditasiNotification with type reminder_asesor2
            $asesorUser->notify(new AkreditasiNotification(
                'reminder_asesor2',
                $title,
                $message,
                $akreditasiUrl
            ));

            $sent++;
            $this->info("Reminder sent to {$asesorUser->name} for {$pesantrenName} ({$percentage}%)");
        }

        $this->info("Reminders sent: {$sent}, skipped: {$skipped}");

        return Command::SUCCESS;
    }
}
