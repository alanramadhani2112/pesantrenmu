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

            session()->flash('status', 'Pengajuan berhasil diverifikasi. Status berubah menjadi Assessment.');
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

<div class="py-12" x-data="deleteConfirmation">
    <x-slot name="header">{{ __('Akreditasi') }}</x-slot>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <x-datatable.layout title="Akreditasi" :records="$this->akreditasis">
            <x-slot name="filters">
                <div class="flex flex-wrap items-center gap-1 bg-gray-50/50 p-1 rounded-xl border border-gray-100 mr-2">
                    <button wire:click="$set('statusFilter', 'pengajuan')"
                        class="px-4 py-1.5 text-[11px] font-bold rounded-lg transition-all
                        {{ $statusFilter === 'pengajuan' ? 'bg-[#1e3a5f] text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                        Pengajuan ({{ $this->countPengajuan }})
                    </button>
                    <button wire:click="$set('statusFilter', 'assessment')"
                        class="px-4 py-1.5 text-[11px] font-bold rounded-lg transition-all
                        {{ $statusFilter === 'assessment' ? 'bg-[#1e3a5f] text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                        Assessment ({{ $this->countAssessment }})
                    </button>
                    <button wire:click="$set('statusFilter', 'visitasi')"
                        class="px-4 py-1.5 text-[11px] font-bold rounded-lg transition-all
                        {{ $statusFilter === 'visitasi' ? 'bg-[#1e3a5f] text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                        Visitasi ({{ $this->countVisitasi }})
                    </button>
                </div>

                <x-datatable.search placeholder="Cari Pesantren..." />

                <button wire:click="export" class="bg-[#1e3a5f] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-[#162d4a] transition-all shadow-sm active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Ekspor Data
                </button>
            </x-slot>

            <x-slot name="thead">
                <th class="w-12 py-3 px-4">
                    <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-green-600 focus:ring-green-500 bg-gray-100 h-4 w-4">
                </th>
                <x-datatable.th field="user_id" :sortField="$sortField" :sortAsc="$sortAsc">
                    PESANTREN
                </x-datatable.th>
                <x-datatable.th field="created_at" :sortField="$sortField" :sortAsc="$sortAsc">
                    TAHAP AKREDITASI
                </x-datatable.th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">NILAI</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">PERINGKAT</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">STATUS</th>
                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest">CATATAN</th>
                <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8">AKSI</th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->akreditasis as $index => $item)
                <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0" wire:key="akred-{{ $item->id }}">
                    <td class="py-5 px-4">
                        <input type="checkbox" wire:model.live="selectedIds" value="{{ $item->id }}" class="rounded border-gray-300 text-green-600 focus:ring-green-500 bg-gray-100 h-4 w-4">
                    </td>
                    <td class="py-5 px-4">
                        <span class="text-sm font-bold text-[#374151]">{{ $item->user->pesantren->nama_pesantren ?? $item->user->name }}</span>
                    </td>
                    <td class="py-5 px-4 text-xs font-bold text-gray-500">
                        @if($item->status == 6)
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                            Pengajuan: {{ $item->created_at->format('d/m/y') }}
                        </span>
                        @elseif($item->status == 5)
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            Assessment: {{ $item->assessment1 ? \Carbon\Carbon::parse($item->assessment1->tanggal_mulai)->format('d/m/y') : '-' }}
                        </span>
                        @else
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                            Visitasi: {{ $item->tgl_visitasi ? \Carbon\Carbon::parse($item->tgl_visitasi)->format('d/m/y') : '-' }}
                            @if($item->tgl_visitasi_akhir && $item->tgl_visitasi != $item->tgl_visitasi_akhir)
                            - {{ \Carbon\Carbon::parse($item->tgl_visitasi_akhir)->format('d/m/y') }}
                            @endif
                        </span>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-center">
                        <span class="text-sm font-bold text-gray-300">{{ $item->nilai ?? '-' }}</span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        <span class="text-sm font-bold text-gray-300">{{ $item->peringkat ?? '-' }}</span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if($item->status >= 3)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-600 uppercase tracking-tight border border-amber-100">Proses</span>
                        @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-tight {{ Akreditasi::getStatusBadgeClass($item->status) }}">
                            {{ Akreditasi::getStatusLabel($item->status) }}
                        </span>
                        @endif
                    </td>
                    <td class="py-5 px-4">
                        <button wire:click="openCatatanModal({{ $item->id }})" class="flex items-center gap-2 text-[10px] font-extrabold text-[#111827] hover:text-blue-600 transition-colors uppercase tracking-tight bg-gray-50 py-1 px-2.5 rounded-lg border border-gray-100">
                            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ $item->catatans->count() }} Catatan
                        </button>
                    </td>
                    <td class="py-5 px-4 text-right pr-6">
                        <div class="inline-block text-left" x-data="{ 
                            open: false,
                            dropdownPosition: { top: 0, left: 0 },
                            updatePosition() {
                                let rect = this.$refs.btn.getBoundingClientRect();
                                this.dropdownPosition = { 
                                    top: (rect.bottom + 5) + 'px', 
                                    left: (rect.right - 176) + 'px' 
                                };
                            }
                        }">
                            <button x-ref="btn" @click="open = !open; if(open) updatePosition()" @click.away="open = false"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-gray-400 hover:text-gray-700 transition-colors bg-gray-50/50 rounded-lg group-hover:bg-gray-100">
                                Aksi
                                <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <template x-teleport="body">
                                <div x-show="open"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    :style="`position: fixed; top: ${dropdownPosition.top}; left: ${dropdownPosition.left}; z-index: 9999;`"
                                    class="w-44 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 origin-top-right overflow-hidden shadow-slate-200/50" x-cloak>
                                    @if ($item->status == 6)
                                    <button wire:click="openVerifikasiModal({{ $item->id }})" @click="open = false"
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-blue-700 hover:bg-blue-50 transition-colors gap-3">
                                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Verifikasi
                                    </button>
                                    @endif
                                    <a href="{{ route('admin.akreditasi-detail', $item->uuid) }}" wire:navigate
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition-colors gap-3 border-t border-gray-50/50">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Lihat Detail
                                    </a>
                                    <button @click="open = false; confirmDelete({{ $item->id }}, 'delete', 'Pengajuan akreditasi yang dihapus tidak dapat dikembalikan!')"
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-rose-600 hover:bg-rose-50 transition-colors gap-3 border-t border-gray-50/50">
                                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus
                                    </button>
                                </div>
                            </template>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-10 h-10 text-gray-400/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-xs text-gray-400 font-bold">Data tidak ditemukan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </div>

    <!-- Modal Verifikasi -->
    <x-modal name="verifikasi-modal" focusable>
        <form wire:submit="verifikasi" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Verifikasi Pengajuan Akreditasi</h2>
            <p class="mt-1 text-sm text-gray-600">
                Pilih tindakan yang akan dilakukan untuk pengajuan akreditasi ini.
            </p>

            <!-- Action Type Selection -->
            <div class="mt-6 space-y-4">
                <div class="flex gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <label class="flex items-center cursor-pointer flex-1">
                        <input type="radio" wire:model.live="action_type" value="approve"
                            class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">Lanjutkan Proses</span>
                            <span class="block text-xs text-gray-500">Pilih asesor dan jadwal assessment</span>
                        </div>
                    </label>
                    <label class="flex items-center cursor-pointer flex-1">
                        <input type="radio" wire:model.live="action_type" value="reject"
                            class="w-4 h-4 text-red-600 border-gray-300 focus:ring-red-500">
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900">Stop Pengajuan</span>
                            <span class="block text-xs text-gray-500">Berikan catatan penolakan</span>
                        </div>
                    </label>
                </div>

                <!-- Form Approve: Pilih Asesor -->
                <div x-show="$wire.action_type === 'approve'" x-transition class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="asesor_id1" value="Ketua Asesor" />
                            <select wire:model="asesor_id1" id="asesor_id1"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Pilih Ketua Asesor</option>
                                @foreach ($this->asesors as $asesor)
                                <option value="{{ $asesor->id }}">{{ $asesor->user->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('asesor_id1')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="asesor_id2" value="Anggota Asesor" />
                            <select wire:model="asesor_id2" id="asesor_id2"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Pilih Anggota Asesor</option>
                                @foreach ($this->asesors as $asesor)
                                <option value="{{ $asesor->id }}">{{ $asesor->user->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('asesor_id2')" class="mt-2" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="tanggal_mulai" value="Tanggal Mulai Assessment" />
                            <x-text-input wire:model="tanggal_mulai" id="tanggal_mulai" type="date"
                                class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('tanggal_mulai')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="tanggal_berakhir" value="Tanggal Berakhir Assessment" />
                            <x-text-input wire:model="tanggal_berakhir" id="tanggal_berakhir" type="date"
                                class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('tanggal_berakhir')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Form Reject: Catatan Penolakan -->
                <div x-show="$wire.action_type === 'reject'" x-transition class="space-y-4">
                    <div>
                        <x-input-label for="catatan_penolakan" value="Catatan Penolakan" />
                        <textarea wire:model="catatan_penolakan" id="catatan_penolakan" rows="4"
                            class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm"
                            placeholder="Jelaskan alasan penolakan pengajuan akreditasi ini..."></textarea>
                        <x-input-error :messages="$errors->get('catatan_penolakan')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500">Minimal 10 karakter</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-primary-button
                    x-bind:class="$wire.action_type === 'reject' ? 'bg-red-600 hover:bg-red-700 focus:bg-red-700 active:bg-red-900' : ''">
                    <span x-show="$wire.action_type === 'approve'">Lanjutkan</span>
                    <span x-show="$wire.action_type === 'reject'">Stop</span>
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Catatan (View Only) -->
    <x-modal name="catatan-modal" focusable>
        <div class="p-0 overflow-hidden rounded-3xl">
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
                    <button x-on:click="$dispatch('close')" class="text-slate-300 hover:text-slate-500 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-8 max-h-[75vh] overflow-y-auto pr-2 custom-scrollbar">
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
    </x-modal>
</div>