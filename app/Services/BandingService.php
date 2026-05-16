<?php

namespace App\Services;

use App\Models\Akreditasi;
use App\Models\AkreditasiCatatan;
use App\Models\Banding;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class BandingService
{
    protected PesantrenService $pesantrenService;

    public function __construct(PesantrenService $pesantrenService)
    {
        $this->pesantrenService = $pesantrenService;
    }

    /**
     * Check if a new banding can be submitted for the given akreditasi.
     * Returns ['allowed' => bool, 'remaining' => int, 'error' => ?string]
     */
    public function checkBandingEligibility(int $akreditasiId): array
    {
        $limit = (int) config('akreditasi.banding_limit');
        $count = Banding::where('akreditasi_id', $akreditasiId)->count();

        if ($count >= $limit) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'error' => "Batas pengajuan banding telah tercapai ({$count}/{$limit}).",
            ];
        }

        return [
            'allowed' => true,
            'remaining' => max(0, $limit - $count),
            'error' => null,
        ];
    }

    /**
     * Create a new banding record for the given akreditasi.
     * Checks appeal limit before creation.
     * Returns the Banding model or null if limit reached.
     */
    public function createBanding(int $akreditasiId, int $userId, string $alasan): ?Banding
    {
        $eligibility = $this->checkBandingEligibility($akreditasiId);

        if (!$eligibility['allowed']) {
            return null;
        }

        return Banding::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $userId,
            'status' => 'pending',
            'alasan' => $alasan,
        ]);
    }

    /**
     * Assign a reviewer to a pending banding.
     * Changes status to under_review, sets deadline, notifies.
     */
    public function assignReviewer(int $bandingId, int $reviewerId): bool
    {
        $banding = Banding::find($bandingId);

        if (!$banding || $banding->status !== 'pending') {
            return false;
        }

        $reviewDays = (int) config('akreditasi.banding_review_days');

        $banding->update([
            'status' => 'under_review',
            'reviewer_id' => $reviewerId,
            'review_deadline' => now()->addDays($reviewDays),
        ]);

        // Send notification to reviewer
        $reviewer = User::find($reviewerId);
        if ($reviewer) {
            $reviewer->notify(new AkreditasiNotification(
                'banding_review',
                'Tugas Review Banding',
                'Anda telah ditugaskan untuk mereview banding dari pesantren.',
                '#'
            ));
        }

        // Send notification to pesantren user
        $pesantrenUser = User::find($banding->user_id);
        if ($pesantrenUser) {
            $pesantrenUser->notify(new AkreditasiNotification(
                'banding_under_review',
                'Banding Sedang Direview',
                'Pengajuan banding Anda sedang dalam proses review.',
                '#'
            ));
        }

        return true;
    }

    /**
     * Reassign a reviewer on an under_review banding.
     * Recalculates deadline.
     */
    public function reassignReviewer(int $bandingId, int $newReviewerId): bool
    {
        $banding = Banding::find($bandingId);

        if (!$banding || $banding->status !== 'under_review') {
            return false;
        }

        $reviewDays = (int) config('akreditasi.banding_review_days');

        $banding->update([
            'reviewer_id' => $newReviewerId,
            'review_deadline' => now()->addDays($reviewDays),
        ]);

        return true;
    }

    /**
     * Accept a banding — creates new akreditasi submission.
     * Returns the new Akreditasi or null on failure.
     */
    public function acceptBanding(int $bandingId, string $keputusan): ?Akreditasi
    {
        $banding = Banding::find($bandingId);

        if (!$banding || $banding->status !== 'under_review') {
            return null;
        }

        if (strlen($keputusan) < 10) {
            return null;
        }

        return DB::transaction(function () use ($banding, $keputusan) {
            $banding->update([
                'status' => 'accepted',
                'keputusan' => $keputusan,
                'decided_at' => now(),
            ]);

            // Revert original akreditasi status to 2 before creating new submission
            // (createSubmission checks for existing active akreditasi in status 3,4,5,6)
            $originalAkreditasi = Akreditasi::find($banding->akreditasi_id);
            if ($originalAkreditasi) {
                $originalAkreditasi->update(['status' => 2]);
            }

            // Create new akreditasi submission via PesantrenService
            $newAkreditasi = $this->pesantrenService->createSubmission(
                $banding->user_id,
                $banding->akreditasi_id
            );

            // Send notification to pesantren user
            $pesantrenUser = User::find($banding->user_id);
            if ($pesantrenUser) {
                $pesantrenUser->notify(new AkreditasiNotification(
                    'banding_accepted',
                    'Banding Diterima',
                    'Pengajuan banding Anda telah diterima. Evaluasi ulang akan dilakukan.',
                    '#'
                ));
            }

            return $newAkreditasi;
        });
    }

    /**
     * Reject a banding — reverts akreditasi to status 2.
     */
    public function rejectBanding(int $bandingId, string $keputusan): bool
    {
        $banding = Banding::find($bandingId);

        if (!$banding || $banding->status !== 'under_review') {
            return false;
        }

        if (strlen($keputusan) < 10) {
            return false;
        }

        return DB::transaction(function () use ($banding, $keputusan) {
            $banding->update([
                'status' => 'rejected',
                'keputusan' => $keputusan,
                'decided_at' => now(),
            ]);

            // Revert akreditasi status to 2 (Ditolak)
            $akreditasi = Akreditasi::find($banding->akreditasi_id);
            if ($akreditasi) {
                $akreditasi->update(['status' => 2]);
            }

            // Create AkreditasiCatatan with rejection explanation
            AkreditasiCatatan::create([
                'akreditasi_id' => $banding->akreditasi_id,
                'user_id' => $banding->reviewer_id,
                'tipe' => 'banding_rejected',
                'catatan' => $keputusan,
            ]);

            // Send notification to pesantren user
            $pesantrenUser = User::find($banding->user_id);
            if ($pesantrenUser) {
                $pesantrenUser->notify(new AkreditasiNotification(
                    'banding_rejected',
                    'Banding Ditolak',
                    'Pengajuan banding Anda ditolak. Alasan: ' . $keputusan,
                    '#'
                ));
            }

            return true;
        });
    }

    /**
     * Get paginated banding list with optional status filter and search.
     * Eager loads akreditasi.user.pesantren and reviewer.
     * Default sort by created_at ascending (oldest first).
     */
    public function getPaginatedBandings(?string $statusFilter = null, ?string $search = null, int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Banding::with(['akreditasi.user.pesantren', 'reviewer'])
            ->orderBy('created_at', 'asc');

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->whereHas('akreditasi.user.pesantren', function ($q) use ($search) {
                $q->where('nama_pesantren', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Get active banding for an akreditasi (latest pending or under_review).
     */
    public function getActiveBanding(int $akreditasiId): ?Banding
    {
        return Banding::where('akreditasi_id', $akreditasiId)
            ->whereIn('status', ['pending', 'under_review'])
            ->latest()
            ->first();
    }

    /**
     * Get count of pending bandings (for admin badge).
     */
    public function getPendingCount(): int
    {
        return Banding::where('status', 'pending')->count();
    }

    /**
     * Process deadline checks — called by scheduled command.
     * Sends reminders and escalation notifications.
     */
    public function processDeadlines(): array
    {
        $reminderDays = (int) config('akreditasi.banding_reminder_days_before');
        $reminders = 0;
        $escalations = 0;

        // Find bandings approaching deadline (reminder)
        $reminderBandings = Banding::where('status', 'under_review')
            ->whereNotNull('review_deadline')
            ->where('review_deadline', '<=', now()->addDays($reminderDays))
            ->where('review_deadline', '>', now())
            ->get();

        foreach ($reminderBandings as $banding) {
            $reviewer = User::find($banding->reviewer_id);
            if ($reviewer) {
                $reviewer->notify(new AkreditasiNotification(
                    'banding_reminder',
                    'Pengingat Deadline Review Banding',
                    'Deadline review banding akan berakhir dalam ' . $banding->daysUntilDeadline() . ' hari.',
                    '#'
                ));
                $reminders++;
            }
        }

        // Find overdue bandings (escalation)
        $overdueBandings = Banding::where('status', 'under_review')
            ->whereNotNull('review_deadline')
            ->where('review_deadline', '<', now())
            ->get();

        if ($overdueBandings->isNotEmpty()) {
            $admins = User::whereHas('role', fn($q) => $q->where('id', 1))->get();

            foreach ($overdueBandings as $banding) {
                Notification::send($admins, new AkreditasiNotification(
                    'banding_escalation',
                    'Banding Melewati Deadline',
                    'Banding #' . $banding->id . ' telah melewati deadline review (' . $banding->daysOverdue() . ' hari).',
                    '#'
                ));
                $escalations++;
            }
        }

        return [
            'reminders_sent' => $reminders,
            'escalations_sent' => $escalations,
        ];
    }
}
