<?php

namespace App\Services;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DeadlineService
{
    protected int $assessmentDurationDays;

    protected int $visitasiDurationDays;

    protected int $reminderDaysBeforeDeadline;

    protected int $escalationIntervalDays;

    public function __construct()
    {
        $this->assessmentDurationDays = (int) config('akreditasi-timeout.assessment.default_duration_days', 30);
        $this->visitasiDurationDays = (int) config('akreditasi-timeout.visitasi.default_duration_days', 14);
        $this->reminderDaysBeforeDeadline = (int) config('akreditasi-timeout.reminder.days_before_deadline', 3);
        $this->escalationIntervalDays = (int) config('akreditasi-timeout.escalation.interval_days', 1);
    }

    /**
     * Returns true if the assessment's tanggal_berakhir is before today (overdue).
     */
    public function isOverdue(Assessment $assessment): bool
    {
        if (! $assessment->tanggal_berakhir) {
            return false;
        }

        return $assessment->tanggal_berakhir->startOfDay()->lt(Carbon::today());
    }

    /**
     * Returns true if the assessment's tanggal_berakhir is within the configured
     * reminder threshold and has not yet passed.
     */
    public function isApproachingDeadline(Assessment $assessment): bool
    {
        if (! $assessment->tanggal_berakhir) {
            return false;
        }

        $deadline = $assessment->tanggal_berakhir->startOfDay();
        $today = Carbon::today();

        // Not yet passed (deadline >= today) and within threshold
        return $deadline->gte($today)
            && $deadline->lte($today->copy()->addDays($this->reminderDaysBeforeDeadline));
    }

    /**
     * Returns the number of days past the deadline (0 if not overdue).
     */
    public function getDaysOverdue(Assessment $assessment): int
    {
        if (! $this->isOverdue($assessment)) {
            return 0;
        }

        return (int) $assessment->tanggal_berakhir->startOfDay()->diffInDays(Carbon::today());
    }

    /**
     * Returns all Akreditasi with status 4 or 5 where the associated Assessment's
     * tanggal_berakhir has passed. Eager loads assessments.
     */
    public function getOverdueAkreditasi(): Collection
    {
        return Akreditasi::whereIn('status', [4, 5])
            ->whereHas('assessments', function ($query) {
                $query->whereNotNull('tanggal_berakhir')
                    ->whereDate('tanggal_berakhir', '<', Carbon::today()->toDateString());
            })
            ->with('assessments')
            ->get();
    }

    /**
     * Returns the count of overdue akreditasi.
     */
    public function getOverdueCount(): int
    {
        return $this->getOverdueAkreditasi()->count();
    }

    /**
     * Returns all Asesor records excluding the currently assigned asesor,
     * with the user relationship eager loaded.
     */
    public function getAvailableAsesorsForReassignment(Assessment $assessment): Collection
    {
        return Asesor::where('id', '!=', $assessment->asesor_id)
            ->with('user')
            ->get();
    }

    /**
     * Process reminders: find approaching-deadline assessments, check deduplication,
     * send reminder notifications, and update last_reminder_sent_at.
     */
    public function processReminders(): void
    {
        $akreditasiList = Akreditasi::whereIn('status', [4, 5])
            ->with(['assessments.asesor.user', 'user.pesantren'])
            ->get();

        foreach ($akreditasiList as $akreditasi) {
            foreach ($akreditasi->assessments as $assessment) {
                if (! $assessment->tanggal_berakhir) {
                    continue;
                }

                if (! $this->isApproachingDeadline($assessment)) {
                    continue;
                }

                // Deduplication: skip if reminder already sent today
                if ($assessment->last_reminder_sent_at
                    && $assessment->last_reminder_sent_at->isToday()) {
                    continue;
                }

                if (! $assessment->asesor || ! $assessment->asesor->user) {
                    continue;
                }

                $pesantrenName = $akreditasi->user->pesantren->nama_pesantren
                    ?? $akreditasi->user->name
                    ?? 'Pesantren';

                $phase = $this->phaseLabelForStatus((int) $akreditasi->status);
                $deadline = $assessment->tanggal_berakhir->format('d/m/Y');
                $isToday = $assessment->tanggal_berakhir->startOfDay()->eq(Carbon::today());

                $type = $isToday ? 'deadline_today' : 'deadline_reminder';
                $title = $isToday
                    ? "Deadline {$phase} Hari Ini"
                    : "Pengingat Deadline {$phase}";
                $message = $isToday
                    ? "Deadline {$phase} untuk {$pesantrenName} adalah hari ini ({$deadline}). Segera selesaikan tugas Anda."
                    : "Deadline {$phase} untuk {$pesantrenName} adalah {$deadline}. Segera selesaikan tugas Anda.";

                $assessment->asesor->user->notify(
                    new AkreditasiNotification($type, $title, $message)
                );

                $assessment->update(['last_reminder_sent_at' => now()]);
            }
        }
    }

    /**
     * Process escalations: find overdue assessments, check interval deduplication,
     * send escalation notifications to all admin users, and update last_escalation_sent_at.
     */
    public function processEscalations(): void
    {
        $overdueAkreditasiList = $this->getOverdueAkreditasi()->load(['user.pesantren', 'assessments.asesor.user']);

        $admins = User::where('role_id', 1)->get();

        if ($admins->isEmpty()) {
            Log::error('DeadlineService: No admin users found for escalation notifications.');

            return;
        }

        foreach ($overdueAkreditasiList as $akreditasi) {
            foreach ($akreditasi->assessments as $assessment) {
                if (! $assessment->tanggal_berakhir) {
                    continue;
                }

                // Check escalation interval deduplication
                if ($assessment->last_escalation_sent_at) {
                    $daysSinceLastEscalation = (int) $assessment->last_escalation_sent_at
                        ->startOfDay()
                        ->diffInDays(Carbon::today());

                    if ($daysSinceLastEscalation < $this->escalationIntervalDays) {
                        continue;
                    }
                }

                $pesantrenName = $akreditasi->user->pesantren->nama_pesantren
                    ?? $akreditasi->user->name
                    ?? 'Pesantren';

                $asesorName = $assessment->asesor->nama_dengan_gelar
                    ?? $assessment->asesor->user->name
                    ?? 'Asesor';

                $phase = $this->phaseLabelForStatus((int) $akreditasi->status);
                $deadline = $assessment->tanggal_berakhir->format('d/m/Y');
                $daysOverdue = $this->getDaysOverdue($assessment);

                $type = 'deadline_overdue_escalation';
                $title = "Eskalasi: {$phase} Terlambat - {$pesantrenName}";
                $message = "{$phase} untuk {$pesantrenName} telah melewati deadline. "
                    ."Asesor: {$asesorName}. "
                    ."Deadline: {$deadline}. "
                    ."Terlambat: {$daysOverdue} hari.";

                foreach ($admins as $admin) {
                    $admin->notify(
                        new AkreditasiNotification($type, $title, $message)
                    );
                }

                $assessment->update(['last_escalation_sent_at' => now()]);
            }
        }
    }

    /**
     * Reassign asesor on an overdue akreditasi.
     * Validates the akreditasi is overdue, updates asesor_id, resets tanggal_berakhir,
     * clears tracking timestamps, and sends notifications to old and new asesor.
     *
     * @throws \DomainException if the akreditasi is not overdue
     */
    public function reassignAsesor(Assessment $assessment, int $newAsesorId): void
    {
        if (! $this->isOverdue($assessment)) {
            throw new \DomainException('Reassignment is only allowed for overdue akreditasi.');
        }

        $akreditasi = $assessment->akreditasi;
        $phase = (int) $akreditasi->status === Akreditasi::STATUS_VISITASI ? 'visitasi' : 'assessment';
        $duration = $phase === 'visitasi'
            ? $this->visitasiDurationDays
            : $this->assessmentDurationDays;

        $newDeadline = Carbon::today()->addDays($duration);

        $oldAsesorId = $assessment->asesor_id;
        $oldAsesor = Asesor::with('user')->find($oldAsesorId);

        $assessment->update([
            'asesor_id' => $newAsesorId,
            'tanggal_berakhir' => $newDeadline,
            'last_reminder_sent_at' => null,
            'last_escalation_sent_at' => null,
        ]);

        // Load relationships for notifications
        $assessment->load(['asesor.user', 'akreditasi.user.pesantren']);

        $pesantrenName = $akreditasi->user->pesantren->nama_pesantren
            ?? $akreditasi->user->name
            ?? 'Pesantren';

        $phaseLabel = $this->phaseLabelForStatus((int) $akreditasi->status);
        $deadlineFormatted = $newDeadline->format('d/m/Y');

        // Notify new asesor
        $newAsesor = Asesor::with('user')->find($newAsesorId);

        app(AuditTrailService::class)->log(
            akreditasiId: $assessment->akreditasi_id,
            actionType: 'asesor_reassigned',
            oldValue: $oldAsesor?->nama_dengan_gelar ?? $oldAsesor?->nama_tanpa_gelar,
            newValue: $newAsesor?->nama_dengan_gelar ?? $newAsesor?->nama_tanpa_gelar,
            metadata: [
                'old_asesor_id' => $oldAsesorId,
                'new_asesor_id' => $newAsesorId,
                'assessment_id' => $assessment->id,
                'tipe' => $assessment->tipe,
            ],
        );

        if ($newAsesor && $newAsesor->user) {
            $newAsesor->user->notify(new AkreditasiNotification(
                'asesor_reassigned_new',
                "Tugas {$phaseLabel} Baru",
                "Anda telah ditugaskan untuk {$phaseLabel} pesantren {$pesantrenName}. Deadline: {$deadlineFormatted}."
            ));
        }

        // Notify old asesor
        if ($oldAsesor && $oldAsesor->user) {
            $oldAsesor->user->notify(new AkreditasiNotification(
                'asesor_reassigned_old',
                "Tugas {$phaseLabel} Dialihkan",
                "Tugas {$phaseLabel} Anda untuk pesantren {$pesantrenName} telah dialihkan ke asesor lain."
            ));
        }
    }

    private function phaseLabelForStatus(int $status): string
    {
        return match ($status) {
            Akreditasi::STATUS_VERIFIKASI_BERKAS => 'Review Awal',
            Akreditasi::STATUS_ASSESSMENT => 'Review Asesor',
            Akreditasi::STATUS_VISITASI => 'Visitasi',
            Akreditasi::STATUS_PASCA_VISITASI => 'Penilaian Pasca Visitasi',
            default => Akreditasi::getStatusLabel($status),
        };
    }
}
