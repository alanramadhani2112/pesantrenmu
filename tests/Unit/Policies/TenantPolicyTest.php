<?php

namespace Tests\Unit\Policies;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Banding;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Ipm;
use App\Models\Pesantren;
use App\Models\Role;
use App\Models\SdmPesantren;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Multi-tenant policy boundary tests (audit fix H-2 P0).
 *
 * Verifies that:
 *   1. Super admin gets god-mode pass via Gate::before for every policy.
 *   2. Owners can manage their own records (pesantren -> akreditasi/banding/etc).
 *   3. Foreign-tenant users are blocked - this is the critical leak guard.
 *   4. Admin/Asesor visibility scopes match expectations.
 */
class TenantPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    // ─── Pesantren policy ──────────────────────────────────────────────────────

    public function test_pesantren_owner_can_view_and_update_their_own_pesantren(): void
    {
        [$user, $pesantren] = $this->makePesantrenUser();

        $this->assertTrue(Gate::forUser($user)->allows('view', $pesantren));
        $this->assertTrue(Gate::forUser($user)->allows('update', $pesantren));
        $this->assertFalse(
            Gate::forUser($user)->allows('delete', $pesantren),
            'Pesantren owners must not be able to delete their own record.'
        );
    }

    public function test_foreign_pesantren_cannot_access_other_tenant_pesantren(): void
    {
        [, $aPesantren] = $this->makePesantrenUser();
        [$bUser] = $this->makePesantrenUser();

        $this->assertFalse(
            Gate::forUser($bUser)->allows('view', $aPesantren),
            'Multi-tenant breach: foreign pesantren must not see another pesantren.'
        );
        $this->assertFalse(
            Gate::forUser($bUser)->allows('update', $aPesantren),
            'Multi-tenant breach: foreign pesantren must not update another pesantren.'
        );
    }

    public function test_admin_can_view_any_pesantren(): void
    {
        $admin = $this->makeUser(Role::ID_ADMIN);
        [, $pesantren] = $this->makePesantrenUser();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $pesantren));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $pesantren));
    }

    public function test_super_admin_bypasses_pesantren_policy(): void
    {
        $sa = $this->makeUser(Role::ID_SUPER_ADMIN);
        [, $pesantren] = $this->makePesantrenUser();

        $this->assertTrue(Gate::forUser($sa)->allows('update', $pesantren));
        $this->assertTrue(Gate::forUser($sa)->allows('delete', $pesantren));
    }

    // ─── Akreditasi policy ─────────────────────────────────────────────────────

    public function test_pesantren_can_view_and_update_their_own_akreditasi(): void
    {
        [$user] = $this->makePesantrenUser();
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 1,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('view', $akreditasi));
        $this->assertTrue(Gate::forUser($user)->allows('update', $akreditasi));
        $this->assertTrue(Gate::forUser($user)->allows('submitBanding', $akreditasi));
    }

    public function test_foreign_pesantren_cannot_access_other_akreditasi(): void
    {
        [$ownerUser] = $this->makePesantrenUser();
        [$strangerUser] = $this->makePesantrenUser();
        $akreditasi = Akreditasi::create([
            'user_id' => $ownerUser->id,
            'status' => 1,
        ]);

        $this->assertFalse(Gate::forUser($strangerUser)->allows('view', $akreditasi));
        $this->assertFalse(Gate::forUser($strangerUser)->allows('update', $akreditasi));
        $this->assertFalse(Gate::forUser($strangerUser)->allows('submitBanding', $akreditasi));
    }

    public function test_assigned_asesor_can_view_akreditasi_but_unassigned_cannot(): void
    {
        [$pUser] = $this->makePesantrenUser();
        $akreditasi = Akreditasi::create(['user_id' => $pUser->id, 'status' => 4]);

        [$assignedAsesor, $assignedAsesorRecord] = $this->makeAsesorUser();
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $assignedAsesorRecord->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(7),
        ]);

        [$otherAsesor] = $this->makeAsesorUser();

        $this->assertTrue(
            Gate::forUser($assignedAsesor)->allows('view', $akreditasi),
            'Assigned asesor must be able to view their assessment.'
        );
        $this->assertFalse(
            Gate::forUser($otherAsesor)->allows('view', $akreditasi),
            'Unassigned asesor must not see akreditasi they were not given.'
        );
    }

    public function test_only_admin_can_finalize_akreditasi(): void
    {
        $admin = $this->makeUser(Role::ID_ADMIN);
        [$pUser] = $this->makePesantrenUser();
        $akreditasi = Akreditasi::create(['user_id' => $pUser->id, 'status' => 5]);

        $this->assertTrue(Gate::forUser($admin)->allows('finalize', $akreditasi));
        $this->assertFalse(Gate::forUser($pUser)->allows('finalize', $akreditasi));
    }

    public function test_super_admin_bypasses_akreditasi_policy(): void
    {
        $sa = $this->makeUser(Role::ID_SUPER_ADMIN);
        [$pUser] = $this->makePesantrenUser();
        $akreditasi = Akreditasi::create(['user_id' => $pUser->id, 'status' => 1]);

        $this->assertTrue(Gate::forUser($sa)->allows('view', $akreditasi));
        $this->assertTrue(Gate::forUser($sa)->allows('update', $akreditasi));
        $this->assertTrue(Gate::forUser($sa)->allows('finalize', $akreditasi));
    }

    // ─── Banding policy ────────────────────────────────────────────────────────

    public function test_banding_owner_can_only_edit_while_pending(): void
    {
        [$pUser] = $this->makePesantrenUser();
        $akreditasi = Akreditasi::create(['user_id' => $pUser->id, 'status' => 2]);

        $pending = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pUser->id,
            'status' => 'pending',
            'alasan' => str_repeat('a', 50),
        ]);
        $under = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pUser->id,
            'status' => 'under_review',
            'alasan' => str_repeat('a', 50),
        ]);

        $this->assertTrue(Gate::forUser($pUser)->allows('update', $pending));
        $this->assertFalse(
            Gate::forUser($pUser)->allows('update', $under),
            'Pesantren must not edit banding once under admin review.'
        );
    }

    public function test_foreign_pesantren_cannot_view_other_banding(): void
    {
        [$ownerUser] = $this->makePesantrenUser();
        [$strangerUser] = $this->makePesantrenUser();
        $akreditasi = Akreditasi::create(['user_id' => $ownerUser->id, 'status' => 2]);
        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $ownerUser->id,
            'status' => 'pending',
            'alasan' => str_repeat('a', 50),
        ]);

        $this->assertFalse(Gate::forUser($strangerUser)->allows('view', $banding));
    }

    public function test_admin_can_review_any_banding(): void
    {
        $admin = $this->makeUser(Role::ID_ADMIN);
        [$pUser] = $this->makePesantrenUser();
        $akreditasi = Akreditasi::create(['user_id' => $pUser->id, 'status' => 2]);
        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pUser->id,
            'status' => 'pending',
            'alasan' => str_repeat('a', 50),
        ]);

        $this->assertTrue(Gate::forUser($admin)->allows('view', $banding));
        $this->assertTrue(Gate::forUser($admin)->allows('review', $banding));
    }

    // ─── Document policy ───────────────────────────────────────────────────────

    public function test_pesantren_sees_public_and_pesantren_secret_documents(): void
    {
        [$pUser] = $this->makePesantrenUser();
        $publicDoc = $this->makeDocument(DocumentCategory::VISIBILITY_PUBLIC);
        $pesantrenDoc = $this->makeDocument(DocumentCategory::VISIBILITY_PESANTREN_SECRET);
        $asesorDoc = $this->makeDocument(DocumentCategory::VISIBILITY_ASESOR_SECRET);

        $this->assertTrue(Gate::forUser($pUser)->allows('view', $publicDoc));
        $this->assertTrue(Gate::forUser($pUser)->allows('view', $pesantrenDoc));
        $this->assertFalse(
            Gate::forUser($pUser)->allows('view', $asesorDoc),
            'Pesantren must not see asesor_secret documents.'
        );
    }

    public function test_asesor_sees_public_and_asesor_secret_documents(): void
    {
        [$aUser] = $this->makeAsesorUser();
        $publicDoc = $this->makeDocument(DocumentCategory::VISIBILITY_PUBLIC);
        $pesantrenDoc = $this->makeDocument(DocumentCategory::VISIBILITY_PESANTREN_SECRET);
        $asesorDoc = $this->makeDocument(DocumentCategory::VISIBILITY_ASESOR_SECRET);

        $this->assertTrue(Gate::forUser($aUser)->allows('view', $publicDoc));
        $this->assertTrue(Gate::forUser($aUser)->allows('view', $asesorDoc));
        $this->assertFalse(
            Gate::forUser($aUser)->allows('view', $pesantrenDoc),
            'Asesor must not see pesantren_secret documents.'
        );
    }

    public function test_only_admin_or_super_admin_can_create_documents(): void
    {
        $admin = $this->makeUser(Role::ID_ADMIN);
        $sa = $this->makeUser(Role::ID_SUPER_ADMIN);
        [$pUser] = $this->makePesantrenUser();

        $this->assertTrue(Gate::forUser($admin)->allows('create', Document::class));
        $this->assertTrue(Gate::forUser($sa)->allows('create', Document::class));
        $this->assertFalse(Gate::forUser($pUser)->allows('create', Document::class));
    }

    // ─── Ipm + SdmPesantren policy ─────────────────────────────────────────────

    public function test_ipm_owner_can_update_only_their_own(): void
    {
        [$ownerUser] = $this->makePesantrenUser();
        [$strangerUser] = $this->makePesantrenUser();

        $ipm = Ipm::create(['user_id' => $ownerUser->id]);

        $this->assertTrue(Gate::forUser($ownerUser)->allows('update', $ipm));
        $this->assertFalse(Gate::forUser($strangerUser)->allows('update', $ipm));
    }

    public function test_sdm_owner_can_update_only_their_own(): void
    {
        [$ownerUser] = $this->makePesantrenUser();
        [$strangerUser] = $this->makePesantrenUser();

        $sdm = SdmPesantren::create([
            'user_id' => $ownerUser->id,
            'tingkat' => 'sma',
        ]);

        $this->assertTrue(Gate::forUser($ownerUser)->allows('update', $sdm));
        $this->assertFalse(Gate::forUser($strangerUser)->allows('update', $sdm));
    }

    // ─── Asesor policy ─────────────────────────────────────────────────────────

    public function test_asesor_can_update_only_their_own_profile(): void
    {
        [$selfUser, $selfAsesor] = $this->makeAsesorUser();
        [$otherUser] = $this->makeAsesorUser();

        $this->assertTrue(Gate::forUser($selfUser)->allows('update', $selfAsesor));
        $this->assertFalse(
            Gate::forUser($otherUser)->allows('update', $selfAsesor),
            'Foreign asesor must not edit another asesor profile.'
        );
    }

    public function test_admin_can_manage_any_asesor(): void
    {
        $admin = $this->makeUser(Role::ID_ADMIN);
        [, $asesor] = $this->makeAsesorUser();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $asesor));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $asesor));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $asesor));
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function makeUser(int $roleId): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
        ])->fresh(['role.permissions']);
    }

    /**
     * @return array{0: User, 1: Pesantren}
     */
    private function makePesantrenUser(): array
    {
        $user = $this->makeUser(Role::ID_PESANTREN);
        $pesantren = Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren '.$user->id,
        ]);

        return [$user, $pesantren];
    }

    /**
     * @return array{0: User, 1: Asesor}
     */
    private function makeAsesorUser(): array
    {
        $user = $this->makeUser(Role::ID_ASESOR);
        $asesor = Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => 'Asesor '.$user->id,
            'nama_tanpa_gelar' => 'Asesor '.$user->id,
        ]);
        $user = $user->fresh(['role.permissions', 'asesor']);

        return [$user, $asesor];
    }

    private function makeDocument(string $visibility): Document
    {
        $category = DocumentCategory::create([
            'name' => 'Cat '.$visibility.' '.uniqid(),
            'slug' => 'cat-'.$visibility.'-'.uniqid(),
            'visibility' => $visibility,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return Document::create([
            'title' => 'Doc '.$visibility,
            'category_id' => $category->id,
            'file_path' => 'placeholder.pdf',
            'status' => 1,
        ]);
    }
}
