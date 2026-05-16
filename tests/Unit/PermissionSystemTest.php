<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
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
 */
class PermissionSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The seeders are intentionally idempotent so we can run them in
        // setUp without risking duplicate keys when RefreshDatabase reuses
        // a transaction.
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    // ─── Super admin shortcut ───────────────────────────────────────────────────

    public function test_super_admin_grants_every_permission_without_pivot(): void
    {
        $user = $this->makeUser(Role::ID_SUPER_ADMIN);

        // Super admin holds zero pivot rows by design.
        $this->assertSame(
            0,
            $user->role->permissions()->count(),
            'Super admin must not require pivot rows to be granted access.'
        );

        // Yet hasPermission() returns true for every key in the catalog.
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

    public function test_admin_holds_every_permission_except_rbac_management(): void
    {
        $user = $this->makeUser(Role::ID_ADMIN);

        $this->assertTrue($user->hasPermission('documents.manage'));
        $this->assertTrue($user->hasPermission('akreditasi.review'));
        $this->assertTrue($user->hasPermission('users.manage'));
        $this->assertTrue($user->hasPermission('master_data.view'));

        // Admin must NOT be allowed to edit the RBAC matrix itself.
        $this->assertFalse($user->hasPermission('roles.manage'));
        $this->assertFalse($user->hasPermission('permissions.manage'));
    }

    // ─── Default role mapping (asesor) ──────────────────────────────────────────

    public function test_asesor_holds_only_review_and_profile_permissions(): void
    {
        $user = $this->makeUser(Role::ID_ASESOR);

        $this->assertTrue($user->hasPermission('dashboard.view'));
        $this->assertTrue($user->hasPermission('akreditasi.review'));
        $this->assertTrue($user->hasPermission('banding.review'));
        $this->assertTrue($user->hasPermission('profile.edit'));

        // Asesor must not have admin-like capabilities.
        $this->assertFalse($user->hasPermission('users.manage'));
        $this->assertFalse($user->hasPermission('documents.manage'));
        $this->assertFalse($user->hasPermission('roles.manage'));
    }

    // ─── Default role mapping (pesantren) ───────────────────────────────────────

    public function test_pesantren_holds_only_self_service_permissions(): void
    {
        $user = $this->makeUser(Role::ID_PESANTREN);

        $this->assertTrue($user->hasPermission('dashboard.view'));
        $this->assertTrue($user->hasPermission('edpm.manage'));
        $this->assertTrue($user->hasPermission('akreditasi.assign'));
        $this->assertTrue($user->hasPermission('banding.submit'));
        $this->assertTrue($user->hasPermission('profile.edit'));

        // Pesantren must not have review or admin capabilities.
        $this->assertFalse($user->hasPermission('akreditasi.review'));
        $this->assertFalse($user->hasPermission('users.manage'));
        $this->assertFalse($user->hasPermission('roles.manage'));
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

        $this->assertFalse($asesor->hasPermission('documents.manage'));

        $perm = Permission::where('key', 'documents.manage')->firstOrFail();
        $asesor->role->grantPermission($perm->id);

        // User instance must re-read pivot after grant.
        $asesor->refresh()->load('role.permissions');

        $this->assertTrue($asesor->hasPermission('documents.manage'));
    }

    public function test_revoking_a_permission_strips_it_from_users(): void
    {
        $admin = $this->makeUser(Role::ID_ADMIN);

        $this->assertTrue($admin->hasPermission('documents.manage'));

        $perm = Permission::where('key', 'documents.manage')->firstOrFail();
        $admin->role->revokePermission($perm->id);
        $admin->refresh()->load('role.permissions');

        $this->assertFalse($admin->hasPermission('documents.manage'));
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function makeUser(int $roleId): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
        ])->fresh(['role.permissions']);
    }
}
