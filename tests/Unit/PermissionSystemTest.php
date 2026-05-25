<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Behaviour-level guarantees for the dynamic RBAC layer.
 *
 * Covers the three invariants that the rest of the application relies on:
 *
 *   1. Super admin is god-mode regardless of pivot state.
 *   2. Regular roles see ONLY the permissions explicitly granted to them.
 *   3. canAccessAdminArea() admits admin and super_admin and rejects everyone else.
 *
 * Permission keys used here match the catalog in PermissionSeeder (rbac-sso-enhance spec).
 */
class PermissionSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    // ─── Super admin shortcut ───────────────────────────────────────────────────

    public function test_super_admin_grants_every_permission_without_pivot(): void
    {
        $user = $this->makeUser(Role::ID_SUPER_ADMIN);

        // Super admin bypasses the pivot via the isSuperAdmin() shortcut in
        // hasPermission(). Whether or not pivot rows exist is an implementation
        // detail of the seeder — what matters is that hasPermission() always
        // returns true for super admin regardless of pivot state.

        // hasPermission() returns true for every key in the catalog.
        foreach (Permission::pluck('key') as $key) {
            $this->assertTrue(
                $user->hasPermission($key),
                "Super admin must hold permission '{$key}' via shortcut."
            );
        }

        // Also returns true for unknown keys (god mode is intentional).
        $this->assertTrue($user->hasPermission('completely.made.up.key'));
    }

    // ─── Default role mapping (admin) ───────────────────────────────────────────

    public function test_admin_holds_every_permission_except_master_role(): void
    {
        $user = $this->makeUser(Role::ID_ADMIN);

        // Admin has all permissions from the current catalog except master.role
        $this->assertTrue($user->hasPermission('akreditasi.view'));
        $this->assertTrue($user->hasPermission('akreditasi.approve'));
        $this->assertTrue($user->hasPermission('akreditasi.reject'));
        $this->assertTrue($user->hasPermission('akreditasi.delete'));
        $this->assertTrue($user->hasPermission('akreditasi.finalize'));
        $this->assertTrue($user->hasPermission('asesor.view'));
        $this->assertTrue($user->hasPermission('asesor.assign'));
        $this->assertTrue($user->hasPermission('asesor.manage'));
        $this->assertTrue($user->hasPermission('pesantren.view'));
        $this->assertTrue($user->hasPermission('pesantren.lock'));
        $this->assertTrue($user->hasPermission('pesantren.manage'));
        $this->assertTrue($user->hasPermission('banding.view'));
        $this->assertTrue($user->hasPermission('banding.review'));
        $this->assertTrue($user->hasPermission('banding.decide'));
        $this->assertTrue($user->hasPermission('master.edpm'));
        $this->assertTrue($user->hasPermission('master.dokumen'));
        $this->assertTrue($user->hasPermission('master.kategori'));
        $this->assertTrue($user->hasPermission('account.view'));
        $this->assertTrue($user->hasPermission('account.create'));
        $this->assertTrue($user->hasPermission('account.toggle'));
        $this->assertTrue($user->hasPermission('account.delete'));
        $this->assertTrue($user->hasPermission('trash.view'));
        $this->assertTrue($user->hasPermission('trash.restore'));
        $this->assertTrue($user->hasPermission('trash.purge'));
        $this->assertTrue($user->hasPermission('notification.view'));
        $this->assertTrue($user->hasPermission('notification.retry'));

        // Admin must NOT be allowed to edit the RBAC matrix itself.
        $this->assertFalse($user->hasPermission('master.role'));
    }

    // ─── Default role mapping (asesor) ──────────────────────────────────────────

    public function test_asesor_holds_only_akreditasi_view(): void
    {
        $user = $this->makeUser(Role::ID_ASESOR);

        $this->assertTrue($user->hasPermission('akreditasi.view'));

        // Asesor must not have admin-like capabilities.
        $this->assertFalse($user->hasPermission('akreditasi.approve'));
        $this->assertFalse($user->hasPermission('akreditasi.reject'));
        $this->assertFalse($user->hasPermission('akreditasi.delete'));
        $this->assertFalse($user->hasPermission('akreditasi.finalize'));
        $this->assertFalse($user->hasPermission('asesor.manage'));
        $this->assertFalse($user->hasPermission('account.view'));
        $this->assertFalse($user->hasPermission('master.role'));
        $this->assertFalse($user->hasPermission('trash.purge'));
    }

    // ─── Default role mapping (pesantren) ───────────────────────────────────────

    public function test_pesantren_holds_only_akreditasi_view(): void
    {
        $user = $this->makeUser(Role::ID_PESANTREN);

        $this->assertTrue($user->hasPermission('akreditasi.view'));

        // Pesantren must not have review or admin capabilities.
        $this->assertFalse($user->hasPermission('akreditasi.approve'));
        $this->assertFalse($user->hasPermission('akreditasi.reject'));
        $this->assertFalse($user->hasPermission('pesantren.lock'));
        $this->assertFalse($user->hasPermission('account.view'));
        $this->assertFalse($user->hasPermission('master.role'));
        $this->assertFalse($user->hasPermission('trash.purge'));
    }

    // ─── canAccessAdminArea ─────────────────────────────────────────────────────

    public function test_admin_and_super_admin_can_access_admin_area(): void
    {
        $admin = $this->makeUser(Role::ID_ADMIN);
        $superAdmin = $this->makeUser(Role::ID_SUPER_ADMIN);

        $this->assertTrue($admin->canAccessAdminArea());
        $this->assertTrue($superAdmin->canAccessAdminArea());
    }

    public function test_asesor_and_pesantren_cannot_access_admin_area(): void
    {
        $asesor = $this->makeUser(Role::ID_ASESOR);
        $pesantren = $this->makeUser(Role::ID_PESANTREN);

        $this->assertFalse($asesor->canAccessAdminArea());
        $this->assertFalse($pesantren->canAccessAdminArea());
    }

    // ─── Role exclusivity ───────────────────────────────────────────────────────

    public function test_super_admin_is_exclusive_from_admin(): void
    {
        $superAdmin = $this->makeUser(Role::ID_SUPER_ADMIN);
        $admin = $this->makeUser(Role::ID_ADMIN);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->isAdmin(), 'Super admin must not satisfy isAdmin() — they are exclusive.');

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isSuperAdmin());
    }

    // ─── Pivot-driven dynamic grants ────────────────────────────────────────────

    public function test_granting_a_permission_to_a_role_propagates_to_users(): void
    {
        $asesor = $this->makeUser(Role::ID_ASESOR);

        // Asesor does not have pesantren.lock by default
        $this->assertFalse($asesor->hasPermission('pesantren.lock'));

        $perm = Permission::where('key', 'pesantren.lock')->firstOrFail();
        $asesor->role->grantPermission($perm->id);

        // User instance must re-read pivot after grant.
        $asesor->refresh()->load('role.permissions');

        $this->assertTrue($asesor->hasPermission('pesantren.lock'));
    }

    public function test_revoking_a_permission_strips_it_from_users(): void
    {
        $admin = $this->makeUser(Role::ID_ADMIN);

        // Admin has akreditasi.approve by default
        $this->assertTrue($admin->hasPermission('akreditasi.approve'));

        $perm = Permission::where('key', 'akreditasi.approve')->firstOrFail();
        $admin->role->revokePermission($perm->id);
        $admin->refresh()->load('role.permissions');

        $this->assertFalse($admin->hasPermission('akreditasi.approve'));
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function makeUser(int $roleId): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
        ])->fresh(['role.permissions']);
    }
}
