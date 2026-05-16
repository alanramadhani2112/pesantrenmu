<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Banding;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Edpm;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminBandingDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
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
            'status' => 3,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'pending',
            'alasan' => 'Saya tidak setuju dengan hasil penilaian yang diberikan.',
        ]);

        $this->actingAs($admin);

        $component = Volt::test('pages.admin.banding-detail', ['id' => $banding->id])
            ->assertSee('Pending')
            ->assertSee('Assign Reviewer')
            ->set('selectedReviewerId', $reviewer->id)
            ->call('assignReviewer');

        // Verify banding status changed
        $banding->refresh();
        $this->assertEquals('under_review', $banding->status);
        $this->assertEquals($reviewer->id, $banding->reviewer_id);

        // Verify the component shows updated status
        $component->assertSee('Under Review');
    }

    /**
     * Task 9.8: Accept decision creates new akreditasi and shows accepted status
     */
    public function test_accept_decision_creates_new_akreditasi(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createPesantrenUser('Pesantren Accept');
        $this->setupDataCompleteness($pesantrenUser);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 3,
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

        $this->actingAs($admin);

        $component = Volt::test('pages.admin.banding-detail', ['id' => $banding->id])
            ->assertSee('Accept')
            ->assertSee('Reject')
            ->set('keputusan', 'Banding diterima karena ada bukti yang valid dan perlu dievaluasi ulang.')
            ->set('decisionType', 'accept')
            ->set('showDecisionModal', true)
            ->call('submitDecision');

        // Verify banding status changed to accepted
        $banding->refresh();
        $this->assertEquals('accepted', $banding->status);
        $this->assertNotNull($banding->decided_at);

        // Verify new akreditasi was created
        $newAkreditasi = Akreditasi::where('parent', $akreditasi->id)->first();
        $this->assertNotNull($newAkreditasi);
        $this->assertEquals(6, $newAkreditasi->status);

        // Verify the component shows accepted status
        $component->assertSee('Accepted');
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
            'status' => 3,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'reviewer_id' => $admin->id,
            'status' => 'under_review',
            'alasan' => 'Saya tidak setuju dengan hasil penilaian.',
            'review_deadline' => now()->addDays(14),
        ]);

        $this->actingAs($admin);

        $component = Volt::test('pages.admin.banding-detail', ['id' => $banding->id])
            ->assertSee('Accept')
            ->assertSee('Reject')
            ->set('keputusan', 'Banding ditolak karena tidak ada bukti yang cukup untuk mendukung klaim.')
            ->set('decisionType', 'reject')
            ->set('showDecisionModal', true)
            ->call('submitDecision');

        // Verify banding status changed to rejected
        $banding->refresh();
        $this->assertEquals('rejected', $banding->status);
        $this->assertNotNull($banding->decided_at);

        // Verify akreditasi reverted to status 2
        $akreditasi->refresh();
        $this->assertEquals(2, $akreditasi->status);

        // Verify the component shows rejected status
        $component->assertSee('Rejected');
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

        $this->actingAs($admin);

        Volt::test('pages.admin.banding-detail', ['id' => $pendingBanding->id])
            ->assertSee('Assign Reviewer')
            ->assertDontSee('Accept')
            ->assertDontSee('Reject');

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

        $this->actingAs($pesantrenUser);

        $response = $this->get('/admin/banding/' . $banding->id);
        $response->assertStatus(403);
    }
}
