<?php

namespace Tests\Feature\E2E;

use App\Models\Akreditasi;
use App\Models\User;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PermissionGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $superAdmin;

    private User $asesor;

    private User $pesantren;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(TestDataSeeder::class);
        $this->admin = User::where('email', 'bf.admin@test.local')->firstOrFail();
        $this->superAdmin = User::where('email', 'bf.superadmin@test.local')->firstOrFail();
        $this->asesor = User::where('email', 'bf.asesor1@test.local')->firstOrFail();
        $this->pesantren = User::where('email', 'bf.pesantren@test.local')->firstOrFail();
    }

    public function test_guest_is_redirected_before_admin_area(): void
    {
        $this->get(route('admin.akreditasi'))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_pesantren_cannot_access_admin_area(): void
    {
        $this->actingAs($this->pesantren)
            ->get(route('admin.akreditasi'))
            ->assertForbidden();
    }

    public function test_asesor_cannot_access_pesantren_area(): void
    {
        $this->actingAs($this->asesor)
            ->get(route('pesantren.akreditasi'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_asesor_area(): void
    {
        $this->actingAs($this->admin)
            ->get(route('asesor.akreditasi'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_pesantren_area(): void
    {
        $this->actingAs($this->admin)
            ->get(route('pesantren.akreditasi'))
            ->assertForbidden();
    }

    public function test_pesantren_cannot_view_other_tenant_akreditasi_detail(): void
    {
        $otherTenantAkreditasi = Akreditasi::where('catatan', 'like', '[BF-NEG-002]%')
            ->firstOrFail();

        $this->actingAs($this->pesantren)
            ->get(route('pesantren.akreditasi-detail', $otherTenantAkreditasi->uuid))
            ->assertNotFound();
    }

    public function test_admin_without_master_role_permission_is_forbidden(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_super_admin_bypasses_role_and_permission_guards(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.akreditasi'))
            ->assertOk();

        $this->actingAs($this->superAdmin)
            ->get(route('admin.roles.index'))
            ->assertOk();
    }

    public function test_role_authorized_admin_can_access_admin_workflow(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.akreditasi'))
            ->assertOk();
    }
}
