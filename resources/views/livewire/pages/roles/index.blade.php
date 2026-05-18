<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Gate;

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
        if (!auth()->user()->canAccessAdminArea()) {
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
        Gate::authorize('master.role');

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
        Gate::authorize('master.role');

        $roleService = app(\App\Services\RoleService::class);
        $roleService->deleteRole($id);
        session()->flash('status', 'Role berhasil dihapus.');
    }
}; ?>

<div x-data="deleteConfirmation" data-module-page="roles">
    <x-ui.index-layout
        title="Roles"
        subtitle="Kelola peran dan parameter akses pengguna sistem."
    >
        <x-datatable.layout title="Kelola Peran (Roles)" :records="$this->roles">
            <x-slot name="filters">
                <x-datatable.search placeholder="Cari Peran..." />
            </x-slot>

            <x-slot name="toolbar">
                <x-ui.button wire:click="createRole" variant="primary" size="sm">
                    <x-ui.icon name="plus" class="fs-4 me-1" />
                    Tambah Peran
                </x-ui.button>
            </x-slot>

            <x-slot name="thead">
                <x-ui.table-th :min-width="false" class="w-70px">No</x-ui.table-th>
                <x-datatable.th field="name" :sortField="$sortField" :sortAsc="$sortAsc">
                    Nama Peran
                </x-datatable.th>
                <x-datatable.th field="parameter" :sortField="$sortField" :sortAsc="$sortAsc">
                    Parameter
                </x-datatable.th>
                <x-ui.table-th align="end">Aksi</x-ui.table-th>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($this->roles as $index => $role)
                <tr wire:key="role-{{ $role->id }}">
                    <td class="text-muted fw-semibold">
                        {{ $this->roles->firstItem() + $index }}
                    </td>
                    <td class="fw-semibold text-gray-900 fs-6">
                        {{ $role->name }}
                    </td>
                    <td>
                        <x-ui.status-badge variant="secondary">{{ $role->parameter }}</x-ui.status-badge>
                    </td>
                    <td class="text-end overflow-visible">
                        <x-ui.action-menu>
                            <x-ui.action-menu-item wire:click="editRole({{ $role->id }})">
                                <x-ui.icon name="pencil" class="fs-4" />
                                    Edit Peran
                            </x-ui.action-menu-item>

                            <x-ui.action-menu-item
                                variant="danger"
                                x-on:click="confirmDelete({{ $role->id }}, 'deleteRole', 'Hapus peran ini secara permanen?')"
                            >
                                <x-ui.icon name="trash" class="fs-4" />
                                    Hapus Peran
                            </x-ui.action-menu-item>
                        </x-ui.action-menu>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">
                        <x-ui.empty-state title="Data tidak ditemukan" class="py-15" />
                    </td>
                </tr>
                @endforelse
            </x-slot>
        </x-datatable.layout>
    </x-ui.index-layout>

    <x-ui.modal name="role-modal" :show="$errors->isNotEmpty()" focusable>
        <form x-on:submit.prevent="confirmAction('saveRole', 'Simpan peran?', 'Data peran akan disimpan ke sistem.')">
            <x-ui.modal-header
                :title="$isEditing ? __('Edit Peran') : __('Tambah Peran')"
                subtitle="Kelola nama dan parameter role sistem."
                icon="security-user"
            />

            <x-ui.modal-body>
                <x-ui.form-field label="{{ __('Name') }}" for="name" :error="$errors->get('name')">
                    <x-ui.input
                        model="name"
                        id="name"
                        name="name"
                        placeholder="{{ __('e.g. Administrator') }}"
                        required
                        autofocus
                    />
                </x-ui.form-field>

                <x-ui.form-field label="Parameter" for="parameter" :error="$errors->get('parameter')">
                    <x-ui.input
                        model="parameter"
                        id="parameter"
                        name="parameter"
                        placeholder="{{ __('e.g. administrator') }}"
                        required
                    />
                </x-ui.form-field>
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
    </x-ui.modal>
</div>
