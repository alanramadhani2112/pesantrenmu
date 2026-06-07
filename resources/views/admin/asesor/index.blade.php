@extends('layouts.app')

@section('content')
<div data-module-page="admin-asesor" x-data="asesorIndex()">
    <x-slot name="header">{{ __('Asesor') }}</x-slot>

    <x-ui.index-layout
        title="Asesor"
        subtitle="Kelola data asesor, status akun, dan riwayat penugasan."
    >
        <x-datatable.layout
            title="Daftar Asesor"
            subtitle="Daftar asesor beserta peran kelompok, penugasan aktif, dan status akses."
            :records="$asesors"
        >
            <x-slot name="filters">
                <x-ui.filter-bar>
                    <form method="GET" action="{{ route('admin.asesor.index') }}" id="asesor-filter-form" class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="position-relative">
                            <x-ui.icon name="magnifier" class="fs-3 position-absolute top-50 translate-middle-y ms-4 text-gray-500" />
                            <input
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Cari Asesor..."
                                class="form-control form-control-solid ps-12"
                                style="min-width: 240px;"
                            >
                        </div>

                        <select name="filterPeran" class="form-select form-select-sm form-select-solid" style="max-width: 200px;">
                            <option value="">Semua Peran</option>
                            <option value="1" @selected($filterPeran === '1')>Ketua Kelompok</option>
                            <option value="2" @selected($filterPeran === '2')>Anggota Kelompok</option>
                        </select>

                        <select name="filterPenugasan" class="form-select form-select-sm form-select-solid" style="max-width: 200px;">
                            <option value="">Semua Penugasan</option>
                            <option value="bertugas" @selected($filterPenugasan === 'bertugas')>Sedang Bertugas</option>
                            <option value="bebas" @selected($filterPenugasan === 'bebas')>Bebas Tugas</option>
                        </select>

                        <select name="filterStatus" class="form-select form-select-sm form-select-solid" style="max-width: 200px;">
                            <option value="">Semua Status</option>
                            <option value="1" @selected($filterStatus === '1')>Aktif</option>
                            <option value="0" @selected($filterStatus === '0')>Tidak Aktif</option>
                        </select>

                        <select name="perPage" class="form-select form-select-sm" style="width: 80px;">
                            @foreach([10, 25, 50] as $pp)
                                <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
                            @endforeach
                        </select>

                        <input type="hidden" name="sortField" value="{{ $sortField }}">
                        <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">

                        <x-ui.button type="submit" variant="primary" size="sm">
                            <x-ui.icon name="magnifier" class="fs-5 me-1" />
                            Cari
                        </x-ui.button>
                    </form>
                </x-ui.filter-bar>
            </x-slot>

            <x-slot name="toolbar">
                <form method="POST" action="{{ route('admin.asesor.export') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="search" value="{{ $search }}">
                    <input type="hidden" name="filterPeran" value="{{ $filterPeran }}">
                    <input type="hidden" name="filterPenugasan" value="{{ $filterPenugasan }}">
                    <input type="hidden" name="filterStatus" value="{{ $filterStatus }}">
                    <input type="hidden" name="sortField" value="{{ $sortField }}">
                    <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">
                    <x-ui.button type="submit" variant="primary" size="sm" icon="document">
                        Ekspor Data
                    </x-ui.button>
                </form>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th :min-width="false" align="center" class="w-60px">
                    <x-ui.table-checkbox model="selectAll" label="Pilih semua asesor" />
                </x-ui.table-th>

                <x-datatable.th field="name" :sortField="$sortField" :sortAsc="$sortAsc">
                    Asesor
                </x-datatable.th>
                <x-ui.table-th>Pesantren Ditangani</x-ui.table-th>
                <x-ui.table-th align="center">Peran Kelompok</x-ui.table-th>
                <x-ui.table-th align="center">Status Penugasan</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($asesors as $user)
                <tr>
                    <td class="text-center">
                        <x-ui.table-checkbox model="selectedIds" :value="$user->id" :label="'Pilih ' . $user->name" />
                    </td>
                    <td>
                        <span class="text-gray-900 fw-semibold fs-6">{{ $user->name }}</span>
                    </td>
                    <td>
                        @php
                        $asesorModel = $user->asesor;
                        $latestTask = $asesorModel ? $asesorModel->assessments->sortByDesc('created_at')->first() : null;
                        $pesantrenName = $latestTask?->akreditasi?->user?->pesantren?->nama_pesantren
                            ?? $latestTask?->akreditasi?->user?->name
                            ?? '-';
                        @endphp
                        <span class="text-muted fw-semibold">{{ $pesantrenName }}</span>
                    </td>
                    <td class="text-center">
                        @if ($latestTask)
                        <x-ui.status-badge :variant="$latestTask->tipe == 1 ? 'primary' : 'info'">
                            {{ $latestTask->tipe == 1 ? 'Ketua Kelompok' : 'Anggota Kelompok' }}
                        </x-ui.status-badge>
                        @else
                        <span class="text-muted fw-semibold">-</span>
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
                        } elseif (in_array($statusAkreditasi, [1, 2])) {
                            $penugasanText = 'Selesai';
                            $penugasanVariant = 'secondary';
                        }
                        @endphp
                        <x-ui.status-badge :variant="$penugasanVariant">
                            {{ $penugasanText }}
                        </x-ui.status-badge>
                        @else
                        <span class="text-muted fw-semibold">-</span>
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

                            <form method="POST" action="{{ route('admin.asesor.toggle-status') }}" class="d-inline toggle-status-form">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <x-ui.action-menu-item
                                    type="submit"
                                    :variant="$user->status == 1 ? 'danger' : 'success'"
                                    x-on:click.prevent="confirmToggle(event, {{ $user->id }}, {{ $user->status }}, '{{ addslashes($user->name) }}', 'Asesor')"
                                >
                                    <x-ui.icon :name="$user->status == 1 ? 'cross-circle' : 'check-circle'" class="fs-5" />
                                    {{ $user->status == 1 ? 'Nonaktifkan' : 'Aktifkan Kembali' }}
                                </x-ui.action-menu-item>
                            </form>
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

        <div class="d-flex justify-content-center mt-4">
            {{ $asesors->appends(compact('search', 'filterPeran', 'filterPenugasan', 'filterStatus', 'perPage', 'sortField', 'sortAsc'))->links() }}
        </div>
    </x-ui.index-layout>
</div>
@endsection

@push('scripts')
<script>
    function asesorIndex() {
        return {
            confirmToggle(event, id, currentStatus, name, label) {
                const nextAction = Number(currentStatus) === 1 ? 'nonaktifkan' : 'aktifkan';
                const title = `${nextAction.charAt(0).toUpperCase()}${nextAction.slice(1)} ${label}?`;
                const text = `${label} ${name} akan di${nextAction}.`;

                window.SpmSwal.fire({
                    title,
                    text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: `Ya, ${nextAction}`,
                }).then((result) => {
                    if (result.isConfirmed) {
                        event.target.closest('form').submit();
                    }
                });
            }
        };
    }
</script>
@endpush
