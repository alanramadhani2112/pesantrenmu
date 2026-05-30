<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Pages\Asesor\AkreditasiDetail;
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
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AsesorRejectionUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        Notification::fake();
    }

    /**
     * Task 15.5: Rejection form validates and submits correctly
     */
    public function test_asesor1_can_submit_structured_rejection(): void
    {
        [$asesorUser, $akreditasi] = $this->createAsesor1WithAkreditasi();
        $this->actingAs($asesorUser);

        $component = Livewire::test(AkreditasiDetail::class, ['uuid' => $akreditasi->uuid]);

        // Verify rejection form data is loaded
        $this->assertNotEmpty($component->get('selectableItems'));
        $rejectionStatus = $component->get('rejectionStatus');
        $this->assertEquals(0, $rejectionStatus['count']);
        $this->assertEquals(3, $rejectionStatus['limit']);

        // Submit rejection
        $component->set('rejectedItems', ['profil', 'ipm.kurikulum'])
            ->set('rejectionExplanation', 'Data profil tidak lengkap dan kurikulum perlu diperbaiki.')
            ->call('submitRejection');

        // Verify rejection was created
        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'status' => 'pending',
            'rejection_number' => 1,
        ]);

        // Verify rejection status updated
        $rejectionStatus = $component->get('rejectionStatus');
        $this->assertEquals(1, $rejectionStatus['count']);
    }

    public function test_asesor1_rejection_form_validates_empty_items(): void
    {
        [$asesorUser, $akreditasi] = $this->createAsesor1WithAkreditasi();
        $this->actingAs($asesorUser);

        $component = Livewire::test(AkreditasiDetail::class, ['uuid' => $akreditasi->uuid]);

        $component->set('rejectedItems', [])
            ->set('rejectionExplanation', 'Some explanation text here.')
            ->call('submitRejection');

        $component->assertHasErrors(['rejectedItems']);
    }

    public function test_asesor1_rejection_form_validates_short_explanation(): void
    {
        [$asesorUser, $akreditasi] = $this->createAsesor1WithAkreditasi();
        $this->actingAs($asesorUser);

        $component = Livewire::test(AkreditasiDetail::class, ['uuid' => $akreditasi->uuid]);

        $component->set('rejectedItems', ['profil'])
            ->set('rejectionExplanation', 'short')
            ->call('submitRejection');

        $component->assertHasErrors(['rejectionExplanation']);
    }

    /**
     * Task 15.6: Accept/reject options appear after perbaikan submission
     */
    public function test_asesor1_sees_accept_reject_options_after_perbaikan(): void
    {
        [$asesorUser, $akreditasi] = $this->createAsesor1WithAkreditasi();
        $this->actingAs($asesorUser);

        // Create a submitted rejection
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'items' => ['profil', 'sdm'],
            'explanation' => 'Data profil dan SDM perlu diperbaiki.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'perbaikan_submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $component = Livewire::test(AkreditasiDetail::class, ['uuid' => $akreditasi->uuid]);

        $component->assertSee('Perbaikan Telah Dikirim')
            ->assertSee('Terima Perbaikan')
            ->assertSee('Tolak Lagi');
    }

    public function test_asesor1_can_accept_perbaikan(): void
    {
        [$asesorUser, $akreditasi] = $this->createAsesor1WithAkreditasi();
        $this->actingAs($asesorUser);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Data profil tidak lengkap dan perlu diperbaiki.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'perbaikan_submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $component = Livewire::test(AkreditasiDetail::class, ['uuid' => $akreditasi->uuid]);

        $component->call('acceptPerbaikan');

        $this->assertDatabaseHas('akreditasi_rejections', [
            'akreditasi_id' => $akreditasi->id,
            'status' => 'accepted',
        ]);
    }

    public function test_asesor1_sees_rejection_count_and_remaining(): void
    {
        [$asesorUser, $akreditasi] = $this->createAsesor1WithAkreditasi();
        $this->actingAs($asesorUser);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Data profil tidak lengkap dan perlu diperbaiki.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'perbaikan_submitted_at' => now()->subDay(),
            'status' => 'accepted',
        ]);

        $component = Livewire::test(AkreditasiDetail::class, ['uuid' => $akreditasi->uuid]);

        $component->assertSee('1 dari 3')
            ->assertSee('Sisa 2 kesempatan');
    }

    public function test_asesor1_sees_rejection_history(): void
    {
        [$asesorUser, $akreditasi] = $this->createAsesor1WithAkreditasi();
        $this->actingAs($asesorUser);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Data profil tidak lengkap dan perlu diperbaiki.',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'perbaikan_submitted_at' => now()->subDay(),
            'status' => 'accepted',
        ]);

        $component = Livewire::test(AkreditasiDetail::class, ['uuid' => $akreditasi->uuid]);

        $component->assertSee('Riwayat Penolakan')
            ->assertSee('Penolakan #1')
            ->assertSee('Diterima');
    }

    public function test_asesor_detail_rejection_modal_and_score_tables_use_safe_metronic_markup(): void
    {
        [$asesorUser, $akreditasi] = $this->createAsesor1WithAkreditasi();
        $this->actingAs($asesorUser);

        $component = Livewire::test(AkreditasiDetail::class, ['uuid' => $akreditasi->uuid])
            ->assertSee('Tolak Dokumen')
            ->assertSee('asesor-reject-documents-modal', false)
            ->assertSee('data-ui-modal="metronic"', false)
            ->assertDontSee('Form Penolakan Dokumen');

        $component
            ->call('setTab', 'edpm_pesantren')
            ->assertSee('data-ui-simple-table="metronic"', false)
            ->assertSee('spm-edpm-review-table', false)
            ->assertSee('Catatan Komponen');

        $component
            ->call('setTab', 'instrumen')
            ->assertSee('Delta')
            ->assertDontSee('x-transition="x-transition"', false);
    }

    public function test_asesor_detail_delta_display_uses_absolute_difference(): void
    {
        $view = file_get_contents(resource_path('views/livewire/pages/asesor/akreditasi-detail/tabs/instrumen/score-table.blade.php'));
        $component = file_get_contents(app_path('Livewire/Pages/Asesor/AkreditasiDetail.php'));

        $this->assertStringContainsString('asesorDeltaValue($butir->id)', $view);
        $this->assertStringContainsString('calculateDelta((int) $na1Value, (int) $na2Value)', $component);
        $this->assertStringNotContainsString('abs((int) $na1Value - (int) $na2Value)', $view);
        $this->assertStringNotContainsString('? ((int) $na1Value - (int) $na2Value)', $view);
    }

    private function createAsesor1WithAkreditasi(): array
    {
        // Create pesantren user with complete data
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test',
            'is_locked' => true,
        ]);
        Ipm::create([
            'user_id' => $pesantrenUser->id,
            'nsp_file' => 'ipm/nsp.pdf',
            'lulus_santri_file' => 'ipm/lulus.pdf',
            'kurikulum_file' => 'ipm/kurikulum.pdf',
            'buku_ajar_file' => 'ipm/buku-ajar.pdf',
        ]);
        SdmPesantren::create([
            'user_id' => $pesantrenUser->id,
            'tingkat' => 'spm',
        ]);
        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'MUTU LULUSAN']);
        $butir = MasterEdpmButir::firstOrCreate([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1.1',
        ], ['butir_pernyataan' => 'Pesantren memiliki dokumen kurikulum.']);
        Edpm::create([
            'user_id' => $pesantrenUser->id,
            'butir_id' => $butir->id,
            'isian' => '4',
        ]);

        // Create akreditasi at assessment status
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_ASSESSMENT,
        ]);

        // Create asesor user
        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Test',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        // Assign as Asesor 1
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        return [$asesorUser, $akreditasi];
    }
}
