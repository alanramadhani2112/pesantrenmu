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

<div x-data="adminManagement" data-module-page="admin-asesor">
    <x-slot name="header">
            {{ __('Asesor') }}
    </x-slot>

    <x-ui.page
        title="Asesor"
        subtitle="Kelola asesor, penugasan aktif, dan status ketersediaan."
    >
        <x-datatable.layout title="Asesor" :records="$this->asesors">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Asesor..." />

                <x-ui.filter-select
                    model="filterPeran"
                    placeholder="Semua Peran"
                    :options="['1' => 'Ketua Asesor', '2' => 'Anggota Asesor']"
                />

                <x-ui.filter-select
                    model="filterPenugasan"
                    placeholder="Semua Penugasan"
                    :options="['bertugas' => 'Sedang Bertugas', 'bebas' => 'Bebas Tugas']"
                />

                <x-ui.filter-select
                    model="filterStatus"
                    placeholder="Semua Status"
                    :options="['1' => 'Aktif', '0' => 'Tidak Aktif']"
                />

                <x-ui.button wire:click="export" variant="primary" size="sm">
                    <x-ui.icon name="document" class="fs-4 me-1" />
                    Ekspor Data
                </x-ui.button>
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
                        <x-ui.status-badge :variant="$latestTask->tipe == 1 ? 'primary' : 'info'" class="text-uppercase">
                            {{ $latestTask->tipe == 1 ? 'Ketua' : 'Anggota' }}
                        </x-ui.status-badge>
                        @else
                        <span class="text-[11px] font-bold text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if ($latestTask)
                        @php
                        $statusAkreditasi = $latestTask->akreditasi?->status;
                        $penugasanText = 'Sedang Bertugas';
                        $penugasanVariant = 'success';
                        if ($statusAkreditasi == 4) {
                        $penugasanText = 'Visitasi';
                        $penugasanVariant = 'info';
                        }
                        elseif (in_array($statusAkreditasi, [1, 2])) {
                        $penugasanText = 'Selesai';
                        $penugasanVariant = 'secondary';
                        }
                        @endphp
                        <x-ui.status-badge :variant="$penugasanVariant" class="text-uppercase">
                            {{ $penugasanText }}
                        </x-ui.status-badge>
                        @else
                        <span class="text-[11px] font-bold text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if($user->status == 1)
                        <x-ui.status-badge variant="success" class="text-uppercase">Aktif</x-ui.status-badge>
                        @else
                        <x-ui.status-badge variant="danger" class="text-uppercase">Non-Aktif</x-ui.status-badge>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-right pr-6">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item :href="route('admin.asesor.detail', $user->uuid)" wire:navigate>
                                <x-ui.icon name="eye" class="fs-5 text-gray-500" />
                                Lihat Detail
                            </x-ui.action-menu-item>

                            <x-ui.action-menu-item
                                :variant="$user->status == 1 ? 'danger' : 'success'"
                                x-on:click="confirmToggleStatus($wire, {{ $user->id }}, {{ $user->status }}, '{{ addslashes($user->name) }}', 'Asesor')"
                            >
                                <x-ui.icon :name="$user->status == 1 ? 'cross-circle' : 'check-circle'" class="fs-5" />
                                {{ $user->status == 1 ? 'Nonaktifkan' : 'Aktifkan Kembali' }}
                            </x-ui.action-menu-item>
                        </x-ui.action-menu>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <x-ui.empty-state title="Data tidak ditemukan" class="py-15" />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.page>
</div>
