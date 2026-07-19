<?php

namespace Tests\Feature\E2E;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_user_can_register_login_and_logout(): void
    {
        $response = $this->post('/register', [
            'name' => 'E2E Pesantren',
            'email' => 'e2e.pesantren@test.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'e2e.pesantren@test.local',
            'role_id' => Role::ID_PESANTREN,
            'status' => 1,
        ]);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $this->post('/login', [
            'email' => 'e2e.pesantren@test.local',
            'password' => 'Password123!',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->asAdmin()->create(['status' => 0]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_invalid_login_does_not_authenticate(): void
    {
        $user = User::factory()->asAdmin()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_password_reset_flow_updates_password(): void
    {
        Notification::fake();
        $user = User::factory()->asPesantren()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])->assertRedirect(route('login', absolute: false));

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'Password123!',
            ])->assertRedirect(route('dashboard', absolute: false));

            $this->assertAuthenticatedAs($user->fresh());

            return true;
        });
    }
}
