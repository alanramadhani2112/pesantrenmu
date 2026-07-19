<?php

namespace Tests\Feature\E2E;

use App\Exceptions\InvalidTransitionException;
use App\Exceptions\StaleStateException;
use App\Models\Akreditasi;
use App\Models\AkreditasiAuditLog;
use App\Models\User;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StateMachineEdgeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AkreditasiStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(TestDataSeeder::class);

        $this->admin = User::where('email', 'bf.admin@test.local')->firstOrFail();
        $this->stateMachine = new AkreditasiStateMachine;
    }

    public function test_state_machine_allows_only_canonical_transitions(): void
    {
        $expected = [
            Akreditasi::STATUS_PENGAJUAN => [Akreditasi::STATUS_VERIFIKASI_BERKAS],
            Akreditasi::STATUS_VERIFIKASI_BERKAS => [Akreditasi::STATUS_ASSESSMENT, Akreditasi::STATUS_DITOLAK],
            Akreditasi::STATUS_ASSESSMENT => [Akreditasi::STATUS_VISITASI, Akreditasi::STATUS_DITOLAK],
            Akreditasi::STATUS_VISITASI => [Akreditasi::STATUS_PASCA_VISITASI],
            Akreditasi::STATUS_PASCA_VISITASI => [Akreditasi::STATUS_VALIDASI_ADMIN],
            Akreditasi::STATUS_VALIDASI_ADMIN => [Akreditasi::STATUS_SELESAI, Akreditasi::STATUS_DITOLAK],
            Akreditasi::STATUS_DITOLAK => [Akreditasi::STATUS_BANDING],
            Akreditasi::STATUS_BANDING => [Akreditasi::STATUS_VALIDASI_ADMIN, Akreditasi::STATUS_DITOLAK],
            Akreditasi::STATUS_SELESAI => [],
        ];

        foreach ($expected as $from => $targets) {
            $this->assertSame($targets, $this->stateMachine->getPermittedTransitions($from));
        }

        $this->assertFalse($this->stateMachine->canTransition(Akreditasi::STATUS_PENGAJUAN, Akreditasi::STATUS_ASSESSMENT));
        $this->assertFalse($this->stateMachine->canTransition(Akreditasi::STATUS_SELESAI, Akreditasi::STATUS_BANDING));
    }

    public function test_invalid_transition_throws_and_preserves_status(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-001');

        try {
            $this->stateMachine->transition($akreditasi, Akreditasi::STATUS_ASSESSMENT, $this->admin);
            $this->fail('Invalid transition should throw.');
        } catch (InvalidTransitionException) {
            $this->assertSame(Akreditasi::STATUS_PENGAJUAN, (int) $akreditasi->fresh()->status);
        }
    }

    public function test_valid_transition_records_audit_log(): void
    {
        $akreditasi = $this->scenario('BF-HAPPY-001');

        $this->stateMachine->transition($akreditasi, Akreditasi::STATUS_VERIFIKASI_BERKAS, $this->admin);

        $this->assertSame(Akreditasi::STATUS_VERIFIKASI_BERKAS, (int) $akreditasi->fresh()->status);

        $log = AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)
            ->where('action_type', 'status_changed')
            ->firstOrFail();

        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame(Akreditasi::STATUS_PENGAJUAN, $log->metadata['from_status']);
        $this->assertSame(Akreditasi::STATUS_VERIFIKASI_BERKAS, $log->metadata['to_status']);
        $this->assertSame('status_transition', $log->metadata['action']);
    }

    public function test_stale_transition_throws_and_preserves_newer_status(): void
    {
        $stale = $this->scenario('BF-HAPPY-001');
        DB::table('akreditasis')
            ->where('id', $stale->id)
            ->update(['updated_at' => now()->addMinute()]);

        try {
            $this->stateMachine->transition($stale, Akreditasi::STATUS_VERIFIKASI_BERKAS, $this->admin);
            $this->fail('Stale transition should throw.');
        } catch (StaleStateException) {
            $this->assertSame(Akreditasi::STATUS_PENGAJUAN, (int) $stale->fresh()->status);
            $this->assertSame(0, AkreditasiAuditLog::where('akreditasi_id', $stale->id)->where('action_type', 'status_changed')->count());
        }
    }

    private function scenario(string $code): Akreditasi
    {
        return Akreditasi::where('catatan', 'like', "[{$code}]%")
            ->firstOrFail();
    }
}
