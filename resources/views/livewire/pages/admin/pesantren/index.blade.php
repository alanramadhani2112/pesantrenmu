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

<div data-module-page="admin-pesantren">
    <x-slot name="header">
            {{ __('Pesantren') }}
    </x-slot>

    <x-ui.page
        title="Pesantren"
        subtitle="Kelola data pesantren, status akun, dan status akreditasi."
    >
        <x-datatable.layout title="Pesantren" :records="$this->pesantrens">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Pesantren..." />

                <x-ui.filter-select
                    model="filterAkreditasi"
                    placeholder="Semua Akreditasi"
                    :options="[
                        'terakreditasi' => 'Unggul',
                        'proses' => 'Proses Akreditasi',
                        'belum' => 'Belum Terakreditasi',
                        'ditolak' => 'Tidak Terakreditasi',
                    ]"
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
                        <x-ui.status-badge variant="secondary">Belum Terakreditasi</x-ui.status-badge>
                        @elseif ($latestAkreditasi->status == 1)
                        <x-ui.status-badge variant="success">{{ $latestAkreditasi->peringkat ?? 'Unggul' }}</x-ui.status-badge>
                        @elseif ($latestAkreditasi->status == 2)
                        <x-ui.status-badge variant="danger">Ditolak</x-ui.status-badge>
                        @else
                        <x-ui.status-badge variant="warning">Proses</x-ui.status-badge>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-center">
                        @if($user->status == 1)
                        <x-ui.status-badge variant="success">Aktif</x-ui.status-badge>
                        @else
                        <x-ui.status-badge variant="danger">Non-Aktif</x-ui.status-badge>
                        @endif
                    </td>
                    <td class="py-5 px-4 text-right pr-6">
                        <x-ui.button :href="route('admin.pesantren.detail', $user->uuid)" wire:navigate variant="light" size="sm">
                            <x-ui.icon name="eye" class="fs-4 me-1" />
                            Detail
                        </x-ui.button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <x-ui.empty-state title="Data tidak ditemukan" class="py-15" />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.page>
</div>
