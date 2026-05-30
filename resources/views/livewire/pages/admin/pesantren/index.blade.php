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

    public function mount()
    {
        if (!auth()->user()?->canAccessAdminArea()) {
            abort(403);
        }
    }

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

    <x-ui.index-layout
        title="Pesantren"
        subtitle="Kelola data pesantren, status akun, dan status akreditasi."
    >
        <x-datatable.layout
            title="Daftar Pesantren"
            subtitle="Daftar pesantren beserta status akun dan progres akreditasi terbaru."
            :records="$this->pesantrens"
            class="spm-table-shell--admin-pesantren"
        >
            <x-slot name="filters">
                <x-ui.filter-bar>
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
                </x-ui.filter-bar>
            </x-slot>

            <x-slot name="toolbar">
                <x-ui.button wire:click="export" variant="primary" size="sm" icon="document">
                    Ekspor Data
                </x-ui.button>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th :min-width="false" align="center" class="w-60px">
                    <x-ui.table-checkbox model="selectAll" label="Pilih semua pesantren" />
                </x-ui.table-th>

                <x-datatable.th field="name" :sortField="$sortField" :sortAsc="$sortAsc">
                    Nama Pesantren
                </x-datatable.th>
                <x-ui.table-th align="center">Akreditasi</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->pesantrens as $index => $user)
                <tr wire:key="user-{{ $user->id }}">
                    <td class="text-center">
                        <x-ui.table-checkbox model="selectedIds" :value="$user->id" :label="'Pilih ' . ($user->pesantren->nama_pesantren ?? $user->name)" />
                    </td>
                    <td>
                        <span class="text-gray-900 fw-semibold fs-6">{{ $user->pesantren->nama_pesantren ?? $user->name }}</span>
                    </td>
                    <td class="text-center">
                        @php
                        $latestAkreditasi = $user->akreditasis->sortByDesc('created_at')->first();
                        @endphp
                        @if (!$latestAkreditasi)
                        <x-ui.status-badge variant="secondary">Belum Terakreditasi</x-ui.status-badge>
                        @elseif ($latestAkreditasi->status == 0)
                        <x-ui.status-badge variant="success">{{ $latestAkreditasi->peringkat ?? 'Unggul' }}</x-ui.status-badge>
                        @elseif ($latestAkreditasi->status == -1)
                        <x-ui.status-badge variant="danger">Ditolak</x-ui.status-badge>
                        @else
                        <x-ui.status-badge variant="warning">Proses</x-ui.status-badge>
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
                            <x-ui.action-menu-item :href="route('admin.pesantren.detail', $user->uuid)">
                                <x-ui.icon name="eye" class="fs-5 text-gray-500" />
                                Lihat Detail
                            </x-ui.action-menu-item>
                        </x-ui.action-menu>
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
    </x-ui.index-layout>
</div>
