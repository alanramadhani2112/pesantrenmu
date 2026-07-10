@extends('layouts.app')

@section('content')
<div x-data="rolePermissionMatrix()" data-module-page="role-permission">
    <x-ui.index-layout
        title="Role & Permission Matrix"
        subtitle="Kelola hak akses tiap role melalui permission matrix."
    >
        <x-slot name="toolbar">
            <x-ui.button type="submit" form="matrix-form" variant="primary" size="sm" icon="check">
                Simpan Perubahan
            </x-ui.button>
        </x-slot>

        {{-- Matrix Table --}}
        <form id="matrix-form" method="POST" action="{{ route('admin.role-permission.save') }}">
            @csrf
            <input type="hidden" name="visible_permission_scope" value="1">
            @foreach($permissions as $permission)
                <input type="hidden" name="visible_permission_ids[]" value="{{ $permission->id }}">
            @endforeach

            <x-ui.table title="Matriks Permission" subtitle="{{ $permissions->count() }} permissions &middot; {{ $roles->count() }} roles" :records="null">
                <x-slot name="filters">
                    <form method="GET" action="{{ route('admin.role-permission.index') }}" id="role-permission-filter-form">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <x-datatable.search name="search" placeholder="Cari permission..." :value="$search" form="role-permission-filter-form" />
                            <x-ui.select name="group" size="sm" class="w-auto min-w-180px" onchange="this.form.submit()">
                                <option value="">Semua Group</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group }}" @selected($groupFilter === $group)>{{ ucfirst($group) }}</option>
                                @endforeach
                            </x-ui.select>
                        </div>
                    </form>
                </x-slot>

                <x-slot name="thead">
                    <x-ui.table-th class="ps-4" style="min-width: 200px;">Permission</x-ui.table-th>
                    <x-ui.table-th style="min-width: 150px;">Label</x-ui.table-th>
                    @foreach($roles as $role)
                        <x-ui.table-th align="center" style="min-width: 100px;">{{ $role->name }}</x-ui.table-th>
                    @endforeach
                </x-slot>

                <x-slot name="tbody">
                    @forelse($groupedPermissions as $group => $perms)
                        <tr class="table-secondary">
                            <td colspan="{{ 2 + $roles->count() }}" class="ps-4 fw-semibold text-uppercase fs-8 text-muted py-2">
                                {{ $group ?: 'Ungrouped' }}
                            </td>
                        </tr>
                        @foreach($perms as $permission)
                            <tr>
                                <td class="ps-4"><code class="fs-8">{{ $permission->key }}</code></td>
                                <td class="text-gray-700">{{ $permission->label }}</td>
                                @foreach($roles as $role)
                                    <td class="text-center">
                                        <input type="checkbox"
                                               name="matrix[{{ $role->id }}][{{ $permission->id }}]"
                                               class="form-check-input"
                                               @checked($matrix[$role->id][$permission->id] ?? false)>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ 2 + $roles->count() }}">
                                <x-ui.empty-state title="Tidak ada permission ditemukan" description="Coba ubah filter atau kata kunci pencarian." class="py-12" />
                            </td>
                        </tr>
                    @endforelse
                </x-slot>
            </x-ui.table>
        </form>
    </x-ui.index-layout>
</div>

@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('notification-received', {
                detail: { type: 'success', title: 'Berhasil!', message: @json(session('success')) }
            }));
        });
    </script>
@endif

<script>
    function rolePermissionMatrix() {
        return {
            // Placeholder for potential future interactivity (select all, etc.)
        };
    }
</script>
@endsection
