<?php

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\Ipm;
use App\Models\SdmPesantren;
use App\Models\Edpm;
use App\Models\MasterEdpmButir;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;
    use \Livewire\WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortAsc = false;
    public $selectedAkreditasiNotes;
    #[Url]
    public $periodeFilter = '';
    #[Url]
    public $statusFilter = '';
    #[Url]
    public $tahapanFilter = '';
    #[Url]
    public $focus = '';

    public function openCatatanModal($id)
    {
        $this->selectedAkreditasiNotes = Akreditasi::with(['catatans.user'])->find($id);
        $this->dispatch('open-modal', 'catatan-modal');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortAsc = true;
        }

        $this->sortField = $field;
    }

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isPesantren()) {
            abort(403);
        }

        $requestedFocus = request()->query('focus');
        $canonicalFocus = in_array($requestedFocus, ['sertifikat', 'banding'], true)
            ? 'hasil'
            : $requestedFocus;

        if (
            in_array($canonicalFocus, ['perbaikan', 'kartu_kendali', 'hasil'], true)
            && ($requestedFocus !== $canonicalFocus || request()->has('statusFilter') || request()->has('tahapanFilter'))
        ) {
            $this->redirect(route('pesantren.akreditasi', ['focus' => $canonicalFocus]), navigate: true);
            return;
        }
    }

    public function getAkreditasisProperty()
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        $effectiveStatusFilter = match ($this->focus) {
            'perbaikan' => '-1',
            'kartu_kendali' => '2',
            'hasil', 'sertifikat', 'banding' => 'hasil_akhir',
            default => $this->statusFilter,
        };

        return $pesantrenService->getAkreditasis(
            Auth::id(),
            $this->search,
            $this->periodeFilter,
            $effectiveStatusFilter,
            $this->tahapanFilter,
            $this->perPage,
            $this->sortField,
            $this->sortAsc
        );
    }

    public function create(): void
    {
        try {
            $workflowService = app(\App\Services\AkreditasiWorkflowService::class);
            $workflowService->submitPengajuan(Auth::id());
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Pengajuan akreditasi berhasil dibuat. Data profil telah dikunci.');
        } catch (\DomainException $e) {
            $this->dispatch('show-validation-alert', title: 'Gagal Membuat Pengajuan', html: e($e->getMessage()));
        }
    }

    public function delete($id)
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        $success = $pesantrenService->deleteSubmission($id, Auth::id());

        if (!$success) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal!', message: 'Tidak dapat menghapus pengajuan ini. Pastikan status masih Pengajuan dan Anda adalah pemiliknya.');
            return;
        }

        $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Pengajuan akreditasi berhasil dihapus. Data profil telah dibuka kunci.');
    }

    public function cancelSubmission($id)
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        $result = $pesantrenService->cancelSubmission($id, Auth::id());

        if ($result) {
            $this->dispatch('notification-received', type: 'success', title: 'Dibatalkan!', message: 'Pengajuan akreditasi telah berhasil dibatalkan.');
        } else {
            $this->dispatch('show-metronic-alert', type: 'error', title: 'Gagal', message: 'Pengajuan tidak dapat dibatalkan. Mungkin sudah dibatalkan sebelumnya.');
        }
    }

    public function banding($id, $alasan): void
    {
        try {
            \Illuminate\Support\Facades\Validator::make(
                ['alasan' => $alasan],
                ['alasan' => 'required|string|min:10|max:1000'],
                [
                    'alasan.required' => 'Alasan banding wajib diisi.',
                    'alasan.min' => 'Alasan banding minimal 10 karakter.',
                    'alasan.max' => 'Alasan banding maksimal 1000 karakter.',
                ]
            )->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Validasi Gagal!', message: collect($e->errors())->flatten()->first());
            return;
        }

        try {
            $workflowService = app(\App\Services\AkreditasiWorkflowService::class);
            $akreditasi = \App\Models\Akreditasi::withTrashed()->find($id);
            if (!$akreditasi) {
                $this->dispatch('notification-received', type: 'error', title: 'Gagal!', message: 'Akreditasi tidak ditemukan.');
                return;
            }
            $workflowService->submitBanding($akreditasi->id, Auth::id(), $alasan);
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Pengajuan banding berhasil dikirim.');
        } catch (\DomainException $e) {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal!', message: $e->getMessage());
        }
    }
}; ?>

<div x-data="akreditasiPesantren" data-module-page="pesantren-akreditasi">
    @php
        $akreditasiRecords = $this->akreditasis;
        $akreditasiCollection = method_exists($akreditasiRecords, 'getCollection')
            ? $akreditasiRecords->getCollection()
            : collect($akreditasiRecords);
        $totalPengajuan = method_exists($akreditasiRecords, 'total')
            ? $akreditasiRecords->total()
            : $akreditasiCollection->count();
        $requestedFocus = in_array($focus, ['perbaikan', 'kartu_kendali', 'hasil', 'sertifikat', 'banding'], true)
            ? $focus
            : 'pengajuan';
        $activeFocus = in_array($requestedFocus, ['sertifikat', 'banding'], true)
            ? 'hasil'
            : $requestedFocus;
        $pengajuanProses = $akreditasiCollection->whereNotIn('status', [0, -1, -2])->count();
        $pengajuanDitolak = $akreditasiCollection->where('status', -1)->count();
        $nilaiRataRata = $akreditasiCollection->whereNotNull('nilai')->avg('nilai');

        $context = match ($activeFocus) {
            'perbaikan' => [
                'title' => 'Status Perbaikan',
                'subtitle' => 'Pantau catatan penolakan, bagian yang harus diperbaiki, dan tindak lanjut berikutnya.',
                'hero' => 'Status Perbaikan',
                'heroSubtitle' => 'Menu ini khusus untuk membaca berkas yang ditolak dan tindakan koreksi berikutnya.',
                'tableTitle' => 'Daftar Perbaikan',
                'tableSubtitle' => 'Fokus pada catatan penolakan, bagian perbaikan, dan status tindak lanjut.',
                'variant' => 'warning',
                'metrics' => [
                    ['label' => 'Total Ditolak', 'value' => $totalPengajuan, 'variant' => 'warning', 'description' => 'Pengajuan yang masuk ke status ditolak.'],
                    ['label' => 'Ada Catatan', 'value' => $akreditasiCollection->filter(fn ($item) => $item->catatans->isNotEmpty())->count(), 'variant' => 'info', 'description' => 'Memiliki catatan yang perlu dibaca pesantren.'],
                    ['label' => 'Belum Banding', 'value' => $akreditasiCollection->filter(fn ($item) => $item->bandings->isEmpty())->count(), 'variant' => 'danger', 'description' => 'Belum ada tindak lanjut banding pada penolakan final.'],
                ],
                'steps' => ['Baca catatan penolakan', 'Perbaiki bagian yang diminta', 'Ajukan banding bila penolakan final perlu ditinjau'],
            ],
            'kartu_kendali' => [
                'title' => 'Kartu Kendali Visitasi',
                'subtitle' => 'Unggah dan pantau kartu kendali setelah proses visitasi selesai.',
                'hero' => 'Kartu Kendali Visitasi',
                'heroSubtitle' => 'Menu ini hanya relevan untuk pengajuan pasca visitasi yang menunggu kartu kendali.',
                'tableTitle' => 'Daftar Kartu Kendali',
                'tableSubtitle' => 'Fokus pada status kartu kendali, jadwal visitasi, dan aksi unggah dokumen.',
                'variant' => 'info',
                'metrics' => [
                    ['label' => 'Penilaian Pasca Visitasi', 'value' => $totalPengajuan, 'variant' => 'info', 'description' => 'Pengajuan pada tahap penilaian pasca visitasi.'],
                    ['label' => 'Menunggu Kartu', 'value' => $akreditasiCollection->whereNull('kartu_kendali')->count(), 'variant' => 'warning', 'description' => 'Belum ada kartu kendali yang tersimpan.'],
                    ['label' => 'Kartu Tersimpan', 'value' => $akreditasiCollection->whereNotNull('kartu_kendali')->count(), 'variant' => 'success', 'description' => 'Kartu kendali sudah diunggah.'],
                ],
                'steps' => ['Unduh template bila tersedia', 'Unggah kartu kendali final', 'Tunggu review admin'],
            ],
            'hasil' => [
                'title' => 'Hasil Akhir Akreditasi',
                'subtitle' => 'Lihat nilai akhir, rekomendasi, sertifikat, dan status banding dalam satu tempat.',
                'hero' => 'Hasil Akhir Akreditasi',
                'heroSubtitle' => 'Satu ruang hasil akhir agar pesantren tidak berpindah ke menu yang hanya berbeda filter.',
                'tableTitle' => 'Daftar Hasil Akhir',
                'tableSubtitle' => 'Fokus pada keputusan akhir, dokumen sertifikat, dan tindak lanjut banding bila ada.',
                'variant' => 'success',
                'metrics' => [
                    ['label' => 'Hasil Terbit', 'value' => $akreditasiCollection->where('status', 0)->count(), 'variant' => 'success', 'description' => 'Pengajuan yang sudah memiliki keputusan akhir.'],
                    ['label' => 'Sertifikat Tersedia', 'value' => $akreditasiCollection->whereNotNull('sertifikat_path')->count(), 'variant' => 'primary', 'description' => 'Sertifikat yang sudah dapat diunduh.'],
                    ['label' => 'Banding Aktif', 'value' => $akreditasiCollection->where('status', -2)->count(), 'variant' => 'warning', 'description' => 'Pengajuan yang sedang berada di proses banding.'],
                ],
                'steps' => ['Baca nilai akhir dan rekomendasi', 'Unduh sertifikat bila sudah terbit', 'Pantau status banding pada baris yang sama'],
            ],
            default => [
                'title' => 'Pengajuan Akreditasi',
                'subtitle' => 'Pantau pengajuan, status proses, catatan, dan tindak lanjut akreditasi pesantren.',
                'hero' => 'Ruang Kendali Pengajuan',
                'heroSubtitle' => 'Ikuti progres akreditasi pesantren dari kesiapan data sampai tindak lanjut catatan.',
                'tableTitle' => 'Daftar Pengajuan',
                'tableSubtitle' => 'Daftar umum seluruh pengajuan akreditasi pesantren Anda.',
                'variant' => 'primary',
                'metrics' => [
                    ['label' => 'Total Pengajuan', 'value' => $totalPengajuan, 'variant' => 'primary', 'description' => 'Riwayat pengajuan akreditasi yang tampil pada daftar.'],
                    ['label' => 'Sedang Diproses', 'value' => $pengajuanProses, 'variant' => 'info', 'description' => 'Pengajuan yang masih diproses atau belum ditolak.'],
                    ['label' => 'Perlu Tindak Lanjut', 'value' => $pengajuanDitolak, 'variant' => 'warning', 'description' => 'Cek catatan, lakukan perbaikan bila diminta, atau ajukan banding jika tersedia.'],
                ],
                'steps' => ['Lengkapi data', 'Pantau status', 'Tindak lanjuti catatan'],
            ],
        };

        $emptyTitle = match ($activeFocus) {
            'perbaikan' => 'Belum ada pengajuan yang perlu perbaikan',
            'kartu_kendali' => 'Belum ada kartu kendali yang perlu diunggah',
            'hasil' => 'Belum ada hasil akhir akreditasi',
            default => 'Belum ada data pengajuan akreditasi',
        };

        $tableColspan = match ($activeFocus) {
            'perbaikan', 'kartu_kendali', 'hasil' => 5,
            default => 7,
        };
    @endphp

    <x-slot name="header">{{ $context['title'] }}</x-slot>

    <x-ui.page
        :title="$context['title']"
        :subtitle="$context['subtitle']"
        data-akreditasi-context="{{ $activeFocus }}"
    >
        <x-datatable.layout
            :title="$context['tableTitle']"
            :subtitle="$context['tableSubtitle']"
            :records="$this->akreditasis"
            class="spm-table-shell--pesantren-akreditasi spm-table-shell--pesantren-{{ $activeFocus }}"
        >
            <x-slot name="filters">
                <div class="spm-list-filterbar">
                    <x-datatable.search placeholder="Cari nomor SK atau peringkat..." />

                    <x-ui.filter-select model="periodeFilter" placeholder="Periode">
                            @for($i = date('Y'); $i >= 2024; $i--)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                    </x-ui.filter-select>

                    @if ($activeFocus === 'pengajuan')
                        <x-ui.filter-select
                            model="statusFilter"
                            placeholder="Status"
                            :options="['0' => 'Selesai', '-1' => 'Ditolak', '-2' => 'Banding']"
                        />

                        <x-ui.filter-select
                            model="tahapanFilter"
                            placeholder="Tahapan"
                            :options="['visitasi' => 'Visitasi']"
                        />

                        <x-ui.button x-on:click="confirmCreate" variant="primary" size="sm" icon="plus">
                            Buat Pengajuan
                        </x-ui.button>
                    @endif
                </div>
            </x-slot>

            <x-slot name="thead">
                @if ($activeFocus === 'perbaikan')
                    <x-ui.table-th>Periode</x-ui.table-th>
                    <x-ui.table-th align="center">Status Perbaikan</x-ui.table-th>
                    <x-ui.table-th>Bagian Perbaikan</x-ui.table-th>
                    <x-ui.table-th align="center">Catatan</x-ui.table-th>
                    <x-ui.table-th align="end">Aksi</x-ui.table-th>
                @elseif ($activeFocus === 'kartu_kendali')
                    <x-ui.table-th>Periode</x-ui.table-th>
                    <x-ui.table-th align="center">Tahap</x-ui.table-th>
                    <x-ui.table-th align="center">Status Kartu</x-ui.table-th>
                    <x-ui.table-th align="center">Jadwal Visitasi</x-ui.table-th>
                    <x-ui.table-th align="end">Aksi</x-ui.table-th>
                @elseif ($activeFocus === 'hasil')
                    <x-ui.table-th :min-width="false" class="spm-year-col">Periode</x-ui.table-th>
                    <x-ui.table-th class="spm-result-col">Hasil & Rekomendasi</x-ui.table-th>
                    <x-ui.table-th align="center" class="spm-certificate-col">Sertifikat</x-ui.table-th>
                    <x-ui.table-th align="center" class="spm-appeal-col">Status Banding</x-ui.table-th>
                    <x-ui.table-th align="end" class="spm-action-col">Aksi</x-ui.table-th>
                @else
                    <x-ui.table-th>Periode</x-ui.table-th>
                    <x-ui.table-th align="center">Status</x-ui.table-th>
                    <x-ui.table-th align="center">Tahap Akreditasi</x-ui.table-th>
                    <x-ui.table-th align="center">Nilai</x-ui.table-th>
                    <x-ui.table-th align="center">Peringkat</x-ui.table-th>
                    <x-ui.table-th align="center">Catatan</x-ui.table-th>
                    <x-ui.table-th align="end">Aksi</x-ui.table-th>
                @endif
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->akreditasis as $index => $item)
                @php
                    $latestCatatan = $item->catatans->sortByDesc('created_at')->first();
                    $latestBanding = $item->bandings->sortByDesc('created_at')->first();
                    $perbaikanList = $latestCatatan && $latestCatatan->perbaikan
                        ? collect(explode(', ', $latestCatatan->perbaikan))->filter()->values()
                        : collect();
                    $recommendation = $item->catatan_rekomendasi_admin
                        ?: ($item->catatan_visitasi ?: ($latestCatatan?->catatan ?: '-'));
                @endphp
                <tr wire:key="akred-{{ $item->id }}">
                    <td class="spm-year-cell">
                        <span class="text-gray-900 fw-semibold fs-6">{{ $item->created_at->format('Y') }}</span>
                    </td>

                    @if ($activeFocus === 'perbaikan')
                        <td class="text-center">
                            <x-ui.status-badge :variant="$item->bandings->isNotEmpty() ? 'info' : 'warning'">
                                {{ $item->bandings->isNotEmpty() ? 'Banding Diajukan' : 'Menunggu Tindak Lanjut' }}
                            </x-ui.status-badge>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                @forelse ($perbaikanList as $perbaikan)
                                    <x-ui.badge variant="warning">{{ $perbaikan }}</x-ui.badge>
                                @empty
                                    <span class="text-muted fw-semibold fs-7">Bagian perbaikan belum dirinci.</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="text-center">
                            <x-ui.button wire:click="openCatatanModal({{ $item->id }})" variant="light" size="sm">
                                {{ $item->catatans->count() > 0 ? $item->catatans->count() . ' Catatan' : 'Catatan' }}
                            </x-ui.button>
                        </td>
                    @elseif ($activeFocus === 'kartu_kendali')
                        <td class="text-center">
                            <x-ui.status-badge variant="info">{{ \App\Models\Akreditasi::getStatusLabel($item->status) }}</x-ui.status-badge>
                        </td>
                        <td class="text-center">
                            <x-ui.status-badge :variant="$item->kartu_kendali ? 'success' : 'warning'">
                                {{ $item->kartu_kendali ? 'Kartu Tersimpan' : 'Belum Diunggah' }}
                            </x-ui.status-badge>
                        </td>
                        <td class="text-center">
                            <span class="fw-semibold text-gray-800">
                                {{ $item->tgl_visitasi ? \Carbon\Carbon::parse($item->tgl_visitasi)->format('d M Y') : '-' }}
                            </span>
                        </td>
                    @elseif ($activeFocus === 'hasil')
                        <td class="spm-result-cell">
                            <div class="d-flex flex-column gap-2 min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="fw-semibold text-success fs-5">{{ $item->nilai ?: '-' }}</span>
                                    @if($item->peringkat)
                                        <x-ui.status-badge variant="success">
                                            {{ $item->peringkat == 'Unggul' ? 'Unggul / Mumtaz' : $item->peringkat }}
                                        </x-ui.status-badge>
                                    @else
                                        <x-ui.status-badge variant="secondary">Belum Ada Peringkat</x-ui.status-badge>
                                    @endif
                                </div>
                                <div class="text-gray-700 fw-semibold fs-7 spm-result-recommendation">
                                    {{ $recommendation }}
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2 text-muted fs-8 fw-semibold">
                                    <span>SK: {{ $item->nomor_sk ?: '-' }}</span>
                                    <span class="bullet bullet-dot bg-gray-400"></span>
                                    <span>Masa berlaku: {{ $item->masa_berlaku_akhir ? \Carbon\Carbon::parse($item->masa_berlaku_akhir)->format('d M Y') : '-' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center spm-certificate-cell">
                            @if ($item->sertifikat_path)
                                <x-ui.button href="{{ Storage::url($item->sertifikat_path) }}" target="_blank" variant="light-success" size="sm" icon="file-down" class="spm-table-compact-action">
                                    Unduh Sertifikat
                                </x-ui.button>
                            @else
                                <x-ui.status-badge variant="secondary">Belum Terbit</x-ui.status-badge>
                            @endif
                        </td>
                        <td class="text-center spm-appeal-cell">
                            @if($latestBanding)
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <x-ui.status-badge variant="warning">
                                        {{ str_replace('_', ' ', ucfirst($latestBanding->status)) }}
                                    </x-ui.status-badge>
                                    <div class="text-muted fw-semibold fs-8 mw-200px text-truncate">
                                        {{ $latestBanding->keputusan ?: $latestBanding->alasan }}
                                    </div>
                                </div>
                            @elseif((int) $item->status === -2)
                                <x-ui.status-badge variant="warning">Banding</x-ui.status-badge>
                            @else
                                <x-ui.status-badge variant="secondary">Tidak Ada</x-ui.status-badge>
                            @endif
                        </td>
                    @else
                        <td class="text-center">
                            @if($item->status == 0)
                                @if($item->masa_berlaku_akhir && \Carbon\Carbon::parse($item->masa_berlaku_akhir)->isPast())
                                    <x-ui.status-badge variant="danger">Masa Berlaku Habis</x-ui.status-badge>
                                @else
                                    <x-ui.status-badge variant="success">Selesai</x-ui.status-badge>
                                @endif
                            @elseif($item->status == -1)
                                <x-ui.status-badge variant="danger">Ditolak</x-ui.status-badge>
                            @elseif($item->status == -2)
                                <x-ui.status-badge variant="warning">Banding</x-ui.status-badge>
                            @else
                                <x-ui.status-badge variant="primary">Proses Akreditasi</x-ui.status-badge>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($item->status == 0 || $item->status == -1)
                                <x-ui.status-badge variant="success">Selesai</x-ui.status-badge>
                            @elseif($item->status == 4 && $item->catatans->whereNotNull('perbaikan')->filter(fn($c) => !empty($c->perbaikan))->isNotEmpty())
                                <x-ui.status-badge variant="warning">Perlu Perbaikan Dokumen</x-ui.status-badge>
                            @elseif($item->tgl_visitasi)
                                <x-ui.status-badge variant="info">Visitasi Dijadwalkan</x-ui.status-badge>
                            @else
                                <x-ui.status-badge variant="secondary">-</x-ui.status-badge>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="fw-semibold {{ $item->nilai ? 'text-success fs-5' : 'text-muted' }}">{{ $item->nilai ?: '-' }}</span>
                        </td>
                        <td class="text-center">
                            @if($item->peringkat)
                                <x-ui.status-badge variant="success">
                                    {{ $item->peringkat == 'Unggul' ? 'Unggul / Mumtaz' : $item->peringkat }}
                                </x-ui.status-badge>
                            @else
                                <span class="text-muted fw-semibold">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <x-ui.button wire:click="openCatatanModal({{ $item->id }})" variant="light" size="sm">
                                <x-ui.icon name="document" class="fs-5 me-1" />
                                {{ $item->catatans->count() > 0 ? $item->catatans->count() . ' Catatan' : 'Catatan' }}
                            </x-ui.button>
                        </td>
                    @endif

                    <td class="text-end spm-action-cell">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item :href="route('pesantren.akreditasi-detail', $item->uuid)">
                                <x-ui.icon name="eye" class="fs-5 text-gray-500" />
                                Lihat Detail
                            </x-ui.action-menu-item>

                            @if ($item->status == 2 && !$item->kartu_kendali)
                                <x-ui.action-menu-item
                                    :href="route('pesantren.akreditasi-detail', ['uuid' => $item->uuid, 'activeTab' => 'kartu'])"
                                    variant="success"
                                >
                                    <x-ui.icon name="arrow-up" class="fs-5" />
                                    Unggah Kartu Kendali
                                </x-ui.action-menu-item>
                            @endif

                            @if ($item->status == 0 && $item->sertifikat_path)
                                <x-ui.action-menu-item href="{{ Storage::url($item->sertifikat_path) }}" target="_blank" variant="success">
                                    <x-ui.icon name="document" class="fs-5" />
                                    Download Sertifikat
                                </x-ui.action-menu-item>
                            @endif

                            @if ($item->status == 6)
                                <x-ui.action-menu-item
                                    variant="danger"
                                    x-on:click="confirmCancel({{ $item->id }}, '{{ $item->created_at->format('Y') }}')"
                                >
                                    <x-ui.icon name="cross-circle" class="fs-5" />
                                    Batalkan Pengajuan
                                </x-ui.action-menu-item>
                            @endif

                        </x-ui.action-menu>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $tableColspan }}">
                        <x-ui.empty-state :title="$emptyTitle" class="py-15" />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.page>

    <!-- Modal Catatan (View Only) -->
    <x-ui.modal name="catatan-modal" focusable>
        @if($selectedAkreditasiNotes)
            @php
            $latestCatatan = $selectedAkreditasiNotes->catatans->sortByDesc('created_at')->first();
            $isRejection = $latestCatatan && !empty($latestCatatan->perbaikan);
            @endphp

            <x-ui.modal-header
                :title="$isRejection ? 'Catatan Penolakan Visitasi' : 'Catatan Penerimaan Visitasi'"
                subtitle="Riwayat catatan dan tindak lanjut pengajuan ini."
                :icon="$isRejection ? 'warning-2' : 'document'"
                :variant="$isRejection ? 'warning' : 'success'"
            />

            <x-ui.modal-body class="spm-modal-content-scroll">
                <div class="d-flex flex-column gap-8 pe-2 custom-scrollbar">
                    @forelse($selectedAkreditasiNotes->catatans->sortByDesc('created_at') as $catatan)
                    @php
                    $isNoteRejection = !empty($catatan->perbaikan);
                    @endphp
                    <div class="border-bottom border-gray-200 pb-8">
                        <div class="d-flex align-items-center gap-4 mb-6">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($catatan->user->name) }}&color=1e3a5f&background=f1f5f9"
                                loading="lazy"
                                class="w-40px h-40px rounded-3 border border-white shadow-sm object-cover" alt="Asesor">
                            <div>
                                <h3 class="fs-6 fw-semibold text-gray-900">{{ $catatan->user->name }}</h3>
                                <p class="text-muted fs-8 fw-semibold text-uppercase">
                                    {{ $catatan->user->isAsesor() ? 'Ketua Asesor' : ($catatan->user->isAdmin() ? 'Administrator Pusat' : 'Pihak Berwenang') }}
                                </p>
                            </div>
                        </div>

                        <div class="row g-4 mb-6">
                            <div class="col-6">
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-2">Status:</p>
                                @if($isNoteRejection)
                                <x-ui.badge variant="warning">Perlu Perbaikan Dokumen</x-ui.badge>
                                @else
                                <x-ui.badge variant="success">Visitasi Dijadwalkan</x-ui.badge>
                                @endif
                            </div>
                            <div class="col-6">
                                <p class="text-muted fs-8 fw-semibold text-uppercase mb-2">
                                    {{ $isNoteRejection ? 'Tanggal Review:' : 'Jadwal Visitasi:' }}
                                </p>
                                <p class="fs-7 fw-semibold text-gray-700">
                                    @if($isNoteRejection)
                                    {{ $catatan->created_at->translatedFormat('d F Y') }}
                                    @else
                                    @if($selectedAkreditasiNotes->tgl_visitasi)
                                    {{ \Carbon\Carbon::parse($selectedAkreditasiNotes->tgl_visitasi)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($selectedAkreditasiNotes->tgl_visitasi_akhir)->format('d/m/y') }}
                                    @else
                                    {{ $catatan->created_at->translatedFormat('d F Y') }}
                                    @endif
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($isNoteRejection)
                        <div class="mb-6">
                            <p class="fs-7 fw-semibold text-gray-800 mb-3">Dokumen yang memerlukan perbaikan</p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(explode(', ', $catatan->perbaikan) as $p)
                                <x-ui.badge variant="warning" class="text-uppercase">
                                    <x-ui.icon name="document" class="fs-7 me-1" />{{ $p }}
                                </x-ui.badge>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="rounded-4 p-6 {{ $isNoteRejection ? 'bg-light-warning' : 'bg-light-success' }}">
                            <div class="fs-7 fw-medium text-gray-700">
                                {!! nl2br(e($catatan->catatan)) !!}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12">
                        <x-ui.icon name="document" class="fs-3x text-gray-300 mb-3 d-block mx-auto" />
                        <p class="text-muted fw-semibold fs-7">Tidak ada catatan ditemukan.</p>
                    </div>
                    @endforelse
                </div>
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Tutup</x-ui.button>
            </x-ui.modal-footer>
        @endif
    </x-ui.modal>
</div>
