<?php

namespace Tests\Feature;

use App\Models\Asesor;
use App\Models\User;
use App\Services\UserService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Coverage: UserService — saveAccount (create + update), deleteAccount,
 * toggleAccountStatus, findUser, getCountByRole.
 */
class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $service;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($this->admin);
        $this->service = app(UserService::class);
    }

    // ─── saveAccount (create) ─────────────────────────────────────────────────

    public function test_save_account_creates_user(): void
    {
        $this->service->saveAccount([
            'name' => 'User Baru',
            'email' => 'baru@test.com',
            'password' => 'secret123',
            'role_id' => 3,
            'status' => true,
        ]);

        $this->assertDatabaseHas('users', ['email' => 'baru@test.com', 'role_id' => 3]);
    }

    public function test_save_account_hashes_password(): void
    {
        $this->service->saveAccount([
            'name' => 'User Hash',
            'email' => 'hash@test.com',
            'password' => 'plaintext',
            'role_id' => 3,
            'status' => true,
        ]);

        $user = User::where('email', 'hash@test.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('plaintext', $user->password));
        $this->assertNotSame('plaintext', $user->password);
    }

    public function test_save_account_creates_asesor_profile_for_role_2(): void
    {
        $this->service->saveAccount([
            'name' => 'Asesor Baru',
            'email' => 'asesor.baru@test.com',
            'password' => 'secret',
            'role_id' => 2,
            'status' => true,
        ]);

        $user = User::where('email', 'asesor.baru@test.com')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('asesors', ['user_id' => $user->id]);
    }

    public function test_save_account_does_not_create_asesor_for_non_asesor_role(): void
    {
        $this->service->saveAccount([
            'name' => 'Pesantren Baru',
            'email' => 'pesantren.baru@test.com',
            'password' => 'secret',
            'role_id' => 3,
            'status' => true,
        ]);

        $user = User::where('email', 'pesantren.baru@test.com')->first();
        $this->assertDatabaseMissing('asesors', ['user_id' => $user->id]);
    }

    // ─── saveAccount (update) ─────────────────────────────────────────────────

    public function test_save_account_updates_existing_user(): void
    {
        $user = User::factory()->create(['role_id' => 3, 'name' => 'Old Name']);

        $this->service->saveAccount([
            'name' => 'New Name',
            'email' => $user->email,
            'role_id' => 3,
            'status' => true,
        ], $user->id);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_save_account_does_not_change_password_when_empty(): void
    {
        $user = User::factory()->create(['password' => Hash::make('original')]);
        $originalHash = $user->password;

        $this->service->saveAccount([
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role_id' => $user->role_id,
            'status' => true,
        ], $user->id);

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    // ─── deleteAccount ────────────────────────────────────────────────────────

    public function test_delete_account_removes_user(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $result = $this->service->deleteAccount($user->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    public function test_delete_account_returns_false_for_self(): void
    {
        $result = $this->service->deleteAccount($this->admin->id);
        $this->assertFalse($result);
    }

    // ─── toggleAccountStatus ─────────────────────────────────────────────────

    public function test_toggle_account_status_flips_status(): void
    {
        $user = User::factory()->create(['role_id' => 3, 'status' => 1]);

        $this->service->toggleAccountStatus($user->id);

        $this->assertSame(0, $user->fresh()->status);

        $this->service->toggleAccountStatus($user->id);

        $this->assertSame(1, $user->fresh()->status);
    }

    // ─── findUser ─────────────────────────────────────────────────────────────

    public function test_find_user_returns_model(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $found = $this->service->findUser($user->id);

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function test_find_user_returns_null_for_unknown(): void
    {
        $this->assertNull($this->service->findUser(99999));
    }

    // ─── getCountByRole ───────────────────────────────────────────────────────

    public function test_get_count_by_role_returns_correct_count(): void
    {
        User::factory()->count(3)->create(['role_id' => 3]);

        $count = $this->service->getCountByRole(3);

        $this->assertGreaterThanOrEqual(3, $count);
    }
}
