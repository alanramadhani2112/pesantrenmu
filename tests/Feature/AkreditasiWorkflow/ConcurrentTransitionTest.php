<?php

namespace Tests\Feature\AkreditasiWorkflow;

use App\Exceptions\StaleStateException;
use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\User;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Task 14.4 — Integration test for concurrent transition conflict.
 *
 * Validates Requirement 1.5 — optimistic locking must reject the second
 * concurrent transition attempt and leave the status unchanged.
 */
class ConcurrentTransitionTest extends TestCase
{
    use RefreshDatabase;

    private AkreditasiStateMachine $sm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->sm = app(AkreditasiStateMachine::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createAkreditasi(int $status = 6): Akreditasi
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Concurrent Test',
        ]);

        return Akreditasi::create([
            'user_id' => $user->id,
            'status' => $status,
        ]);
    }

    private function createAdmin(): User
    {
        return User::factory()->create(['role_id' => 1]);
    }

    // =========================================================================
    // Tests
    // =========================================================================

    /**
     * Simulates a concurrent modification by bumping updated_at after the
     * model is loaded. The state machine's optimistic-lock UPDATE will then
     * match zero rows and throw StaleStateException.
     *
     * Validates Requirement 1.5.
     */
    public function test_stale_state_exception_thrown_when_record_modified_concurrently(): void
    {
        $akreditasi = $this->createAkreditasi(6);
        $admin = $this->createAdmin();

        // Simulate concurrent modification: bump updated_at on the row
        // AFTER the model was loaded, so the optimistic-lock WHERE clause
        // will not match.
        DB::table('akreditasis')
            ->where('id', $akreditasi->id)
            ->update(['updated_at' => now()->addMinute()]);

        $this->expectException(StaleStateException::class);

        $this->sm->transition($akreditasi, AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS, $admin);
    }

    /**
     * After a StaleStateException the status in the database must remain
     * unchanged (still 6).
     *
     * Validates Requirement 1.5.
     */
    public function test_status_remains_unchanged_after_stale_state_exception(): void
    {
        $akreditasi = $this->createAkreditasi(6);
        $admin = $this->createAdmin();

        // Bump updated_at to simulate concurrent write
        DB::table('akreditasis')
            ->where('id', $akreditasi->id)
            ->update(['updated_at' => now()->addMinute()]);

        try {
            $this->sm->transition($akreditasi, AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS, $admin);
        } catch (StaleStateException) {
            // Expected — swallow so we can assert below
        }

        // Status must still be 6 in the database
        $this->assertSame(6, (int) Akreditasi::find($akreditasi->id)->status);
    }

    /**
     * The first transition succeeds; a second attempt on the same stale model
     * (updated_at no longer matches) throws StaleStateException.
     *
     * Validates Requirement 1.5.
     */
    public function test_first_transition_succeeds_second_on_stale_model_fails(): void
    {
        $akreditasi = $this->createAkreditasi(6);
        $admin = $this->createAdmin();

        // First transition succeeds normally
        $this->sm->transition($akreditasi, AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS, $admin);
        $this->assertSame(5, (int) Akreditasi::find($akreditasi->id)->status);

        // Reload a fresh copy at status 5
        $freshCopy = Akreditasi::find($akreditasi->id);

        // Simulate another concurrent write bumping updated_at
        DB::table('akreditasis')
            ->where('id', $freshCopy->id)
            ->update(['updated_at' => now()->addMinutes(2)]);

        // Second transition on the stale fresh copy must fail
        $this->expectException(StaleStateException::class);
        $this->sm->transition($freshCopy, AkreditasiStateMachine::STATUS_ASSESSMENT, $admin);
    }

    /**
     * Verify that the StaleStateException carries the correct akreditasi ID.
     *
     * Validates Requirement 1.5.
     */
    public function test_stale_state_exception_contains_akreditasi_id(): void
    {
        $akreditasi = $this->createAkreditasi(6);
        $admin = $this->createAdmin();

        DB::table('akreditasis')
            ->where('id', $akreditasi->id)
            ->update(['updated_at' => now()->addMinute()]);

        try {
            $this->sm->transition($akreditasi, AkreditasiStateMachine::STATUS_VERIFIKASI_BERKAS, $admin);
            $this->fail('Expected StaleStateException was not thrown.');
        } catch (StaleStateException $e) {
            $this->assertSame($akreditasi->id, $e->akreditasiId);
        }
    }
}
