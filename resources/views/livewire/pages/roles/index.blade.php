<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    use \Livewire\WithPagination;

    public $name;
    public $parameter;
    public $roleId;
    public $isEditing = false;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'id';
    public $sortAsc = false;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
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

    public function mount()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function getRolesProperty()
    {
        $roleService = app(\App\Services\RoleService::class);
        return $roleService->getPaginatedRoles($this->search, $this->perPage, $this->sortField, $this->sortAsc);
    }

    public function resetForm()
    {
        $this->name = '';
        $this->parameter = '';
        $this->roleId = null;
        $this->isEditing = false;
        $this->resetErrorBag();
    }

    public function createRole()
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'role-modal');
    }

    public function editRole($id)
    {
        $roleService = app(\App\Services\RoleService::class);
        $role = $roleService->findRole($id);
        if (!$role) return;

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->parameter = $role->parameter;
        $this->isEditing = true;
        $this->dispatch('open-modal', 'role-modal');
    }

    public function saveRole()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . ($this->roleId ?? 'NULL'),
            'parameter' => 'required|string|max:255|unique:roles,parameter,' . ($this->roleId ?? 'NULL'),
        ]);

        $roleService = app(\App\Services\RoleService::class);
        $roleService->saveRole(['name' => $this->name, 'parameter' => $this->parameter], $this->roleId);

        session()->flash('status', $this->isEditing ? 'Role berhasil diperbarui.' : 'Role berhasil dibuat.');
        $this->dispatch('close-modal', 'role-modal');
        $this->resetForm();
    }

    public function deleteRole($id)
    {
        $roleService = app(\App\Services\RoleService::class);
        $roleService->deleteRole($id);
        session()->flash('status', 'Role berhasil dihapus.');
    }
}; ?>

<div>
    <x-slot name="header">{{ __('Roles') }}</x-slot>

    <div class="py-12" x-data="deleteConfirmation">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-datatable.layout title="Kelola Peran (Roles)" :records="$this->roles">
                <x-slot name="filters">
                    <x-datatable.search placeholder="Cari Peran..." />

                    <button wire:click="createRole" class="bg-[#1e3a5f] text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-[#162d4a] transition-all shadow-sm active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Peran
                    </button>
                </x-slot>

                <x-slot name="thead">
                    <th class="py-3 px-4 text-left text-[11px] font-bold text-gray-400 uppercase tracking-widest w-16">NO</th>
                    <x-datatable.th field="name" :sortField="$sortField" :sortAsc="$sortAsc">
                        NAMA PERAN
                    </x-datatable.th>
                    <x-datatable.th field="parameter" :sortField="$sortField" :sortAsc="$sortAsc">
                        PARAMETER
                    </x-datatable.th>
                    <th class="py-3 px-4 text-right text-[11px] font-bold text-gray-400 uppercase tracking-widest pr-8">AKSI</th>
                </x-slot>

                <x-slot name="tbody">
                    @forelse ($this->roles as $index => $role)
                    <tr class="hover:bg-gray-50/50 transition-colors duration-150 group border-b border-gray-50 last:border-0" wire:key="role-{{ $role->id }}">
                        <td class="py-5 px-4 text-sm font-bold text-gray-400">
                            {{ $this->roles->firstItem() + $index }}
                        </td>
                        <td class="py-5 px-4 font-bold text-[#374151] text-sm tracking-tight">
                            {{ $role->name }}
                        </td>
                        <td class="py-5 px-4">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 uppercase tracking-tight">
                                {{ $role->parameter }}
                            </span>
                        </td>
                        <td class="py-5 px-4 text-right pr-6 overflow-visible">
                            <div class="relative inline-block text-left" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-gray-400 hover:text-gray-700 transition-colors bg-gray-50/50 rounded-lg group-hover:bg-gray-100">
                                    Aksi
                                    <svg class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                    class="absolute right-0 z-[100] mt-1 w-40 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 origin-top-right overflow-hidden shadow-slate-200/50" x-cloak>
                                    <button wire:click="editRole({{ $role->id }})" @click="open = false"
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-slate-700 hover:bg-slate-50 transition-colors gap-3 text-left">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit Peran
                                    </button>
                                    <button @click="open = false; confirmDelete({{ $role->id }}, 'deleteRole', 'Hapus peran ini secara permanen?')"
                                        class="flex items-center w-full px-4 py-2.5 text-[11px] font-bold text-rose-600 hover:bg-rose-50 transition-colors gap-3 border-t border-gray-50/50 text-left">
                                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus Peran
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-16 text-center">
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

    <x-modal name="role-modal" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="saveRole" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ $isEditing ? __('Edit Peran') : __('Tambah Peran') }}
            </h2>
            <div class="mt-6">
                <x-input-label for="name" value="{{ __('Name') }}" />

                <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 block w-full"
                    placeholder="{{ __('e.g. Administrator') }}" required autofocus />

                <x-input-error :messages="$errors->get('name')" class="mt-2" />

                <x-text-input wire:model="parameter" id="parameter" name="parameter" type="text"
                    class="mt-1 block w-full" placeholder="{{ __('e.g. administrator') }}" required autofocus />
                <x-input-error :messages="$errors->get('parameter')" class="mt-2" />
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
</div>