<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtered_save_preserves_hidden_granted_permissions(): void
    {
        $this->seedBasePermissions();
        $superAdmin = User::factory()->create(['role_id' => 4, 'email_verified_at' => now()]);
        $adminRole = Role::findOrFail(1);
        $visible = Permission::where('key', 'akreditasi.view')->firstOrFail();
        $hidden = Permission::where('key', 'master.dokumen')->firstOrFail();
        $adminRole->permissions()->sync([$visible->id, $hidden->id]);

        $this->actingAs($superAdmin)->post(route('admin.role-permission.save'), [
            'visible_permission_ids' => [$visible->id],
            'matrix' => [
                $adminRole->id => [$visible->id => 'on'],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue($adminRole->fresh()->permissions()->whereKey($visible->id)->exists());
        $this->assertTrue($adminRole->fresh()->permissions()->whereKey($hidden->id)->exists());
    }

    public function test_filtered_save_revokes_visible_unchecked_permission_only(): void
    {
        $this->seedBasePermissions();
        $superAdmin = User::factory()->create(['role_id' => 4, 'email_verified_at' => now()]);
        $adminRole = Role::findOrFail(1);
        $visible = Permission::where('key', 'akreditasi.view')->firstOrFail();
        $hidden = Permission::where('key', 'master.dokumen')->firstOrFail();
        $adminRole->permissions()->sync([$visible->id, $hidden->id]);

        $this->actingAs($superAdmin)->post(route('admin.role-permission.save'), [
            'visible_permission_scope' => '1',
            'visible_permission_ids' => [$visible->id],
            'matrix' => [
                $adminRole->id => [],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertFalse($adminRole->fresh()->permissions()->whereKey($visible->id)->exists());
        $this->assertTrue($adminRole->fresh()->permissions()->whereKey($hidden->id)->exists());
    }

    public function test_empty_filtered_save_preserves_all_permissions(): void
    {
        $this->seedBasePermissions();
        $superAdmin = User::factory()->create(['role_id' => 4, 'email_verified_at' => now()]);
        $adminRole = Role::findOrFail(1);
        $visible = Permission::where('key', 'akreditasi.view')->firstOrFail();
        $hidden = Permission::where('key', 'master.dokumen')->firstOrFail();
        $adminRole->permissions()->sync([$visible->id, $hidden->id]);

        $this->actingAs($superAdmin)->post(route('admin.role-permission.save'), [
            'visible_permission_scope' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue($adminRole->fresh()->permissions()->whereKey($visible->id)->exists());
        $this->assertTrue($adminRole->fresh()->permissions()->whereKey($hidden->id)->exists());
    }

    private function seedBasePermissions(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }
}
