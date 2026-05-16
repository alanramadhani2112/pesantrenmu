<?php

namespace App\Services;

use App\Models\AkreditasiRejection;
use App\Models\Assessment;
use App\Models\MasterEdpmKomponen;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Repositories\Contracts\AkreditasiRepositoryInterface;
use App\Repositories\Contracts\PesantrenRepositoryInterface;
use App\Repositories\Contracts\RejectionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RejectionService
{
    public function __construct(
        protected RejectionRepositoryInterface $rejectionRepository,
        protected AkreditasiRepositoryInterface $akreditasiRepository,
        protected PesantrenRepositoryInterface $pesantrenRepository
    ) {}

    /**
     * Get the selectable rejection items structure.
     * Returns the full tree of sections and sub-items available for rejection.
     *
     * @param int $akreditasiId - To load EDPM butir items dynamically
     * @return array
     */
    public function getSelectableItems(int $akreditasiId): array
    {
        $items = [
            [
                'id' => 'profil',
                'label' => 'Profil',
                'type' => 'section',
            ],
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
            [
                'id' => 'sdm',
                'label' => 'SDM',
                'type' => 'section',
            ],
        ];

        // Load EDPM komponen with butirs dynamically
        $komponens = MasterEdpmKomponen::with('butirs')->orderBy('id')->get();

        $edpmChildren = [];
        foreach ($komponens as $komponen) {
            $butirItems = [];
            foreach ($komponen->butirs as $butir) {
                $butirItems[] = [
                    'id' => 'edpm.butir.' . $butir->id,
                    'label' => $butir->nomor_butir . ' - ' . $butir->butir_pernyataan,
                ];
            }

            $edpmChildren[] = [
                'id' => 'edpm.komponen.' . $komponen->id,
                'label' => $komponen->nama,
                'children' => $butirItems,
            ];
        }

        $items[] = [
            'id' => 'edpm',
            'label' => 'EDPM',
            'type' => 'section',
            'children' => $edpmChildren,
        ];

        return $items;
    }

    /**
     * Create a structured rejection by Asesor 1.
     *
     * @param int $akreditasiId
     * @param int $userId - The Asesor 1 user ID
     * @param array $items - Array of rejected item identifiers
     * @param string $explanation - Free-text explanation (min 10 chars)
     * @return array{success: bool, error: ?string, rejection: ?AkreditasiRejection}
     */
    public function createRejection(int $akreditasiId, int $userId, array $items, string $explanation): array
    {
        // Validate: user is Asesor 1 (tipe=1) for the akreditasi
        $isAsesor1 = Assessment::where('akreditasi_id', $akreditasiId)
            ->whereHas('asesor', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('tipe', 1)
            ->exists();

        if (!$isAsesor1) {
            return ['success' => false, 'error' => 'unauthorized', 'rejection' => null];
        }

        // Validate: akreditasi at status 5
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (!$akreditasi || (int) $akreditasi->status !== 5) {
            return ['success' => false, 'error' => 'invalid_status', 'rejection' => null];
        }

        // Validate: items array not empty
        if (empty($items)) {
            return ['success' => false, 'error' => 'items_required', 'rejection' => null];
        }

        // Validate: explanation min 10 chars
        if (strlen($explanation) < 10) {
            return ['success' => false, 'error' => 'explanation_too_short', 'rejection' => null];
        }

        // Validate: rejection count < configured limit
        $currentCount = $this->rejectionRepository->countByAkreditasi($akreditasiId);
        $limit = (int) config('akreditasi.rejection_limit', 3);

        // If count + 1 >= limit: auto-reject
        if ($currentCount + 1 >= $limit) {
            return DB::transaction(function () use ($akreditasiId, $userId, $items, $explanation, $currentCount) {
                // Create the rejection record with status 'limit_reached'
                $rejection = $this->rejectionRepository->create([
                    'akreditasi_id' => $akreditasiId,
                    'user_id' => $userId,
                    'type' => 'asesor',
                    'items' => $items,
                    'explanation' => $explanation,
                    'rejection_number' => $currentCount + 1,
                    'status' => 'limit_reached',
                ]);

                // Change akreditasi status to 2 (Ditolak)
                $this->akreditasiRepository->update($akreditasiId, ['status' => 2]);

                // Unlock pesantren data
                $akreditasi = $this->akreditasiRepository->find($akreditasiId);
                if ($akreditasi) {
                    $this->pesantrenRepository->updateByUserId($akreditasi->user_id, ['is_locked' => false]);
                }

                // Send notifications: auto-rejection (limit reached)
                if ($akreditasi) {
                    $pesantrenUser = User::find($akreditasi->user_id);
                    if ($pesantrenUser) {
                        $pesantrenUser->notify(new AkreditasiNotification(
                            'rejection_limit_reached',
                            'Akreditasi Ditolak Otomatis',
                            'Akreditasi Anda telah ditolak secara otomatis karena batas maksimal perbaikan telah tercapai.',
                            '#'
                        ));
                    }

                    $admins = User::where('role_id', 1)->get();
                    Notification::send($admins, new AkreditasiNotification(
                        'rejection_limit_reached_admin',
                        'Penolakan Otomatis - Batas Tercapai',
                        'Akreditasi telah ditolak otomatis karena batas penolakan tercapai.',
                        '#'
                    ));
                }

                return ['success' => true, 'error' => null, 'rejection' => $rejection];
            });
        }

        // Normal rejection: create record with deadline
        return DB::transaction(function () use ($akreditasiId, $userId, $items, $explanation, $currentCount) {
            $deadlineDays = (int) config('akreditasi.perbaikan_deadline_days', 14);
            $deadline = now()->addDays($deadlineDays);

            $rejection = $this->rejectionRepository->create([
                'akreditasi_id' => $akreditasiId,
                'user_id' => $userId,
                'type' => 'asesor',
                'items' => $items,
                'explanation' => $explanation,
                'rejection_number' => $currentCount + 1,
                'perbaikan_deadline' => $deadline,
                'status' => 'pending',
            ]);

            // Send notifications: rejection with item summary to pesantren and admin
            $akreditasi = $this->akreditasiRepository->find($akreditasiId);
            if ($akreditasi) {
                $itemSummary = implode(', ', $items);

                $pesantrenUser = User::find($akreditasi->user_id);
                if ($pesantrenUser) {
                    $pesantrenUser->notify(new AkreditasiNotification(
                        'rejection_created',
                        'Dokumen Ditolak',
                        'Asesor telah menolak dokumen Anda. Item yang perlu diperbaiki: ' . $itemSummary,
                        '#'
                    ));
                }

                $admins = User::where('role_id', 1)->get();
                Notification::send($admins, new AkreditasiNotification(
                    'rejection_created_admin',
                    'Asesor Menolak Dokumen',
                    'Asesor telah menolak dokumen akreditasi. Item: ' . $itemSummary,
                    '#'
                ));
            }

            return ['success' => true, 'error' => null, 'rejection' => $rejection];
        });
    }

    /**
     * Check if a specific section/item is unlocked for editing.
     *
     * @param int $akreditasiId
     * @param string $section - Section identifier (e.g., 'profil', 'ipm.kurikulum', 'edpm.butir.3')
     * @return bool
     */
    public function isSectionUnlocked(int $akreditasiId, string $section): bool
    {
        $activeRejection = $this->rejectionRepository->findActiveByAkreditasi($akreditasiId);

        if (!$activeRejection) {
            return false;
        }

        $items = $activeRejection->items ?? [];

        return in_array($section, $items, true);
    }

    /**
     * Get all currently unlocked sections for an akreditasi.
     *
     * @param int $akreditasiId
     * @return array - List of unlocked section identifiers
     */
    public function getUnlockedSections(int $akreditasiId): array
    {
        $activeRejection = $this->rejectionRepository->findActiveByAkreditasi($akreditasiId);

        if (!$activeRejection) {
            return [];
        }

        return $activeRejection->items ?? [];
    }

    /**
     * Submit perbaikan (corrections) by pesantren.
     * Validates: active rejection exists, user is pesantren owner.
     * Re-locks all sections, records submission timestamp, sends notifications.
     *
     * @param int $akreditasiId
     * @param int $userId - The pesantren user ID
     * @return array{success: bool, error: ?string}
     */
    public function submitPerbaikan(int $akreditasiId, int $userId): array
    {
        // Validate: active rejection exists for this akreditasi
        $activeRejection = $this->rejectionRepository->findActiveByAkreditasi($akreditasiId);
        if (!$activeRejection) {
            return ['success' => false, 'error' => 'no_active_rejection'];
        }

        // Validate: user is the pesantren owner of the akreditasi
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (!$akreditasi || (int) $akreditasi->user_id !== $userId) {
            return ['success' => false, 'error' => 'unauthorized'];
        }

        return DB::transaction(function () use ($activeRejection, $akreditasiId) {
            // Re-lock all sections: update rejection status to 'submitted', set perbaikan_submitted_at
            $this->rejectionRepository->update($activeRejection->id, [
                'status' => 'submitted',
                'perbaikan_submitted_at' => now(),
            ]);

            // Send notifications: perbaikan submitted to Asesor 1 and Admin
            $akreditasi = $this->akreditasiRepository->find($akreditasiId);
            if ($akreditasi) {
                // Find Asesor 1 user for this akreditasi
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
            }

            return ['success' => true, 'error' => null];
        });
    }

    /**
     * Accept corrections after perbaikan (Asesor 1 action).
     * Clears active rejection state, allows normal visitasi scheduling.
     *
     * @param int $akreditasiId
     * @param int $userId - The Asesor 1 user ID
     * @return array{success: bool, error: ?string}
     */
    public function acceptPerbaikan(int $akreditasiId, int $userId): array
    {
        // Validate: user is Asesor 1 (tipe=1) for the akreditasi
        $isAsesor1 = Assessment::where('akreditasi_id', $akreditasiId)
            ->whereHas('asesor', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('tipe', 1)
            ->exists();

        if (!$isAsesor1) {
            return ['success' => false, 'error' => 'unauthorized'];
        }

        // Validate: active rejection with status='submitted' exists
        $rejection = AkreditasiRejection::where('akreditasi_id', $akreditasiId)
            ->where('type', 'asesor')
            ->where('status', 'submitted')
            ->latest()
            ->first();

        if (!$rejection) {
            return ['success' => false, 'error' => 'no_submitted_rejection'];
        }

        return DB::transaction(function () use ($rejection) {
            // Update rejection status to 'accepted'
            $this->rejectionRepository->update($rejection->id, [
                'status' => 'accepted',
            ]);

            // Allow normal visitasi scheduling flow to proceed
            // (No additional status change needed - akreditasi stays at 5)

            return ['success' => true, 'error' => null];
        });
    }

    /**
     * Get the current rejection status for an akreditasi.
     *
     * @param int $akreditasiId
     * @return array{count: int, limit: int, active: ?AkreditasiRejection, history: Collection}
     */
    public function getRejectionStatus(int $akreditasiId): array
    {
        $count = $this->rejectionRepository->countByAkreditasi($akreditasiId);
        $limit = (int) config('akreditasi.rejection_limit', 3);
        $history = $this->rejectionRepository->getByAkreditasi($akreditasiId);

        // Active rejection: pending (waiting for perbaikan) OR submitted (waiting for asesor review)
        $active = AkreditasiRejection::where('akreditasi_id', $akreditasiId)
            ->where('type', 'asesor')
            ->whereIn('status', ['pending', 'submitted'])
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
     * Create a structured final rejection by Admin at status 3.
     * Validates: akreditasi at status 3, user is admin (role_id=1).
     * Changes status to 2, stores categories + explanations, unlocks pesantren data, sends notifications.
     *
     * @param int $akreditasiId
     * @param int $adminUserId
     * @param array $categories - Array of [{category: string, explanation: string}]
     * @return array{success: bool, error: ?string}
     */
    public function createFinalRejection(int $akreditasiId, int $adminUserId, array $categories): array
    {
        // Validate: akreditasi at status 3
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (!$akreditasi || (int) $akreditasi->status !== 3) {
            return ['success' => false, 'error' => 'invalid_status'];
        }

        // Validate: user is admin (role_id=1)
        $adminUser = \App\Models\User::find($adminUserId);
        if (!$adminUser || (int) $adminUser->role_id !== 1) {
            return ['success' => false, 'error' => 'unauthorized'];
        }

        // Validate: categories array not empty
        if (empty($categories)) {
            return ['success' => false, 'error' => 'categories_required'];
        }

        // Validate: each category has valid key and explanation min 10 chars
        $validKeys = array_keys(config('akreditasi.final_rejection_categories', []));
        foreach ($categories as $entry) {
            if (!isset($entry['category']) || !in_array($entry['category'], $validKeys, true)) {
                return ['success' => false, 'error' => 'invalid_category'];
            }
            if (!isset($entry['explanation']) || strlen($entry['explanation']) < 10) {
                return ['success' => false, 'error' => 'explanation_too_short'];
            }
        }

        return DB::transaction(function () use ($akreditasiId, $adminUserId, $categories, $akreditasi) {
            // Create rejection record with type='admin_final'
            $this->rejectionRepository->create([
                'akreditasi_id' => $akreditasiId,
                'user_id' => $adminUserId,
                'type' => 'admin_final',
                'categories' => $categories,
                'status' => 'final',
            ]);

            // Change akreditasi status to 2 (Ditolak)
            $this->akreditasiRepository->update($akreditasiId, ['status' => 2]);

            // Unlock pesantren data
            $this->pesantrenRepository->updateByUserId($akreditasi->user_id, ['is_locked' => false]);

            // Send notification to pesantren with structured rejection detail (categories + explanations)
            $pesantrenUser = User::find($akreditasi->user_id);
            if ($pesantrenUser) {
                $categoryLabels = config('akreditasi.final_rejection_categories', []);
                $details = collect($categories)->map(function ($entry) use ($categoryLabels) {
                    $label = $categoryLabels[$entry['category']] ?? $entry['category'];
                    return $label . ': ' . $entry['explanation'];
                })->implode('; ');

                $pesantrenUser->notify(new AkreditasiNotification(
                    'final_rejection',
                    'Akreditasi Ditolak',
                    'Akreditasi Anda ditolak pada tahap validasi. Alasan: ' . $details,
                    '#'
                ));
            }

            return ['success' => true, 'error' => null];
        });
    }

    /**
     * Process perbaikan deadline checks.
     * Called by scheduled command. Sends reminders for approaching deadlines
     * and auto-rejects expired rejections.
     *
     * @return array{reminders_sent: int, auto_rejected: int}
     */
    public function processDeadlines(): array
    {
        $reminderDays = (int) config('akreditasi.perbaikan_reminder_days_before', 3);
        $remindersSent = 0;
        $autoRejected = 0;

        // Find expired rejections (past deadline, still pending)
        $expired = AkreditasiRejection::where('type', 'asesor')
            ->where('status', 'pending')
            ->whereNotNull('perbaikan_deadline')
            ->where('perbaikan_deadline', '<', now())
            ->get();

        foreach ($expired as $rejection) {
            DB::transaction(function () use ($rejection) {
                // Update rejection status to 'expired'
                $this->rejectionRepository->update($rejection->id, ['status' => 'expired']);

                // Change akreditasi status to 2 (Ditolak)
                $this->akreditasiRepository->update($rejection->akreditasi_id, ['status' => 2]);

                // Unlock pesantren data
                $akreditasi = $this->akreditasiRepository->find($rejection->akreditasi_id);
                if ($akreditasi) {
                    $this->pesantrenRepository->updateByUserId($akreditasi->user_id, ['is_locked' => false]);

                    // Send notifications: deadline expired auto-rejection
                    $pesantrenUser = User::find($akreditasi->user_id);
                    if ($pesantrenUser) {
                        $pesantrenUser->notify(new AkreditasiNotification(
                            'rejection_deadline_expired',
                            'Akreditasi Ditolak - Batas Waktu Habis',
                            'Akreditasi Anda telah ditolak otomatis karena batas waktu perbaikan telah habis.',
                            '#'
                        ));
                    }

                    // Notify Asesor 1
                    $assessment = Assessment::where('akreditasi_id', $rejection->akreditasi_id)
                        ->where('tipe', 1)
                        ->with('asesor')
                        ->first();

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
                }
            });
            $autoRejected++;
        }

        // Find approaching deadline rejections (within reminder days, still pending)
        $approaching = AkreditasiRejection::where('type', 'asesor')
            ->where('status', 'pending')
            ->whereNotNull('perbaikan_deadline')
            ->where('perbaikan_deadline', '>', now())
            ->where('perbaikan_deadline', '<=', now()->addDays($reminderDays))
            ->get();

        foreach ($approaching as $rejection) {
            // Send reminder notification to pesantren
            $akreditasi = $this->akreditasiRepository->find($rejection->akreditasi_id);
            if ($akreditasi) {
                $pesantrenUser = User::find($akreditasi->user_id);
                if ($pesantrenUser) {
                    $daysLeft = $rejection->daysUntilDeadline();
                    $pesantrenUser->notify(new AkreditasiNotification(
                        'perbaikan_deadline_reminder',
                        'Pengingat Deadline Perbaikan',
                        'Batas waktu perbaikan dokumen akan berakhir dalam ' . $daysLeft . ' hari. Segera kirimkan perbaikan Anda.',
                        '#'
                    ));
                }
            }
            $remindersSent++;
        }

        return ['reminders_sent' => $remindersSent, 'auto_rejected' => $autoRejected];
    }
}
