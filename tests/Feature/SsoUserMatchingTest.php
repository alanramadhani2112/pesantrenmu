<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use App\Services\Sso\UserService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for SSO user matching logic in Sso\UserService::findOrCreate().
 *
 * Covers Task 6 sub-tasks:
 *   6.1 Matching priority: email → profile.data.id → create new
 *   6.2 Role NOT overridden when sso_sync_role = false
 *   6.3 Manually-created user linked on first SSO login (no duplicate)
 *   6.4 Email changed in SSO → update email via profile.data.id match
 */
class SsoUserMatchingTest extends TestCase
{
    use RefreshDatabase;

    /** Sample SSO user payload returned by the SSO server */
    private function makeSsoPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 'sso-uid-123',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'level' => 3, // maps to role_id = 2 (asesor)
        ], $overrides);
    }

    /**
     * Invoke the protected findOrCreate() method via the public getUser() entry
     * point by faking the HTTP call to the SSO server.
     */
    private function callGetUser(array $ssoPayload): ?User
    {
        Http::fake([
            '*' => Http::response($ssoPayload, 200),
        ]);

        $result = UserService::getUser('fake-token');

        return $result instanceof User ? $result : null;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['sso.server_url' => 'https://sso.example.com/']);
    }

    // -------------------------------------------------------------------------
    // 6.1  Matching priority: email → profile.data.id → create new
    // -------------------------------------------------------------------------

    /**
     * 6.1a  When no user exists at all, a new user is created.
     *
     * Task 7.1: Verifies role mapping from SSO level field:
     *   level 1 → role_id 1 (admin)
     *   level 2 → role_id 3 (pesantren)
     *   level 3 → role_id 2 (asesor)
     */
    public function test_creates_new_user_when_no_match_found(): void
    {
        $this->assertDatabaseCount('users', 0);

        $user = $this->callGetUser($this->makeSsoPayload());

        $this->assertNotNull($user);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
        $this->assertNotNull($user->sso_linked_at);
        $this->assertTrue($user->sso_sync_role);

        // Task 7.1: level 3 must map to role_id 2 (asesor)
        $this->assertEquals(2, $user->role_id, 'SSO level 3 should map to role_id 2 (asesor)');
    }

    /**
     * Task 7.1: SSO level 1 → role_id 1 (admin).
     */
    public function test_new_user_with_level_1_gets_admin_role(): void
    {
        $user = $this->callGetUser($this->makeSsoPayload([
            'id' => 'sso-uid-level1',
            'email' => 'user-level1@example.com',
            'level' => 1,
        ]));

        $this->assertNotNull($user);
        $this->assertEquals(1, $user->role_id, 'SSO level 1 should map to role_id 1 (admin)');
    }

    /**
     * Task 7.1: SSO level 2 → role_id 3 (pesantren).
     */
    public function test_new_user_with_level_2_gets_pesantren_role(): void
    {
        $user = $this->callGetUser($this->makeSsoPayload([
            'id' => 'sso-uid-level2',
            'email' => 'user-level2@example.com',
            'level' => 2,
        ]));

        $this->assertNotNull($user);
        $this->assertEquals(3, $user->role_id, 'SSO level 2 should map to role_id 3 (pesantren)');
    }

    /**
     * Task 7.1: SSO level 3 → role_id 2 (asesor).
     */
    public function test_new_user_with_level_3_gets_asesor_role(): void
    {
        $user = $this->callGetUser($this->makeSsoPayload([
            'id' => 'sso-uid-level3',
            'email' => 'user-level3@example.com',
            'level' => 3,
        ]));

        $this->assertNotNull($user);
        $this->assertEquals(2, $user->role_id, 'SSO level 3 should map to role_id 2 (asesor)');
    }

    /**
     * 6.1b  When a user with the same email already exists, that user is
     *       returned (no duplicate created).
     */
    public function test_finds_existing_user_by_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'budi@example.com',
            'role_id' => 3,
        ]);

        $user = $this->callGetUser($this->makeSsoPayload());

        $this->assertNotNull($user);
        $this->assertEquals($existing->id, $user->id);
        $this->assertDatabaseCount('users', 1); // no duplicate
    }

    /**
     * 6.1c  When no user matches by email but a Profile row with the same
     *       SSO id exists, that user is returned (no duplicate created).
     */
    public function test_finds_existing_user_by_profile_sso_id_when_email_not_found(): void
    {
        // User with a different email but already has a profile linked to the SSO id
        $existing = User::factory()->create([
            'email' => 'old-email@example.com',
            'role_id' => 3,
        ]);
        $existing->profile_data()->create([
            'data' => ['id' => 'sso-uid-123', 'name' => 'Budi', 'email' => 'old-email@example.com'],
            'access_token' => 'old-token',
        ]);

        // SSO now reports a different email for the same SSO id
        $user = $this->callGetUser($this->makeSsoPayload(['email' => 'new-email@example.com']));

        $this->assertNotNull($user);
        $this->assertEquals($existing->id, $user->id);
        $this->assertDatabaseCount('users', 1); // no duplicate
    }

    // -------------------------------------------------------------------------
    // 6.2  Role NOT overridden when sso_sync_role = false
    // -------------------------------------------------------------------------

    /**
     * 6.2a  When sso_sync_role is true, the role IS updated from SSO.
     */
    public function test_role_is_synced_when_sso_sync_role_is_true(): void
    {
        $existing = User::factory()->create([
            'email' => 'budi@example.com',
            'role_id' => 3, // pesantren
            'sso_sync_role' => true,
        ]);

        // SSO says level=1 → role_id=1 (admin)
        $this->callGetUser($this->makeSsoPayload(['level' => 1]));

        $existing->refresh();
        $this->assertEquals(1, $existing->role_id);
    }

    /**
     * 6.2b  When sso_sync_role is false, the role is NOT changed even though
     *       SSO reports a different level.
     */
    public function test_role_is_not_overridden_when_sso_sync_role_is_false(): void
    {
        $existing = User::factory()->create([
            'email' => 'budi@example.com',
            'role_id' => 1, // admin — manually set
            'sso_sync_role' => false,
        ]);

        // SSO says level=3 → would map to role_id=2 (asesor)
        $this->callGetUser($this->makeSsoPayload(['level' => 3]));

        $existing->refresh();
        $this->assertEquals(1, $existing->role_id); // unchanged
    }

    // -------------------------------------------------------------------------
    // 6.3  Manually-created user linked on first SSO login (no duplicate)
    // -------------------------------------------------------------------------

    /**
     * 6.3a  A user created manually (no sso_linked_at) gets sso_linked_at set
     *       on their first SSO login.
     */
    public function test_manually_created_user_gets_linked_on_first_sso_login(): void
    {
        $existing = User::factory()->create([
            'email' => 'budi@example.com',
            'role_id' => 3,
            'sso_linked_at' => null, // not yet linked
        ]);

        $this->assertNull($existing->sso_linked_at);

        $user = $this->callGetUser($this->makeSsoPayload());

        $this->assertNotNull($user);
        $this->assertEquals($existing->id, $user->id);
        $this->assertDatabaseCount('users', 1); // no duplicate created

        $existing->refresh();
        $this->assertNotNull($existing->sso_linked_at);
    }

    /**
     * 6.3b  sso_linked_at is NOT overwritten on subsequent SSO logins.
     */
    public function test_sso_linked_at_is_not_overwritten_on_subsequent_logins(): void
    {
        $linkedAt = now()->subDays(5);

        $existing = User::factory()->create([
            'email' => 'budi@example.com',
            'role_id' => 3,
            'sso_linked_at' => $linkedAt,
        ]);

        $this->callGetUser($this->makeSsoPayload());

        $existing->refresh();
        // Timestamp should remain the original value (within 1 second tolerance)
        $this->assertTrue(
            $existing->sso_linked_at->diffInSeconds($linkedAt) < 2,
            'sso_linked_at should not be overwritten on subsequent logins'
        );
    }

    // -------------------------------------------------------------------------
    // 6.4  Email changed in SSO → update email via profile.data.id match
    // -------------------------------------------------------------------------

    /**
     * 6.4  When a user's email changes in SSO:
     *      - The lookup by new email fails (no user with that email)
     *      - The fallback lookup by profile.data.id succeeds
     *      - The user's email in the system is updated to the new SSO email
     *      - No duplicate user is created
     */
    public function test_email_updated_when_sso_email_changes(): void
    {
        // User exists with old email and a linked SSO profile
        $existing = User::factory()->create([
            'email' => 'budi-old@example.com',
            'role_id' => 3,
            'sso_linked_at' => now()->subDays(10),
        ]);
        $existing->profile_data()->create([
            'data' => [
                'id' => 'sso-uid-123',
                'name' => 'Budi Santoso',
                'email' => 'budi-old@example.com',
                'level' => 3,
            ],
            'access_token' => 'old-token',
        ]);

        // SSO now reports a new email for the same SSO id
        $user = $this->callGetUser($this->makeSsoPayload([
            'id' => 'sso-uid-123',
            'email' => 'budi-new@example.com',
        ]));

        $this->assertNotNull($user);
        $this->assertEquals($existing->id, $user->id);
        $this->assertDatabaseCount('users', 1); // no duplicate

        $existing->refresh();
        $this->assertEquals('budi-new@example.com', $existing->email);
    }

    /**
     * 6.4b  When both old-email user AND a profile-id match exist for
     *       different users, the email match takes priority (6.1 ordering).
     */
    public function test_email_match_takes_priority_over_profile_id_match(): void
    {
        // User A: matches by email
        $userA = User::factory()->create([
            'email' => 'budi@example.com',
            'role_id' => 3,
        ]);

        // User B: has a profile with the same SSO id but different email
        $userB = User::factory()->create([
            'email' => 'other@example.com',
            'role_id' => 3,
        ]);
        $userB->profile_data()->create([
            'data' => ['id' => 'sso-uid-123', 'name' => 'Other', 'email' => 'other@example.com'],
            'access_token' => 'token-b',
        ]);

        // SSO payload matches userA by email
        $user = $this->callGetUser($this->makeSsoPayload([
            'id' => 'sso-uid-123',
            'email' => 'budi@example.com',
        ]));

        $this->assertNotNull($user);
        $this->assertEquals($userA->id, $user->id); // email match wins
        $this->assertDatabaseCount('users', 2);      // no new user created
    }
}
