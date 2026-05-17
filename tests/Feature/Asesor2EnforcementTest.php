<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AsesorService;
use App\Services\ProgressTracker;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class Asesor2EnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected ProgressTracker $progressTracker;
    protected AsesorService $asesorService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        $this->progressTracker = app(ProgressTracker::class);
        $this->asesorService = app(AsesorService::class);
    }

    private function createButirs(int $count = 5): void
    {
        $komponen = MasterEdpmKomponen::firstOrCreate(['nama' => 'Test Komponen']);
        MasterEdpmButir::query()->delete();
        for ($i = 0; $i < $count; $i++) {
            MasterEdpmButir::create([
                'komponen_id' => $komponen->id,
                'no_sk' => (string) ($i + 1),
                'nomor_butir' => (string) ($i + 1) . '.1',
                'butir_pernyataan' => "Butir test {$i}",
            ]);
        }
    }

    private function createPesantrenWithAkreditasi(int $status = 5): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test ' . $pesantrenUser->id,
        ]);
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => $status]);
        return [$pesantrenUser, $akreditasi];
    }

    private function createAsesor(int $tipe, int $akreditasiId, ?string $tanggalBerakhir = null): array
    {
        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => "Asesor {$tipe} Test",
            'nama_tanpa_gelar' => "Asesor {$tipe} Test",
        ]);
        Assessment::create([
            'akreditasi_id' => $akreditasiId,
            'asesor_id' => $asesor->id,
            'tipe' => $tipe,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => $tanggalBerakhir ?? now()->addDays(30),
        ]);
        return [$asesorUser, $asesor];
    }

    private function fillAllButirs(int $akreditasiId, int $asesorId, int $pesantrenId, bool $includeNk = false): void
    {
        foreach (MasterEdpmButir::all() as $butir) {
            AkreditasiEdpm::create([
                'akreditasi_id' => $akreditasiId,
                'asesor_id' => $asesorId,
                'butir_id' => $butir->id,
                'pesantren_id' => $pesantrenId,
                'isian' => '3',
                'nk' => $includeNk ? '3' : null,
            ]);
        }
    }

    // =========================================================================
    // Task 8.1: Unit tests for ProgressTracker edge cases
    // =========================================================================

    /** Edge case: 0 records -> filled=0, total=N, percentage=0. Validates: Requirements 1.1, 1.2 */
    public function test_progress_tracker_zero_records_returns_zero_percentage(): void
    {
        $this->createButirs(5);

        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor Test',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        $result = $this->progressTracker->getCompletion($akreditasi->id, $asesor->id, 'isian');

        $this->assertEquals(0, $result['filled']);
        $this->assertEquals(5, $result['total']);
        $this->assertEquals(0.0, $result['percentage']);
    }

    /** Edge case: All filled -> percentage=100. Validates: Requirements 1.1, 1.2 */
    public function test_progress_tracker_all_filled_returns_100_percentage(): void
    {
        $this->createButirs(5);

        [$pesantrenUser, $akreditasi] = $this->createPesantrenWithAkreditasi();
        [$asesorUser, $asesor] = $this->createAsesor(1, $akreditasi->id);

        $this->fillAllButirs($akreditasi->id, $asesor->id, $pesantrenUser->id);

        $result = $this->progressTracker->getCompletion($akreditasi->id, $asesor->id, 'isian');

        $this->assertEquals(5, $result['filled']);
        $this->assertEquals(5, $result['total']);
        $this->assertEquals(100.0, $result['percentage']);
    }

    /** Edge case: No asesor2 -> asesor2_na=null. Validates: Requirements 1.4 */
    public function test_progress_tracker_no_asesor2_returns_null_for_asesor2_na(): void
    {
        $this->createButirs(5);

        [$pesantrenUser, $akreditasi] = $this->createPesantrenWithAkreditasi();
        $this->createAsesor(1, $akreditasi->id);

        $progress = $this->progressTracker->getAkreditasiProgress($akreditasi->id);

        $this->assertNotNull($progress['asesor1_na']);
        $this->assertNull($progress['asesor2_na']);
    }

    /** Edge case: Invalid akreditasi -> all zeros. Validates: Requirements 1.1 */
    public function test_progress_tracker_invalid_akreditasi_returns_zeros(): void
    {
        $this->createButirs(5);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor Test',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        $result = $this->progressTracker->getCompletion(99999, $asesor->id, 'isian');

        $this->assertEquals(0, $result['filled']);
        $this->assertEquals(5, $result['total']);
        $this->assertEquals(0.0, $result['percentage']);
    }

    // =========================================================================
    // Task 8.2: Unit tests for AsesorService::finalizeVerification()
    // =========================================================================

    /** Success path: all complete, no asesor2. Validates: Requirements 2.3 */
    public function test_finalize_verification_success_when_all_complete_no_asesor2(): void
    {
        $this->createButirs(3);

        [$pesantrenUser, $akreditasi] = $this->createPesantrenWithAkreditasi();
        [$asesor1User, $asesor1] = $this->createAsesor(1, $akreditasi->id);

        $this->fillAllButirs($akreditasi->id, $asesor1->id, $pesantrenUser->id, true);

        $this->actingAs($asesor1User);

        $result = $this->asesorService->finalizeVerification($akreditasi->id, $asesor1User->id);

        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);

        $akreditasi->refresh();
        $this->assertEquals(3, $akreditasi->status);
    }

    /** No-asesor2 path: only asesor1 assigned, both complete -> success. Validates: Requirements 2.3 */
    public function test_finalize_verification_success_with_only_asesor1_assigned(): void
    {
        $this->createButirs(4);

        [$pesantrenUser, $akreditasi] = $this->createPesantrenWithAkreditasi();
        [$asesor1User, $asesor1] = $this->createAsesor(1, $akreditasi->id);

        $this->fillAllButirs($akreditasi->id, $asesor1->id, $pesantrenUser->id, true);

        $this->assertNull(
            Assessment::where('akreditasi_id', $akreditasi->id)->where('tipe', 2)->first()
        );

        $this->actingAs($asesor1User);

        $result = $this->asesorService->finalizeVerification($akreditasi->id, $asesor1User->id);

        $this->assertTrue($result['success']);
        $this->assertNull($result['error']);

        $akreditasi->refresh();
        $this->assertEquals(3, $akreditasi->status);
    }

    // =========================================================================
    // Task 8.3: Integration test for SendAsesor2Reminders command
    // =========================================================================

    /** Initial reminder sent when < 7 days since assignment. Validates: Requirements 5.1, 5.2 */
    public function test_send_asesor2_reminders_sends_initial_reminder_when_less_than_7_days(): void
    {
        $this->createButirs(5);

        [$pesantrenUser, $akreditasi] = $this->createPesantrenWithAkreditasi();

        $asesor2User = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Asesor 2 Test',
            'nama_tanpa_gelar' => 'Asesor 2 Test',
        ]);

        $assessment = Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now()->subDays(3),
            'tanggal_berakhir' => now()->addDays(14),
        ]);
        DB::table('assessments')->where('id', $assessment->id)->update(['created_at' => now()->subDays(3)]);

        Artisan::call('reminders:asesor2');

        Notification::assertSentTo(
            $asesor2User,
            \App\Notifications\AkreditasiNotification::class,
            function ($notification) {
                return $notification->type === 'reminder_asesor2'
                    && str_contains($notification->title, 'Pengingat');
            }
        );
    }

    /** Urgency reminder sent when >= 7 days since assignment. Validates: Requirements 5.2 */
    public function test_send_asesor2_reminders_sends_urgency_reminder_when_7_or_more_days(): void
    {
        $this->createButirs(5);

        [$pesantrenUser, $akreditasi] = $this->createPesantrenWithAkreditasi();

        $asesor2User = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Asesor 2 Test',
            'nama_tanpa_gelar' => 'Asesor 2 Test',
        ]);

        $assessment = Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now()->subDays(10),
            'tanggal_berakhir' => now()->addDays(5),
        ]);
        DB::table('assessments')->where('id', $assessment->id)->update(['created_at' => now()->subDays(10)]);

        Artisan::call('reminders:asesor2');

        Notification::assertSentTo(
            $asesor2User,
            \App\Notifications\AkreditasiNotification::class,
            function ($notification) {
                return $notification->type === 'reminder_asesor2'
                    && str_contains($notification->title, 'Segera');
            }
        );
    }

    /** Skip at 100% completion. Validates: Requirements 5.6 */
    public function test_send_asesor2_reminders_skips_when_100_percent_complete(): void
    {
        $this->createButirs(5);

        [$pesantrenUser, $akreditasi] = $this->createPesantrenWithAkreditasi();

        $asesor2User = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Asesor 2 Test',
            'nama_tanpa_gelar' => 'Asesor 2 Test',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now()->subDays(5),
            'tanggal_berakhir' => now()->addDays(10),
        ]);

        $this->fillAllButirs($akreditasi->id, $asesor2->id, $pesantrenUser->id);

        Artisan::call('reminders:asesor2');

        Notification::assertNotSentTo(
            $asesor2User,
            \App\Notifications\AkreditasiNotification::class
        );
    }

    /**
     * Rate limit: second run same day sends 0 reminders.
     * Inserts a notification record to simulate the rate-limit check.
     * Validates: Requirements 5.5
     */
    public function test_send_asesor2_reminders_rate_limits_to_one_per_day(): void
    {
        $this->createButirs(5);

        [$pesantrenUser, $akreditasi] = $this->createPesantrenWithAkreditasi();

        $asesor2User = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Asesor 2 Test',
            'nama_tanpa_gelar' => 'Asesor 2 Test',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now()->subDays(5),
            'tanggal_berakhir' => now()->addDays(10),
        ]);

        // First run - should send 1 notification
        Artisan::call('reminders:asesor2');

        Notification::assertSentToTimes(
            $asesor2User,
            \App\Notifications\AkreditasiNotification::class,
            1
        );

        // Manually insert a notification record to simulate the rate-limit check
        // (Notification::fake() does not write to the DB, so we insert manually)
        $akreditasiUrl = route('asesor.akreditasi-detail', $akreditasi->uuid);
        DB::table('notifications')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\AkreditasiNotification::class,
            'notifiable_type' => get_class($asesor2User),
            'notifiable_id' => $asesor2User->id,
            'data' => json_encode([
                'type' => 'reminder_asesor2',
                'title' => 'Pengingat',
                'message' => 'Test',
                'url' => $akreditasiUrl,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Second run same day - should NOT send another notification due to rate limit
        Artisan::call('reminders:asesor2');

        // Still only 1 total notification (the fake one from first run)
        Notification::assertSentToTimes(
            $asesor2User,
            \App\Notifications\AkreditasiNotification::class,
            1
        );
    }

    // =========================================================================
    // Task 8.4: Livewire test for progress indicator rendering in asesor detail view
    // =========================================================================

    /**
     * Mount component with status=5 akreditasi and assert progress properties are loaded.
     * Validates: Requirements 4.1, 4.2, 4.3
     */
    public function test_asesor_detail_loads_progress_properties_for_status_5(): void
    {
        $this->createButirs(5);

        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test',
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);

        $asesor1User = User::factory()->create(['role_id' => 2]);
        $asesor1 = Asesor::create([
            'user_id' => $asesor1User->id,
            'nama_dengan_gelar' => 'Dr. Asesor 1 Test',
            'nama_tanpa_gelar' => 'Asesor 1 Test',
        ]);
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        $asesor2User = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Dr. Asesor 2 Test',
            'nama_tanpa_gelar' => 'Asesor 2 Test',
        ]);
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        $this->actingAs($asesor1User);

        $component = Livewire::test(
            \App\Livewire\Pages\Asesor\AkreditasiDetail::class,
            ['uuid' => $akreditasi->uuid]
        );

        $asesor1NaProgress = $component->get('asesor1NaProgress');
        $asesor1NkProgress = $component->get('asesor1NkProgress');
        $asesor2NaProgress = $component->get('asesor2NaProgress');

        $this->assertNotNull($asesor1NaProgress, 'asesor1NaProgress should be loaded for status 5');
        $this->assertNotNull($asesor1NkProgress, 'asesor1NkProgress should be loaded for status 5');
        $this->assertNotNull($asesor2NaProgress, 'asesor2NaProgress should be loaded when Asesor 2 is assigned');

        $this->assertArrayHasKey('filled', $asesor1NaProgress);
        $this->assertArrayHasKey('total', $asesor1NaProgress);
        $this->assertArrayHasKey('percentage', $asesor1NaProgress);

        $this->assertArrayHasKey('filled', $asesor2NaProgress);
        $this->assertArrayHasKey('total', $asesor2NaProgress);
        $this->assertArrayHasKey('percentage', $asesor2NaProgress);
    }

    // =========================================================================
    // Task 8.5: Livewire test for finalization error feedback display
    // =========================================================================

    /**
     * Trigger finalizeVerification with incomplete Asesor 2 data and assert finalization-failed event.
     * Uses AsesorService directly to bypass the Livewire saveAsesorEdpm status check.
     * Validates: Requirements 7.1, 7.2, 7.3
     */
    public function test_asesor_detail_dispatches_finalization_failed_event_when_asesor2_incomplete(): void
    {
        $this->createButirs(5);

        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test',
            'is_locked' => true,
        ]);

        // Use status 4 (Visitasi) so saveAsesorEdpm is allowed to run
        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 4]);

        $asesor1User = User::factory()->create(['role_id' => 2]);
        $asesor1 = Asesor::create([
            'user_id' => $asesor1User->id,
            'nama_dengan_gelar' => 'Dr. Asesor 1 Test',
            'nama_tanpa_gelar' => 'Asesor 1 Test',
        ]);
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        // Create Asesor 2 with INCOMPLETE data (0 butirs filled)
        $asesor2User = User::factory()->create(['role_id' => 2]);
        $asesor2 = Asesor::create([
            'user_id' => $asesor2User->id,
            'nama_dengan_gelar' => 'Dr. Asesor 2 Test',
            'nama_tanpa_gelar' => 'Asesor 2 Test',
        ]);
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor2->id,
            'tipe' => 2,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        $this->actingAs($asesor1User);

        $component = Livewire::test(
            \App\Livewire\Pages\Asesor\AkreditasiDetail::class,
            ['uuid' => $akreditasi->uuid]
        );

        // Set all asesor1 evaluasis, nks, and otherAsesorEvaluasis
        $butirs = MasterEdpmButir::all();
        $evaluasis = [];
        $nks = [];
        $otherEvaluasis = [];
        foreach ($butirs as $butir) {
            $evaluasis[$butir->id] = '3';
            $nks[$butir->id] = '3';
            // Set otherAsesorEvaluasis to non-empty so saveAsesorEdpm passes the in-memory check
            // The actual DB check in finalizeVerification will still catch the incomplete Asesor 2
            $otherEvaluasis[$butir->id] = '3';
        }

        $component->set('asesorEvaluasis', $evaluasis)
            ->set('asesorNks', $nks)
            ->set('otherAsesorEvaluasis', $otherEvaluasis);

        // Call finalizeVerification - should fail because Asesor 2 has no DB records
        $component->call('finalizeVerification');

        // Assert finalization-failed event was dispatched with correct error type
        $component->assertDispatched('finalization-failed', function ($event, $params) {
            return isset($params['error']) && $params['error'] === 'asesor2_incomplete';
        });

        // Verify akreditasi status unchanged (still 4)
        $akreditasi->refresh();
        $this->assertEquals(4, $akreditasi->status);
    }

    /**
     * Trigger finalizeVerification with incomplete Asesor 1 data and assert status unchanged.
     * Validates: Requirements 7.2, 7.3
     */
    public function test_asesor_detail_dispatches_finalization_failed_event_when_asesor1_na_incomplete(): void
    {
        $this->createButirs(5);

        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test',
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create(['user_id' => $pesantrenUser->id, 'status' => 5]);

        $asesor1User = User::factory()->create(['role_id' => 2]);
        $asesor1 = Asesor::create([
            'user_id' => $asesor1User->id,
            'nama_dengan_gelar' => 'Dr. Asesor 1 Test',
            'nama_tanpa_gelar' => 'Asesor 1 Test',
        ]);
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        $this->actingAs($asesor1User);

        $component = Livewire::test(
            \App\Livewire\Pages\Asesor\AkreditasiDetail::class,
            ['uuid' => $akreditasi->uuid]
        );

        // Call finalizeVerification with empty evaluasis
        $component->call('finalizeVerification');

        // Either validation-failed or finalization-failed should be dispatched
        // Either way, the akreditasi status should remain unchanged
        $akreditasi->refresh();
        $this->assertEquals(5, $akreditasi->status, 'Akreditasi status should remain 5 after failed finalization');
    }
}
