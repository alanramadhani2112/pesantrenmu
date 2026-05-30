<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Permission;
use App\Models\PermissionAuditLog;
use App\Models\Role;
use Illuminate\Support\Facades\Log;

new #[Layout('layouts.app')] class extends Component {
    /**
     * Matrix indexed by [role_id][permission_id] => bool granted.
     *
     * @var array<int, array<int, bool>>
     */
    public array $matrix = [];

    public string $search = '';

    public string $groupFilter = '';

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
     * Persist the matrix in a single transaction and write an audit log entry
     * for every role whose permission set changed.
     */
    public function save(): void
    {
        $roles = $this->getRoles();

        // Build a lookup map: permission_id → key (for human-readable audit entries).
        $permissionKeys = Permission::query()->pluck('key', 'id');

        // Snapshot the current (before) state for each role so we can diff it.
        $before = [];
        foreach ($roles as $role) {
            $before[$role->id] = $role->permissions()->pluck('permissions.id')->all();
        }

        \DB::transaction(function () use ($roles, $before, $permissionKeys): void {
            $actor   = auth()->user();
            $request = request();

            foreach ($roles as $role) {
                $newGranted = collect($this->matrix[$role->id] ?? [])
                    ->filter(fn ($v) => (bool) $v)
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $oldGranted = array_map('intval', $before[$role->id]);

                $added   = array_values(array_diff($newGranted, $oldGranted));
                $removed = array_values(array_diff($oldGranted, $newGranted));

                $role->syncPermissions($newGranted);

                // Only write an audit entry when something actually changed.
                if (empty($added) && empty($removed)) {
                    continue;
                }

                $addedKeys   = array_values($permissionKeys->only($added)->all());
                $removedKeys = array_values($permissionKeys->only($removed)->all());

                // --- Primary audit: structured DB record ---
                try {
                    PermissionAuditLog::create([
                        'user_id'             => $actor?->id,
                        'role_id'             => $role->id,
                        'permissions_added'   => $addedKeys ?: null,
                        'permissions_removed' => $removedKeys ?: null,
                        'ip_address'          => $request?->ip(),
                        'user_agent'          => $request?->userAgent(),
                        'created_at'          => now(),
                    ]);
                } catch (\Throwable $e) {
                    // Fallback: if the DB write fails for any reason, at least
                    // leave a trace in the application log.
                    Log::warning('permission_audit_db_failed', [
                        'error'              => $e->getMessage(),
                        'actor_id'           => $actor?->id,
                        'role_id'            => $role->id,
                        'permissions_added'  => $addedKeys,
                        'permissions_removed'=> $removedKeys,
                    ]);
                }

                // --- Secondary audit: always write to the application log ---
                Log::info('permission_matrix_changed', [
                    'actor_id'            => $actor?->id,
                    'actor_name'          => $actor?->name,
                    'role_id'             => $role->id,
                    'role_name'           => $role->name,
                    'permissions_added'   => $addedKeys,
                    'permissions_removed' => $removedKeys,
                    'ip_address'          => $request?->ip(),
                ]);
            }
        });

        $this->loadMatrix();

        $this->dispatch('notification-received', type: 'success', title: 'Hak akses tersimpan',
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

    public function grantVisibleForRole(int $roleId): void
    {
        if (!$this->isEditableRole($roleId)) {
            return;
        }

        foreach ($this->visiblePermissionIds() as $permissionId) {
            $this->matrix[$roleId][$permissionId] = true;
        }
    }

    public function revokeVisibleForRole(int $roleId): void
    {
        if (!$this->isEditableRole($roleId)) {
            return;
        }

        foreach ($this->visiblePermissionIds() as $permissionId) {
            $this->matrix[$roleId][$permissionId] = false;
        }
    }

    public function grantVisibleForAllRoles(): void
    {
        foreach ($this->getRoles() as $role) {
            $this->grantVisibleForRole((int) $role->id);
        }
    }

    public function revokeVisibleForAllRoles(): void
    {
        foreach ($this->getRoles() as $role) {
            $this->revokeVisibleForRole((int) $role->id);
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->groupFilter = '';
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

    public function getGroupOptionsProperty(): array
    {
        return Permission::query()
            ->select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->mapWithKeys(fn (string $group) => [$group => ucfirst($group)])
            ->all();
    }

    public function getFilteredPermissionsProperty()
    {
        $search = trim($this->search);

        return Permission::query()
            ->when($this->groupFilter !== '', fn ($query) => $query->where('group', $this->groupFilter))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('key', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('group')
            ->orderBy('label')
            ->get();
    }

    public function getPermissionsByGroupProperty()
    {
        return $this->getFilteredPermissionsProperty()
            ->groupBy('group');
    }

    public function getVisiblePermissionIdsProperty(): array
    {
        return $this->visiblePermissionIds();
    }

    public function getChangeSummaryProperty(): array
    {
        $permissionLabels = Permission::query()->pluck('label', 'id');

        return $this->getRoles()
            ->map(function (Role $role) use ($permissionLabels): ?array {
                $oldGranted = $role->permissions()
                    ->pluck('permissions.id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $newGranted = collect($this->matrix[$role->id] ?? [])
                    ->filter(fn ($value) => (bool) $value)
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $added = array_values(array_diff($newGranted, $oldGranted));
                $removed = array_values(array_diff($oldGranted, $newGranted));

                if (empty($added) && empty($removed)) {
                    return null;
                }

                return [
                    'role' => $role,
                    'added' => $this->permissionLabels($added, $permissionLabels),
                    'removed' => $this->permissionLabels($removed, $permissionLabels),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function getAuditLogsProperty()
    {
        return PermissionAuditLog::query()
            ->with(['user', 'role'])
            ->latest('created_at')
            ->latest('id')
            ->limit(8)
            ->get();
    }

    public function roleLabel(?Role $role): string
    {
        if (!$role) {
            return 'Role tidak tersedia';
        }

        return ucwords(str_replace('_', ' ', $role->name));
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

    private function isEditableRole(int $roleId): bool
    {
        return in_array($roleId, [Role::ID_ADMIN, Role::ID_ASESOR, Role::ID_PESANTREN], true);
    }

    /**
     * @return array<int, int>
     */
    private function visiblePermissionIds(): array
    {
        return $this->getFilteredPermissionsProperty()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $permissionIds
     */
    private function permissionLabels(array $permissionIds, $permissionLabels): array
    {
        return collect($permissionIds)
            ->map(fn (int $id) => $permissionLabels[$id] ?? "Permission #{$id}")
            ->values()
            ->all();
    }
}; ?>

<div x-data="deleteConfirmation()" data-module-page="master-role-permission">
    <x-slot name="header">
        Peran & Hak Akses
    </x-slot>

    <x-ui.page
        title="Matriks Peran & Hak Akses"
        subtitle="Atur permission tiap role secara dinamis. Super Admin selalu memiliki akses penuh dan tidak dapat diedit dari sini."
    >
        @php
            $roles = $this->roles;
            $visiblePermissionIds = $this->visiblePermissionIds;
            $visiblePermissionCount = count($visiblePermissionIds);
        @endphp

        <x-slot name="toolbar">
            <x-ui.button wire:click="loadMatrix" variant="light" size="sm" class="text-nowrap">
                <x-ui.icon name="arrow-up-down" class="fs-4 me-1" />
                Reset Tampilan
            </x-ui.button>

            <x-ui.button
                x-on:click="confirmAction('save', 'Simpan perubahan hak akses?', 'Perubahan matriks peran dan permission akan langsung berlaku.', 'Ya, simpan')"
                variant="primary"
                size="sm"
                class="text-nowrap"
            >
                <x-ui.icon name="check" class="fs-4 me-1" />
                Simpan Perubahan
            </x-ui.button>
        </x-slot>

        <x-ui.section-card
            title="Kontrol Hak Akses"
            subtitle="Filter permission dan jalankan aksi massal sebelum menyimpan perubahan."
            class="spm-permission-control-card"
        >
            <div class="p-6 spm-permission-control-panel">
                <x-ui.alert
                    variant="info"
                    title="Akses Super Admin"
                    icon="information-2"
                    class="spm-permission-note mb-6"
                >
                    Super Admin selalu memiliki akses penuh secara hardcoded dan tidak ditampilkan di tabel.
                    Matrix ini hanya berlaku untuk role Admin, Asesor, dan Pesantren.
                </x-ui.alert>

                <div class="row g-4 align-items-end">
                    <div class="col-12 col-lg-5">
                        <label for="search" class="spm-permission-field-label">Cari Permission</label>
                        <x-ui.input
                            model="search"
                            modifier="live.debounce.300ms"
                            placeholder="Cari label, key, atau deskripsi"
                            class="spm-permission-search"
                        />
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label for="groupFilter" class="spm-permission-field-label">Grup</label>
                        <x-ui.filter-select
                            id="groupFilter"
                            model="groupFilter"
                            placeholder="Semua Grup"
                            :options="$this->groupOptions"
                            class="w-100"
                        />
                    </div>

                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="spm-permission-metric">
                            <div>
                                <span class="spm-permission-metric-label">Permission Terlihat</span>
                                <strong class="spm-permission-metric-value">{{ $visiblePermissionCount }}</strong>
                            </div>

                            @if ($search || $groupFilter)
                                <x-ui.button wire:click="resetFilters" variant="light" size="sm">
                                    Reset Filter
                                </x-ui.button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="separator my-6"></div>

                <div class="spm-permission-quick-actions">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2 mb-4">
                        <div>
                            <div class="spm-permission-block-title">Aksi Cepat</div>
                            <div class="spm-permission-block-subtitle">Berlaku hanya untuk permission yang sedang terlihat dari filter.</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="spm-permission-action-card spm-permission-action-card--global">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                    <span class="spm-permission-action-title">Semua Role</span>
                                    <span class="spm-permission-action-meta">{{ $visiblePermissionCount }} permission</span>
                                </div>

                                <div class="d-flex gap-2">
                                    <x-ui.button wire:click="grantVisibleForAllRoles" variant="light-success" size="sm" class="flex-fill">
                                        Beri
                                    </x-ui.button>
                                    <x-ui.button wire:click="revokeVisibleForAllRoles" variant="light-danger" size="sm" class="flex-fill">
                                        Cabut
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>

                        @foreach ($roles as $role)
                            @php
                                $grantedVisibleCount = collect($visiblePermissionIds)
                                    ->filter(fn (int $permissionId) => (bool) ($matrix[$role->id][$permissionId] ?? false))
                                    ->count();
                            @endphp

                            <div class="col-12 col-md-6 col-xl-3" wire:key="role-action-{{ $role->id }}">
                                <div class="spm-permission-action-card spm-permission-role-actions">
                                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                        <span class="spm-permission-action-title">{{ $this->roleLabel($role) }}</span>
                                        <span class="spm-permission-action-meta">{{ $grantedVisibleCount }}/{{ $visiblePermissionCount }} aktif</span>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <x-ui.button wire:click="grantVisibleForRole({{ $role->id }})" variant="light-success" size="sm" class="flex-fill">
                                            Beri
                                        </x-ui.button>
                                        <x-ui.button wire:click="revokeVisibleForRole({{ $role->id }})" variant="light-danger" size="sm" class="flex-fill">
                                            Cabut
                                        </x-ui.button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-ui.section-card>

        <x-ui.section-card
            title="Matriks Permission"
            subtitle="Centang permission sesuai role. Header tabel dibuat fokus hanya untuk membaca role."
            class="spm-permission-matrix-card"
        >
            <div class="p-0">
                <x-ui.simple-table dense class="spm-permission-matrix-wrap" table-class="spm-permission-matrix">
                    <thead>
                        <tr class="text-uppercase fw-semibold text-muted fs-7">
                            <th class="ps-4 spm-permission-name-col">Permission</th>
                            @foreach ($roles as $role)
                                @php
                                    $grantedVisibleCount = collect($visiblePermissionIds)
                                        ->filter(fn (int $permissionId) => (bool) ($matrix[$role->id][$permissionId] ?? false))
                                        ->count();
                                @endphp

                                <th class="text-center spm-permission-role-col" wire:key="role-head-{{ $role->id }}">
                                    <div class="spm-permission-role-head">
                                        <span class="spm-permission-role-name">{{ $this->roleLabel($role) }}</span>
                                        <span class="spm-permission-role-count">{{ $grantedVisibleCount }}/{{ $visiblePermissionCount }} aktif</span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->permissionsByGroup as $group => $perms)
                            <tr class="spm-permission-group-row" wire:key="group-{{ $group }}">
                                <td colspan="{{ 1 + $roles->count() }}" class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="bullet bullet-dot bg-primary"></span>
                                        <span>{{ ucfirst($group) }}</span>
                                        <x-ui.badge variant="secondary">{{ $perms->count() }}</x-ui.badge>
                                    </div>
                                </td>
                            </tr>

                            @foreach ($perms as $perm)
                                <tr wire:key="perm-{{ $perm->id }}">
                                    <td class="ps-4 spm-permission-name-cell">
                                        <div class="spm-permission-item">
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <span class="spm-permission-label">{{ $perm->label }}</span>
                                                <span class="spm-permission-key">{{ $perm->key }}</span>
                                            </div>

                                            @if ($perm->description)
                                                <div class="spm-permission-desc">{{ $perm->description }}</div>
                                            @endif
                                        </div>
                                    </td>

                                    @foreach ($roles as $role)
                                        <td class="text-center spm-permission-check-cell">
                                            <x-ui.checkbox
                                                model="matrix.{{ $role->id }}.{{ $perm->id }}"
                                                modifier="live"
                                                class="justify-content-center m-0 spm-permission-checkbox"
                                                aria-label="Toggle {{ $this->roleLabel($role) }} untuk {{ $perm->label }}"
                                                wire:key="cell-{{ $role->id }}-{{ $perm->id }}"
                                            />
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="{{ 1 + $roles->count() }}">
                                    <x-ui.empty-state
                                        title="Permission tidak ditemukan"
                                        description="Ubah kata kunci atau filter grup untuk melihat permission lain."
                                        variant="info"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-ui.simple-table>
            </div>
        </x-ui.section-card>

        @if (count($this->changeSummary) > 0)
            <x-ui.section-card
                title="Perubahan Belum Tersimpan"
                subtitle="Review perubahan sebelum tombol Simpan Perubahan ditekan."
                class="spm-permission-summary-card"
            >
                <div class="p-6">
                    <div class="spm-permission-summary-panel">
                        <div class="d-flex flex-column gap-3">
                            @foreach ($this->changeSummary as $summary)
                                <div class="d-flex flex-column gap-2">
                                    <span class="fw-semibold text-gray-800">{{ $this->roleLabel($summary['role']) }}</span>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($summary['added'] as $label)
                                            <x-ui.badge variant="success">+ {{ $label }}</x-ui.badge>
                                        @endforeach
                                        @foreach ($summary['removed'] as $label)
                                            <x-ui.badge variant="danger">- {{ $label }}</x-ui.badge>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-ui.section-card>
        @endif

        <x-ui.section-card
            title="Riwayat Perubahan"
            subtitle="Audit terakhir dari perubahan role dan permission."
            class="spm-permission-audit-card"
        >
            <x-ui.simple-table dense table-class="spm-permission-audit-table">
                <thead>
                    <tr class="text-uppercase fw-semibold text-muted fs-7">
                        <th class="ps-4 min-w-150px">Waktu</th>
                        <th class="min-w-180px">Aktor</th>
                        <th class="min-w-140px">Role</th>
                        <th class="min-w-300px">Perubahan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->auditLogs as $log)
                        <tr wire:key="audit-{{ $log->id }}">
                            <td class="ps-4 text-muted fw-semibold">
                                {{ $log->created_at?->format('d M Y H:i') }}
                            </td>
                            <td class="text-gray-800 fw-semibold">
                                {{ $log->user?->name ?? 'System' }}
                            </td>
                            <td>
                                <x-ui.badge variant="primary">{{ $this->roleLabel($log->role) }}</x-ui.badge>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-2">
                                    @if (!empty($log->permissions_added))
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($log->permissions_added as $key)
                                                <x-ui.badge variant="success">+ {{ $key }}</x-ui.badge>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if (!empty($log->permissions_removed))
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach ($log->permissions_removed as $key)
                                                <x-ui.badge variant="danger">- {{ $key }}</x-ui.badge>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-ui.empty-state
                                    title="Belum ada riwayat perubahan"
                                    description="Setiap perubahan hak akses akan tercatat di sini."
                                    variant="info"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.simple-table>
        </x-ui.section-card>
    </x-ui.page>
</div>
