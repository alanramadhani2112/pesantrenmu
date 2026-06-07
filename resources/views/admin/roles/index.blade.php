@extends('layouts.app')

@section('content')
<div x-data="roleManager()" data-module-page="roles">
    <x-ui.index-layout
        title="Manajemen Role"
        subtitle="Kelola role dan parameter akses sistem."
    >
        <x-slot name="toolbar">
            <x-ui.button variant="primary" size="sm" icon="plus" x-on:click="openCreateModal()">
                Tambah Role
            </x-ui.button>
        </x-slot>

        {{-- Search --}}
        <div class="d-flex align-items-center gap-3 mb-5">
            <form method="GET" action="{{ route('admin.roles.index') }}" class="d-flex align-items-center gap-3 flex-grow-1">
                <div class="position-relative flex-grow-1" style="max-width: 320px;">
                    <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm ps-10"
                           placeholder="Cari role..." x-on:input.debounce.400ms="$el.closest('form').submit()">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-3">
                        <i class="ki-outline ki-magnifier fs-6 text-muted"></i>
                    </span>
                </div>
                <input type="hidden" name="sort" value="{{ $sortField }}">
                <input type="hidden" name="direction" value="{{ $sortAsc ? 'asc' : 'desc' }}">
            </form>
        </div>

        {{-- Table --}}
        <x-ui.simple-table>
            <thead>
                <tr>
                    <x-ui.table-th>
                        <a href="{{ route('admin.roles.index', array_merge(request()->query(), ['sort' => 'id', 'direction' => ($sortField === 'id' && $sortAsc) ? 'desc' : 'asc'])) }}" class="text-dark text-hover-primary">
                            ID @if($sortField === 'id') <i class="ki-outline ki-arrow-{{ $sortAsc ? 'up' : 'down' }} fs-7"></i> @endif
                        </a>
                    </x-ui.table-th>
                    <x-ui.table-th>
                        <a href="{{ route('admin.roles.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => ($sortField === 'name' && $sortAsc) ? 'desc' : 'asc'])) }}" class="text-dark text-hover-primary">
                            Nama Role @if($sortField === 'name') <i class="ki-outline ki-arrow-{{ $sortAsc ? 'up' : 'down' }} fs-7"></i> @endif
                        </a>
                    </x-ui.table-th>
                    <x-ui.table-th>
                        <a href="{{ route('admin.roles.index', array_merge(request()->query(), ['sort' => 'parameter', 'direction' => ($sortField === 'parameter' && $sortAsc) ? 'desc' : 'asc'])) }}" class="text-dark text-hover-primary">
                            Parameter @if($sortField === 'parameter') <i class="ki-outline ki-arrow-{{ $sortAsc ? 'up' : 'down' }} fs-7"></i> @endif
                        </a>
                    </x-ui.table-th>
                    <x-ui.table-th align="end">Aksi</x-ui.table-th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td>{{ $role->id }}</td>
                        <td class="fw-semibold">{{ $role->name }}</td>
                        <td><code>{{ $role->parameter }}</code></td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <x-ui.icon-button
                                    icon="pencil"
                                    label="Edit"
                                    variant="primary"
                                    x-on:click="openEditModal({{ $role->id }}, '{{ addslashes($role->name) }}', '{{ addslashes($role->parameter) }}')"
                                />
                                <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}" class="d-inline"
                                      x-on:submit.prevent="confirmDelete($event)">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.icon-button
                                        type="submit"
                                        icon="trash"
                                        label="Hapus"
                                        variant="danger"
                                    />
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <x-ui.empty-state title="Belum ada role" description="Tambahkan role baru untuk mulai mengelola akses." class="py-10" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-ui.simple-table>

        <div class="mt-5">
            {{ $roles->links() }}
        </div>
    </x-ui.index-layout>

    {{-- Modal Create/Edit --}}
    <x-ui.modal name="role-modal" focusable>
        <form method="POST" x-bind:action="formAction" x-on:submit.prevent="submitForm($event)">
            @csrf
            <template x-if="isEditing">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <x-ui.modal-header
                x-bind:title="isEditing ? 'Edit Role' : 'Tambah Role'"
                subtitle="Atur nama dan parameter role."
                icon="security-user"
            />

            <x-ui.modal-body>
                <div class="mb-5">
                    <label class="form-label required" for="role_name">Nama Role</label>
                    <input type="text" name="name" id="role_name" x-model="form.name"
                           class="form-control" placeholder="Contoh: Admin" required>
                    @error('name') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-0">
                    <label class="form-label required" for="role_parameter">Parameter</label>
                    <input type="text" name="parameter" id="role_parameter" x-model="form.parameter"
                           class="form-control" placeholder="Contoh: admin" required>
                    @error('parameter') <div class="text-danger fs-8 mt-1">{{ $message }}</div> @enderror
                </div>
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary" x-text="isEditing ? 'Perbarui' : 'Simpan'"></x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>
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

@if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(new CustomEvent('notification-received', {
                detail: { type: 'error', title: 'Gagal', message: @json(session('error')) }
            }));
        });
    </script>
@endif

<script>
    function roleManager() {
        return {
            isEditing: false,
            formAction: '{{ route("admin.roles.store") }}',
            form: { name: '', parameter: '' },

            openCreateModal() {
                this.isEditing = false;
                this.formAction = '{{ route("admin.roles.store") }}';
                this.form = { name: '', parameter: '' };
                this.$dispatch('open-modal', 'role-modal');
            },

            openEditModal(id, name, parameter) {
                this.isEditing = true;
                this.formAction = '{{ url("admin/roles") }}/' + id;
                this.form = { name, parameter };
                this.$dispatch('open-modal', 'role-modal');
            },

            submitForm(e) {
                e.target.submit();
            },

            confirmDelete(e) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Hapus role ini?',
                        text: 'Tindakan ini tidak dapat dibatalkan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                    }).then((result) => {
                        if (result.isConfirmed) e.target.submit();
                    });
                } else {
                    if (confirm('Hapus role ini?')) e.target.submit();
                }
            }
        };
    }
</script>
@endsection
