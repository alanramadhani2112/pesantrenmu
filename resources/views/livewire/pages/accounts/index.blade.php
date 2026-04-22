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

<div>
    <x-slot name="header">{{ __('Account Management') }}</x-slot>

    <div class="py-12" x-data="adminManagement">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-datatable.layout title="Manajemen Akun" :records="$this->users">
                <x-slot name="filters">
                    <div class="flex flex-wrap items-center gap-1 bg-gray-50/50 p-1 rounded-xl border border-gray-100 mr-2">
                        <button wire:click="setTab(1)"
                            class="px-4 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-2
                        {{ $activeTab == 1 ? 'bg-[#1e3a5f] text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            Admin
                            <span class="py-0.5 px-1.5 rounded-md text-[9px] {{ $activeTab == 1 ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $this->getCountByRole(1) }}</span>
                        </button>
                        <button wire:click="setTab(2)"
                            class="px-4 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-2
                        {{ $activeTab == 2 ? 'bg-[#1e3a5f] text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            Asesor
                            <span class="py-0.5 px-1.5 rounded-md text-[9px] {{ $activeTab == 2 ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $this->getCountByRole(2) }}</span>
                        </button>
                        <button wire:click="setTab(3)"
                            class="px-4 py-1.5 text-[11px] font-bold rounded-lg transition-all flex items-center gap-2
                        {{ $activeTab == 3 ? 'bg-[#1e3a5f] text-white shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            Pesantren
                            <span class="py-0.5 px-1.5 rounded-md text-[9px] {{ $activeTab == 3 ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">{{ $this->getCountByRole(3) }}</span>
                        </button>
                    </div>

                    <x-datatable.search placeholder="Cari nama atau email..." />

                    <button wire:click="createUser" class="bg-[#1e3a5f] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-[#162d4a] transition-all shadow-sm active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Akun
                    </button>
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
                            @if($user->status)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-600 uppercase tracking-tight border border-green-100 shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Aktif
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 uppercase tracking-tight border border-rose-100 shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                Non-Aktif
                            </span>
                            @endif
                        </td>
                        <td class="py-5 px-4 text-right pr-6">
                            <div class="inline-block text-left" x-data="{ 
                                open: false,
                                dropdownPosition: { top: 0, left: 0 },
                                updatePosition() {
                                    let rect = this.$refs.btn.getBoundingClientRect();
                                    this.dropdownPosition = { 
                                        top: (rect.bottom + 5) + 'px', 
                                        left: (rect.right - 176) + 'px' 
                                    };
                                }
                            }">
                                <button x-ref="btn" @click="open = !open; if(open) updatePosition()" @click.away="open = false"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-gray-400 hover:text-gray-700 transition-colors bg-gray-50/50 rounded-lg group-hover:bg-gray-100">
                                    Aksi
                                    <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <template x-teleport="body">
                                    <div x-show="open"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        :style="`position: fixed; top: ${dropdownPosition.top}; left: ${dropdownPosition.left}; z-index: 9999;`"
                                        class="w-44 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 origin-top-right overflow-hidden shadow-slate-200/50" x-cloak>
                                        <button wire:click="editUser({{ $user->id }})" @click="open = false"
                                            class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition-colors gap-3 text-left">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit Akun
                                        </button>
                                        @if ($user->id !== auth()->id())
                                        <button @click="confirmToggleStatus($wire, {{ $user->id }}, {{ $user->status ? 1 : 0 }}, '{{ addslashes($user->name) }}'); open = false"
                                            class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold {{ $user->status ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} transition-colors gap-3 border-t border-gray-50/50 text-left">
                                            <svg class="w-4 h-4 {{ $user->status ? 'text-amber-400' : 'text-emerald-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            {{ $user->status ? __('Non-Aktifkan') : __('Aktifkan') }}
                                        </button>
                                        <button @click="confirmDeleteUser($wire, {{ $user->id }}, '{{ addslashes($user->name) }}'); open = false"
                                            class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-rose-600 hover:bg-rose-50 transition-colors gap-3 border-t border-gray-50/50 text-left">
                                            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Hapus Akun
                                        </button>
                                        @endif
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-10 h-10 text-gray-400/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-xs text-gray-400 font-bold">Data tidak ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </x-slot>
            </x-datatable.layout>
        </div>
    </div>

    <x-modal name="account-modal" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="saveAccount" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ $isEditing ? __('Edit Account') : __('Create Account') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ $isEditing ? __('Update account details.') : __('Add a new user account to the system.') }}
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="name" value="{{ __('Name') }}" />
                    <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" required
                        autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="{{ __('Email') }}" />
                    <x-text-input wire:model="email" id="email" type="email" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="role_id" value="{{ __('Role') }}" />
                    <select wire:model="role_id" id="role_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        required>
                        <option value="">Select Role</option>
                        @foreach ($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="{{ __('Password') }}" />
                    <x-text-input wire:model="password" id="password" type="password" class="mt-1 block w-full"
                        :required="!$isEditing" />
                    @if ($isEditing)
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password.</p>
                    @endif
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="block mt-4">
                    <label for="status" class="inline-flex items-center">
                        <input id="status" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="status" wire:model="status">
                        <span class="ms-2 text-sm text-gray-600">{{ __('Status Aktif') }}</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ms-3">
                    {{ $isEditing ? __('Update') : __('Save') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('swal:success', (data) => {
                Swal.fire({
                    title: data[0].title,
                    text: data[0].text,
                    icon: 'success',
                    confirmButtonColor: '#10b981', // emerald-500 matching the theme
                    confirmButtonText: 'OK',
                    timer: 3000,
                    timerProgressBar: true
                });
            });

            Livewire.on('swal:error', (data) => {
                Swal.fire({
                    title: data[0].title,
                    text: data[0].text,
                    icon: 'error',
                    confirmButtonColor: '#ef4444', // red-500
                    confirmButtonText: 'OK'
                });
            });
        });
    </script>
</div>