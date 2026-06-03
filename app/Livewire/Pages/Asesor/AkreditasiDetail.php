<?php

namespace App\Livewire\Pages\Asesor;

use App\Exceptions\ImmutableValueException;
use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\User;
use App\Services\AkreditasiDocumentService;
use App\Services\AkreditasiWorkflowService;
use App\Services\AsesorService;
use App\Services\AssessorScoringService;
use App\Services\ProgressTracker;
use App\Services\RejectionService;
use App\Services\ScoreCalculationService;
use App\StateMachine\AkreditasiStateMachine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class AkreditasiDetail extends Component
{
    use WithFileUploads;

    public $akreditasi;

    public $pesantren;

    public $ipm;

    public $sdm;

    public $levels = [];

    public $fields = [
        'santri_l',
        'santri_p',
        'ustadz_dirosah_l',
        'ustadz_dirosah_p',
        'ustadz_non_dirosah_l',
        'ustadz_non_dirosah_p',
        'pamong_l',
        'pamong_p',
        'musyrif_l',
        'musyrif_p',
        'tendik_l',
        'tendik_p',
    ];

    public $komponens;

    // Pesantren's EDPM data (read only)
    public $pesantrenEvaluasis = [];

    public $pesantrenCatatans = [];

    public $pesantrenLinks = [];

    // Assessor's EDPM evaluation (editable)
    public $asesorEvaluasis = [];

    public $asesorCatatans = [];

    public $asesorNks = [];

    public $asesorCatatanNks = [];

    public $asesorButirCatatans = [];

    public $visitasiTemplate;

    // Values from the other assessor (for preview)
    public $otherAsesorEvaluasis = [];

    public $otherAsesorCatatans = [];

    public $otherAsesorButirCatatans = [];

    public $asesorTipe;

    // Progress tracking
    public $asesor1NaProgress = null;

    public $asesor1NkProgress = null;

    public $asesor2NaProgress = null;

    public bool $nilaiKetuaFinalComplete = false;

    public bool $nilaiAnggotaFinalComplete = false;

    public bool $nilaiKelompokUnlocked = false;

    #[Url]
    public $activeTab = 'profil';

    public $isLocked = false;

    // Rejection form properties
    public $rejectedItems = [];

    // Concurrent access handling
    public string $akreditasiUpdatedAt = '';

    public $rejectionExplanation = '';

    // Schedule visitasi form properties
    public $tanggalMulai = '';

    public $tanggalAkhir = '';

    public $catatanVisitasi = '';

    public $selectableItems = [];

    public $rejectionStatus = [];

    // Overall Accreditation Scores

    public $asesorFinalStatus = [];

    public $laporan_individu_file;

    public $laporan_kelompok_file;

    public function mount($uuid)
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->isAsesor()) {
            abort(403);
        }

        $asesorService = app(AsesorService::class);
        $data = $asesorService->getAkreditasiDetailAsesor($uuid, $user->id);

        if (empty($data)) {
            abort(404);
        }

        $this->akreditasi = $data['akreditasi'];
        $this->asesorTipe = $data['asesorTipe'];
        $this->pesantren = $data['pesantren'];
        $this->ipm = $data['ipm'];
        $this->sdm = $data['sdm'];
        $this->komponens = $data['komponens'];
        $this->visitasiTemplate = $data['visitasiTemplate'];

        // Tenant boundary: only assigned asesor / owner pesantren / admin can view
        Gate::authorize('view', $this->akreditasi);

        if ($this->pesantren && $this->pesantren->relationLoaded('units')) {
            $this->levels = $this->pesantren->units->pluck('unit')->toArray();
        }

        // Security check: scoring and report tabs only open after the related workflow phase.
        if (($this->akreditasi->status == AkreditasiStateMachine::STATUS_ASSESSMENT
            || $this->akreditasi->status == AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS)
            && $this->activeTab === 'laporan_visitasi') {
            $this->activeTab = 'profil';
        }

        if (! in_array((int) $this->akreditasi->status, [
            AkreditasiStateMachine::STATUS_PASCA_VISITASI,
            AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
            AkreditasiStateMachine::STATUS_SELESAI,
        ], true) && $this->activeTab === 'instrumen') {
            $this->activeTab = 'profil';
        }

        // Pesantren EDPM
        $this->pesantrenEvaluasis = $data['pesantren_edpm']['evaluasis'];
        $this->pesantrenLinks = $data['pesantren_edpm']['links'];
        $this->pesantrenCatatans = $data['pesantren_edpm']['catatans'];

        // Assessor EDPM Data
        $this->asesorEvaluasis = $data['evaluation']['asesorEvaluasis'];
        $this->asesorNks = $data['evaluation']['asesorNks'];
        $this->asesorButirCatatans = $data['evaluation']['asesorButirCatatans'];
        $this->asesorCatatans = $data['evaluation']['asesorCatatans'];
        $this->asesorCatatanNks = $data['evaluation']['asesorCatatanNks'];
        $this->otherAsesorEvaluasis = $data['evaluation']['otherAsesorEvaluasis'];
        $this->otherAsesorButirCatatans = $data['evaluation']['otherAsesorButirCatatans'];
        $this->otherAsesorCatatans = $data['evaluation']['otherAsesorCatatans'];

        if ($this->asesorTipe == 1 && ! empty($this->asesorEvaluasis)) {
            $this->isLocked = true;
        }

        foreach ($this->komponens as $komponen) {
            if (! isset($this->pesantrenCatatans[$komponen->id])) {
                $this->pesantrenCatatans[$komponen->id] = '-';
            }
        }

        // Load rejection data for Asesor 1
        if ($this->asesorTipe == 1) {
            $rejectionService = app(RejectionService::class);
            $this->rejectionStatus = $rejectionService->getRejectionStatus($this->akreditasi->id);
            $this->selectableItems = $rejectionService->getSelectableItems($this->akreditasi->id);
        }

        // Load scoring progress only after visitasi has been confirmed selesai.
        if ($this->akreditasi->status == AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            $progress = $data['progress'] ?? [];
            $this->asesor1NaProgress = $progress['asesor1_na'] ?? null;
            $this->asesor1NkProgress = $progress['asesor1_nk'] ?? null;
            $this->asesor2NaProgress = $progress['asesor2_na'] ?? null;
        }
        $this->syncScoringGateState();

        // Concurrent access: store updated_at for optimistic locking
        $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();

        // Load final status for each butir
        $asesorId = $this->akreditasi->{'assessment'.$this->asesorTipe}?->asesor_id ?? null;
        if ($asesorId) {
            $finalRecords = AkreditasiEdpm::where('akreditasi_id', $this->akreditasi->id)
                ->where('asesor_id', $asesorId)
                ->where('is_final', true)
                ->pluck('is_final', 'butir_id');
            $this->asesorFinalStatus = $finalRecords->toArray();
        }
    }

    /**
     * Poll for status changes (called by wire:poll).
     */
    public function checkForUpdates(): void
    {
        $fresh = Akreditasi::find($this->akreditasi->id);
        if (! $fresh) {
            return;
        }

        $freshUpdatedAt = $fresh->updated_at->toISOString();

        if ($freshUpdatedAt !== $this->akreditasiUpdatedAt) {
            $oldStatus = $this->akreditasi->status;
            $this->akreditasi = $fresh;
            $this->akreditasiUpdatedAt = $freshUpdatedAt;

            if ($oldStatus !== $fresh->status) {
                $this->dispatch('notification-received',
                    type: 'warning',
                    title: 'Status Diperbarui',
                    message: 'Status akreditasi telah diperbarui oleh pengguna lain. Status saat ini: '
                        .Akreditasi::getStatusLabel($fresh->status)
                );
            }
        }
    }

    protected function messages()
    {
        return [
            'asesorEvaluasis.*.required' => 'Nilai NA wajib diisi.',
            'asesorEvaluasis.*.integer' => 'Nilai NA harus berupa angka.',
            'asesorEvaluasis.*.between' => 'Nilai NA harus antara 1 sampai 4.',
            'asesorNks.*.required' => 'Nilai NK wajib diisi.',
            'asesorNks.*.integer' => 'Nilai NK harus berupa angka.',
            'asesorNks.*.between' => 'Nilai NK harus antara 1 sampai 4.',
        ];
    }

    protected function validationAttributes()
    {
        $attributes = [];
        foreach ($this->komponens as $k) {
            foreach ($k->butirs as $b) {
                $attributes["asesorEvaluasis.{$b->id}"] = "Nilai NA Butir {$b->nomor_butir}";
                $attributes["asesorNks.{$b->id}"] = "Nilai NK Butir {$b->nomor_butir}";
            }
        }

        return $attributes;
    }

    public function saveAsesorEdpm($isFinal = false)
    {
        Gate::authorize('update', $this->akreditasi);

        if ($this->akreditasi->status != AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Nilai asesor hanya dapat diisi setelah visitasi dikonfirmasi selesai.');

            return;
        }

        $rules = [
            'asesorEvaluasis.*' => ($isFinal ? 'required' : 'nullable').'|integer|between:1,4',
            'asesorCatatans.*' => 'nullable|string',
            'asesorButirCatatans.*' => 'nullable|string',
        ];

        // Custom validation for completeness
        $missingItems = [];
        foreach ($this->komponens as $komponen) {
            foreach ($komponen->butirs as $butir) {
                if ($isFinal && empty($this->asesorEvaluasis[$butir->id])) {
                    $missingItems[] = "<li><b>NA {$this->asesorTipe}</b>: Butir {$butir->nomor_butir} ({$komponen->nama})</li>";
                }

                if ($this->asesorTipe == 1) {
                    if ($isFinal && empty($this->otherAsesorEvaluasis[$butir->id])) {
                        $this->dispatch('validation-failed', title: 'Validasi Gagal', html: "Asesor 2 belum menyelesaikan penilaian (Butir {$butir->nomor_butir} masih kosong).");

                        return false;
                    }

                    $hasAllNa = ! empty($this->asesorEvaluasis[$butir->id]) && ! empty($this->otherAsesorEvaluasis[$butir->id]);
                    if (($isFinal || $hasAllNa) && empty($this->asesorNks[$butir->id])) {
                        $missingItems[] = "<li><b>NK</b>: Butir {$butir->nomor_butir} ({$komponen->nama})</li>";
                    }
                }
            }
        }

        if ($isFinal && ! empty($missingItems)) {
            $htmlList = '<ul class="text-left list-disc pl-5 mt-2 space-y-1 text-[11px]">'.implode('', array_unique($missingItems)).'</ul>';
            $this->dispatch('validation-failed', title: 'Data Belum Lengkap', html: 'Mohon lengkapi seluruh penilaian sebelum menyelesaikan:<br>'.$htmlList);

            return false;
        }

        $this->validate($rules);

        $asesorService = app(AsesorService::class);
        $asesorId = Auth::user()->asesor->id;

        try {
            $saved = $asesorService->saveAsesorEdpm($this->akreditasi->id, $asesorId, $this->asesorTipe, $this->akreditasi->user_id, [
                'asesorEvaluasis' => $this->asesorEvaluasis,
                'asesorButirCatatans' => $this->asesorButirCatatans,
                'asesorNks' => $this->asesorNks,
                'asesorCatatans' => $this->asesorCatatans,
                'asesorCatatanNks' => $this->asesorCatatanNks,
            ]);
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Nilai Kelompok Terkunci', message: $e->getMessage());

            return false;
        }

        if ($saved) {
            if ($this->asesorTipe == 1) {
                $this->isLocked = true;
            }
            $this->refreshScoringProgress();
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Instrumen Akreditasi berhasil disimpan.');

            return true;
        }

        return false;
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function getTotal($field)
    {
        $total = 0;
        foreach ($this->levels as $level) {
            $total += (int) ($this->sdm[$level]->$field ?? 0);
        }

        return $total;
    }

    public function submitRejection()
    {
        Gate::authorize('update', $this->akreditasi);

        if ($this->asesorTipe != 1) {
            abort(403);
        }

        $this->validate([
            'rejectedItems' => 'required|array|min:1',
            'rejectionExplanation' => 'required|string|min:10|max:2000',
        ], [
            'rejectedItems.required' => 'Pilih minimal satu item yang ditolak.',
            'rejectedItems.min' => 'Pilih minimal satu item yang ditolak.',
            'rejectionExplanation.required' => 'Catatan penolakan wajib diisi.',
            'rejectionExplanation.min' => 'Catatan penolakan minimal 10 karakter.',
        ]);

        try {
            $workflowService = app(AkreditasiWorkflowService::class);
            $workflowService->createDocumentRejection(
                $this->akreditasi->id,
                Auth::id(),
                $this->rejectedItems,
                $this->rejectionExplanation
            );

            $this->reset(['rejectedItems', 'rejectionExplanation']);
            // Reload rejection status
            $rejectionService = app(RejectionService::class);
            $this->rejectionStatus = $rejectionService->getRejectionStatus($this->akreditasi->id);
            $this->akreditasi->refresh();

            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Penolakan berhasil dikirim.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function acceptPerbaikan()
    {
        Gate::authorize('update', $this->akreditasi);

        if ($this->asesorTipe != 1) {
            abort(403);
        }

        $rejectionService = app(RejectionService::class);
        $result = $rejectionService->acceptPerbaikan($this->akreditasi->id, Auth::id());

        if ($result['success']) {
            $this->rejectionStatus = $rejectionService->getRejectionStatus($this->akreditasi->id);
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Perbaikan diterima. Proses visitasi dapat dilanjutkan.');
        } else {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Gagal menerima perbaikan.');
        }
    }

    public function rejectAgain()
    {
        // Reset form and let asesor fill in new rejection
        $this->reset(['rejectedItems', 'rejectionExplanation']);
        $this->activeTab = 'profil';
        $this->dispatch('notification-received', type: 'info', title: 'Info', message: 'Silakan isi form penolakan baru di bagian bawah halaman.');
    }

    public function scheduleVisitasi(): void
    {
        Gate::authorize('update', $this->akreditasi);

        if ($this->asesorTipe != 1) {
            abort(403);
        }

        $this->validate([
            'tanggalMulai' => 'required|date|after_or_equal:today',
            'tanggalAkhir' => 'required|date|after_or_equal:tanggalMulai',
            'catatanVisitasi' => 'nullable|string|max:1000',
        ], [
            'tanggalMulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggalMulai.after_or_equal' => 'Tanggal mulai minimal hari ini.',
            'tanggalAkhir.required' => 'Tanggal akhir wajib diisi.',
            'tanggalAkhir.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
            'catatanVisitasi.max' => 'Catatan visitasi tidak boleh melebihi 1000 karakter.',
        ]);

        try {
            $workflowService = app(AkreditasiWorkflowService::class);
            $workflowService->scheduleVisitasi(
                $this->akreditasi->id,
                Auth::id(),
                [
                    'tanggal_mulai' => $this->tanggalMulai,
                    'tanggal_akhir' => $this->tanggalAkhir,
                    'catatan_visitasi' => $this->catatanVisitasi ?: '',
                ]
            );
            $this->akreditasi->refresh();
            $this->dispatch('close-modal', 'asesor-schedule-visitasi-modal');
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Visitasi berhasil dijadwalkan.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function confirmVisitasiSelesai(): void
    {
        try {
            $workflowService = app(AkreditasiWorkflowService::class);
            $workflowService->confirmVisitasiSelesai($this->akreditasi->id, Auth::id());
            $this->akreditasi->refresh();
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Visitasi dikonfirmasi selesai. Tahap penilaian pasca visitasi dimulai.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function finalizeScoring(): void
    {
        try {
            $workflowService = app(AkreditasiWorkflowService::class);
            $workflowService->finalizeAssessorScoring($this->akreditasi->id, Auth::id());
            $this->akreditasi->refresh();
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Penilaian difinalisasi. Akreditasi masuk tahap Validasi Admin.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function saveNaValue(int $butirId, int $value, bool $isFinal): void
    {
        try {
            $scoringService = app(AssessorScoringService::class);
            $scoringService->saveNA($this->akreditasi->id, Auth::id(), $butirId, $value, $isFinal);
            $this->asesorEvaluasis[$butirId] = $value;
            $this->refreshScoringProgress();
            if ($isFinal) {
                $this->asesorFinalStatus[$butirId] = true;
                $this->dispatch('notification-received', type: 'success', title: 'Final!', message: "Nilai butir #{$butirId} dikunci sebagai Final.");
            }
        } catch (ImmutableValueException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Nilai sudah Final dan tidak dapat diubah.');
        } catch (\Throwable $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function saveNkValue(int $butirId, int $value, bool $isFinal): void
    {
        try {
            $scoringService = app(AssessorScoringService::class);
            // Get asesor2 user_id from assessment
            $asesor2UserId = $this->akreditasi->assessment2?->asesor?->user_id ?? 0;
            $scoringService->saveNK($this->akreditasi->id, Auth::id(), $asesor2UserId, $butirId, $value, $isFinal);
            $this->asesorNks[$butirId] = $value;
            $this->refreshScoringProgress();
            if ($isFinal) {
                $this->dispatch('notification-received', type: 'success', title: 'Final!', message: "NK butir #{$butirId} dikunci sebagai Final.");
            }
        } catch (ImmutableValueException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Nilai sudah Final dan tidak dapat diubah.');
        } catch (\Throwable $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function uploadLaporanIndividu(): void
    {
        if ((int) $this->akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            return;
        }
        $this->validate(['laporan_individu_file' => 'required|file|mimes:pdf,docx|max:5120']);
        try {
            $docService = app(AkreditasiDocumentService::class);
            $docService->uploadLaporanIndividuForAsesor($this->akreditasi->id, Auth::id(), $this->laporan_individu_file);
            $this->reset(['laporan_individu_file']);
            $this->akreditasi->refresh();
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Laporan individu berhasil diunggah.');
        } catch (\Throwable $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Upload gagal: '.$e->getMessage());
        }
    }

    public function uploadLaporanKelompok(): void
    {
        if ($this->asesorTipe !== 1) {
            return;
        }
        if ((int) $this->akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            return;
        }
        $this->validate(['laporan_kelompok_file' => 'required|file|mimes:pdf,docx|max:5120']);
        try {
            $docService = app(AkreditasiDocumentService::class);
            $docService->uploadLaporanKelompokForAsesor1($this->akreditasi->id, Auth::id(), $this->laporan_kelompok_file);
            $this->reset(['laporan_kelompok_file']);
            $this->akreditasi->refresh();
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Laporan kelompok berhasil diunggah.');
        } catch (\Throwable $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Upload gagal: '.$e->getMessage());
        }
    }

    private function refreshScoringProgress(): void
    {
        $progress = app(ProgressTracker::class)->getAkreditasiProgress($this->akreditasi->id);
        $this->asesor1NaProgress = $progress['asesor1_na'] ?? null;
        $this->asesor1NkProgress = $progress['asesor1_nk'] ?? null;
        $this->asesor2NaProgress = $progress['asesor2_na'] ?? null;
        $this->syncScoringGateState();
    }

    private function syncScoringGateState(): void
    {
        $this->nilaiKetuaFinalComplete = false;
        $this->nilaiAnggotaFinalComplete = false;
        $this->nilaiKelompokUnlocked = false;

        if (! $this->akreditasi) {
            return;
        }

        $ketuaUserId = $this->akreditasi->assessment1?->asesor?->user_id;
        $anggotaUserId = $this->akreditasi->assessment2?->asesor?->user_id;

        if (! $ketuaUserId || ! $anggotaUserId) {
            return;
        }

        $scoringService = app(AssessorScoringService::class);
        $this->nilaiKetuaFinalComplete = $scoringService->allNA1Final($this->akreditasi->id, $ketuaUserId);
        $this->nilaiAnggotaFinalComplete = $scoringService->allNA2Final($this->akreditasi->id, $anggotaUserId);
        $this->nilaiKelompokUnlocked = $this->nilaiKetuaFinalComplete && $this->nilaiAnggotaFinalComplete;
    }

    public function asesorScoringProgressCards(): array
    {
        if ((int) $this->akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI
            || (! $this->asesor1NaProgress && ! $this->asesor2NaProgress)) {
            return [];
        }

        $cards = $this->asesorTipe == 1
            ? [
                $this->scoringProgressCard($this->asesor1NaProgress, 'Nilai Ketua'),
                $this->scoringProgressCard($this->asesor1NkProgress, 'Nilai Kelompok'),
                $this->scoringProgressCard($this->asesor2NaProgress, 'Nilai Anggota'),
            ]
            : [
                $this->scoringProgressCard($this->asesor2NaProgress, 'Nilai Anggota', 'col-lg-6'),
                $this->scoringProgressCard($this->asesor1NaProgress, 'Nilai Ketua', 'col-lg-6'),
            ];

        return array_values(array_filter($cards));
    }

    public function isAsesorNaFinal(int $butirId): bool
    {
        return isset($this->asesorFinalStatus[$butirId]) && (bool) $this->asesorFinalStatus[$butirId];
    }

    public function asesorDeltaValue(int $butirId): ?int
    {
        $na1Value = $this->asesorEvaluasis[$butirId] ?? null;
        $na2Value = $this->otherAsesorEvaluasis[$butirId] ?? null;

        if (! is_numeric($na1Value) || ! is_numeric($na2Value)) {
            return null;
        }

        return app(ScoreCalculationService::class)->calculateDelta((int) $na1Value, (int) $na2Value);
    }

    public function asesorDeltaVariant(int $butirId): string
    {
        return $this->asesorDeltaValue($butirId) === 0 ? 'success' : 'warning';
    }

    public function canConfirmVisitasi(): bool
    {
        if ((int) $this->akreditasi->status !== AkreditasiStateMachine::STATUS_VISITASI || $this->asesorTipe != 1) {
            return false;
        }

        return $this->akreditasi->tgl_visitasi
            && Carbon::today()->gte(Carbon::parse($this->akreditasi->tgl_visitasi)->startOfDay());
    }

    private function scoringProgressCard(?array $progress, string $label, string $column = 'col-lg-4'): ?array
    {
        if (empty($progress)) {
            return null;
        }

        return [
            'progress' => $progress,
            'label' => $label,
            'column' => $column,
            'color' => app(ProgressTracker::class)->getColorClass((float) ($progress['percentage'] ?? 0)),
        ];
    }

    public function render()
    {
        return view('livewire.pages.asesor.akreditasi-detail');
    }
}
