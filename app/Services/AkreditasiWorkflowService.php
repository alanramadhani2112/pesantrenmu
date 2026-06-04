<?php

namespace App\Services;

use App\Events\AsesorAssigned;
use App\Events\AsesorPackageSubmitted;
use App\Events\PerbaikanRequested;
use App\Events\ScoringCompleted;
use App\Events\SKIssued;
use App\Events\VisitasiScheduled;
use App\Exceptions\InvalidTransitionException;
use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\AkreditasiRejection;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Repositories\Contracts\AkreditasiRepositoryInterface;
use App\Services\Concerns\ChecksOptimisticLock;
use App\StateMachine\AkreditasiStateMachine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * AkreditasiWorkflowService
 *
 * Orchestrates business logic for each workflow step of the akreditasi process.
 * Delegates to sub-services for specific concerns (rejection, banding, documents, scoring, etc.)
 * and uses AkreditasiStateMachine for all status transitions.
 *
 * Task 9: WorkflowService Pengajuan & Verifikasi — Req 2.1-2.6, 3.1-3.8
 */
class AkreditasiWorkflowService
{
    use ChecksOptimisticLock;

    public function __construct(
        protected AkreditasiStateMachine $stateMachine,
        protected AkreditasiRepositoryInterface $akreditasiRepository,
        protected RejectionService $rejectionService,
        protected BandingService $bandingService,
        protected DocumentService $documentService,
        protected ScoreCalculationService $scoreCalculationService,
        protected PesantrenService $pesantrenService,
        protected AuditTrailService $auditTrailService,
    ) {}

    // =========================================================================
    // Task 9.2: submitPengajuan — Req 2.1-2.6
    // =========================================================================

    /**
     * Submit an akreditasi application for a pesantren user.
     *
     * Validates:
     *  - All mandatory fields in Profil, IPM, SDM, EDPM sections are non-empty
     *  - No active akreditasi exists (status 6 through 1)
     *
     * On success:
     *  - Creates Akreditasi at status 6 (Pengajuan)
     *  - Locks pesantren data (is_locked = true)
     *  - Dispatches notification to all Admin users
     *
     * @param  int  $pesantrenUserId  The user_id of the pesantren submitting
     * @return Akreditasi The newly created akreditasi record
     *
     * @throws \DomainException When validation fails or active akreditasi exists
     *
     * Validates Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.6
     */
    public function submitPengajuan(int $pesantrenUserId): Akreditasi
    {
        // Req 2.1, 2.2: Validate all mandatory fields in all required sections
        $missingFields = $this->pesantrenService->checkDataCompleteness($pesantrenUserId);
        if (! empty($missingFields)) {
            throw new \DomainException(
                'Data tidak lengkap. Bagian yang belum terisi: '.implode('; ', $missingFields)
            );
        }

        // Req 2.3: Reject if active akreditasi exists (status 6 through 1)
        $hasActive = Akreditasi::where('user_id', $pesantrenUserId)
            ->whereIn('status', [6, 5, 4, 3, 2, 1])
            ->exists();

        if ($hasActive) {
            throw new \DomainException(
                'Pengajuan akreditasi aktif sudah ada. Selesaikan atau batalkan pengajuan yang ada sebelum membuat yang baru.'
            );
        }

        $akreditasi = DB::transaction(function () use ($pesantrenUserId) {
            // Req 2.4: Set status to 6 (Pengajuan)
            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUserId,
                'status' => AkreditasiStateMachine::STATUS_PENGAJUAN,
            ]);

            // Req 2.5: Lock all data in Profil, IPM, SDM, EDPM sections
            $pesantren = Pesantren::where('user_id', $pesantrenUserId)->first();
            if ($pesantren) {
                $pesantren->update(['is_locked' => true]);
            }

            return $akreditasi;
        });

        // Req 2.6: Send notification to all Admin users (dispatched after transaction)
        $this->notifyAdminsNewPengajuan($akreditasi);

        return $akreditasi;
    }

    // =========================================================================
    // Task 9.3: openForReview — Req 3.1
    // =========================================================================

    /**
     * Admin opens a pengajuan for review, transitioning status 6 → 5.
     *
     *
     * @throws \DomainException When akreditasi not found or not at status 6
     * @throws InvalidTransitionException When transition is not permitted
     *
     * Validates Requirement 3.1
     */
    public function openForReview(int $akreditasiId, int $adminId): void
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi) {
            throw new \DomainException("Akreditasi #{$akreditasiId} tidak ditemukan.");
        }

        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_PENGAJUAN) {
            throw new \DomainException(
                "Akreditasi tidak berada pada status Pengajuan (status saat ini: {$akreditasi->status})."
            );
        }

        $adminUser = User::findOrFail($adminId);

        // Transition 6 → 5 via state machine (includes optimistic lock + audit trail)
        $this->stateMachine->transition($akreditasi, AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS, $adminUser);
    }

    // =========================================================================
    // Task 9.4: approveBerkas — Req 3.2-3.5
    // =========================================================================

    /**
     * Admin approves berkas verification with asesor assignment, transitioning 5 → 4.
     *
     * Validates:
     *  - asesor1Id ≠ asesor2Id (Req 3.2)
     *  - Both asesor1Id and asesor2Id must be provided (Req 3.3)
     *
     * On success:
     *  - Creates Assessment records (tipe=1 for Asesor_1, tipe=2 for Asesor_2)
     *  - Transitions status 5 → 4
     *  - Notifies Pesantren, Asesor_1, Asesor_2 (Req 3.5)
     *
     * @param  int  $asesor1Id  Asesor user_id for Asesor_1 (tipe=1)
     * @param  int  $asesor2Id  Asesor user_id for Asesor_2 (tipe=2)
     * @param  string  $clientUpdatedAt  Optional ISO timestamp for optimistic locking
     *
     * @throws \DomainException When validation fails
     * @throws InvalidTransitionException When transition is not permitted
     *
     * Validates Requirements 3.2, 3.3, 3.4, 3.5
     */
    public function approveBerkas(int $akreditasiId, int $adminId, int $asesor1Id, int $asesor2Id, string $clientUpdatedAt = ''): void
    {
        // Req 3.2: Asesor_1 ≠ Asesor_2
        if ($asesor1Id === $asesor2Id) {
            throw new \DomainException(
                'Asesor_1 dan Asesor_2 harus berbeda. Tidak dapat menugaskan asesor yang sama untuk kedua peran.'
            );
        }

        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi) {
            throw new \DomainException("Akreditasi #{$akreditasiId} tidak ditemukan.");
        }

        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS) {
            throw new \DomainException(
                "Akreditasi tidak berada pada status Verifikasi Berkas (status saat ini: {$akreditasi->status})."
            );
        }

        // Req 3.3: Both asesors must exist
        $asesor1 = Asesor::where('user_id', $asesor1Id)->first();
        $asesor2 = Asesor::where('user_id', $asesor2Id)->first();

        if (! $asesor1) {
            throw new \DomainException("Asesor_1 dengan user_id={$asesor1Id} tidak ditemukan.");
        }
        if (! $asesor2) {
            throw new \DomainException("Asesor_2 dengan user_id={$asesor2Id} tidak ditemukan.");
        }

        $adminUser = User::findOrFail($adminId);

        DB::transaction(function () use ($akreditasi, $akreditasiId, $adminUser, $asesor1, $asesor2, $clientUpdatedAt) {
            if ($clientUpdatedAt !== '') {
                $this->assertNotStale($akreditasi->id, $clientUpdatedAt);
                $akreditasi->refresh();
            }

            // Remove any existing assessments for this akreditasi
            Assessment::where('akreditasi_id', $akreditasiId)->delete();

            $tanggalMulai = now();
            $tanggalBerakhir = $tanggalMulai->copy()->addDays(
                (int) config('akreditasi-timeout.assessment.default_duration_days', 30)
            );

            // Create Assessment for Asesor_1 (tipe=1)
            Assessment::create([
                'akreditasi_id' => $akreditasiId,
                'asesor_id' => $asesor1->id,
                'tipe' => 1,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_berakhir' => $tanggalBerakhir,
            ]);

            // Create Assessment for Asesor_2 (tipe=2)
            Assessment::create([
                'akreditasi_id' => $akreditasiId,
                'asesor_id' => $asesor2->id,
                'tipe' => 2,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_berakhir' => $tanggalBerakhir,
            ]);

            // Req 3.4: Transition 5 → 4 via state machine
            $this->stateMachine->transition($akreditasi, AkreditasiStateMachine::STATUS_ASSESSMENT, $adminUser);

            $this->auditTrailService->log(
                akreditasiId: $akreditasiId,
                actionType: 'asesor_assigned',
                newValue: $asesor1->nama_dengan_gelar.'; '.$asesor2->nama_dengan_gelar,
                metadata: [
                    'asesor_1_id' => $asesor1->id,
                    'asesor_1_user_id' => $asesor1->user_id,
                    'asesor_2_id' => $asesor2->id,
                    'asesor_2_user_id' => $asesor2->user_id,
                ],
            );

            $this->auditTrailService->log(
                akreditasiId: $akreditasiId,
                actionType: 'approved',
                newValue: 'Berkas disetujui dan tim asesor ditugaskan.',
                metadata: ['stage' => 'verifikasi_berkas'],
            );
        });

        // Req 3.5: Notify Pesantren, Asesor_1, Asesor_2 (after transaction)
        $this->notifyApproveBerkas($akreditasi, $asesor1, $asesor2);

        // Dispatch AsesorAssigned event for notification system
        $asesor1User = User::find($asesor1->user_id);
        $asesor2User = User::find($asesor2->user_id);
        if ($asesor1User && $asesor2User) {
            event(new AsesorAssigned($akreditasi, $asesor1User, $asesor2User));
        }
    }

    // =========================================================================
    // Task 9.5: rejectBerkas — Req 3.6-3.8
    // =========================================================================

    /**
     * Admin rejects berkas, delegating to RejectionService, transitioning 5 → -1.
     *
     * Delegates structured rejection logic to RejectionService::rejectBerkas().
     *
     * @param  array  $rejectionData  ['sections' => [...], 'catatan' => '...']
     *
     * @throws \DomainException When rejection fails
     *
     * Validates Requirements 3.6, 3.7, 3.8
     */
    public function rejectBerkas(int $akreditasiId, int $adminId, array $rejectionData): void
    {
        $result = $this->rejectionService->rejectBerkas($akreditasiId, $adminId, $rejectionData);

        if (! $result['success']) {
            $errorMessages = [
                'invalid_status' => 'Akreditasi tidak berada pada status Verifikasi Berkas.',
                'unauthorized' => 'Hanya Admin yang dapat menolak berkas.',
                'sections_required' => 'Minimal satu bagian harus dipilih untuk penolakan.',
                'catatan_required' => 'Catatan penolakan wajib diisi.',
                'catatan_too_long' => 'Catatan penolakan tidak boleh melebihi 2000 karakter.',
            ];

            $message = $errorMessages[$result['error']] ?? ('Penolakan berkas gagal: '.$result['error']);
            throw new \DomainException($message);
        }
    }

    // =========================================================================
    // Stub methods for future tasks (10, 11) — defined in design.md interface
    // =========================================================================

    /**
     * Create a document rejection by Asesor_1 at status 4.
     * Delegates to RejectionService::createDocumentRejection().
     * Implemented in Task 10.1.
     */
    public function createDocumentRejection(int $akreditasiId, int $asesor1Id, array $items, string $explanation): void
    {
        $result = $this->rejectionService->createDocumentRejection($akreditasiId, $asesor1Id, $items, $explanation);
        if (! $result['success']) {
            throw new \DomainException('Penolakan dokumen gagal: '.($result['error'] ?? 'unknown'));
        }
    }

    /**
     * Submit perbaikan (corrections) by Pesantren.
     * Delegates to RejectionService::submitPerbaikan().
     * Implemented in Task 10.2.
     */
    public function submitPerbaikan(int $akreditasiId, int $pesantrenId): void
    {
        $result = $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenId);
        if (! $result['success']) {
            throw new \DomainException('Submit perbaikan gagal: '.($result['error'] ?? 'unknown'));
        }
    }

    // =========================================================================
    // Task 10.3: scheduleVisitasi — Req 5.1-5.5
    // =========================================================================

    /**
     * Schedule visitasi by Asesor_1 (status 4 → 3).
     *
     * Validates:
     *  - Akreditasi is at status 4 (Review Asesor)
     *  - Actor is assigned Asesor_1 (tipe=1) for this akreditasi
     *  - tanggal_mulai ≥ today + 7 days (Req 5.2)
     *  - tanggal_akhir ≥ tanggal_mulai (Req 5.2)
     *  - (tanggal_akhir - tanggal_mulai) ≤ 14 days (Req 5.2)
     *  - catatan_visitasi max 1000 chars (Req 5.1)
     *
     * On success:
     *  - Saves tgl_visitasi, tgl_visitasi_akhir, catatan_visitasi to akreditasi
     *  - Transitions 4 → 3 via AkreditasiStateMachine
     *  - Notifies Pesantren and Admin with schedule details (Req 5.5)
     *
     * @param  array  $scheduleData  ['tanggal_mulai' => 'Y-m-d', 'tanggal_akhir' => 'Y-m-d', 'catatan_visitasi' => '...']
     *
     * @throws \DomainException When validation fails
     *
     * Validates Requirements 5.1, 5.2, 5.3, 5.4, 5.5
     */
    public function scheduleVisitasi(int $akreditasiId, int $asesor1Id, array $scheduleData): void
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi) {
            throw new \DomainException("Akreditasi #{$akreditasiId} tidak ditemukan.");
        }

        // Req 5.1: Must be at status 4 (Review Asesor)
        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_ASSESSMENT) {
            throw new \DomainException(
                "Penjadwalan visitasi hanya dapat dilakukan saat tahap Review Asesor (status saat ini: {$akreditasi->status})."
            );
        }

        // Validate actor is Asesor_1 (tipe=1) for this akreditasi
        $isAsesor1 = Assessment::where('akreditasi_id', $akreditasiId)
            ->whereHas('asesor', fn ($q) => $q->where('user_id', $asesor1Id))
            ->where('tipe', 1)
            ->exists();

        if (! $isAsesor1) {
            throw new \DomainException(
                'Hanya Ketua Kelompok yang ditugaskan yang dapat menjadwalkan visitasi.'
            );
        }

        // Validate dates (Req 5.2)
        $this->validateVisitasiDates($scheduleData);

        // Validate catatan_visitasi max 1000 chars (Req 5.1)
        $catatan = $scheduleData['catatan_visitasi'] ?? '';
        if (strlen($catatan) > 1000) {
            throw new \DomainException('Catatan visitasi tidak boleh melebihi 1000 karakter.');
        }

        $tanggalMulai = $scheduleData['tanggal_mulai'];
        $tanggalAkhir = $scheduleData['tanggal_akhir'];
        $asesor1User = User::findOrFail($asesor1Id);

        DB::transaction(function () use ($akreditasi, $tanggalMulai, $tanggalAkhir, $catatan, $asesor1User) {
            // Save schedule data to akreditasi
            $akreditasi->update([
                'tgl_visitasi' => $tanggalMulai,
                'tgl_visitasi_akhir' => $tanggalAkhir,
                'catatan_visitasi' => $catatan,
            ]);

            // Transition 4 → 3 via state machine
            $this->stateMachine->transition($akreditasi, AkreditasiStateMachine::STATUS_VISITASI, $asesor1User);
        });

        // Req 5.5: Notify Pesantren and Admin (after transaction)
        $this->notifyVisitasiScheduled($akreditasi, $tanggalMulai, $tanggalAkhir, $catatan, isReschedule: false);

        // Task 12.3: Dispatch VisitasiScheduled event for extensibility
        event(new VisitasiScheduled($akreditasi, [
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_akhir' => $tanggalAkhir,
            'catatan_visitasi' => $catatan,
        ], isReschedule: false));
    }

    // =========================================================================
    // Task 10.4: rescheduleVisitasi — Req 5.6-5.9
    // =========================================================================

    /**
     * Reschedule visitasi by Asesor_1 (status must be 3).
     *
     * Validates:
     *  - Akreditasi is at status 3 (Visitasi)
     *  - Actor is assigned Asesor_1 (tipe=1)
     *  - Current date is at least 7 days before tanggal_mulai (H-7 window open) (Req 5.6, 5.9)
     *  - Same date validation rules as scheduleVisitasi (Req 5.7)
     *  - catatan max 1000 chars
     *
     * On success:
     *  - Updates tgl_visitasi, tgl_visitasi_akhir, catatan_visitasi on akreditasi
     *  - Notifies Pesantren and Admin with updated schedule (Req 5.8)
     *
     * @param  array  $scheduleData  ['tanggal_mulai' => 'Y-m-d', 'tanggal_akhir' => 'Y-m-d', 'catatan_visitasi' => '...']
     *
     * @throws \DomainException When validation fails
     *
     * Validates Requirements 5.6, 5.7, 5.8, 5.9
     */
    public function rescheduleVisitasi(int $akreditasiId, int $asesor1Id, array $scheduleData): void
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi) {
            throw new \DomainException("Akreditasi #{$akreditasiId} tidak ditemukan.");
        }

        // Must be at status 3 (Visitasi)
        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_VISITASI) {
            throw new \DomainException(
                "Penjadwalan ulang visitasi hanya dapat dilakukan saat status Visitasi (status saat ini: {$akreditasi->status})."
            );
        }

        // Validate actor is Asesor_1 (tipe=1) for this akreditasi
        $isAsesor1 = Assessment::where('akreditasi_id', $akreditasiId)
            ->whereHas('asesor', fn ($q) => $q->where('user_id', $asesor1Id))
            ->where('tipe', 1)
            ->exists();

        if (! $isAsesor1) {
            throw new \DomainException(
                'Hanya Ketua Kelompok yang ditugaskan yang dapat menjadwalkan ulang visitasi.'
            );
        }

        // Req 5.6, 5.9: Validate H-7 window — current date must be at least 7 days before tanggal_mulai
        $currentTanggalMulai = $akreditasi->tgl_visitasi;
        if ($currentTanggalMulai) {
            $mulaiDate = Carbon::parse($currentTanggalMulai)->startOfDay();
            $today = Carbon::today();
            $daysUntilVisitasi = $today->diffInDays($mulaiDate, false);

            if ($daysUntilVisitasi < 7) {
                throw new \DomainException(
                    'Penjadwalan ulang tidak dapat dilakukan. Jendela H-7 telah ditutup (kurang dari 7 hari sebelum tanggal mulai visitasi).'
                );
            }
        }

        // Validate new dates (same rules as scheduleVisitasi — Req 5.7)
        $this->validateVisitasiDates($scheduleData);

        // Validate catatan max 1000 chars
        $catatan = $scheduleData['catatan_visitasi'] ?? '';
        if (strlen($catatan) > 1000) {
            throw new \DomainException('Catatan visitasi tidak boleh melebihi 1000 karakter.');
        }

        $tanggalMulai = $scheduleData['tanggal_mulai'];
        $tanggalAkhir = $scheduleData['tanggal_akhir'];

        // Update schedule data on akreditasi
        $akreditasi->update([
            'tgl_visitasi' => $tanggalMulai,
            'tgl_visitasi_akhir' => $tanggalAkhir,
            'catatan_visitasi' => $catatan,
        ]);

        // Req 5.8: Notify Pesantren and Admin with updated schedule (after update)
        $this->notifyVisitasiScheduled($akreditasi, $tanggalMulai, $tanggalAkhir, $catatan, isReschedule: true);

        // Task 12.3: Dispatch VisitasiScheduled event for extensibility
        event(new VisitasiScheduled($akreditasi, [
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_akhir' => $tanggalAkhir,
            'catatan_visitasi' => $catatan,
        ], isReschedule: true));
    }

    // =========================================================================
    // Task 10.5: confirmVisitasiSelesai — Req 6.1-6.7
    // =========================================================================

    /**
     * Confirm visitasi selesai by Asesor_1 (status 3 → 2).
     *
     * Validates:
     *  - Akreditasi is at status 3 (Visitasi)
     *  - Actor is assigned Asesor_1 (tipe=1) (Req 6.7)
     *  - Current date ≥ tgl_visitasi (tanggal_mulai) (Req 6.2, 6.6)
     *
     * On success:
     *  - Records visitasi_confirmed_at = now()
     *  - Transitions 3 → 2 via AkreditasiStateMachine
     *  - Notifies Admin, Asesor_2, Pesantren (Req 6.4)
     *
     *
     * @throws \DomainException When validation fails
     *
     * Validates Requirements 6.2, 6.3, 6.4, 6.6, 6.7
     */
    public function confirmVisitasiSelesai(int $akreditasiId, int $asesor1Id): void
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi) {
            throw new \DomainException("Akreditasi #{$akreditasiId} tidak ditemukan.");
        }

        // Must be at status 3 (Visitasi)
        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_VISITASI) {
            throw new \DomainException(
                "Konfirmasi visitasi selesai hanya dapat dilakukan saat status Visitasi (status saat ini: {$akreditasi->status})."
            );
        }

        // Req 6.7: Validate actor is Asesor_1 (tipe=1) for this akreditasi
        $isAsesor1 = Assessment::where('akreditasi_id', $akreditasiId)
            ->whereHas('asesor', fn ($q) => $q->where('user_id', $asesor1Id))
            ->where('tipe', 1)
            ->exists();

        if (! $isAsesor1) {
            throw new \DomainException(
                'Hanya Ketua Kelompok yang ditugaskan yang dapat mengonfirmasi visitasi selesai.'
            );
        }

        // Req 6.2, 6.6: Validate current date ≥ tgl_visitasi (tanggal_mulai)
        $tanggalMulai = $akreditasi->tgl_visitasi;
        if ($tanggalMulai) {
            $mulaiDate = Carbon::parse($tanggalMulai)->startOfDay();
            $today = Carbon::today();

            if ($today->lt($mulaiDate)) {
                throw new \DomainException(
                    'Visitasi belum dapat dikonfirmasi selesai. Periode visitasi belum dimulai (tanggal mulai: '.
                    $mulaiDate->format('d/m/Y').').'
                );
            }
        }

        $asesor1User = User::findOrFail($asesor1Id);

        DB::transaction(function () use ($akreditasi, $asesor1User) {
            // Transition 3 → 2 via state machine.
            // Keep the transition as the first write so its optimistic lock
            // checks the row state loaded at the start of this action.
            $this->stateMachine->transition($akreditasi, AkreditasiStateMachine::STATUS_PASCA_VISITASI, $asesor1User);

            // Req 6.3: Record visitasi_confirmed_at = now()
            $akreditasi->update([
                'visitasi_confirmed_at' => now(),
            ]);
        });

        // Req 6.4: Notify Admin, Asesor_2, Pesantren (after transaction)
        $this->notifyVisitasiSelesai($akreditasi);
    }

    // =========================================================================
    // Task 11.3: finalizeAssessorScoring — Req 7.11, 7.13, 7.14, 8.10
    // =========================================================================

    /**
     * Finalize assessor scoring and transition status 2 → 1.
     *
     * Validates:
     *  - All 62 NA1 are Final (Asesor_1)
     *  - All 62 NK are Final (Asesor_1)
     *  - All 62 catatan_butir are Final (Asesor_1) — stored as `catatan` in akreditasi_edpms
     *  - All 4 catatan_rekomendasi are Final (Asesor_1) — stored in akreditasi_edpm_catatans
     *  - All 62 NA2 are Final (Asesor_2)
     *  - All 4 required documents uploaded:
     *      laporan_visitasi_asesor1, laporan_visitasi_asesor2,
     *      laporan_visitasi_kelompok, kartu_kendali
     *
     * On success:
     *  - Sets is_nilai_asesor_final=true and is_nilai_asesor2_final=true
     *  - Transitions 2 → 1 via AkreditasiStateMachine
     *  - Notifies Admin
     *
     *
     * @throws \DomainException When any precondition fails
     *
     * Validates Requirements 7.11, 7.13, 7.14, 8.10
     */
    public function finalizeAssessorScoring(int $akreditasiId, int $asesor1Id): void
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi) {
            throw new \DomainException("Akreditasi #{$akreditasiId} tidak ditemukan.");
        }

        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            throw new \DomainException(
                "Finalisasi penilaian hanya dapat dilakukan saat tahap Penilaian Pasca Visitasi (status saat ini: {$akreditasi->status})."
            );
        }

        // Validate actor is Asesor_1 (tipe=1) for this akreditasi
        $asesor1Assessment = Assessment::where('akreditasi_id', $akreditasiId)
            ->whereHas('asesor', fn ($q) => $q->where('user_id', $asesor1Id))
            ->where('tipe', 1)
            ->first();

        if (! $asesor1Assessment) {
            throw new \DomainException('Hanya Ketua Kelompok yang ditugaskan yang dapat memfinalisasi penilaian.');
        }

        $asesor1ModelId = $asesor1Assessment->asesor_id;

        // Get Asesor_2 user_id
        $asesor2Assessment = Assessment::where('akreditasi_id', $akreditasiId)
            ->where('tipe', 2)
            ->with('asesor')
            ->first();

        if (! $asesor2Assessment || ! $asesor2Assessment->asesor) {
            throw new \DomainException('Anggota Kelompok tidak ditemukan untuk akreditasi ini.');
        }

        $asesor2ModelId = $asesor2Assessment->asesor_id;

        // Validate all 62 NA1 are Final (Asesor_1)
        $na1FinalCount = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->where('asesor_id', $asesor1ModelId)
            ->whereNotNull('isian')
            ->where('is_final', true)
            ->count();

        if ($na1FinalCount < 62) {
            throw new \DomainException(
                "Belum semua Nilai Ketua final. Sudah final: {$na1FinalCount}/62."
            );
        }

        // Validate all 62 NK are Final (Asesor_1)
        $nkFinalCount = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->where('asesor_id', $asesor1ModelId)
            ->whereNotNull('nk')
            ->where('is_final', true)
            ->count();

        if ($nkFinalCount < 62) {
            throw new \DomainException(
                "Belum semua Nilai Kelompok final. Sudah final: {$nkFinalCount}/62."
            );
        }

        // Validate all 62 catatan_butir are Final (Asesor_1) — stored as `catatan` in akreditasi_edpms
        $catatanButirFinalCount = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->where('asesor_id', $asesor1ModelId)
            ->whereNotNull('catatan')
            ->where('is_final', true)
            ->count();

        if ($catatanButirFinalCount < 62) {
            throw new \DomainException(
                "Belum semua catatan butir Final. Sudah Final: {$catatanButirFinalCount}/62."
            );
        }

        // Validate all 4 catatan_rekomendasi are Final (Asesor_1) — stored in akreditasi_edpm_catatans
        $catatanRekomendasiFinalCount = AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasiId)
            ->where('asesor_id', $asesor1ModelId)
            ->whereNotNull('catatan')
            ->count();

        if ($catatanRekomendasiFinalCount < 4) {
            throw new \DomainException(
                "Belum semua catatan rekomendasi diisi. Sudah diisi: {$catatanRekomendasiFinalCount}/4."
            );
        }

        // Validate all 62 NA2 are Final (Asesor_2)
        $na2FinalCount = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->where('asesor_id', $asesor2ModelId)
            ->whereNotNull('isian')
            ->where('is_final', true)
            ->count();

        if ($na2FinalCount < 62) {
            throw new \DomainException(
                "Belum semua Nilai Anggota final. Sudah final: {$na2FinalCount}/62."
            );
        }

        // Validate all required post-visitasi documents uploaded (Req 8.10)
        $this->assertPostVisitasiDocumentsComplete($akreditasi);

        $asesor1User = User::findOrFail($asesor1Id);

        DB::transaction(function () use ($akreditasi, $asesor1User) {
            // Set both finalization flags
            $akreditasi->update([
                'is_nilai_asesor_final' => true,
                'is_nilai_asesor2_final' => true,
            ]);

            // Transition 2 → 1 via state machine
            $this->stateMachine->transition($akreditasi, AkreditasiStateMachine::STATUS_VALIDASI_ADMIN, $asesor1User);
        });

        // Req 7.14: Notify Admin (after transaction)
        $this->notifyAdminsScoringFinalized($akreditasi);

        // Task 12.3: Dispatch ScoringCompleted event for extensibility
        event(new ScoringCompleted($akreditasi));

        // Dispatch AsesorPackageSubmitted event for notification system
        event(new AsesorPackageSubmitted($akreditasi));
    }

    // =========================================================================
    // Task 11.5: issueSK — Req 11.1-11.8
    // =========================================================================

    /**
     * Issue SK (Surat Keputusan) and transition status 1 → 0.
     *
     * Validates:
     *  - All 62 NV are Final (is_nv_final check)
     *  - nomor_sk (required, max 100 chars)
     *  - masa_berlaku (date, today or later)
     *  - masa_berlaku_akhir (date, after masa_berlaku)
     *  - sertifikat_path (must be uploaded — validate it's been set)
     *  - catatan_rekomendasi_admin (max 2000 chars)
     *
     * On success:
     *  - Auto-populates nilai_akhir and peringkat from ScoreCalculationService::calculateAll()
     *  - Sets is_nv_final=true on akreditasi
     *  - Saves SK data to akreditasi
     *  - Transitions 1 → 0 via AkreditasiStateMachine
     *  - Notifies Pesantren with nilai_akhir, peringkat, nomor_sk
     *
     * @param  array  $skData  [
     *                         'nomor_sk'                 => string,
     *                         'masa_berlaku'             => 'Y-m-d',
     *                         'masa_berlaku_akhir'       => 'Y-m-d',
     *                         'sertifikat_path'          => string,
     *                         'catatan_rekomendasi_admin'=> string (optional),
     *                         ]
     * @param  string  $clientUpdatedAt  Optional ISO timestamp for optimistic locking
     *
     * @throws \DomainException When any precondition or validation fails
     *
     * Validates Requirements 11.1-11.8
     */
    public function issueSK(int $akreditasiId, int $adminId, array $skData, string $clientUpdatedAt = ''): void
    {
        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi) {
            throw new \DomainException("Akreditasi #{$akreditasiId} tidak ditemukan.");
        }

        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_VALIDASI_ADMIN) {
            throw new \DomainException(
                "Penerbitan SK hanya dapat dilakukan saat status Validasi Admin (status saat ini: {$akreditasi->status})."
            );
        }

        $this->assertPostVisitasiDocumentsComplete($akreditasi);

        // Validate all 62 NV are Final (Req 11.1)
        $nvFinalCount = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->whereNotNull('nv')
            ->where('is_final', true)
            ->count();

        if ($nvFinalCount < 62) {
            throw new \DomainException(
                "Belum semua NV Final. Sudah Final: {$nvFinalCount}/62. Semua NV harus Final sebelum SK dapat diterbitkan."
            );
        }

        // Validate nomor_sk (required, max 100 chars) — Req 11.2
        $nomorSk = $skData['nomor_sk'] ?? null;
        if (empty($nomorSk)) {
            throw new \DomainException('Nomor SK wajib diisi.');
        }
        if (strlen($nomorSk) > 100) {
            throw new \DomainException('Nomor SK tidak boleh melebihi 100 karakter.');
        }

        // Validate masa_berlaku (date, today or later) — Req 11.2
        $masaBerlakuRaw = $skData['masa_berlaku'] ?? null;
        if (empty($masaBerlakuRaw)) {
            throw new \DomainException('Masa berlaku wajib diisi.');
        }
        $masaBerlaku = Carbon::parse($masaBerlakuRaw)->startOfDay();
        $today = Carbon::today();
        if ($masaBerlaku->lt($today)) {
            throw new \DomainException('Masa berlaku harus hari ini atau setelahnya.');
        }

        // Validate masa_berlaku_akhir (date, after masa_berlaku) — Req 11.2
        $masaBerlakuAkhirRaw = $skData['masa_berlaku_akhir'] ?? null;
        if (empty($masaBerlakuAkhirRaw)) {
            throw new \DomainException('Masa berlaku akhir wajib diisi.');
        }
        $masaBerlakuAkhir = Carbon::parse($masaBerlakuAkhirRaw)->startOfDay();
        if (! $masaBerlakuAkhir->gt($masaBerlaku)) {
            throw new \DomainException('Masa berlaku akhir harus setelah masa berlaku.');
        }

        // Validate sertifikat_path (must be uploaded) — Req 11.2
        $sertifikatPath = $skData['sertifikat_path'] ?? null;
        if (empty($sertifikatPath)) {
            throw new \DomainException('Sertifikat SK wajib diunggah.');
        }

        // Validate catatan_rekomendasi_admin (max 2000 chars) — Req 11.2
        $catatanRekomendasiAdmin = $skData['catatan_rekomendasi_admin'] ?? '';
        if (strlen($catatanRekomendasiAdmin) > 2000) {
            throw new \DomainException('Catatan rekomendasi admin tidak boleh melebihi 2000 karakter.');
        }

        // Auto-populate nilai_akhir and peringkat from ScoreCalculationService (Req 11.3)
        $nvRecords = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->whereNotNull('nv')
            ->where('is_final', true)
            ->get();

        // Build NV values array for score calculation
        // We need to map butir_id to komponen for calculateAll()
        // For now, calculate using the flat NV values
        $allNvValues = $nvRecords->pluck('nv', 'butir_id')->toArray();
        ksort($allNvValues);
        $nvFlat = array_values($allNvValues);

        // Split into IK (first 40) and IPR (last 22) based on butir ordering
        $ikNvFlat = array_slice($nvFlat, 0, 40);
        $iprNvFlat = array_slice($nvFlat, 40, 22);

        // Build IK values per komponen
        $ikByKomponen = [];
        $offset = 0;
        foreach (ScoreCalculationService::KOMPONEN_CONFIG as $komponenName => $config) {
            $ikByKomponen[$komponenName] = array_slice($ikNvFlat, $offset, $config['butir_count']);
            $offset += $config['butir_count'];
        }

        $scoreResult = $this->scoreCalculationService->calculateAll([
            'ik' => $ikByKomponen,
            'ipr' => $iprNvFlat,
        ]);

        $nilaiAkhir = $scoreResult['nilai_akhir'];
        $peringkat = $scoreResult['peringkat'];

        $adminUser = User::findOrFail($adminId);

        DB::transaction(function () use (
            $akreditasi, $adminUser, $nomorSk, $masaBerlaku, $masaBerlakuAkhir,
            $sertifikatPath, $catatanRekomendasiAdmin, $nilaiAkhir, $peringkat, $clientUpdatedAt
        ) {
            if ($clientUpdatedAt !== '') {
                $this->assertNotStale($akreditasi->id, $clientUpdatedAt);
                $akreditasi->refresh();
            }

            // Set is_nv_final=true and save SK data
            $akreditasi->update([
                'is_nv_final' => true,
                'nomor_sk' => $nomorSk,
                'masa_berlaku' => $masaBerlaku->toDateString(),
                'masa_berlaku_akhir' => $masaBerlakuAkhir->toDateString(),
                'sertifikat_path' => $sertifikatPath,
                'catatan_rekomendasi_admin' => $catatanRekomendasiAdmin,
                'nilai' => $nilaiAkhir,
                'peringkat' => $peringkat,
            ]);

            // Transition 1 → 0 via state machine
            $this->stateMachine->transition($akreditasi, AkreditasiStateMachine::STATUS_SELESAI, $adminUser);

            $this->auditTrailService->log(
                akreditasiId: $akreditasi->id,
                actionType: 'approved',
                newValue: 'Akreditasi disetujui dan SK diterbitkan.',
                metadata: [
                    'stage' => 'validasi_admin',
                    'nomor_sk' => $nomorSk,
                    'nilai' => $nilaiAkhir,
                    'peringkat' => $peringkat,
                ],
            );
        });

        // Req 11.5: Notify Pesantren with nilai_akhir, peringkat, nomor_sk (after transaction)
        $this->notifyPesantrenSKIssued($akreditasi, $nilaiAkhir, $peringkat, $nomorSk);

        // Task 12.3: Dispatch SKIssued event for extensibility
        event(new SKIssued($akreditasi, $nilaiAkhir, $peringkat, $nomorSk));
    }

    // =========================================================================
    // Task 11.6: rejectAtValidasi — Req 11.6
    // =========================================================================

    /**
     * Reject akreditasi at Validasi Admin stage (status 1 → -1).
     *
     * Validates:
     *  - reason provided (max 2000 chars)
     *
     * On success:
     *  - Creates AkreditasiRejection with type='admin_final'
     *  - Transitions 1 → -1 via AkreditasiStateMachine
     *
     * @param  string  $reason  Rejection reason (required, max 2000 chars)
     *
     * @throws \DomainException When validation fails
     *
     * Validates Requirement 11.6
     */
    public function rejectAtValidasi(
        int $akreditasiId,
        int $adminId,
        string $reason,
        string $clientUpdatedAt = '',
        ?array $categories = null,
    ): void {
        if (empty(trim($reason))) {
            throw new \DomainException('Alasan penolakan wajib diisi.');
        }

        if (strlen($reason) > 2000) {
            throw new \DomainException('Alasan penolakan tidak boleh melebihi 2000 karakter.');
        }

        $akreditasi = $this->akreditasiRepository->find($akreditasiId);
        if (! $akreditasi) {
            throw new \DomainException("Akreditasi #{$akreditasiId} tidak ditemukan.");
        }

        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_VALIDASI_ADMIN) {
            throw new \DomainException(
                "Penolakan hanya dapat dilakukan saat status Validasi Admin (status saat ini: {$akreditasi->status})."
            );
        }

        $adminUser = User::findOrFail($adminId);

        DB::transaction(function () use ($akreditasi, $adminId, $reason, $adminUser, $clientUpdatedAt, $categories) {
            if ($clientUpdatedAt !== '') {
                $this->assertNotStale($akreditasi->id, $clientUpdatedAt);
                $akreditasi->refresh();
            }

            // Create AkreditasiRejection with type='admin_final'
            AkreditasiRejection::create([
                'akreditasi_id' => $akreditasi->id,
                'user_id' => $adminId,
                'type' => 'admin_final',
                'explanation' => $reason,
                'categories' => $categories,
                'status' => 'final',
            ]);

            // Transition 1 → -1 via state machine
            $this->stateMachine->transition($akreditasi, AkreditasiStateMachine::STATUS_DITOLAK, $adminUser);
            $this->auditTrailService->log(
                akreditasiId: $akreditasi->id,
                actionType: 'rejected',
                oldValue: Akreditasi::getStatusLabel(AkreditasiStateMachine::STATUS_VALIDASI_ADMIN),
                newValue: Akreditasi::getStatusLabel(AkreditasiStateMachine::STATUS_DITOLAK),
                metadata: [
                    'stage' => 'validasi_admin',
                    'reason' => $reason,
                    'categories' => $categories,
                ],
            );
            Pesantren::where('user_id', $akreditasi->user_id)->update(['is_locked' => false]);
        });

        $categorySummary = collect($categories ?? [])
            ->map(function (array $entry): string {
                $category = $entry['category'] ?? '';
                $label = config('akreditasi.final_rejection_categories.'.$category, $category);
                $explanation = $entry['explanation'] ?? '';

                return trim($label.($explanation !== '' ? ': '.$explanation : ''));
            })
            ->filter()
            ->implode('; ');

        $pesantrenUser = User::find($akreditasi->user_id);
        if ($pesantrenUser) {
            $pesantrenUser->notify(new AkreditasiNotification(
                'final_rejection',
                'Akreditasi Ditolak',
                'Akreditasi ditolak pada tahap validasi admin.'.($categorySummary !== '' ? ' Catatan validasi: '.$categorySummary : ' Catatan: '.$reason),
                '#'
            ));
        }
    }

    /**
     * Submit banding (status -1 → -2).
     * Delegates to BandingService::submitBanding().
     */
    public function submitBanding(int $akreditasiId, int $pesantrenId, string $alasan): void
    {
        $result = $this->bandingService->submitBanding($akreditasiId, $pesantrenId, $alasan);
        if (! $result['success']) {
            throw new \DomainException('Submit banding gagal: '.($result['error'] ?? 'unknown'));
        }
    }

    /**
     * Decide banding result.
     * Delegates to BandingService::decideBanding().
     */
    public function decideBanding(int $bandingId, int $adminId, string $result, string $keputusan = ''): void
    {
        $outcome = $this->bandingService->decideBanding($bandingId, $adminId, $result, $keputusan);
        if (! $outcome['success']) {
            throw new \DomainException('Keputusan banding gagal: '.($outcome['error'] ?? 'unknown'));
        }
    }

    private function assertPostVisitasiDocumentsComplete(Akreditasi $akreditasi): void
    {
        $missingDocs = app(AkreditasiDocumentService::class)->missingPostVisitasiDocuments($akreditasi);

        if (! empty($missingDocs)) {
            throw new \DomainException(
                'Dokumen berikut belum diunggah: '.implode(', ', $missingDocs).'.'
            );
        }
    }

    // =========================================================================
    // Private notification helpers
    // =========================================================================

    /**
     * Notify all Admin users about a new pengajuan.
     * Dispatched after the DB transaction commits (non-blocking).
     */
    private function notifyAdminsNewPengajuan(Akreditasi $akreditasi): void
    {
        try {
            $user = User::find($akreditasi->user_id);
            $pesantrenName = $user?->pesantren?->nama_pesantren ?? $user?->name ?? 'Pesantren';

            $admins = User::where('role_id', 1)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AkreditasiNotification(
                    'pengajuan',
                    'Pengajuan Akreditasi Baru',
                    "Pesantren {$pesantrenName} telah mengajukan akreditasi baru.",
                    route('admin.akreditasi')
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiWorkflowService: Failed to notify admins of new pengajuan', [
                'akreditasi_id' => $akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Private: Visitasi date validation helper
    // =========================================================================

    /**
     * Validate visitasi schedule dates per Req 5.2.
     *
     * Rules:
     *  - tanggal_mulai ≥ today + 7 days
     *  - tanggal_akhir ≥ tanggal_mulai
     *  - (tanggal_akhir - tanggal_mulai) ≤ 14 days
     *
     * @param  array  $scheduleData  ['tanggal_mulai' => 'Y-m-d', 'tanggal_akhir' => 'Y-m-d']
     *
     * @throws \DomainException When any date rule is violated
     */
    private function validateVisitasiDates(array $scheduleData): void
    {
        $tanggalMulaiRaw = $scheduleData['tanggal_mulai'] ?? null;
        $tanggalAkhirRaw = $scheduleData['tanggal_akhir'] ?? null;

        if (! $tanggalMulaiRaw) {
            throw new \DomainException('Tanggal mulai visitasi wajib diisi.');
        }
        if (! $tanggalAkhirRaw) {
            throw new \DomainException('Tanggal akhir visitasi wajib diisi.');
        }

        $tanggalMulai = Carbon::parse($tanggalMulaiRaw)->startOfDay();
        $tanggalAkhir = Carbon::parse($tanggalAkhirRaw)->startOfDay();
        $minMulai = Carbon::today()->addDays(7);

        // Rule 1: tanggal_mulai ≥ today + 7 days
        if ($tanggalMulai->lt($minMulai)) {
            throw new \DomainException(
                'Tanggal mulai visitasi harus minimal 7 hari dari sekarang (minimal: '.
                $minMulai->format('d/m/Y').').'
            );
        }

        // Rule 2: tanggal_akhir ≥ tanggal_mulai
        if ($tanggalAkhir->lt($tanggalMulai)) {
            throw new \DomainException(
                'Tanggal akhir visitasi tidak boleh sebelum tanggal mulai.'
            );
        }

        // Rule 3: (tanggal_akhir - tanggal_mulai) ≤ 14 days
        $durationDays = $tanggalMulai->diffInDays($tanggalAkhir);
        if ($durationDays > 14) {
            throw new \DomainException(
                "Durasi visitasi tidak boleh melebihi 14 hari (durasi saat ini: {$durationDays} hari)."
            );
        }
    }

    // =========================================================================
    // Private: Visitasi notification helpers
    // =========================================================================

    /**
     * Notify Pesantren and Admin after visitasi is scheduled or rescheduled.
     */
    private function notifyVisitasiScheduled(
        Akreditasi $akreditasi,
        string $tanggalMulai,
        string $tanggalAkhir,
        string $catatan,
        bool $isReschedule,
    ): void {
        try {
            $mulaiFormatted = Carbon::parse($tanggalMulai)->format('d/m/Y');
            $akhirFormatted = Carbon::parse($tanggalAkhir)->format('d/m/Y');
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
                    route('pesantren.akreditasi')
                ));
            }

            // Notify all Admins
            $admins = User::where('role_id', 1)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AkreditasiNotification(
                    $isReschedule ? 'visitasi_rescheduled_admin' : 'visitasi_scheduled_admin',
                    $title,
                    $message,
                    route('admin.akreditasi')
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiWorkflowService: Failed to notify visitasi scheduled', [
                'akreditasi_id' => $akreditasi->id,
                'is_reschedule' => $isReschedule,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify Admin, Asesor_2, and Pesantren after visitasi selesai is confirmed.
     */
    private function notifyVisitasiSelesai(Akreditasi $akreditasi): void
    {
        try {
            $title = 'Visitasi Selesai — Tahap Penilaian Dimulai';
            $message = 'Visitasi telah dikonfirmasi selesai. Tahap penilaian pasca visitasi telah dimulai.';

            // Notify Pesantren
            $pesantrenUser = User::find($akreditasi->user_id);
            if ($pesantrenUser) {
                $pesantrenUser->notify(new AkreditasiNotification(
                    'visitasi_selesai',
                    $title,
                    $message,
                    route('pesantren.akreditasi')
                ));
            }

            // Notify Asesor_2
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
                        route('asesor.akreditasi')
                    ));
                }
            }

            // Notify all Admins
            $admins = User::where('role_id', 1)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AkreditasiNotification(
                    'visitasi_selesai_admin',
                    $title,
                    $message,
                    route('admin.akreditasi')
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiWorkflowService: Failed to notify visitasi selesai', [
                'akreditasi_id' => $akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify Pesantren, Asesor_1, and Asesor_2 after berkas approval.
     * Dispatched after the DB transaction commits (non-blocking).
     */
    private function notifyApproveBerkas(Akreditasi $akreditasi, Asesor $asesor1, Asesor $asesor2): void
    {
        try {
            // Notify Pesantren
            $pesantrenUser = User::find($akreditasi->user_id);
            if ($pesantrenUser) {
                $pesantrenUser->notify(new AkreditasiNotification(
                    'berkas_approved',
                    'Berkas Akreditasi Disetujui',
                    'Berkas akreditasi Anda telah diverifikasi dan masuk tahap Review Asesor.',
                    route('pesantren.akreditasi')
                ));
            }

            // Notify Asesor_1
            $asesor1User = User::find($asesor1->user_id);
            if ($asesor1User) {
                $pesantrenName = $pesantrenUser?->pesantren?->nama_pesantren ?? $pesantrenUser?->name ?? 'Pesantren';
                $asesor1User->notify(new AkreditasiNotification(
                    'tugas_baru',
                    'Penugasan Review Asesor Baru',
                    "Anda ditugaskan sebagai Ketua Kelompok untuk pesantren {$pesantrenName}.",
                    route('asesor.akreditasi')
                ));
            }

            // Notify Asesor_2
            $asesor2User = User::find($asesor2->user_id);
            if ($asesor2User) {
                $pesantrenName = $pesantrenUser?->pesantren?->nama_pesantren ?? $pesantrenUser?->name ?? 'Pesantren';
                $asesor2User->notify(new AkreditasiNotification(
                    'tugas_baru',
                    'Penugasan Review Asesor Baru',
                    "Anda ditugaskan sebagai Anggota Kelompok untuk pesantren {$pesantrenName}.",
                    route('asesor.akreditasi')
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiWorkflowService: Failed to notify after berkas approval', [
                'akreditasi_id' => $akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Task 11.3: Notify Admin after assessor scoring finalized
    // =========================================================================

    /**
     * Notify all Admin users after assessor scoring is finalized (2 → 1).
     */
    private function notifyAdminsScoringFinalized(Akreditasi $akreditasi): void
    {
        try {
            $admins = User::where('role_id', 1)->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AkreditasiNotification(
                    'scoring_finalized',
                    'Penilaian Asesor Selesai',
                    'Semua penilaian asesor telah difinalisasi. Akreditasi siap untuk validasi admin.',
                    route('admin.akreditasi')
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiWorkflowService: Failed to notify admins of scoring finalized', [
                'akreditasi_id' => $akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // Task 11.5: Notify Pesantren after SK issued
    // =========================================================================

    /**
     * Notify Pesantren after SK is issued (1 → 0).
     */
    private function notifyPesantrenSKIssued(
        Akreditasi $akreditasi,
        float $nilaiAkhir,
        string $peringkat,
        string $nomorSk
    ): void {
        try {
            $pesantrenUser = User::find($akreditasi->user_id);
            if ($pesantrenUser) {
                $pesantrenUser->notify(new AkreditasiNotification(
                    'sk_issued',
                    'SK Akreditasi Diterbitkan',
                    "Akreditasi Anda telah selesai. Nilai Akhir: {$nilaiAkhir}, Peringkat: {$peringkat}, Nomor SK: {$nomorSk}.",
                    route('pesantren.akreditasi')
                ));
            }
        } catch (\Throwable $e) {
            Log::error('AkreditasiWorkflowService: Failed to notify pesantren of SK issued', [
                'akreditasi_id' => $akreditasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
