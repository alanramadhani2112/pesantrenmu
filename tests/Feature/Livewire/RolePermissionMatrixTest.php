<?php

namespace Tests\Feature\Livewire;

use App\Models\Permission;
use App\Models\PermissionAuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_save_persists_permission_matrix_to_pivot_table(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        $adminRole = Role::find(Role::ID_ADMIN);
        $permissions = Permission::take(3)->get();

        // Start with no permissions assigned
        $adminRole->permissions()->detach();

        $component = Volt::test('pages.admin.master.role-permission');

        // Build matrix: grant first 2 permissions to admin, deny the 3rd
        $matrix = $component->get('matrix');
        $matrix[$adminRole->id][$permissions[0]->id] = true;
        $matrix[$adminRole->id][$permissions[1]->id] = true;
        $matrix[$adminRole->id][$permissions[2]->id] = false;

        $component->set('matrix', $matrix)
            ->call('save')
            ->assertDispatched('notification-received');

        // Verify pivot table state
        $grantedIds = $adminRole->fresh()->permissions()->pluck('permissions.id')->all();
        $this->assertContains($permissions[0]->id, $grantedIds);
        $this->assertContains($permissions[1]->id, $grantedIds);
        $this->assertNotContains($permissions[2]->id, $grantedIds);
    }

    public function test_save_removes_previously_granted_permissions(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        $asesorRole = Role::find(Role::ID_ASESOR);
        $permissions = Permission::take(3)->get();

        // Pre-assign all 3 permissions
        $asesorRole->syncPermissions($permissions->pluck('id')->all());

        $component = Volt::test('pages.admin.master.role-permission');

        // Now revoke permission[1] via the matrix
        $matrix = $component->get('matrix');
        $matrix[$asesorRole->id][$permissions[0]->id] = true;
        $matrix[$asesorRole->id][$permissions[1]->id] = false;
        $matrix[$asesorRole->id][$permissions[2]->id] = true;

        $component->set('matrix', $matrix)
            ->call('save');

        $grantedIds = $asesorRole->fresh()->permissions()->pluck('permissions.id')->all();
        $this->assertContains($permissions[0]->id, $grantedIds);
        $this->assertNotContains($permissions[1]->id, $grantedIds);
        $this->assertContains($permissions[2]->id, $grantedIds);
    }

    public function test_save_updates_multiple_roles_in_single_transaction(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        $adminRole = Role::find(Role::ID_ADMIN);
        $asesorRole = Role::find(Role::ID_ASESOR);
        $pesantrenRole = Role::find(Role::ID_PESANTREN);
        $permissions = Permission::take(2)->get();

        // Clear all
        $adminRole->permissions()->detach();
        $asesorRole->permissions()->detach();
        $pesantrenRole->permissions()->detach();

        $component = Volt::test('pages.admin.master.role-permission');

        // Grant perm[0] to admin, perm[1] to asesor, both to pesantren
        $matrix = $component->get('matrix');
        $matrix[$adminRole->id][$permissions[0]->id] = true;
        $matrix[$adminRole->id][$permissions[1]->id] = false;
        $matrix[$asesorRole->id][$permissions[0]->id] = false;
        $matrix[$asesorRole->id][$permissions[1]->id] = true;
        $matrix[$pesantrenRole->id][$permissions[0]->id] = true;
        $matrix[$pesantrenRole->id][$permissions[1]->id] = true;

        $component->set('matrix', $matrix)
            ->call('save');

        $this->assertEquals(
            [$permissions[0]->id],
            $adminRole->fresh()->permissions()->pluck('permissions.id')->all()
        );
        $this->assertEquals(
            [$permissions[1]->id],
            $asesorRole->fresh()->permissions()->pluck('permissions.id')->all()
        );
        $this->assertEqualsCanonicalizing(
            $permissions->pluck('id')->all(),
            $pesantrenRole->fresh()->permissions()->pluck('permissions.id')->all()
        );
    }

    public function test_non_super_admin_cannot_access_matrix(): void
    {
        $admin = User::factory()->create(['role_id' => Role::ID_ADMIN]);
        $this->actingAs($admin);

        Volt::test('pages.admin.master.role-permission')
            ->assertForbidden();
    }

    public function test_save_dispatches_success_toast(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        $component = Volt::test('pages.admin.master.role-permission');

        $component->call('save')
            ->assertDispatched('notification-received', type: 'success', title: 'Hak akses tersimpan');
    }

    public function test_matrix_supports_search_and_group_filter(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        Volt::test('pages.admin.master.role-permission')
            ->set('groupFilter', 'master')
            ->set('search', 'EDPM')
            ->assertSee('Kelola Master EDPM')
            ->assertDontSee('Lihat Akreditasi')
            ->assertDontSee('Kelola Master Dokumen');
    }

    public function test_bulk_role_action_sets_only_visible_permissions(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        $adminRole = Role::find(Role::ID_ADMIN);
        $adminRole->permissions()->detach();

        $edpmPermission = Permission::where('key', 'master.edpm')->firstOrFail();
        $dokumenPermission = Permission::where('key', 'master.dokumen')->firstOrFail();

        $component = Volt::test('pages.admin.master.role-permission')
            ->set('groupFilter', 'master')
            ->set('search', 'EDPM')
            ->call('grantVisibleForRole', $adminRole->id);

        $matrix = $component->get('matrix');

        $this->assertTrue($matrix[$adminRole->id][$edpmPermission->id]);
        $this->assertFalse($matrix[$adminRole->id][$dokumenPermission->id]);
    }

    public function test_bulk_visible_action_can_apply_to_all_editable_roles(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        $edpmPermission = Permission::where('key', 'master.edpm')->firstOrFail();

        $component = Volt::test('pages.admin.master.role-permission')
            ->set('groupFilter', 'master')
            ->set('search', 'EDPM')
            ->call('grantVisibleForAllRoles');

        $matrix = $component->get('matrix');

        foreach ([Role::ID_ADMIN, Role::ID_ASESOR, Role::ID_PESANTREN] as $roleId) {
            $this->assertTrue($matrix[$roleId][$edpmPermission->id]);
        }
    }

    public function test_audit_history_is_rendered_on_matrix_page(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        PermissionAuditLog::create([
            'user_id' => $superAdmin->id,
            'role_id' => Role::ID_ADMIN,
            'permissions_added' => ['master.edpm'],
            'permissions_removed' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);

        Volt::test('pages.admin.master.role-permission')
            ->assertSee('Riwayat Perubahan')
            ->assertSee('master.edpm')
            ->assertSee('Admin');
    }

    public function test_role_permission_page_uses_clean_metronic_matrix_layout(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        Volt::test('pages.admin.master.role-permission')
            ->assertSee('spm-permission-control-panel', false)
            ->assertSee('spm-permission-quick-actions', false)
            ->assertSee('spm-permission-role-actions', false)
            ->assertSee('spm-permission-matrix', false)
            ->assertSee('data-ui-simple-table="metronic"', false);
    }

    // -------------------------------------------------------------------------
    // Audit log tests (task 4.4)
    // -------------------------------------------------------------------------

    public function test_save_writes_audit_log_when_permissions_added(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        $adminRole = Role::find(Role::ID_ADMIN);
        $permissions = Permission::take(2)->get();

        // Start with no permissions
        $adminRole->permissions()->detach();

        $component = Volt::test('pages.admin.master.role-permission');
        $matrix = $component->get('matrix');

        $matrix[$adminRole->id][$permissions[0]->id] = true;
        $matrix[$adminRole->id][$permissions[1]->id] = true;

        $component->set('matrix', $matrix)->call('save');

        // An audit log row must exist for the admin role
        $this->assertDatabaseHas('permission_audit_logs', [
            'user_id' => $superAdmin->id,
            'role_id' => $adminRole->id,
        ]);

        $log = PermissionAuditLog::where('role_id', $adminRole->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertContains($permissions[0]->key, $log->permissions_added);
        $this->assertContains($permissions[1]->key, $log->permissions_added);
        $this->assertEmpty($log->permissions_removed);
    }

    public function test_save_writes_audit_log_when_permissions_removed(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        $asesorRole = Role::find(Role::ID_ASESOR);
        $permissions = Permission::take(2)->get();

        // Pre-assign both permissions
        $asesorRole->syncPermissions($permissions->pluck('id')->all());

        $component = Volt::test('pages.admin.master.role-permission');
        $matrix = $component->get('matrix');

        // Revoke the second permission
        $matrix[$asesorRole->id][$permissions[0]->id] = true;
        $matrix[$asesorRole->id][$permissions[1]->id] = false;

        $component->set('matrix', $matrix)->call('save');

        $log = PermissionAuditLog::where('role_id', $asesorRole->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertContains($permissions[1]->key, $log->permissions_removed);
        $this->assertEmpty($log->permissions_added);
    }

    public function test_save_does_not_write_audit_log_when_nothing_changed(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        $adminRole = Role::find(Role::ID_ADMIN);
        $permissions = Permission::take(2)->get();

        // Pre-assign permissions
        $adminRole->syncPermissions($permissions->pluck('id')->all());

        $component = Volt::test('pages.admin.master.role-permission');

        // Save without changing anything
        $component->call('save');

        // No audit log should be written for this role
        $this->assertDatabaseMissing('permission_audit_logs', [
            'role_id' => $adminRole->id,
        ]);
    }

    public function test_save_records_actor_in_audit_log(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        $pesantrenRole = Role::find(Role::ID_PESANTREN);
        $permission = Permission::first();

        $pesantrenRole->permissions()->detach();

        $component = Volt::test('pages.admin.master.role-permission');
        $matrix = $component->get('matrix');
        $matrix[$pesantrenRole->id][$permission->id] = true;

        $component->set('matrix', $matrix)->call('save');

        $log = PermissionAuditLog::where('role_id', $pesantrenRole->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals($superAdmin->id, $log->user_id);
    }

    public function test_save_writes_to_application_log_on_permission_change(): void
    {
        Log::spy();

        $superAdmin = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN]);
        $this->actingAs($superAdmin);

        $adminRole = Role::find(Role::ID_ADMIN);
        $permission = Permission::first();

        $adminRole->permissions()->detach();

        $component = Volt::test('pages.admin.master.role-permission');
        $matrix = $component->get('matrix');
        $matrix[$adminRole->id][$permission->id] = true;

        $component->set('matrix', $matrix)->call('save');

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($channel, $context = []) => $channel === 'permission_matrix_changed' ||
                (is_array($context) && isset($context['role_id']))
            );
    }
}
