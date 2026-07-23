<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanduanAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_panduan_redirects_each_role_to_matching_guide(): void
    {
        $cases = [
            [Role::ID_SUPER_ADMIN, 'panduan.superadmin'],
            [Role::ID_ADMIN, 'panduan.admin'],
            [Role::ID_ASESOR, 'panduan.asesor'],
            [Role::ID_PESANTREN, 'panduan.pesantren'],
        ];

        foreach ($cases as [$roleId, $routeName]) {
            $user = User::factory()->create(['role_id' => $roleId, 'email_verified_at' => now()]);

            $this->actingAs($user)
                ->get('/panduan')
                ->assertRedirectToRoute($routeName);
        }
    }

    public function test_non_super_admin_roles_cannot_open_other_role_guides(): void
    {
        $cases = [
            [Role::ID_ADMIN, '/panduan-admin'],
            [Role::ID_ASESOR, '/panduan-asesor'],
            [Role::ID_PESANTREN, '/panduan-pesantren'],
        ];

        $routes = ['/panduan-superadmin', '/panduan-admin', '/panduan-asesor', '/panduan-pesantren'];

        foreach ($cases as [$roleId, $allowedRoute]) {
            $user = User::factory()->create(['role_id' => $roleId, 'email_verified_at' => now()]);

            foreach (array_diff($routes, [$allowedRoute]) as $route) {
                $this->actingAs($user)
                    ->get($route)
                    ->assertForbidden();
            }
        }
    }

    public function test_super_admin_keeps_existing_role_gate_bypass(): void
    {
        $user = User::factory()->create(['role_id' => Role::ID_SUPER_ADMIN, 'email_verified_at' => now()]);

        foreach (['/panduan-superadmin', '/panduan-admin', '/panduan-asesor', '/panduan-pesantren'] as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertOk();
        }
    }
}
