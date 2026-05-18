<?php

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\AkreditasiCatatan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    use \Livewire\WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'id';
    public $sortAsc = false;
    public $periodeFilter = '';
    public $statusFilter = '';
    public $selectedAkreditasiNotes;
    public $selectedAssessment;
    public $visitasi_perbaikan = [];

    public function updatedPeriodeFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function openCatatanModal($id)
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);
        $this->selectedAkreditasiNotes = $akreditasiService->findAkreditasiById($id, ['catatans.user']);
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
        if (!auth()->user()->isAsesor()) {
            abort(403);
        }
    }

    public function getAssessmentsProperty()
    {
        $asesor = auth()->user()->asesor;
        if (!$asesor) return collect();

        $asesorService = app(\App\Services\AsesorService::class);
        return $asesorService->getPaginatedAssessments(
            $asesor->id,
            $this->search,
            $this->periodeFilter,
            $this->statusFilter,
            $this->perPage,
            $this->sortField,
            $this->sortAsc
        );
    }


    public $visitasi_akreditasi_id;
    public $visitasi_tanggal;
    public $visitasi_tanggal_akhir;
    public $visitasi_catatan;
    public $visitasi_action = 'terima';

    public function openAturJadwalModal($id)
    {
        $asesorService = app(\App\Services\AsesorService::class);
        $this->selectedAssessment = $asesorService->findAssessment($id);
        
        if ($this->selectedAssessment) {
            $this->visitasi_akreditasi_id = $this->selectedAssessment->akreditasi_id;
            $this->visitasi_tanggal = date('Y-m-d');
            $this->visitasi_tanggal_akhir = date('Y-m-d');
            $this->visitasi_catatan = '';
            $this->visitasi_action = 'terima';
            $this->resetErrorBag();
            $this->dispatch('open-modal', 'atur-jadwal-modal');
        }
    }

    public function openTolakVisitasiModal($id)
    {
        $asesorService = app(\App\Services\AsesorService::class);
        $this->selectedAssessment = $asesorService->findAssessment($id);
        
        if ($this->selectedAssessment) {
            $this->visitasi_akreditasi_id = $this->selectedAssessment->akreditasi_id;
            $this->visitasi_catatan = '';
            $this->visitasi_perbaikan = [];
            $this->visitasi_action = 'tolak';
            $this->resetErrorBag();
            $this->dispatch('open-modal', 'tolak-visitasi-modal');
        }
    }

    public function submitVisitasi()
    {
        $asesorService = app(\App\Services\AsesorService::class);
        $assessment = $this->selectedAssessment;

        if ($this->visitasi_action == 'terima') {
            $this->validate([
                'visitasi_tanggal' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) use ($assessment) {
                         if ($assessment && ($value < $assessment->tanggal_mulai || $value > $assessment->tanggal_berakhir)) {
                            $fail('Tanggal visitasi harus berada dalam rentang assessment (' . \Carbon\Carbon::parse($assessment->tanggal_mulai)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($assessment->tanggal_berakhir)->format('d/m/Y') . ').');
                        }
                    },
                ],
                'visitasi_tanggal_akhir' => [
                    'required',
                    'date',
                    'after_or_equal:visitasi_tanggal',
                    function ($attribute, $value, $fail) use ($assessment) {
                         if ($assessment && ($value < $assessment->tanggal_mulai || $value > $assessment->tanggal_berakhir)) {
                            $fail('Tanggal visitasi akhir harus berada dalam rentang assessment (' . \Carbon\Carbon::parse($assessment->tanggal_mulai)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($assessment->tanggal_berakhir)->format('d/m/Y') . ').');
                        }

                        $start = \Carbon\Carbon::parse($this->visitasi_tanggal);
                        $end = \Carbon\Carbon::parse($value);
                        if ($start->diffInDays($end) >= 4) {
                            $fail('Rentang visitasi maksimal adalah 4 hari.');
                        }
                    },
                ],
            ]);
        } else {
            $this->validate([
                'visitasi_perbaikan' => 'required|array|min:1',
                'visitasi_catatan' => 'required|min:10',
            ]);
        }

        if ($asesorService->processVisitasi($this->visitasi_akreditasi_id, auth()->id(), [
            'tanggal' => $this->visitasi_tanggal,
            'tanggal_akhir' => $this->visitasi_tanggal_akhir,
            'catatan' => $this->visitasi_catatan,
            'perbaikan' => $this->visitasi_perbaikan,
        ], $this->visitasi_action)) {
            $this->dispatch('close-modal', 'atur-jadwal-modal');
            $this->dispatch('close-modal', 'tolak-visitasi-modal');
            $this->js('window.location.reload()');
        }
    }
}; ?>

<div x-data="deleteConfirmation" data-module-page="asesor-akreditasi">
    <x-slot name="header">{{ __('Akreditasi') }}</x-slot>

    <x-ui.page
        title="Akreditasi"
        subtitle="Kelola tugas penilaian, jadwal visitasi, catatan, dan laporan akreditasi pesantren."
    >
        @php
            $assessmentRecords = $this->assessments;
            $assessmentCollection = method_exists($assessmentRecords, 'getCollection')
                ? $assessmentRecords->getCollection()
                : collect($assessmentRecords);
            $totalTugas = method_exists($assessmentRecords, 'total')
                ? $assessmentRecords->total()
                : $assessmentCollection->count();
            $assessmentAktif = $assessmentCollection->filter(fn ($item) => $item->akreditasi && (int) $item->akreditasi->status === 5)->count();
            $visitasiAktif = $assessmentCollection->filter(fn ($item) => $item->akreditasi && in_array((int) $item->akreditasi->status, [3, 4], true))->count();
        @endphp

        <div class="row g-6 mb-6">
            <div class="col-12 col-xl-8">
                <x-ui.card
                    title="Prioritas Tugas Asesor"
                    subtitle="Mulai dari tugas yang sedang aktif, lalu lanjutkan ke instrumen dan laporan visitasi."
                    class="h-100"
                >
                    <div class="row g-4">
                        <div class="col-12 col-md-4">
                            <div class="border border-dashed border-gray-300 rounded-3 p-5 h-100">
                                <x-ui.badge variant="primary" class="mb-4">Total Tugas</x-ui.badge>
                                <div class="fs-2 fw-bold text-gray-900 mb-1">{{ $totalTugas }}</div>
                                <div class="text-muted fw-semibold fs-7">Daftar pengajuan akreditasi yang ditugaskan ke asesor.</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="border border-dashed border-gray-300 rounded-3 p-5 h-100">
                                <x-ui.badge variant="warning" class="mb-4">Penilaian</x-ui.badge>
                                <div class="fs-2 fw-bold text-gray-900 mb-1">{{ $assessmentAktif }}</div>
                                <div class="text-muted fw-semibold fs-7">Tugas yang perlu dilanjutkan ke pengisian instrumen akreditasi.</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="border border-dashed border-gray-300 rounded-3 p-5 h-100">
                                <x-ui.badge variant="info" class="mb-4">Visitasi</x-ui.badge>
                                <div class="fs-2 fw-bold text-gray-900 mb-1">{{ $visitasiAktif }}</div>
                                <div class="text-muted fw-semibold fs-7">Jadwal dan laporan visitasi yang perlu diselesaikan.</div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-12 col-xl-4">
                <x-ui.card
                    title="Alur Kerja Asesor"
                    subtitle="Aksi utama tetap berada di menu tiap baris tugas akreditasi."
                    class="h-100"
                >
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">1</span>
                            <div>
                                <div class="fw-bold text-gray-900">Buka detail</div>
                                <div class="text-muted fs-7">Cek profil pesantren, instrumen, dan catatan sebelum memberi penilaian.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">2</span>
                            <div>
                                <div class="fw-bold text-gray-900">Isi instrumen</div>
                                <div class="text-muted fs-7">Gunakan aksi input nilai saat status pengajuan sudah memungkinkan.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">3</span>
                            <div>
                                <div class="fw-bold text-gray-900">Selesaikan visitasi</div>
                                <div class="text-muted fs-7">Atur jadwal, unggah laporan, dan tindak lanjuti revisi dari menu aksi.</div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-datatable.layout title="Pengajuan Akreditasi" :records="$this->assessments">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Pesantren..." />

                <x-ui.filter-select model="periodeFilter" placeholder="Periode">
                    @for($i = date('Y'); $i >= 2024; $i--)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </x-ui.filter-select>

                <x-ui.filter-select
                    model="statusFilter"
                    placeholder="Status"
                    :options="[
                        'siap' => 'Siap Visitasi',
                        'belum' => 'Belum Visitasi',
                        'revisi' => 'Perlu Revisi',
                        'selesai' => 'Selesai',
                    ]"
                />
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th>Pesantren</x-ui.table-th>
                <x-ui.table-th align="center">Jadwal Assessment</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th align="center">Jadwal Visitasi</x-ui.table-th>
                <x-ui.table-th>Catatan</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->assessments as $index => $item)
                @if($item->akreditasi)
                <tr wire:key="ass-{{ $item->id }}">
                    <td>
                        <span class="text-gray-900 fw-bold fs-6">{{ $item->akreditasi->user?->pesantren?->nama_pesantren ?? $item->akreditasi->user?->name ?? 'N/A' }}</span>
                    </td>
                    <td class="text-center">
                        <span class="text-muted fw-semibold">
                            {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d') }}â€“{{ \Carbon\Carbon::parse($item->tanggal_berakhir)->format('d M Y') }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($item->akreditasi->status == 1)
                        <x-ui.status-badge variant="success">
                            Selesai
                        </x-ui.status-badge>
                        @elseif($item->akreditasi->status == 2)
                        <x-ui.status-badge variant="danger">
                            Di Tolak
                        </x-ui.status-badge>
                        @elseif($item->akreditasi->status == 3)
                        <x-ui.status-badge variant="primary">
                            Validasi
                        </x-ui.status-badge>
                        @elseif($item->akreditasi->tgl_visitasi)
                        <x-ui.status-badge variant="info">
                            Siap Visitasi
                        </x-ui.status-badge>
                        @elseif($item->akreditasi->catatans->whereNotNull('perbaikan')->filter(fn($c) => !empty($c->perbaikan))->isNotEmpty())
                        <x-ui.status-badge variant="danger">
                            Perlu Revisi
                        </x-ui.status-badge>
                        @else
                        <x-ui.status-badge variant="warning">
                            Belum Visitasi
                        </x-ui.status-badge>
                        @endif
                    </td>
                    <td class="text-center text-muted fw-semibold">
                        @if($item->akreditasi->tgl_visitasi)
                        {{ \Carbon\Carbon::parse($item->akreditasi->tgl_visitasi)->format('d') }}â€“{{ \Carbon\Carbon::parse($item->akreditasi->tgl_visitasi_akhir)->format('d M Y') }}
                        @else
                        <span class="text-muted">Belum Dijadwalkan</span>
                        @endif
                    </td>
                    <td>
                        <x-ui.button wire:click="openCatatanModal({{ $item->akreditasi->id }})" variant="light" size="sm">
                            <x-ui.icon name="document" class="fs-5 me-1" />
                            {{ $item->akreditasi->catatans->count() > 0 ? $item->akreditasi->catatans->count() . ' Catatan' : 'Catatan' }}
                        </x-ui.button>
                    </td>
                    <td class="text-end">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item :href="route('asesor.akreditasi-detail', $item->akreditasi->uuid)">
                                <x-ui.icon name="eye" class="fs-5 text-gray-500" />
                                Lihat Detail
                            </x-ui.action-menu-item>

                            @if($item->akreditasi->status == 5 && $item->tipe == 1)
                                <x-ui.action-menu-item wire:click="openAturJadwalModal({{ $item->id }})" variant="primary">
                                    <x-ui.icon name="timer" class="fs-5" />
                                    Atur Jadwal Visitasi
                                </x-ui.action-menu-item>

                                <x-ui.action-menu-item wire:click="openTolakVisitasiModal({{ $item->id }})" variant="danger">
                                    <x-ui.icon name="cross-circle" class="fs-5" />
                                    Tolak Visitasi
                                </x-ui.action-menu-item>
                            @endif

                            @if($item->akreditasi->status == 4 || $item->akreditasi->status == 3 || $item->akreditasi->status == 1 || $item->akreditasi->status == 2)
                                <x-ui.action-menu-item
                                    :href="route('asesor.akreditasi-detail', ['uuid' => $item->akreditasi->uuid, 'activeTab' => 'instrumen'])"
                                    variant="primary"
                                >
                                    <x-ui.icon name="pencil" class="fs-5" />
                                    {{ $item->akreditasi->status == 1 || $item->akreditasi->status == 2 ? 'Lihat Nilai Akreditasi' : 'Input Nilai Akreditasi' }}
                                </x-ui.action-menu-item>
                            @endif

                            @if($item->akreditasi->status == 3 || $item->akreditasi->status == 1 || $item->akreditasi->status == 2)
                                <x-ui.action-menu-item
                                    :href="route('asesor.akreditasi-detail', ['uuid' => $item->akreditasi->uuid, 'activeTab' => 'laporan_visitasi'])"
                                    variant="success"
                                >
                                    <x-ui.icon name="document" class="fs-5" />
                                    {{ $item->akreditasi->status == 3 ? 'Unggah Laporan' : 'Lihat Laporan Visitasi' }}
                                </x-ui.action-menu-item>
                            @endif
                        </x-ui.action-menu>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="6">
                        <x-ui.empty-state title="Belum ada tugas akreditasi ditugaskan" class="py-15" />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.page>

    <!-- Modal Atur Jadwal Visitasi -->
    <x-ui.modal name="atur-jadwal-modal" focusable>
        <form x-on:submit.prevent="confirmAction('submitVisitasi', 'Atur jadwal visitasi?', 'Jadwal visitasi akan disimpan.', 'Ya, atur')">
            <x-ui.modal-header
                title="Atur Jadwal Visitasi"
                subtitle="Tentukan jadwal visitasi pesantren."
                icon="timer"
            />

            <x-ui.modal-body>

            @if($selectedAssessment)
            <div class="bg-light rounded-4 p-6 border border-gray-200 mb-8">
                <div class="mb-4">
                    <p class="text-muted fs-8 fw-bold text-uppercase mb-1">Pesantren</p>
                    <p class="fs-6 fw-bolder text-gray-900">{{ $selectedAssessment->akreditasi->user?->pesantren?->nama_pesantren ?? $selectedAssessment->akreditasi->user?->name }}</p>
                </div>
                <div>
                    <p class="text-muted fs-8 fw-bold text-uppercase mb-1">Jadwal Penilaian</p>
                    <p class="fs-6 fw-bolder text-gray-900">{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_mulai)->format('d') }}–{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_berakhir)->format('d F Y') }}</p>
                </div>
            </div>
            @endif

            <div class="space-y-6">
                <h3 class="fw-bold text-gray-900 fs-6 mb-4">Input Jadwal</h3>
                <div class="row g-5">
                    <div class="col-md-6">
                        <x-ui.form-field label="Tanggal Mulai Visitasi" :error="$errors->get('visitasi_tanggal')">
                            <x-ui.input model="visitasi_tanggal" type="date" />
                        </x-ui.form-field>
                    </div>
                    <div class="col-md-6">
                        <x-ui.form-field label="Tanggal Selesai Visitasi" :error="$errors->get('visitasi_tanggal_akhir')">
                            <x-ui.input model="visitasi_tanggal_akhir" type="date" />
                        </x-ui.form-field>
                    </div>
                </div>

                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4">
                    <div class="fw-semibold text-warning fs-7">
                        Rentang visitasi maksimal 4 hari dan harus berada dalam periode penilaian yang telah ditetapkan oleh Admin Pusat.
                    </div>
                </div>

                <x-ui.form-field label="Catatan Tambahan">
                    <x-ui.textarea
                        model="visitasi_catatan"
                        rows="4"
                        placeholder="Contoh: Koordinasi kedatangan dengan pimpinan pesantren pukul 08.00 WIB."
                    />
                </x-ui.form-field>
            </div>

            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="submit" variant="primary">
                    {{ __('Atur Jadwal Visitasi') }}
                </x-ui.button>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">
                    Batal
                </x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

    <!-- Modal Tolak Visitasi -->
    <x-ui.modal name="tolak-visitasi-modal" focusable>
        <form x-on:submit.prevent="confirmAction('submitVisitasi', 'Tolak visitasi?', 'Pesantren akan menerima catatan perbaikan dokumen.', 'Ya, tolak', 'danger')">
            <x-ui.modal-header
                title="Tolak Visitasi"
                subtitle="Berikan alasan penolakan untuk proses perbaikan dokumen."
                icon="cross-circle"
                variant="danger"
            />

            <x-ui.modal-body>

            @if($selectedAssessment)
            <div class="bg-light rounded-4 p-6 border border-gray-200 mb-8">
                <div class="mb-4">
                    <p class="text-muted fs-8 fw-bold text-uppercase mb-1">Pesantren</p>
                    <p class="fs-6 fw-bolder text-gray-900">{{ $selectedAssessment->akreditasi->user?->pesantren?->nama_pesantren ?? $selectedAssessment->akreditasi->user?->name }}</p>
                </div>
                <div>
                    <p class="text-muted fs-8 fw-bold text-uppercase mb-1">Jadwal Penilaian</p>
                    <p class="fs-6 fw-bolder text-gray-900">{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_mulai)->format('d') }}–{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_berakhir)->format('d F Y') }}</p>
                </div>
            </div>
            @endif

            <div class="space-y-6">
                <x-ui.form-field label="Dokumen yang Memerlukan Perbaikan" :error="$errors->get('visitasi_perbaikan')">
                    <div class="row g-3">
                        @foreach(['Profil Pesantren', 'IPM', 'Data SDM', 'EPDM'] as $doc)
                            <div class="col-6 col-md-3">
                                <x-ui.checkbox model="visitasi_perbaikan" :value="$doc" :label="$doc" />
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text">Minimal satu bagian harus dipilih sebelum melanjutkan.</div>
                </x-ui.form-field>

                <x-ui.form-field label="Alasan Penolakan" :error="$errors->get('visitasi_catatan')">
                    <x-ui.textarea
                        model="visitasi_catatan"
                        rows="4"
                        placeholder="Jelaskan secara spesifik bagian yang perlu diperbaiki."
                    />
                </x-ui.form-field>
            </div>

            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="submit" variant="danger">
                    {{ __('Tolak Visitasi') }}
                </x-ui.button>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">
                    Batal
                </x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

    <!-- Modal Catatan (View Only) -->
    <x-ui.modal name="catatan-modal" focusable>
        <div class="p-0 overflow-hidden rounded-4 spm-modal-content-scroll">
            @if($selectedAkreditasiNotes)
            @php
            $latestCatatan = $selectedAkreditasiNotes->catatans->sortByDesc('created_at')->first();
            $isRejection = $latestCatatan && !empty($latestCatatan->perbaikan);
            @endphp

            <div class="p-8">
                <div class="d-flex justify-content-between align-items-center mb-8">
                    <h2 class="fs-4 fw-bold text-gray-900">
                        {{ $isRejection ? 'Catatan Penolakan Visitasi' : 'Catatan Penerimaan Visitasi' }}
                    </h2>
                    <x-ui.icon-button icon="cross" label="Tutup" variant="light" size="sm" x-on:click="$dispatch('close')" />
                </div>

                <div class="d-flex flex-column gap-8 pe-2 custom-scrollbar">
                    @forelse($selectedAkreditasiNotes->catatans->sortByDesc('created_at') as $catatan)
                    @php
                    $isNoteRejection = !empty($catatan->perbaikan);
                    @endphp
                    <div class="border-bottom border-gray-200 pb-8">
                        <div class="d-flex align-items-center gap-4 mb-6">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($catatan->user->name) }}&color=1e3a5f&background=f1f5f9"
                                class="w-40px h-40px rounded-3 border border-white shadow-sm object-cover" alt="Avatar">
                            <div>
                                <h3 class="fs-6 fw-bolder text-gray-900">{{ $catatan->user->name }}</h3>
                                <p class="text-muted fs-8 fw-bold text-uppercase">
                                    {{ $catatan->user->isAsesor() ? 'Ketua Asesor' : ($catatan->user->isAdmin() ? 'Administrator Pusat' : 'Pihak Berwenang') }}
                                </p>
                            </div>
                        </div>

                        <div class="row g-4 mb-6">
                            <div class="col-6">
                                <p class="text-muted fs-8 fw-bold text-uppercase mb-2">Status:</p>
                                @if($isNoteRejection)
                                <x-ui.badge variant="warning">Perlu Perbaikan Dokumen</x-ui.badge>
                                @else
                                <x-ui.badge variant="success">Visitasi Dijadwalkan</x-ui.badge>
                                @endif
                            </div>
                            <div class="col-6">
                                <p class="text-muted fs-8 fw-bold text-uppercase mb-2">
                                    {{ $isNoteRejection ? 'Tanggal Review:' : 'Jadwal Visitasi:' }}
                                </p>
                                <p class="fs-7 fw-bolder text-gray-700">
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
                            <p class="fs-7 fw-bolder text-gray-800 mb-3">Dokumen yang memerlukan perbaikan</p>
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
            </div>
            @endif
        </div>
    </x-ui.modal>
</div>
