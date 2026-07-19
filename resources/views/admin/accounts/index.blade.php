@extends('layouts.app')

@section('content')
<div x-data="accountsPage()" data-module-page="accounts">
    <x-ui.index-layout
        title="Akun Pengguna"
        subtitle="Kelola akun admin, asesor, dan pesantren dari satu daftar."
    >
        <x-ui.table
            title="Daftar Akun"
            subtitle="Ringkasan akun pengguna menurut peran dan status akses."
            :records="$users"
        >
            <x-slot name="filters">
                <x-ui.tabs>
                    @foreach ($roles as $role)
                        <x-ui.tab
                            :active="$activeTab == $role->id"
                            :href="route('accounts.index', array_merge(request()->query(), ['activeTab' => $role->id]))"
                        >
                            {{ $role->name }}
                            <x-ui.badge variant="primary" class="ms-2">{{ $roleCounts[$role->id] ?? 0 }}</x-ui.badge>
                        </x-ui.tab>
                    @endforeach
                </x-ui.tabs>

                <form method="GET" action="{{ route('accounts.index') }}" id="accounts-filter-form" class="d-flex align-items-center gap-3 flex-wrap">
                    <input type="hidden" name="activeTab" value="{{ $activeTab }}">
                    <input type="hidden" name="perPage" value="{{ $perPage }}">
                    <input type="hidden" name="sortField" value="{{ $sortField }}">
                    <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">
                    <x-datatable.search name="search" placeholder="Cari nama atau email..." :value="$search" form="accounts-filter-form" onchange="this.form.submit()" />
                </form>
            </x-slot>

            <x-slot name="toolbar">
                <x-ui.button variant="primary" size="sm" icon="plus" x-on:click="openCreateModal()">
                    Tambah Akun
                </x-ui.button>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th :min-width="false" class="w-70px">No</x-ui.table-th>
                <x-ui.table-th field="name" :sortField="$sortField" :sortAsc="$sortAsc" form="accounts-filter-form">Pengguna</x-ui.table-th>
                <x-ui.table-th field="email" :sortField="$sortField" :sortAsc="$sortAsc" form="accounts-filter-form">Email</x-ui.table-th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($users as $index => $user)
                <tr>
                    <td class="text-muted fw-semibold">
                        {{ $users->firstItem() + $index }}
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="symbol symbol-40px">
                                <div class="symbol-label bg-light-primary text-primary fw-semibold text-uppercase">
                                    {{ substr($user->name, 0, 2) }}
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-1">
                                <span class="text-gray-900 fw-semibold fs-6">{{ $user->name }}</span>
                                @if ($user->sso_linked_at)
                                    <x-ui.badge variant="success" class="fs-8">
                                        <x-ui.icon name="shield-tick" class="fs-7 me-1" />
                                        Linked to Muhammadiyah ID
                                    </x-ui.badge>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-muted fw-semibold">
                        {{ $user->email }}
                    </td>
                    <td class="text-center">
                        <x-ui.status-badge :variant="$user->status ? 'success' : 'danger'">
                            {{ $user->status ? 'Aktif' : 'Non-Aktif' }}
                        </x-ui.status-badge>
                    </td>
                    <td class="text-end">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item x-on:click="openEditModal({{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role_id' => $user->role_id, 'status' => $user->status, 'sso_sync_role' => $user->sso_sync_role]) }})">
                                <x-ui.icon name="pencil" class="fs-5 text-gray-500" />
                                Edit Akun
                            </x-ui.action-menu-item>

                            @if ($user->sso_linked_at)
                                <x-ui.action-menu-item
                                    variant="warning"
                                    x-on:click="confirmUnlinkSso({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                >
                                    <x-ui.icon name="disconnect" class="fs-5" />
                                    Unlink SSO
                                </x-ui.action-menu-item>
                            @endif

                            @if ($user->id !== auth()->id())
                                <x-ui.action-menu-item
                                    :variant="$user->status ? 'warning' : 'success'"
                                    x-on:click="confirmToggleStatus({{ $user->id }}, {{ $user->status ? 1 : 0 }}, '{{ addslashes($user->name) }}')"
                                >
                                    <x-ui.icon :name="$user->status ? 'cross-circle' : 'check-circle'" class="fs-5" />
                                    {{ $user->status ? __('Non-Aktifkan') : __('Aktifkan') }}
                                </x-ui.action-menu-item>

                                <x-ui.action-menu-item
                                    variant="danger"
                                    x-on:click="confirmDeleteUser({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                >
                                    <x-ui.icon name="trash" class="fs-5" />
                                    Hapus Akun
                                </x-ui.action-menu-item>
                            @endif
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

    {{-- Create/Edit Modal --}}
    <x-ui.modal name="account-modal" :show="$errors->hasAny(['name', 'email', 'role_id', 'password', 'status', 'sso_sync_role'])" focusable>
        <form :action="isEditing ? '{{ route('accounts.update', '__ID__') }}'.replace('__ID__', formData.id) : '{{ route('accounts.store') }}'" method="POST">
            @csrf
            <template x-if="isEditing">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <x-ui.modal-header
                title="Kelola Akun"
                subtitle="Atur data akun, role, status akses, dan opsi SSO."
                icon="profile-user"
            />

            <x-ui.modal-body>
                <div class="d-flex flex-column gap-4">
                    <x-ui.form-field label="Nama" for="name" :error="$errors->get('name')" required>
                        <x-ui.input name="name" id="name" x-model="formData.name" placeholder="Contoh: Ahmad Fauzi" required autofocus />
                    </x-ui.form-field>

                    <x-ui.form-field label="Email" for="email" :error="$errors->get('email')" required>
                        <x-ui.input type="email" name="email" id="email" x-model="formData.email" placeholder="nama@domain.com" required />
                    </x-ui.form-field>

                    <x-ui.form-field label="Role" for="role_id" :error="$errors->get('role_id')" required>
                        <x-ui.select name="role_id" id="role_id" x-model="formData.role_id" required>
                            <option value="">Pilih Role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.form-field>

                    <x-ui.form-field
                        label="Password"
                        for="password"
                        :error="$errors->get('password')"
                        hint="Kosongkan untuk membuat password otomatis saat tambah akun, atau bila password tidak diubah saat edit."
                    >
                        <x-ui.input type="password" name="password" id="password" x-model="formData.password" placeholder="Kosongkan bila tidak diubah" />
                    </x-ui.form-field>

                    <template x-if="isEditing">
                        <x-ui.form-field hint="Jika aktif, role akan di-sync dari SSO saat login.">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input type="hidden" name="sso_sync_role" value="0">
                                <input type="checkbox" name="sso_sync_role" id="sso_sync_role" class="form-check-input" value="1" x-model="formData.sso_sync_role">
                                <label class="form-check-label fw-semibold text-gray-700" for="sso_sync_role">Sync role dari SSO</label>
                            </div>
                        </x-ui.form-field>
                    </template>

                    <x-ui.form-field>
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input type="hidden" name="status" value="0">
                            <input type="checkbox" name="status" id="status" class="form-check-input" value="1" x-model="formData.status">
                            <label class="form-check-label fw-semibold text-gray-700" for="status">Status Aktif</label>
                        </div>
                    </x-ui.form-field>
                </div>
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary" x-text="isEditing ? 'Perbarui' : 'Simpan'"></x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

    {{-- Hidden forms for actions --}}
    <form id="toggle-status-form" method="POST" action="{{ route('accounts.toggle-status') }}" class="d-none">
        @csrf
        <input type="hidden" name="user_id" id="toggle-user-id">
    </form>

    <form id="delete-user-form" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    <form id="unlink-sso-form" method="POST" action="{{ route('accounts.unlink-sso') }}" class="d-none">
        @csrf
        <input type="hidden" name="user_id" id="unlink-user-id">
    </form>
</div>

@push('scripts')
<script>
function accountsPage() {
    return {
        showModal: @js($errors->hasAny(['name', 'email', 'role_id', 'password', 'status', 'sso_sync_role'])),
        isEditing: @js(old('_method') === 'PUT'),
        formData: {
            id: @js(old('id', '')),
            name: @js(old('name', '')),
            email: @js(old('email', '')),
            role_id: @js(old('role_id', '')),
            password: '',
            status: @js(old('status', '1') == '1'),
            sso_sync_role: @js(old('sso_sync_role', '1') == '1'),
        },

        openCreateModal() {
            this.isEditing = false;
            this.formData = { id: '', name: '', email: '', role_id: '', password: '', status: true, sso_sync_role: true };
            this.$dispatch('open-modal', 'account-modal');
        },

        openEditModal(user) {
            this.isEditing = true;
            this.formData = {
                id: user.id,
                name: user.name,
                email: user.email,
                role_id: String(user.role_id),
                password: '',
                status: user.status == 1,
                sso_sync_role: Boolean(user.sso_sync_role),
            };
            this.$dispatch('open-modal', 'account-modal');
        },

        confirmToggleStatus(userId, currentStatus, name) {
            const action = currentStatus ? 'menonaktifkan' : 'mengaktifkan';
            window.SpmSwal.confirm({
                title: 'Konfirmasi',
                text: `Apakah Anda yakin ingin ${action} akun "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, lanjutkan',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('toggle-user-id').value = userId;
                    document.getElementById('toggle-status-form').requestSubmit();
                }
            });
        },

        confirmDeleteUser(userId, name) {
            window.SpmSwal.confirm({
                title: 'Hapus Akun?',
                text: `Akun "${name}" akan dihapus secara permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-user-form');
                    form.action = '{{ route('accounts.destroy', '__ID__') }}'.replace('__ID__', userId);
                    form.requestSubmit();
                }
            });
        },

        confirmUnlinkSso(userId, name) {
            window.SpmSwal.confirm({
                title: 'Unlink SSO?',
                text: `SSO akan di-unlink dari akun "${name}". Akun tetap aktif tetapi tidak terhubung dengan Muhammadiyah ID.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Unlink',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('unlink-user-id').value = userId;
                    document.getElementById('unlink-sso-form').requestSubmit();
                }
            });
        },
    };
}
</script>
@endpush
@endsection
