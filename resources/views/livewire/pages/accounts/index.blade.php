<?php

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    use \Livewire\WithPagination;

    public $roles;
    public $name;
    public $email;
    public $password;
    public $role_id;
    public $status = true; // Default to true (active)
    public $sso_sync_role = true;
    public $userId;
    public $search = '';
    public $perPage = 10;
    public $sortField = 'id';
    public $sortAsc = false;
    public $activeTab = 1; // Default to Admin role (ID 1)
    public $isEditing = false;

    public function mount()
    {
        if (!auth()->user()->canAccessAdminArea()) {
                    abort(403);
                }
        $roleService = app(\App\Services\RoleService::class);
        $this->roles = $roleService->getAllRoles();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedActiveTab()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortAsc = true;
        }

        $this->sortField = $field;
    }

    public function getUsersProperty()
    {
        $userService = app(\App\Services\UserService::class);
        return $userService->getPaginatedAccounts(
            $this->activeTab,
            $this->search,
            $this->perPage,
            $this->sortField,
            $this->sortAsc
        );
    }

    public function getCountByRole($roleId)
    {
        $userService = app(\App\Services\UserService::class);
        return $userService->getCountByRole($roleId);
    }

    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role_id = '';
        $this->status = true;
        $this->sso_sync_role = true;
        $this->userId = null;
        $this->isEditing = false;
        $this->resetErrorBag();
    }

    public function createUser()
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'account-modal');
    }

    public function editUser($id)
    {
        $userService = app(\App\Services\UserService::class);
        $user = $userService->findUser($id);
        
        if (!$user) {
            $this->dispatch('swal:error', title: 'Gagal!', text: 'Data tidak ditemukan.');
            return;
        }

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_id = $user->role_id;
        $this->status = $user->status == 1;
        $this->sso_sync_role = (bool) $user->sso_sync_role;
        $this->password = '';
        $this->isEditing = true;
        $this->dispatch('open-modal', 'account-modal');
    }

    public function saveAccount()
    {
        Gate::authorize('account.create');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . ($this->userId ?? 'NULL')],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['boolean'],
            'sso_sync_role' => ['boolean'],
            'password' => ['nullable', 'string'],
        ];

        $validatedData = $this->validate($rules);

        // If creating and no password provided, generate a random one
        if (!$this->isEditing && empty($validatedData['password'])) {
            $validatedData['password'] = \Illuminate\Support\Str::random(16);
        }

        $userService = app(\App\Services\UserService::class);
        $userService->saveAccount($validatedData, $this->userId);

        $msg = $this->isEditing ? 'Data Akun berhasil diperbarui.' : 'Data Akun berhasil ditambahkan.';
        $this->dispatch('swal:success', title: 'Berhasil!', text: $msg);

        $this->dispatch('close-modal', 'account-modal');
        $this->resetForm();
    }

    public function deleteUser($id)
    {
        Gate::authorize('account.delete');

        $userService = app(\App\Services\UserService::class);
        if ($userService->deleteAccount($id)) {
            $this->dispatch('swal:success', title: 'Berhasil!', text: 'Data Akun berhasil dihapus.');
        } else {
            $this->dispatch('swal:error', title: 'Gagal!', text: 'Anda tidak dapat menghapus akun Anda sendiri atau terjadi kesalahan.');
        }
    }

    public function toggleStatus($id)
    {
        Gate::authorize('account.toggle');

        $userService = app(\App\Services\UserService::class);
        if ($userService->toggleAccountStatus($id)) {
            $this->dispatch('swal:success', title: 'Berhasil!', text: 'Status akun berhasil diubah.');
        }
    }

    public function unlinkSso($id)
    {
        Gate::authorize('account.create');

        $user = User::find($id);

        if (!$user) {
            $this->dispatch('swal:error', title: 'Gagal!', text: 'Data tidak ditemukan.');
            return;
        }

        // Delete profile data (SSO profile)
        $user->profile_data()->delete();

        // Reset sso_linked_at
        $user->update(['sso_linked_at' => null]);

        $this->dispatch('swal:success', title: 'Berhasil!', text: 'SSO berhasil di-unlink dari akun ' . $user->name . '.');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }
}; ?>

<div x-data="adminManagement" data-module-page="accounts">
    <x-slot name="header">{{ __('Akun Pengguna') }}</x-slot>

    <x-ui.index-layout
        title="Akun Pengguna"
        subtitle="Kelola akun admin, asesor, dan pesantren dari satu daftar."
    >
        <x-datatable.layout
            title="Daftar Akun"
            subtitle="Ringkasan akun pengguna menurut peran dan status akses."
            :records="$this->users"
        >
            <x-slot name="filters">
                <x-ui.tabs>
                    <x-ui.tab :active="$activeTab == 1" wire:click="setTab(1)">
                        Admin
                        <x-ui.badge variant="primary" class="ms-2">{{ $this->getCountByRole(1) }}</x-ui.badge>
                    </x-ui.tab>

                    <x-ui.tab :active="$activeTab == 2" wire:click="setTab(2)">
                        Asesor
                        <x-ui.badge variant="primary" class="ms-2">{{ $this->getCountByRole(2) }}</x-ui.badge>
                    </x-ui.tab>

                    <x-ui.tab :active="$activeTab == 3" wire:click="setTab(3)">
                        Pesantren
                        <x-ui.badge variant="primary" class="ms-2">{{ $this->getCountByRole(3) }}</x-ui.badge>
                    </x-ui.tab>
                </x-ui.tabs>

                <x-datatable.search placeholder="Cari nama atau email..." />
            </x-slot>

            <x-slot name="toolbar">
                <x-ui.button wire:click="createUser" variant="primary" size="sm" icon="plus">
                    Tambah Akun
                </x-ui.button>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th :min-width="false" class="w-70px">No</x-ui.table-th>
                <x-datatable.th field="name" :sortField="$sortField" :sortAsc="$sortAsc">
                    Pengguna
                </x-datatable.th>
                <x-datatable.th field="email" :sortField="$sortField" :sortAsc="$sortAsc">
                    Email
                </x-datatable.th>
                <x-ui.table-th align="center">Status</x-ui.table-th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->users as $index => $user)
                <tr wire:key="user-{{ $user->id }}">
                    <td class="text-muted fw-semibold">
                        {{ $this->users->firstItem() + $index }}
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
                                    <span class="badge badge-light-success fs-8 fw-semibold">
                                        <i class="ki-duotone ki-shield-tick fs-7 me-1">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        Linked to Muhammadiyah ID
                                    </span>
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
                            <x-ui.action-menu-item wire:click="editUser({{ $user->id }})">
                                <x-ui.icon name="pencil" class="fs-5 text-gray-500" />
                                Edit Akun
                            </x-ui.action-menu-item>

                            @if ($user->sso_linked_at)
                                <x-ui.action-menu-item
                                    variant="warning"
                                    x-on:click="confirmUnlinkSso($wire, {{ $user->id }}, '{{ addslashes($user->name) }}')"
                                >
                                    <x-ui.icon name="disconnect" class="fs-5" />
                                    Unlink SSO
                                </x-ui.action-menu-item>
                            @endif

                            @if ($user->id !== auth()->id())
                                <x-ui.action-menu-item
                                    :variant="$user->status ? 'warning' : 'success'"
                                    x-on:click="confirmToggleStatus($wire, {{ $user->id }}, {{ $user->status ? 1 : 0 }}, '{{ addslashes($user->name) }}')"
                                >
                                    <x-ui.icon :name="$user->status ? 'cross-circle' : 'check-circle'" class="fs-5" />
                                    {{ $user->status ? __('Non-Aktifkan') : __('Aktifkan') }}
                                </x-ui.action-menu-item>

                                <x-ui.action-menu-item
                                    variant="danger"
                                    x-on:click="confirmDeleteUser($wire, {{ $user->id }}, '{{ addslashes($user->name) }}')"
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
        </x-datatable.layout>
    </x-ui.index-layout>

    <x-ui.modal name="account-modal" :show="$errors->isNotEmpty()" focusable>
        <form x-on:submit.prevent="confirmSaveAccount($wire)">
            <x-ui.modal-header
                :title="$isEditing ? __('Edit Akun') : __('Tambah Akun')"
                :subtitle="$isEditing ? __('Perbarui detail akun pengguna.') : __('Tambahkan akun pengguna baru ke sistem.')"
                icon="profile-user"
            />

            <x-ui.modal-body>
                <x-ui.form-field label="{{ __('Nama') }}" for="name" :error="$errors->get('name')">
                    <x-ui.input model="name" id="name" required autofocus />
                </x-ui.form-field>

                <x-ui.form-field label="{{ __('Email') }}" for="email" :error="$errors->get('email')">
                    <x-ui.input model="email" id="email" type="email" required />
                </x-ui.form-field>

                <x-ui.form-field label="{{ __('Role') }}" for="role_id" :error="$errors->get('role_id')">
                    <x-ui.select model="role_id" id="role_id" placeholder="Pilih Role" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.form-field>

                <x-ui.form-field
                    label="{{ __('Password') }}"
                    for="password"
                    :error="$errors->get('password')"
                    :hint="$isEditing ? __('Kosongkan bila password tidak diubah.') : __('Kosongkan untuk membuat password otomatis. Pengguna juga dapat masuk melalui SSO tanpa password manual.')"
                >
                    <x-ui.input model="password" id="password" type="password" />
                </x-ui.form-field>

                @if ($isEditing)
                    <x-ui.form-field
                        label="{{ __('Sync Role dari SSO') }}"
                        for="sso_sync_role"
                        :error="$errors->get('sso_sync_role')"
                        hint="{{ __('Jika aktif, role akan di-sync dari SSO saat login. Jika non-aktif, role yang diset admin akan dipertahankan.') }}"
                    >
                        <x-ui.checkbox model="sso_sync_role" id="sso_sync_role" label="{{ __('Sync role dari SSO') }}" />
                    </x-ui.form-field>
                @endif

                <x-ui.checkbox model="status" id="status" label="{{ __('Status Aktif') }}" />
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">
                    {{ __('Batal') }}
                </x-ui.button>

                <x-ui.button type="submit" variant="primary">
                    {{ $isEditing ? __('Perbarui') : __('Simpan') }}
                </x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-ui.modal>

</div>
