<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Banding;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminBandingDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        foreach (['banding.review', 'banding.decide'] as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
    }

    private function createPesantrenUser(string $pesantrenName = 'Pesantren Test'): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => $pesantrenName,
        ]);

        return $user;
    }

    private function setupDataCompleteness(User $user): void
    {
        // Create IPM with required files
        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'test.pdf',
            'lulus_santri_file' => 'test.pdf',
            'kurikulum_file' => 'test.pdf',
            'buku_ajar_file' => 'test.pdf',
        ]);

        // Create SDM
        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'aliyah',
            'santri_l' => 10,
            'santri_p' => 10,
        ]);

        // Create EDPM - ensure all butirs are filled
        $komponen = MasterEdpmKomponen::create(['nama' => 'Test Komponen']);
        $butir = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => 'SK-001',
            'nomor_butir' => '1',
            'butir_pernyataan' => 'Test Butir',
        ]);
        Edpm::create([
            'user_id' => $user->id,
            'butir_id' => $butir->id,
            'isian' => 'A',
        ]);
    }

    /**
     * Task 9.7: Assign reviewer updates banding and shows under_review status
     */
    public function test_assign_reviewer_updates_banding_status(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $reviewer = User::factory()->create(['role_id' => 1, 'name' => 'Reviewer Admin']);
        $pesantrenUser = $this->createPesantrenUser('Pesantren Banding');

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'pending',
            'alasan' => 'Saya tidak setuju dengan hasil penilaian yang diberikan.',
        ]);

        Volt::actingAs($admin);

        $component = Volt::test('pages.admin.banding-detail', ['id' => $banding->id])
            ->assertOk()
            ->assertSee('Tertunda')
            ->assertSee('Assign Reviewer');

        $component
            ->call('openAssignModal')
            ->assertSet('showAssignModal', true)
            ->set('selectedReviewerId', $reviewer->id)
            ->assertSet('selectedReviewerId', $reviewer->id)
            ->call('assignReviewer')
            ->assertOk()
            ->assertHasNoErrors();

        // Verify banding status changed
        $banding->refresh();
        $this->assertEquals('under_review', $banding->status);
        $this->assertEquals($reviewer->id, $banding->reviewer_id);

        // Verify the component shows updated status
        $component->assertSee('Dalam Peninjauan');
    }

    /**
     * Task 9.8: Accept decision returns akreditasi to final admin validation.
     */
    public function test_accept_decision_returns_to_validasi_admin(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createPesantrenUser('Pesantren Accept');
        $this->setupDataCompleteness($pesantrenUser);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -2,
        ]);

        // Create an assessment so the akreditasi looks valid
        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Test, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(30),
            'tanggal_berakhir' => now()->subDays(15),
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'reviewer_id' => $admin->id,
            'status' => 'under_review',
            'alasan' => 'Saya tidak setuju dengan hasil penilaian.',
            'review_deadline' => now()->addDays(14),
        ]);

        Volt::actingAs($admin);

        $component = Volt::test('pages.admin.banding-detail', ['id' => $banding->id])
            ->assertSee('Terima Banding')
            ->assertSee('Tolak Banding')
            ->call('openDecisionModal', 'accept')
            ->set('keputusan', 'Banding diterima karena ada bukti yang valid dan perlu dievaluasi ulang.')
            ->set('decisionType', 'accept')
            ->call('submitDecision');

        // Verify banding status changed to accepted
        $banding->refresh();
        $this->assertEquals('accepted', $banding->status);
        $this->assertNotNull($banding->decided_at);

        // Verify akreditasi returns to Validasi Akhir Admin.
        $akreditasi->refresh();
        $this->assertEquals(1, $akreditasi->status);
        $this->assertSame(1, Akreditasi::where('id', $akreditasi->id)->count());

        // Verify the component shows accepted status
        $component->assertSee('Diterima');
    }

    /**
     * Task 9.9: Reject decision reverts akreditasi and shows rejected status
     */
    public function test_reject_decision_reverts_akreditasi(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createPesantrenUser('Pesantren Reject');

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'reviewer_id' => $admin->id,
            'status' => 'under_review',
            'alasan' => 'Saya tidak setuju dengan hasil penilaian.',
            'review_deadline' => now()->addDays(14),
        ]);

        Volt::actingAs($admin);

        $component = Volt::test('pages.admin.banding-detail', ['id' => $banding->id])
            ->assertSee('Terima Banding')
            ->assertSee('Tolak Banding')
            ->call('openDecisionModal', 'reject')
            ->set('keputusan', 'Banding ditolak karena tidak ada bukti yang cukup untuk mendukung klaim.')
            ->set('decisionType', 'reject')
            ->call('submitDecision');

        // Verify banding status changed to rejected
        $banding->refresh();
        $this->assertEquals('rejected', $banding->status);
        $this->assertNotNull($banding->decided_at);

        // Verify akreditasi remains rejected.
        $akreditasi->refresh();
        $this->assertEquals(-1, $akreditasi->status);

        // Verify the component shows rejected status
        $component->assertSee('Ditolak');
    }

    /**
     * Task 9.10: Decision actions hidden when status is not under_review
     */
    public function test_decision_actions_hidden_when_not_under_review(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createPesantrenUser('Pesantren Hidden');

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 3,
        ]);

        // Test with pending status - should show Assign but not Accept/Reject
        $pendingBanding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'pending',
            'alasan' => 'Alasan banding pending.',
        ]);

        Volt::actingAs($admin);

        Volt::test('pages.admin.banding-detail', ['id' => $pendingBanding->id])
            ->assertSee('Assign Reviewer')
            ->assertDontSee('Terima Banding')
            ->assertDontSee('Tolak Banding');

        // Test with accepted status - should not show any action buttons
        $acceptedBanding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'accepted',
            'alasan' => 'Alasan banding accepted.',
            'keputusan' => 'Diterima karena valid.',
            'decided_at' => now(),
        ]);

        Volt::test('pages.admin.banding-detail', ['id' => $acceptedBanding->id])
            ->assertDontSee('Assign Reviewer')
            ->assertDontSee('Reassign Reviewer')
            ->assertSee('Banding ini sudah diputuskan.');

        // Test with rejected status - should not show any action buttons
        $rejectedBanding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'rejected',
            'alasan' => 'Alasan banding rejected.',
            'keputusan' => 'Ditolak karena tidak valid.',
            'decided_at' => now(),
        ]);

        Volt::test('pages.admin.banding-detail', ['id' => $rejectedBanding->id])
            ->assertDontSee('Assign Reviewer')
            ->assertDontSee('Reassign Reviewer')
            ->assertSee('Banding ini sudah diputuskan.');
    }

    /**
     * Additional: Non-admin cannot access banding detail
     */
    public function test_non_admin_cannot_access_banding_detail(): void
    {
        $pesantrenUser = $this->createPesantrenUser('Pesantren Unauthorized');

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 3,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'pending',
            'alasan' => 'Alasan banding.',
        ]);

        Volt::actingAs($pesantrenUser);

        $response = $this->get('/admin/banding/'.$banding->id);
        $response->assertStatus(403);
    }
}
