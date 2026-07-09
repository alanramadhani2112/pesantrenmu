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

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.role-permission.index') }}" id="role-permission-filter-form" class="mb-5">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <x-datatable.search name="search" placeholder="Cari permission..." :value="$search" form="role-permission-filter-form" />

                <x-ui.select name="group" size="sm" class="w-auto min-w-180px" onchange="this.form.submit()">
                    <option value="">Semua Group</option>
                    @foreach($groups as $group)
                        <option value="{{ $group }}" @selected($groupFilter === $group)>{{ ucfirst($group) }}</option>
                    @endforeach
                </x-ui.select>

                <div class="ms-auto text-muted fs-8">
                    {{ $permissions->count() }} permissions &middot; {{ $roles->count() }} roles
                </div>
            </div>
        </form>

        {{-- Matrix Table --}}
        <form id="matrix-form" method="POST" action="{{ route('admin.role-permission.save') }}">
            @csrf
            <input type="hidden" name="visible_permission_scope" value="1">
            @foreach($permissions as $permission)
                <input type="hidden" name="visible_permission_ids[]" value="{{ $permission->id }}">
            @endforeach

            <x-ui.simple-table dense table-class="table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <x-ui.table-th class="ps-4" style="min-width: 200px;">Permission</x-ui.table-th>
                        <x-ui.table-th style="min-width: 150px;">Label</x-ui.table-th>
                        @foreach($roles as $role)
                            <x-ui.table-th align="center" style="min-width: 100px;">{{ $role->name }}</x-ui.table-th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                        @foreach($groupedPermissions as $group => $perms)
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
                        @endforeach
                </tbody>
            </x-ui.simple-table>
        </form>

        @if($permissions->isEmpty())
            <x-ui.empty-state
                title="Tidak ada permission ditemukan"
                description="Coba ubah filter atau kata kunci pencarian."
                class="py-12"
            />
        @endif
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
