<?php

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\AkreditasiCatatan;
use App\Services\DeadlineService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AkreditasiExport;

new #[Layout('layouts.app')] class extends Component {
    use \Livewire\WithPagination;
    public $akreditasi_id;
    public $asesor_id1;
    public $asesor_id2;
    public $tanggal_mulai;
    public $tanggal_berakhir;
    public $catatan_penolakan;
    public $action_type = 'approve'; // 'approve' or 'reject'
    public $statusFilter = 'pengajuan';
    public $search = '';
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortAsc = false;
    public $selectedAkreditasiNotes;
    public $selectedIds = [];
    public $selectAll = false;

    public function updatedSearch()
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortAsc = true;
        }

        $this->sortField = $field;
        $this->resetSelection();
    }

    public function openCatatanModal($id)
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);
        $this->selectedAkreditasiNotes = $akreditasiService->findAkreditasiById($id, ['catatans.user']);
        $this->dispatch('open-modal', 'catatan-modal');
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedIds = $this->akreditasis->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function updatedSelectedIds()
    {
        $this->selectAll = count($this->selectedIds) > 0 && count($this->selectedIds) === count($this->akreditasis->pluck('id'));
    }

    private function resetSelection()
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->canAccessAdminArea()) {
                    abort(403);
                }
    }

    public function getAkreditasisProperty()
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);

        if ($this->statusFilter === 'overdue') {
            // For overdue filter, get all assessment/visitasi phase items and filter by overdue
            $deadlineService = app(DeadlineService::class);
            $overdueIds = $deadlineService->getOverdueAkreditasi()->pluck('id')->toArray();

            $query = \App\Models\Akreditasi::with(['user.pesantren', 'assessments', 'catatans.user', 'assessment1'])
                ->whereIn('id', $overdueIds);

            if ($this->search) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('pesantren', function ($q2) {
                            $q2->where('nama_pesantren', 'like', '%' . $this->search . '%');
                        });
                });
            }

            return $query->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                ->paginate($this->perPage);
        }

        return $akreditasiService->getPaginatedAkreditasis(
            $this->statusFilter,
            $this->search,
            $this->perPage,
            $this->sortField,
            $this->sortAsc
        );
    }

    public function getCountPengajuanProperty()
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);
        return $akreditasiService->getStatusCounts()['pengajuan'];
    }

    public function getCountAssessmentProperty()
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);
        return $akreditasiService->getStatusCounts()['assessment'];
    }

    public function getCountVisitasiProperty()
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);
        return $akreditasiService->getStatusCounts()['visitasi'];
    }

    public function getCountOverdueProperty()
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);
        return $akreditasiService->getStatusCounts()['overdue'];
    }

    public function getOverdueMapProperty(): array
    {
        $deadlineService = app(DeadlineService::class);
        $overdueAkreditasi = $deadlineService->getOverdueAkreditasi();
        $map = [];
        foreach ($overdueAkreditasi as $akreditasi) {
            // Find the primary assessment (tipe=1) for days overdue calculation
            $primaryAssessment = $akreditasi->assessments->firstWhere('tipe', 1)
                ?? $akreditasi->assessments->first();
            if ($primaryAssessment) {
                $map[$akreditasi->id] = $deadlineService->getDaysOverdue($primaryAssessment);
            }
        }
        return $map;
    }

    public function getAsesorsProperty()
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);
        return $akreditasiService->getAvailableAsesors();
    }

    public function delete($id)
    {
        Gate::authorize('akreditasi.delete');

        $akreditasiService = app(\App\Services\AkreditasiService::class);
        if ($akreditasiService->deleteAkreditasi($id)) {
            session()->flash('status', 'Pengajuan akreditasi berhasil dihapus.');
        } else {
            session()->flash('error', 'Gagal menghapus pengajuan akreditasi.');
        }
    }

    public function openVerifikasiModal($id)
    {
        $this->akreditasi_id = $id;
        $this->asesor_id1 = '';
        $this->asesor_id2 = '';
        $this->tanggal_mulai = '';
        $this->tanggal_berakhir = '';
        $this->catatan_penolakan = '';
        $this->action_type = 'approve';
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'verifikasi-modal');
    }

    public function verifikasi()
    {
        if ($this->action_type === 'approve') {
            Gate::authorize('akreditasi.approve');
        } else {
            Gate::authorize('akreditasi.reject');
        }

        $akreditasiService = app(\App\Services\AkreditasiService::class);

        if ($this->action_type === 'approve') {
            $this->validate([
                'asesor_id1' => 'required',
                'asesor_id2' => 'nullable|different:asesor_id1',
                'tanggal_mulai' => 'required|date',
                'tanggal_berakhir' => 'required|date|after_or_equal:tanggal_mulai',
            ]);

            try {
                $akreditasiService->approvePengajuan($this->akreditasi_id, [
                    'asesor_id1' => $this->asesor_id1,
                    'asesor_id2' => $this->asesor_id2,
                    'tanggal_mulai' => $this->tanggal_mulai,
                    'tanggal_berakhir' => $this->tanggal_berakhir,
                ]);
            } catch (\DomainException $e) {
                session()->flash('error', 'Gagal memverifikasi pengajuan: ' . $e->getMessage() . '.');
                $this->dispatch('close-modal', 'verifikasi-modal');
                return;
            }

            session()->flash('status', 'Pengajuan berhasil diverifikasi. Status berubah menjadi tahap penilaian.');
        } else {
            $this->validate([
                'catatan_penolakan' => 'required|string|min:10',
            ]);

            try {
                $akreditasiService->rejectPengajuan($this->akreditasi_id, $this->catatan_penolakan);
            } catch (\DomainException $e) {
                session()->flash('error', 'Gagal menolak pengajuan: ' . $e->getMessage() . '.');
                $this->dispatch('close-modal', 'verifikasi-modal');
                return;
            }

            session()->flash('status', 'Pengajuan berhasil ditolak (Stop) dan dialihkan untuk perbaikan.');
        }

        $this->dispatch('close-modal', 'verifikasi-modal');
    }

    public function export()
    {
        return Excel::download(new AkreditasiExport($this->statusFilter, $this->search, $this->sortField, $this->sortAsc), 'data-akreditasi-' . $this->statusFilter . '-' . now()->format('Y-m-d') . '.xlsx');
    }
}; ?>

<div data-admin-akreditasi-page="metronic" x-data="{ ...deleteConfirmation(), ...adminManagement() }">
    <x-slot name="header">{{ __('Akreditasi') }}</x-slot>

    <x-ui.index-layout
        title="Akreditasi"
        subtitle="Kelola pengajuan, penilaian, visitasi, dan tindak lanjut pesantren dari satu daftar akreditasi."
    >
        <x-slot name="toolbar">
            <x-ui.badge variant="primary">Admin</x-ui.badge>
            <x-ui.badge variant="warning">Aktif: {{ $this->countPengajuan + $this->countAssessment + $this->countVisitasi }}</x-ui.badge>
            @if($this->countOverdue > 0)
                <x-ui.badge variant="danger">Terlambat: {{ $this->countOverdue }}</x-ui.badge>
            @endif
        </x-slot>

        <div class="row g-6 mb-6">
            <div class="col-12 col-xl-8">
                <x-ui.card
                    title="Prioritas Operasional"
                    subtitle="Gunakan ringkasan ini untuk menentukan antrean pengajuan yang perlu diproses terlebih dahulu."
                    class="h-100"
                >
                    <div class="row g-4">
                        <div class="col-12 col-md-4">
                            <div class="border border-dashed border-gray-300 rounded-3 p-5 h-100">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <x-ui.badge variant="primary">Verifikasi</x-ui.badge>
                                    <span class="fs-2 fw-bold text-gray-900">{{ $this->countPengajuan }}</span>
                                </div>
                                <div class="text-muted fw-semibold fs-7 mb-4">Pengajuan baru yang menunggu keputusan admin.</div>
                                <x-ui.button type="button" wire:click="$set('statusFilter', 'pengajuan')" variant="light-primary" size="sm" class="w-100">
                                    Buka Pengajuan
                                </x-ui.button>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="border border-dashed border-gray-300 rounded-3 p-5 h-100">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <x-ui.badge variant="warning">Penilaian</x-ui.badge>
                                    <span class="fs-2 fw-bold text-gray-900">{{ $this->countAssessment }}</span>
                                </div>
                                <div class="text-muted fw-semibold fs-7 mb-4">Proses penilaian yang perlu dipantau progresnya.</div>
                                <x-ui.button type="button" wire:click="$set('statusFilter', 'assessment')" variant="light-warning" size="sm" class="w-100">
                                    Pantau Proses
                                </x-ui.button>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="border border-dashed border-gray-300 rounded-3 p-5 h-100">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <x-ui.badge variant="info">Visitasi</x-ui.badge>
                                    <span class="fs-2 fw-bold text-gray-900">{{ $this->countVisitasi }}</span>
                                </div>
                                <div class="text-muted fw-semibold fs-7 mb-4">Jadwal visitasi dan tindak lanjut lapangan.</div>
                                <x-ui.button type="button" wire:click="$set('statusFilter', 'visitasi')" variant="light-info" size="sm" class="w-100">
                                    Lihat Jadwal
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-12 col-xl-4">
                <x-ui.card
                    title="Mode Kerja Admin"
                    subtitle="Alur keputusan tetap mengikuti proses yang sudah berjalan."
                    class="h-100"
                >
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">1</span>
                            <div>
                                <div class="fw-bold text-gray-900">Pilih tahap proses</div>
                                <div class="text-muted fs-7">Gunakan tab Pengajuan, Penilaian, atau Visitasi.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">2</span>
                            <div>
                                <div class="fw-bold text-gray-900">Cari pesantren</div>
                                <div class="text-muted fs-7">Persempit daftar akreditasi sebelum membuka aksi.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">3</span>
                            <div>
                                <div class="fw-bold text-gray-900">Tindak lanjuti</div>
                                <div class="text-muted fs-7">Verifikasi, detail, catatan, ekspor, dan hapus tetap di tabel.</div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-datatable.layout
            title="Daftar Pengajuan"
            subtitle="Filter berdasarkan tahap, cari pesantren, lalu tindak lanjuti item yang membutuhkan keputusan."
            :records="$this->akreditasis"
        >
            <x-slot name="filters">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <x-ui.button type="button" wire:click="$set('statusFilter', 'pengajuan')" :variant="$statusFilter === 'pengajuan' ? 'primary' : 'light'" size="sm">
                        Pengajuan ({{ $this->countPengajuan }})
                    </x-ui.button>

                    <x-ui.button type="button" wire:click="$set('statusFilter', 'assessment')" :variant="$statusFilter === 'assessment' ? 'primary' : 'light'" size="sm">
                        Penilaian ({{ $this->countAssessment }})
                    </x-ui.button>

                    <x-ui.button type="button" wire:click="$set('statusFilter', 'visitasi')" :variant="$statusFilter === 'visitasi' ? 'primary' : 'light'" size="sm">
                        Visitasi ({{ $this->countVisitasi }})
                    </x-ui.button>

                    <x-ui.button type="button" wire:click="$set('statusFilter', 'overdue')" :variant="$statusFilter === 'overdue' ? 'danger' : 'light-danger'" size="sm">
                        <x-ui.icon name="warning-2" class="fs-6 me-1" />
                        Terlambat ({{ $this->countOverdue }})
                    </x-ui.button>
                </div>

                <x-datatable.search placeholder="Cari Pesantren..." />

                <x-ui.button wire:click="export" variant="primary" size="sm">
                    <x-ui.icon name="document" class="fs-4 me-1" />
                    Ekspor Data
                </x-ui.button>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th :min-width="false" align="center" class="w-60px">
                    <x-ui.table-checkbox model="selectAll" label="Pilih semua pengajuan" />
                </x-ui.table-th>

                <x-datatable.th field="user_id" :sortField="$sortField" :sortAsc="$sortAsc">Pesantren</x-datatable.th>
                <x-datatable.th field="created_at" :sortField="$sortField" :sortAsc="$sortAsc">Tahap Akreditasi</x-datatable.th>
                <x-ui.table-th align="center">Nilai</x-ui.table-th>
                <x-ui.table-th align="center">Peringkat</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th>Catatan</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->akreditasis as $index => $item)
                @php
                    $stage = match ((int) $item->status) {
                        6 => [
                            'label' => 'Pengajuan',
                            'date' => $item->created_at->format('d/m/y'),
                            'variant' => 'primary',
                        ],
                        5 => [
                            'label' => 'Penilaian',
                            'date' => $item->assessment1 ? \Carbon\Carbon::parse($item->assessment1->tanggal_mulai)->format('d/m/y') : '-',
                            'variant' => 'warning',
                        ],
                        default => [
                            'label' => 'Visitasi',
                            'date' => $item->tgl_visitasi ? \Carbon\Carbon::parse($item->tgl_visitasi)->format('d/m/y') : '-',
                            'variant' => 'info',
                        ],
                    };

                    $statusVariant = match ((int) $item->status) {
                        1 => 'success',
                        2 => 'danger',
                        5, 6 => 'warning',
                        default => 'info',
                    };

                    $statusLabel = $item->status >= 3 ? 'Proses' : Akreditasi::getStatusLabel($item->status);
                @endphp

                <tr wire:key="akred-{{ $item->id }}">
                    <td class="text-center">
                        <x-ui.table-checkbox model="selectedIds" :value="$item->id" :label="'Pilih pengajuan ' . ($item->user->pesantren->nama_pesantren ?? $item->user->name)" />
                    </td>

                    <td>
                        <div class="d-flex flex-column">
                            <span class="text-gray-900 fw-bold fs-6">{{ $item->user->pesantren->nama_pesantren ?? $item->user->name }}</span>
                            <span class="text-muted fw-semibold fs-7">{{ $item->user->email }}</span>
                        </div>
                    </td>

                    <td>
                        <div class="d-flex flex-column gap-1">
                            <div class="d-flex align-items-center gap-2">
                                <x-ui.badge :variant="$stage['variant']">{{ $stage['label'] }}</x-ui.badge>
                                @if($item->parent)
                                    <x-ui.badge variant="warning">Pengajuan Ulang</x-ui.badge>
                                @endif
                            </div>
                            <span class="text-muted fw-semibold fs-7">
                                {{ $stage['date'] }}

                            @if($item->tgl_visitasi_akhir && $item->tgl_visitasi != $item->tgl_visitasi_akhir)
                                - {{ \Carbon\Carbon::parse($item->tgl_visitasi_akhir)->format('d/m/y') }}
                            @endif
                            </span>
                        </div>
                    </td>

                    <td class="text-center">
                        <span class="fw-bold text-gray-900">{{ $item->nilai ?? '-' }}</span>
                    </td>

                    <td class="text-center">
                        <span class="fw-bold text-gray-900">{{ $item->peringkat ?? '-' }}</span>
                    </td>

                    <td class="text-center">
                        <div class="d-flex flex-column align-items-center gap-1">
                            <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
                            @if ($item->status == 5)
                                @php
                                    $progressTracker = app(\App\Services\ProgressTracker::class);
                                    $blockingStatus = $progressTracker->getBlockingStatus($item->id);
                                @endphp
                                @if ($blockingStatus['blocked'])
                                    @foreach ($blockingStatus['blockers'] as $blocker)
                                        @if ($blocker === 'asesor2_na')
                                            <x-ui.badge variant="warning" class="fs-9">Menunggu Asesor 2</x-ui.badge>
                                        @elseif ($blocker === 'asesor1_na' || $blocker === 'asesor1_nk')
                                            <x-ui.badge variant="warning" class="fs-9">Menunggu Asesor 1</x-ui.badge>
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                            @if (isset($this->overdueMap[$item->id]))
                                <x-ui.badge variant="danger" class="fs-9">
                                    Terlambat {{ $this->overdueMap[$item->id] }} hari
                                </x-ui.badge>
                            @endif
                        </div>
                    </td>

                    <td>
                        <x-ui.button type="button" wire:click="openCatatanModal({{ $item->id }})" variant="light-warning" size="sm">
                            <x-ui.icon name="document" class="fs-4 me-1" />
                            {{ $item->catatans->count() }} Catatan
                        </x-ui.button>
                    </td>

                    <td class="text-end">
                        <x-ui.action-menu>
                            @if ($item->status == 6)
                                <x-ui.action-menu-item wire:click="openVerifikasiModal({{ $item->id }})" variant="primary">
                                    <x-ui.icon name="check-circle" class="fs-4" />
                                        Verifikasi
                                </x-ui.action-menu-item>
                            @endif

                            <x-ui.action-menu-item :href="route('admin.akreditasi-detail', $item->uuid)">
                                <x-ui.icon name="eye" class="fs-4" />
                                Lihat Detail
                            </x-ui.action-menu-item>

                            <x-ui.action-menu-item
                                variant="danger"
                                x-on:click="confirmDelete({{ $item->id }}, 'delete', 'Pengajuan akreditasi yang dihapus tidak dapat dikembalikan!')"
                            >
                                <x-ui.icon name="trash" class="fs-4" />
                                Hapus
                            </x-ui.action-menu-item>
                        </x-ui.action-menu>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <x-ui.empty-state
                            title="Data tidak ditemukan"
                            description="Coba ubah filter atau kata kunci pencarian."
                            class="py-15"
                        />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.index-layout>

    <!-- Modal Verifikasi -->
    <x-ui.modal name="verifikasi-modal" focusable>
        <form x-on:submit.prevent="confirmVerifikasiPengajuan($wire)">
            <x-ui.modal-header
                title="Verifikasi Pengajuan Akreditasi"
                subtitle="Pilih tindak lanjut untuk pengajuan akreditasi."
                icon="check-circle"
            />

            <x-ui.modal-body>
                <x-ui.form-field label="Tindakan" class="mb-6">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 h-100">
                                <x-ui.radio
                                    wire:model.live="action_type"
                                    value="approve"
                                    label="Lanjutkan Proses"
                                />
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="notice d-flex bg-light-danger rounded border-danger border border-dashed p-4 h-100">
                                <x-ui.radio
                                    wire:model.live="action_type"
                                    value="reject"
                                    label="Stop Pengajuan"
                                />
                            </div>
                        </div>
                    </div>
                </x-ui.form-field>

                <div x-show="$wire.action_type === 'approve'" x-transition>
                    <div class="row g-5">
                        <div class="col-md-6">
                            <x-ui.form-field label="Ketua Asesor" for="asesor_id1" :error="$errors->get('asesor_id1')">
                                <x-ui.select model="asesor_id1" id="asesor_id1" placeholder="Pilih Ketua Asesor">
                                    @foreach ($this->asesors as $asesor)
                                        <option value="{{ $asesor->id }}">{{ $asesor->user->name }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.form-field>
                        </div>

                        <div class="col-md-6">
                            <x-ui.form-field label="Anggota Asesor" for="asesor_id2" :error="$errors->get('asesor_id2')">
                                <x-ui.select model="asesor_id2" id="asesor_id2" placeholder="Pilih Anggota Asesor">
                                    @foreach ($this->asesors as $asesor)
                                        <option value="{{ $asesor->id }}">{{ $asesor->user->name }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.form-field>
                        </div>

                        <div class="col-md-6">
            <x-ui.form-field label="Tanggal Mulai Penilaian" for="tanggal_mulai" :error="$errors->get('tanggal_mulai')">
                                <x-ui.input model="tanggal_mulai" id="tanggal_mulai" type="date" />
                            </x-ui.form-field>
                        </div>

                        <div class="col-md-6">
            <x-ui.form-field label="Tanggal Berakhir Penilaian" for="tanggal_berakhir" :error="$errors->get('tanggal_berakhir')">
                                <x-ui.input model="tanggal_berakhir" id="tanggal_berakhir" type="date" />
                            </x-ui.form-field>
                        </div>
                    </div>
                </div>

                <div x-show="$wire.action_type === 'reject'" x-transition>
                    <x-ui.form-field
                        label="Catatan Penolakan"
                        for="catatan_penolakan"
                        :error="$errors->get('catatan_penolakan')"
                        hint="Minimal 10 karakter."
                        class="mb-0"
                    >
                        <x-ui.textarea
                            model="catatan_penolakan"
                            id="catatan_penolakan"
                            rows="4"
                            placeholder="Jelaskan alasan penolakan pengajuan akreditasi ini..."
                        />
                    </x-ui.form-field>
                </div>
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">
                    Batal
                </x-ui.button>

                <x-ui.button type="submit" :variant="$action_type === 'reject' ? 'danger' : 'primary'">
                    <span x-show="$wire.action_type === 'approve'">Lanjutkan</span>
                    <span x-show="$wire.action_type === 'reject'">Stop</span>
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
                        {{ $isRejection ? 'Catatan Penolakan Visitasi' : 'Catatan Akreditasi' }}
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
                                <p class="text-muted fs-8 fw-bold text-uppercase mb-2">Tipe Catatan:</p>
                                @if($isNoteRejection)
                                <x-ui.badge variant="warning">{{ $catatan->tipe ?? 'Umum' }}</x-ui.badge>
                                @else
                                <x-ui.badge variant="info">{{ $catatan->tipe ?? 'Umum' }}</x-ui.badge>
                                @endif
                            </div>
                            <div class="col-6">
                                <p class="text-muted fs-8 fw-bold text-uppercase mb-2">Tanggal:</p>
                                <p class="fs-7 fw-bolder text-gray-700">
                                    {{ $catatan->created_at->translatedFormat('d F Y H:i') }}
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

                        <div class="rounded-4 p-6 {{ $isNoteRejection ? 'bg-light-warning' : 'bg-light-info' }}">
                            <div class="fs-7 fw-medium text-gray-700">
                                {!! nl2br(e($catatan->catatan)) !!}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12">
                        <x-ui.icon name="document" class="fs-3x text-gray-300 mb-3 d-block mx-auto" />
                        <p class="text-muted fw-semibold fs-7 text-center">Tidak ada catatan ditemukan.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
    </x-ui.modal>
</div>
