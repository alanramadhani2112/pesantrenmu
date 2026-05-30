<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Coverage: RoleService — getPaginatedRoles, getAllRoles, findRole,
 * saveRole (create + update), deleteRole.
 */
class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RoleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class); // seeds roles id 1-4
        $this->service = app(RoleService::class);
    }

    // ─── getAllRoles ──────────────────────────────────────────────────────────

    public function test_get_all_roles_returns_all_seeded_roles(): void
    {
        $roles = $this->service->getAllRoles();

        $this->assertCount(4, $roles);
        $this->assertTrue($roles->pluck('name')->contains('admin'));
        $this->assertTrue($roles->pluck('name')->contains('asesor'));
        $this->assertTrue($roles->pluck('name')->contains('pesantren'));
        $this->assertTrue($roles->pluck('name')->contains('super_admin'));
    }

    public function test_get_all_roles_returns_collection(): void
    {
        $roles = $this->service->getAllRoles();

        $this->assertInstanceOf(Collection::class, $roles);
    }

    // ─── getPaginatedRoles ────────────────────────────────────────────────────

    public function test_get_paginated_roles_returns_paginator(): void
    {
        $result = $this->service->getPaginatedRoles(null, 10, 'name', true);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_get_paginated_roles_returns_all_when_no_search(): void
    {
        $result = $this->service->getPaginatedRoles(null, 10, 'name', true);

        $this->assertEquals(4, $result->total());
    }

    public function test_get_paginated_roles_filters_by_search(): void
    {
        $result = $this->service->getPaginatedRoles('admin', 10, 'name', true);

        // Should match 'admin' and 'super_admin'
        $this->assertGreaterThanOrEqual(1, $result->total());
        foreach ($result->items() as $role) {
            $this->assertStringContainsString('admin', $role->name);
        }
    }

    public function test_get_paginated_roles_respects_per_page(): void
    {
        $result = $this->service->getPaginatedRoles(null, 2, 'name', true);

        $this->assertCount(2, $result->items());
        $this->assertEquals(4, $result->total());
    }

    public function test_get_paginated_roles_sorts_ascending(): void
    {
        $result = $this->service->getPaginatedRoles(null, 10, 'name', true);

        $names = collect($result->items())->pluck('name')->all();
        $sorted = $names;
        sort($sorted);
        $this->assertEquals($sorted, $names);
    }

    public function test_get_paginated_roles_sorts_descending(): void
    {
        $result = $this->service->getPaginatedRoles(null, 10, 'name', false);

        $names = collect($result->items())->pluck('name')->all();
        $sorted = $names;
        rsort($sorted);
        $this->assertEquals($sorted, $names);
    }

    // ─── findRole ─────────────────────────────────────────────────────────────

    public function test_find_role_returns_correct_role(): void
    {
        $role = $this->service->findRole(Role::ID_ADMIN);

        $this->assertNotNull($role);
        $this->assertEquals('admin', $role->name);
        $this->assertEquals(Role::ID_ADMIN, $role->id);
    }

    public function test_find_role_returns_null_for_nonexistent_id(): void
    {
        $role = $this->service->findRole(99999);

        $this->assertNull($role);
    }

    public function test_find_role_returns_all_four_canonical_roles(): void
    {
        foreach ([Role::ID_ADMIN, Role::ID_ASESOR, Role::ID_PESANTREN, Role::ID_SUPER_ADMIN] as $id) {
            $this->assertNotNull($this->service->findRole($id));
        }
    }

    // ─── saveRole (create) ────────────────────────────────────────────────────

    public function test_save_role_creates_new_role_when_no_id_given(): void
    {
        $role = $this->service->saveRole(['name' => 'custom_role']);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertDatabaseHas('roles', ['name' => 'custom_role']);
    }

    public function test_save_role_returns_role_model_on_create(): void
    {
        $result = $this->service->saveRole(['name' => 'new_role']);

        $this->assertInstanceOf(Role::class, $result);
        $this->assertNotNull($result->id);
    }

    public function test_save_role_create_assigns_correct_name(): void
    {
        $role = $this->service->saveRole(['name' => 'custom_role_2']);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('custom_role_2', $role->name);
    }

    // ─── saveRole (update) ────────────────────────────────────────────────────

    public function test_save_role_updates_existing_role_when_id_given(): void
    {
        $role = Role::create(['name' => 'old_name']);

        $result = $this->service->saveRole(['name' => 'new_name'], $role->id);

        $this->assertTrue((bool) $result);
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'new_name']);
        $this->assertDatabaseMissing('roles', ['id' => $role->id, 'name' => 'old_name']);
    }

    public function test_save_role_update_returns_false_for_nonexistent_id(): void
    {
        $result = $this->service->saveRole(['name' => 'ghost'], 99999);

        $this->assertFalse($result);
    }

    public function test_save_role_update_does_not_create_new_record(): void
    {
        $countBefore = Role::count();

        $this->service->saveRole(['name' => 'updated'], Role::ID_ADMIN);

        $this->assertEquals($countBefore, Role::count());
    }

    // ─── deleteRole ───────────────────────────────────────────────────────────

    public function test_delete_role_removes_role_from_db(): void
    {
        $role = Role::create(['name' => 'to_delete']);

        $result = $this->service->deleteRole($role->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_delete_role_returns_false_for_nonexistent_id(): void
    {
        $result = $this->service->deleteRole(99999);

        $this->assertFalse($result);
    }

    public function test_delete_role_does_not_affect_other_roles(): void
    {
        $toDelete = Role::create(['name' => 'temp_role']);
        $countBefore = Role::count();

        $this->service->deleteRole($toDelete->id);

        // All original 4 seeded roles should still exist
        $this->assertEquals($countBefore - 1, Role::count());
        $this->assertNotNull($this->service->findRole(Role::ID_ADMIN));
        $this->assertNotNull($this->service->findRole(Role::ID_ASESOR));
    }

    // ─── Integration: users are not deleted when role is deleted ─────────────

    public function test_users_with_role_still_exist_after_role_deleted(): void
    {
        // Create a custom role (not one of the 4 canonical ones used by users)
        $role = Role::create(['name' => 'temp_deletable']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->service->deleteRole($role->id);

        // User still exists (no cascade delete on role)
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
