<?php

use App\Models\User;
use App\Models\Asesor;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AsesorExport;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';
    public $filterPeran = '';
    public $filterPenugasan = '';
    public $filterStatus = '';
    public $perPage = 10;
    public $sortField = 'name';
    public $sortAsc = true;
    public $selectedIds = [];
    public $selectAll = false;

    public function updatedSearch()
    {
        $this->resetPage();
        $this->resetSelection();
    }
    public function updatedFilterPeran()
    {
        $this->resetPage();
        $this->resetSelection();
    }
    public function updatedFilterPenugasan()
    {
        $this->resetPage();
        $this->resetSelection();
    }
    public function updatedFilterStatus()
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

    public function toggleStatus($userId)
    {
        $asesorService = app(\App\Services\AsesorService::class);
        if ($asesorService->toggleStatus($userId)) {
            session()->flash('status', 'Status asesor berhasil diperbarui.');
        }
    }

    public function getAsesorsProperty()
    {
        $asesorService = app(\App\Services\AsesorService::class);
        return $asesorService->getPaginatedAsesors(
            [
                'search' => $this->search,
                'status' => $this->filterStatus,
                'peran' => $this->filterPeran,
                'penugasan' => $this->filterPenugasan,
            ],
            $this->perPage,
            $this->sortField,
            $this->sortAsc
        );
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedIds = $this->asesors->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function updatedSelectedIds()
    {
        $this->selectAll = count($this->selectedIds) > 0 && count($this->selectedIds) === count($this->asesors->pluck('id'));
    }

    private function resetSelection()
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function export()
    {
        return Excel::download(new AsesorExport($this->search, $this->filterPeran, $this->filterPenugasan, $this->filterStatus, $this->sortField, $this->sortAsc), 'data-asesor-' . now()->format('Y-m-d') . '.xlsx');
    }
}; ?>

<div class="py-12" x-data="adminManagement">
    <x-slot name="header">
            {{ __('Asesor') }}
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <x-datatable.layout title="Asesor" :records="$this->asesors">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Asesor..." />

                <select wire:model.live="filterPeran" class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-2 pl-3 pr-8 focus:ring-1 focus:ring-green-500 focus:border-green-500 text-gray-500">
                    <option value="">Semua Peran</option>
                    <option value="1">Ketua Asesor</option>
                    <option value="2">Anggota Asesor</option>
                </select>
                <select wire:model.live="filterPenugasan" class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-2 pl-3 pr-8 focus:ring-1 focus:ring-green-500 focus:border-green-500 text-gray-500">
                    <option value="">Semua Penugasan</option>
                    <option value="bertugas">Sedang Bertugas</option>
                    <option value="bebas">Bebas Tugas</option>
                </select>
                <select wire:model.live="filterStatus" class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-2 pl-3 pr-8 focus:ring-1 focus:ring-green-500 focus:border-green-500 text-gray-500">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Tidak Aktif</option>
                </select>
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
                <x-datatable.th field="name" :sortField="$sortField" :sortAsc="$sortAsc">
                    ASESOR
                </x-datatable.th>
                <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest">PESANTREN DITANGANI</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">PERAN ASESOR</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">STATUS PENUGASAN</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">STATUS</th>
                <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8">AKSI</th>
            </x-slot>

            <x-slot name="tbody">
                @if (session('status'))
                <tr>
                    <td colspan="7" class="px-4 py-2">
                        <div class="p-3 bg-green-50 text-green-700 rounded-xl border border-green-100 text-[11px] font-bold uppercase tracking-tight">
                            {{ session('status') }}
                        </div>
                    </td>
                </tr>
                @endif

                @forelse ($this->asesors as $user)
                <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0" wire:key="asesor-{{ $user->id }}">
                    <td class="py-5 px-4">
                        <input type="checkbox" wire:model.live="selectedIds" value="{{ $user->id }}" class="rounded border-gray-300 text-green-600 focus:ring-green-500 bg-gray-100 h-4 w-4">
                    </td>
                    <td class="py-5 px-4">
                        <span class="text-sm font-bold text-[#374151]">{{ $user->name }}</span>
                    </td>
                    <td class="py-5 px-4">
                        @php
                        $latestTask = $user->asesor?->assessments->sortByDesc('created_at')->first();
                        $pesantrenName = $latestTask?->akreditasi?->user?->pesantren?->nama_pesantren ?? '-';
                        @endphp
                        <span class="text-[11px] font-bold text-gray-500">{{ $pesantrenName }}</span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if ($latestTask)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $latestTask->tipe == 1 ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-sky-50 text-sky-600 border border-sky-100' }} uppercase tracking-tight">
                            {{ $latestTask->tipe == 1 ? 'Ketua' : 'Anggota' }}
                        </span>
                        @else
                        <span class="text-[11px] font-bold text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if ($latestTask)
                        @php
                        $statusAkreditasi = $latestTask->akreditasi?->status;
                        $penugasanText = 'Sedang Bertugas';
                        $penugasanColor = 'bg-green-50 text-green-600 border-green-100';
                        if ($statusAkreditasi == 4) {
                        $penugasanText = 'Visitasi';
                        $penugasanColor = 'bg-indigo-50 text-indigo-600 border-indigo-100';
                        }
                        elseif (in_array($statusAkreditasi, [1, 2])) {
                        $penugasanText = 'Selesai';
                        $penugasanColor = 'bg-gray-50 text-gray-400 border-gray-100';
                        }
                        @endphp
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $penugasanColor }} border uppercase tracking-tight">
                            {{ $penugasanText }}
                        </span>
                        @else
                        <span class="text-[11px] font-bold text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if($user->status == 1)
                        <span class="flex items-center justify-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                            <span class="text-[11px] font-bold text-green-600 uppercase tracking-tight">Aktif</span>
                        </span>
                        @else
                        <span class="flex items-center justify-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            <span class="text-[11px] font-bold text-rose-600 uppercase tracking-tight">Non-Aktif</span>
                        </span>
                        @endif
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
                                    <a href="{{ route('admin.asesor.detail', $user->uuid) }}" wire:navigate
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition-colors gap-3">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Lihat Detail
                                    </a>
                                    <button @click="confirmToggleStatus($wire, {{ $user->id }}, {{ $user->status }}, '{{ addslashes($user->name) }}', 'Asesor'); open = false"
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold {{ $user->status == 1 ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition-colors gap-3 border-t border-gray-50 mt-1">
                                        @if($user->status == 1)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        Nonaktifkan
                                        @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Aktifkan Kembali
                                        @endif
                                    </button>
                                </div>
                            </template>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-10 h-10 text-gray-400/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <p class="text-xs text-gray-400 font-bold">Data tidak ditemukan.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </div>
</div>