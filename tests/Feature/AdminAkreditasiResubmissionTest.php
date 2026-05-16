<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminAkreditasiResubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Task 7.6: Admin detail shows chain timeline with correct entries
     */
    public function test_admin_detail_shows_chain_timeline_with_correct_entries(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createCompletePesantrenUser();

        config(['akreditasi.resubmission_limit' => 3]);
        config(['akreditasi.cooling_period_days' => 0]);

        // Create a chain: root -> child1 -> child2
        $root = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 2,
            'catatan' => 'Ditolak pertama',
            'parent' => null,
        ]);

        $child1 = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 2,
            'catatan' => 'Ditolak kedua',
            'parent' => $root->id,
        ]);

        $child2 = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
            'catatan' => null,
            'parent' => $child1->id,
        ]);

        $this->actingAs($admin);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $child2->uuid]);

        // Verify chain timeline is loaded
        $chainTimeline = $component->get('chainTimeline');
        $this->assertCount(3, $chainTimeline);

        // Verify resubmission status is loaded
        $resubmissionStatus = $component->get('resubmissionStatus');
        $this->assertNotNull($resubmissionStatus);
        $this->assertEquals(2, $resubmissionStatus['count']);
        $this->assertEquals(3, $resubmissionStatus['limit']);

        // Switch to riwayat tab and verify content
        $component->call('setTab', 'riwayat')
            ->assertSee('Riwayat Pengajuan')
            ->assertSee('Ditolak pertama')
            ->assertSee('Ditolak kedua')
            ->assertSee(Akreditasi::getStatusLabel(2))
            ->assertSee(Akreditasi::getStatusLabel(6));
    }

    /**
     * Task 7.6 (additional): Admin detail shows resubmission count badge
     */
    public function test_admin_detail_shows_resubmission_count_badge(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createCompletePesantrenUser();

        config(['akreditasi.resubmission_limit' => 3]);
        config(['akreditasi.cooling_period_days' => 0]);

        // Create a chain: root -> child1
        $root = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 2,
            'catatan' => 'Ditolak',
            'parent' => null,
        ]);

        $child1 = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
            'catatan' => null,
            'parent' => $root->id,
        ]);

        $this->actingAs($admin);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $child1->uuid])
            ->assertSee('Pengajuan Ulang:')
            ->assertSee('1/3');
    }

    /**
     * Task 7.6 (additional): Admin detail does NOT show chain timeline for standalone akreditasi
     */
    public function test_admin_detail_does_not_show_timeline_for_standalone_akreditasi(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createCompletePesantrenUser();

        // Create a standalone akreditasi (no parent, no children)
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
            'catatan' => null,
            'parent' => null,
        ]);

        $this->actingAs($admin);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // Verify chain timeline is empty
        $chainTimeline = $component->get('chainTimeline');
        $this->assertEmpty($chainTimeline);

        // Verify resubmission status is null
        $this->assertNull($component->get('resubmissionStatus'));
    }

    /**
     * Task 7.7: Admin list shows resubmission badge for chain members
     */
    public function test_admin_list_shows_resubmission_badge_for_chain_members(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createCompletePesantrenUser();

        // Create a chain: root (rejected) -> child (pengajuan)
        $root = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 2,
            'catatan' => 'Ditolak',
            'parent' => null,
        ]);

        $child = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
            'catatan' => null,
            'parent' => $root->id,
        ]);

        $this->actingAs($admin);

        // The admin list with 'pengajuan' filter should show the child with badge
        $component = Volt::test('pages.admin.akreditasi', [])
            ->set('statusFilter', 'pengajuan')
            ->assertSee('Pengajuan Ulang');
    }

    /**
     * Task 7.7 (additional): Admin list does NOT show resubmission badge for root akreditasi
     */
    public function test_admin_list_does_not_show_resubmission_badge_for_root_akreditasi(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createCompletePesantrenUser();

        // Create a standalone akreditasi (no parent)
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
            'catatan' => null,
            'parent' => null,
        ]);

        $this->actingAs($admin);

        // The admin list should NOT show "Pengajuan Ulang" badge for root items
        $component = Volt::test('pages.admin.akreditasi', [])
            ->set('statusFilter', 'pengajuan')
            ->assertDontSee('Pengajuan Ulang');
    }

    private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Admin Test',
            'is_locked' => false,
        ]);

        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'ipm/nsp.pdf',
            'lulus_santri_file' => 'ipm/lulus.pdf',
            'kurikulum_file' => 'ipm/kurikulum.pdf',
            'buku_ajar_file' => 'ipm/buku-ajar.pdf',
        ]);

        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'spm',
        ]);

        $komponen = MasterEdpmKomponen::create(['nama' => 'Standar Isi']);
        $butir = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Pesantren memiliki dokumen kurikulum.',
        ]);

        Edpm::create([
            'user_id' => $user->id,
            'butir_id' => $butir->id,
            'isian' => '4',
        ]);

        return $user->refresh();
    }
}
