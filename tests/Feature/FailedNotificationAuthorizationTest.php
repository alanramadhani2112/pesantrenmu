<?php

namespace Tests\Feature;

use App\Models\FailedNotification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FailedNotificationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_without_retry_permission_cannot_retry_failed_notification(): void
    {
        $admin = User::factory()->create(['role_id' => Role::ID_ADMIN]);
        $permission = Permission::where('key', 'notification.retry')->firstOrFail();
        Role::findOrFail(Role::ID_ADMIN)->revokePermission($permission->id);
        $record = $this->makeRecord();

        $this->actingAs($admin)
            ->post(route('admin.failed-notifications.retry', $record->id))
            ->assertForbidden();
    }

    public function test_admin_without_dismiss_permission_cannot_dismiss_failed_notification(): void
    {
        $admin = User::factory()->create(['role_id' => Role::ID_ADMIN]);
        $permission = Permission::where('key', 'notification.dismiss')->firstOrFail();
        Role::findOrFail(Role::ID_ADMIN)->revokePermission($permission->id);
        $record = $this->makeRecord();

        $this->actingAs($admin)
            ->post(route('admin.failed-notifications.dismiss', $record->id))
            ->assertForbidden();

        $this->assertDatabaseHas('failed_notifications', ['id' => $record->id, 'status' => 'pending']);
    }

    private function makeRecord(): FailedNotification
    {
        $user = User::factory()->create(['role_id' => Role::ID_PESANTREN]);

        return FailedNotification::create([
            'notification_type' => 'akreditasi.status.updated',
            'notifiable_id' => $user->id,
            'payload' => [
                'type' => 'info',
                'title' => 'Test',
                'message' => 'Test message',
                'url' => '#',
            ],
            'failure_reason' => 'SMTP timeout',
            'failed_at' => now(),
            'status' => 'pending',
        ]);
    }
}
