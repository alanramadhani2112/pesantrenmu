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

        {{-- Table --}}
        <x-ui.table title="Daftar Role" subtitle="ID, nama, parameter, dan aksi untuk tiap role." :records="$roles">
            <x-slot name="filters">
                <form method="GET" action="{{ route('admin.roles.index') }}" id="roles-filter-form">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <x-datatable.search name="search" placeholder="Cari role..." :value="$search" form="roles-filter-form" />
                        <x-ui.table-per-page name="perPage" :value="$perPage" :options="[10, 25, 50]" form="roles-filter-form" />
                        <input type="hidden" name="sortField" value="{{ $sortField }}">
                        <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">
                    </div>
                </form>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th field="id" :sortField="$sortField" :sortAsc="$sortAsc" form="roles-filter-form" :min-width="false" class="w-70px">ID</x-ui.table-th>
                <x-ui.table-th field="name" :sortField="$sortField" :sortAsc="$sortAsc" form="roles-filter-form">Nama Role</x-ui.table-th>
                <x-ui.table-th field="parameter" :sortField="$sortField" :sortAsc="$sortAsc" form="roles-filter-form">Parameter</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse($roles as $role)
                    <tr>
                        <td>{{ $role->id }}</td>
                        <td class="fw-semibold">{{ $role->name }}</td>
                        <td><code>{{ $role->parameter }}</code></td>
                        <td class="text-end">
                            @if(! in_array($role->id, [1, 2, 3, 4], true))
                                <x-ui.action-menu>
                                    <x-ui.action-menu-item
                                        variant="primary"
                                        x-on:click="openEditModal({{ $role->id }}, '{{ addslashes($role->name) }}', '{{ addslashes($role->parameter) }}')"
                                    >
                                        <x-ui.icon name="pencil" class="fs-5" />
                                        Edit Role
                                    </x-ui.action-menu-item>

                                    <x-ui.action-menu-item
                                        variant="danger"
                                        data-delete-url="{{ route('admin.roles.destroy', $role->id) }}"
                                        x-on:click="confirmDelete($el.dataset.deleteUrl)"
                                    >
                                        <x-ui.icon name="trash" class="fs-5" />
                                        Hapus Role
                                    </x-ui.action-menu-item>
                                </x-ui.action-menu>
                            @else
                                <x-ui.badge variant="secondary">Role inti</x-ui.badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <x-ui.empty-state title="Belum ada role" description="Tambahkan role baru untuk mulai mengelola akses." class="py-10" />
                        </td>
                    </tr>
                @endforelse
            </x-slot>
        </x-ui.table>
    </x-ui.index-layout>

    <form id="role-delete-form" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Modal Create/Edit --}}
    <x-ui.modal name="role-modal" focusable>
        <form method="POST" x-bind:action="formAction" x-on:submit.prevent="submitForm($event)">
            @csrf
            <template x-if="isEditing">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <x-ui.modal-header
                title="Kelola Role"
                subtitle="Atur nama dan parameter role."
                icon="security-user"
            />

            <x-ui.modal-body>
                <div class="d-flex flex-column gap-4">
                    <x-ui.form-field label="Nama Role" for="role_name" :error="$errors->get('name')" required>
                        <x-ui.input name="name" id="role_name" x-model="form.name" placeholder="Contoh: Admin" required />
                    </x-ui.form-field>

                    <x-ui.form-field label="Parameter" for="role_parameter" :error="$errors->get('parameter')" required>
                        <x-ui.input name="parameter" id="role_parameter" x-model="form.parameter" placeholder="Contoh: admin" required />
                    </x-ui.form-field>
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

            confirmDelete(url) {
                window.SpmSwal.confirm({
                    title: 'Hapus role ini?',
                    text: 'Tindakan ini tidak dapat dibatalkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('role-delete-form');
                        form.action = url;
                        form.requestSubmit();
                    }
                });
            }
        };
    }
</script>
@endsection
