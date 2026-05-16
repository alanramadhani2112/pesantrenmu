<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Banding;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PesantrenBandingVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    /**
     * Task 10.7: Pesantren sees banding status on akreditasi detail
     */
    public function test_pesantren_sees_banding_status_on_akreditasi_detail(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        config(['akreditasi.banding_limit' => 1]);

        // Create a rejected akreditasi that has been appealed (status 3 = Validasi)
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
            'catatan' => 'Data tidak lengkap',
            'parent' => null,
        ]);

        // Create a banding record for this akreditasi
        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'alasan' => 'Saya merasa penilaian tidak adil karena data sudah lengkap',
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid])
            ->set('activeTab', 'hasil');

        // Verify banding status section is visible
        $component->assertSee('Status Banding')
            ->assertSee('Menunggu')
            ->assertSee('Saya merasa penilaian tidak adil karena data sudah lengkap')
            ->assertSee('Sisa kesempatan banding:');

        // Verify the bandingStatus property is loaded
        $this->assertNotNull($component->get('bandingStatus'));
        $this->assertEquals('pending', $component->get('bandingStatus')->status);
    }

    /**
     * Task 10.7: Pesantren sees banding under_review status
     */
    public function test_pesantren_sees_banding_under_review_status(): void
    {
        $user = $this->createCompletePesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);
        $this->actingAs($user);

        config(['akreditasi.banding_limit' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
            'catatan' => 'Data tidak lengkap',
            'parent' => null,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Penilaian tidak sesuai dengan fakta di lapangan',
            'review_deadline' => now()->addDays(14),
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid])
            ->set('activeTab', 'hasil');

        $component->assertSee('Status Banding')
            ->assertSee('Sedang Direview')
            ->assertSee('Penilaian tidak sesuai dengan fakta di lapangan');
    }

    /**
     * Task 10.7: Pesantren sees banding decision when accepted
     */
    public function test_pesantren_sees_banding_accepted_decision(): void
    {
        $user = $this->createCompletePesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);
        $this->actingAs($user);

        config(['akreditasi.banding_limit' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'catatan' => 'Data tidak lengkap',
            'parent' => null,
        ]);

        // Create accepted banding
        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'accepted',
            'alasan' => 'Penilaian tidak adil',
            'keputusan' => 'Setelah ditinjau ulang, banding diterima karena ada bukti tambahan yang valid.',
            'decided_at' => now(),
        ]);

        // Create the new akreditasi that resulted from the accepted banding
        $newAkreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 6,
            'parent' => $akreditasi->id,
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid])
            ->set('activeTab', 'hasil');

        $component->assertSee('Status Banding')
            ->assertSee('Diterima')
            ->assertSee('Setelah ditinjau ulang, banding diterima karena ada bukti tambahan yang valid.')
            ->assertSee('Lihat Pengajuan Baru');
    }

    /**
     * Task 10.7: Pesantren sees banding decision when rejected
     */
    public function test_pesantren_sees_banding_rejected_decision(): void
    {
        $user = $this->createCompletePesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);
        $this->actingAs($user);

        config(['akreditasi.banding_limit' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'catatan' => 'Data tidak lengkap',
            'parent' => null,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'rejected',
            'alasan' => 'Penilaian tidak adil',
            'keputusan' => 'Banding ditolak karena tidak ada bukti baru yang mendukung klaim pesantren.',
            'decided_at' => now(),
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid])
            ->set('activeTab', 'hasil');

        $component->assertSee('Status Banding')
            ->assertSee('Ditolak')
            ->assertSee('Banding ditolak karena tidak ada bukti baru yang mendukung klaim pesantren.');
    }

    /**
     * Task 10.7: Pesantren sees remaining appeal count
     */
    public function test_pesantren_sees_remaining_appeal_count(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        config(['akreditasi.banding_limit' => 2]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'catatan' => 'Data tidak lengkap',
            'parent' => null,
        ]);

        // Create one banding (so remaining = 1 out of limit 2)
        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'rejected',
            'alasan' => 'Banding pertama',
            'keputusan' => 'Ditolak karena alasan tidak cukup kuat untuk diterima.',
            'decided_at' => now(),
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid])
            ->set('activeTab', 'hasil');

        // Should show remaining count: 1/2
        $component->assertSee('Sisa kesempatan banding:')
            ->assertSee('1/2');

        // Verify eligibility data
        $this->assertEquals(1, $component->get('bandingEligibility')['remaining']);
        $this->assertTrue($component->get('bandingEligibility')['allowed']);
    }

    /**
     * Task 10.8: Banding button disabled when limit reached
     */
    public function test_banding_button_disabled_when_limit_reached(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        config(['akreditasi.banding_limit' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'catatan' => 'Data tidak lengkap',
            'parent' => null,
        ]);

        // Create one banding (limit = 1, so no more allowed)
        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'rejected',
            'alasan' => 'Banding pertama',
            'keputusan' => 'Ditolak karena alasan tidak cukup kuat untuk diterima.',
            'decided_at' => now(),
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid])
            ->set('activeTab', 'hasil');

        // Should show limit reached message
        $component->assertSee('Batas pengajuan banding telah tercapai')
            ->assertSee('0/1');

        // Verify eligibility data
        $this->assertFalse($component->get('bandingEligibility')['allowed']);
        $this->assertEquals(0, $component->get('bandingEligibility')['remaining']);
    }

    /**
     * Task 10.8: Banding button enabled when limit not reached
     */
    public function test_banding_button_enabled_when_limit_not_reached(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        config(['akreditasi.banding_limit' => 2]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'catatan' => 'Data tidak lengkap',
            'parent' => null,
        ]);

        // No banding yet, limit is 2
        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid])
            ->set('activeTab', 'hasil');

        // Should show remaining count and button should be enabled
        $component->assertSee('Sisa kesempatan banding:')
            ->assertSee('2/2')
            ->assertSee('Ajukan Banding')
            ->assertDontSee('Batas pengajuan banding telah tercapai');

        // Verify eligibility data
        $this->assertTrue($component->get('bandingEligibility')['allowed']);
        $this->assertEquals(2, $component->get('bandingEligibility')['remaining']);
    }

    private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren TDD',
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
