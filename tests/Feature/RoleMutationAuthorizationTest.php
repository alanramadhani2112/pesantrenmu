<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMutationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_cannot_create_role_even_with_master_role_permission(): void
    {
        $admin = User::factory()->create(['role_id' => Role::ID_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'operator',
            'parameter' => 'operator',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('roles', ['name' => 'operator']);
    }

    public function test_super_admin_can_create_update_and_delete_custom_role(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);

        $this->actingAs($superAdmin)->post(route('admin.roles.store'), [
            'name' => 'operator',
            'parameter' => 'operator',
        ])->assertRedirect();

        $role = Role::where('parameter', 'operator')->firstOrFail();

        $this->actingAs($superAdmin)->put(route('admin.roles.update', $role->id), [
            'name' => 'operator lp2m',
            'parameter' => 'operator-lp2m',
        ])->assertRedirect();

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'operator lp2m', 'parameter' => 'operator-lp2m']);

        $this->actingAs($superAdmin)->delete(route('admin.roles.destroy', $role->id))->assertRedirect();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_super_admin_cannot_update_canonical_role(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);

        $response = $this->actingAs($superAdmin)->put(route('admin.roles.update', Role::ID_ADMIN), [
            'name' => 'admin-baru',
            'parameter' => 'admin-baru',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => Role::ID_ADMIN, 'name' => 'admin', 'parameter' => 'admin']);
    }

    public function test_super_admin_cannot_delete_canonical_role(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);

        $response = $this->actingAs($superAdmin)->delete(route('admin.roles.destroy', Role::ID_ASESOR));

        $response->assertForbidden();
        $this->assertDatabaseHas('roles', ['id' => Role::ID_ASESOR, 'name' => 'asesor', 'parameter' => 'asesor']);
    }
}
