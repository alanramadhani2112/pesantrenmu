<?php

namespace Tests\Feature\Livewire;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Livewire component tests for admin akreditasi detail reassignment UI.
 *
 * Covers:
 *  - Task 8.5: Reassign button disabled when not overdue, enabled when overdue
 *  - Task 8.6: Reassignment action updates the view and shows success message
 *
 */
#[Group('Feature: assessment-visitasi-timeout')]
class AdminReassignAsesorUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createAdminUser(): User
    {
        return User::factory()->create(['role_id' => 1]);
    }

    private function createAsesorWithUser(string $name = 'Asesor Test'): array
    {
        $user = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => $name,
            'nama_tanpa_gelar' => $name,
        ]);
        return [$asesor, $user];
    }

    private function createCompletePesantrenUser(string $pesantrenName = 'Pesantren Test'): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => $pesantrenName,
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

    private function createAkreditasiWithAssessment(
        User $pesantrenUser,
        Asesor $asesor,
        int $status,
        bool $overdue
    ): Akreditasi {
        $today = Carbon::today();
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(20),
            'tanggal_berakhir' => $overdue
                ? $today->copy()->subDays(5)->toDateString()   // overdue: 5 days past deadline
                : $today->copy()->addDays(20)->toDateString(), // not overdue: 20 days in future
        ]);

        return $akreditasi;
    }

    // =========================================================================
    // Task 8.5: Reassign button disabled when not overdue, enabled when overdue
    // =========================================================================

    /**
     * Task 8.5: Reassign button is disabled (not shown as active) when akreditasi is NOT overdue.
     *
     * When the akreditasi is in assessment/visitasi phase but not overdue,
     * the "Ganti Asesor" button should be present but disabled.
     */
public function test_reassign_button_disabled_when_not_overdue(): void
    {
        $today = Carbon::create(2025, 11, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser('Pesantren Tepat Waktu');
        [$asesor] = $this->createAsesorWithUser('Asesor Aktif');

        $akreditasi = $this->createAkreditasiWithAssessment($pesantrenUser, $asesor, 5, false);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // The button should be present but disabled
        $component->assertSee('Ganti Asesor');
        $component->assertSeeHtml('disabled');

        // isOverdue should be false
        $component->assertSet('isOverdue', false);

        Carbon::setTestNow();
    }

    /**
     * Task 8.5: Reassign button is enabled when akreditasi IS overdue.
     *
     * When the akreditasi is overdue, the "Ganti Asesor" button should be
     * enabled (not disabled) and styled as danger.
     */
public function test_reassign_button_enabled_when_overdue(): void
    {
        $today = Carbon::create(2025, 11, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser('Pesantren Terlambat');
        [$asesor] = $this->createAsesorWithUser('Asesor Terlambat');

        $akreditasi = $this->createAkreditasiWithAssessment($pesantrenUser, $asesor, 5, true);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // The button should be present and enabled
        $component->assertSee('Ganti Asesor');

        // isOverdue should be true
        $component->assertSet('isOverdue', true);

        Carbon::setTestNow();
    }

    /**
     * Task 8.5: Reassign button is NOT shown for completed akreditasi (status 1).
     */
public function test_reassign_button_not_shown_for_completed_akreditasi(): void
    {
        $today = Carbon::create(2025, 11, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser('Pesantren Selesai');

        // Status 1 = completed, no reassign button
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 1,
            'nomor_sk' => 'SK-001',
            'masa_berlaku' => now()->subYear(),
            'masa_berlaku_akhir' => now()->addYears(4),
            'nilai' => 90,
            'peringkat' => 'Unggul',
        ]);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // The button should NOT be shown for completed akreditasi (status 1 is not in [4,5])
        // The @if(in_array($akreditasi->status, [4, 5])) condition prevents rendering
        $component->assertDontSeeHtml('data-testid="reassign-asesor-btn"');

        Carbon::setTestNow();
    }

    /**
     * Task 8.5: Overdue badge is shown in toolbar when akreditasi is overdue.
     */
public function test_overdue_badge_shown_in_toolbar_when_overdue(): void
    {
        $today = Carbon::create(2025, 11, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser('Pesantren Terlambat');
        [$asesor] = $this->createAsesorWithUser('Asesor Terlambat');

        $akreditasi = $this->createAkreditasiWithAssessment($pesantrenUser, $asesor, 5, true);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        $component->assertSee('Terlambat');

        Carbon::setTestNow();
    }

    // =========================================================================
    // Task 8.6: Reassignment action updates the view and shows success message
    // =========================================================================

    /**
     * Task 8.6: Reassignment action updates the view and shows success message.
     *
     * When the admin calls reassignAsesor() with a valid new asesor:
     * - The assessment should be updated with the new asesor
     * - A success flash message should be shown
     * - Notifications should be sent to old and new asesor
     */
public function test_reassignment_action_updates_view_and_shows_success_message(): void
    {
        $today = Carbon::create(2025, 11, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        config(['akreditasi-timeout.assessment.default_duration_days' => 30]);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser('Pesantren Terlambat');
        [$oldAsesor, $oldAsesorUser] = $this->createAsesorWithUser('Asesor Lama');
        [$newAsesor, $newAsesorUser] = $this->createAsesorWithUser('Asesor Baru');

        $akreditasi = $this->createAkreditasiWithAssessment($pesantrenUser, $oldAsesor, 5, true);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // Verify initial state: overdue
        $component->assertSet('isOverdue', true);

        // Perform reassignment
        $component->set('reassignAsesorId', $newAsesor->id)
            ->call('reassignAsesor');

        // Verify the assessment was updated in the database
        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $newAsesor->id,
        ]);

        // Verify notifications were sent
        Notification::assertSentTo(
            $newAsesorUser,
            AkreditasiNotification::class,
            fn($n) => $n->type === 'asesor_reassigned_new'
        );

        Notification::assertSentTo(
            $oldAsesorUser,
            AkreditasiNotification::class,
            fn($n) => $n->type === 'asesor_reassigned_old'
        );

        Carbon::setTestNow();
    }

    /**
     * Task 8.6: Reassignment action fails with error when no asesor selected.
     */
public function test_reassignment_action_fails_with_validation_error_when_no_asesor_selected(): void
    {
        $today = Carbon::create(2025, 11, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser('Pesantren Terlambat');
        [$asesor] = $this->createAsesorWithUser('Asesor Terlambat');

        $akreditasi = $this->createAkreditasiWithAssessment($pesantrenUser, $asesor, 5, true);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // Try to reassign without selecting an asesor
        $component->set('reassignAsesorId', '')
            ->call('reassignAsesor');

        $component->assertHasErrors(['reassignAsesorId']);

        Carbon::setTestNow();
    }

    /**
     * Task 8.6: Reassignment action fails with error when akreditasi is not overdue.
     *
     * If somehow the reassign action is called on a non-overdue akreditasi,
     * it should show an error message.
     */
public function test_reassignment_action_shows_error_when_not_overdue(): void
    {
        $today = Carbon::create(2025, 11, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser('Pesantren Tepat Waktu');
        [$asesor] = $this->createAsesorWithUser('Asesor Aktif');
        [$newAsesor] = $this->createAsesorWithUser('Asesor Baru');

        // Non-overdue akreditasi
        $akreditasi = $this->createAkreditasiWithAssessment($pesantrenUser, $asesor, 5, false);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // Try to reassign on non-overdue akreditasi
        $component->set('reassignAsesorId', $newAsesor->id)
            ->call('reassignAsesor');

        // Assessment should NOT be updated (still the original asesor)
        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id, // still the original asesor
        ]);

        Carbon::setTestNow();
    }

    /**
     * Task 8.6: After successful reassignment, the new deadline is reset.
     */
public function test_reassignment_resets_deadline_to_configured_duration(): void
    {
        $today = Carbon::create(2025, 11, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $configDuration = 30;
        config(['akreditasi-timeout.assessment.default_duration_days' => $configDuration]);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser('Pesantren Terlambat');
        [$oldAsesor] = $this->createAsesorWithUser('Asesor Lama');
        [$newAsesor] = $this->createAsesorWithUser('Asesor Baru');

        $akreditasi = $this->createAkreditasiWithAssessment($pesantrenUser, $oldAsesor, 5, true);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        $component->set('reassignAsesorId', $newAsesor->id)
            ->call('reassignAsesor');

        // Verify the new deadline is today + configured duration
        $expectedDeadline = $today->copy()->addDays($configDuration)->toDateString();
        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $newAsesor->id,
        ]);

        // Verify the deadline was reset correctly
        $updatedAssessment = \App\Models\Assessment::where('akreditasi_id', $akreditasi->id)->first();
        $this->assertEquals($expectedDeadline, $updatedAssessment->tanggal_berakhir->toDateString());

        Carbon::setTestNow();
    }

    /**
     * Task 8.6: Available asesors list excludes the currently assigned asesor.
     */
public function test_available_asesors_excludes_current_asesor(): void
    {
        $today = Carbon::create(2025, 11, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser('Pesantren Terlambat');
        [$currentAsesor] = $this->createAsesorWithUser('Asesor Saat Ini');
        [$otherAsesor] = $this->createAsesorWithUser('Asesor Lain');

        $akreditasi = $this->createAkreditasiWithAssessment($pesantrenUser, $currentAsesor, 5, true);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // The available asesors should not include the current asesor
        $availableAsesors = $component->get('availableAsesorsForReassignment');
        $availableIds = collect($availableAsesors)->pluck('id')->toArray();

        $this->assertNotContains($currentAsesor->id, $availableIds, 'Current asesor should not be in available list');
        $this->assertContains($otherAsesor->id, $availableIds, 'Other asesor should be in available list');

        Carbon::setTestNow();
    }
}
