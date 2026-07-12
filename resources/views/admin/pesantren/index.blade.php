@extends('layouts.app')

@section('content')
<div data-module-page="admin-pesantren" x-data="{ sortAsc: {{ json_encode($sortAsc) }}, sortField: '{{ $sortField }}', selectAll: false, selectedIds: [] }">
    <x-slot name="header">{{ __('Pesantren') }}</x-slot>

    <x-ui.index-layout
        title="Pesantren"
        subtitle="Kelola data pesantren, status akun, dan status akreditasi."
    >
        <x-ui.table
            title="Daftar Pesantren"
            subtitle="Daftar pesantren beserta status akun dan progres akreditasi terbaru."
            :records="$pesantrens"
            class="spm-table-shell--admin-pesantren"
        >
            <x-slot name="filters">
                <form method="GET" action="{{ route('admin.pesantren.index') }}" id="pesantren-filter-form" class="d-flex align-items-center gap-3 flex-wrap mb-5">
                    <x-datatable.search name="search" placeholder="Cari Pesantren..." :value="$search" form="pesantren-filter-form" />

                    <x-ui.filter-select
                        name="filterAkreditasi"
                        placeholder="Semua Akreditasi"
                        :options="[
                            'terakreditasi' => 'Unggul',
                            'proses' => 'Proses Akreditasi',
                            'belum' => 'Belum Terakreditasi',
                            'ditolak' => 'Tidak Terakreditasi',
                        ]"
                        :value="$filterAkreditasi"
                        form="pesantren-filter-form"
                    />

                    <x-ui.filter-select
                        name="filterStatus"
                        placeholder="Semua Status"
                        :options="['1' => 'Aktif', '0' => 'Tidak Aktif']"
                        :value="$filterStatus"
                        form="pesantren-filter-form"
                    />

                    <input type="hidden" name="sortField" x-model="sortField">
                    <input type="hidden" name="sortAsc" x-model="sortAsc">
                </form>
            </x-slot>

            <x-slot name="toolbar">
                <form method="POST" action="{{ route('admin.pesantren.export') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="search" value="{{ $search }}">
                    <input type="hidden" name="filterStatus" value="{{ $filterStatus }}">
                    <input type="hidden" name="filterAkreditasi" value="{{ $filterAkreditasi }}">
                    <input type="hidden" name="sortField" :value="sortField">
                    <input type="hidden" name="sortAsc" :value="sortAsc">
                    <x-ui.button type="submit" variant="primary" size="sm" icon="document">
                        Ekspor Data
                    </x-ui.button>
                </form>
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
                @forelse ($pesantrens as $user)
                <tr>
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
        </x-ui.table>
    </x-ui.index-layout>
</div>
@endsection
