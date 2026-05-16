<?php

namespace Tests\Feature\Livewire;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
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

class AdminRejectionUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    /**
     * Task 16.4: Reject button hidden at status 6
     */
    public function test_reject_button_hidden_at_status_6(): void
    {
        $adminUser = User::factory()->create(['role_id' => 1]);
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // At status 6, the reject form should NOT be visible
        $component->assertDontSee('Tolak Akreditasi')
            ->assertDontSee('Tolak Pengajuan');
    }

    /**
     * Task 16.5: Structured final rejection form validates and submits
     */
    public function test_structured_final_rejection_form_validates_empty_categories(): void
    {
        [$adminUser, $akreditasi] = $this->createAdminWithStatus3Akreditasi();
        $this->actingAs($adminUser);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // Try to submit with empty categories
        $component->set('rejectionCategories', [])
            ->call('reject');

        $component->assertHasErrors(['rejectionCategories']);
    }

    public function test_structured_final_rejection_form_validates_short_explanation(): void
    {
        [$adminUser, $akreditasi] = $this->createAdminWithStatus3Akreditasi();
        $this->actingAs($adminUser);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        $component->set('rejectionCategories', [
            ['category' => 'nilai_tidak_memenuhi', 'explanation' => 'short'],
        ])->call('reject');

        $component->assertHasErrors(['rejectionCategories.0.explanation']);
    }

    public function test_structured_final_rejection_form_submits_successfully(): void
    {
        [$adminUser, $akreditasi] = $this->createAdminWithStatus3Akreditasi();
        $this->actingAs($adminUser);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        $component->set('rejectionCategories', [
            ['category' => 'nilai_tidak_memenuhi', 'explanation' => 'Nilai akhir di bawah standar minimum yang ditetapkan.'],
            ['category' => 'laporan_tidak_lengkap', 'explanation' => 'Laporan visitasi tidak mencakup semua komponen penilaian.'],
        ])->call('reject');

        // Verify rejection was created
        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $adminUser->id,
            'type' => 'admin_final',
            'status' => 'final',
        ]);

        // Verify akreditasi status changed to 2
        $akreditasi->refresh();
        $this->assertEquals(2, $akreditasi->status);

        // Verify redirect
        $component->assertRedirect(route('admin.akreditasi'));
    }

    public function test_admin_sees_rejection_history(): void
    {
        $adminUser = User::factory()->create(['role_id' => 1]);
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 5,
        ]);

        // Create an asesor user to use as the rejection creator
        $asesorUser = User::factory()->create(['role_id' => 2]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'items' => ['profil', 'sdm'],
            'explanation' => 'Data profil dan SDM perlu diperbaiki segera.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'status' => 'pending',
        ]);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        $component->assertSee('Riwayat Penolakan')
            ->assertSee('Penolakan Asesor #1');
    }

    public function test_admin_sees_final_rejection_detail(): void
    {
        $adminUser = User::factory()->create(['role_id' => 1]);
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 2,
            'catatan' => 'Ditolak',
        ]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $adminUser->id,
            'type' => 'admin_final',
            'categories' => [
                ['category' => 'nilai_tidak_memenuhi', 'explanation' => 'Nilai akhir di bawah standar minimum.'],
            ],
            'status' => 'final',
        ]);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        $component->assertSee('Detail Penolakan Final (Admin)')
            ->assertSee('Nilai Tidak Memenuhi Standar');
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

        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'MUTU LULUSAN']);
        $butir = MasterEdpmButir::firstOrCreate([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1.1',
        ], ['butir_pernyataan' => 'Pesantren memiliki dokumen kurikulum.']);

        Edpm::create([
            'user_id' => $user->id,
            'butir_id' => $butir->id,
            'isian' => '4',
        ]);

        return $user->refresh();
    }

    private function createAdminWithStatus3Akreditasi(): array
    {
        $adminUser = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createCompletePesantrenUser();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 3,
            'kartu_kendali' => 'akreditasi/kartu_kendali/test.pdf',
            'laporan_visitasi_file' => 'akreditasi/laporan/test.pdf',
        ]);

        // Create asesor with complete EDPM data for checkScores to pass
        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor',
            'nama_tanpa_gelar' => 'Asesor',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        // Create EDPM data for asesor (NK values needed for checkScores)
        $komponen = MasterEdpmKomponen::first();
        foreach ($komponen->butirs as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor->id,
                'butir_id' => $butir->id,
                'pesantren_id' => $pesantrenUser->id,
                'isian' => 3,
                'nk' => 3,
                'nv' => 3,
            ]);
        }

        return [$adminUser, $akreditasi];
    }
}
