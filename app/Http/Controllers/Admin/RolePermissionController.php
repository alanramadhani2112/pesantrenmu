<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionAuditLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $search = $request->input('search', '');
        $groupFilter = $request->input('group', '');

        // Exclude super_admin (id=4) from matrix
        $roles = Role::where('id', '!=', 4)->orderBy('id')->get();

        $permissionsQuery = Permission::query()
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('key', 'like', "%{$search}%")
                   ->orWhere('label', 'like', "%{$search}%");
            }))
            ->when($groupFilter, fn ($q) => $q->where('group', $groupFilter))
            ->orderBy('group')
            ->orderBy('key');

        $permissions = $permissionsQuery->get();
        $groups = Permission::distinct()->pluck('group')->filter()->sort()->values();

        // Build matrix
        $matrix = [];
        foreach ($roles as $role) {
            $granted = $role->permissions()->pluck('permissions.id')->all();
            foreach ($permissions as $permission) {
                $matrix[$role->id][$permission->id] = in_array($permission->id, $granted, true);
            }
        }

        // Group permissions for display
        $groupedPermissions = $permissions->groupBy('group');

        return view('admin.role-permission.index', compact(
            'roles', 'permissions', 'groupedPermissions', 'groups', 'matrix',
            'search', 'groupFilter'
        ));
    }

    public function save(Request $request)
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $matrixInput = $request->input('matrix', []);
        $roles = Role::where('id', '!=', 4)->get();
        $permissionKeys = Permission::pluck('key', 'id');

        $visiblePermissionIds = collect($request->input('visible_permission_ids', []))
            ->map(fn ($id) => (int) $id)
            ->all();
        $hasVisibleScope = $request->boolean('visible_permission_scope') || $request->has('visible_permission_ids');

        DB::transaction(function () use ($roles, $matrixInput, $permissionKeys, $visiblePermissionIds, $hasVisibleScope) {
            $actor = auth()->user();

            foreach ($roles as $role) {
                $before = $role->permissions()->pluck('permissions.id')->all();

                $checkedVisible = collect($matrixInput[$role->id] ?? [])
                    ->keys()
                    ->map(fn ($v) => (int) $v)
                    ->all();

                $newGranted = $hasVisibleScope
                    ? array_values(array_unique(array_merge(
                        array_diff($before, $visiblePermissionIds),
                        $checkedVisible
                    )))
                    : $checkedVisible;

                sort($before);
                sort($newGranted);

                if ($before !== $newGranted) {
                    $role->permissions()->sync($newGranted);

                    $added = array_diff($newGranted, $before);
                    $removed = array_diff($before, $newGranted);

                    PermissionAuditLog::create([
                        'user_id' => $actor->id,
                        'role_id' => $role->id,
                        'permissions_added' => collect($added)->map(fn ($id) => $permissionKeys[$id] ?? $id)->values()->all(),
                        'permissions_removed' => collect($removed)->map(fn ($id) => $permissionKeys[$id] ?? $id)->values()->all(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Permission matrix berhasil diperbarui.');
    }
}
