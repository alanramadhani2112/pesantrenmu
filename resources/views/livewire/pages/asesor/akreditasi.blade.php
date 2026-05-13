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
            <div class="bg-gray-50/50 rounded-2xl p-6 border border-slate-100 mb-8">
                <div class="mb-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pesantren</p>
                    <p class="text-sm font-black text-[#1e3a5f]">{{ $selectedAssessment->akreditasi->user?->pesantren?->nama_pesantren ?? $selectedAssessment->akreditasi->user?->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Jadwal Penilaian</p>
                    <p class="text-sm font-black text-[#1e3a5f]">{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_mulai)->format('d') }}â€“{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_berakhir)->format('d F Y') }}</p>
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
            <div class="bg-gray-50/50 rounded-2xl p-6 border border-slate-100 mb-8">
                <div class="mb-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pesantren</p>
                    <p class="text-sm font-black text-[#1e3a5f]">{{ $selectedAssessment->akreditasi->user?->pesantren?->nama_pesantren ?? $selectedAssessment->akreditasi->user?->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Jadwal Assessment</p>
                    <p class="text-sm font-black text-[#1e3a5f]">{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_mulai)->format('d') }}â€“{{ \Carbon\Carbon::parse($selectedAssessment->tanggal_berakhir)->format('d F Y') }}</p>
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
        <div class="p-0 overflow-hidden rounded-3xl spm-modal-content-scroll">
            @if($selectedAkreditasiNotes)
            @php
            $latestCatatan = $selectedAkreditasiNotes->catatans->sortByDesc('created_at')->first();
            $isRejection = $latestCatatan && !empty($latestCatatan->perbaikan);
            @endphp

            <div class="p-8">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-xl font-bold text-[#1e3a5f]">
                        {{ $isRejection ? 'Catatan Penolakan Visitasi' : 'Catatan Penerimaan Visitasi' }}
                    </h2>
                    <x-ui.button type="button" variant="light" size="sm" x-on:click="$dispatch('close')" class="btn-icon btn-active-light-primary text-slate-300 hover:text-slate-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </x-ui.button>
                </div>

                <div class="space-y-8 pr-2 custom-scrollbar">
                    @forelse($selectedAkreditasiNotes->catatans->sortByDesc('created_at') as $catatan)
                    @php
                    $isNoteRejection = !empty($catatan->perbaikan);
                    @endphp
                    <div class="bg-white border-b border-slate-50 last:border-0 pb-8 last:pb-0">
                        <div class="flex items-center gap-4 mb-6">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($catatan->user->name) }}&color=1e3a5f&background=f1f5f9"
                                class="w-12 h-12 rounded-2xl border-2 border-white shadow-sm object-cover" alt="Avatar">
                            <div>
                                <h3 class="text-sm font-black text-[#1e3a5f]">{{ $catatan->user->name }}</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    {{ $catatan->user->isAsesor() ? 'Ketua Asesor' : ($catatan->user->isAdmin() ? 'Administrator Pusat' : 'Pihak Berwenang') }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Status:</p>
                                @if($isNoteRejection)
                                <span class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-tight">Perlu Perbaikan Dokumen</span>
                                @else
                                <span class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-tight">Visitasi Dijadwalkan</span>
                                @endif
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                                    {{ $isNoteRejection ? 'Tanggal Review:' : 'Jadwal Visitasi:' }}
                                </p>
                                <p class="text-[11px] font-black text-slate-700">
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
                            <p class="text-[11px] font-black text-slate-800 mb-3">Dokumen yang memerlukan perbaikan</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach(explode(', ', $catatan->perbaikan) as $p)
                                <div class="flex items-center gap-2 px-3 py-2 bg-amber-500 rounded-xl text-white">
                                    @switch($p)
                                    @case('Profil Pesantren')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @case('IPM')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @case('Data SDM')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @case('EPDM')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @break
                                    @default
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    @endswitch
                                    <span class="text-[10px] font-black uppercase tracking-tight">{{ $p }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="rounded-3xl p-6 {{ $isNoteRejection ? 'bg-amber-50 text-slate-700' : 'bg-emerald-50 text-slate-700' }}">
                            <div class="text-xs leading-relaxed font-medium space-y-4 prose-sm prose-slate max-w-none">
                                {!! nl2br(e($catatan->catatan)) !!}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12">
                        <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-gray-400 font-medium font-bold text-xs text-center">Tidak ada catatan ditemukan.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
    </x-ui.modal>
</div>
