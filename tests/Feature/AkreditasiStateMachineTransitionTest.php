<?php

namespace Tests\Feature;

use App\Exceptions\InvalidTransitionException;
use App\Exceptions\StaleStateException;
use App\Models\Akreditasi;
use App\Models\AkreditasiAuditLog;
use App\Models\Pesantren;
use App\Models\User;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration tests for AkreditasiStateMachine::transition.
 *
 * Validates Requirements 1.3, 1.4, 1.5 — the transition method must
 * enforce the permitted-transition map, write an audit-trail entry, and
 * detect concurrent modifications via optimistic locking.
 */
class AkreditasiStateMachineTransitionTest extends TestCase
{
    use RefreshDatabase;

    private AkreditasiStateMachine $sm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->sm = app(AkreditasiStateMachine::class);
    }

    private function createAkreditasi(int $status = 6): Akreditasi
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test ' . $pesantrenUser->id,
        ]);

        return Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ]);
    }

    private function createAdmin(): User
    {
        return User::factory()->create(['role_id' => 1]);
    }

    public function test_transition_succeeds_for_permitted_path_and_persists_new_status(): void
    {
        $akreditasi = $this->createAkreditasi(6);
        $admin = $this->createAdmin();

        $this->sm->transition($akreditasi, 5, $admin);

        $this->assertSame(5, (int) $akreditasi->status);
        $this->assertSame(5, (int) Akreditasi::find($akreditasi->id)->status);
    }

    public function test_transition_throws_invalid_transition_for_unpermitted_path(): void
    {
        $akreditasi = $this->createAkreditasi(6);
        $admin = $this->createAdmin();

        try {
            // 6 -> 4 is not in the TRANSITIONS map.
            $this->sm->transition($akreditasi, 4, $admin);
            $this->fail('Expected InvalidTransitionException was not thrown.');
        } catch (InvalidTransitionException $e) {
            $this->assertSame(6, $e->from);
            $this->assertSame(4, $e->to);
        }

        // Status must be preserved on rejection.
        $this->assertSame(6, (int) Akreditasi::find($akreditasi->id)->status);
    }

    public function test_transition_writes_audit_trail_entry_with_metadata(): void
    {
        $akreditasi = $this->createAkreditasi(6);
        $admin = $this->createAdmin();

        $this->sm->transition($akreditasi, 5, $admin);

        $log = AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)
            ->where('action_type', 'status_changed')
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'Audit trail entry must be created for the transition.');
        $this->assertSame($admin->id, $log->user_id);
        $this->assertIsArray($log->metadata);
        $this->assertSame('status_transition', $log->metadata['action']);
        $this->assertSame(6, $log->metadata['from_status']);
        $this->assertSame(5, $log->metadata['to_status']);
        $this->assertSame($admin->id, $log->metadata['user_id']);
        $this->assertArrayHasKey('timestamp', $log->metadata);
    }

    public function test_transition_throws_stale_state_when_record_modified_concurrently(): void
    {
        $akreditasi = $this->createAkreditasi(6);
        $admin = $this->createAdmin();

        // Simulate a concurrent modification by bumping updated_at on the row
        // after the model was loaded. The optimistic-lock UPDATE in
        // transition() will then match zero rows.
        DB::table('akreditasis')
            ->where('id', $akreditasi->id)
            ->update(['updated_at' => now()->addMinute()]);

        $this->expectException(StaleStateException::class);

        try {
            $this->sm->transition($akreditasi, 5, $admin);
        } finally {
            // Status must remain unchanged (still 6).
            $this->assertSame(6, (int) Akreditasi::find($akreditasi->id)->status);
        }
    }
}
