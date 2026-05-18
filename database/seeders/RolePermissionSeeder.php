<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Assign default permission sets to each role via the role_permission pivot.
     * Idempotent: uses sync() so re-running produces the same result.
     */
    public function run(): void
    {
        $allPermissions = Permission::all();

        // Super Admin: ALL permissions
        $superAdmin = Role::find(Role::ID_SUPER_ADMIN);
        if ($superAdmin) {
            $superAdmin->syncPermissions($allPermissions->pluck('id')->all());
        }

        // Admin: ALL permissions EXCEPT master.role
        $admin = Role::find(Role::ID_ADMIN);
        if ($admin) {
            $adminPermissions = $allPermissions
                ->where('key', '!=', 'master.role')
                ->pluck('id')
                ->all();
            $admin->syncPermissions($adminPermissions);
        }

        // Asesor: only akreditasi.view
        $asesor = Role::find(Role::ID_ASESOR);
        if ($asesor) {
            $asesorPermissions = $allPermissions
                ->where('key', 'akreditasi.view')
                ->pluck('id')
                ->all();
            $asesor->syncPermissions($asesorPermissions);
        }

        // Pesantren: only akreditasi.view
        $pesantren = Role::find(Role::ID_PESANTREN);
        if ($pesantren) {
            $pesantrenPermissions = $allPermissions
                ->where('key', 'akreditasi.view')
                ->pluck('id')
                ->all();
            $pesantren->syncPermissions($pesantrenPermissions);
        }
    }
}
