<?php

namespace App\Services;

use App\Models\AkreditasiAuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class AuditTrailService
{
    /**
     * Allowed action types for audit logging.
     */
    public const ALLOWED_ACTION_TYPES = [
        'status_changed',
        'asesor_assigned',
        'asesor_reassigned',
        'approved',
        'rejected',
        'finalized',
        'banding_submitted',
        'deleted',
    ];

    /**
     * Create an audit log entry for an akreditasi action.
     */
    public function log(
        int $akreditasiId,
        string $actionType,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?array $metadata = null
    ): AkreditasiAuditLog {
        $this->validateActionType($actionType);

        $log = new AkreditasiAuditLog();
        $log->akreditasi_id = $akreditasiId;
        $log->user_id = Auth::id();
        $log->action_type = $actionType;
        $log->old_value = $oldValue;
        $log->new_value = $newValue;
        $log->metadata = $metadata;
        $log->ip_address = $this->resolveIpAddress();
        $log->user_agent = $this->resolveUserAgent();
        $log->created_at = now();
        $log->save();

        return $log;
    }

    /**
     * Get paginated timeline of audit logs for an akreditasi with optional filters.
     *
     * Supported filters:
     * - action_type: string or array of action types
     * - user_id: int
     * - date_from: string (Y-m-d)
     * - date_to: string (Y-m-d)
     */
    public function getTimeline(
        int $akreditasiId,
        array $filters = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = AkreditasiAuditLog::where('akreditasi_id', $akreditasiId)
            ->with('user');

        if (!empty($filters['action_type'])) {
            $actionTypes = is_array($filters['action_type'])
                ? $filters['action_type']
                : [$filters['action_type']];
            $query->whereIn('action_type', $actionTypes);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Validate that the action type is in the allowed list.
     *
     * @throws \InvalidArgumentException
     */
    protected function validateActionType(string $actionType): void
    {
        if (!in_array($actionType, self::ALLOWED_ACTION_TYPES, true)) {
            throw new \InvalidArgumentException(
                "Invalid action type '{$actionType}'. Allowed types: " . implode(', ', self::ALLOWED_ACTION_TYPES)
            );
        }
    }

    /**
     * Resolve the IP address from the current request.
     */
    protected function resolveIpAddress(): string
    {
        try {
            return request()->ip() ?? 'system';
        } catch (\Throwable) {
            return 'system';
        }
    }

    /**
     * Resolve the user agent from the current request.
     */
    protected function resolveUserAgent(): string
    {
        try {
            return request()->userAgent() ?? 'system';
        } catch (\Throwable) {
            return 'system';
        }
    }
}
