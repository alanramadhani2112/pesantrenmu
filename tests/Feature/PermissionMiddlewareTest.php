<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserHasPermission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_unauthenticated_user_gets_401(): void
    {
        $middleware = new EnsureUserHasPermission;
        $request = Request::create('/test', 'GET');

        $this->expectException(HttpException::class);

        $middleware->handle($request, fn () => new Response, 'akreditasi.view');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create(['role_id' => 2]); // asesor

        $middleware = new EnsureUserHasPermission;
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectException(HttpException::class);

        $middleware->handle($request, fn () => new Response, 'users.manage');
    }

    public function test_user_with_permission_passes_through(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['role_id' => 2]); // asesor
        $user->load('role.permissions');

        $middleware = new EnsureUserHasPermission;
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle(
            $request,
            fn () => new Response('OK'),
            'akreditasi.view' // asesor has this permission via RolePermissionSeeder
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_super_admin_passes_through_any_permission(): void
    {
        $user = User::factory()->create(['role_id' => 4]); // super_admin
        $user->load('role.permissions');

        $middleware = new EnsureUserHasPermission;
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle(
            $request,
            fn () => new Response('OK'),
            'any.random.permission'
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}
