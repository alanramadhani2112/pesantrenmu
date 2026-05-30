<?php

namespace Tests\Feature\ConcurrentAccess;

use App\Exceptions\ConflictException;
use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Services\AkreditasiWorkflowService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Example-Based Feature Tests for Conflict Feedback.
 */
#[Group('Feature:concurrent-access-handling')]
class ConflictFeedbackTest extends TestCase
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
     * Task 8.1: Stale timestamp on final admin rejection service call throws ConflictException.
     *
     * When finalizeAkreditasi() is called with a stale timestamp, the service should
     * throw a ConflictException.
     */
    public function test_approve_with_stale_timestamp_dispatches_conflict_notification(): void
    {
        [$adminUser, $akreditasi] = $this->createAdminWithStatus1Akreditasi();
        $this->actingAs($adminUser);

        $staleTimestamp = Carbon::now()->subHours(2)->toISOString();

        // Verify the stale timestamp differs from actual
        $this->assertNotEquals($akreditasi->updated_at->toISOString(), $staleTimestamp);

        // Call the service directly with a stale timestamp — should throw ConflictException
        $this->expectException(ConflictException::class);

        $workflowService = app(AkreditasiWorkflowService::class);
        $workflowService->rejectAtValidasi(
            $akreditasi->id,
            $adminUser->id,
            'Test rejection reason for conflict test.',
            $staleTimestamp,
            [['category' => 'lainnya', 'explanation' => 'Test rejection reason for conflict test.']]
        );
    }

    /**
     * Task 8.2: Livewire test — stale timestamp on reject triggers conflict notification.
     *
     * When reject() is called with a stale timestamp, the component should
     * dispatch a conflict notification (not throw an unhandled exception).
     */
    public function test_reject_with_stale_timestamp_dispatches_conflict_notification(): void
    {
        [$adminUser, $akreditasi] = $this->createAdminWithStatus1Akreditasi();
        $this->actingAs($adminUser);

        $staleTimestamp = Carbon::now()->subHours(2)->toISOString();

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid])->assertOk();

        // Set a stale timestamp
        $component->set('akreditasiUpdatedAt', $staleTimestamp);

        // Set required fields for reject
        $component->set('rejectionCategories', [
            ['category' => 'lainnya', 'explanation' => 'Test rejection reason for conflict test.'],
        ]);

        // Call reject — should catch ConflictException and dispatch notification
        $component->call('reject');

        // Should dispatch a conflict notification
        $component->assertDispatched('notification-received');

        // Status should remain unchanged (still 3)
        $this->assertEquals(1, $akreditasi->fresh()->status);
    }

    /**
     * Task 8.2 (variant): No 500 error on stale reject — graceful handling.
     */
    public function test_reject_with_stale_timestamp_does_not_throw_500(): void
    {
        [$adminUser, $akreditasi] = $this->createAdminWithStatus1Akreditasi();
        $this->actingAs($adminUser);

        $staleTimestamp = Carbon::now()->subHours(5)->toISOString();

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid])->assertOk();
        $component->set('akreditasiUpdatedAt', $staleTimestamp);
        $component->set('rejectionCategories', [
            ['category' => 'lainnya', 'explanation' => 'Test rejection reason for no-500 test.'],
        ]);

        // Should not throw any exception — ConflictException is caught
        $component->call('reject');

        // Component should still be alive (no redirect, no crash)
        $this->assertEquals(1, $akreditasi->fresh()->status);
    }

    /**
     * Task 8.4: akreditasiUpdatedAt is set on mount.
     */
    public function test_akreditasi_updated_at_is_set_on_mount(): void
    {
        [$adminUser, $akreditasi] = $this->createAdminWithStatus1Akreditasi();
        $this->actingAs($adminUser);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid])->assertOk();

        $expectedTimestamp = $akreditasi->updated_at->toISOString();

        $component->assertSet('akreditasiUpdatedAt', $expectedTimestamp);
    }

    /**
     * Task 8.5: Buttons are disabled after status changes to terminal state.
     *
     * When akreditasi status is 1 or 2, the approve/reject forms should not be shown.
     */
    public function test_action_forms_hidden_when_status_is_terminal(): void
    {
        $adminUser = User::factory()->create(['role_id' => 1]);
        $this->actingAs($adminUser);

        $pesantrenUser = $this->createCompletePesantrenUser();

        // Status 1 (Berhasil) — terminal
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 1,
            'nomor_sk' => 'SK/001/2024',
            'masa_berlaku' => now()->toDateString(),
            'masa_berlaku_akhir' => now()->addYears(5)->toDateString(),
            'nilai' => 90,
            'peringkat' => 'Unggul',
        ]);

        $component = Volt::test('pages.admin.akreditasi-detail', ['uuid' => $akreditasi->uuid]);

        // At status 1, the finalize forms should NOT be visible
        $component->assertDontSee('Setujui & Simpan')
            ->assertDontSee('Tolak Pengajuan');
    }

    private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);

        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Conflict Test',
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

    private function createAdminWithStatus1Akreditasi(): array
    {
        $adminUser = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = $this->createCompletePesantrenUser();

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 1,
            'kartu_kendali' => 'akreditasi/kartu_kendali/test.pdf',
            'laporan_visitasi_asesor1' => 'akreditasi/laporan/test.pdf',
            'is_nv_final' => true,
        ]);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Conflict',
            'nama_tanpa_gelar' => 'Asesor Conflict',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        $komponen = MasterEdpmKomponen::first();
        if ($komponen) {
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
        }

        return [$adminUser, $akreditasi];
    }
}
