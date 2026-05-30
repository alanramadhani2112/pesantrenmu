<?php

namespace App\Listeners;

use App\Events\AkreditasiTransitioned;
use App\Events\AsesorAssigned;
use App\Events\AsesorPackageSubmitted;
use App\Events\BandingDecided;
use App\Events\BandingSubmitted;
use App\Events\PerbaikanDeadlineApproaching;
use App\Events\PerbaikanRequested;
use App\Events\PerbaikanSubmitted;
use App\Events\ScoringCompleted;
use App\Events\SKIssued;
use App\Events\VisitasiScheduled;
use App\Models\Akreditasi;
use App\Models\Assessment;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\StateMachine\AkreditasiStateMachine;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * AkreditasiNotificationListener
 *
 * Listens to all akreditasi workflow events and dispatches the appropriate
 * AkreditasiNotification to the relevant users.
 *
 * Implements ShouldQueue so notification dispatch is non-blocking.
 *
 * Task 12.2 — Req: 2.6, 3.5, 3.8, 4.3, 4.5, 4.10, 5.5, 5.8, 6.4, 7.14, 11.5, 14.2, 14.9
 */
class AkreditasiNotificationListener implements ShouldQueue
{
    /**
     * Handle AkreditasiTransitioned events.
     *
     * Dispatches notifications based on the specific transition that occurred.
     */
    public function handleAkreditasiTransitioned(AkreditasiTransitioned $event): void
    {
        try {
            $akreditasi = $event->akreditasi;
            $from = $event->fromStatus;
            $to = $event->toStatus;

            match (true) {
                // 6 → 5: Admin opens for review — notify admins
                $from === AkreditasiStateMachine::STATUS_PENGAJUAN
                    && $to === AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS => $this->notifyOpenForReview($akreditasi),

                // 5 → -1: Berkas rejected — notify pesantren
                $from === AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS
                    && $to === AkreditasiStateMachine::STATUS_DITOLAK => $this->notifyBerkasRejected($akreditasi),

                // 4 → -1: Auto-rejected at assessment — notify pesantren
                $from === AkreditasiStateMachine::STATUS_ASSESSMENT
                    && $to === AkreditasiStateMachine::STATUS_DITOLAK => $this->notifyAssessmentRejected($akreditasi),

                // 3 → 2: Visitasi confirmed selesai — notify pesantren, asesor2, admins
                $from === AkreditasiStateMachine::STATUS_VISITASI
                    && $to === AkreditasiStateMachine::STATUS_PASCA_VISITASI => $this->notifyVisitasiSelesai($akreditasi),

                // 1 → -1: Rejected at validasi admin — notify pesantren
                $from === AkreditasiStateMachine::STATUS_VALIDASI_ADMIN
                    && $to === AkreditasiStateMachine::STATUS_DITOLAK => $this->notifyValidasiRejected($akreditasi),

                // -2 → 1: Banding accepted, back to final admin validation — notify pesantren
                $from === AkreditasiStateMachine::STATUS_BANDING
                    && $to === AkreditasiStateMachine::STATUS_VALIDASI_ADMIN => $this->notifyBandingAcceptedValidasiAdmin($akreditasi),

                // -2 → -1: Banding rejected — notify pesantren
                $from === AkreditasiStateMachine::STATUS_BANDING
                    && $to === AkreditasiStateMachine::STATUS_DITOLAK => $this->notifyBandingRejectedFinal($akreditasi),

                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('AkreditasiNotificationListener: handleAkreditasiTransitioned failed', [
                'akreditasi_id' => $event->akreditasi->id,
                'from' => $event->fromStatus,
                'to' => $event->toStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle PerbaikanSubmitted events.
     *
     * Notifies Asesor_1 and Admins that perbaikan has been submitted.
     */
    public function handlePerbaikanSubmitted(PerbaikanSubmitted $event): void
    {
        try {
            $akreditasi = $event->akreditasi;

            // Notify Asesor_1
            $assessment = Assessment::where('akreditasi_id', $akreditasi->id)
                ->where('tipe', 1)
                ->with('asesor')
                ->first();

            if ($assessment && $assessment->asesor) {
                $asesor1User = User::find($assessment->asesor->user_id);
                if ($asesor1User) {
                    $asesor1User->notify(new AkreditasiNotification(
                        'perbaikan_submitted',
                        'Perbaikan Disubmit',
                        'Pesantren telah mengirimkan perbaikan dokumen dan siap untuk direview ulang.',
                        '#'
                    ));
                }
            }

            // Notify Admins
            $admins = User::where('role_id', 1)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AkreditasiNotification(
                    'perbaikan_submitted_admin',
                    'Perbaikan Disubmit',
                    'Pesantren telah mengirimkan perbaikan dokumen akreditasi.',
                    '#'
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiNotificationListener: handlePerbaikanSubmitted failed', [
                'akreditasi_id' => $event->akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle VisitasiScheduled events.
     *
     * Notifies Pesantren and Admins about the visitasi schedule.
     */
    public function handleVisitasiScheduled(VisitasiScheduled $event): void
    {
        try {
            $akreditasi = $event->akreditasi;
            $schedule = $event->schedule;
            $isReschedule = $event->isReschedule;

            $mulaiFormatted = Carbon::parse($schedule['tanggal_mulai'])->format('d/m/Y');
            $akhirFormatted = Carbon::parse($schedule['tanggal_akhir'])->format('d/m/Y');
            $catatan = $schedule['catatan_visitasi'] ?? '';
            $action = $isReschedule ? 'dijadwalkan ulang' : 'dijadwalkan';
            $title = $isReschedule ? 'Visitasi Dijadwalkan Ulang' : 'Visitasi Dijadwalkan';
            $message = "Visitasi telah {$action}: {$mulaiFormatted} s/d {$akhirFormatted}.".
                ($catatan ? " Catatan: {$catatan}" : '');

            // Notify Pesantren
            $pesantrenUser = User::find($akreditasi->user_id);
            if ($pesantrenUser) {
                $pesantrenUser->notify(new AkreditasiNotification(
                    $isReschedule ? 'visitasi_rescheduled' : 'visitasi_scheduled',
                    $title,
                    $message,
                    '#'
                ));
            }

            // Notify Anggota Kelompok (Asesor 2)
            $assessment2 = Assessment::where('akreditasi_id', $akreditasi->id)
                ->where('tipe', 2)
                ->with('asesor')
                ->first();

            if ($assessment2 && $assessment2->asesor) {
                $asesor2User = User::find($assessment2->asesor->user_id);
                if ($asesor2User) {
                    $asesor2User->notify(new AkreditasiNotification(
                        $isReschedule ? 'visitasi_rescheduled_asesor2' : 'visitasi_scheduled_asesor2',
                        $title,
                        $message,
                        '#'
                    ));
                }
            }

            // Notify Admins
            $admins = User::where('role_id', 1)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AkreditasiNotification(
                    $isReschedule ? 'visitasi_rescheduled_admin' : 'visitasi_scheduled_admin',
                    $title,
                    $message,
                    '#'
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiNotificationListener: handleVisitasiScheduled failed', [
                'akreditasi_id' => $event->akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle ScoringCompleted events.
     *
     * Notifies Admins that assessor scoring has been finalized.
     */
    public function handleScoringCompleted(ScoringCompleted $event): void
    {
        try {
            $admins = User::where('role_id', 1)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AkreditasiNotification(
                    'scoring_finalized',
                    'Penilaian Asesor Selesai',
                    'Semua penilaian asesor telah difinalisasi. Akreditasi siap untuk validasi admin.',
                    '#'
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiNotificationListener: handleScoringCompleted failed', [
                'akreditasi_id' => $event->akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle SKIssued events.
     *
     * Notifies Pesantren with the final score, grade, and SK number.
     */
    public function handleSKIssued(SKIssued $event): void
    {
        try {
            $akreditasi = $event->akreditasi;
            $pesantrenUser = User::find($akreditasi->user_id);
            if ($pesantrenUser) {
                $pesantrenUser->notify(new AkreditasiNotification(
                    'sk_issued',
                    'SK Akreditasi Diterbitkan',
                    "Akreditasi Anda telah selesai. Nilai Akhir: {$event->nilaiAkhir}, Peringkat: {$event->peringkat}, Nomor SK: {$event->nomorSk}.",
                    '#'
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiNotificationListener: handleSKIssued failed', [
                'akreditasi_id' => $event->akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle BandingSubmitted events.
     *
     * Notifies Admins about the new banding submission.
     */
    public function handleBandingSubmitted(BandingSubmitted $event): void
    {
        try {
            $admins = User::where('role_id', 1)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AkreditasiNotification(
                    'banding_submitted',
                    'Pengajuan Banding Baru',
                    'Pesantren telah mengajukan banding untuk akreditasi #'.$event->akreditasi->id.'.',
                    '#'
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiNotificationListener: handleBandingSubmitted failed', [
                'akreditasi_id' => $event->akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle BandingDecided events.
     *
     * Notifies Pesantren about the banding decision.
     */
    public function handleBandingDecided(BandingDecided $event): void
    {
        try {
            $banding = $event->banding;
            $pesantrenUser = User::find($banding->user_id);
            if ($pesantrenUser) {
                if ($event->result === 'diterima') {
                    $pesantrenUser->notify(new AkreditasiNotification(
                        'banding_accepted',
                        'Banding Diterima',
                        'Pengajuan banding Anda telah diterima. Proses akreditasi kembali ke tahap Validasi Akhir Admin.',
                        '#'
                    ));
                } else {
                    $pesantrenUser->notify(new AkreditasiNotification(
                        'banding_rejected',
                        'Banding Ditolak',
                        'Pengajuan banding Anda telah ditolak.',
                        '#'
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiNotificationListener: handleBandingDecided failed', [
                'banding_id' => $event->banding->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle PerbaikanDeadlineApproaching events.
     *
     * Notifies Pesantren about the approaching perbaikan deadline.
     */
    public function handlePerbaikanDeadlineApproaching(PerbaikanDeadlineApproaching $event): void
    {
        try {
            $akreditasi = $event->akreditasi;
            $daysRemaining = $event->daysRemaining;

            $pesantrenUser = User::find($akreditasi->user_id);
            if ($pesantrenUser) {
                $pesantrenUser->notify(new AkreditasiNotification(
                    'perbaikan_deadline_reminder',
                    'Pengingat Deadline Perbaikan',
                    "Batas waktu perbaikan dokumen akan berakhir dalam {$daysRemaining} hari. Segera kirimkan perbaikan Anda.",
                    '#'
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiNotificationListener: handlePerbaikanDeadlineApproaching failed', [
                'akreditasi_id' => $event->akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle AsesorAssigned events.
     *
     * Notifies both Ketua Kelompok and Anggota Kelompok when assigned to an akreditasi.
     */
    public function handleAsesorAssigned(AsesorAssigned $event): void
    {
        try {
            $akreditasi = $event->akreditasi;

            // Notify Ketua Kelompok (Asesor 1)
            $event->asesor1User->notify(new AkreditasiNotification(
                'asesor_assigned_ketua',
                'Penugasan Asesor',
                "Anda ditugaskan sebagai Ketua Kelompok untuk akreditasi pesantren #{$akreditasi->id}.",
                '#'
            ));

            // Notify Anggota Kelompok (Asesor 2)
            $event->asesor2User->notify(new AkreditasiNotification(
                'asesor_assigned_anggota',
                'Penugasan Asesor',
                "Anda ditugaskan sebagai Anggota Kelompok untuk akreditasi pesantren #{$akreditasi->id}.",
                '#'
            ));
        } catch (\Throwable $e) {
            Log::error('AkreditasiNotificationListener: handleAsesorAssigned failed', [
                'akreditasi_id' => $event->akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle PerbaikanRequested events.
     *
     * Notifies Pesantren when admin returns application for administrative revision.
     */
    public function handlePerbaikanRequested(PerbaikanRequested $event): void
    {
        try {
            $akreditasi = $event->akreditasi;
            $pesantrenUser = User::find($akreditasi->user_id);

            if ($pesantrenUser) {
                $message = 'Akreditasi Anda dikembalikan untuk perbaikan administratif. Silakan perbaiki dan submit ulang.';
                if ($event->catatan) {
                    $message .= " Catatan: {$event->catatan}";
                }

                $pesantrenUser->notify(new AkreditasiNotification(
                    'perbaikan_requested',
                    'Perbaikan Diperlukan',
                    $message,
                    '#'
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiNotificationListener: handlePerbaikanRequested failed', [
                'akreditasi_id' => $event->akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle AsesorPackageSubmitted events.
     *
     * Notifies Admin when Ketua Kelompok submits final asesor package.
     */
    public function handleAsesorPackageSubmitted(AsesorPackageSubmitted $event): void
    {
        try {
            $akreditasi = $event->akreditasi;
            $admins = User::where('role_id', 1)->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AkreditasiNotification(
                    'asesor_package_submitted',
                    'Paket Asesor Lengkap',
                    "Ketua Kelompok telah menyelesaikan penilaian untuk akreditasi #{$akreditasi->id}. Silakan lakukan validasi akhir.",
                    '#'
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiNotificationListener: handleAsesorPackageSubmitted failed', [
                'akreditasi_id' => $event->akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Private notification helpers for AkreditasiTransitioned sub-cases
    // =========================================================================

    private function notifyOpenForReview(Akreditasi $akreditasi): void
    {
        $admins = User::where('role_id', 1)->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new AkreditasiNotification(
                'open_for_review',
                'Akreditasi Dibuka untuk Review',
                'Akreditasi #'.$akreditasi->id.' telah dibuka untuk verifikasi berkas.',
                '#'
            ));
        }
    }

    private function notifyBerkasRejected(Akreditasi $akreditasi): void
    {
        $pesantrenUser = User::find($akreditasi->user_id);
        if ($pesantrenUser) {
            $pesantrenUser->notify(new AkreditasiNotification(
                'berkas_rejected',
                'Berkas Akreditasi Ditolak',
                'Berkas akreditasi Anda telah ditolak pada tahap Verifikasi Berkas.',
                '#'
            ));
        }
    }

    private function notifyAssessmentRejected(Akreditasi $akreditasi): void
    {
        $pesantrenUser = User::find($akreditasi->user_id);
        if ($pesantrenUser) {
            $pesantrenUser->notify(new AkreditasiNotification(
                'assessment_rejected',
                'Akreditasi Ditolak',
                'Akreditasi Anda telah ditolak pada tahap Assessment.',
                '#'
            ));
        }
    }

    private function notifyVisitasiSelesai(Akreditasi $akreditasi): void
    {
        $title = 'Visitasi Selesai — Tahap Penilaian Dimulai';
        $message = 'Visitasi telah dikonfirmasi selesai. Tahap penilaian pasca visitasi telah dimulai.';

        // Notify Pesantren
        $pesantrenUser = User::find($akreditasi->user_id);
        if ($pesantrenUser) {
            $pesantrenUser->notify(new AkreditasiNotification(
                'visitasi_selesai',
                $title,
                $message,
                '#'
            ));
        }

        // Notify Anggota Kelompok
        $assessment2 = Assessment::where('akreditasi_id', $akreditasi->id)
            ->where('tipe', 2)
            ->with('asesor')
            ->first();

        if ($assessment2 && $assessment2->asesor) {
            $asesor2User = User::find($assessment2->asesor->user_id);
            if ($asesor2User) {
                $asesor2User->notify(new AkreditasiNotification(
                    'visitasi_selesai_asesor2',
                    $title,
                    'Visitasi telah selesai. Silakan mulai mengisi Nilai Anggota.',
                    '#'
                ));
            }
        }

        // Notify Admins
        $admins = User::where('role_id', 1)->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new AkreditasiNotification(
                'visitasi_selesai_admin',
                $title,
                $message,
                '#'
            ));
        }
    }

    private function notifyValidasiRejected(Akreditasi $akreditasi): void
    {
        $pesantrenUser = User::find($akreditasi->user_id);
        if ($pesantrenUser) {
            $pesantrenUser->notify(new AkreditasiNotification(
                'validasi_rejected',
                'Akreditasi Ditolak pada Validasi Admin',
                'Akreditasi Anda telah ditolak pada tahap Validasi Admin.',
                '#'
            ));
        }
    }

    private function notifyBandingAcceptedValidasiAdmin(Akreditasi $akreditasi): void
    {
        $pesantrenUser = User::find($akreditasi->user_id);
        if ($pesantrenUser) {
            $pesantrenUser->notify(new AkreditasiNotification(
                'banding_accepted_validasi_admin',
                'Banding Diterima — Validasi Akhir Admin',
                'Banding Anda diterima. Proses akreditasi kembali ke tahap Validasi Akhir Admin.',
                '#'
            ));
        }
    }

    private function notifyBandingRejectedFinal(Akreditasi $akreditasi): void
    {
        $pesantrenUser = User::find($akreditasi->user_id);
        if ($pesantrenUser) {
            $pesantrenUser->notify(new AkreditasiNotification(
                'banding_rejected_final',
                'Banding Ditolak',
                'Banding Anda telah ditolak. Akreditasi berstatus Ditolak.',
                '#'
            ));
        }
    }
}
