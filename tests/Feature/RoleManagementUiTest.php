<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_roles_do_not_render_edit_or_delete_actions(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $superAdmin = User::factory()->create(['role_id' => 4, 'email_verified_at' => now()]);
        $custom = Role::create(['name' => 'Custom Reviewer', 'parameter' => 'custom_reviewer']);

        $html = $this->actingAs($superAdmin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->getContent();

        foreach ([1, 2, 3, 4] as $id) {
            $this->assertStringNotContainsString(route('admin.roles.destroy', $id), $html);
            $this->assertStringNotContainsString("openEditModal({$id},", $html);
        }

        $this->assertStringContainsString(route('admin.roles.destroy', $custom->id), $html);
        $this->assertStringContainsString("openEditModal({$custom->id},", $html);
    }
}
