<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Role;
use App\Models\Permission;

new #[Layout('layouts.app')] class extends Component {
    /**
     * Matrix indexed by [role_id][permission_id] => bool granted.
     *
     * @var array<int, array<int, bool>>
     */
    public array $matrix = [];

    public function mount(): void
    {
        $user = auth()->user();
        // Hard gate: only super admin may edit the RBAC matrix. Even regular
        // admins (role 1) are explicitly excluded so the role tree itself
        // cannot be elevated by anyone with the admin permission set.
        if (!$user || !$user->isSuperAdmin()) {
            abort(403);
        }

        $this->loadMatrix();
    }

    /**
     * Hydrate the matrix from the current role-permission pivot state.
     * Super admin (id=4) is omitted because it bypasses the pivot via the
     * hardcoded shortcut in User::hasPermission().
     */
    public function loadMatrix(): void
    {
        $roles = $this->getRoles();
        $permissionIds = Permission::query()->pluck('id');

        $matrix = [];
        foreach ($roles as $role) {
            $granted = $role->permissions()->pluck('permissions.id')->all();
            foreach ($permissionIds as $pid) {
                $matrix[$role->id][$pid] = in_array($pid, $granted, true);
            }
        }
        $this->matrix = $matrix;
    }

    /**
     * Persist the matrix in a single transaction.
     */
    public function save(): void
    {
        $roles = $this->getRoles();

        \DB::transaction(function () use ($roles) {
            foreach ($roles as $role) {
                $granted = collect($this->matrix[$role->id] ?? [])
                    ->filter(fn ($v) => (bool) $v)
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $role->syncPermissions($granted);
            }
        });

        $this->loadMatrix();

        $this->dispatch('toast', type: 'success', title: 'Hak akses tersimpan',
            message: 'Matriks peran dan hak akses berhasil diperbarui.');
    }

    /**
     * Toggle a single cell in the matrix without saving.
     */
    public function toggle(int $roleId, int $permissionId): void
    {
        $current = (bool) ($this->matrix[$roleId][$permissionId] ?? false);
        $this->matrix[$roleId][$permissionId] = !$current;
    }

    /**
     * Roles that participate in the editable matrix (super admin excluded).
     *
     * @return \Illuminate\Support\Collection<int, Role>
     */
    public function getRolesProperty()
    {
        return $this->getRoles();
    }

    public function getPermissionsByGroupProperty()
    {
        return Permission::query()
            ->orderBy('group')
            ->orderBy('label')
            ->get()
            ->groupBy('group');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Role>
     */
    private function getRoles()
    {
        return Role::query()
            ->whereIn('id', [Role::ID_ADMIN, Role::ID_ASESOR, Role::ID_PESANTREN])
            ->orderBy('id')
            ->get();
    }
}; ?>

<div data-module-page="master-role-permission">
    <x-slot name="header">
        Peran &amp; Hak Akses
    </x-slot>

    <x-ui.page
        title="Matriks Peran &amp; Hak Akses"
        subtitle="Atur permission tiap role secara dinamis. Super Admin selalu memiliki akses penuh dan tidak dapat diedit dari sini."
    >
        <x-ui.section-card
            title="Matriks Permission"
            subtitle="Centang sel untuk memberi izin. Klik Simpan untuk menyimpan perubahan."
        >
            <div class="p-6">
                <div class="alert alert-light-warning d-flex align-items-center mb-6">
                    <x-ui.icon name="information" class="fs-2 me-3" />
                    <div>
                        <strong>Super Admin</strong> selalu memiliki akses penuh secara hardcoded dan tidak ditampilkan di tabel.
                        Perubahan di sini berlaku untuk role <strong>Admin</strong>, <strong>Asesor</strong>, dan <strong>Pesantren</strong>.
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-3">
                        <thead>
                            <tr class="text-uppercase fw-bold text-muted fs-7">
                                <th class="min-w-300px">Permission</th>
                                @foreach ($this->roles as $role)
                                    <th class="text-center min-w-120px">
                                        <span class="badge badge-light-primary fs-7">
                                            {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                        </span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->permissionsByGroup as $group => $perms)
                                <tr class="bg-light">
                                    <td colspan="{{ 1 + $this->roles->count() }}" class="fw-bold text-uppercase fs-7 text-gray-700">
                                        {{ ucfirst($group) }}
                                    </td>
                                </tr>
                                @foreach ($perms as $perm)
                                    <tr wire:key="perm-{{ $perm->id }}">
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="text-gray-900 fw-semibold">{{ $perm->label }}</span>
                                                <span class="text-muted fs-7"><code>{{ $perm->key }}</code></span>
                                                @if ($perm->description)
                                                    <span class="text-muted fs-7 mt-1">{{ $perm->description }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        @foreach ($this->roles as $role)
                                            <td class="text-center">
                                                <div class="form-check form-check-custom form-check-solid d-inline-flex">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input"
                                                        wire:model.live="matrix.{{ $role->id }}.{{ $perm->id }}"
                                                        wire:key="cell-{{ $role->id }}-{{ $perm->id }}"
                                                    />
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-6 gap-3">
                    <x-ui.button wire:click="loadMatrix" variant="light" size="sm">
                        <x-ui.icon name="arrows-circle" class="fs-4 me-1" />
                        Reset Tampilan
                    </x-ui.button>
                    <x-ui.button wire:click="save" variant="primary" size="sm">
                        <x-ui.icon name="check" class="fs-4 me-1" />
                        Simpan Perubahan
                    </x-ui.button>
                </div>
            </div>
        </x-ui.section-card>
    </x-ui.page>
</div>
