<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AkreditasiService;
use App\Services\DeadlineService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Tests for admin akreditasi overdue indicators and filter.
 *
 * Covers:
 *  - Task 7.5: Overdue filter returns only overdue items
 *  - Task 7.6: Overdue count is included in status counts
 *  - Task 7.7: Overdue badge is rendered for overdue items and not for non-overdue items
 *
 * @group Feature: assessment-visitasi-timeout
 */
class AdminAkreditasiOverdueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createAdminUser(): User
    {
        return User::factory()->create(['role_id' => 1]);
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

    private function createAkreditasiWithOverdueAssessment(int $status = 5, string $pesantrenName = 'Pesantren Overdue'): array
    {
        $today = Carbon::today();
        $pesantrenUser = $this->createPesantrenUser($pesantrenName);
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => $status]);
        [$asesor] = $this->createAsesorWithUser();
        $assessment = Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(20),
            'tanggal_berakhir' => $today->copy()->subDays(5), // overdue
        ]);
        return [$akreditasi, $pesantrenUser, $asesor, $assessment];
    }

    private function createAkreditasiWithFutureAssessment(int $status = 5, string $pesantrenName = 'Pesantren Future'): array
    {
        $today = Carbon::today();
        $pesantrenUser = $this->createPesantrenUser($pesantrenName);
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => $status]);
        [$asesor] = $this->createAsesorWithUser();
        $assessment = Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(5),
            'tanggal_berakhir' => $today->copy()->addDays(20), // not overdue
        ]);
        return [$akreditasi, $pesantrenUser, $asesor, $assessment];
    }

    // =========================================================================
    // Task 7.5: Overdue filter returns only overdue items
    // =========================================================================

    /**
     * Task 7.5: Overdue filter returns only overdue items.
     *
     * When statusFilter is set to 'overdue', the component should only show
     * akreditasi items that are overdue (past tanggal_berakhir).
     */
    public function test_overdue_filter_returns_only_overdue_items(): void
    {
        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        // Create one overdue akreditasi (status 5, past deadline)
        [$overdueAkreditasi] = $this->createAkreditasiWithOverdueAssessment(5, 'Pesantren Terlambat');

        // Create one non-overdue akreditasi (status 5, future deadline)
        [$futureAkreditasi] = $this->createAkreditasiWithFutureAssessment(5, 'Pesantren Tepat Waktu');

        // Create one pengajuan (status 6) - should not appear in overdue filter
        $pengajuanUser = $this->createPesantrenUser('Pesantren Pengajuan');
        $pengajuanAkreditasi = Akreditasi::create(['user_id' => $pengajuanUser->id, 'status' => 6]);

        $component = Volt::test('pages.admin.akreditasi')
            ->set('statusFilter', 'overdue');

        // Should see the overdue pesantren
        $component->assertSee('Pesantren Terlambat');

        // Should NOT see the non-overdue pesantren
        $component->assertDontSee('Pesantren Tepat Waktu');

        // Should NOT see the pengajuan pesantren
        $component->assertDontSee('Pesantren Pengajuan');

        Carbon::setTestNow();
    }

    /**
     * Task 7.5 (variant): Overdue filter includes both status 4 and 5 overdue items.
     */
    public function test_overdue_filter_includes_status_4_and_5(): void
    {
        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        // Create overdue status 5 (assessment)
        [$overdueAssessment] = $this->createAkreditasiWithOverdueAssessment(5, 'Pesantren Assessment Terlambat');

        // Create overdue status 4 (visitasi)
        [$overdueVisitasi] = $this->createAkreditasiWithOverdueAssessment(4, 'Pesantren Visitasi Terlambat');

        $component = Volt::test('pages.admin.akreditasi')
            ->set('statusFilter', 'overdue');

        $component->assertSee('Pesantren Assessment Terlambat');
        $component->assertSee('Pesantren Visitasi Terlambat');

        Carbon::setTestNow();
    }

    /**
     * Task 7.5 (variant): Overdue filter returns empty when no overdue items exist.
     */
    public function test_overdue_filter_returns_empty_when_no_overdue_items(): void
    {
        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        // Create only non-overdue items
        $this->createAkreditasiWithFutureAssessment(5, 'Pesantren Tepat Waktu');

        $component = Volt::test('pages.admin.akreditasi')
            ->set('statusFilter', 'overdue');

        $component->assertSee('Data tidak ditemukan');

        Carbon::setTestNow();
    }

    // =========================================================================
    // Task 7.6: Overdue count is included in status counts
    // =========================================================================

    /**
     * Task 7.6: Overdue count is included in AkreditasiService::getStatusCounts().
     */
    public function test_overdue_count_is_included_in_status_counts(): void
    {
        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        // Create 2 overdue akreditasi
        $this->createAkreditasiWithOverdueAssessment(5, 'Pesantren Overdue 1');
        $this->createAkreditasiWithOverdueAssessment(4, 'Pesantren Overdue 2');

        // Create 1 non-overdue akreditasi
        $this->createAkreditasiWithFutureAssessment(5, 'Pesantren Future');

        $akreditasiService = app(AkreditasiService::class);
        $counts = $akreditasiService->getStatusCounts();

        $this->assertArrayHasKey('overdue', $counts, 'Status counts should include overdue key');
        $this->assertEquals(2, $counts['overdue'], 'Overdue count should be 2');

        Carbon::setTestNow();
    }

    /**
     * Task 7.6 (variant): Overdue count is zero when no overdue items exist.
     */
    public function test_overdue_count_is_zero_when_no_overdue_items(): void
    {
        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        // Create only non-overdue items
        $this->createAkreditasiWithFutureAssessment(5, 'Pesantren Future');

        $akreditasiService = app(AkreditasiService::class);
        $counts = $akreditasiService->getStatusCounts();

        $this->assertArrayHasKey('overdue', $counts, 'Status counts should include overdue key');
        $this->assertEquals(0, $counts['overdue'], 'Overdue count should be 0');

        Carbon::setTestNow();
    }

    /**
     * Task 7.6 (variant): Overdue count badge is displayed in the component toolbar.
     */
    public function test_overdue_count_badge_displayed_in_toolbar_when_overdue_exists(): void
    {
        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $this->createAkreditasiWithOverdueAssessment(5, 'Pesantren Overdue');

        $component = Volt::test('pages.admin.akreditasi');

        // The toolbar should show the overdue count badge
        $component->assertSee('Terlambat: 1');

        Carbon::setTestNow();
    }

    /**
     * Task 7.6 (variant): Overdue filter button shows correct count.
     */
    public function test_overdue_filter_button_shows_correct_count(): void
    {
        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $this->createAkreditasiWithOverdueAssessment(5, 'Pesantren Overdue 1');
        $this->createAkreditasiWithOverdueAssessment(4, 'Pesantren Overdue 2');

        $component = Volt::test('pages.admin.akreditasi');

        // The filter button should show count 2
        $component->assertSee('Terlambat (2)');

        Carbon::setTestNow();
    }

    // =========================================================================
    // Task 7.7: Overdue badge rendered for overdue items, not for non-overdue
    // =========================================================================

    /**
     * Task 7.7: Overdue badge is rendered for overdue items.
     *
     * When an akreditasi is overdue, the list should show a red "Terlambat X hari" badge.
     */
    public function test_overdue_badge_rendered_for_overdue_items(): void
    {
        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        // Create overdue assessment (5 days overdue)
        $this->createAkreditasiWithOverdueAssessment(5, 'Pesantren Terlambat');

        // View the assessment filter to see the overdue item
        $component = Volt::test('pages.admin.akreditasi')
            ->set('statusFilter', 'assessment');

        // Should see the overdue badge with days count
        $component->assertSee('Terlambat');
        $component->assertSee('hari');

        Carbon::setTestNow();
    }

    /**
     * Task 7.7: Overdue badge is NOT rendered for non-overdue items.
     */
    public function test_overdue_badge_not_rendered_for_non_overdue_items(): void
    {
        $today = Carbon::create(2025, 10, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        // Create non-overdue assessment
        $this->createAkreditasiWithFutureAssessment(5, 'Pesantren Tepat Waktu');

        $component = Volt::test('pages.admin.akreditasi')
            ->set('statusFilter', 'assessment');

        // Should NOT see the overdue badge with "hari" (days count)
        // The filter button "Terlambat (0)" is always shown, but the per-row badge should not appear
        $component->assertDontSee('Terlambat 0 hari');
        $component->assertDontSee('Terlambat 1 hari');
        $component->assertDontSee('Terlambat 2 hari');
        $component->assertDontSee('Terlambat 3 hari');
        $component->assertDontSee('Terlambat 4 hari');
        $component->assertDontSee('Terlambat 5 hari');

        Carbon::setTestNow();
    }

    /**
     * Task 7.7: Overdue badge shows correct days count.
     */
    public function test_overdue_badge_shows_correct_days_count(): void
    {
        $today = Carbon::create(2025, 10, 10, 0, 0, 0);
        Carbon::setTestNow($today);

        $adminUser = $this->createAdminUser();
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createPesantrenUser('Pesantren Terlambat 7 Hari');
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);
        [$asesor] = $this->createAsesorWithUser();
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(17),
            'tanggal_berakhir' => $today->copy()->subDays(7), // exactly 7 days overdue
        ]);

        $component = Volt::test('pages.admin.akreditasi')
            ->set('statusFilter', 'assessment');

        $component->assertSee('Terlambat 7 hari');

        Carbon::setTestNow();
    }
}
