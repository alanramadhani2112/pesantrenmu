<?php

use App\Services\BandingService;
use App\Services\PesantrenService;
use App\Services\RejectionService;
use App\Services\ResubmissionService;
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

    #[Url]
    public $activeTab = 'profil';

    public $kartu_kendali_file;

    public $visitasiTemplate;

    public $resubmissionStatus = [];

    public $bandingStatus = null;

    public $bandingEligibility = [];

    // Rejection status data
    public $rejectionStatus = [];

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

        // Load resubmission status when akreditasi is rejected (status 2)
        if ((int) $this->akreditasi->status === 2) {
            $resubmissionService = app(ResubmissionService::class);
            $this->resubmissionStatus = $resubmissionService->getResubmissionStatus($this->akreditasi->id);
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

        $pesantrenService = app(\App\Services\PesantrenService::class);

        if ($this->akreditasi->status != 3) {
            return;
        }

        $this->validate([
            'kartu_kendali_file' => 'required|file|mimes:pdf,docx|max:5120',
        ], [
            'kartu_kendali_file.required' => 'File Kartu Kendali wajib diunggah.',
            'kartu_kendali_file.mimes' => 'Format file harus PDF atau DOCX.',
            'kartu_kendali_file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        $path = $this->kartu_kendali_file->store('akreditasi/kartu_kendali', 'public');

        $success = $pesantrenService->uploadKartuKendali($this->akreditasi->id, Auth::id(), $path);

        if (! $success) {
            $this->dispatch(
                'notification-received',
                type: 'error',
                title: 'Gagal',
                message: 'Anda tidak memiliki akses ke pengajuan ini.'
            );

            return;
        }

        $this->reset(['kartu_kendali_file']);

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Kartu Kendali berhasil diunggah.'
        );
    }

    public function resubmit()
    {
        Gate::authorize('update', $this->akreditasi);

        $pesantrenService = app(PesantrenService::class);
        $resubmissionService = app(ResubmissionService::class);

        $result = $pesantrenService->createSubmission(Auth::id(), $this->akreditasi->id);

        if ($result === null) {
            // Get the eligibility info to determine the error message
            $eligibility = $resubmissionService->checkResubmissionEligibility($this->akreditasi->id);

            if (! $eligibility['allowed'] && $eligibility['error_code']) {
                $message = $resubmissionService->getErrorMessage($eligibility['error_code'], $eligibility['error_data']);
            } else {
                $message = 'Pengajuan ulang gagal. Silakan periksa kelengkapan data Anda.';
            }

            $this->dispatch(
                'notification-received',
                type: 'error',
                title: 'Gagal',
                message: $message
            );

            return;
        }

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Pengajuan ulang berhasil dibuat.'
        );

        $this->redirect(route('pesantren.akreditasi-detail', $result->uuid), navigate: true);
    }
}; ?>

@php
    $statusVariant = match ((int) $akreditasi->status) {
        1 => 'success',
        2 => 'danger',
        3 => 'warning',
        4 => 'info',
        default => 'primary',
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

<x-slot name="header">{{ __('Akreditasi Detail') }}</x-slot>

<x-ui.page
    title="Detail Pengajuan Akreditasi"
    subtitle="{{ $pesantren?->nama_pesantren ?? 'Pesantren' }}"
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
            <x-ui.stat-card label="Status Pengajuan" value="{{ \App\Models\Akreditasi::getStatusLabel($akreditasi->status) }}" variant="{{ $statusVariant }}">
                <x-slot:icon><x-ui.icon name="shield-tick" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Kelengkapan Data" value="{{ ($ipm ? 1 : 0) + ($sdm ? 1 : 0) + (filled($pesantrenEvaluasis) ? 1 : 0) }} Bagian" variant="info">
                <x-slot:icon><x-ui.icon name="document" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>

        <div class="col-lg-4">
            <x-ui.stat-card label="Jadwal Visitasi" value="{{ $akreditasi->tgl_visitasi ? \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d M Y') : 'Menunggu Penjadwalan' }}" variant="success">
                <x-slot:icon><x-ui.icon name="calendar" class="fs-2" /></x-slot:icon>
            </x-ui.stat-card>
        </div>
    </div>

    {{-- Rejection Status Section --}}
    @if(!empty($rejectionStatus) && ($rejectionStatus['count'] > 0 || $rejectionStatus['active'] || $rejectionStatus['history']->count() > 0))
        <div class="mb-6">
            {{-- Active rejection: Submit Perbaikan or Menunggu Review --}}
            @if($rejectionStatus['active'] && $rejectionStatus['active']->status === 'pending')
                <div class="spm-inline-alert mb-4" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 16px;">
                    <x-ui.icon name="information-2" class="fs-2 text-warning" />
                    <div class="flex-grow-1">
                        <div class="spm-inline-alert-title">Perbaikan Diperlukan</div>
                        <div class="spm-inline-alert-text">
                            Asesor telah menolak beberapa bagian dokumen Anda. Silakan perbaiki bagian yang ditandai, lalu kirim perbaikan.
                        </div>
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
                            <x-ui.button wire:click="submitPerbaikan" wire:loading.attr="disabled" variant="primary" size="sm">
                                <span wire:loading.remove wire:target="submitPerbaikan">Submit Perbaikan</span>
                                <span wire:loading wire:target="submitPerbaikan">Memproses...</span>
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            @elseif($rejectionStatus['history']->where('type', 'asesor')->where('status', 'submitted')->count() > 0)
                <div class="spm-inline-alert mb-4" style="background: #d1ecf1; border: 1px solid #17a2b8; border-radius: 8px; padding: 16px;">
                    <x-ui.icon name="timer" class="fs-2 text-info" />
                    <div class="flex-grow-1">
                        <div class="spm-inline-alert-title">Menunggu Review</div>
                        <div class="spm-inline-alert-text">
                            Perbaikan Anda telah dikirim dan sedang menunggu review dari asesor.
                        </div>
                        <div class="d-flex align-items-center gap-3 mt-3">
                            <x-ui.badge variant="info">
                                Penolakan {{ $rejectionStatus['count'] }} dari {{ $rejectionStatus['limit'] }}
                            </x-ui.badge>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Admin Final Rejection Detail --}}
            @if((int) $akreditasi->status === 2)
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
                        <div class="spm-inline-alert mb-4" style="background: #f8d7da; border: 1px solid #dc3545; border-radius: 8px; padding: 16px;">
                            <x-ui.icon name="cross-circle" class="fs-2 text-danger" />
                            <div>
                                <div class="spm-inline-alert-title">Ditolak Otomatis — Batas Penolakan Tercapai</div>
                                <div class="spm-inline-alert-text">
                                    Pengajuan ditolak secara otomatis karena batas maksimum penolakan ({{ $rejectionStatus['limit'] }}x) telah tercapai.
                                </div>
                            </div>
                        </div>
                    @elseif($expiredRejection)
                        <div class="spm-inline-alert mb-4" style="background: #f8d7da; border: 1px solid #dc3545; border-radius: 8px; padding: 16px;">
                            <x-ui.icon name="cross-circle" class="fs-2 text-danger" />
                            <div>
                                <div class="spm-inline-alert-title">Ditolak Otomatis — Batas Waktu Perbaikan Terlewat</div>
                                <div class="spm-inline-alert-text">
                                    Pengajuan ditolak secara otomatis karena batas waktu perbaikan telah terlewat.
                                </div>
                            </div>
                        </div>
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
                                        <div class="fw-bold">
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
        <div class="px-6 pt-5">
            <x-ui.tabs>
                <x-ui.tab wire:click="setTab('profil')" :active="$activeTab === 'profil'">Profil</x-ui.tab>
                <x-ui.tab wire:click="setTab('ipm')" :active="$activeTab === 'ipm'">IPM</x-ui.tab>
                <x-ui.tab wire:click="setTab('sdm')" :active="$activeTab === 'sdm'">SDM</x-ui.tab>
                <x-ui.tab wire:click="setTab('edpm')" :active="$activeTab === 'edpm'">EDPM</x-ui.tab>
                @if($akreditasi->status == 1 || $akreditasi->status == 2 || ($akreditasi->status == 3 && $bandingStatus))
                    <x-ui.tab wire:click="setTab('hasil')" :active="$activeTab === 'hasil'">Hasil Penilaian</x-ui.tab>
                @endif
                @if($akreditasi->status == 3)
                    <x-ui.tab wire:click="setTab('kartu')" :active="$activeTab === 'kartu'">Kartu Kendali</x-ui.tab>
                @endif
            </x-ui.tabs>
        </div>

        <div class="p-6">
            @if ($activeTab === 'profil')
                <div class="d-flex flex-column gap-6">
                    <x-ui.section-card title="Profil Pesantren" subtitle="Identitas pesantren pada pengajuan akreditasi.">
                        <div class="p-6">
                            <div class="row g-5">
                                <x-ui.detail-item label="Nama Pesantren" value="{{ $pesantren->nama_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="NSP" value="{{ $pesantren->ns_pesantren ?? '-' }}" />
                                <x-ui.detail-item label="Alamat" span="2">
                                    <div class="spm-detail-block spm-detail-value-muted">{{ $pesantren->alamat ?? '-' }}</div>
                                </x-ui.detail-item>
                                <x-ui.detail-item label="Kota/Kabupaten" value="{{ $pesantren->kota_kabupaten ?? '-' }}" />
                                <x-ui.detail-item label="Provinsi" value="{{ $pesantren->provinsi ?? '-' }}" />

                                @if($akreditasi->tgl_visitasi)
                                    <x-ui.detail-item label="Jadwal Visitasi" span="2">
                                        <div class="spm-detail-block">
                                            {{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi)->format('d F Y') }}
                                            @if($akreditasi->tgl_visitasi_akhir && $akreditasi->tgl_visitasi != $akreditasi->tgl_visitasi_akhir)
                                                - {{ \Carbon\Carbon::parse($akreditasi->tgl_visitasi_akhir)->format('d F Y') }}
                                            @endif
                                        </div>
                                    </x-ui.detail-item>
                                @endif
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Layanan & Fasilitas" subtitle="Unit layanan pendidikan dan kapasitas sarana.">
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
                                        <x-ui.stat-card label="Total Luas Tanah" value="{{ $pesantren->luas_tanah ?? '-' }} m2" variant="success">
                                            <x-slot:icon><x-ui.icon name="geolocation" class="fs-2" /></x-slot:icon>
                                        </x-ui.stat-card>
                                        <x-ui.stat-card label="Total Luas Bangunan" value="{{ $pesantren->luas_bangunan ?? '-' }} m2" variant="info">
                                            <x-slot:icon><x-ui.icon name="category" class="fs-2" /></x-slot:icon>
                                        </x-ui.stat-card>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.section-card>

                    <x-ui.section-card title="Dokumen Pengajuan" subtitle="Status dokumen pendukung pengajuan.">
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
                </div>
            @endif

            @if ($activeTab === 'ipm')
                <x-ui.section-card title="Indikator Pemenuhan Mutlak" subtitle="Dokumen IPM yang sudah dikirim.">
                    <div class="p-6">
                        <div class="spm-document-list">
                            @foreach ($ipmItems as $field => $label)
                                <x-ui.document-item :label="$label" :href="$ipm && $ipm->$field ? Storage::url($ipm->$field) : null" />
                            @endforeach
                        </div>
                    </div>
                </x-ui.section-card>
            @endif

            @if ($activeTab === 'sdm')
                <x-ui.section-card title="Rekapitulasi Data SDM" subtitle="Rekap santri, ustadz, pamong, musyrif, dan tenaga kependidikan.">
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

            @if ($activeTab === 'edpm')
                <div class="d-flex flex-column gap-6">
                    <x-ui.section-card title="EDPM Pesantren" subtitle="Isian evaluasi diri dan tautan bukti pesantren.">
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
                                                    <x-ui.badge variant="warning">{{ $pesantrenEvaluasis[$butir->id] ?? '-' }}</x-ui.badge>
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

                    <x-ui.section-card title="Catatan Kinerja Satuan Pendidikan" subtitle="Catatan pesantren per komponen.">
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

            @if ($activeTab === 'hasil')
                <div class="d-flex flex-column gap-6">
                    @if($akreditasi->status == 1)
                        <x-ui.section-card title="Hasil Akreditasi Akhir" subtitle="Nilai, peringkat, SK, dan masa berlaku.">
                            <div class="p-6">
                                <div class="row g-5">
                                    <div class="col-md-6"><div class="spm-result-metric"><div class="spm-detail-label">Nilai Akhir</div><div class="fs-2 fw-bold text-success">{{ $akreditasi->nilai }}</div></div></div>
                                    <div class="col-md-6"><div class="spm-result-metric"><div class="spm-detail-label">Peringkat</div><div class="fs-2 fw-bold text-success">{{ $akreditasi->peringkat }}</div></div></div>
                                    <x-ui.detail-item label="Nomor SK" value="{{ $akreditasi->nomor_sk }}" />
                                    <x-ui.detail-item label="Masa Berlaku">
                                        {{ \Carbon\Carbon::parse($akreditasi->masa_berlaku)->format('d F Y') }}
                                        @if($akreditasi->masa_berlaku_akhir && $akreditasi->masa_berlaku != $akreditasi->masa_berlaku_akhir)
                                            - {{ \Carbon\Carbon::parse($akreditasi->masa_berlaku_akhir)->format('d F Y') }}
                                        @endif
                                    </x-ui.detail-item>
                                    @if($akreditasi->sertifikat_path)
                                        <div class="col-12">
                                            <x-ui.button :href="Storage::url($akreditasi->sertifikat_path)" target="_blank" variant="success">Unduh Sertifikat</x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-ui.section-card>
                    @elseif($akreditasi->status == 2)
                        <div class="spm-inline-alert">
                            <x-ui.icon name="cross-circle" class="fs-2 text-danger" />
                            <div>
                                <div class="spm-inline-alert-title">Pengajuan Ditolak</div>
                                <div class="spm-inline-alert-text">Catatan: {{ $akreditasi->catatan }}</div>
                            </div>
                        </div>

                        {{-- Resubmission Status Section --}}
                        @if(!empty($resubmissionStatus))
                            <x-ui.section-card title="Pengajuan Ulang" subtitle="Status dan informasi pengajuan ulang akreditasi.">
                                <div class="p-6">
                                    <div class="d-flex flex-column gap-4">
                                        {{-- Resubmission count/limit --}}
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="fw-semibold">Pengajuan Ulang:</span>
                                            <x-ui.badge variant="{{ $resubmissionStatus['count'] >= $resubmissionStatus['limit'] ? 'danger' : 'info' }}">
                                                {{ $resubmissionStatus['count'] }}/{{ $resubmissionStatus['limit'] }}
                                            </x-ui.badge>
                                        </div>

                                        {{-- Status message --}}
                                        @if($resubmissionStatus['count'] >= $resubmissionStatus['limit'])
                                            <div class="text-danger fw-semibold">
                                                Batas pengajuan ulang telah tercapai
                                            </div>
                                        @elseif($resubmissionStatus['cooling_remaining_days'] > 0)
                                            <div class="text-warning fw-semibold">
                                                Anda dapat mengajukan ulang pada tanggal {{ $resubmissionStatus['cooling_end_date'] }} ({{ $resubmissionStatus['cooling_remaining_days'] }} hari lagi)
                                            </div>
                                        @endif

                                        {{-- Resubmit button --}}
                                        <div>
                                            @if($resubmissionStatus['can_resubmit'])
                                                <x-ui.button wire:click="resubmit" wire:loading.attr="disabled">
                                                    <span wire:loading.remove wire:target="resubmit">Pengajuan Ulang</span>
                                                    <span wire:loading wire:target="resubmit">Memproses...</span>
                                                </x-ui.button>
                                            @else
                                                <x-ui.button disabled
                                                    title="{{ $resubmissionStatus['count'] >= $resubmissionStatus['limit'] ? 'Batas pengajuan ulang telah tercapai' : 'Masa tunggu pengajuan ulang belum berakhir' }}">
                                                    Pengajuan Ulang
                                                </x-ui.button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </x-ui.section-card>
                        @endif
                    @endif

                    {{-- Banding Status Section --}}
                    @if($bandingStatus)
                        <x-ui.section-card title="Status Banding" subtitle="Status dan informasi pengajuan banding akreditasi.">
                            <div class="p-6">
                                <div class="d-flex flex-column gap-4">
                                    {{-- Banding status badge --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fw-semibold">Status:</span>
                                        @php
                                            $bandingVariant = match ($bandingStatus->status) {
                                                'pending' => 'warning',
                                                'under_review' => 'info',
                                                'accepted' => 'success',
                                                'rejected' => 'danger',
                                                default => 'secondary',
                                            };
                                            $bandingLabel = match ($bandingStatus->status) {
                                                'pending' => 'Menunggu',
                                                'under_review' => 'Sedang Direview',
                                                'accepted' => 'Diterima',
                                                'rejected' => 'Ditolak',
                                                default => $bandingStatus->status,
                                            };
                                        @endphp
                                        <x-ui.badge variant="{{ $bandingVariant }}">{{ $bandingLabel }}</x-ui.badge>
                                    </div>

                                    {{-- Submission date --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fw-semibold">Tanggal Pengajuan:</span>
                                        <span>{{ $bandingStatus->created_at->format('d F Y') }}</span>
                                    </div>

                                    {{-- Reason --}}
                                    <div>
                                        <span class="fw-semibold">Alasan Banding:</span>
                                        <div class="mt-1 text-muted">{{ $bandingStatus->alasan }}</div>
                                    </div>

                                    {{-- Decision (when decided) --}}
                                    @if(in_array($bandingStatus->status, ['accepted', 'rejected']))
                                        <div>
                                            <span class="fw-semibold">Keputusan:</span>
                                            <div class="mt-1 text-muted">{{ $bandingStatus->keputusan }}</div>
                                        </div>
                                    @endif

                                    {{-- Link to new akreditasi (when accepted) --}}
                                    @if($bandingStatus->status === 'accepted')
                                        @php
                                            $newAkreditasi = \App\Models\Akreditasi::where('parent', $bandingStatus->akreditasi_id)->latest()->first();
                                        @endphp
                                        @if($newAkreditasi)
                                            <div>
                                                <x-ui.button :href="route('pesantren.akreditasi-detail', $newAkreditasi->uuid)" variant="success" size="sm">
                                                    Lihat Pengajuan Baru
                                                </x-ui.button>
                                            </div>
                                        @endif
                                    @endif

                                    {{-- Remaining appeal count --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fw-semibold">Sisa kesempatan banding:</span>
                                        <x-ui.badge variant="{{ $bandingEligibility['remaining'] <= 0 ? 'danger' : 'info' }}">
                                            {{ $bandingEligibility['remaining'] }}/{{ config('akreditasi.banding_limit') }}
                                        </x-ui.badge>
                                    </div>

                                    {{-- Ajukan Banding button --}}
                                    @if((int) $akreditasi->status === 2)
                                        <div>
                                            @if($bandingEligibility['allowed'])
                                                <x-ui.button :href="route('pesantren.akreditasi', ['uuid' => $akreditasi->uuid])" variant="primary" size="sm">
                                                    Ajukan Banding
                                                </x-ui.button>
                                            @else
                                                <x-ui.button disabled title="Batas pengajuan banding telah tercapai">
                                                    Ajukan Banding
                                                </x-ui.button>
                                                <div class="text-danger fw-semibold mt-2">
                                                    Batas pengajuan banding telah tercapai
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-ui.section-card>
                    @elseif(!empty($bandingEligibility))
                        {{-- Show banding eligibility even when no banding exists yet --}}
                        <x-ui.section-card title="Banding" subtitle="Informasi pengajuan banding akreditasi.">
                            <div class="p-6">
                                <div class="d-flex flex-column gap-4">
                                    {{-- Remaining appeal count --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="fw-semibold">Sisa kesempatan banding:</span>
                                        <x-ui.badge variant="{{ $bandingEligibility['remaining'] <= 0 ? 'danger' : 'info' }}">
                                            {{ $bandingEligibility['remaining'] }}/{{ config('akreditasi.banding_limit') }}
                                        </x-ui.badge>
                                    </div>

                                    {{-- Ajukan Banding button --}}
                                    @if((int) $akreditasi->status === 2)
                                        <div>
                                            @if($bandingEligibility['allowed'])
                                                <x-ui.button :href="route('pesantren.akreditasi', ['uuid' => $akreditasi->uuid])" variant="primary" size="sm">
                                                    Ajukan Banding
                                                </x-ui.button>
                                            @else
                                                <x-ui.button disabled title="Batas pengajuan banding telah tercapai">
                                                    Ajukan Banding
                                                </x-ui.button>
                                                <div class="text-danger fw-semibold mt-2">
                                                    Batas pengajuan banding telah tercapai
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-ui.section-card>
                    @endif

                    <x-ui.section-card title="Data Penilaian" subtitle="Catatan rekomendasi asesor per komponen.">
                        <div class="p-6">
                            <x-ui.simple-table>
                                <thead>
                                    <tr>
                                        <th class="ps-4">Komponen</th>
                                        <th class="pe-4">Catatan Rekomendasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($komponens as $komponen)
                                        <tr>
                                            <td class="ps-4 text-uppercase fw-bold">{{ $komponen->nama }}</td>
                                            <td class="pe-4">{!! $asesorCatatans[$komponen->id] ?? '-' !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-ui.simple-table>
                        </div>
                    </x-ui.section-card>
                </div>
            @endif

            @if ($activeTab === 'kartu')
                <x-ui.section-card title="Kartu Kendali" subtitle="Unduh, tinjau, lalu unggah kembali kartu kendali final.">
                    <div class="p-6">
                        <div class="row g-5">
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    <div class="spm-detail-label">Langkah 1</div>
                                    <div class="spm-detail-value">Unduh template kartu kendali dari menu dokumen.</div>
                                    <x-ui.button :href="route('documents.index', ['doc' => 'kartu_kendali'])" variant="light" size="sm" class="mt-4">Unduh Template</x-ui.button>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    <div class="spm-detail-label">Langkah 2</div>
                                    <div class="spm-detail-value">Tinjau kelengkapan data dan tanda tangan hasil visitasi.</div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="spm-soft-panel h-100">
                                    <div class="spm-detail-label">Langkah 3</div>
                                    @if($akreditasi->status == 3 && $akreditasi->kartu_kendali && !$errors->has('kartu_kendali_file'))
                                        <x-ui.document-item label="Kartu Kendali" :href="Storage::url($akreditasi->kartu_kendali)" />
                                    @elseif($akreditasi->status == 3)
                                        <x-ui.form-field label="Unggah Kartu Kendali" for="kartu_kendali_file" :error="$errors->get('kartu_kendali_file')">
                                            <x-ui.file-upload
                                                model="kartu_kendali_file"
                                                id="kartu_kendali_file"
                                                accept=".pdf,.docx"
                                                :file="$kartu_kendali_file"
                                                placeholder="Pilih file kartu kendali"
                                                hint="PDF/DOCX maksimal 5MB"
                                            />
                                        </x-ui.form-field>

                                        @if($kartu_kendali_file)
                                            <x-ui.button type="button" @click="confirmUploadKartu($wire)" wire:loading.attr="disabled" class="w-100 justify-content-center">
                                                <span wire:loading.remove wire:target="uploadKartuKendali">Simpan Kartu Kendali</span>
                                                <span wire:loading wire:target="uploadKartuKendali">Mengunggah...</span>
                                            </x-ui.button>
                                        @endif
                                    @else
                                        <div class="text-muted fw-semibold fs-7">Menu unggah muncul saat status pengajuan Validasi.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ui.section-card>
            @endif
        </div>
    </x-ui.card>
</x-ui.page>
