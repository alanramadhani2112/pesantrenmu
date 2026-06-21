<?php

namespace Tests\Unit;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Ipm;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\PesantrenService;
use App\Services\RejectionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RejectionServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    protected RejectionService $rejectionService;

    protected PesantrenService $pesantrenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->rejectionService = app(RejectionService::class);
        $this->pesantrenService = app(PesantrenService::class);
    }

    /**
     * Helper: create a pesantren user with locked pesantren and akreditasi at status 5.
     */
    private function createLockedPesantrenSetup(array $rejectedItems = ['profil']): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        $pesantren = Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test '.$pesantrenUser->id,
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 5,
        ]);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Test',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        // Create active rejection with specified items
        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesorUser->id,
            'type' => 'asesor',
            'items' => $rejectedItems,
            'explanation' => 'Data perlu diperbaiki sesuai catatan',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'status' => 'pending',
        ]);

        return [
            'pesantrenUser' => $pesantrenUser,
            'pesantren' => $pesantren,
            'akreditasi' => $akreditasi,
            'asesorUser' => $asesorUser,
            'rejection' => $rejection,
        ];
    }

    // =========================================================================
    // Task 13.6: Profile editing allowed only when 'profil' is in rejected items
    // =========================================================================
    #[Test]
    public function profile_editing_allowed_when_profil_is_in_rejected_items(): void
    {
        $setup = $this->createLockedPesantrenSetup(['profil']);

        $isUnlocked = $this->rejectionService->isSectionUnlocked(
            $setup['akreditasi']->id,
            'profil'
        );

        $this->assertTrue($isUnlocked);
    }

    #[Test]
    public function profile_editing_blocked_when_profil_is_not_in_rejected_items(): void
    {
        $setup = $this->createLockedPesantrenSetup(['ipm.nsp', 'sdm']);

        $isUnlocked = $this->rejectionService->isSectionUnlocked(
            $setup['akreditasi']->id,
            'profil'
        );

        $this->assertFalse($isUnlocked);
    }

    #[Test]
    public function profile_editing_blocked_when_no_active_rejection(): void
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren No Rejection',
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 5,
        ]);

        $isUnlocked = $this->rejectionService->isSectionUnlocked(
            $akreditasi->id,
            'profil'
        );

        $this->assertFalse($isUnlocked);
    }

    #[Test]
    public function profile_update_service_allows_when_profil_unlocked(): void
    {
        $setup = $this->createLockedPesantrenSetup(['profil']);

        $result = $this->pesantrenService->updateProfile(
            $setup['pesantrenUser']->id,
            ['nama_pesantren' => 'Pesantren Updated Name']
        );

        $this->assertTrue($result);
        $setup['pesantren']->refresh();
        $this->assertEquals('Pesantren Updated Name', $setup['pesantren']->nama_pesantren);
    }

    #[Test]
    public function profile_update_service_allows_during_assessment_phase(): void
    {
        // During status 5 (Assessment Awal), all sections are editable regardless of rejection
        $setup = $this->createLockedPesantrenSetup(['ipm.nsp']);

        $result = $this->pesantrenService->updateProfile(
            $setup['pesantrenUser']->id,
            ['nama_pesantren' => 'Updated During Assessment']
        );

        $this->assertTrue($result);
        $setup['pesantren']->refresh();
        $this->assertEquals('Updated During Assessment', $setup['pesantren']->nama_pesantren);
    }

    #[Test]
    public function profile_update_service_allows_when_pesantren_not_locked(): void
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Unlocked',
            'is_locked' => false,
        ]);

        $result = $this->pesantrenService->updateProfile(
            $pesantrenUser->id,
            ['nama_pesantren' => 'Updated Freely']
        );

        $this->assertTrue($result);
    }

    // =========================================================================
    // Task 13.7: IPM sub-item granular unlock works correctly
    // =========================================================================
    #[Test]
    public function ipm_nsp_unlocked_when_in_rejected_items(): void
    {
        $setup = $this->createLockedPesantrenSetup(['ipm.nsp']);

        $this->assertTrue($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'ipm.nsp'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'ipm.kurikulum'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'ipm.buku_ajar'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'ipm.lulus_santri'));
    }

    #[Test]
    public function ipm_multiple_sub_items_unlocked(): void
    {
        $setup = $this->createLockedPesantrenSetup(['ipm.kurikulum', 'ipm.buku_ajar']);

        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'ipm.nsp'));
        $this->assertTrue($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'ipm.kurikulum'));
        $this->assertTrue($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'ipm.buku_ajar'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'ipm.lulus_santri'));
    }

    #[Test]
    public function ipm_update_service_saves_all_during_assessment(): void
    {
        // During status 5 (Assessment Awal), ALL IPM items are saved regardless of rejection filter
        $setup = $this->createLockedPesantrenSetup(['ipm.nsp', 'ipm.kurikulum']);

        // Create IPM record for the user
        Ipm::create(['user_id' => $setup['pesantrenUser']->id]);

        $result = $this->pesantrenService->updateIpm(
            $setup['pesantrenUser']->id,
            [
                'nsp_file' => 'ipm_docs/new_nsp.pdf',
                'kurikulum_file' => 'ipm_docs/new_kurikulum.pdf',
                'buku_ajar_file' => 'ipm_docs/new_buku_ajar.pdf',
                'lulus_santri_file' => 'ipm_docs/new_lulus_santri.pdf',
            ]
        );

        $this->assertTrue($result);

        // During Assessment, ALL items are saved
        $ipm = Ipm::where('user_id', $setup['pesantrenUser']->id)->first();
        $this->assertEquals('ipm_docs/new_nsp.pdf', $ipm->nsp_file);
        $this->assertEquals('ipm_docs/new_kurikulum.pdf', $ipm->kurikulum_file);
        $this->assertEquals('ipm_docs/new_buku_ajar.pdf', $ipm->buku_ajar_file);
        $this->assertEquals('ipm_docs/new_lulus_santri.pdf', $ipm->lulus_santri_file);
    }

    #[Test]
    public function ipm_update_service_allows_all_during_assessment_regardless_of_rejection(): void
    {
        // During status 5 (Assessment Awal), ALL sections are editable regardless of which sections were rejected
        $setup = $this->createLockedPesantrenSetup(['profil', 'sdm']);

        // Create IPM record
        Ipm::create(['user_id' => $setup['pesantrenUser']->id]);

        $result = $this->pesantrenService->updateIpm(
            $setup['pesantrenUser']->id,
            ['nsp_file' => 'ipm_docs/updated_during_assessment.pdf']
        );

        $this->assertTrue($result);
    }

    #[Test]
    public function ipm_update_service_allows_all_when_pesantren_not_locked(): void
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Unlocked',
            'is_locked' => false,
        ]);
        Ipm::create(['user_id' => $pesantrenUser->id]);

        $result = $this->pesantrenService->updateIpm(
            $pesantrenUser->id,
            [
                'nsp_file' => 'ipm_docs/free_nsp.pdf',
                'kurikulum_file' => 'ipm_docs/free_kurikulum.pdf',
            ]
        );

        $this->assertTrue($result);

        $ipm = Ipm::where('user_id', $pesantrenUser->id)->first();
        $this->assertEquals('ipm_docs/free_nsp.pdf', $ipm->nsp_file);
        $this->assertEquals('ipm_docs/free_kurikulum.pdf', $ipm->kurikulum_file);
    }

    // =========================================================================
    // Task 13.8: EDPM butir-level granular unlock works correctly
    // =========================================================================
    #[Test]
    public function edpm_butir_unlocked_when_in_rejected_items(): void
    {
        $setup = $this->createLockedPesantrenSetup(['edpm.butir.3', 'edpm.butir.7']);

        $this->assertTrue($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'edpm.butir.3'));
        $this->assertTrue($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'edpm.butir.7'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'edpm.butir.1'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'edpm.butir.5'));
    }

    #[Test]
    public function edpm_butir_not_unlocked_when_other_sections_rejected(): void
    {
        $setup = $this->createLockedPesantrenSetup(['profil', 'ipm.nsp', 'sdm']);

        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'edpm.butir.1'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'edpm.butir.3'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'edpm.butir.99'));
    }

    #[Test]
    public function edpm_mixed_sections_with_butir_unlock(): void
    {
        $setup = $this->createLockedPesantrenSetup(['profil', 'edpm.butir.5', 'ipm.kurikulum']);

        $this->assertTrue($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'profil'));
        $this->assertTrue($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'edpm.butir.5'));
        $this->assertTrue($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'ipm.kurikulum'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'sdm'));
        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'edpm.butir.1'));
    }

    #[Test]
    public function edpm_unlock_not_active_after_perbaikan_submitted(): void
    {
        $setup = $this->createLockedPesantrenSetup(['edpm.butir.3']);

        // Simulate perbaikan submitted — rejection status changes to 'submitted'
        $setup['rejection']->update(['status' => 'submitted', 'perbaikan_submitted_at' => now()]);

        // After submission, no sections should be unlocked
        $this->assertFalse($this->rejectionService->isSectionUnlocked($setup['akreditasi']->id, 'edpm.butir.3'));
    }

    #[Test]
    public function get_unlocked_sections_returns_all_rejected_items(): void
    {
        $items = ['profil', 'ipm.nsp', 'edpm.butir.3', 'edpm.butir.7'];
        $setup = $this->createLockedPesantrenSetup($items);

        $unlocked = $this->rejectionService->getUnlockedSections($setup['akreditasi']->id);

        $this->assertEquals($items, $unlocked);
    }

    #[Test]
    public function get_unlocked_sections_returns_empty_when_no_active_rejection(): void
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren No Rejection',
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 5,
        ]);

        $unlocked = $this->rejectionService->getUnlockedSections($akreditasi->id);

        $this->assertEmpty($unlocked);
    }
}
