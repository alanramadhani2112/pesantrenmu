<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Services\DeadlineService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Tests for DeadlineService::reassignAsesor()
 *
 * Covers:
 *  - Task 6.1 / 6.2: Implementation verification (via unit + integration tests)
 *  - Task 6.3 (Property 8): Asesor and deadline correctly updated after reassignment
 *  - Task 6.4 (Property 9): EDPM data preserved after reassignment
 *  - Task 6.5: Notifications sent to both old and new asesor
 *  - Task 6.6: DomainException thrown when akreditasi is not overdue
 */
#[Group('Feature: assessment-visitasi-timeout')]
class ReassignAsesorTest extends TestCase
{
    use RefreshDatabase;

    protected DeadlineService $deadlineService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->deadlineService = app(DeadlineService::class);

        // Log in as admin so audit trail observer has a user_id
        $admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($admin);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create an Asesor with an associated User (role_id=2).
     * Returns [$asesor, $user].
     */
    private function createAsesorWithUser(?string $name = null): array
    {
        $user = User::factory()->create(['role_id' => 2]);
        $displayName = $name ?? ('Asesor '.$user->id);
        $asesor = Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => $displayName,
            'nama_tanpa_gelar' => $displayName,
        ]);

        return [$asesor, $user];
    }

    /**
     * Create an Akreditasi with a pesantren user (role_id=3).
     * Returns [$akreditasi, $pesantrenUser].
     */
    private function createAkreditasiWithUser(int $status = 5): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test '.$pesantrenUser->id,
        ]);
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ]);

        return [$akreditasi, $pesantrenUser];
    }

    /**
     * Create an overdue Assessment for the given akreditasi and asesor.
     */
    private function createOverdueAssessment(Akreditasi $akreditasi, Asesor $asesor, Carbon $today): Assessment
    {
        return Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(20),
            'tanggal_berakhir' => $today->copy()->subDays(5), // overdue
            'last_reminder_sent_at' => $today->copy()->subDays(3),
            'last_escalation_sent_at' => $today->copy()->subDays(1),
        ]);
    }

    /**
     * Create a MasterEdpmKomponen and distinct MasterEdpmButir records.
     */
    private function createEdpmMasterData(int $butirCount = 1): array
    {
        $komponen = MasterEdpmKomponen::create(['nama' => 'Komponen Test']);
        $butirs = collect();

        for ($i = 1; $i <= $butirCount; $i++) {
            $butirs->push(MasterEdpmButir::create([
                'komponen_id' => $komponen->id,
                'no_sk' => sprintf('SK-%03d', $i),
                'nomor_butir' => "1.{$i}",
                'butir_pernyataan' => "Butir pernyataan test {$i}",
            ]));
        }

        return [$komponen, $butirs];
    }

    // =========================================================================
    // Property 8: Reassignment updates asesor and resets deadline
    // **Validates: Requirements 6.3**
    // =========================================================================

    /**
     * Property 8: Reassignment updates asesor_id and resets tanggal_berakhir.
     *
     * For 100 reassignment scenarios with random overdue assessments:
     * - After reassignment, asesor_id SHALL equal newAsesorId
     * - After reassignment, tanggal_berakhir SHALL equal today + configured duration
     * - last_reminder_sent_at and last_escalation_sent_at SHALL be cleared (null)
     *
     * **Validates: Requirements 6.3**
     */
    public function test_property_8_reassignment_updates_asesor_and_resets_deadline(): void
    {
        $faker = Faker::create();

        Notification::fake();

        $today = Carbon::create(2025, 6, 15, 0, 0, 0);
        Carbon::setTestNow($today);

        // Reuse a single akreditasi to avoid observer issues on delete
        [$akreditasi] = $this->createAkreditasiWithUser(5);

        for ($i = 0; $i < 100; $i++) {
            // Random assessment duration (1–60 days)
            $configDuration = $faker->numberBetween(1, 60);
            config(['akreditasi-timeout.assessment.default_duration_days' => $configDuration]);

            // Random days overdue (1–30)
            $daysOverdue = $faker->numberBetween(1, 30);

            [$oldAsesor] = $this->createAsesorWithUser();
            [$newAsesor] = $this->createAsesorWithUser();

            $assessment = Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $oldAsesor->id,
                'tipe' => 1,
                'tanggal_mulai' => $today->copy()->subDays($daysOverdue + 10),
                'tanggal_berakhir' => $today->copy()->subDays($daysOverdue),
                'last_reminder_sent_at' => $today->copy()->subDays(1),
                'last_escalation_sent_at' => $today->copy()->subDays(1),
            ]);

            // Reinitialize service so it picks up the new config
            $this->deadlineService = app(DeadlineService::class);
            $this->deadlineService->reassignAsesor($assessment, $newAsesor->id);

            $assessment->refresh();

            // asesor_id must be updated to newAsesorId
            $this->assertEquals(
                $newAsesor->id,
                $assessment->asesor_id,
                "Iteration {$i}: asesor_id should be updated to new asesor id={$newAsesor->id}"
            );

            // tanggal_berakhir must be today + configured duration
            $expectedDeadline = $today->copy()->addDays($configDuration)->toDateString();
            $this->assertEquals(
                $expectedDeadline,
                $assessment->tanggal_berakhir->toDateString(),
                "Iteration {$i}: tanggal_berakhir should be today + {$configDuration} days = {$expectedDeadline}"
            );

            // last_reminder_sent_at must be cleared
            $this->assertNull(
                $assessment->last_reminder_sent_at,
                "Iteration {$i}: last_reminder_sent_at should be null after reassignment"
            );

            // last_escalation_sent_at must be cleared
            $this->assertNull(
                $assessment->last_escalation_sent_at,
                "Iteration {$i}: last_escalation_sent_at should be null after reassignment"
            );

            // Clean up for next iteration
            $assessment->forceDelete();
        }

        Carbon::setTestNow();
    }

    // =========================================================================
    // Property 9: Data preservation during reassignment
    // **Validates: Requirements 6.6**
    // =========================================================================

    /**
     * Property 9: EDPM data is preserved after asesor reassignment.
     *
     * For 100 scenarios with random EDPM data:
     * - Create AkreditasiEdpm and AkreditasiEdpmCatatan records
     * - Perform reassignment
     * - Verify all EDPM records remain unchanged (same count, same values)
     *
     * **Validates: Requirements 6.6**
     */
    public function test_property_9_edpm_data_preserved_after_reassignment(): void
    {
        $faker = Faker::create();

        Notification::fake();

        $today = Carbon::create(2025, 7, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        config(['akreditasi-timeout.assessment.default_duration_days' => 30]);

        // Create master EDPM data once (reused across iterations)
        [$komponen, $butirs] = $this->createEdpmMasterData(5);

        for ($i = 0; $i < 100; $i++) {
            // Create a fresh akreditasi for each iteration
            [$akreditasi, $pesantrenUser] = $this->createAkreditasiWithUser(5);

            [$oldAsesor] = $this->createAsesorWithUser();
            [$newAsesor] = $this->createAsesorWithUser();

            $assessment = Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $oldAsesor->id,
                'tipe' => 1,
                'tanggal_mulai' => $today->copy()->subDays(15),
                'tanggal_berakhir' => $today->copy()->subDays(3), // overdue
                'last_reminder_sent_at' => null,
                'last_escalation_sent_at' => null,
            ]);

            // Create random number of AkreditasiEdpm records (1–5)
            $edpmCount = $faker->numberBetween(1, 5);
            $edpmData = [];
            for ($j = 0; $j < $edpmCount; $j++) {
                $edpm = AkreditasiEdpm::create([
                    'akreditasi_id' => $akreditasi->id,
                    'pesantren_id' => $pesantrenUser->id,
                    'asesor_id' => $oldAsesor->id,
                    'butir_id' => $butirs[$j]->id,
                    'isian' => $faker->randomElement(['A', 'B', 'C', 'D']),
                    'nk' => $faker->numberBetween(1, 100),
                    'nv' => $faker->numberBetween(1, 100),
                    'catatan' => $faker->sentence(),
                ]);
                $edpmData[] = [
                    'id' => $edpm->id,
                    'isian' => $edpm->isian,
                    'nk' => $edpm->nk,
                    'nv' => $edpm->nv,
                    'catatan' => $edpm->catatan,
                ];
            }

            // Create random number of AkreditasiEdpmCatatan records (1–3)
            $catatanCount = $faker->numberBetween(1, 3);
            $catatanData = [];
            for ($j = 0; $j < $catatanCount; $j++) {
                $catatan = AkreditasiEdpmCatatan::create([
                    'akreditasi_id' => $akreditasi->id,
                    'pesantren_id' => $pesantrenUser->id,
                    'asesor_id' => $oldAsesor->id,
                    'komponen_id' => $komponen->id,
                    'catatan' => $faker->sentence(),
                    'nk' => $faker->numberBetween(1, 100),
                ]);
                $catatanData[] = [
                    'id' => $catatan->id,
                    'catatan' => $catatan->catatan,
                    'nk' => $catatan->nk,
                ];
            }

            // Perform reassignment
            $this->deadlineService = app(DeadlineService::class);
            $this->deadlineService->reassignAsesor($assessment, $newAsesor->id);

            // Verify AkreditasiEdpm records are preserved
            $remainingEdpms = AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)->get();
            $this->assertCount(
                $edpmCount,
                $remainingEdpms,
                "Iteration {$i}: AkreditasiEdpm count should remain {$edpmCount} after reassignment"
            );

            foreach ($edpmData as $expected) {
                $actual = $remainingEdpms->firstWhere('id', $expected['id']);
                $this->assertNotNull(
                    $actual,
                    "Iteration {$i}: AkreditasiEdpm id={$expected['id']} should still exist after reassignment"
                );
                $this->assertEquals($expected['isian'], $actual->isian, "Iteration {$i}: isian should be unchanged");
                $this->assertEquals($expected['nk'], $actual->nk, "Iteration {$i}: nk should be unchanged");
                $this->assertEquals($expected['nv'], $actual->nv, "Iteration {$i}: nv should be unchanged");
                $this->assertEquals($expected['catatan'], $actual->catatan, "Iteration {$i}: catatan should be unchanged");
            }

            // Verify AkreditasiEdpmCatatan records are preserved
            $remainingCatatans = AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasi->id)->get();
            $this->assertCount(
                $catatanCount,
                $remainingCatatans,
                "Iteration {$i}: AkreditasiEdpmCatatan count should remain {$catatanCount} after reassignment"
            );

            foreach ($catatanData as $expected) {
                $actual = $remainingCatatans->firstWhere('id', $expected['id']);
                $this->assertNotNull(
                    $actual,
                    "Iteration {$i}: AkreditasiEdpmCatatan id={$expected['id']} should still exist after reassignment"
                );
                $this->assertEquals($expected['catatan'], $actual->catatan, "Iteration {$i}: catatan should be unchanged");
                $this->assertEquals($expected['nk'], $actual->nk, "Iteration {$i}: nk should be unchanged");
            }

            // Clean up for next iteration (bypass observer)
            $assessment->forceDelete();
            AkreditasiEdpm::where('akreditasi_id', $akreditasi->id)->forceDelete();
            AkreditasiEdpmCatatan::where('akreditasi_id', $akreditasi->id)->forceDelete();
            DB::table('akreditasis')->where('id', $akreditasi->id)->delete();
            DB::table('users')->where('id', $pesantrenUser->id)->delete();
        }

        Carbon::setTestNow();
    }

    // =========================================================================
    // Task 6.5: Integration test — reassignment sends notifications to both asesors
    // **Validates: Requirements 6.4, 6.5**
    // =========================================================================

    /**
     * Task 6.5: Integration test — reassignment sends notification to both old and new asesor.
     *
     * When reassignAsesor() is called on an overdue assessment:
     * - The new asesor SHALL receive a notification of type 'asesor_reassigned_new'
     * - The old asesor SHALL receive a notification of type 'asesor_reassigned_old'
     *
     * **Validates: Requirements 6.4, 6.5**
     */
    public function test_integration_reassignment_sends_notifications_to_both_asesors(): void
    {
        Notification::fake();

        $today = Carbon::create(2025, 8, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        config(['akreditasi-timeout.assessment.default_duration_days' => 30]);

        [$akreditasi, $pesantrenUser] = $this->createAkreditasiWithUser(5);
        [$oldAsesor, $oldAsesorUser] = $this->createAsesorWithUser('Old Asesor');
        [$newAsesor, $newAsesorUser] = $this->createAsesorWithUser('New Asesor');

        $assessment = $this->createOverdueAssessment($akreditasi, $oldAsesor, $today);

        $this->deadlineService->reassignAsesor($assessment, $newAsesor->id);

        // New asesor should receive 'asesor_reassigned_new' notification
        Notification::assertSentTo(
            $newAsesorUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'asesor_reassigned_new';
            }
        );

        // Old asesor should receive 'asesor_reassigned_old' notification
        Notification::assertSentTo(
            $oldAsesorUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'asesor_reassigned_old';
            }
        );

        Carbon::setTestNow();
    }

    /**
     * Task 6.5 (variant): Notification to new asesor contains pesantren name and deadline.
     *
     * **Validates: Requirements 6.4**
     */
    public function test_integration_new_asesor_notification_contains_pesantren_and_deadline(): void
    {
        Notification::fake();

        $today = Carbon::create(2025, 8, 10, 0, 0, 0);
        Carbon::setTestNow($today);

        $configDuration = 30;
        config(['akreditasi-timeout.assessment.default_duration_days' => $configDuration]);

        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        $pesantrenName = 'Pesantren Notifikasi Test';
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => $pesantrenName,
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 5,
        ]);

        [$oldAsesor] = $this->createAsesorWithUser();
        [$newAsesor, $newAsesorUser] = $this->createAsesorWithUser();

        $assessment = $this->createOverdueAssessment($akreditasi, $oldAsesor, $today);

        $this->deadlineService->reassignAsesor($assessment, $newAsesor->id);

        $expectedDeadline = $today->copy()->addDays($configDuration)->format('d/m/Y');

        Notification::assertSentTo(
            $newAsesorUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) use ($pesantrenName, $expectedDeadline) {
                return $notification->type === 'asesor_reassigned_new'
                    && str_contains($notification->message, $pesantrenName)
                    && str_contains($notification->message, $expectedDeadline);
            }
        );

        Carbon::setTestNow();
    }

    // =========================================================================
    // Task 6.6: Unit test — reassignment on non-overdue akreditasi throws DomainException
    // **Validates: Requirements 6.7**
    // =========================================================================

    /**
     * Task 6.6: Unit test — reassignment on non-overdue akreditasi throws DomainException.
     *
     * When reassignAsesor() is called on an assessment that is NOT overdue,
     * it SHALL throw a DomainException.
     *
     * **Validates: Requirements 6.7**
     */
    public function test_unit_reassignment_on_non_overdue_throws_domain_exception(): void
    {
        $today = Carbon::create(2025, 9, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        [$akreditasi] = $this->createAkreditasiWithUser(5);
        [$asesor] = $this->createAsesorWithUser();
        [$newAsesor] = $this->createAsesorWithUser();

        // Assessment with deadline in the future (not overdue)
        $assessment = Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(5),
            'tanggal_berakhir' => $today->copy()->addDays(10), // future deadline
        ]);

        $this->expectException(\DomainException::class);

        $this->deadlineService->reassignAsesor($assessment, $newAsesor->id);

        Carbon::setTestNow();
    }

    /**
     * Task 6.6 (variant): Reassignment on assessment with deadline exactly today does NOT throw.
     *
     * Deadline today means tanggal_berakhir == today, which is NOT overdue
     * (isOverdue returns true only when tanggal_berakhir < today).
     * So this should also throw DomainException.
     *
     * **Validates: Requirements 6.7**
     */
    public function test_unit_reassignment_on_deadline_today_throws_domain_exception(): void
    {
        $today = Carbon::create(2025, 9, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        [$akreditasi] = $this->createAkreditasiWithUser(5);
        [$asesor] = $this->createAsesorWithUser();
        [$newAsesor] = $this->createAsesorWithUser();

        // Assessment with deadline exactly today (not overdue per isOverdue logic)
        $assessment = Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(10),
            'tanggal_berakhir' => $today->copy(), // today = not overdue
        ]);

        $this->expectException(\DomainException::class);

        $this->deadlineService->reassignAsesor($assessment, $newAsesor->id);

        Carbon::setTestNow();
    }

    /**
     * Task 6.6 (variant): Reassignment on overdue assessment does NOT throw.
     *
     * Verify the happy path: overdue assessment can be reassigned without exception.
     *
     * **Validates: Requirements 6.1**
     */
    public function test_unit_reassignment_on_overdue_does_not_throw(): void
    {
        Notification::fake();

        $today = Carbon::create(2025, 9, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        config(['akreditasi-timeout.assessment.default_duration_days' => 30]);

        [$akreditasi] = $this->createAkreditasiWithUser(5);
        [$asesor] = $this->createAsesorWithUser();
        [$newAsesor] = $this->createAsesorWithUser();

        $assessment = $this->createOverdueAssessment($akreditasi, $asesor, $today);

        // Should not throw
        $this->deadlineService->reassignAsesor($assessment, $newAsesor->id);

        $assessment->refresh();
        $this->assertEquals($newAsesor->id, $assessment->asesor_id);

        Carbon::setTestNow();
    }

    /**
     * Task 6.6 (variant): Reassignment on assessment far in the future throws DomainException.
     *
     * An assessment with a deadline far in the future is not overdue,
     * so reassignment should be blocked.
     *
     * **Validates: Requirements 6.7**
     */
    public function test_unit_reassignment_on_far_future_deadline_throws_domain_exception(): void
    {
        $today = Carbon::create(2025, 9, 1, 0, 0, 0);
        Carbon::setTestNow($today);

        [$akreditasi] = $this->createAkreditasiWithUser(5);
        [$asesor] = $this->createAsesorWithUser();
        [$newAsesor] = $this->createAsesorWithUser();

        // Assessment with deadline far in the future (not overdue)
        $assessment = Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => $today->copy()->subDays(5),
            'tanggal_berakhir' => $today->copy()->addDays(60), // far future
        ]);

        $this->expectException(\DomainException::class);

        $this->deadlineService->reassignAsesor($assessment, $newAsesor->id);

        Carbon::setTestNow();
    }
}
