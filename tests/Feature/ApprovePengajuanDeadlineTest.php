<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AkreditasiService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Integration tests for AkreditasiService::approvePengajuan() deadline calculation.
 *
 * @group Feature: assessment-visitasi-timeout
 */
class ApprovePengajuanDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected AkreditasiService $akreditasiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->akreditasiService = app(AkreditasiService::class);

        // Log in as admin for audit trail
        $admin = User::factory()->create(['role_id' => 1]);
        Auth::login($admin);
    }

    /**
     * Helper: create a pesantren user with a Pesantren record.
     */
    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Approval Test ' . $user->id,
        ]);
        return $user;
    }

    /**
     * Helper: create an Asesor with an associated User.
     */
    private function createAsesor(string $name = 'Asesor Test'): Asesor
    {
        $user = User::factory()->create(['role_id' => 2]);
        return Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => $name,
            'nama_tanpa_gelar' => $name,
        ]);
    }

    // =========================================================================
    // Task 5.2: Approval without explicit end date uses config duration
    // =========================================================================

    /**
     * Task 5.2: Integration test — approval without explicit end date uses config duration.
     *
     * When approvePengajuan() is called without tanggal_berakhir,
     * the system SHALL calculate it as tanggal_mulai + config duration.
     *
     * **Validates: Requirements 1.2**
     */
    public function test_approval_without_explicit_end_date_uses_config_duration(): void
    {
        $configDuration = 30;
        config(['akreditasi-timeout.assessment.default_duration_days' => $configDuration]);

        $pesantrenUser = $this->createPesantrenUser();
        $asesor = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $tanggalMulai = '2025-11-01';

        // Call approvePengajuan WITHOUT tanggal_berakhir
        $this->akreditasiService->approvePengajuan($akreditasi->id, [
            'asesor_id1' => $asesor->id,
            'tanggal_mulai' => $tanggalMulai,
            // tanggal_berakhir intentionally omitted
        ]);

        // Verify the assessment was created with the correct tanggal_berakhir
        $assessment = Assessment::where('akreditasi_id', $akreditasi->id)
            ->where('tipe', 1)
            ->first();

        $this->assertNotNull($assessment, 'Assessment should be created');

        $expectedEndDate = Carbon::parse($tanggalMulai)->addDays($configDuration)->toDateString();
        $this->assertEquals(
            $expectedEndDate,
            $assessment->tanggal_berakhir->toDateString(),
            "tanggal_berakhir should be tanggal_mulai + {$configDuration} days = {$expectedEndDate}"
        );
    }

    /**
     * Task 5.2 (variant): Approval without explicit end date uses config duration — different duration.
     *
     * Verify the calculation works with a different configured duration.
     */
    public function test_approval_without_explicit_end_date_uses_custom_config_duration(): void
    {
        $configDuration = 45;
        config(['akreditasi-timeout.assessment.default_duration_days' => $configDuration]);

        $pesantrenUser = $this->createPesantrenUser();
        $asesor = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $tanggalMulai = '2025-12-01';

        $this->akreditasiService->approvePengajuan($akreditasi->id, [
            'asesor_id1' => $asesor->id,
            'tanggal_mulai' => $tanggalMulai,
            // tanggal_berakhir intentionally omitted
        ]);

        $assessment = Assessment::where('akreditasi_id', $akreditasi->id)
            ->where('tipe', 1)
            ->first();

        $this->assertNotNull($assessment);

        $expectedEndDate = Carbon::parse($tanggalMulai)->addDays($configDuration)->toDateString();
        $this->assertEquals(
            $expectedEndDate,
            $assessment->tanggal_berakhir->toDateString(),
            "tanggal_berakhir should be tanggal_mulai + {$configDuration} days = {$expectedEndDate}"
        );
    }

    // =========================================================================
    // Task 5.3: Approval with explicit end date preserves the provided date
    // =========================================================================

    /**
     * Task 5.3: Integration test — approval with explicit end date preserves the provided date.
     *
     * When approvePengajuan() is called WITH an explicit tanggal_berakhir,
     * the system SHALL use the provided date, not the config-calculated one.
     *
     * **Validates: Requirements 1.2**
     */
    public function test_approval_with_explicit_end_date_preserves_provided_date(): void
    {
        config(['akreditasi-timeout.assessment.default_duration_days' => 30]);

        $pesantrenUser = $this->createPesantrenUser();
        $asesor = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $tanggalMulai = '2025-11-01';
        $explicitEndDate = '2025-12-31'; // Different from tanggal_mulai + 30 days

        // Call approvePengajuan WITH explicit tanggal_berakhir
        $this->akreditasiService->approvePengajuan($akreditasi->id, [
            'asesor_id1' => $asesor->id,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_berakhir' => $explicitEndDate,
        ]);

        $assessment = Assessment::where('akreditasi_id', $akreditasi->id)
            ->where('tipe', 1)
            ->first();

        $this->assertNotNull($assessment, 'Assessment should be created');

        $this->assertEquals(
            $explicitEndDate,
            $assessment->tanggal_berakhir->toDateString(),
            "tanggal_berakhir should be the explicitly provided date {$explicitEndDate}"
        );

        // Verify it's NOT the config-calculated date
        $configCalculatedDate = Carbon::parse($tanggalMulai)->addDays(30)->toDateString();
        $this->assertNotEquals(
            $configCalculatedDate,
            $assessment->tanggal_berakhir->toDateString(),
            "tanggal_berakhir should NOT be the config-calculated date {$configCalculatedDate}"
        );
    }

    /**
     * Task 5.3 (variant): Explicit end date is preserved even when it equals the config-calculated date.
     */
    public function test_approval_with_explicit_end_date_matching_config_still_uses_provided_date(): void
    {
        $configDuration = 30;
        config(['akreditasi-timeout.assessment.default_duration_days' => $configDuration]);

        $pesantrenUser = $this->createPesantrenUser();
        $asesor = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $tanggalMulai = '2025-11-01';
        // Explicit end date that happens to match the config calculation
        $explicitEndDate = Carbon::parse($tanggalMulai)->addDays($configDuration)->toDateString();

        $this->akreditasiService->approvePengajuan($akreditasi->id, [
            'asesor_id1' => $asesor->id,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_berakhir' => $explicitEndDate,
        ]);

        $assessment = Assessment::where('akreditasi_id', $akreditasi->id)
            ->where('tipe', 1)
            ->first();

        $this->assertNotNull($assessment);
        $this->assertEquals(
            $explicitEndDate,
            $assessment->tanggal_berakhir->toDateString()
        );
    }

    // =========================================================================
    // Task 5.4: Verify existing approval tests still pass
    // (These tests replicate the existing approval behavior to ensure no regression)
    // =========================================================================

    /**
     * Task 5.4: Existing approval behavior — status changes to 5 after approval.
     */
    public function test_existing_approval_changes_status_to_5(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $this->akreditasiService->approvePengajuan($akreditasi->id, [
            'asesor_id1' => $asesor->id,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addDays(14)->toDateString(),
        ]);

        $akreditasi->refresh();
        $this->assertEquals(5, $akreditasi->status, 'Status should change to 5 (Assessment) after approval');
    }

    /**
     * Task 5.4: Existing approval behavior — assessment records are created.
     */
    public function test_existing_approval_creates_assessment_records(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor1 = $this->createAsesor('Asesor 1');
        $asesor2 = $this->createAsesor('Asesor 2');

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $this->akreditasiService->approvePengajuan($akreditasi->id, [
            'asesor_id1' => $asesor1->id,
            'asesor_id2' => $asesor2->id,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addDays(14)->toDateString(),
        ]);

        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
        ]);

        $this->assertDatabaseHas('assessments', [
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
        ]);
    }

    /**
     * Task 5.4: Existing approval behavior — throws DomainException for non-pengajuan status.
     */
    public function test_existing_approval_throws_exception_for_non_pengajuan_status(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 5, // Not status 6 (Pengajuan)
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Status bukan Pengajuan');

        $this->akreditasiService->approvePengajuan($akreditasi->id, [
            'asesor_id1' => $asesor->id,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addDays(14)->toDateString(),
        ]);
    }

    /**
     * Task 5.4: Existing approval behavior — notifications are sent after approval.
     */
    public function test_existing_approval_sends_notifications(): void
    {
        $pesantrenUser = $this->createPesantrenUser();
        $asesor = $this->createAsesor();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $this->akreditasiService->approvePengajuan($akreditasi->id, [
            'asesor_id1' => $asesor->id,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addDays(14)->toDateString(),
        ]);

        // Verify notification was sent to pesantren user
        Notification::assertSentTo(
            $pesantrenUser,
            \App\Notifications\AkreditasiNotification::class
        );

        // Verify notification was sent to asesor user
        Notification::assertSentTo(
            $asesor->user,
            \App\Notifications\AkreditasiNotification::class
        );
    }
}
