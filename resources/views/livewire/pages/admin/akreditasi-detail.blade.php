<?php

use App\Models\Akreditasi;
use App\Models\Edpm;
use App\Models\Pesantren;
use App\Services\DeadlineService;
use App\Services\ResubmissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public $akreditasi;

    public $pesantren;

    public $ipm;

    public $sdm;

    public $komponens;

    // Pesantren's EDPM data (read only)
    public $pesantrenEvaluasis = [];

    public $pesantrenCatatans = [];

    public $pesantrenLinks = [];

    // Assessor 1 EDPM evaluation
    public $asesor1Evaluasis = [];

    public $asesor1Catatans = [];

    public $asesor1Nks = [];

    public $asesor1CatatanNks = [];

    public $asesor1ButirCatatans = [];

    // Assessor 2 EDPM evaluation
    public $asesor2Evaluasis = [];

    public $asesor2Catatans = [];

    public $asesor2ButirCatatans = [];

    public $tgl_visitasi;

    public $tgl_visitasi_akhir;

    public $nomor_sk;

    public $sertifikat_file;

    public $masa_berlaku;

    public $masa_berlaku_akhir;

    public $catatan_admin;

    // Admin NV (Nilai Verifikasi)
    public $adminNvs = [];

    // Resubmission chain data
    public $chainTimeline = [];

    public $resubmissionStatus = null;

    // Rejection data
    public $rejectionCategories = [];

    public $rejectionStatus = [];

    // Progress tracking (status 5 only)
    public $asesor1NaProgress = null;
    public $asesor1NkProgress = null;
    public $asesor2NaProgress = null;

    public $activeTab = 'profil';

    // Reassignment
    public $reassignAsesorId = '';
    public $isOverdue = false;
    public $availableAsesorsForReassignment = [];

    // New workflow properties
    public $asesor1Id = '';
    public $asesor2Id = '';
    public $berkasRejectionSections = [];
    public $berkasRejectionCatatan = '';

    // Concurrent access handling
    public $akreditasiUpdatedAt = '';

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

    public function mount($uuid)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->canAccessAdminArea()) {
            abort(403);
        }

        $akreditasiService = app(\App\Services\AkreditasiService::class);
        $pesantrenService = app(\App\Services\PesantrenService::class);

        $this->akreditasi = $akreditasiService->findAkreditasi($uuid, ['user.pesantren', 'assessments.asesor.user', 'assessment1', 'assessment2']);

        if (! $this->akreditasi) {
            abort(404);
        }

        // Tenant boundary: admin / super admin only (super admin via Gate::before)
        Gate::authorize('view', $this->akreditasi);

        $userId = $this->akreditasi->user_id;
        $this->pesantren = $pesantrenService->getProfile($userId);
        $this->ipm = $pesantrenService->getIpm($userId);
        $this->sdm = $pesantrenService->getSdm($userId)->keyBy('tingkat');
        if ($this->pesantren && $this->pesantren->relationLoaded('units')) {
            $this->levels = $this->pesantren->units->pluck('unit')->toArray();
        }

        $pEdpmData = $pesantrenService->getEdpmData($userId);
        $this->komponens = $pEdpmData['komponens'];

        // Load Pesantren EDPM
        $pEvaluasis = $pEdpmData['existingEdpms']->pluck('isian', 'butir_id');
        $pLinks = $pEdpmData['existingEdpms']->pluck('link', 'butir_id');
        $pCatatans = $pEdpmData['existingCatatans'];

        // Load Assessor 1 EDPM
        $asesor1Id = $this->akreditasi->assessment1->asesor_id ?? null;
        if ($asesor1Id) {
            $a1Data = $akreditasiService->getAsesorEdpmData($this->akreditasi->id, $asesor1Id);
            $a1Evaluasis = $a1Data['evaluasis'];
            $a1Nks = $a1Data['nks'];
            $a1Nvs = $a1Data['nvs'];
            $a1ButirCatatans = $a1Data['butirCatatans'];
            $a1Catatans = $a1Data['catatans'];
            $a1CatatanNks = $a1Data['catatanNks'];
        }

        // Load Assessor 2 EDPM
        $asesor2Id = $this->akreditasi->assessment2->asesor_id ?? null;
        if ($asesor2Id) {
            $a2Data = $akreditasiService->getAsesorEdpmData($this->akreditasi->id, $asesor2Id);
            $a2Evaluasis = $a2Data['evaluasis'];
            $a2ButirCatatans = $a2Data['butirCatatans'];
            $a2Catatans = $a2Data['catatans'];
        }

        foreach ($this->komponens as $komponen) {
            $this->pesantrenCatatans[$komponen->id] = $pCatatans[$komponen->id] ?? '';
            $this->asesor1Catatans[$komponen->id] = $a1Catatans[$komponen->id] ?? '';
            $this->asesor2Catatans[$komponen->id] = $a2Catatans[$komponen->id] ?? '';

            foreach ($komponen->butirs as $butir) {
                $this->pesantrenEvaluasis[$butir->id] = $pEvaluasis[$butir->id] ?? '';
                $this->pesantrenLinks[$butir->id] = $pLinks[$butir->id] ?? null;
                $this->asesor1Evaluasis[$butir->id] = $a1Evaluasis[$butir->id] ?? '';
                $this->asesor1Nks[$butir->id] = $a1Nks[$butir->id] ?? '';
                $this->adminNvs[$butir->id] = $a1Nvs[$butir->id] ?? ($this->akreditasi->status == 1 ? ($a1Nks[$butir->id] ?? '') : '');
                $this->asesor1ButirCatatans[$butir->id] = $a1ButirCatatans[$butir->id] ?? '';
                $this->asesor2Evaluasis[$butir->id] = $a2Evaluasis[$butir->id] ?? '';
                $this->asesor2ButirCatatans[$butir->id] = $a2ButirCatatans[$butir->id] ?? '';
            }
            $this->asesor1CatatanNks[$komponen->id] = $a1CatatanNks[$komponen->id] ?? '';
        }

        $this->nomor_sk = $this->akreditasi->nomor_sk;
        $this->masa_berlaku = $this->akreditasi->masa_berlaku;
        $this->masa_berlaku_akhir = $this->akreditasi->masa_berlaku_akhir;
        $this->tgl_visitasi = $this->akreditasi->tgl_visitasi;
        $this->tgl_visitasi_akhir = $this->akreditasi->tgl_visitasi_akhir;

        // Load resubmission chain timeline data
        $resubmissionService = app(ResubmissionService::class);
        $hasChain = $this->akreditasi->parent !== null || Akreditasi::where('parent', $this->akreditasi->id)->exists();
        if ($hasChain) {
            $this->chainTimeline = $resubmissionService->getChainTimeline($this->akreditasi->id);
            $this->resubmissionStatus = $resubmissionService->getResubmissionStatus($this->akreditasi->id);
        }

        // Load rejection status data
        $rejectionService = app(\App\Services\RejectionService::class);
        $this->rejectionStatus = $rejectionService->getRejectionStatus($this->akreditasi->id);

        // Load scoring progress only after visitasi is confirmed selesai.
        if ($this->akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            $progressTracker = app(\App\Services\ProgressTracker::class);
            $progress = $progressTracker->getAkreditasiProgress($this->akreditasi->id);
            $this->asesor1NaProgress = $progress['asesor1_na'];
            $this->asesor1NkProgress = $progress['asesor1_nk'];
            $this->asesor2NaProgress = $progress['asesor2_na'];
        }

        // Check overdue status for reassignment feature
        $deadlineService = app(DeadlineService::class);
        $primaryAssessment = $this->akreditasi->assessments->firstWhere('tipe', 1)
            ?? $this->akreditasi->assessments->first();
        if ($primaryAssessment) {
            $this->isOverdue = $deadlineService->isOverdue($primaryAssessment);
            if ($this->isOverdue) {
                $this->availableAsesorsForReassignment = $deadlineService
                    ->getAvailableAsesorsForReassignment($primaryAssessment)
                    ->toArray();
            }
        }

        // Concurrent access: store updated_at for optimistic locking
        $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();
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
                        . Akreditasi::getStatusLabel($fresh->status)
                );
            }
        }
    }

    public function toggleLock()
    {
        Gate::authorize('pesantren.lock');

        if ($this->pesantren) {
            $prevLocked = $this->pesantren->is_locked;
            $this->pesantren->is_locked = ! $this->pesantren->is_locked;
            $this->pesantren->save();

            $status = $this->pesantren->is_locked ? 'terkunci' : 'terbuka';

            if ($prevLocked && ! $this->pesantren->is_locked) {
                // Notifikasi ke pesantren saat data dibuka kuncinya
                $this->akreditasi->user->notify(new \App\Notifications\AkreditasiNotification(
                    'buka_kunci',
                    'Akses Data Dibuka',
                    'Administrator telah membuka kunci data Anda. Anda sekarang dapat memperbarui profil dan dokumen.',
                    route('pesantren.profile')
                ));
            }

            $this->dispatch('notification-received', title: 'Berhasil', message: "Akses data pesantren berhasil diubah menjadi $status.");
        }
    }

    public function openVisitasiEditModal()
    {
        $this->tgl_visitasi = $this->akreditasi->tgl_visitasi;
        $this->tgl_visitasi_akhir = $this->akreditasi->tgl_visitasi_akhir ?? $this->akreditasi->tgl_visitasi;
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'visitasi-edit-modal');
    }

    public function saveVisitasiReschedule(): void
    {
        Gate::authorize('akreditasi.approve');

        $this->validate([
            'tgl_visitasi' => 'required|date',
            'tgl_visitasi_akhir' => 'required|date|after_or_equal:tgl_visitasi',
        ]);

        try {
            $asesor1UserId = $this->akreditasi->assessment1?->asesor?->user_id;
            if (!$asesor1UserId) {
                $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Ketua Kelompok tidak ditemukan.');
                return;
            }

            $workflowService = app(\App\Services\AkreditasiWorkflowService::class);
            $workflowService->rescheduleVisitasi($this->akreditasi->id, $asesor1UserId, [
                'tanggal_mulai' => $this->tgl_visitasi,
                'tanggal_akhir' => $this->tgl_visitasi_akhir,
                'catatan_visitasi' => $this->akreditasi->catatan_visitasi ?? '',
            ]);

            $this->dispatch('close-modal', 'visitasi-edit-modal');
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Jadwal Visitasi berhasil diperbarui.');
            $this->akreditasi->refresh();
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    protected function messages()
    {
        return [
            'adminNvs.*.required' => 'Nilai NV wajib diisi.',
            'adminNvs.*.integer' => 'Nilai NV harus berupa angka.',
            'adminNvs.*.between' => 'Nilai NV harus antara 1 sampai 4.',
        ];
    }

    protected function validationAttributes()
    {
        $attributes = [];
        foreach ($this->komponens as $k) {
            foreach ($k->butirs as $b) {
                $attributes["adminNvs.{$b->id}"] = "Nilai NV Butir {$b->nomor_butir}";
            }
        }

        return $attributes;
    }

    public function saveAdminNv(): void
    {
        Gate::authorize('akreditasi.approve');

        if ((int) $this->akreditasi->status !== 1) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Data tidak dapat diubah karena status bukan Validasi Admin.');
            return;
        }

        if (empty($this->akreditasi->laporan_visitasi_asesor1)) {
            $this->dispatch('notification-received', type: 'error', title: 'Data Belum Lengkap', message: 'Nilai NV belum dapat disimpan karena Laporan Visitasi Ketua Kelompok belum diunggah.');
            return;
        }

        try {
            $this->validate(['adminNvs.*' => 'required|integer|between:1,4']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $missingItems = [];
            foreach ($e->validator->errors()->messages() as $key => $messages) {
                if (preg_match('/adminNvs\.(\d+)/', $key, $matches)) {
                    $butirId = $matches[1];
                    foreach ($this->komponens as $komponen) {
                        $butir = $komponen->butirs->firstWhere('id', $butirId);
                        if ($butir) {
                            $missingItems[] = "<li><b>NV</b>: Butir {$butir->nomor_butir} ({$komponen->nama})</li>";
                            break;
                        }
                    }
                }
            }
            $htmlList = '<ul class="text-left list-disc pl-5 mt-2 space-y-1 text-[11px]">'.implode('', array_unique($missingItems)).'</ul>';
            $this->dispatch('validation-failed', title: 'Nilai NV Belum Lengkap', html: 'Mohon lengkapi nilai verifikasi berikut sebelum menyimpan:<br>'.$htmlList);
            throw $e;
        }

        $scoringService = app(\App\Services\AssessorScoringService::class);
        $adminId = Auth::id();
        $errors = [];
        foreach ($this->adminNvs as $butirId => $nvValue) {
            if (!empty($nvValue)) {
                try {
                    $scoringService->saveNV($this->akreditasi->id, $adminId, (int) $butirId, (int) $nvValue, false);
                } catch (\Throwable $e) {
                    $errors[] = "Butir #{$butirId}: " . $e->getMessage();
                }
            }
        }

        if (!empty($errors)) {
            $this->dispatch('notification-received', type: 'warning', title: 'Sebagian Gagal', message: implode('; ', array_slice($errors, 0, 3)));
            return;
        }

        $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Nilai Verifikasi berhasil disimpan.');
    }

    public function approve()
    {
        Gate::authorize('finalize', $this->akreditasi);

        if ((int) $this->akreditasi->status !== 1) {
            $this->dispatch('notification-received', type: 'warning', title: 'Tidak Dapat Diproses', message: 'Akreditasi tidak berada pada status Validasi Admin.');
            return;
        }

        $this->validate([
            'nomor_sk' => 'required|string|max:100',
            'sertifikat_file' => 'required|file|mimes:pdf|max:10240',
            'masa_berlaku' => 'required|date',
            'masa_berlaku_akhir' => 'required|date|after:masa_berlaku',
        ]);

        try {
            $sertifikatPath = $this->sertifikat_file->store('akreditasi/sertifikat', 'public');

            $workflowService = app(\App\Services\AkreditasiWorkflowService::class);
            $workflowService->issueSK($this->akreditasi->id, Auth::id(), [
                'nomor_sk' => $this->nomor_sk,
                'masa_berlaku' => $this->masa_berlaku,
                'masa_berlaku_akhir' => $this->masa_berlaku_akhir,
                'sertifikat_path' => $sertifikatPath,
                'catatan_rekomendasi_admin' => $this->catatan_admin ?? '',
            ]);

            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'SK Akreditasi berhasil diterbitkan.');
            return redirect()->route('admin.akreditasi');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        } catch (\App\Exceptions\StaleStateException $e) {
            $this->akreditasi->refresh();
            $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();
            $this->dispatch('notification-received', type: 'error', title: 'Konflik Terdeteksi', message: 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
        }
    }

    public function reject()
    {
        Gate::authorize('finalize', $this->akreditasi);

        if ((int) $this->akreditasi->status !== 1) {
            $this->dispatch('notification-received', type: 'warning', title: 'Tidak Dapat Diproses', message: 'Akreditasi tidak berada pada status Validasi Admin.');
            return;
        }

        $this->validate([
            'rejectionCategories' => 'required|array|min:1',
            'rejectionCategories.*.category' => 'required|string',
            'rejectionCategories.*.explanation' => 'required|string|min:10|max:2000',
        ], [
            'rejectionCategories.required' => 'Pilih minimal satu kategori penolakan.',
            'rejectionCategories.min' => 'Pilih minimal satu kategori penolakan.',
            'rejectionCategories.*.explanation.required' => 'Penjelasan wajib diisi untuk setiap kategori.',
            'rejectionCategories.*.explanation.min' => 'Penjelasan minimal 10 karakter.',
        ]);

        try {
            $reason = collect($this->rejectionCategories)
                ->map(fn($c) => ($c['category'] ?? '') . ': ' . ($c['explanation'] ?? ''))
                ->implode('; ');

            $workflowService = app(\App\Services\AkreditasiWorkflowService::class);
            $workflowService->rejectAtValidasi(
                $this->akreditasi->id,
                Auth::id(),
                $reason,
                $this->akreditasiUpdatedAt,
                $this->rejectionCategories
            );

            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Akreditasi telah ditolak.');
            return redirect()->route('admin.akreditasi');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        } catch (\App\Exceptions\StaleStateException $e) {
            $this->akreditasi->refresh();
            $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();
            $this->dispatch('notification-received', type: 'error', title: 'Konflik Terdeteksi', message: 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
        }
    }

    public function addRejectionCategory()
    {
        $this->rejectionCategories[] = ['category' => '', 'explanation' => ''];
    }

    public function removeRejectionCategory($index)
    {
        unset($this->rejectionCategories[$index]);
        $this->rejectionCategories = array_values($this->rejectionCategories);
    }

    public function getTotal($field)
    {
        $total = 0;
        foreach ($this->levels as $level) {
            $total += (int) ($this->sdm[$level]->$field ?? 0);
        }

        return $total;
    }

    private function checkScores()
    {
        // Let the service handle validation
        return true;
    }

    public function openForReview(): void
    {
        Gate::authorize('akreditasi.approve');
        try {
            $workflowService = app(\App\Services\AkreditasiWorkflowService::class);
            $workflowService->openForReview($this->akreditasi->id, Auth::id());
            $this->akreditasi->refresh();
            $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Pengajuan dibuka untuk verifikasi berkas.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function approveBerkas(): void
    {
        Gate::authorize('akreditasi.approve');
        $this->validate([
            'asesor1Id' => 'required|integer|exists:users,id',
            'asesor2Id' => 'required|integer|exists:users,id|different:asesor1Id',
        ], [
            'asesor1Id.required' => 'Ketua Kelompok wajib dipilih.',
            'asesor2Id.required' => 'Anggota Kelompok wajib dipilih.',
            'asesor2Id.different' => 'Ketua Kelompok dan Anggota Kelompok harus berbeda.',
        ]);
        try {
            $workflowService = app(\App\Services\AkreditasiWorkflowService::class);
            $workflowService->approveBerkas($this->akreditasi->id, Auth::id(), (int) $this->asesor1Id, (int) $this->asesor2Id);
            $this->akreditasi->refresh();
            $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();
            $this->dispatch('close-modal', 'approve-berkas-modal');
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Berkas disetujui. Asesor telah ditugaskan.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function rejectBerkas()
    {
        Gate::authorize('akreditasi.approve');
        $this->validate([
            'berkasRejectionSections' => 'required|array|min:1',
            'berkasRejectionCatatan' => 'required|string|min:10|max:2000',
        ], [
            'berkasRejectionSections.required' => 'Pilih minimal satu bagian yang ditolak.',
            'berkasRejectionSections.min' => 'Pilih minimal satu bagian yang ditolak.',
            'berkasRejectionCatatan.required' => 'Catatan penolakan wajib diisi.',
            'berkasRejectionCatatan.min' => 'Catatan minimal 10 karakter.',
        ]);
        try {
            $workflowService = app(\App\Services\AkreditasiWorkflowService::class);
            $workflowService->rejectBerkas($this->akreditasi->id, Auth::id(), [
                'sections' => $this->berkasRejectionSections,
                'catatan' => $this->berkasRejectionCatatan,
            ]);
            $this->dispatch('close-modal', 'reject-berkas-modal');
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Berkas ditolak.');
            return redirect()->route('admin.akreditasi');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function finalizeAllNv(): void
    {
        Gate::authorize('akreditasi.approve');

        if ((int) $this->akreditasi->status !== 1) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Finalisasi NV hanya dapat dilakukan saat status Validasi Admin.');
            return;
        }

        try {
            $scoringService = app(\App\Services\AssessorScoringService::class);
            $adminId = Auth::id();
            $finalizedCount = 0;
            foreach ($this->adminNvs as $butirId => $nvValue) {
                if (!empty($nvValue)) {
                    try {
                        $scoringService->saveNV($this->akreditasi->id, $adminId, (int) $butirId, (int) $nvValue, true);
                        $finalizedCount++;
                    } catch (\App\Exceptions\ImmutableValueException $e) {
                        // Already final, skip
                        $finalizedCount++;
                    } catch (\Throwable $e) {
                        // Log but continue
                    }
                }
            }

            // Set is_nv_final flag on akreditasi
            $this->akreditasi->update(['is_nv_final' => true]);
            $this->akreditasi->refresh();

            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: "Semua NV ({$finalizedCount} butir) berhasil difinalisasi dan dikunci.");
        } catch (\Throwable $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        }
    }

    public function openReassignModal(): void
    {
        $this->reassignAsesorId = '';
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'reassign-asesor-modal');
    }

    public function reassignAsesor(): void
    {
        Gate::authorize('asesor.assign');

        $this->validate([
            'reassignAsesorId' => 'required|integer|exists:asesors,id',
        ], [
            'reassignAsesorId.required' => 'Pilih asesor pengganti.',
            'reassignAsesorId.exists' => 'Asesor tidak ditemukan.',
        ]);

        $deadlineService = app(DeadlineService::class);

        $primaryAssessment = $this->akreditasi->assessments->firstWhere('tipe', 1)
            ?? $this->akreditasi->assessments->first();

        if (! $primaryAssessment) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Penugasan asesor tidak ditemukan.');
            $this->dispatch('close-modal', 'reassign-asesor-modal');
            return;
        }

        try {
            $deadlineService->reassignAsesor($primaryAssessment, (int) $this->reassignAsesorId);

            // Refresh akreditasi data
            $akreditasiService = app(\App\Services\AkreditasiService::class);
            $this->akreditasi = $akreditasiService->findAkreditasi(
                $this->akreditasi->uuid,
                ['user.pesantren', 'assessments.asesor.user', 'assessment1', 'assessment2']
            );

            // Refresh overdue status
            $primaryAssessment->refresh();
            $this->isOverdue = $deadlineService->isOverdue($primaryAssessment);
            $this->availableAsesorsForReassignment = $this->isOverdue
                ? $deadlineService->getAvailableAsesorsForReassignment($primaryAssessment)->toArray()
                : [];

            $this->dispatch('close-modal', 'reassign-asesor-modal');
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Asesor berhasil diganti. Deadline baru telah ditetapkan.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Gagal mengganti asesor: ' . $e->getMessage());
            $this->dispatch('close-modal', 'reassign-asesor-modal');
        }
    }
}; ?>

@php
    $statusVariant = match ((int) $akreditasi->status) {
        0 => 'success',
        -1, -2 => 'danger',
        1 => 'warning',
        2 => 'info',
        3, 4, 5, 6 => 'primary',
        default => 'secondary',
    };

    $canShowAdminScoring = in_array((int) $akreditasi->status, [
        \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI,
        \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
        \App\StateMachine\AkreditasiStateMachine::STATUS_SELESAI,
    ], true);

    $dokumenUtama = [
        'status_kepemilikan_tanah' => 'Status Kepemilikan Tanah',
        'sertifikat_nsp' => 'Sertifikat NSP',
        'rk_anggaran' => 'Rencana Kerja Anggaran',
        'silabus_rpp' => 'Silabus dan RPP',
        'peraturan_kepegawaian' => 'Peraturan Kepegawaian',
        'file_lk_iapm' => 'File LK Penilaian IAPM',
        'laporan_tahunan' => 'Laporan Tahunan',
    ];

    $dokumenSekunder = [
        'dok_profil' => 'Dokumen Profil',
        'dok_nsp' => 'Dokumen NSP',
        'dok_renstra' => 'Dokumen Renstra',
        'dok_rk_anggaran' => 'Dokumen RK Anggaran',
        'dok_kurikulum' => 'Dokumen Kurikulum',
        'dok_silabus_rpp' => 'Dokumen Silabus & RPP',
        'dok_kepengasuhan' => 'Dokumen Kepengasuhan',
        'dok_peraturan_kepegawaian' => 'Dokumen Peraturan Kepegawaian',
        'dok_sarpras' => 'Dokumen Sarpras',
        'dok_laporan_tahunan' => 'Dokumen Laporan Tahunan',
        'dok_sop' => 'Dokumen SOP',
    ];

    $ipmItems = [
        'nsp_file' => '1. Izin operasional Kementerian Agama (NSP)',
        'lulus_santri_file' => '2. Pernah meluluskan santri / memiliki santri kelas akhir',
        'kurikulum_file' => '3. Menyelenggarakan kurikulum Dirasah Islamiyah',
        'buku_ajar_file' => '4. Menggunakan buku ajar terbitan LP2 PPM',
    ];
@endphp

<x-slot name="header">{{ __('Detail Akreditasi') }}</x-slot>

<x-ui.page
    title="Detail Akreditasi"
    subtitle="{{ $pesantren?->nama_pesantren ?? $akreditasi->user->name }}"
    class="spm-detail-page"
    x-data="{ ...akreditasiManagement(), ...adminManagement() }"
    wire:poll.30s="checkForUpdates"
>
    <x-akreditasi.presence-indicator :akreditasi-id="$akreditasi->id" />
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$statusVariant">
            {{ Akreditasi::getStatusLabel($akreditasi->status) }}
        </x-ui.status-badge>

        @if($resubmissionStatus)
            <x-ui.badge variant="warning">
                Pengajuan Ulang: {{ $resubmissionStatus['count'] }}/{{ $resubmissionStatus['limit'] }}
            </x-ui.badge>
        @endif

        @if($isOverdue)
            <x-ui.badge variant="danger">
                <x-ui.icon name="warning-2" class="fs-6 me-1" />
                Terlambat
            </x-ui.badge>
        @endif

        @if(in_array($akreditasi->status, [\App\StateMachine\AkreditasiStateMachine::STATUS_ASSESSMENT, \App\StateMachine\AkreditasiStateMachine::STATUS_VISITASI]))
            <x-ui.button
                type="button"
                wire:click="openReassignModal"
                :variant="$isOverdue ? 'danger' : 'light'"
                :disabled="!$isOverdue"
                size="sm"
                data-testid="reassign-asesor-btn"
            >
                <x-ui.icon name="arrows-circle" class="fs-4 me-1" />
                Ganti Asesor
            </x-ui.button>
        @endif

        @if((int)$akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_PENGAJUAN)
            <x-ui.button type="button" wire:click="openForReview" variant="primary" size="sm">
                <x-ui.icon name="eye" class="fs-4 me-1" />
                Buka untuk Review
            </x-ui.button>
        @endif

        @if((int)$akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS)
            <x-ui.button type="button" @click="$dispatch('open-modal', 'approve-berkas-modal')" variant="success" size="sm">
                <x-ui.icon name="check-circle" class="fs-4 me-1" />
                Setujui Berkas
            </x-ui.button>
            <x-ui.button type="button" @click="$dispatch('open-modal', 'reject-berkas-modal')" variant="danger" size="sm">
                <x-ui.icon name="cross-circle" class="fs-4 me-1" />
                Tolak Berkas
            </x-ui.button>
        @endif

        <x-ui.button :href="route('admin.akreditasi')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    {{-- Flash messages handled by notification-received event --}}

    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status Pengajuan" value="{{ Akreditasi::getStatusLabel($akreditasi->status) }}" variant="{{ $statusVariant }}" icon="shield-tick" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Tim Penilai" value="{{ $akreditasi->assessments->count() }} Asesor" variant="info" icon="profile-user" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Jadwal Visitasi" value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Belum Dijadwalkan' }}" variant="success" icon="calendar" />
        </div>
    </div>

    <x-akreditasi.workflow-stepper
        :status="$akreditasi->status"
        title="Tahapan Akreditasi LP2M"
        subtitle="Pantau posisi pengajuan dari review awal, review asesor, visitasi, penilaian pasca visitasi, validasi admin, sampai hasil akhir."
        class="mb-6"
    />

    {{-- Rejection History and Admin Final Rejection Detail --}}
    @if(!empty($rejectionStatus) && ($rejectionStatus['count'] > 0 || $rejectionStatus['history']->count() > 0))
        <div class="mb-6">
            {{-- Admin Final Rejection Detail --}}
            @php
                $adminFinalRejection = $rejectionStatus['history']->where('type', 'admin_final')->first();
            @endphp
            @if($adminFinalRejection)
                <x-ui.section-card title="Detail Penolakan Final (Admin)" subtitle="Penolakan terstruktur oleh Admin pada tahap Validasi." class="mb-4">
                    <div class="p-6">
                        <div class="d-flex flex-column gap-4">
                            @foreach($adminFinalRejection->categories ?? [] as $entry)
                                <div class="spm-soft-panel">
                                    <div class="spm-detail-label">
                                        {{ config('akreditasi.final_rejection_categories.' . ($entry['category'] ?? ''), $entry['category'] ?? '-') }}
                                    </div>
                                    <div class="spm-detail-value spm-detail-value-muted">{{ $entry['explanation'] ?? '-' }}</div>
                                </div>
                            @endforeach
                            <div class="text-muted fs-8">
                                Ditolak pada: {{ $adminFinalRejection->created_at->format('d F Y H:i') }}
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>
            @endif

            {{-- Rejection History --}}
            @if($rejectionStatus['history']->count() > 0)
                <x-ui.section-card title="Riwayat Penolakan" subtitle="Catatan penolakan asesor dan admin untuk pengajuan ini.">
                    <div class="p-6">
                        <div class="d-flex flex-column gap-4">
                            @foreach($rejectionStatus['history'] as $rejection)
                                <div class="spm-soft-panel">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="fw-bold">
                                            @if($rejection->type === 'admin_final')
                                                Penolakan Final (Admin)
                                            @else
                                                Penolakan Asesor #{{ $rejection->rejection_number }}
                                            @endif
                                        </div>
                                        <x-ui.badge variant="{{ match($rejection->status) {
                                            'pending' => 'warning',
                                            'submitted' => 'info',
                                            'accepted' => 'success',
                                            'expired' => 'danger',
                                            'limit_reached' => 'danger',
                                            'final' => 'danger',
                                            default => 'secondary',
                                        } }}">
                                            {{ match($rejection->status) {
                                                'pending' => 'Menunggu Perbaikan',
                                                'submitted' => 'Perbaikan Dikirim',
                                                'accepted' => 'Diterima',
                                                'expired' => 'Kadaluarsa',
                                                'limit_reached' => 'Batas Tercapai',
                                                'final' => 'Final',
                                                default => $rejection->status,
                                            } }}
                                        </x-ui.badge>
                                    </div>
                                    @if($rejection->type === 'asesor' && $rejection->items)
                                        <div class="mb-2">
                                            <span class="text-muted fs-8">Item ditolak:</span>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @foreach($rejection->items as $item)
                                                    <x-ui.badge variant="light">{{ $item }}</x-ui.badge>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if($rejection->explanation)
                                        <div class="mb-2">
                                            <span class="text-muted fs-8">Catatan:</span>
                                            <div class="fs-7">{{ $rejection->explanation }}</div>
                                        </div>
                                    @endif
                                    @if($rejection->type === 'admin_final' && $rejection->categories)
                                        <div class="mb-2">
                                            <span class="text-muted fs-8">Kategori:</span>
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @foreach($rejection->categories as $cat)
                                                    <x-ui.badge variant="danger">
                                                        {{ config('akreditasi.final_rejection_categories.' . ($cat['category'] ?? ''), $cat['category'] ?? '-') }}
                                                    </x-ui.badge>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    <div class="d-flex gap-3 text-muted fs-8">
                                        <span>Tanggal: {{ $rejection->created_at->format('d M Y H:i') }}</span>
                                        @if($rejection->perbaikan_submitted_at)
                                            <span>Perbaikan dikirim: {{ $rejection->perbaikan_submitted_at->format('d M Y H:i') }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-ui.section-card>
            @endif
        </div>
    @endif

    <x-ui.card flush>
        <div class="px-6 pt-5">
            <x-ui.tabs>
                <x-ui.tab wire:click="setTab('profil')" :active="$activeTab === 'profil'">Profil</x-ui.tab>
                <x-ui.tab wire:click="setTab('ipm')" :active="$activeTab === 'ipm'">IPM</x-ui.tab>
                <x-ui.tab wire:click="setTab('sdm')" :active="$activeTab === 'sdm'">SDM</x-ui.tab>
                <x-ui.tab wire:click="setTab('edpm_pesantren')" :active="$activeTab === 'edpm_pesantren'">EDPM</x-ui.tab>
                <x-ui.tab wire:click="setTab('instrumen')" :active="$activeTab === 'instrumen'">Nilai</x-ui.tab>
                <x-ui.tab wire:click="setTab('laporan_visitasi')" :active="$activeTab === 'laporan_visitasi'">Laporan Visitasi</x-ui.tab>
                @if(count($chainTimeline) > 0)
                    <x-ui.tab wire:click="setTab('riwayat')" :active="$activeTab === 'riwayat'">Riwayat Pengajuan</x-ui.tab>
                @endif
                <x-ui.tab wire:click="setTab('audit_trail')" :active="$activeTab === 'audit_trail'">Audit Trail</x-ui.tab>
            </x-ui.tabs>
        </div>

        <div class="p-6">
            @if ($activeTab === 'profil')
                <div class="d-flex flex-column gap-6">
                    <x-ui.section-card title="Profil Pesantren" subtitle="Identitas pesantren dan status akses data.">
                        <x-slot:toolbar>
                            @if($pesantren)
                                <x-ui.button
                                    type="button"
                                    @click="confirmToggleLock($wire, {{ $pesantren?->is_locked ? 'true' : 'false' }})"
                                    wire:loading.attr="disabled"
                                    :variant="$pesantren?->is_locked ? 'warning' : 'light'"
                                    size="sm"
                                >
                                    <x-ui.icon name="shield-tick" class="fs-4 me-1" wire:loading.remove wire:target="toggleLock" />
                                    <span wire:loading.remove wire:target="toggleLock">
                                        {{ $pesantren?->is_locked ? 'Buka Kunci Data' : 'Kunci Data' }}
                                    </span>
                                    <span wire:loading wire:target="toggleLock">Memproses...</span>
                                </x-ui.button>
                            @endif
                        </x-slot:toolbar>

                        <div class="p-6">
                            <div class="row g-5">
                                <x-ui.detail-item label="Nama Pesantren" value="{{ $pesantren->nama_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="NSP" value="{{ $pesantren->ns_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="Alamat" span="2">
                                    <div class="spm-detail-block spm-detail-value-muted">{{ $pesantren->alamat ?? '-' }}</div>
                                </x-ui.detail-item>
                                <x-ui.detail-item label="Kota/Kabupaten" value="{{ $pesantren->kota_kabupaten ?? '-' }}" />
                                <x-ui.detail-item label="Provinsi" value="{{ $pesantren->provinsi ?? '-' }}" />
                                <x-ui.detail-item label="Nama Mudir" value="{{ $pesantren->nama_mudir ?? '-' }}" />
                                <x-ui.detail-item label="Tahun Pendirian" value="{{ $pesantren->tahun_pendirian ?? '-' }}" />
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Layanan & Fasilitas" subtitle="Unit layanan pendidikan dan luas sarana.">
                        <div class="p-6">
                            <div class="row g-6">
                                <div class="col-lg-7">
                                    @if($pesantren && $pesantren->units && $pesantren->units->count() > 0)
                                        <x-ui.simple-table dense>
                                            <thead>
                                                <tr>
                                                    <th class="ps-4">Unit</th>
                                                    <th class="text-end pe-4">Jumlah Rombel</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($pesantren->units as $unit)
                                                    <tr>
                                                        <td class="ps-4 text-uppercase fw-bold">{{ $unit->unit }}</td>
                                                        <td class="text-end pe-4">
                                                            <x-ui.badge variant="success">{{ $unit->jumlah_rombel }} Rombel</x-ui.badge>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </x-ui.simple-table>
                                    @else
                                        <x-ui.empty-state title="Belum Ada Unit" description="Data unit pendidikan belum diisi." />
                                    @endif
                                </div>

                                <div class="col-lg-5">
                                    <div class="d-flex flex-column gap-4">
                                        <x-ui.stat-card label="Total Luas Tanah" value="{{ $pesantren->luas_tanah ?? '-' }} m2" variant="success" icon="geolocation" />
                                        <x-ui.stat-card label="Total Luas Bangunan" value="{{ $pesantren->luas_bangunan ?? '-' }} m2" variant="info" icon="category" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Dokumen Pesantren" subtitle="Dokumen utama dan dokumen pendukung profil.">
                        <div class="p-6">
                            <div class="row g-5">
                                <div class="col-lg-6">
                                    <div class="spm-detail-label mb-3">Dokumen Utama</div>
                                    <div class="spm-document-list">
                                        @foreach($dokumenUtama as $field => $label)
                                            <x-ui.document-item :label="$label" :href="$pesantren && $pesantren->$field ? Storage::url($pesantren->$field) : null" />
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="spm-detail-label mb-3">Dokumen Sekunder</div>
                                    <div class="spm-document-list">
                                        @foreach($dokumenSekunder as $field => $label)
                                            <x-ui.document-item :label="$label" :href="$pesantren && $pesantren->$field ? Storage::url($pesantren->$field) : null" />
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Asesor & Visitasi" subtitle="Tim penilai dan jadwal penilaian.">
                        <div class="p-6">
                            <div class="row g-5">
                                @forelse ($akreditasi->assessments as $assessment)
                                    <x-ui.detail-item label="{{ $assessment->tipe == 1 ? 'Ketua' : 'Anggota' }}" value="{{ $assessment->asesor->user->name ?? '-' }}" />
                                @empty
                                    <div class="col-12">
                                        <x-ui.empty-state title="Belum Ada Asesor" description="Asesor belum ditugaskan untuk pengajuan ini." />
                                    </div>
                                @endforelse

                                @if ($akreditasi->assessments->isNotEmpty())
                                    @php $mainAssessment = $akreditasi->assessments->first(); @endphp
                                    <x-ui.detail-item label="Penilaian Mulai" value="{{ \Carbon\Carbon::parse($mainAssessment->tanggal_mulai)->format('d M Y') }}" />
                                    <x-ui.detail-item label="Penilaian Berakhir" value="{{ \Carbon\Carbon::parse($mainAssessment->tanggal_berakhir)->format('d M Y') }}" />
                                @endif

                                @if ($akreditasi->tgl_visitasi)
                                    <x-ui.detail-item label="Visitasi Mulai" value="{{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') }}" />
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-end justify-content-between gap-3">
                                            <div class="spm-detail-item">
                                                <div class="spm-detail-label">Visitasi Berakhir</div>
                                                <div class="spm-detail-value">
                                                    {{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi_akhir ?? $akreditasi->tgl_visitasi)->format('d M Y') }}
                                                </div>
                                            </div>

                                            @if(in_array($akreditasi->status, [0, -1]))
                                                <x-ui.status-badge variant="secondary">Reschedule Terkunci</x-ui.status-badge>
                                            @else
                                                <x-ui.button type="button" wire:click="openVisitasiEditModal" variant="light" size="sm">
                                                    Reschedule
                                                </x-ui.button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

            @if ($activeTab === 'ipm')
                <x-ui.section-card title="Indikator Pemenuhan Mutlak" subtitle="Status dokumen IPM dari pesantren.">
                    <div class="p-6">
                        <div class="spm-document-list">
                            @foreach ($ipmItems as $field => $label)
                                <x-ui.document-item :label="$label" :href="$ipm && $ipm->$field ? Storage::url($ipm->$field) : null" />
                            @endforeach
                        </div>
                    </div>
                </x-ui.section-card>
            @endif

            @if ($activeTab === 'riwayat' && count($chainTimeline) > 0)
                <x-ui.section-card title="Riwayat Pengajuan" subtitle="Timeline pengajuan akreditasi dalam rantai pengajuan ulang.">
                    <div class="p-6">
                        <x-ui.simple-table dense>
                            <thead>
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Status</th>
                                    <th>Alasan Penolakan</th>
                                    <th>Waktu Antar Pengajuan</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chainTimeline as $index => $entry)
                                    @php
                                        $entryStatusVariant = match ((int) $entry->status) {
                                            1 => 'success',
                                            2 => 'danger',
                                            3 => 'warning',
                                            4 => 'info',
                                            default => 'primary',
                                        };
                                        $timeElapsed = null;
                                        if ($index > 0) {
                                            $prevEntry = $chainTimeline[$index - 1];
                                            $timeElapsed = \Carbon\Carbon::parse($prevEntry->created_at)->diffForHumans(\Carbon\Carbon::parse($entry->created_at), true);
                                        }
                                    @endphp
                                    <tr class="{{ $entry->id === $akreditasi->id ? 'bg-light-primary' : '' }}">
                                        <td class="ps-4 fw-bold">{{ $index + 1 }}</td>
                                        <td>{{ \Carbon\Carbon::parse($entry->created_at)->format('d M Y H:i') }}</td>
                                        <td>
                                            <x-ui.badge :variant="$entryStatusVariant">
                                                {{ Akreditasi::getStatusLabel($entry->status) }}
                                            </x-ui.badge>
                                        </td>
                                        <td>{{ (int) $entry->status === -1 ? ($entry->catatan ?? '-') : '-' }}</td>
                                        <td>{{ $timeElapsed ?? '-' }}</td>
                                        <td class="text-end pe-4">
                                            <a href="{{ route('admin.akreditasi-detail', $entry->uuid) }}" class="btn btn-sm btn-light-primary">
                                                <x-ui.icon name="eye" class="fs-4" />
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.simple-table>
                    </div>
                </x-ui.section-card>
            @endif

            @if ($activeTab === 'sdm')
                <x-ui.section-card title="Data SDM Pesantren" subtitle="Rekap santri, ustadz, pamong, musyrif, dan tenaga kependidikan.">
                    <div class="p-6">
                        <x-ui.simple-table tableClass="spm-wide-table">
                            <thead>
                                <tr class="text-center">
                                    <th rowspan="2" class="ps-4">No.</th>
                                    <th rowspan="2" class="text-start">Bentuk</th>
                                    <th colspan="2">Santri</th>
                                    <th colspan="2">Ustadz Dirosah</th>
                                    <th colspan="2">Ustadz Non Dirosah</th>
                                    <th colspan="2">Pamong</th>
                                    <th colspan="2">Musyrif/Ah</th>
                                    <th colspan="2" class="pe-4">Tenaga Kependidikan</th>
                                </tr>
                                <tr class="text-center">
                                    @for($i = 0; $i < 6; $i++)
                                        <th>L</th>
                                        <th>P</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($levels as $index => $level)
                                    <tr class="text-center">
                                        <td class="ps-4 fw-bold">{{ $index + 1 }}</td>
                                        <td class="text-start text-uppercase fw-bold">{{ $level }}</td>
                                        @foreach($fields as $field)
                                            <td>{{ $sdm[$level]->$field ?? 0 }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="text-center">
                                    <td colspan="2" class="ps-4 text-uppercase text-start">Jumlah</td>
                                    @foreach($fields as $field)
                                        <td>{{ $this->getTotal($field) }}</td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </x-ui.simple-table>
                    </div>
                </x-ui.section-card>
            @endif

            @if ($activeTab === 'edpm_pesantren')
                <div class="d-flex flex-column gap-6">
                    <x-ui.section-card title="EDPM Pesantren" subtitle="Isian evaluasi diri dan bukti yang dikirim pesantren.">
                        <div class="p-6">
                            <x-ui.simple-table tableClass="spm-edpm-review-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4 w-100px">No Butir</th>
                                        <th>Pernyataan</th>
                                        <th class="text-center w-125px">Isian</th>
                                        <th class="text-center pe-4 w-150px">Bukti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponens as $komponen)
                                        @foreach ($komponen->butirs as $butir)
                                            <tr>
                                                <td class="ps-4 fw-bold text-primary">{{ $butir->nomor_butir }}</td>
                                                <td class="spm-edpm-statement">{{ $butir->butir_pernyataan }}</td>
                                                <td class="text-center">
                                                    <x-ui.badge variant="warning">{{ $pesantrenEvaluasis[$butir->id] }}</x-ui.badge>
                                                </td>
                                                <td class="text-center pe-4">
                                                    @if(!empty($pesantrenLinks[$butir->id]))
                                                        <x-ui.button :href="$pesantrenLinks[$butir->id]" target="_blank" variant="light" size="sm">Bukti</x-ui.button>
                                                    @else
                                                        <x-ui.status-badge variant="secondary">-</x-ui.status-badge>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Catatan Kinerja Satuan Pendidikan" subtitle="Catatan pesantren per komponen EDPM.">
                        <div class="p-6">
                            <div class="row g-5">
                                @foreach ($komponens as $komponen)
                                    <div class="col-lg-6">
                                        <div class="spm-soft-panel h-100">
                                            <div class="spm-detail-label">{{ $komponen->nama }}</div>
                                            <div class="spm-detail-value spm-detail-value-muted">
                                                {{ $pesantrenCatatans[$komponen->id] ?: 'Tidak ada catatan.' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

            @if ($activeTab === 'instrumen')
                <div class="d-flex flex-column gap-6">
                    @if(! $canShowAdminScoring)
                        <x-ui.alert variant="info" icon="information-2" title="Penilaian Belum Dibuka">
                            Alur yang benar: visitasi dilakukan lebih dulu, Ketua Kelompok mengonfirmasi visitasi selesai, lalu Nilai Ketua dan Nilai Anggota diisi pada tahap Penilaian Pasca Visitasi. Nilai Verifikasi Admin baru terbuka setelah Nilai Kelompok final.
                        </x-ui.alert>

                        <x-ui.section-card title="Posisi Proses Saat Ini" subtitle="Admin dapat memantau berkas, tim asesor, dan jadwal visitasi pada tahap ini.">
                            <div class="p-6">
                                <div class="row g-5">
                                    <x-ui.detail-item label="Status Saat Ini" value="{{ Akreditasi::getStatusLabel($akreditasi->status) }}" />
                                    <x-ui.detail-item label="Jadwal Visitasi" value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Belum dijadwalkan' }}" />
                                    <x-ui.detail-item label="Tahap Nilai Berikutnya" value="Penilaian Pasca Visitasi" />
                                    <x-ui.detail-item label="Nilai Verifikasi" value="Terbuka setelah Nilai Kelompok final" />
                                </div>
                            </div>
                        </x-ui.section-card>
                    @else
                    @if ($akreditasi->status == 1 && (empty($akreditasi->kartu_kendali) || empty($akreditasi->laporan_visitasi_asesor1)))
                        <x-ui.alert variant="warning" icon="timer" title="Kelengkapan Dokumen Wajib">
                            Nilai NV hanya dapat disimpan apabila Kartu Kendali dan Laporan Visitasi telah diunggah.
                        </x-ui.alert>
                    @endif

                    {{-- Progress indicators are available after visitasi is confirmed selesai. --}}
                    @if ((int) $akreditasi->status === \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI && ($asesor1NaProgress || $asesor2NaProgress))
                        <x-ui.section-card title="Progress Penilaian Asesor" subtitle="Kelengkapan pengisian butir oleh masing-masing asesor.">
                            <div class="p-6">
                                <div class="row g-5">
                                    @if ($asesor1NaProgress)
                                        @php $c1Na = $asesor1NaProgress['percentage'] >= 100 ? 'green' : ($asesor1NaProgress['percentage'] >= 50 ? 'amber' : 'red'); @endphp
                                        <div class="col-lg-4">
                                            <x-progress-indicator
                                                :filled="$asesor1NaProgress['filled']"
                                                :total="$asesor1NaProgress['total']"
                                                :percentage="$asesor1NaProgress['percentage']"
                                                label="Nilai Ketua"
                                                :color="$c1Na"
                                            />
                                        </div>
                                    @endif
                                    @if ($asesor1NkProgress)
                                        @php $c1Nk = $asesor1NkProgress['percentage'] >= 100 ? 'green' : ($asesor1NkProgress['percentage'] >= 50 ? 'amber' : 'red'); @endphp
                                        <div class="col-lg-4">
                                            <x-progress-indicator
                                                :filled="$asesor1NkProgress['filled']"
                                                :total="$asesor1NkProgress['total']"
                                                :percentage="$asesor1NkProgress['percentage']"
                                                label="Nilai Kelompok"
                                                :color="$c1Nk"
                                            />
                                        </div>
                                    @endif
                                    @if ($asesor2NaProgress)
                                        @php $c2Na = $asesor2NaProgress['percentage'] >= 100 ? 'green' : ($asesor2NaProgress['percentage'] >= 50 ? 'amber' : 'red'); @endphp
                                        <div class="col-lg-4">
                                            <x-progress-indicator
                                                :filled="$asesor2NaProgress['filled']"
                                                :total="$asesor2NaProgress['total']"
                                                :percentage="$asesor2NaProgress['percentage']"
                                                label="Nilai Anggota"
                                                :color="$c2Na"
                                            />
                                        </div>
                                    @endif
                                </div>

                                {{-- Blocking badges --}}
                                @php
                                    $blockers = [];
                                    if ($asesor1NaProgress && $asesor1NaProgress['percentage'] < 100) $blockers[] = 'Menunggu Nilai Ketua';
                                    if ($asesor1NkProgress && $asesor1NkProgress['percentage'] < 100) $blockers[] = 'Menunggu Nilai Kelompok';
                                    if ($asesor2NaProgress && $asesor2NaProgress['percentage'] < 100) $blockers[] = 'Menunggu Nilai Anggota';
                                @endphp
                                @if (!empty($blockers))
                                    <div class="d-flex flex-wrap gap-2 mt-4">
                                        @foreach ($blockers as $blocker)
                                            <x-ui.badge variant="warning">
                                                <x-ui.icon name="timer" class="fs-7 me-1" />
                                                {{ $blocker }}
                                            </x-ui.badge>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </x-ui.section-card>
                    @endif

                    <x-ui.section-card title="Nilai Akhir" subtitle="Perbandingan Nilai Ketua, Nilai Anggota, Nilai Kelompok, Nilai Verifikasi Admin, catatan butir, dan rekomendasi.">
                        <div class="p-6">
                            <x-ui.simple-table tableClass="spm-score-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4 w-150px">Komponen</th>
                                        <th class="text-center w-80px">No SK</th>
                                        <th class="text-center w-90px">No Butir</th>
                                        <th>Pernyataan</th>
                                        <th class="text-center w-90px">Nilai Ketua</th>
                                        <th class="text-center w-90px">Nilai Anggota</th>
                                        <th class="text-center w-100px">Nilai Kelompok</th>
                                        <th class="text-center w-110px">Nilai Verifikasi</th>
                                        <th class="w-220px">Catatan Butir</th>
                                        <th class="pe-4 w-260px">Rekomendasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponens as $komponen)
                                        @php $butirsCount = count($komponen->butirs); @endphp
                                        @foreach ($komponen->butirs as $index => $butir)
                                            <tr>
                                                @if ($index === 0)
                                                    <td rowspan="{{ $butirsCount }}" class="ps-4 fw-bold text-primary text-uppercase align-middle">
                                                        {{ $komponen->nama }}
                                                    </td>
                                                @endif
                                                <td class="text-center text-muted">{{ $butir->no_sk }}</td>
                                                <td class="text-center fw-bold">{{ $butir->nomor_butir }}</td>
                                                <td class="spm-edpm-statement">{{ $butir->butir_pernyataan }}</td>
                                                <td class="text-center fw-bold">{{ $asesor1Evaluasis[$butir->id] ?? '' }}</td>
                                                <td class="text-center fw-bold">{{ $asesor2Evaluasis[$butir->id] ?? '' }}</td>
                                                <td class="text-center fw-bold text-warning">{{ $asesor1Nks[$butir->id] ?? '' }}</td>
                                                <td class="text-center">
                                                    @if ($akreditasi->status == 1)
                                                        <x-ui.select
                                                            model="adminNvs.{{ $butir->id }}"
                                                            modifier="live"
                                                            :options="['1' => '1', '2' => '2', '3' => '3', '4' => '4']"
                                                            placeholder="Pilih"
                                                            size="sm"
                                                            class="spm-score-control mx-auto"
                                                        />
                                                        @error('adminNvs.' . $butir->id)
                                                            <div class="invalid-feedback d-block fs-9">{{ $message }}</div>
                                                        @enderror
                                                    @else
                                                        <x-ui.badge variant="primary">{{ $adminNvs[$butir->id] ?? '' }}</x-ui.badge>
                                                    @endif
                                                </td>
                                                <td class="fs-8 text-muted">{{ $asesor1ButirCatatans[$butir->id] ?? '' }}</td>
                                                @if ($index === 0)
                                                    <td rowspan="{{ $butirsCount }}" class="pe-4 fs-8 text-gray-700 align-top">
                                                        {!! $asesor1Catatans[$komponen->id] ?? '-' !!}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>
                        </div>
                    </x-ui.section-card>

                    @if ($akreditasi->status == 1)
                        <div class="spm-action-panel d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                            <div>
                                <h3 class="spm-card-title mb-1">Nilai Verifikasi (NV)</h3>
                                <div class="text-muted fw-semibold fs-7">Simpan nilai verifikasi setelah semua butir lengkap.</div>
                            </div>
                            <div class="d-flex gap-3">
                                <x-ui.button type="button" @click="confirmSaveNV($wire)" wire:loading.attr="disabled" variant="primary">
                                    <span wire:loading.remove wire:target="saveAdminNv">Simpan NV (Draft)</span>
                                    <span wire:loading wire:target="saveAdminNv">Menyimpan...</span>
                                </x-ui.button>
                                @if(!$akreditasi->is_nv_final)
                                    <x-ui.button type="button" wire:click="finalizeAllNv" wire:loading.attr="disabled" variant="success">
                                        <span wire:loading.remove wire:target="finalizeAllNv">
                                            <x-ui.icon name="lock" class="fs-5 me-1" />
                                            Finalisasi Semua NV
                                        </span>
                                        <span wire:loading wire:target="finalizeAllNv">Memproses...</span>
                                    </x-ui.button>
                                @else
                                    <x-ui.badge variant="success" class="align-self-center">
                                        <x-ui.icon name="lock" class="fs-7 me-1" />
                                        NV Sudah Final
                                    </x-ui.badge>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($akreditasi->status == 1 || $akreditasi->status == 0)
                        <x-ui.section-card title="Ringkasan Data Penilaian" subtitle="Perhitungan skor komponen dan hasil akhir.">
                            <div class="p-6">
                                @php
                                    $totalCmaks = 0;
                                    $totalCI = 0;
                                    $totalBK = 0;
                                    $totalSkorKomponen = 0;
                                    $grandTotalSkor = 0;

                                    $bobotKomponen = [
                                        'MUTU LULUSAN' => 35,
                                        'PROSES PEMBELAJARAN' => 29,
                                        'MUTU USTAZ' => 18,
                                        'MANAJEMEN PESANTREN' => 18,
                                        'INDIKATOR PEMENUHAN RELATIF' => 97,
                                    ];

                                    $iprNullComponents = $komponens->filter(function($k) { return is_null($k->ipr); });
                                    $iprNotNullComponents = $komponens->filter(function($k) { return !is_null($k->ipr); });

                                    $totalSkorIprNull = 0;
                                    foreach ($iprNullComponents as $k) {
                                        $b = $bobotKomponen[$k->nama] ?? 0;
                                        $c_total = count($k->butirs) * 4;
                                        $c_ci = 0;
                                        foreach ($k->butirs as $butir) {
                                            $c_ci += (int)($adminNvs[$butir->id] ?? 0);
                                        }
                                        if ($c_total > 0) {
                                            $totalSkorIprNull += round(($c_ci / $c_total) * $b);
                                        }
                                    }
                                @endphp

                                <x-ui.simple-table tableClass="spm-wide-table">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">Komponen</th>
                                            <th class="text-center">Cmaks</th>
                                            <th class="text-center">CI</th>
                                            <th class="text-center">BK</th>
                                            <th class="text-center">Skor Komponen</th>
                                            <th class="text-center pe-4">Total Skor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($komponens as $index => $komponen)
                                            @php
                                                $totalButir = count($komponen->butirs);
                                                $cmaksKomponen = $totalButir * 4;
                                                $sumNvKomponen = 0;
                                                foreach ($komponen->butirs as $butir) {
                                                    $sumNvKomponen += (int) ($adminNvs[$butir->id] ?? 0);
                                                }
                                                $bkValue = $bobotKomponen[$komponen->nama] ?? 0;
                                                $isIpr = !is_null($komponen->ipr);
                                                $faktor = $isIpr ? 100 : $bkValue;
                                                $skorKomponen = $cmaksKomponen > 0 ? round(($sumNvKomponen / $cmaksKomponen) * $faktor) : 0;
                                            @endphp

                                            <tr>
                                                <td class="ps-4 fw-bold">{{ $komponen->nama }}</td>
                                                <td class="text-center fw-bold">{{ $cmaksKomponen }}</td>
                                                <td class="text-center fw-bold text-primary">{{ $sumNvKomponen }}</td>
                                                <td class="text-center fw-bold text-warning">{{ $bkValue }}</td>
                                                <td class="text-center fw-bold">{{ $skorKomponen }}</td>

                                                @if ($index === 0)
                                                    <td rowspan="{{ $iprNullComponents->count() }}" class="text-center pe-4 fw-bold fs-4 text-success align-middle">
                                                        {{ $totalSkorIprNull }}
                                                    </td>
                                                @elseif ($index === $iprNullComponents->count())
                                                    <td class="text-center pe-4 fw-bold fs-4 text-success align-middle">
                                                        {{ $skorKomponen }}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </x-ui.simple-table>

                                @php
                                    $totalSkorIprNotNull = 0;
                                    foreach ($iprNotNullComponents as $k) {
                                        $c_total = count($k->butirs) * 4;
                                        $c_ci = 0;
                                        foreach ($k->butirs as $butir) {
                                            $c_ci += (int)($adminNvs[$butir->id] ?? 0);
                                        }
                                        if ($c_total > 0) {
                                            $totalSkorIprNotNull += round(($c_ci / $c_total) * 100);
                                        }
                                    }

                                    $nilaiAkreditasi = round((0.7 * $totalSkorIprNull) + (0.3 * $totalSkorIprNotNull));
                                    $peringkat = 'NA';
                                    if ($nilaiAkreditasi >= 86) {
                                        $peringkat = 'Unggul';
                                    } elseif ($nilaiAkreditasi >= 70) {
                                        $peringkat = 'Baik';
                                    } elseif ($nilaiAkreditasi >= 0) {
                                        $peringkat = 'Cukup';
                                    }
                                @endphp

                                <div class="row g-5 mt-2">
                                    <div class="col-md-6">
                                        <div class="spm-result-metric">
                                            <div class="spm-detail-label">Nilai Akreditasi</div>
                                            <div class="fs-2 fw-bold text-primary">{{ $nilaiAkreditasi }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="spm-result-metric">
                                            <div class="spm-detail-label">Peringkat Akreditasi</div>
                                            <div class="fs-2 fw-bold text-gray-900">{{ $peringkat }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif

                    @if ($akreditasi->status == 1)
                        <div class="row g-6">
                            <div class="col-lg-6">
                                <x-ui.section-card title="Setujui Akreditasi" subtitle="Lengkapi SK dan sertifikat final.">
                                    @if(in_array($akreditasi->status, [0, -1]))
                                        <div class="p-6">
                                            <x-ui.alert variant="warning" class="mb-0">
                                                Akreditasi telah diproses oleh admin lain. Muat ulang halaman untuk melihat status terbaru.
                                            </x-ui.alert>
                                        </div>
                                    @else
                                    <form @submit.prevent="confirmApprove($wire)" class="p-6">
                                        <x-ui.form-field label="Nomor SK" for="nomor_sk" :error="$errors->get('nomor_sk')">
                                            <x-ui.input model="nomor_sk" id="nomor_sk" placeholder="Masukkan nomor SK resmi..." required />
                                        </x-ui.form-field>

                                        <x-ui.form-field label="Unggah Sertifikat (PDF)" for="sertifikat_file" :error="$errors->get('sertifikat_file')">
                                            <x-ui.file-upload
                                                model="sertifikat_file"
                                                id="sertifikat_file"
                                                accept="application/pdf"
                                                :file="$sertifikat_file"
                                                placeholder="Pilih file sertifikat"
                                                hint="PDF maksimal 10MB"
                                            />
                                            <div wire:loading wire:target="sertifikat_file" class="text-primary fw-bold fs-8 mt-2">Mengunggah...</div>
                                        </x-ui.form-field>

                                        <div class="row g-5">
                                            <div class="col-md-6">
                                                <x-ui.form-field label="Mulai Berlaku" for="masa_berlaku" :error="$errors->get('masa_berlaku')">
                                                    <x-ui.input model="masa_berlaku" id="masa_berlaku" type="date" required />
                                                </x-ui.form-field>
                                            </div>
                                            <div class="col-md-6">
                                                <x-ui.form-field label="Akhir Berlaku" for="masa_berlaku_akhir" :error="$errors->get('masa_berlaku_akhir')">
                                                    <x-ui.input model="masa_berlaku_akhir" id="masa_berlaku_akhir" type="date" required />
                                                </x-ui.form-field>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <x-ui.button type="submit" variant="success" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="approve">Setujui & Simpan</span>
                                                <span wire:loading wire:target="approve">Memproses...</span>
                                            </x-ui.button>
                                        </div>
                                    </form>
                                    @endif
                                </x-ui.section-card>
                            </div>

                            <div class="col-lg-6">
                                <x-ui.section-card title="Tolak Akreditasi" subtitle="Pilih kategori dan berikan penjelasan per kategori.">
                                    @if(in_array($akreditasi->status, [0, -1]))
                                        <div class="p-6">
                                            <x-ui.alert variant="warning" class="mb-0">
                                                Akreditasi telah diproses oleh admin lain. Muat ulang halaman untuk melihat status terbaru.
                                            </x-ui.alert>
                                        </div>
                                    @else
                                    <form x-on:submit.prevent="confirmRejectFinal($wire)" class="p-6">
                                        <div class="mb-4">
                                            <div class="spm-detail-label mb-2">Kategori Penolakan <span class="text-danger">*</span></div>

                                            @foreach($rejectionCategories as $index => $entry)
                                                <div class="spm-soft-panel mb-3">
                                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                                        <div class="flex-grow-1">
                                                            <x-ui.select model="rejectionCategories.{{ $index }}.category" size="sm" class="mb-2">
                                                                <option value="">-- Pilih Kategori --</option>
                                                                @foreach(config('akreditasi.final_rejection_categories', []) as $key => $label)
                                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                                @endforeach
                                                            </x-ui.select>
                                                            @error("rejectionCategories.{$index}.category")
                                                                <div class="text-danger fs-8">{{ $message }}</div>
                                                            @enderror

                                                            <x-ui.textarea
                                                                model="rejectionCategories.{{ $index }}.explanation"
                                                                rows="3"
                                                                placeholder="Penjelasan detail (min 10 karakter)..."
                                                            />
                                                            @error("rejectionCategories.{$index}.explanation")
                                                                <div class="text-danger fs-8">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                        <x-ui.button type="button" variant="light-danger" size="sm" wire:click="removeRejectionCategory({{ $index }})">
                                                            <x-ui.icon name="cross" class="fs-6" />
                                                        </x-ui.button>
                                                    </div>
                                                </div>
                                            @endforeach

                                            @error('rejectionCategories')
                                                <div class="text-danger fs-8 mb-2">{{ $message }}</div>
                                            @enderror

                                            <x-ui.button type="button" wire:click="addRejectionCategory" variant="light" size="sm">
                                                + Tambah Kategori
                                            </x-ui.button>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <x-ui.button type="submit" variant="danger" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="reject">Tolak Pengajuan</span>
                                                <span wire:loading wire:target="reject">Memproses...</span>
                                            </x-ui.button>
                                        </div>
                                    </form>
                                    @endif
                                </x-ui.section-card>
                            </div>
                        </div>
                    @endif

                    <div class="spm-scroll-actions">
                        <x-ui.button
                            type="button"
                            variant="primary"
                            class="btn-icon"
                            title="Scroll ke atas"
                            onclick="document.getElementById('main-content-scroll')?.scrollTo({top: 0, behavior: 'smooth'})"
                        >
                            <x-ui.icon name="arrow-up" class="fs-2" />
                        </x-ui.button>
                        <x-ui.button
                            type="button"
                            variant="primary"
                            class="btn-icon"
                            title="Scroll ke bawah"
                            onclick="const el = document.getElementById('main-content-scroll'); el?.scrollTo({top: el.scrollHeight, behavior: 'smooth'})"
                        >
                            <x-ui.icon name="arrow-down" class="fs-2" />
                        </x-ui.button>
                    </div>
                    @endif
                </div>
            @endif

            @if($activeTab === 'laporan_visitasi')
                <div class="d-flex flex-column gap-6">
                    {{-- Post-Visitasi Document Checklist — visible after visitasi (Req 10.6) --}}
                    @if(in_array((int) $akreditasi->status, [
                        \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI,
                        \App\StateMachine\AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
                        \App\StateMachine\AkreditasiStateMachine::STATUS_SELESAI,
                    ]))
                        @php
                            $requiredDocs = [
                                ['key' => 'laporan_visitasi_asesor1',  'label' => 'Laporan Visitasi Ketua Kelompok',  'uploader' => 'Ketua Kelompok'],
                                ['key' => 'laporan_visitasi_asesor2',  'label' => 'Laporan Visitasi Anggota Kelompok', 'uploader' => 'Anggota Kelompok'],
                                ['key' => 'laporan_visitasi_kelompok', 'label' => 'Laporan Visitasi Kelompok',          'uploader' => 'Ketua Kelompok'],
                                ['key' => 'kartu_kendali',             'label' => 'Kartu Kendali',                       'uploader' => 'Pesantren'],
                            ];
                            $available = 0;
                            foreach ($requiredDocs as $doc) {
                                if (!empty($akreditasi->{$doc['key']})) {
                                    $available++;
                                }
                            }
                            $isComplete = $available === count($requiredDocs);
                        @endphp
                        <x-ui.section-card title="Kelengkapan Dokumen Penilaian Pasca Visitasi">
                            <x-slot:toolbar>
                                <x-ui.status-badge :variant="$isComplete ? 'success' : 'warning'">
                                    <x-ui.icon :name="$isComplete ? 'check-circle' : 'information'" class="fs-4 me-2" />
                                    {{ $available }}/{{ count($requiredDocs) }} Lengkap
                                </x-ui.status-badge>
                            </x-slot:toolbar>
                            <div class="p-6">
                                <div class="d-flex flex-column gap-3">
                                    @foreach($requiredDocs as $doc)
                                        @php $has = !empty($akreditasi->{$doc['key']}) @endphp
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="symbol symbol-30px">
                                                <span class="symbol-label {{ $has ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">
                                                    <span class="svg-icon svg-icon-2">
                                                        @if($has)
                                                            <i class="bi bi-check-lg fw-bold"></i>
                                                        @else
                                                            <i class="bi bi-x-lg fw-bold"></i>
                                                        @endif
                                                    </span>
                                                </span>
                                            </span>
                                            <span class="fw-semibold text-gray-800">{{ $doc['label'] }}</span>
                                            <span class="text-muted fs-8 ms-auto">{{ $doc['uploader'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif

                    @if($akreditasi->status >= 3)
                        <x-ui.section-card title="Kartu Kendali" subtitle="Dokumen kontrol validasi dari pesantren.">
                            <div class="p-6">
                                <div class="spm-document-list">
                                    <x-ui.document-item
                                        label="Dokumen Kartu Kendali"
                                        :href="$akreditasi->kartu_kendali ? Storage::url($akreditasi->kartu_kendali) : null"
                                        description="Diunggah oleh pesantren untuk validasi."
                                    />
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif

                    <x-ui.section-card title="Laporan Hasil Visitasi" subtitle="Dokumen laporan dari ketua dan anggota asesor.">
                        <div class="p-6">
                            <div class="row g-5">
                                <div class="col-lg-4">
                                    <div class="spm-document-list">
                                        <x-ui.document-item
                                            label="Laporan Ketua Kelompok"
                                            :href="$akreditasi->laporan_visitasi_asesor1 ? Storage::url($akreditasi->laporan_visitasi_asesor1) : null"
                                        />
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="spm-document-list">
                                        <x-ui.document-item
                                            label="Laporan Anggota Kelompok"
                                            :href="$akreditasi->laporan_visitasi_asesor2 ? Storage::url($akreditasi->laporan_visitasi_asesor2) : null"
                                        />
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="spm-document-list">
                                        <x-ui.document-item
                                            label="Laporan Kelompok"
                                            :href="$akreditasi->laporan_visitasi_kelompok ? Storage::url($akreditasi->laporan_visitasi_kelompok) : null"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

            @if($activeTab === 'audit_trail')
                <livewire:pages.admin.audit-timeline :akreditasiId="$akreditasi->id" />
            @endif
        </div>

        <x-ui.modal name="visitasi-edit-modal" focusable>
            <form x-on:submit.prevent="confirmRescheduleVisitasi($wire)">
                <x-ui.modal-header
                    title="Reschedule Jadwal Visitasi"
                    subtitle="Perbarui jadwal visitasi dalam rentang penilaian."
                    icon="timer"
                />

                <x-ui.modal-body>
                    <div class="row g-5">
                        <div class="col-md-6">
                            <x-ui.form-field label="Tanggal Mulai Visitasi" for="tgl_visitasi" :error="$errors->get('tgl_visitasi')">
                                <x-ui.input model="tgl_visitasi" id="tgl_visitasi" type="date" />
                            </x-ui.form-field>
                        </div>

                        <div class="col-md-6">
                            <x-ui.form-field label="Tanggal Akhir Visitasi" for="tgl_visitasi_akhir" :error="$errors->get('tgl_visitasi_akhir')">
                                <x-ui.input model="tgl_visitasi_akhir" id="tgl_visitasi_akhir" type="date" />
                            </x-ui.form-field>
                        </div>
                    </div>
                </x-ui.modal-body>

                <x-ui.modal-footer>
                    <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">
                        Batal
                    </x-ui.button>

                    <x-ui.button type="submit" variant="primary">
                        Simpan Perubahan
                    </x-ui.button>
                </x-ui.modal-footer>
            </form>
        </x-ui.modal>
    </x-ui.card>

    {{-- Reassign Asesor Modal --}}
    <x-ui.modal name="reassign-asesor-modal" focusable>
        <form x-on:submit.prevent="confirmReassignAsesor($wire)">
            <x-ui.modal-header
                title="Ganti Asesor"
                subtitle="Pilih asesor pengganti untuk akreditasi yang telah melewati deadline."
                icon="arrows-circle"
            />

            <x-ui.modal-body>
                <div class="notice d-flex bg-light-danger rounded border-danger border border-dashed p-4 mb-6">
                    <x-ui.icon name="warning-2" class="fs-2 text-danger me-4" />
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-danger">Akreditasi Terlambat</h4>
                        <span class="fs-7 text-gray-700">Asesor saat ini belum menyelesaikan tugasnya setelah melewati deadline. Pilih asesor pengganti untuk melanjutkan proses akreditasi.</span>
                    </div>
                </div>

                <x-ui.form-field label="Asesor Pengganti" for="reassignAsesorId" :error="$errors->get('reassignAsesorId')">
                    <x-ui.select model="reassignAsesorId" id="reassignAsesorId" placeholder="Pilih Asesor Pengganti">
                        @foreach ($availableAsesorsForReassignment as $asesor)
                            <option value="{{ $asesor['id'] }}">
                                {{ $asesor['nama_dengan_gelar'] ?? ($asesor['user']['name'] ?? 'Asesor #' . $asesor['id']) }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </x-ui.form-field>

                <div class="text-muted fs-7 mt-3">
                    <x-ui.icon name="information-5" class="fs-6 me-1" />
                    Setelah penggantian, deadline baru akan ditetapkan berdasarkan konfigurasi sistem.
                </div>
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">
                    Batal
                </x-ui.button>
                <x-ui.button type="submit" variant="danger" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="reassignAsesor">Ganti Asesor</span>
                    <span wire:loading wire:target="reassignAsesor">Memproses...</span>
                </x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

    {{-- Approve Berkas Modal --}}
    <x-ui.modal name="approve-berkas-modal" focusable>
        <x-ui.modal-header title="Setujui Berkas" subtitle="Tugaskan Ketua Kelompok dan Anggota Kelompok untuk melanjutkan ke tahap assessment." icon="check-circle" variant="success" />
        <x-ui.modal-body>
            <x-ui.form-field label="Ketua Kelompok" for="asesor1Id" :error="$errors->first('asesor1Id')">
                <x-ui.select model="asesor1Id" id="asesor1Id" placeholder="-- Pilih Ketua Kelompok --">
                    @foreach(\App\Models\Asesor::with('user')->get() as $asesor)
                        <option value="{{ $asesor->user_id }}">{{ $asesor->nama_tanpa_gelar ?? $asesor->user->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-field>
            <x-ui.form-field label="Anggota Kelompok" for="asesor2Id" :error="$errors->first('asesor2Id')">
                <x-ui.select model="asesor2Id" id="asesor2Id" placeholder="-- Pilih Anggota Kelompok --">
                    @foreach(\App\Models\Asesor::with('user')->get() as $asesor)
                        <option value="{{ $asesor->user_id }}">{{ $asesor->nama_tanpa_gelar ?? $asesor->user->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-field>
        </x-ui.modal-body>
        <x-ui.modal-footer>
            <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'approve-berkas-modal')">Batal</x-ui.button>
            <x-ui.button type="button" variant="success" wire:click="approveBerkas" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="approveBerkas">Setujui & Tugaskan Tim Asesor</span>
                <span wire:loading wire:target="approveBerkas">Memproses...</span>
            </x-ui.button>
        </x-ui.modal-footer>
    </x-ui.modal>

    {{-- Reject Berkas Modal --}}
    <x-ui.modal name="reject-berkas-modal" focusable>
        <x-ui.modal-header title="Tolak Berkas" subtitle="Pilih bagian yang bermasalah dan berikan catatan penolakan." icon="cross-circle" variant="danger" />
        <x-ui.modal-body>
            <x-ui.form-field label="Bagian yang Ditolak" :error="$errors->first('berkasRejectionSections')">
                <div class="d-flex flex-column gap-3">
                    @foreach(['profil' => 'Profil', 'ipm.nsp' => 'IPM - NSP', 'ipm.kurikulum' => 'IPM - Kurikulum', 'ipm.buku_ajar' => 'IPM - Buku Ajar', 'ipm.lulus_santri' => 'IPM - Lulus Santri', 'sdm' => 'SDM', 'edpm' => 'EDPM'] as $value => $label)
                        <x-ui.checkbox model="berkasRejectionSections" :value="$value" :label="$label" />
                    @endforeach
                </div>
            </x-ui.form-field>
            <x-ui.form-field label="Catatan Penolakan" for="berkasRejectionCatatan" :error="$errors->first('berkasRejectionCatatan')" hint="Minimal 10 karakter, maksimal 2000 karakter.">
                <x-ui.textarea model="berkasRejectionCatatan" id="berkasRejectionCatatan" rows="4" placeholder="Jelaskan alasan penolakan berkas..." />
            </x-ui.form-field>
        </x-ui.modal-body>
        <x-ui.modal-footer>
            <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'reject-berkas-modal')">Batal</x-ui.button>
            <x-ui.button type="button" variant="danger" wire:click="rejectBerkas" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="rejectBerkas">Tolak Berkas</span>
                <span wire:loading wire:target="rejectBerkas">Memproses...</span>
            </x-ui.button>
        </x-ui.modal-footer>
    </x-ui.modal>
</x-ui.page>
