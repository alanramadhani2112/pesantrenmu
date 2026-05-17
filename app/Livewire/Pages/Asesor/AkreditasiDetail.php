<?php

namespace App\Livewire\Pages\Asesor;

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

    public $laporan_visitasi_file;

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

    #[Url]
    public $activeTab = 'profil';

    public $isLocked = false;

    // Rejection form properties
    public $rejectedItems = [];

    // Concurrent access handling
    public string $akreditasiUpdatedAt = '';

    public $rejectionExplanation = '';

    public $selectableItems = [];

    public $rejectionStatus = [];

    // Overall Accreditation Scores

    public function mount($uuid)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isAsesor()) {
            abort(403);
        }

        $asesorService = app(\App\Services\AsesorService::class);
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

        // Security check: Hide Laporan Visitasi tab if status is 4 or 5
        if (($this->akreditasi->status == 4 || $this->akreditasi->status == 5) && $this->activeTab === 'laporan_visitasi') {
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
            $rejectionService = app(\App\Services\RejectionService::class);
            $this->rejectionStatus = $rejectionService->getRejectionStatus($this->akreditasi->id);
            $this->selectableItems = $rejectionService->getSelectableItems($this->akreditasi->id);
        }

        // Load progress data (for status 4 and 5)
        if ($this->akreditasi->status == 4 || $this->akreditasi->status == 5) {
            $progress = $data['progress'] ?? [];
            $this->asesor1NaProgress = $progress['asesor1_na'] ?? null;
            $this->asesor1NkProgress = $progress['asesor1_nk'] ?? null;
            $this->asesor2NaProgress = $progress['asesor2_na'] ?? null;
        }

        // Concurrent access: store updated_at for optimistic locking
        $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();
    }

    /**
     * Poll for status changes (called by wire:poll).
     */
    public function checkForUpdates(): void
    {
        $fresh = \App\Models\Akreditasi::find($this->akreditasi->id);
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
                        . \App\Models\Akreditasi::getStatusLabel($fresh->status)
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

        if ($this->akreditasi->status != 4 && $this->akreditasi->status != 3) {
            session()->flash('error', 'Data tidak dapat diubah karena status bukan Visitasi/Validasi.');

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

        $asesorService = app(\App\Services\AsesorService::class);
        $asesorId = Auth::user()->asesor->id;

        if ($asesorService->saveAsesorEdpm($this->akreditasi->id, $asesorId, $this->asesorTipe, $this->akreditasi->user_id, [
            'asesorEvaluasis' => $this->asesorEvaluasis,
            'asesorButirCatatans' => $this->asesorButirCatatans,
            'asesorNks' => $this->asesorNks,
            'asesorCatatans' => $this->asesorCatatans,
            'asesorCatatanNks' => $this->asesorCatatanNks,
        ])) {
            if ($this->asesorTipe == 1) {
                $this->isLocked = true;
            }
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Instrumen Akreditasi berhasil disimpan.');

            return true;
        }

        return false;
    }

    public function finalizeVerification()
    {
        if ($this->asesorTipe != 1) {
            abort(403);
        }

        if (! $this->saveAsesorEdpm(isFinal: true)) {
            return;
        }

        $asesorService = app(\App\Services\AsesorService::class);

        try {
            $result = $asesorService->finalizeVerification($this->akreditasi->id, Auth::id(), $this->akreditasiUpdatedAt);
        } catch (\App\Exceptions\ConflictException $e) {
            $this->akreditasi->refresh();
            $this->akreditasiUpdatedAt = $this->akreditasi->updated_at->toISOString();
            $this->dispatch('notification-received',
                type: 'error',
                title: 'Konflik Terdeteksi',
                message: "Akreditasi telah dimodifikasi oleh pengguna lain. Status saat ini: {$e->getStatusLabel()}. Silakan muat ulang halaman untuk melihat data terbaru."
            );
            return;
        }

        if ($result['success']) {
            session()->flash('status', 'Assessment berhasil diselesaikan. Status berubah menjadi Validasi Admin.');

            return redirect()->route('asesor.akreditasi');
        }

        // Dispatch error event with error type and details for UI feedback
        $this->dispatch('finalization-failed', error: $result['error'], details: $result['details']);
    }

    public function uploadLaporanVisitasi()
    {
        Gate::authorize('update', $this->akreditasi);

        if ($this->akreditasi->status != 4 && $this->akreditasi->status != 3) {
            abort(403, 'Proses unggah laporan hanya dapat dilakukan pada masa Visitasi atau Validasi.');

            return;
        }

        $this->validate([
            'laporan_visitasi_file' => 'required|file|mimes:pdf,docx|max:5120',
        ], [
            'laporan_visitasi_file.required' => 'File Laporan Visitasi wajib diunggah.',
            'laporan_visitasi_file.mimes' => 'Format file harus PDF atau DOCX.',
            'laporan_visitasi_file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        $path = $this->laporan_visitasi_file->store('akreditasi/laporan_visitasi', 'public');

        $asesorService = app(\App\Services\AsesorService::class);
        $asesorService->uploadLaporanVisitasi($this->akreditasi->id, $this->asesorTipe, $path);

        $this->dispatch('notification-received', type: 'success', title: 'Berhasil Upload', message: 'Laporan Visitasi berhasil diunggah secara permanen.');
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

        $asesorService = app(\App\Services\AsesorService::class);
        $result = $asesorService->processVisitasi($this->akreditasi->id, \Illuminate\Support\Facades\Auth::id(), [
            'rejected_items' => $this->rejectedItems,
            'catatan' => $this->rejectionExplanation,
        ], 'tolak');

        if ($result) {
            $this->reset(['rejectedItems', 'rejectionExplanation']);
            // Reload rejection status
            $rejectionService = app(\App\Services\RejectionService::class);
            $this->rejectionStatus = $rejectionService->getRejectionStatus($this->akreditasi->id);
            $this->akreditasi->refresh();

            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Penolakan berhasil dikirim.');
        } else {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Penolakan gagal diproses.');
        }
    }

    public function acceptPerbaikan()
    {
        Gate::authorize('update', $this->akreditasi);

        if ($this->asesorTipe != 1) {
            abort(403);
        }

        $rejectionService = app(\App\Services\RejectionService::class);
        $result = $rejectionService->acceptPerbaikan($this->akreditasi->id, \Illuminate\Support\Facades\Auth::id());

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

    public function render()
    {
        return view('livewire.pages.asesor.akreditasi-detail');
    }
}
