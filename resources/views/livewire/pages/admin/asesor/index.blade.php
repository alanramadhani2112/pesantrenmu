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

    <x-ui.index-layout
        title="Asesor"
        subtitle="Kelola asesor, penugasan aktif, dan status ketersediaan."
    >
        <x-slot name="toolbar">
            <x-ui.filter-bar>
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
            </x-ui.filter-bar>
        </x-slot>

        <x-datatable.layout title="Asesor" :records="$this->asesors">

            <x-slot name="thead">
                <x-ui.table-th :min-width="false" align="center" class="w-60px">
                    <x-ui.table-checkbox model="selectAll" label="Pilih semua asesor" />
                </x-ui.table-th>

                <x-datatable.th field="name" :sortField="$sortField" :sortAsc="$sortAsc">
                    Asesor
                </x-datatable.th>
                <x-ui.table-th>Pesantren Ditangani</x-ui.table-th>
                <x-ui.table-th align="center">Peran Asesor</x-ui.table-th>
                <x-ui.table-th align="center">Status Penugasan</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @if (session('status'))
                <tr>
                    <td colspan="7">
                        <div class="alert alert-success d-flex align-items-center p-4 mb-0">
                            <x-ui.icon name="check-circle" class="fs-3 me-3" />
                            {{ session('status') }}
                        </div>
                    </td>
                </tr>
                @endif

                @forelse ($this->asesors as $user)
                <tr wire:key="asesor-{{ $user->id }}">
                    <td class="text-center">
                        <x-ui.table-checkbox model="selectedIds" :value="$user->id" :label="'Pilih ' . $user->name" />
                    </td>
                    <td>
                        <span class="text-gray-900 fw-bold fs-6">{{ $user->name }}</span>
                    </td>
                    <td>
                        @php
                        $latestTask = $user->asesor?->assessments->sortByDesc('created_at')->first();
                        $pesantrenName = $latestTask?->akreditasi?->user?->pesantren?->nama_pesantren ?? '-';
                        @endphp
                        <span class="text-muted fw-semibold">{{ $pesantrenName }}</span>
                    </td>
                    <td class="text-center">
                        @if ($latestTask)
                        <x-ui.status-badge :variant="$latestTask->tipe == 1 ? 'primary' : 'info'">
                            {{ $latestTask->tipe == 1 ? 'Ketua' : 'Anggota' }}
                        </x-ui.status-badge>
                        @else
                        <span class="text-muted fw-bold">-</span>
                        @endif
                    </td>
                    <td class="text-center">
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
                        <x-ui.status-badge :variant="$penugasanVariant">
                            {{ $penugasanText }}
                        </x-ui.status-badge>
                        @else
                        <span class="text-muted fw-bold">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($user->status == 1)
                        <x-ui.status-badge variant="success">Aktif</x-ui.status-badge>
                        @else
                        <x-ui.status-badge variant="danger">Non-Aktif</x-ui.status-badge>
                        @endif
                    </td>
                    <td class="text-end">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item :href="route('admin.asesor.detail', $user->uuid)">
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
    </x-ui.index-layout>
</div>
