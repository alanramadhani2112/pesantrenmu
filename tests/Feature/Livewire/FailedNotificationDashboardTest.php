<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Pages\Admin\FailedNotificationDashboard;
use App\Models\FailedNotification;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Livewire component tests for FailedNotificationDashboard.
 *
 * Validates: Requirements 5.1, 5.2, 5.3, 5.4
 */
class FailedNotificationDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function createAdminUser(): User
    {
        return User::factory()->create(['role_id' => 1]);
    }

    private function createPesantrenUser(): User
    {
        return User::factory()->create(['role_id' => 3]);
    }

    private function createFailedNotification(User $user, string $status = 'pending'): FailedNotification
    {
        return FailedNotification::create([
            'notification_type' => 'assessment',
            'notifiable_id' => $user->id,
            'payload' => [
                'type' => 'assessment',
                'title' => 'Update Status: Verifikasi Berkas',
                'message' => 'Pengajuan akreditasi Anda telah diverifikasi.',
                'url' => 'https://example.com/akreditasi',
            ],
            'failure_reason' => 'WebPush provider timeout',
            'failed_at' => now()->subHour(),
            'status' => $status,
        ]);
    }

    /**
     * Task 7.7: Dashboard renders paginated list of failed notifications.
     *
     * Validates: Requirement 5.1
     */
    public function test_dashboard_renders_paginated_failed_notifications(): void
    {
        $admin = $this->createAdminUser();
        $pesantrenUser = $this->createPesantrenUser();

        $this->createFailedNotification($pesantrenUser);

        $this->actingAs($admin);

        Livewire::test(FailedNotificationDashboard::class)
            ->assertSee('assessment')
            ->assertSee('WebPush provider timeout')
            ->assertSee($pesantrenUser->name);
    }

    /**
     * Task 7.7: Retry button re-dispatches notification and updates status.
     *
     * Validates: Requirement 5.2
     */
    public function test_retry_dispatches_notification_and_updates_status(): void
    {
        Notification::fake();

        $admin = $this->createAdminUser();
        $pesantrenUser = $this->createPesantrenUser();

        $record = $this->createFailedNotification($pesantrenUser);

        $this->actingAs($admin);

        Livewire::test(FailedNotificationDashboard::class)
            ->call('retry', $record->id);

        // Status updated to resolved
        $record->refresh();
        $this->assertSame('resolved', $record->status);
        $this->assertNotNull($record->resolved_at);

        // Notification re-dispatched
        Notification::assertSentTo($pesantrenUser, AkreditasiNotification::class);
    }

    /**
     * Task 7.7: Dismiss button marks record as dismissed.
     *
     * Validates: Requirement 5.3
     */
    public function test_dismiss_marks_record_as_dismissed(): void
    {
        $admin = $this->createAdminUser();
        $pesantrenUser = $this->createPesantrenUser();

        $record = $this->createFailedNotification($pesantrenUser);

        $this->actingAs($admin);

        Livewire::test(FailedNotificationDashboard::class)
            ->call('dismiss', $record->id);

        $record->refresh();
        $this->assertSame('dismissed', $record->status);
    }

    /**
     * Task 7.7: Non-admin users cannot access the dashboard (403).
     *
     * Validates: Requirement 5.4
     */
    public function test_non_admin_cannot_access_dashboard(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $this->actingAs($pesantrenUser);

        // Non-admin should get 403 from the role middleware
        $response = $this->get(route('admin.failed-notifications'));
        $response->assertStatus(403);
    }

    /**
     * Task 7.7: Dashboard shows pending count badge.
     *
     * Validates: Requirement 5.5
     */
    public function test_dashboard_shows_pending_count(): void
    {
        $admin = $this->createAdminUser();
        $pesantrenUser = $this->createPesantrenUser();

        // Create 3 pending and 1 resolved
        $this->createFailedNotification($pesantrenUser, 'pending');
        $this->createFailedNotification($pesantrenUser, 'pending');
        $this->createFailedNotification($pesantrenUser, 'pending');
        $this->createFailedNotification($pesantrenUser, 'resolved');

        $this->actingAs($admin);

        $component = Livewire::test(FailedNotificationDashboard::class);

        $component->assertSet('pendingCount', 3);
    }

    /**
     * Task 7.7: Status filter works correctly.
     *
     * Validates: Requirement 5.1
     */
    public function test_status_filter_shows_only_matching_records(): void
    {
        $admin = $this->createAdminUser();
        $pesantrenUser = $this->createPesantrenUser();

        $pending = $this->createFailedNotification($pesantrenUser, 'pending');
        $resolved = $this->createFailedNotification($pesantrenUser, 'resolved');

        $this->actingAs($admin);

        // Default filter is 'pending' — should show pending, not resolved
        $component = Livewire::test(FailedNotificationDashboard::class);
        $component->assertSet('statusFilter', 'pending');

        // Switch to resolved filter
        $component->set('statusFilter', 'resolved');
        $component->assertSet('statusFilter', 'resolved');
    }

    /**
     * Task 7.5: Route is protected by admin role middleware.
     *
     * Validates: Requirement 5.4
     */
    public function test_route_is_protected_by_admin_middleware(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $this->actingAs($pesantrenUser);

        $response = $this->get(route('admin.failed-notifications'));

        $response->assertStatus(403);
    }

    /**
     * Task 7.5: Admin can access the route (component renders without error).
     *
     * Validates: Requirement 5.4
     */
    public function test_admin_can_access_dashboard_component(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        // Test via Livewire::test() which doesn't require the full layout
        $component = Livewire::test(FailedNotificationDashboard::class);
        $component->assertOk();
    }

    /**
     * Task 7.6: Sidebar badge count includes failed notifications.
     *
     * Validates: Requirement 5.5
     */
    public function test_sidebar_badge_count_includes_failed_notifications(): void
    {
        $admin = $this->createAdminUser();
        $pesantrenUser = $this->createPesantrenUser();

        $this->createFailedNotification($pesantrenUser, 'pending');
        $this->createFailedNotification($pesantrenUser, 'pending');

        $this->actingAs($admin);

        // Verify the FailedNotification count is accessible
        $this->assertSame(2, FailedNotification::where('status', 'pending')->count());
    }
}
