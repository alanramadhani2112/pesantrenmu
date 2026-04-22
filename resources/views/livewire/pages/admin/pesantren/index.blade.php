<?php

use App\Models\User;
use App\Models\Pesantren;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PesantrenExport;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $filterAkreditasi = '';
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

    public function updatedFilterStatus()
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedFilterAkreditasi()
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

    public function getPesantrensProperty()
    {
        $pesantrenService = app(\App\Services\PesantrenService::class);
        return $pesantrenService->getPaginatedData(
            $this->search,
            $this->filterStatus,
            $this->filterAkreditasi,
            $this->perPage,
            $this->sortField,
            $this->sortAsc
        );
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedIds = $this->pesantrens->pluck('id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function updatedSelectedIds()
    {
        $this->selectAll = count($this->selectedIds) > 0 && count($this->selectedIds) === count($this->pesantrens->pluck('id'));
    }

    private function resetSelection()
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function export()
    {
        return Excel::download(new PesantrenExport($this->search, $this->filterStatus, $this->filterAkreditasi, $this->sortField, $this->sortAsc), 'data-pesantren-' . now()->format('Y-m-d') . '.xlsx');
    }
}; ?>

<div class="py-12">
    <x-slot name="header">
            {{ __('Pesantren') }}
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <x-datatable.layout title="Pesantren" :records="$this->pesantrens">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Pesantren..." />

                <select wire:model.live="filterAkreditasi" class="text-xs border border-gray-100 rounded-lg bg-gray-50/50 py-2 pl-3 pr-8 focus:ring-1 focus:ring-green-500 focus:border-green-500 text-gray-500">
                    <option value="">Semua Akreditasi</option>
                    <option value="terakreditasi">Unggul</option>
                    <option value="proses">Proses Akreditasi</option>
                    <option value="belum">Belum Terakreditasi</option>
                    <option value="ditolak">Tidak Terakreditasi</option>
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
                    NAMA PESANTREN
                </x-datatable.th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">AKREDITASI</th>
                <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">STATUS</th>
                <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8">AKSI</th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->pesantrens as $index => $user)
                <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0" wire:key="user-{{ $user->id }}">
                    <td class="py-5 px-4">
                        <input type="checkbox" wire:model.live="selectedIds" value="{{ $user->id }}" class="rounded border-gray-300 text-green-600 focus:ring-green-500 bg-gray-100 h-4 w-4">
                    </td>
                    <td class="py-5 px-4">
                        <span class="text-sm font-bold text-[#374151]">{{ $user->pesantren->nama_pesantren ?? $user->name }}</span>
                    </td>
                    <td class="py-5 px-4 text-center">
                        @php
                        $latestAkreditasi = $user->akreditasis->sortByDesc('created_at')->first();
                        @endphp
                        @if (!$latestAkreditasi)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 text-gray-400 uppercase tracking-tight">Belum Teakreditasi</span>
                        @elseif ($latestAkreditasi->status == 1)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-600 uppercase tracking-tight border border-green-100">{{ $latestAkreditasi->peringkat ?? 'Unggul' }}</span>
                        @elseif ($latestAkreditasi->status == 2)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 uppercase tracking-tight border border-rose-100">Ditolak</span>
                        @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-600 uppercase tracking-tight border border-amber-100">Proses</span>
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
                        <a href="{{ route('admin.pesantren.detail', $user->uuid) }}" wire:navigate
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-[11px] font-bold text-gray-500 hover:text-gray-800 transition-colors bg-gray-50/80 rounded-lg group-hover:bg-gray-100">
                            <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-16 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-10 h-10 text-gray-400/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
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