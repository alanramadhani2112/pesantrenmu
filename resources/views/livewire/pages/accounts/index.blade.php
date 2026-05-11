<?php

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
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
    public $userId;
    public $search = '';
    public $perPage = 10;
    public $sortField = 'id';
    public $sortAsc = false;
    public $activeTab = 1; // Default to Admin role (ID 1)
    public $isEditing = false;

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
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
        $this->password = '';
        $this->isEditing = true;
        $this->dispatch('open-modal', 'account-modal');
    }

    public function saveAccount()
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . ($this->userId ?? 'NULL')],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['boolean'],
            'password' => ['nullable', 'string']
        ];

        if (!$this->isEditing) {
            $rules['password'] = ['required', 'string'];
        }

        $validatedData = $this->validate($rules);

        $userService = app(\App\Services\UserService::class);
        $userService->saveAccount($validatedData, $this->userId);

        $msg = $this->isEditing ? 'Data Akun berhasil diperbarui.' : 'Data Akun berhasil ditambahkan.';
        $this->dispatch('swal:success', title: 'Berhasil!', text: $msg);

        $this->dispatch('close-modal', 'account-modal');
        $this->resetForm();
    }

    public function deleteUser($id)
    {
        $userService = app(\App\Services\UserService::class);
        if ($userService->deleteAccount($id)) {
            $this->dispatch('swal:success', title: 'Berhasil!', text: 'Data Akun berhasil dihapus.');
        } else {
            $this->dispatch('swal:error', title: 'Gagal!', text: 'Anda tidak dapat menghapus akun Anda sendiri atau terjadi kesalahan.');
        }
    }

    public function toggleStatus($id)
    {
        $userService = app(\App\Services\UserService::class);
        if ($userService->toggleAccountStatus($id)) {
            $this->dispatch('swal:success', title: 'Berhasil!', text: 'Status akun berhasil diubah.');
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }
}; ?>

<div x-data="adminManagement" data-module-page="accounts">
    <x-slot name="header">{{ __('Account Management') }}</x-slot>

    <x-ui.page
        title="Accounts"
        subtitle="Kelola akun admin, asesor, dan pesantren dari satu daftar."
    >
            <x-datatable.layout title="Manajemen Akun" :records="$this->users">
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

                    <x-ui.button wire:click="createUser" variant="primary" size="sm">
                        <x-ui.icon name="plus" class="fs-4 me-1" />
                        Tambah Akun
                    </x-ui.button>
                </x-slot>

                <x-slot name="thead">
                    <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest w-16">NO</th>
                    <x-datatable.th field="name" :sortField="$sortField" :sortAsc="$sortAsc">
                        PENGGUNA
                    </x-datatable.th>
                    <x-datatable.th field="email" :sortField="$sortField" :sortAsc="$sortAsc">
                        EMAIL
                    </x-datatable.th>
                    <th class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 uppercase tracking-widest">STATUS</th>
                    <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8">AKSI</th>
                </x-slot>

                <x-slot name="tbody">
                    @forelse ($this->users as $index => $user)
                    <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0" wire:key="user-{{ $user->id }}">
                        <td class="py-5 px-4 text-xs font-bold text-gray-400">
                            {{ $this->users->firstItem() + $index }}
                        </td>
                        <td class="py-5 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-[#1e3a5f] font-bold text-xs ring-1 ring-slate-200 shadow-sm uppercase">
                                    {{ substr($user->name, 0, 2) }}
                                </div>
                                <span class="text-sm font-bold text-[#374151]">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="py-5 px-4 text-xs font-bold text-gray-500">
                            {{ $user->email }}
                        </td>
                        <td class="py-5 px-4 text-center">
                            <x-ui.status-badge :variant="$user->status ? 'success' : 'danger'" class="text-uppercase">
                                {{ $user->status ? 'Aktif' : 'Non-Aktif' }}
                            </x-ui.status-badge>
                        </td>
                        <td class="py-5 px-4 text-right pr-6">
                            <x-ui.action-menu>
                                <x-ui.action-menu-item wire:click="editUser({{ $user->id }})">
                                    <x-ui.icon name="pencil" class="fs-5 text-gray-500" />
                                    Edit Akun
                                </x-ui.action-menu-item>

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
    </x-ui.page>

    <x-modal name="account-modal" :show="$errors->isNotEmpty()" focusable>
        <form x-on:submit.prevent="confirmSaveAccount($wire)">
            <x-ui.modal-header
                :title="$isEditing ? __('Edit Account') : __('Create Account')"
                :subtitle="$isEditing ? __('Update account details.') : __('Add a new user account to the system.')"
                icon="profile-user"
            />

            <x-ui.modal-body>
                <x-ui.form-field label="{{ __('Name') }}" for="name" :error="$errors->get('name')">
                    <x-ui.input model="name" id="name" required autofocus />
                </x-ui.form-field>

                <x-ui.form-field label="{{ __('Email') }}" for="email" :error="$errors->get('email')">
                    <x-ui.input model="email" id="email" type="email" required />
                </x-ui.form-field>

                <x-ui.form-field label="{{ __('Role') }}" for="role_id" :error="$errors->get('role_id')">
                    <x-ui.select model="role_id" id="role_id" placeholder="Select Role" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.form-field>

                <x-ui.form-field
                    label="{{ __('Password') }}"
                    for="password"
                    :error="$errors->get('password')"
                    :hint="$isEditing ? 'Leave blank to keep current password.' : null"
                >
                    <x-ui.input model="password" id="password" type="password" :required="!$isEditing" />
                </x-ui.form-field>

                <x-ui.checkbox model="status" id="status" label="{{ __('Status Aktif') }}" />
            </x-ui.modal-body>

            <x-ui.modal-footer>
                <x-ui.button type="button" variant="light" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-ui.button>

                <x-ui.button type="submit" variant="primary">
                    {{ $isEditing ? __('Update') : __('Save') }}
                </x-ui.button>
            </x-ui.modal-footer>
        </form>
    </x-modal>

</div>
