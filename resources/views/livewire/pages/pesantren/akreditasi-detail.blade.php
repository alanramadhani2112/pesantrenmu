<?php

use App\Services\BandingService;
use App\Services\PesantrenService;
use App\Services\RejectionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    public $akreditasi;

    public $pesantren;

    public $ipm;

    public $sdm;

    public $komponens;

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

    public $pesantrenEvaluasis = [];

    public $pesantrenCatatans = [];

    public $pesantrenLinks = [];

    public $asesor1Evaluasis = [];

    public $asesor2Evaluasis = [];

    public $asesor1Nks = [];

    public $adminNvs = [];

    public $asesorButirCatatans = [];

    public $asesorCatatans = [];

    public $asesorRekomendasis = [];

    #[Url]
    public $activeTab = 'profil';

    public $kartu_kendali_file;

    public $visitasiTemplate;

    public $bandingStatus = null;

    public $bandingEligibility = [];

    // Rejection status data
    public $rejectionStatus = [];

    // Banding
    public $bandingAlasan = '';

    use WithFileUploads;

    public function mount($uuid)
    {
        $pesantrenService = app(PesantrenService::class);
        $data = $pesantrenService->getAkreditasiDetail($uuid, Auth::id());

        $this->akreditasi = $data['akreditasi'];
        $this->pesantren = $data['pesantren'];
        $this->ipm = $data['ipm'];
        $this->sdm = $data['sdm'];
        $this->komponens = $data['komponens'];
        $this->visitasiTemplate = $data['visitasiTemplate'];

        // Tenant boundary: only owner pesantren / assigned asesor / admin can view
        Gate::authorize('view', $this->akreditasi);

        if ($this->pesantren && $this->pesantren->relationLoaded('units')) {
            $this->levels = $this->pesantren->units->pluck('unit')->toArray();
        }

        // Pesantren EDPM
        $this->pesantrenEvaluasis = $data['pesantren_edpm']['evaluasis'];
        $this->pesantrenLinks = $data['pesantren_edpm']['links'];
        $this->pesantrenCatatans = $data['pesantren_edpm']['catatans']->toArray();

        // Assessor 1
        if (! empty($data['asesor1'])) {
            $this->asesor1Evaluasis = $data['asesor1']['evaluasis'];
            $this->asesor1Nks = $data['asesor1']['nks'];
            $this->adminNvs = $data['asesor1']['nvs'];
            $this->asesorButirCatatans = $data['asesor1']['butir_catatans'];
            $this->asesorCatatans = $data['asesor1']['catatans'];
            $this->asesorRekomendasis = $data['asesor1']['rekomendasis'] ?? [];
        }

        // Assessor 2
        if (! empty($data['asesor2'])) {
            $this->asesor2Evaluasis = $data['asesor2']['evaluasis'];
        }

        // Ensure all components have entries in catatans
        foreach ($this->komponens as $komponen) {
            if (! isset($this->pesantrenCatatans[$komponen->id])) {
                $this->pesantrenCatatans[$komponen->id] = '';
            }
        }

        // Load banding status data
        $bandingService = app(BandingService::class);

        // Load the latest banding for this akreditasi (any status)
        $latestBanding = \App\Models\Banding::where('akreditasi_id', $this->akreditasi->id)
            ->latest()
            ->first();

        if ($latestBanding) {
            $this->bandingStatus = $latestBanding;
        }

        // Load banding eligibility
        $this->bandingEligibility = $bandingService->checkBandingEligibility($this->akreditasi->id);

        // Load rejection status data
        $rejectionService = app(RejectionService::class);
        $this->rejectionStatus = $rejectionService->getRejectionStatus($this->akreditasi->id);
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

    public function submitPerbaikan()
    {
        Gate::authorize('update', $this->akreditasi);

        $rejectionService = app(RejectionService::class);
        $result = $rejectionService->submitPerbaikan($this->akreditasi->id, Auth::id());

        if (! $result['success']) {
            $this->dispatch(
                'notification-received',
                type: 'error',
                title: 'Gagal',
                message: match ($result['error']) {
                    'no_active_rejection' => 'Tidak ada penolakan aktif untuk pengajuan ini.',
                    'unauthorized' => 'Anda tidak memiliki akses untuk melakukan aksi ini.',
                    default => 'Terjadi kesalahan saat mengirim perbaikan.',
                }
            );

            return;
        }

        // Reload rejection status
        $this->rejectionStatus = $rejectionService->getRejectionStatus($this->akreditasi->id);

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Perbaikan berhasil dikirim. Menunggu review dari asesor.'
        );
    }

    public function uploadKartuKendali()
    {
        Gate::authorize('update', $this->akreditasi);

        if ((int)$this->akreditasi->status !== \App\StateMachine\AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            return;
        }

        $this->validate([
            'kartu_kendali_file' => 'required|file|mimes:pdf,docx|max:5120',
        ], [
            'kartu_kendali_file.required' => 'File Kartu Kendali wajib diunggah.',
            'kartu_kendali_file.mimes' => 'Format file harus PDF atau DOCX.',
            'kartu_kendali_file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $docService = app(\App\Services\AkreditasiDocumentService::class);
            $docService->uploadKartuKendaliForPesantren($this->akreditasi->id, Auth::id(), $this->kartu_kendali_file);
            $this->reset(['kartu_kendali_file']);
            $this->akreditasi->refresh();
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Kartu Kendali berhasil diunggah.');
        } catch (\Throwable $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: 'Upload gagal: ' . $e->getMessage());
        }
    }

    public function submitBanding(): void
    {
        Gate::authorize('update', $this->akreditasi);
        $this->validate(['bandingAlasan' => 'required|string|min:10|max:1000']);
        try {
            $workflowService = app(\App\Services\AkreditasiWorkflowService::class);
            $workflowService->submitBanding($this->akreditasi->id, Auth::id(), $this->bandingAlasan);
            $this->akreditasi->refresh();
            $this->bandingAlasan = '';
            $this->dispatch('close-modal', 'banding-modal');
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Banding berhasil diajukan.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal', message: $e->getMessage());
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

    $ipmItems = [
        'nsp_file' => '1. Izin operasional Kementerian Agama (NSP)',
        'lulus_santri_file' => '2. Pernah meluluskan santri / memiliki santri kelas akhir',
        'kurikulum_file' => '3. Menyelenggarakan kurikulum Dirasah Islamiyah',
        'buku_ajar_file' => '4. Menggunakan buku ajar terbitan LP2 PPM',
    ];

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
@endphp

<x-slot name="header">{{ __('Detail Pengajuan Akreditasi') }}</x-slot>

<x-ui.page
    title="Detail Pengajuan Akreditasi"
    subtitle="{{ $pesantren?->nama_pesantren ?? 'Pesantren' }}"
    class="spm-detail-page"
    x-data="akreditasiPesantren()"
>
    <x-slot:toolbar>
        <x-ui.status-badge :variant="$statusVariant">
            {{ \App\Models\Akreditasi::getStatusLabel($akreditasi->status) }}
        </x-ui.status-badge>

        <x-ui.button :href="route('pesantren.akreditasi')" variant="light">
            <x-ui.icon name="exit-right" class="fs-4 me-1" />
            Kembali
        </x-ui.button>
    </x-slot:toolbar>

    <div class="row g-5 mb-6">
        <div class="col-lg-4">
            <x-ui.stat-card label="Status Pengajuan" value="{{ \App\Models\Akreditasi::getStatusLabel($akreditasi->status) }}" variant="{{ $statusVariant }}" icon="shield-tick" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Kelengkapan Data" value="{{ ($ipm ? 1 : 0) + ($sdm ? 1 : 0) + (filled($pesantrenEvaluasis) ? 1 : 0) }} Bagian" variant="info" icon="document" />
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Jadwal Visitasi" value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Menunggu Penjadwalan' }}" variant="success" icon="calendar" />
        </div>
    </div>

    <x-akreditasi.workflow-stepper
        :status="$akreditasi->status"
        title="Tahapan Akreditasi LP2M"
        subtitle="Lihat posisi pengajuan pesantren dari pengiriman berkas sampai hasil akhir akreditasi."
        class="mb-6"
    />

    {{-- Rejection Status Section --}}
    @if(!empty($rejectionStatus) && ($rejectionStatus['count'] > 0 || $rejectionStatus['active'] || $rejectionStatus['history']->count() > 0))
        <div class="mb-6">
            {{-- Active rejection: Submit Perbaikan or Menunggu Review --}}
            @if($rejectionStatus['active'] && $rejectionStatus['active']->status === 'pending')
                <x-ui.alert variant="warning" icon="information-2" title="Perbaikan Diperlukan" class="mb-4">
                    <div>
                        Asesor telah menolak beberapa bagian dokumen Anda. Silakan perbaiki bagian yang ditandai, lalu kirim perbaikan.
                        <div class="d-flex align-items-center gap-3 mt-3">
                            <x-ui.badge variant="warning">
                                Penolakan {{ $rejectionStatus['count'] }} dari {{ $rejectionStatus['limit'] }}
                            </x-ui.badge>
                            @if($rejectionStatus['active']->perbaikan_deadline)
                                <x-ui.badge variant="{{ $rejectionStatus['active']->daysUntilDeadline() <= 3 ? 'danger' : 'info' }}">
                                    Sisa waktu: {{ $rejectionStatus['active']->daysUntilDeadline() }} hari
                                </x-ui.badge>
                            @endif
                        </div>
                        <div class="mt-3">
                            <x-ui.button @click="confirmSubmitPerbaikan($wire)" wire:loading.attr="disabled" variant="primary" size="sm">
                                <span wire:loading.remove wire:target="submitPerbaikan">Submit Perbaikan</span>
                                <span wire:loading wire:target="submitPerbaikan">Memproses...</span>
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.alert>
            @elseif($rejectionStatus['history']->where('type', 'asesor')->where('status', 'submitted')->count() > 0)
                <x-ui.alert variant="info" icon="timer" title="Menunggu Review" class="mb-4">
                    <div>
                        Perbaikan Anda telah dikirim dan sedang menunggu review dari asesor.
                        <div class="d-flex align-items-center gap-3 mt-3">
                            <x-ui.badge variant="info">
                                Penolakan {{ $rejectionStatus['count'] }} dari {{ $rejectionStatus['limit'] }}
                            </x-ui.badge>
                        </div>
                    </div>
                </x-ui.alert>
            @endif

            {{-- Admin Final Rejection Detail --}}
            @if((int) $akreditasi->status === -1)
                @php
                    $adminFinalRejection = $rejectionStatus['history']->where('type', 'admin_final')->first();
                @endphp
                @if($adminFinalRejection)
                    <x-ui.section-card title="Detail Penolakan Final" subtitle="Penolakan oleh Admin pada tahap Validasi." class="mb-4">
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
                @else
                    @php
                        $limitRejection = $rejectionStatus['history']->where('status', 'limit_reached')->first();
                        $expiredRejection = $rejectionStatus['history']->where('status', 'expired')->first();
                    @endphp
                    @if($limitRejection)
                        <x-ui.alert variant="danger" icon="cross-circle" title="Ditolak Otomatis - Batas Penolakan Tercapai" class="mb-4">
                            Pengajuan ditolak secara otomatis karena batas maksimum penolakan ({{ $rejectionStatus['limit'] }}x) telah tercapai.
                        </x-ui.alert>
                    @elseif($expiredRejection)
                        <x-ui.alert variant="danger" icon="cross-circle" title="Ditolak Otomatis - Batas Waktu Perbaikan Terlewat" class="mb-4">
                            Pengajuan ditolak secara otomatis karena batas waktu perbaikan telah terlewat.
                        </x-ui.alert>
                    @endif
                @endif
            @endif

            {{-- Rejection History --}}
            @if($rejectionStatus['history']->count() > 0)
                <x-ui.section-card title="Riwayat Penolakan" subtitle="Catatan penolakan dan perbaikan untuk pengajuan ini.">
                    <div class="p-6">
                        <div class="d-flex flex-column gap-4">
                            @foreach($rejectionStatus['history'] as $rejection)
                                <div class="spm-soft-panel">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="fw-semibold">
                                            @if($rejection->type === 'admin_final')
                                                Penolakan Final (Admin)
                                            @else
                                                Penolakan #{{ $rejection->rejection_number }}
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
                <x-ui.tab wire:click="setTab('edpm')" :active="$activeTab === 'edpm'">EDPM</x-ui.tab>
                @if((int) $akreditasi->status === 0 || (int) $akreditasi->status === -1 || $bandingStatus)
                    <x-ui.tab wire:click="setTab('hasil')" :active="$activeTab === 'hasil'">Hasil Penilaian</x-ui.tab>
                @endif
                @if((int) $akreditasi->status === 2)
                    <x-ui.tab wire:click="setTab('kartu')" :active="$activeTab === 'kartu'">Kartu Kendali</x-ui.tab>
                @endif
            </x-ui.tabs>
        </div>

        <div class="spm-detail-tab-content p-6">
            @include('livewire.pages.pesantren.akreditasi-detail.tabs.profil')
            @include('livewire.pages.pesantren.akreditasi-detail.tabs.ipm')
            @include('livewire.pages.pesantren.akreditasi-detail.tabs.sdm')
            @include('livewire.pages.pesantren.akreditasi-detail.tabs.edpm')
            @include('livewire.pages.pesantren.akreditasi-detail.tabs.hasil')
            @include('livewire.pages.pesantren.akreditasi-detail.tabs.kartu-kendali')
        </div>
    </x-ui.card>

    {{-- Banding Modal --}}
    <x-ui.modal name="banding-modal" focusable>
        <x-ui.modal-header title="Ajukan Banding" subtitle="Berikan alasan keberatan Anda terhadap hasil akreditasi." icon="information-2" variant="warning" />
        <x-ui.modal-body>
            <x-ui.form-field label="Alasan Banding" for="bandingAlasan" :error="$errors->first('bandingAlasan')" hint="Minimal 10 karakter, maksimal 1000 karakter.">
                <x-ui.textarea model="bandingAlasan" id="bandingAlasan" rows="5" placeholder="Jelaskan alasan keberatan Anda..." />
            </x-ui.form-field>
        </x-ui.modal-body>
        <x-ui.modal-footer>
            <x-ui.button type="button" variant="light" x-on:click="$dispatch('close-modal', 'banding-modal')">Batal</x-ui.button>
            <x-ui.button type="button" variant="warning" wire:click="submitBanding" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submitBanding">Ajukan Banding</span>
                <span wire:loading wire:target="submitBanding">Memproses...</span>
            </x-ui.button>
        </x-ui.modal-footer>
    </x-ui.modal>
</x-ui.page>
