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
        <div class="d-flex align-items-center gap-3 mb-5 flex-wrap">
            <form method="GET" action="{{ route('admin.role-permission.index') }}" class="d-flex align-items-center gap-3 flex-wrap">
                <div class="position-relative" style="min-width: 240px;">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm ps-10"
                           placeholder="Cari permission..." x-on:input.debounce.400ms="$el.closest('form').submit()">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3">
                        <i class="ki-outline ki-magnifier fs-6 text-muted"></i>
                    </span>
                </div>

                <select name="group" class="form-select form-select-sm" style="min-width: 180px;" onchange="this.form.submit()">
                    <option value="">Semua Group</option>
                    @foreach($groups as $group)
                        <option value="{{ $group }}" @selected($groupFilter === $group)>{{ ucfirst($group) }}</option>
                    @endforeach
                </select>
            </form>

            <div class="ms-auto text-muted fs-8">
                {{ $permissions->count() }} permissions &middot; {{ $roles->count() }} roles
            </div>
        </div>

        {{-- Matrix Table --}}
        <form id="matrix-form" method="POST" action="{{ route('admin.role-permission.save') }}">
            @csrf
            <input type="hidden" name="visible_permission_scope" value="1">
            @foreach($permissions as $permission)
                <input type="hidden" name="visible_permission_ids[]" value="{{ $permission->id }}">
            @endforeach

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 fw-semibold" style="min-width: 200px;">Permission</th>
                            <th class="fw-semibold" style="min-width: 150px;">Label</th>
                            @foreach($roles as $role)
                                <th class="text-center fw-semibold" style="min-width: 100px;">{{ $role->name }}</th>
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
                </table>
            </div>
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
