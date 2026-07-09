<?php

namespace Tests\Feature\Trash;

use App\Models\Akreditasi;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 7.1: Authorization tests for the Trash view.
 *
 * Validates: Requirement 7.1–7.4
 */
class TrashAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['role_id' => 1]);
    }

    private function makePesantren(): User
    {
        return User::factory()->create(['role_id' => 3]);
    }

    private function makeAsesor(): User
    {
        return User::factory()->create(['role_id' => 2]);
    }

    public function test_admin_can_access_trash_view(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $response = $this->get(route('admin.trash'));
        $response->assertStatus(200);
    }

    public function test_pesantren_user_gets_403_on_trash_view(): void
    {
        $user = $this->makePesantren();
        $this->actingAs($user);

        $response = $this->get(route('admin.trash'));
        $response->assertStatus(403);
    }

    public function test_asesor_gets_403_on_trash_view(): void
    {
        $user = $this->makeAsesor();
        $this->actingAs($user);

        $response = $this->get(route('admin.trash'));
        $response->assertStatus(403);
    }

    public function test_admin_without_restore_permission_cannot_restore_trash(): void
    {
        $admin = $this->makeAdmin();
        $permission = Permission::where('key', 'trash.restore')->firstOrFail();
        Role::findOrFail(1)->revokePermission($permission->id);

        $akreditasi = $this->makeTrashedAkreditasi();

        $response = $this->actingAs($admin)->post(route('admin.trash.restore'), ['id' => $akreditasi->id]);

        $response->assertForbidden();
    }

    public function test_admin_without_purge_permission_cannot_force_delete_trash(): void
    {
        $admin = $this->makeAdmin();
        $permission = Permission::where('key', 'trash.purge')->firstOrFail();
        Role::findOrFail(1)->revokePermission($permission->id);

        $akreditasi = $this->makeTrashedAkreditasi();

        $response = $this->actingAs($admin)->post(route('admin.trash.force-delete'), ['id' => $akreditasi->id]);

        $response->assertForbidden();
        $this->assertNotNull(Akreditasi::withTrashed()->find($akreditasi->id));
    }

    private function makeTrashedAkreditasi(): Akreditasi
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create(['user_id' => $user->id, 'status' => 5]);
        $akreditasi->delete();

        return $akreditasi;
    }

    public function test_unauthenticated_user_gets_redirected(): void
    {
        $response = $this->get(route('admin.trash'));
        $response->assertRedirect(route('login'));
    }
}
