<?php

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Edpm;
use App\Models\Pesantren;
use App\Livewire\Concerns\AdminAkreditasiInstrumenViewData;
use App\Services\DeadlineService;
use App\Services\ProgressTracker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;
    use AdminAkreditasiInstrumenViewData;

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

    // Rejection data
    public $rejectionCategories = [];

    public $rejectionStatus = [];

    // Progress tracking (status 5 only)
    public $asesor1NaProgress = null;
    public $asesor1NkProgress = null;
    public $asesor2NaProgress = null;

    #[Url]
    public $activeTab = 'profil';

    // Reassignment
    public $reassignAsesorId = '';
    public $isOverdue = false;
    public $availableAsesorsForReassignment = [];
    public $asesorsForAssignment = [];

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
        $this->asesorsForAssignment = Asesor::query()
            ->with('user:id,name')
            ->get()
            ->map(fn (Asesor $asesor): array => [
                'user_id' => $asesor->user_id,
                'name' => $asesor->nama_tanpa_gelar ?? ($asesor->user?->name ?? 'Asesor #'.$asesor->user_id),
            ])
            ->all();

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

        // Load rejection status data
        $rejectionService = app(\App\Services\RejectionService::class);
        $this->rejectionStatus = $rejectionService->getRejectionStatus($this->akreditasi->id);

        // Load scoring progress only after visitasi is confirmed selesai.
        if ($this->akreditasi->status == \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            $progressTracker = app(ProgressTracker::class);
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
            ], $this->akreditasiUpdatedAt);

            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'SK Akreditasi berhasil diterbitkan.');
            return redirect()->route('admin.akreditasi');
        } catch (\App\Exceptions\ConflictException $e) {
            $this->akreditasi->refresh();
            $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();
            $this->dispatch('notification-received', type: 'error', title: 'Konflik Terdeteksi', message: 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
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
            $workflowService->approveBerkas($this->akreditasi->id, Auth::id(), (int) $this->asesor1Id, (int) $this->asesor2Id, $this->akreditasiUpdatedAt);
            $this->akreditasi->refresh();
            $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();
            $this->dispatch('close-modal', 'approve-berkas-modal');
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Berkas disetujui. Asesor telah ditugaskan.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
        } catch (\App\Exceptions\StaleStateException $e) {
            $this->akreditasi->refresh();
            $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();
            $this->dispatch('notification-received', type: 'error', title: 'Konflik Terdeteksi', message: 'Akreditasi telah dimodifikasi oleh pengguna lain. Silakan muat ulang halaman.');
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

        if (empty($this->akreditasi->laporan_visitasi_asesor1)) {
            $this->dispatch('notification-received', type: 'error', title: 'Data Belum Lengkap', message: 'NV belum dapat difinalisasi karena Laporan Visitasi Ketua Kelompok belum diunggah.');
            return;
        }

        try {
            $this->validate(['adminNvs.*' => 'required|integer|between:1,4']);

            $scoringService = app(\App\Services\AssessorScoringService::class);
            $adminId = Auth::id();
            $finalizedCount = 0;

            foreach ($this->adminNvs as $butirId => $nvValue) {
                try {
                    $scoringService->saveNV($this->akreditasi->id, $adminId, (int) $butirId, (int) $nvValue, true);
                    $finalizedCount++;
                } catch (\App\Exceptions\ImmutableValueException $e) {
                    $finalizedCount++;
                }
            }

            $this->akreditasi->update(['is_nv_final' => true]);
            $this->akreditasi->refresh();
            $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();

            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: "Semua NV ({$finalizedCount} butir) berhasil difinalisasi dan dikunci.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('validation-failed', title: 'Nilai NV Belum Lengkap', html: 'Mohon lengkapi seluruh nilai verifikasi sebelum finalisasi.');
            throw $e;
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
    wire:poll.visible.45s="checkForUpdates"
>
    <x-akreditasi.presence-indicator :akreditasi-id="$akreditasi->id" />
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$statusVariant">
            {{ Akreditasi::getStatusLabel($akreditasi->status) }}
        </x-ui.status-badge>

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
                                        <div class="fw-semibold">
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
        <div class="spm-detail-tabs-shell px-6 pt-5 pb-5">
            <x-ui.tabs>
                <x-ui.tab wire:click="setTab('profil')" :active="$activeTab === 'profil'">Profil</x-ui.tab>
                <x-ui.tab wire:click="setTab('ipm')" :active="$activeTab === 'ipm'">IPM</x-ui.tab>
                <x-ui.tab wire:click="setTab('sdm')" :active="$activeTab === 'sdm'">SDM</x-ui.tab>
                <x-ui.tab wire:click="setTab('edpm_pesantren')" :active="$activeTab === 'edpm_pesantren'">EDPM</x-ui.tab>
                <x-ui.tab wire:click="setTab('instrumen')" :active="$activeTab === 'instrumen'">Nilai</x-ui.tab>
                <x-ui.tab wire:click="setTab('laporan_visitasi')" :active="$activeTab === 'laporan_visitasi'">Laporan Visitasi</x-ui.tab>
                <x-ui.tab wire:click="setTab('audit_trail')" :active="$activeTab === 'audit_trail'">Audit Trail</x-ui.tab>
            </x-ui.tabs>
        </div>

        <div class="spm-detail-tab-content p-6">
            @include('livewire.pages.admin.akreditasi-detail.tabs.profil')
            @include('livewire.pages.admin.akreditasi-detail.tabs.ipm')
            @include('livewire.pages.admin.akreditasi-detail.tabs.sdm')
            @include('livewire.pages.admin.akreditasi-detail.tabs.edpm-pesantren')
            @include('livewire.pages.admin.akreditasi-detail.tabs.instrumen')
            @include('livewire.pages.admin.akreditasi-detail.tabs.laporan-visitasi')
            @include('livewire.pages.admin.akreditasi-detail.tabs.audit-trail')
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
        <x-ui.modal-header title="Setujui Berkas" subtitle="Tugaskan Ketua Kelompok dan Anggota Kelompok untuk melanjutkan ke tahap Review Asesor." icon="check-circle" variant="success" />
        <x-ui.modal-body>
            <x-ui.form-field label="Ketua Kelompok" for="asesor1Id" :error="$errors->first('asesor1Id')">
                <x-ui.select model="asesor1Id" id="asesor1Id" placeholder="-- Pilih Ketua Kelompok --">
                    @foreach($asesorsForAssignment as $asesor)
                        <option value="{{ $asesor['user_id'] }}">{{ $asesor['name'] }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.form-field>
            <x-ui.form-field label="Anggota Kelompok" for="asesor2Id" :error="$errors->first('asesor2Id')">
                <x-ui.select model="asesor2Id" id="asesor2Id" placeholder="-- Pilih Anggota Kelompok --">
                    @foreach($asesorsForAssignment as $asesor)
                        <option value="{{ $asesor['user_id'] }}">{{ $asesor['name'] }}</option>
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
