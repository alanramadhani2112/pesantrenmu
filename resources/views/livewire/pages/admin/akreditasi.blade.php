<?php

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\AkreditasiCatatan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
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
        if (!$user->isAdmin()) {
            abort(403);
        }
    }

    public function getAkreditasisProperty()
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);
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

    public function getAsesorsProperty()
    {
        $akreditasiService = app(\App\Services\AkreditasiService::class);
        return $akreditasiService->getAvailableAsesors();
    }

    public function delete($id)
    {
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
        $akreditasiService = app(\App\Services\AkreditasiService::class);

        if ($this->action_type === 'approve') {
            $this->validate([
                'asesor_id1' => 'required',
                'asesor_id2' => 'nullable|different:asesor_id1',
                'tanggal_mulai' => 'required|date',
                'tanggal_berakhir' => 'required|date|after_or_equal:tanggal_mulai',
            ]);

            $akreditasiService->approvePengajuan($this->akreditasi_id, [
                'asesor_id1' => $this->asesor_id1,
                'asesor_id2' => $this->asesor_id2,
                'tanggal_mulai' => $this->tanggal_mulai,
                'tanggal_berakhir' => $this->tanggal_berakhir,
            ]);

            session()->flash('status', 'Pengajuan berhasil diverifikasi. Status berubah menjadi tahap penilaian.');
        } else {
            $this->validate([
                'catatan_penolakan' => 'required|string|min:10',
            ]);

            $akreditasiService->rejectPengajuan($this->akreditasi_id, $this->catatan_penolakan);

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

    <x-ui.page
        title="Akreditasi"
        subtitle="Kelola pengajuan, penilaian, visitasi, dan tindak lanjut pesantren dari satu daftar akreditasi."
    >
        <x-slot name="toolbar">
            <x-ui.badge variant="primary">Admin</x-ui.badge>
            <x-ui.badge variant="warning">Aktif: {{ $this->countPengajuan + $this->countAssessment + $this->countVisitasi }}</x-ui.badge>
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

        <x-ui.table
            title="Daftar Pengajuan"
            subtitle="Filter berdasarkan tahap, cari pesantren, lalu tindak lanjuti item yang membutuhkan keputusan."
            :records="$this->akreditasis"
            class="spm-admin-akreditasi-table"
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
                </div>

                <x-ui.table-search placeholder="Cari Pesantren..." />

                <x-ui.button wire:click="export" variant="primary" size="sm">
                    <x-ui.icon name="document" class="fs-4 me-1" />
                    Ekspor Data
                </x-ui.button>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th :min-width="false" align="center" class="w-60px">
                    <x-ui.table-checkbox model="selectAll" label="Pilih semua pengajuan" />
                </x-ui.table-th>

                <x-ui.table-th field="user_id" :sortField="$sortField" :sortAsc="$sortAsc">Pesantren</x-ui.table-th>
                <x-ui.table-th field="created_at" :sortField="$sortField" :sortAsc="$sortAsc">Tahap Akreditasi</x-ui.table-th>
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
                            <x-ui.badge :variant="$stage['variant']">{{ $stage['label'] }}</x-ui.badge>
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
                        <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
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
        </x-ui.table>
    </x-ui.page>

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
        <div class="p-0 overflow-hidden rounded-3xl spm-modal-content-scroll">
            @if($selectedAkreditasiNotes)
            @php
            $latestCatatan = $selectedAkreditasiNotes->catatans->sortByDesc('created_at')->first();
            $isRejection = $latestCatatan && !empty($latestCatatan->perbaikan);
            @endphp

            <div class="p-8">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-xl font-bold text-[#1e3a5f]">
                        {{ $isRejection ? 'Catatan Penolakan Visitasi' : 'Catatan Akreditasi' }}
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
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Tipe Catatan:</p>
                                <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-tight {{ $isNoteRejection ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600' }}">
                                    {{ $catatan->tipe ?? 'Umum' }}
                                </span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Tanggal:</p>
                                <p class="text-[11px] font-black text-slate-700">
                                    {{ $catatan->created_at->translatedFormat('d F Y H:i') }}
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

                        <div class="rounded-3xl p-6 {{ $isNoteRejection ? 'bg-amber-50 text-slate-700' : 'bg-blue-50 text-slate-700' }}">
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
