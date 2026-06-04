<?php

namespace App\Services;

use App\Events\BandingDecided;
use App\Events\BandingSubmitted;
use App\Exceptions\InvalidTransitionException;
use App\Models\Akreditasi;
use App\Models\AkreditasiBandingEdpm;
use App\Models\AkreditasiBandingEdpmCatatan;
use App\Models\AkreditasiCatatan;
use App\Models\Assessment;
use App\Models\Banding;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\StateMachine\AkreditasiStateMachine;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class BandingService
{
    protected PesantrenService $pesantrenService;

    protected AkreditasiStateMachine $stateMachine;

    public function __construct(
        PesantrenService $pesantrenService,
        AkreditasiStateMachine $stateMachine
    ) {
        $this->pesantrenService = $pesantrenService;
        $this->stateMachine = $stateMachine;
    }

    // =========================================================================
    // New spec-compliant methods (Req 14.1-14.10)
    // =========================================================================

    /**
     * Submit a banding (appeal) for a rejected akreditasi.
     *
     * Validates:
     *  - Akreditasi is at status -1 (Ditolak)
     *  - Assessors were previously assigned (akreditasi reached status 4 or beyond)
     *  - No existing banding record for this akreditasi (only 1 banding per akreditasi)
     *  - Within 14-day window from rejection date
     *
     * Transitions: -1 → -2 via AkreditasiStateMachine
     * Notifies: Admin
     *
     * Returns ['success' => bool, 'banding' => ?Banding, 'error' => ?string]
     *
     * Validates Requirements 14.1, 14.2, 14.3, 14.10
     */
    public function submitBanding(int $akreditasiId, int $pesantrenId, string $alasan): array
    {
        $akreditasi = Akreditasi::withTrashed()->find($akreditasiId);

        if (! $akreditasi) {
            return ['success' => false, 'banding' => null, 'error' => 'Akreditasi tidak ditemukan.'];
        }

        // Must be at status -1 (Ditolak)
        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_DITOLAK) {
            return [
                'success' => false,
                'banding' => null,
                'error' => 'Banding hanya dapat diajukan untuk akreditasi yang berstatus Ditolak.',
            ];
        }

        // Validate: assessors were previously assigned (akreditasi reached status 4 or beyond)
        // This is indicated by the existence of Assessment records for this akreditasi
        $hasAssessors = Assessment::where('akreditasi_id', $akreditasiId)->exists();
        if (! $hasAssessors) {
            return [
                'success' => false,
                'banding' => null,
                'error' => 'Banding tidak tersedia untuk penolakan pada tahap Verifikasi Berkas (asesor belum pernah ditugaskan).',
            ];
        }

        if ((int) config('akreditasi.banding_limit', 1) <= 0) {
            return [
                'success' => false,
                'banding' => null,
                'error' => 'Pengajuan banding saat ini tidak tersedia.',
            ];
        }

        // Validate: no existing banding record for this akreditasi (only 1 banding per akreditasi)
        $existingBanding = Banding::where('akreditasi_id', $akreditasiId)->exists();
        if ($existingBanding) {
            return [
                'success' => false,
                'banding' => null,
                'error' => 'Hanya 1 banding yang diperbolehkan per akreditasi.',
            ];
        }

        // Validate: within 14-day window from rejection date
        // The rejection date is the updated_at timestamp when status became -1
        $rejectionDate = Carbon::parse($akreditasi->updated_at);
        $windowEnd = $rejectionDate->copy()->addDays(14);
        if (Carbon::now()->gt($windowEnd)) {
            return [
                'success' => false,
                'banding' => null,
                'error' => 'Masa pengajuan banding telah berakhir (14 hari sejak tanggal penolakan).',
            ];
        }

        // Perform the transition and create banding record in a transaction
        $pesantrenUser = User::find($pesantrenId);
        if (! $pesantrenUser) {
            return ['success' => false, 'banding' => null, 'error' => 'Pengguna pesantren tidak ditemukan.'];
        }

        try {
            $banding = DB::transaction(function () use ($akreditasi, $pesantrenId, $alasan, $pesantrenUser) {
                // Transition -1 → -2
                $this->stateMachine->transition(
                    $akreditasi,
                    AkreditasiStateMachine::STATUS_BANDING,
                    $pesantrenUser
                );

                // Create banding record
                $banding = Banding::create([
                    'akreditasi_id' => $akreditasi->id,
                    'user_id' => $pesantrenId,
                    'status' => 'pending',
                    'alasan' => $alasan,
                ]);

                // Notify all Admin users
                $admins = User::where('role_id', 1)->get();
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new AkreditasiNotification(
                        'banding_submitted',
                        'Pengajuan Banding Baru',
                        'Pesantren telah mengajukan banding untuk akreditasi #'.$akreditasi->id.'.',
                        '#'
                    ));
                }

                return $banding;
            });

            // Task 12.3: Dispatch BandingSubmitted event after transaction commits
            event(new BandingSubmitted($akreditasi, $banding));

            return ['success' => true, 'banding' => $banding, 'error' => null];
        } catch (InvalidTransitionException $e) {
            return ['success' => false, 'banding' => null, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error('BandingService::submitBanding failed', [
                'akreditasi_id' => $akreditasiId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'banding' => null, 'error' => 'Terjadi kesalahan saat mengajukan banding.'];
        }
    }

    /**
     * Record the admin's decision on a banding.
     *
     * $result must be 'diterima' or 'ditolak'.
     *
     * If 'diterima':
     *   - Reassign same Asesor_1 and Asesor_2 (restore Assessment records)
     *   - Transition -2 → 1 (Validasi Akhir Admin)
     *
     * If 'ditolak':
     *   - Transition -2 → -1 (Ditolak)
     *
     * Notifies: Pesantren
     *
     * Returns ['success' => bool, 'error' => ?string]
     *
     * Validates Requirements 14.4, 14.5, 14.6, 14.9
     */
    public function decideBanding(int $bandingId, int $adminId, string $result): array
    {
        if (! in_array($result, ['diterima', 'ditolak'], true)) {
            return ['success' => false, 'error' => 'Hasil banding harus "diterima" atau "ditolak".'];
        }

        $banding = Banding::find($bandingId);
        if (! $banding) {
            return ['success' => false, 'error' => 'Banding tidak ditemukan.'];
        }

        $akreditasi = Akreditasi::withTrashed()->find($banding->akreditasi_id);
        if (! $akreditasi) {
            return ['success' => false, 'error' => 'Akreditasi tidak ditemukan.'];
        }

        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_BANDING) {
            return ['success' => false, 'error' => 'Akreditasi tidak berada pada status Banding.'];
        }

        $adminUser = User::find($adminId);
        if (! $adminUser) {
            return ['success' => false, 'error' => 'Pengguna admin tidak ditemukan.'];
        }

        try {
            DB::transaction(function () use ($banding, $akreditasi, $adminId, $adminUser, $result) {
                if ($result === 'diterima') {
                    // Reassign same Asesor_1 and Asesor_2
                    // Restore soft-deleted assessment records for this akreditasi
                    Assessment::withTrashed()
                        ->where('akreditasi_id', $akreditasi->id)
                        ->restore();

                    // Transition -2 → 1 (Validasi Akhir Admin)
                    $this->stateMachine->transition(
                        $akreditasi,
                        AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
                        $adminUser
                    );

                    // Update banding record
                    $banding->update([
                        'status' => 'accepted',
                        'reviewer_id' => $adminId,
                        'keputusan' => 'Diterima',
                        'decided_at' => now(),
                    ]);

                    // Notify Pesantren
                    $pesantrenUser = User::find($banding->user_id);
                    if ($pesantrenUser) {
                        $pesantrenUser->notify(new AkreditasiNotification(
                            'banding_accepted',
                            'Banding Diterima',
                            'Pengajuan banding Anda telah diterima. Proses akreditasi kembali ke tahap Validasi Akhir Admin.',
                            '#'
                        ));
                    }
                } else {
                    // Transition -2 → -1 (Ditolak)
                    $this->stateMachine->transition(
                        $akreditasi,
                        AkreditasiStateMachine::STATUS_DITOLAK,
                        $adminUser
                    );

                    // Update banding record
                    $banding->update([
                        'status' => 'rejected',
                        'reviewer_id' => $adminId,
                        'keputusan' => 'Ditolak',
                        'decided_at' => now(),
                    ]);

                    // Notify Pesantren
                    $pesantrenUser = User::find($banding->user_id);
                    if ($pesantrenUser) {
                        $pesantrenUser->notify(new AkreditasiNotification(
                            'banding_rejected',
                            'Banding Ditolak',
                            'Pengajuan banding Anda telah ditolak.',
                            '#'
                        ));
                    }
                }
            });

            // Task 12.3: Dispatch BandingDecided event after transaction commits
            event(new BandingDecided($banding, $result));

            return ['success' => true, 'error' => null];
        } catch (InvalidTransitionException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error('BandingService::decideBanding failed', [
                'banding_id' => $bandingId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Terjadi kesalahan saat memproses keputusan banding.'];
        }
    }

    /**
     * Determine whether scoring data for a given akreditasi should be stored
     * in the banding tables (akreditasi_banding_edpms) rather than the
     * original tables (akreditasi_edpms).
     *
     * NOTE: This method and related banding-table methods are defined but
     * not currently invoked from any caller. The AkreditasiBandingEdpm model
     * and migration exist for future banding-scoring integration.
     *
     * Returns true when:
     *   - akreditasi status is 2 (Penilaian Pasca Visitasi)
     *   - AND a banding record with status 'accepted' exists for this akreditasi
     *
     * Validates Requirement 14.7, 14.8
     */
    public function shouldUseBandingTables(int $akreditasiId): bool
    {
        $akreditasi = Akreditasi::find($akreditasiId);
        if (! $akreditasi || (int) $akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            return false;
        }

        return Banding::where('akreditasi_id', $akreditasiId)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * Get the accepted banding record for an akreditasi, if any.
     */
    public function getAcceptedBanding(int $akreditasiId): ?Banding
    {
        return Banding::where('akreditasi_id', $akreditasiId)
            ->where('status', 'accepted')
            ->first();
    }

    /**
     * Store a scoring entry in the banding EDPM table (post-banding assessment).
     * This ensures original akreditasi_edpms data remains unchanged.
     *
     * Validates Requirement 14.7
     */
    public function storeBandingEdpm(int $akreditasiId, int $bandingId, array $data): AkreditasiBandingEdpm
    {
        return AkreditasiBandingEdpm::updateOrCreate(
            [
                'akreditasi_id' => $akreditasiId,
                'banding_id' => $bandingId,
                'asesor_id' => $data['asesor_id'],
                'butir_id' => $data['butir_id'],
            ],
            array_filter([
                'isian' => $data['isian'] ?? null,
                'nk' => $data['nk'] ?? null,
                'nv' => $data['nv'] ?? null,
                'catatan_butir' => $data['catatan_butir'] ?? null,
                'is_final' => $data['is_final'] ?? false,
            ], fn ($v) => $v !== null)
        );
    }

    /**
     * Store a catatan entry in the banding EDPM catatan table.
     *
     * Validates Requirement 14.7
     */
    public function storeBandingEdpmCatatan(int $akreditasiId, int $bandingId, array $data): AkreditasiBandingEdpmCatatan
    {
        return AkreditasiBandingEdpmCatatan::updateOrCreate(
            [
                'akreditasi_id' => $akreditasiId,
                'banding_id' => $bandingId,
                'komponen_id' => $data['komponen_id'],
            ],
            [
                'catatan' => $data['catatan'] ?? null,
                'rekomendasi' => $data['rekomendasi'] ?? null,
            ]
        );
    }

    // =========================================================================
    // Legacy methods (preserved for backward compatibility)
    // =========================================================================

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

        if (! $eligibility['allowed']) {
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

        if (! $banding || $banding->status !== 'pending') {
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

        if (! $banding || $banding->status !== 'under_review') {
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
     * Accept a banding through the legacy service entry point.
     *
     * The LP2M flow no longer creates a new submission when banding is
     * accepted. It returns the existing akreditasi to Validasi Akhir Admin.
     */
    public function acceptBanding(int $bandingId, string $keputusan): ?Akreditasi
    {
        $banding = Banding::find($bandingId);

        if (! $banding || $banding->status !== 'under_review') {
            return null;
        }

        if (strlen($keputusan) < 10) {
            return null;
        }

        $reviewer = User::find($banding->reviewer_id);
        $akreditasi = Akreditasi::withTrashed()->find($banding->akreditasi_id);

        if (! $reviewer || ! $akreditasi || (int) $akreditasi->status !== AkreditasiStateMachine::STATUS_BANDING) {
            return null;
        }

        try {
            Assessment::withTrashed()
                ->where('akreditasi_id', $akreditasi->id)
                ->restore();

            $this->stateMachine->transition(
                $akreditasi,
                AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
                $reviewer
            );

            $banding->update([
                'status' => 'accepted',
                'keputusan' => $keputusan,
                'decided_at' => now(),
            ]);

            // Send notification to pesantren user
            $pesantrenUser = User::find($banding->user_id);
            if ($pesantrenUser) {
                $pesantrenUser->notify(new AkreditasiNotification(
                    'banding_accepted',
                    'Banding Diterima',
                    'Pengajuan banding Anda telah diterima. Proses akreditasi kembali ke tahap Validasi Akhir Admin.',
                    '#'
                ));
            }

            return $akreditasi->fresh();
        } catch (\Throwable $e) {
            Log::error('BandingService::acceptBanding failed', [
                'banding_id' => $bandingId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Reject a banding through the legacy service entry point.
     */
    public function rejectBanding(int $bandingId, string $keputusan): bool
    {
        $banding = Banding::find($bandingId);

        if (! $banding || $banding->status !== 'under_review') {
            return false;
        }

        if (strlen($keputusan) < 10) {
            return false;
        }

        $reviewer = User::find($banding->reviewer_id);
        $akreditasi = Akreditasi::withTrashed()->find($banding->akreditasi_id);

        if (! $reviewer || ! $akreditasi || (int) $akreditasi->status !== AkreditasiStateMachine::STATUS_BANDING) {
            return false;
        }

        try {
            $this->stateMachine->transition(
                $akreditasi,
                AkreditasiStateMachine::STATUS_DITOLAK,
                $reviewer
            );

            $banding->update([
                'status' => 'rejected',
                'keputusan' => $keputusan,
                'decided_at' => now(),
            ]);

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
                    'Pengajuan banding Anda ditolak. Alasan: '.$keputusan,
                    '#'
                ));
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('BandingService::rejectBanding failed', [
                'banding_id' => $bandingId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get paginated banding list with optional status filter and search.
     */
    public function getPaginatedBandings(?string $statusFilter = null, ?string $search = null, int $perPage = 10): LengthAwarePaginator
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
     */
    public function processDeadlines(): array
    {
        $reminderDays = (int) config('akreditasi.banding_reminder_days_before');
        $reminders = 0;
        $escalations = 0;

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
                    'Deadline review banding akan berakhir dalam '.$banding->daysUntilDeadline().' hari.',
                    '#'
                ));
                $reminders++;
            }
        }

        $overdueBandings = Banding::where('status', 'under_review')
            ->whereNotNull('review_deadline')
            ->where('review_deadline', '<', now())
            ->get();

        if ($overdueBandings->isNotEmpty()) {
            $admins = User::whereHas('role', fn ($q) => $q->where('id', 1))->get();

            foreach ($overdueBandings as $banding) {
                Notification::send($admins, new AkreditasiNotification(
                    'banding_escalation',
                    'Banding Melewati Deadline',
                    'Banding #'.$banding->id.' telah melewati deadline review ('.$banding->daysOverdue().' hari).',
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
