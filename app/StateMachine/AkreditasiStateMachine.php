<?php

namespace App\StateMachine;

use App\Events\AkreditasiTransitioned;
use App\Exceptions\InvalidTransitionException;
use App\Exceptions\StaleStateException;
use App\Models\Akreditasi;
use App\Models\AkreditasiAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Central state machine for Akreditasi workflow.
 *
 * Defines the permitted set of status transitions for an akreditasi record
 * and provides the canonical mutation entry point that performs the
 * transition under optimistic locking with a recorded audit trail.
 *
 * Validates Requirements 1.1, 1.2, 1.3, 1.4, 1.5.
 */
class AkreditasiStateMachine
{
    /**
     * Permitted transitions: from_status => [allowed_to_statuses].
     *
     * Status values:
     *   6  Pengajuan
     *   5  Verifikasi Berkas
     *   4  Review Asesor
     *   3  Visitasi
     *   2  Penilaian Pasca Visitasi
     *   1  Validasi Admin
     *   0  Selesai
     *  -1  Ditolak
     *  -2  Banding
     */
    public const TRANSITIONS = [
        6  => [5],
        5  => [4, -1],
        4  => [3, -1],
        3  => [2],
        2  => [1],
        1  => [0, -1],
        -1 => [-2],
        -2 => [1, -1],
    ];

    public const STATUS_PENGAJUAN = 6;
    public const STATUS_VERIFIKASI_BERKAS = 5;
    public const STATUS_ASSESSMENT = 4;
    public const STATUS_VISITASI = 3;
    public const STATUS_PASCA_VISITASI = 2;
    public const STATUS_VALIDASI_ADMIN = 1;
    public const STATUS_SELESAI = 0;
    public const STATUS_DITOLAK = -1;
    public const STATUS_BANDING = -2;

    public function __construct()
    {
    }

    /**
     * Return the list of statuses that the given current status may transition to.
     * Returns an empty array for terminal or unknown statuses.
     *
     * Validates Requirement 1.1.
     */
    public function getPermittedTransitions(int $currentStatus): array
    {
        return self::TRANSITIONS[$currentStatus] ?? [];
    }

    /**
     * Check if a transition from one status to another is permitted by the state machine.
     *
     * Returns true if and only if the transition (from -> to) exists in the
     * TRANSITIONS map. Returns false for unknown source statuses or
     * unpermitted target statuses.
     *
     * Validates Requirement 1.2.
     */
    public function canTransition(int $from, int $to): bool
    {
        if (!isset(self::TRANSITIONS[$from])) {
            return false;
        }

        return in_array($to, self::TRANSITIONS[$from], true);
    }

    /**
     * Atomically transition an akreditasi record to a new status.
     *
     * Behavior:
     *  - Validates the transition (current → toStatus) against the permitted
     *    map; throws {@see InvalidTransitionException} if not permitted.
     *  - Wraps the mutation in a DB transaction.
     *  - Uses optimistic locking by including the current updated_at value in
     *    the WHERE clause of the UPDATE. If zero rows are affected the record
     *    has been modified concurrently and {@see StaleStateException} is
     *    thrown.
     *  - Records a status_transition audit log entry containing
     *    from_status, to_status, actor user_id, and timestamp.
     *  - Refreshes the in-memory model with the new status and updated_at.
     *
     * Validates Requirements 1.3, 1.4, 1.5.
     *
     * @throws InvalidTransitionException When the transition is not permitted.
     * @throws StaleStateException        When the record was modified concurrently.
     */
    public function transition(Akreditasi $akreditasi, int $toStatus, User $actor): void
    {
        $fromStatus = (int) $akreditasi->status;

        if (!$this->canTransition($fromStatus, $toStatus)) {
            throw new InvalidTransitionException($fromStatus, $toStatus);
        }

        DB::transaction(function () use ($akreditasi, $fromStatus, $toStatus, $actor) {
            $originalUpdatedAt = $akreditasi->updated_at;

            // Format the timestamp the same way the database stores it so
            // the equality match in the WHERE clause is deterministic.
            $originalUpdatedAtFormatted = $originalUpdatedAt instanceof \DateTimeInterface
                ? $originalUpdatedAt->format('Y-m-d H:i:s')
                : (string) $originalUpdatedAt;

            $now = now();

            // Optimistic-lock UPDATE: only update if updated_at has not changed
            // since the model was loaded. If another request already wrote to
            // this row, rowsAffected will be 0.
            $rowsAffected = DB::table('akreditasis')
                ->where('id', $akreditasi->id)
                ->where('updated_at', $originalUpdatedAtFormatted)
                ->update([
                    'status' => $toStatus,
                    'updated_at' => $now,
                ]);

            if ($rowsAffected === 0) {
                throw new StaleStateException($akreditasi->id);
            }

            $this->writeAuditTrail($akreditasi->id, $fromStatus, $toStatus, $actor, $now);

            // Sync the in-memory model with the row we just wrote.
            $akreditasi->refresh();
        });

        // Task 12.3: Dispatch AkreditasiTransitioned event after the transaction commits.
        // Fired outside the transaction so listeners see the committed state.
        event(new AkreditasiTransitioned($akreditasi, $fromStatus, $toStatus, $actor));
    }

    /**
     * Persist the transition to the audit trail.
     *
     * Writes directly to the {@see AkreditasiAuditLog} table so the recorded
     * actor matches the explicit `$actor` parameter passed in (rather than
     * the Auth::id() used by the generic {@see AuditTrailService::log()}
     * helper). The state machine is invoked from background jobs and CLI
     * commands where the auth user may differ from the workflow actor.
     *
     * The action_type is 'status_changed' to slot into the existing
     * timeline; full transition metadata is preserved in `metadata`.
     */
    private function writeAuditTrail(
        int $akreditasiId,
        int $fromStatus,
        int $toStatus,
        User $actor,
        \DateTimeInterface $timestamp,
    ): void {
        try {
            $log = new AkreditasiAuditLog();
            $log->akreditasi_id = $akreditasiId;
            $log->user_id = $actor->id;
            $log->action_type = 'status_changed';
            $log->old_value = Akreditasi::getStatusLabel($fromStatus);
            $log->new_value = Akreditasi::getStatusLabel($toStatus);
            $log->metadata = [
                'akreditasi_id' => $akreditasiId,
                'action' => 'status_transition',
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'user_id' => $actor->id,
                'timestamp' => $timestamp->format(\DateTimeInterface::ATOM),
            ];
            $log->ip_address = $this->resolveIpAddress();
            $log->user_agent = $this->resolveUserAgent();
            $log->created_at = $timestamp;
            $log->save();
        } catch (\Throwable $e) {
            // TODO: Audit infrastructure unavailable — fall back to structured
            // log so the transition is still observable. Replace once audit
            // logging is guaranteed to succeed.
            Log::info('akreditasi.status_transition', [
                'akreditasi_id' => $akreditasiId,
                'action' => 'status_transition',
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'user_id' => $actor->id,
                'timestamp' => $timestamp->format(\DateTimeInterface::ATOM),
                'audit_write_error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveIpAddress(): string
    {
        try {
            return request()->ip() ?? 'system';
        } catch (\Throwable) {
            return 'system';
        }
    }

    private function resolveUserAgent(): string
    {
        try {
            return request()->userAgent() ?? 'system';
        } catch (\Throwable) {
            return 'system';
        }
    }
}
