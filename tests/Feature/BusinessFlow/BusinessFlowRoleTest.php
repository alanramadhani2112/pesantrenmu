<?php

namespace Tests\Feature\BusinessFlow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessFlowRoleTest extends TestCase
{
    use RefreshDatabase;
    use BusinessFlowTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBusinessFlowFixtures();
    }

    public function test_guest_is_redirected_from_business_areas(): void
    {
        foreach (['/dashboard', '/admin/akreditasi', '/pesantren/akreditasi', '/asesor/akreditasi'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_super_admin_can_access_admin_business_areas(): void
    {
        $user = $this->bfUser('bf.superadmin@test.local');

        foreach (['/dashboard', '/admin/master-role-permission', '/accounts', '/admin/akreditasi', '/admin/banding'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_admin_is_blocked_from_pesantren_and_asesor_areas(): void
    {
        $user = $this->bfUser('bf.admin@test.local');

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/admin/akreditasi')->assertOk();
        $this->actingAs($user)->get('/admin/banding')->assertOk();
        $this->actingAs($user)->get('/pesantren/akreditasi')->assertForbidden();
        $this->actingAs($user)->get('/asesor/akreditasi')->assertForbidden();
    }

    public function test_pesantren_is_blocked_from_admin_and_asesor_areas(): void
    {
        $user = $this->bfUser('bf.pesantren@test.local');

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/pesantren/profile')->assertOk();
        $this->actingAs($user)->get('/pesantren/ipm')->assertOk();
        $this->actingAs($user)->get('/pesantren/sdm')->assertOk();
        $this->actingAs($user)->get('/pesantren/edpm')->assertOk();
        $this->actingAs($user)->get('/pesantren/akreditasi')->assertOk();
        $this->actingAs($user)->get('/admin/akreditasi')->assertForbidden();
        $this->actingAs($user)->get('/asesor/akreditasi')->assertForbidden();
    }

    public function test_asesor_is_blocked_from_admin_and_pesantren_areas(): void
    {
        $user = $this->bfUser('bf.asesor1@test.local');

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/asesor/profile')->assertOk();
        $this->actingAs($user)->get('/asesor/akreditasi')->assertOk();
        $this->actingAs($user)->get('/admin/akreditasi')->assertForbidden();
        $this->actingAs($user)->get('/pesantren/akreditasi')->assertForbidden();
    }
}
