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

    public function getStatusCountsProperty(): array
    {
        return app(\App\Services\AkreditasiService::class)->getStatusCounts();
    }

    public function getWorkflowStatusProperty(): ?int
    {
        return match ($this->statusFilter) {
            'pengajuan' => Akreditasi::STATUS_PENGAJUAN,
            'verifikasi' => Akreditasi::STATUS_VERIFIKASI_BERKAS,
            'assessment' => Akreditasi::STATUS_ASSESSMENT,
            'visitasi' => Akreditasi::STATUS_VISITASI,
            'validasi' => Akreditasi::STATUS_VALIDASI_ADMIN,
            'selesai' => Akreditasi::STATUS_SELESAI,
            'ditolak' => Akreditasi::STATUS_DITOLAK,
            'banding' => Akreditasi::STATUS_BANDING,
            default => null,
        };
    }

    public function getOverdueMapProperty(): array
    {
        $deadlineService = app(DeadlineService::class);
        $overdueAkreditasi = $deadlineService->getOverdueAkreditasi();
        $map = [];
        foreach ($overdueAkreditasi as $akreditasi) {
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
            $this->dispatch('notification-received', type: 'success', title: 'Berhasil!', message: 'Pengajuan akreditasi berhasil dihapus.');
        } else {
            $this->dispatch('notification-received', type: 'error', title: 'Gagal!', message: 'Gagal menghapus pengajuan akreditasi.');
        }
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
            <x-ui.badge variant="warning">Aktif: {{ ($this->statusCounts['pengajuan'] ?? 0) + ($this->statusCounts['verifikasi'] ?? 0) + ($this->statusCounts['assessment'] ?? 0) + ($this->statusCounts['visitasi'] ?? 0) + ($this->statusCounts['validasi'] ?? 0) }}</x-ui.badge>
            @if(($this->statusCounts['overdue'] ?? 0) > 0)
                <x-ui.badge variant="danger">Terlambat: {{ $this->statusCounts['overdue'] }}</x-ui.badge>
            @endif
        </x-slot>

        <x-akreditasi.workflow-stepper
            :status="$this->workflowStatus"
            title="Tahapan Akreditasi LP2M"
            subtitle="Alur kerja dari pengajuan berkas sampai hasil akhir akreditasi."
            class="mb-6"
        />

        <div class="row g-6 mb-6">
            <div class="col-12 col-xl-8">
                <x-ui.card
                    title="Prioritas Operasional"
                    subtitle="Gunakan ringkasan ini untuk menentukan antrean pengajuan yang perlu diproses terlebih dahulu."
                    class="h-100"
                >
                    <div class="row g-4">
                        <div class="col-12 col-md-4">
                            <x-ui.metric-box
                                label="Pengajuan"
                                :value="$this->statusCounts['pengajuan'] ?? 0"
                                variant="primary"
                                description="Pengajuan baru menunggu dibuka untuk verifikasi berkas."
                                actionLabel="Buka Pengajuan"
                                actionWireClick="$set('statusFilter', 'pengajuan')"
                            />
                        </div>

                        <div class="col-12 col-md-4">
                            <x-ui.metric-box
                                label="Review Berkas & Asesor"
                                :value="($this->statusCounts['verifikasi'] ?? 0) + ($this->statusCounts['assessment'] ?? 0)"
                                variant="warning"
                                description="Verifikasi awal admin dan review asesor sebelum visitasi dijadwalkan."
                                actionLabel="Pantau Review"
                                actionWireClick="$set('statusFilter', 'assessment')"
                            />
                        </div>

                        <div class="col-12 col-md-4">
                            <x-ui.metric-box
                                label="Visitasi & Penilaian"
                                :value="($this->statusCounts['visitasi'] ?? 0) + ($this->statusCounts['validasi'] ?? 0)"
                                variant="info"
                                description="Visitasi lapangan, penilaian pasca visitasi, dan Nilai Verifikasi admin."
                                actionLabel="Lihat Jadwal"
                                actionWireClick="$set('statusFilter', 'visitasi')"
                            />
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
                                <div class="fw-bold text-gray-900">Tahap proses</div>
                                <div class="text-muted fs-7">Pengajuan, review asesor, visitasi, penilaian pasca visitasi, dan validasi admin punya keputusan berbeda.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">2</span>
                            <div>
                                <div class="fw-bold text-gray-900">Konteks pesantren</div>
                                <div class="text-muted fs-7">Nama, status, dan catatan menjadi dasar sebelum keputusan admin.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge badge-circle badge-light-primary">3</span>
                            <div>
                                <div class="fw-bold text-gray-900">Tindak lanjuti</div>
                                <div class="text-muted fs-7">Keputusan admin tercatat pada riwayat akreditasi pesantren.</div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <x-datatable.layout
            title="Daftar Pengajuan"
            subtitle="Daftar pengajuan berdasarkan tahap akreditasi dan kebutuhan keputusan admin."
            :records="$this->akreditasis"
        >
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Pesantren..." />

                <x-ui.filter-select model="statusFilter" placeholder="Semua Tahap">
                    <option value="pengajuan">Pengajuan ({{ $this->statusCounts['pengajuan'] ?? 0 }})</option>
                    <option value="verifikasi">Verifikasi Berkas ({{ $this->statusCounts['verifikasi'] ?? 0 }})</option>
                    <option value="assessment">Review Asesor ({{ $this->statusCounts['assessment'] ?? 0 }})</option>
                    <option value="visitasi">Visitasi & Penilaian Pasca Visitasi ({{ $this->statusCounts['visitasi'] ?? 0 }})</option>
                    <option value="validasi">Validasi Admin ({{ $this->statusCounts['validasi'] ?? 0 }})</option>
                    <option value="overdue">Terlambat ({{ $this->statusCounts['overdue'] ?? 0 }})</option>
                    <option value="">Semua</option>
                </x-ui.filter-select>
            </x-slot>

            <x-slot name="toolbar">
                <x-ui.button wire:click="export" variant="primary" size="sm" icon="document">
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
                        6 => ['label' => 'Pengajuan',         'date' => $item->created_at->format('d/m/y'), 'variant' => 'primary'],
                        5 => ['label' => 'Verifikasi Berkas', 'date' => $item->created_at->format('d/m/y'), 'variant' => 'warning'],
                        4 => ['label' => 'Review Asesor',     'date' => $item->assessment1 ? \Carbon\Carbon::parse($item->assessment1->tanggal_mulai)->format('d/m/y') : '-', 'variant' => 'info'],
                        3 => ['label' => 'Visitasi',          'date' => $item->tgl_visitasi ? \Carbon\Carbon::parse($item->tgl_visitasi)->format('d/m/y') : '-', 'variant' => 'info'],
                        2 => ['label' => 'Penilaian Pasca Visitasi', 'date' => $item->visitasi_confirmed_at ? \Carbon\Carbon::parse($item->visitasi_confirmed_at)->format('d/m/y') : ($item->tgl_visitasi ? \Carbon\Carbon::parse($item->tgl_visitasi)->format('d/m/y') : '-'), 'variant' => 'info'],
                        1 => ['label' => 'Validasi Admin',    'date' => $item->updated_at->format('d/m/y'), 'variant' => 'warning'],
                        0 => ['label' => 'Selesai',           'date' => $item->updated_at->format('d/m/y'), 'variant' => 'success'],
                        -1 => ['label' => 'Ditolak',          'date' => $item->updated_at->format('d/m/y'), 'variant' => 'danger'],
                        -2 => ['label' => 'Banding',          'date' => $item->updated_at->format('d/m/y'), 'variant' => 'warning'],
                        default => ['label' => 'Unknown',     'date' => '-', 'variant' => 'secondary'],
                    };

                    $statusVariant = match ((int) $item->status) {
                        0      => 'success',
                        -1, -2 => 'danger',
                        1      => 'warning',
                        2      => 'info',
                        3, 4, 5, 6 => 'primary',
                        default => 'secondary',
                    };

                    $statusLabel = \App\Models\Akreditasi::getStatusLabel($item->status);
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
                            @if ($item->status == 4)
                                <x-ui.badge variant="info" class="fs-9">Pra Visitasi</x-ui.badge>
                            @endif
                            @if ($item->status == 2)
                                @php
                                    $progressTracker = app(\App\Services\ProgressTracker::class);
                                    $blockingStatus = $progressTracker->getBlockingStatus($item->id);
                                @endphp
                                @if ($blockingStatus['blocked'])
                                    @foreach ($blockingStatus['blockers'] as $blocker)
                                        @if ($blocker === 'asesor2_na')
                                            <x-ui.badge variant="warning" class="fs-9">Menunggu Anggota</x-ui.badge>
                                        @elseif ($blocker === 'asesor1_na' || $blocker === 'asesor1_nk')
                                            <x-ui.badge variant="warning" class="fs-9">Menunggu Ketua</x-ui.badge>
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
                                <x-ui.action-menu-item :href="route('admin.akreditasi-detail', $item->uuid)" variant="primary">
                                    <x-ui.icon name="eye" class="fs-4" />
                                    Buka untuk Review
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

    <!-- Modal Catatan (View Only) -->
    <x-ui.modal name="catatan-modal" focusable>
        @if($selectedAkreditasiNotes)
            @php
                $latestCatatan = $selectedAkreditasiNotes->catatans->sortByDesc('created_at')->first();
                $isRejection = $latestCatatan && !empty($latestCatatan->perbaikan);
            @endphp

            <x-ui.modal-header
                :title="$isRejection ? 'Catatan Penolakan Visitasi' : 'Catatan Akreditasi'"
                subtitle="Riwayat catatan dan tindak lanjut pada proses akreditasi ini."
                :icon="$isRejection ? 'warning-2' : 'document'"
                :variant="$isRejection ? 'warning' : 'info'"
            />
            <x-ui.modal-body>
                <x-ui.form-field label="Riwayat Catatan">
                <div class="d-flex flex-column gap-8 pe-2 custom-scrollbar">
                    @forelse($selectedAkreditasiNotes->catatans->sortByDesc('created_at') as $catatan)
                    @php
                    $isNoteRejection = !empty($catatan->perbaikan);
                    @endphp
                    <div class="border-bottom border-gray-200 pb-8">
                        <div class="d-flex align-items-center gap-4 mb-6">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($catatan->user->name) }}&color=1e3a5f&background=f1f5f9"
                                loading="lazy"
                                class="w-40px h-40px rounded-3 border border-white shadow-sm object-cover" alt="Avatar">
                            <div>
                                <h3 class="fs-6 fw-semibold text-gray-900">{{ $catatan->user->name }}</h3>
                                <p class="text-muted fs-8 fw-bold text-uppercase">
                                    {{ $catatan->user->isAsesor() ? 'Ketua Kelompok' : ($catatan->user->isAdmin() ? 'Administrator Pusat' : 'Pihak Berwenang') }}
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
                                <p class="fs-7 fw-semibold text-gray-700">
                                    {{ $catatan->created_at->translatedFormat('d F Y H:i') }}
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
                </x-ui.form-field>
            </x-ui.modal-body>
            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Tutup</x-ui.button>
            </x-ui.modal-footer>
        @endif
    </x-ui.modal>
</div>
