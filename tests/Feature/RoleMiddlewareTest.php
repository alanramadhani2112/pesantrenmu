<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /** Task 1.4: pesantren user cannot access /admin/* routes → 403 */
    public function test_pesantren_user_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create(['role_id' => 3]); // pesantren

        $this->actingAs($user)
            ->get('/admin/akreditasi')
            ->assertForbidden();
    }

    /** Task 1.4: asesor user cannot access /admin/* routes → 403 */
    public function test_asesor_user_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create(['role_id' => 2]); // asesor

        $this->actingAs($user)
            ->get('/admin/akreditasi')
            ->assertForbidden();
    }

    /** Task 1.4: pesantren user cannot access /asesor/* routes → 403 */
    public function test_pesantren_user_cannot_access_asesor_routes(): void
    {
        $user = User::factory()->create(['role_id' => 3]); // pesantren

        $this->actingAs($user)
            ->get('/asesor/akreditasi')
            ->assertForbidden();
    }

    /** Task 1.4: admin user CAN access /admin/* routes → not 403 */
    public function test_admin_user_can_access_admin_routes(): void
    {
        $user = User::factory()->create(['role_id' => 1]); // admin

        $response = $this->actingAs($user)
            ->get('/admin/akreditasi');

        // Must not be a 403 Forbidden response
        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
