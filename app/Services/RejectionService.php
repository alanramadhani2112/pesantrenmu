<?php

namespace App\Services;

use App\Events\PerbaikanDeadlineApproaching;
use App\Events\PerbaikanSubmitted;
use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\Assessment;
use App\Models\MasterEdpmKomponen;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Repositories\Contracts\AkreditasiRepositoryInterface;
use App\Repositories\Contracts\PesantrenRepositoryInterface;
use App\Repositories\Contracts\RejectionRepositoryInterface;
use App\StateMachine\AkreditasiStateMachine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RejectionService
{
    public function __construct(
        protected RejectionRepositoryInterface $rejectionRepository,
        protected AkreditasiRepositoryInterface $akreditasiRepository,
        protected PesantrenRepositoryInterface $pesantrenRepository,
        protected AkreditasiStateMachine $stateMachine,
    ) {}

    // =========================================================================
    // Task 6.2: Berkas Rejection (Admin at status 5) — Req 3.6-3.8
    // =========================================================================

    /**
     * Reject berkas by Admin at status 5.
     *
     * Validates: at least one checkbox selected from sections, catatan required (max 2000 chars).
     * Creates AkreditasiRejection with type='admin_verifikasi'.
     * Soft deletes the akreditasi and transitions status to -1.
     *
     * @param  array  $rejectionData  ['sections' => [...], 'catatan' => '...']
     * @return array{success: bool, error: ?string}
     */
    public function rejectBerkas(int $akreditasiId, int $adminId, array $rejectionData): array
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi || (int) $akreditasi->status !== 5) {
            return ['success' => false, 'error' => 'invalid_status'];
        }

        $adminUser = User::find($adminId);
        if (! $adminUser || (int) $adminUser->role_id !== 1) {
            return ['success' => false, 'error' => 'unauthorized'];
        }

        $sections = $rejectionData['sections'] ?? [];
        if (empty($sections)) {
            return ['success' => false, 'error' => 'sections_required'];
        }

        $catatan = $rejectionData['catatan'] ?? '';
        if (empty($catatan)) {
            return ['success' => false, 'error' => 'catatan_required'];
        }
        if (strlen($catatan) > 2000) {
            return ['success' => false, 'error' => 'catatan_too_long'];
        }

        return DB::transaction(function () use ($akreditasiId, $adminId, $sections, $catatan, $akreditasi, $adminUser) {
            // Create rejection record
            $this->rejectionRepository->create([
                'akreditasi_id' => $akreditasiId,
                'user_id' => $adminId,
                'type' => 'admin_verifikasi',
                'items' => $sections,
                'explanation' => $catatan,
                'status' => 'final',
            ]);

            // Transition status to -1 via state machine
            $this->stateMachine->transition($akreditasi, AkreditasiStateMachine::STATUS_DITOLAK, $adminUser);

            // Soft delete the akreditasi
            $akreditasi->delete();

            // Notify pesantren
            $pesantrenUser = User::find($akreditasi->user_id);
            if ($pesantrenUser) {
                $sectionList = implode(', ', $sections);
                $pesantrenUser->notify(new AkreditasiNotification(
                    'berkas_rejected',
                    'Berkas Akreditasi Ditolak',
                    'Berkas akreditasi Anda ditolak. Bagian bermasalah: '.$sectionList.'. Catatan: '.$catatan,
                    '#'
                ));
            }

            return ['success' => true, 'error' => null];
        });
    }

    // =========================================================================
    // Task 6.3: Document Rejection at status 4 — Req 4.2-4.11
    // =========================================================================

    /**
     * Create a document rejection by Asesor_1 at status 4.
     *
     * Validates: at least one item selected, explanation 10-1000 chars.
     * Blocks if a perbaikan is currently pending.
     * Tracks rejection count (max 3); if 3rd rejection → auto-reject.
     * Creates AkreditasiRejection with type='asesor'.
     * Unlocks only the rejected sections and sets 14-day deadline.
     *
     * @return array{success: bool, error: ?string, rejection: ?AkreditasiRejection}
     */
    public function createDocumentRejection(int $akreditasiId, int $asesor1Id, array $items, string $explanation): array
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi || (int) $akreditasi->status !== 4) {
            return ['success' => false, 'error' => 'invalid_status', 'rejection' => null];
        }

        // Validate: user is Asesor 1 (tipe=1) for this akreditasi
        $isAsesor1 = Assessment::where('akreditasi_id', $akreditasiId)
            ->whereHas('asesor', fn ($q) => $q->where('user_id', $asesor1Id))
            ->where('tipe', 1)
            ->exists();

        if (! $isAsesor1) {
            return ['success' => false, 'error' => 'unauthorized', 'rejection' => null];
        }

        // Block if a perbaikan is currently pending (Req 4.11)
        $pendingRejection = $this->rejectionRepository->findActiveByAkreditasi($akreditasiId);
        if ($pendingRejection) {
            return ['success' => false, 'error' => 'perbaikan_pending', 'rejection' => null];
        }

        // Validate items
        if (empty($items)) {
            return ['success' => false, 'error' => 'items_required', 'rejection' => null];
        }

        // Validate explanation length (10-1000 chars)
        $explanationLen = strlen($explanation);
        if ($explanationLen < 10) {
            return ['success' => false, 'error' => 'explanation_too_short', 'rejection' => null];
        }
        if ($explanationLen > 1000) {
            return ['success' => false, 'error' => 'explanation_too_long', 'rejection' => null];
        }

        // Count existing asesor rejections for this akreditasi
        $currentCount = $this->rejectionRepository->countByAkreditasi($akreditasiId);
        $maxRejections = (int) config('akreditasi.rejection_limit', 3);

        return DB::transaction(function () use (
            $akreditasiId, $asesor1Id, $items, $explanation, $currentCount, $maxRejections, $akreditasi
        ) {
            $newCount = $currentCount + 1;
            $asesor1User = User::find($asesor1Id);

            // If this is the 3rd rejection → auto-reject (Req 4.7-4.8)
            if ($newCount >= $maxRejections) {
                $rejection = $this->rejectionRepository->create([
                    'akreditasi_id' => $akreditasiId,
                    'user_id' => $asesor1Id,
                    'type' => 'asesor',
                    'items' => $items,
                    'explanation' => $explanation,
                    'rejection_number' => $newCount,
                    'status' => 'limit_reached',
                ]);

                // Transition to -1 and soft delete
                if ($asesor1User) {
                    $this->stateMachine->transition($akreditasi, AkreditasiStateMachine::STATUS_DITOLAK, $asesor1User);
                }
                $akreditasi->delete();
                $this->pesantrenRepository->updateByUserId($akreditasi->user_id, ['is_locked' => false]);

                // Notify pesantren and admin
                $this->notifyDocumentRejection($akreditasi, $items, $explanation, isAutoRejection: true);

                return ['success' => true, 'error' => null, 'rejection' => $rejection];
            }

            // Normal rejection: create record with 14-day deadline
            $deadline = now()->addDays((int) config('akreditasi.perbaikan_deadline_days', 14));
            $rejection = $this->rejectionRepository->create([
                'akreditasi_id' => $akreditasiId,
                'user_id' => $asesor1Id,
                'type' => 'asesor',
                'items' => $items,
                'explanation' => $explanation,
                'rejection_number' => $newCount,
                'perbaikan_deadline' => $deadline,
                'status' => 'pending',
            ]);

            // Notify pesantren and admin
            $this->notifyDocumentRejection($akreditasi, $items, $explanation, isAutoRejection: false);

            return ['success' => true, 'error' => null, 'rejection' => $rejection];
        });
    }

    // =========================================================================
    // Task 6.4: Perbaikan — unlock rejected sections, re-lock on submit — Req 4.4-4.6
    // =========================================================================

    /**
     * Submit perbaikan (corrections) by Pesantren.
     *
     * Re-locks the corrected sections by marking the rejection as 'submitted'.
     * Allows Asesor_1 to review again.
     *
     * @return array{success: bool, error: ?string}
     */
    public function submitPerbaikan(int $akreditasiId, int $pesantrenId): array
    {
        // Validate: active rejection exists for this akreditasi
        $activeRejection = $this->rejectionRepository->findActiveByAkreditasi($akreditasiId);
        if (! $activeRejection) {
            return ['success' => false, 'error' => 'no_active_rejection'];
        }

        // Validate: user is the pesantren owner of the akreditasi
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi || (int) $akreditasi->user_id !== $pesantrenId) {
            return ['success' => false, 'error' => 'unauthorized'];
        }

        return DB::transaction(function () use ($activeRejection, $akreditasiId) {
            // Re-lock sections and mark the correction ready for assessor review.
            $this->rejectionRepository->update($activeRejection->id, [
                'status' => 'submitted',
                'perbaikan_submitted_at' => now(),
            ]);

            // Notify Asesor_1 and Admin
            $akreditasi = $this->akreditasiRepository->find($akreditasiId);
            if ($akreditasi) {
                $assessment = Assessment::where('akreditasi_id', $akreditasiId)
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

                $admins = User::where('role_id', 1)->get();
                Notification::send($admins, new AkreditasiNotification(
                    'perbaikan_submitted_admin',
                    'Perbaikan Disubmit',
                    'Pesantren telah mengirimkan perbaikan dokumen akreditasi.',
                    '#'
                ));

                // Task 12.3: Dispatch PerbaikanSubmitted event for extensibility
                // Reload the rejection to get the submitted state
                $submittedRejection = AkreditasiRejection::find($activeRejection->id) ?? $activeRejection;
                event(new PerbaikanSubmitted($akreditasi, $submittedRejection));
            }

            return ['success' => true, 'error' => null];
        });
    }

    // =========================================================================
    // Task 6.5: Auto-rejection on deadline expiry — Req 4.8-4.9
    // =========================================================================

    /**
     * Auto-reject an akreditasi when the perbaikan deadline has expired.
     *
     * Transitions to -1 with soft delete.
     *
     * @return array{success: bool, error: ?string}
     */
    public function autoRejectOnDeadlineExpiry(int $akreditasiId): array
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi) {
            return ['success' => false, 'error' => 'akreditasi_not_found'];
        }

        // Find the expired pending rejection
        $expiredRejection = AkreditasiRejection::where('akreditasi_id', $akreditasiId)
            ->where('type', 'asesor')
            ->where('status', 'pending')
            ->whereNotNull('perbaikan_deadline')
            ->where('perbaikan_deadline', '<', now())
            ->latest()
            ->first();

        if (! $expiredRejection) {
            return ['success' => false, 'error' => 'no_expired_rejection'];
        }

        return DB::transaction(function () use ($akreditasiId, $expiredRejection, $akreditasi) {
            // Mark rejection as expired
            $this->rejectionRepository->update($expiredRejection->id, ['status' => 'expired']);

            $assessment = Assessment::where('akreditasi_id', $akreditasiId)
                ->where('tipe', 1)
                ->with('asesor')
                ->first();

            // Use a system actor for the transition
            $systemUser = User::where('role_id', 1)->first();
            if ($systemUser && $this->stateMachine->canTransition((int) $akreditasi->status, AkreditasiStateMachine::STATUS_DITOLAK)) {
                $this->stateMachine->transition($akreditasi, AkreditasiStateMachine::STATUS_DITOLAK, $systemUser);
            } elseif ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_DITOLAK) {
                $akreditasi->update(['status' => AkreditasiStateMachine::STATUS_DITOLAK]);
            }

            // Soft delete
            $akreditasi->delete();
            $this->pesantrenRepository->updateByUserId($akreditasi->user_id, ['is_locked' => false]);

            // Notify pesantren
            $pesantrenUser = User::find($akreditasi->user_id);
            if ($pesantrenUser) {
                $pesantrenUser->notify(new AkreditasiNotification(
                    'rejection_deadline_expired',
                    'Akreditasi Ditolak - Batas Waktu Habis',
                    'Akreditasi Anda telah ditolak otomatis karena batas waktu perbaikan telah habis.',
                    '#'
                ));
            }

            // Notify Asesor_1
            if ($assessment && $assessment->asesor) {
                $asesor1User = User::find($assessment->asesor->user_id);
                if ($asesor1User) {
                    $asesor1User->notify(new AkreditasiNotification(
                        'rejection_deadline_expired_asesor',
                        'Perbaikan Timeout',
                        'Pesantren tidak mengirimkan perbaikan dalam batas waktu. Akreditasi ditolak otomatis.',
                        '#'
                    ));
                }
            }

            // Notify admins
            $admins = User::where('role_id', 1)->get();
            Notification::send($admins, new AkreditasiNotification(
                'rejection_deadline_expired_admin',
                'Penolakan Otomatis - Timeout',
                'Akreditasi ditolak otomatis karena batas waktu perbaikan habis.',
                '#'
            ));

            return ['success' => true, 'error' => null];
        });
    }

    // =========================================================================
    // Section unlock helpers — used by Pesantren to check edit permissions
    // =========================================================================

    /**
     * Check if a specific section/item is unlocked for editing.
     *
     * @param  string  $section  e.g. 'profil', 'ipm.kurikulum', 'edpm.butir.3'
     */
    public function isSectionUnlocked(int $akreditasiId, string $section): bool
    {
        $activeRejection = $this->rejectionRepository->findActiveByAkreditasi($akreditasiId);

        if (! $activeRejection) {
            return false;
        }

        $items = $activeRejection->items ?? [];

        return in_array($section, $items, true);
    }

    /**
     * Get all currently unlocked sections for an akreditasi.
     */
    public function getUnlockedSections(int $akreditasiId): array
    {
        $activeRejection = $this->rejectionRepository->findActiveByAkreditasi($akreditasiId);

        if (! $activeRejection) {
            return [];
        }

        return $activeRejection->items ?? [];
    }

    // =========================================================================
    // Legacy / existing methods preserved for backward compatibility
    // =========================================================================

    /**
     * Get the selectable rejection items structure.
     */
    public function getSelectableItems(int $akreditasiId): array
    {
        $items = [
            ['id' => 'profil', 'label' => 'Profil', 'type' => 'section'],
            [
                'id' => 'ipm',
                'label' => 'IPM',
                'type' => 'section',
                'children' => [
                    ['id' => 'ipm.nsp', 'label' => 'NSP'],
                    ['id' => 'ipm.kurikulum', 'label' => 'Kurikulum'],
                    ['id' => 'ipm.buku_ajar', 'label' => 'Buku Ajar'],
                    ['id' => 'ipm.lulus_santri', 'label' => 'Lulus Santri'],
                ],
            ],
            ['id' => 'sdm', 'label' => 'SDM', 'type' => 'section'],
        ];

        $komponens = MasterEdpmKomponen::with('butirs')->orderBy('id')->get();
        $edpmChildren = [];
        foreach ($komponens as $komponen) {
            $butirItems = [];
            foreach ($komponen->butirs as $butir) {
                $butirItems[] = [
                    'id' => 'edpm.butir.'.$butir->id,
                    'label' => $butir->nomor_butir.' - '.$butir->butir_pernyataan,
                ];
            }
            $edpmChildren[] = [
                'id' => 'edpm.komponen.'.$komponen->id,
                'label' => $komponen->nama,
                'children' => $butirItems,
            ];
        }

        $items[] = ['id' => 'edpm', 'label' => 'EDPM', 'type' => 'section', 'children' => $edpmChildren];

        return $items;
    }

    /**
     * Accept corrections after perbaikan (Asesor_1 action).
     *
     * @return array{success: bool, error: ?string}
     */
    public function acceptPerbaikan(int $akreditasiId, int $userId): array
    {
        $isAsesor1 = Assessment::where('akreditasi_id', $akreditasiId)
            ->whereHas('asesor', fn ($q) => $q->where('user_id', $userId))
            ->where('tipe', 1)
            ->exists();

        if (! $isAsesor1) {
            return ['success' => false, 'error' => 'unauthorized'];
        }

        $rejection = AkreditasiRejection::where('akreditasi_id', $akreditasiId)
            ->where('type', 'asesor')
            ->whereIn('status', ['submitted', 'resolved'])
            ->latest()
            ->first();

        if (! $rejection) {
            return ['success' => false, 'error' => 'no_submitted_rejection'];
        }

        return DB::transaction(function () use ($rejection) {
            $this->rejectionRepository->update($rejection->id, ['status' => 'accepted']);

            return ['success' => true, 'error' => null];
        });
    }

    /**
     * Get the current rejection status for an akreditasi.
     *
     * @return array{count: int, limit: int, active: ?AkreditasiRejection, history: Collection}
     */
    public function getRejectionStatus(int $akreditasiId): array
    {
        $count = $this->rejectionRepository->countByAkreditasi($akreditasiId);
        $limit = 3;
        $history = $this->rejectionRepository->getByAkreditasi($akreditasiId);

        $active = AkreditasiRejection::where('akreditasi_id', $akreditasiId)
            ->where('type', 'asesor')
            ->whereIn('status', ['pending', 'submitted', 'resolved'])
            ->latest()
            ->first();

        return [
            'count' => $count,
            'limit' => $limit,
            'active' => $active,
            'history' => $history,
        ];
    }

    /**
     * Process perbaikan deadline checks (called by scheduled command).
     *
     * @return array{reminders_sent: int, auto_rejected: int}
     */
    public function processDeadlines(): array
    {
        $reminderDays = (int) config('akreditasi.perbaikan_reminder_days_before', 3);
        $remindersSent = 0;
        $autoRejected = 0;

        $expired = AkreditasiRejection::where('type', 'asesor')
            ->where('status', 'pending')
            ->whereNotNull('perbaikan_deadline')
            ->where('perbaikan_deadline', '<', now())
            ->get();

        foreach ($expired as $rejection) {
            $result = $this->autoRejectOnDeadlineExpiry($rejection->akreditasi_id);
            if ($result['success']) {
                $autoRejected++;
            }
        }

        $approaching = AkreditasiRejection::where('type', 'asesor')
            ->where('status', 'pending')
            ->whereNotNull('perbaikan_deadline')
            ->where('perbaikan_deadline', '>', now())
            ->where('perbaikan_deadline', '<=', now()->addDays($reminderDays))
            ->get();

        foreach ($approaching as $rejection) {
            $akreditasi = $this->akreditasiRepository->find($rejection->akreditasi_id);
            if ($akreditasi) {
                $pesantrenUser = User::find($akreditasi->user_id);
                if ($pesantrenUser) {
                    $daysLeft = $rejection->daysUntilDeadline();
                    $pesantrenUser->notify(new AkreditasiNotification(
                        'perbaikan_deadline_reminder',
                        'Pengingat Deadline Perbaikan',
                        'Batas waktu perbaikan dokumen akan berakhir dalam '.$daysLeft.' hari. Segera kirimkan perbaikan Anda.',
                        '#'
                    ));
                }

                // Task 12.4: Dispatch PerbaikanDeadlineApproaching event for extensibility
                $daysLeft = $rejection->daysUntilDeadline();
                event(new PerbaikanDeadlineApproaching($akreditasi, $rejection, $daysLeft));
            }
            $remindersSent++;
        }

        return ['reminders_sent' => $remindersSent, 'auto_rejected' => $autoRejected];
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function notifyDocumentRejection(Akreditasi $akreditasi, array $items, string $explanation, bool $isAutoRejection): void
    {
        $itemSummary = implode(', ', $items);

        $pesantrenUser = User::find($akreditasi->user_id);
        if ($pesantrenUser) {
            if ($isAutoRejection) {
                $pesantrenUser->notify(new AkreditasiNotification(
                    'rejection_limit_reached',
                    'Akreditasi Ditolak Otomatis',
                    'Akreditasi Anda telah ditolak secara otomatis karena batas maksimal perbaikan telah tercapai.',
                    '#'
                ));
            } else {
                $pesantrenUser->notify(new AkreditasiNotification(
                    'document_rejection_created',
                    'Dokumen Ditolak',
                    'Asesor telah menolak dokumen Anda. Item yang perlu diperbaiki: '.$itemSummary.'. Penjelasan: '.$explanation,
                    '#'
                ));
            }
        }

        $admins = User::where('role_id', 1)->get();
        $adminMessage = $isAutoRejection
            ? 'Akreditasi ditolak otomatis karena batas penolakan tercapai.'
            : 'Asesor telah menolak dokumen akreditasi. Item: '.$itemSummary;

        Notification::send($admins, new AkreditasiNotification(
            $isAutoRejection ? 'rejection_limit_reached_admin' : 'document_rejection_created_admin',
            $isAutoRejection ? 'Penolakan Otomatis - Batas Tercapai' : 'Asesor Menolak Dokumen',
            $adminMessage,
            '#'
        ));
    }
}
