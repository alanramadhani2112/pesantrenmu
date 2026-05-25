<?php

namespace Tests\Feature\Livewire;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\Asesor;
use App\Models\Assessment;
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

class PesantrenRejectionUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    /**
     * Task 14.8: Pesantren sees rejection history and status
     */
public function test_pesantren_sees_rejection_count_and_status(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        // Create an asesor and rejection
        $asesorUser = User::factory()->create(['role_id' => 2]);
        Asesor::create(['user_id' => $asesorUser->id, 'nama_dengan_gelar' => 'Dr. Test', 'nama_tanpa_gelar' => 'Test']);
        $asesor = Asesor::where('user_id', $asesorUser->id)->first();
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'items' => ['profil', 'ipm.kurikulum'],
            'explanation' => 'Data profil tidak lengkap dan kurikulum perlu diperbaiki.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'status' => 'pending',
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // Verify rejection status is loaded
        $rejectionStatus = $component->get('rejectionStatus');
        $this->assertEquals(1, $rejectionStatus['count']);
        $this->assertEquals(3, $rejectionStatus['limit']);
        $this->assertNotNull($rejectionStatus['active']);

        // Verify the page renders with rejection info
        $component->assertSee('Penolakan 1 dari 3')
            ->assertSee('Submit Perbaikan')
            ->assertSee('Perbaikan Diperlukan');
    }

    public function test_pesantren_sees_menunggu_review_when_submitted(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $asesorUser = User::factory()->create(['role_id' => 2]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Data profil tidak lengkap.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'perbaikan_submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        $component->assertSee('Menunggu Review')
            ->assertDontSee('Submit Perbaikan');
    }

    public function test_pesantren_sees_rejection_history(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $asesorUser = User::factory()->create(['role_id' => 2]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Data profil tidak lengkap dan perlu diperbaiki segera.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'perbaikan_submitted_at' => now()->subDays(2),
            'status' => 'accepted',
        ]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'items' => ['sdm'],
            'explanation' => 'Data SDM masih belum sesuai dengan ketentuan.',
            'rejection_number' => 2,
            'perbaikan_deadline' => now()->addDays(14),
            'status' => 'pending',
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        $rejectionStatus = $component->get('rejectionStatus');
        $this->assertEquals(2, $rejectionStatus['count']);
        $this->assertCount(2, $rejectionStatus['history']);

        $component->assertSee('Riwayat Penolakan')
            ->assertSee('Penolakan #1')
            ->assertSee('Penolakan #2');
    }

    public function test_pesantren_sees_admin_final_rejection_detail(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        $adminUser = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
            'catatan' => 'Ditolak',
        ]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $adminUser->id,
            'type' => 'admin_final',
            'categories' => [
                ['category' => 'nilai_tidak_memenuhi', 'explanation' => 'Nilai akhir di bawah standar minimum yang ditetapkan.'],
                ['category' => 'laporan_tidak_lengkap', 'explanation' => 'Laporan visitasi tidak mencakup semua komponen.'],
            ],
            'status' => 'final',
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        $component->assertSee('Detail Penolakan Final')
            ->assertSee('Nilai Tidak Memenuhi Standar')
            ->assertSee('Laporan Visitasi Tidak Lengkap');
    }

    public function test_pesantren_can_submit_perbaikan(): void
    {
        $user = $this->createCompletePesantrenUser();
        $this->actingAs($user);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $asesorUser = User::factory()->create(['role_id' => 2]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Data profil tidak lengkap dan perlu diperbaiki.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'status' => 'pending',
        ]);

        $component = Volt::test('pages.pesantren.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        $component->assertSee('Submit Perbaikan');

        $component->call('submitPerbaikan');

        // After submission, the rejection should be 'submitted'
        $rejectionStatus = $component->get('rejectionStatus');
        $this->assertNotNull($rejectionStatus['active']);
        $this->assertEquals('submitted', $rejectionStatus['active']->status);
    }

    private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Test',
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

        $komponen = MasterEdpmKomponen::create(['nama' => 'MUTU LULUSAN']);
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
