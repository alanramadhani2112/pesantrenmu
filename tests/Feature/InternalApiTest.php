<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InternalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_cannot_access_layout_api(): void
    {
        $this->getJson('/_api/sidebar-badges')
            ->assertUnauthorized();
    }

    public function test_sidebar_badges_keep_legacy_count_fields(): void
    {
        $user = User::factory()->asAdmin()->create();

        $this->actingAs($user)
            ->getJson('/_api/sidebar-badges')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => null,
                'pendingAkreditasiCount' => 0,
                'pendingBandingCount' => 0,
                'activeTaskCount' => 0,
            ]);
    }

    public function test_notifications_keep_legacy_fields(): void
    {
        $user = User::factory()->asAdmin()->create();
        $notificationId = $this->createNotificationFor($user);

        $this->actingAs($user)
            ->getJson('/_api/notifications')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => null,
                'unreadCount' => 1,
            ])
            ->assertJsonPath('notifications.0.id', $notificationId)
            ->assertJsonPath('notifications.0.data.url', '/dashboard');
    }

    public function test_user_can_mark_own_notification_read(): void
    {
        $user = User::factory()->asAdmin()->create();
        $notificationId = $this->createNotificationFor($user);

        $this->actingAs($user)
            ->postJson("/_api/notifications/{$notificationId}/read")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => null,
                'url' => '/dashboard',
            ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
            'read_at' => null,
        ]);
    }

    public function test_user_cannot_mark_another_user_notification_read(): void
    {
        $user = User::factory()->asAdmin()->create();
        $otherUser = User::factory()->asAdmin()->create();
        $notificationId = $this->createNotificationFor($otherUser);

        $this->actingAs($user)
            ->postJson("/_api/notifications/{$notificationId}/read")
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan.',
                'url' => '/dashboard',
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'read_at' => null,
        ]);
    }

    public function test_onboarding_status_keeps_legacy_fields(): void
    {
        $user = User::factory()->asPesantren()->create();

        $this->actingAs($user)
            ->getJson('/_api/onboarding/status')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => null,
                'showModal' => true,
                'allCompleted' => false,
            ])
            ->assertJsonStructure([
                'steps',
                'completionStatus',
            ]);
    }

    public function test_invalid_onboarding_step_returns_safe_404_shape(): void
    {
        $user = User::factory()->asPesantren()->create();

        $this->actingAs($user)
            ->postJson('/_api/onboarding/navigate', ['step_key' => 'missing-step'])
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Langkah onboarding tidak ditemukan.',
                'url' => '/dashboard',
            ]);
    }

    public function test_onboarding_skip_returns_success_shape(): void
    {
        $user = User::factory()->asPesantren()->create();

        $this->actingAs($user)
            ->postJson('/_api/onboarding/skip')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => null,
            ]);
    }

    public function test_onboarding_complete_returns_success_shape(): void
    {
        $user = User::factory()->asPesantren()->create();

        $this->actingAs($user)
            ->postJson('/_api/onboarding/complete')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => null,
            ]);
    }

    private function createNotificationFor(User $user): string
    {
        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Test',
                'message' => 'Hello',
                'url' => '/dashboard',
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $notificationId;
    }
}
