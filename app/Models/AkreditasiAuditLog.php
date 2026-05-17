<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AkreditasiAuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relationship: the akreditasi this log belongs to.
     */
    public function akreditasi(): BelongsTo
    {
        return $this->belongsTo(Akreditasi::class);
    }

    /**
     * Relationship: the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get human-readable label for an action type.
     */
    public static function getActionTypeLabel(string $actionType): string
    {
        return match ($actionType) {
            'status_changed' => 'Status Berubah',
            'asesor_assigned' => 'Asesor Ditugaskan',
            'asesor_reassigned' => 'Asesor Diganti',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'finalized' => 'Finalisasi',
            'banding_submitted' => 'Banding Diajukan',
            'deleted' => 'Dihapus',
            default => $actionType,
        };
    }

    /**
     * Get CSS badge class for an action type.
     */
    public static function getActionTypeBadgeClass(string $actionType): string
    {
        return match ($actionType) {
            'status_changed' => 'badge-light-primary',
            'asesor_assigned' => 'badge-light-info',
            'asesor_reassigned' => 'badge-light-warning',
            'approved' => 'badge-light-success',
            'rejected' => 'badge-light-danger',
            'finalized' => 'badge-light-primary',
            'banding_submitted' => 'badge-light-warning',
            'deleted' => 'badge-light-danger',
            default => 'badge-light-secondary',
        };
    }

    /**
     * Override update to enforce immutability.
     *
     * @throws \RuntimeException
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \RuntimeException('Audit logs are immutable');
    }

    /**
     * Override delete to enforce immutability.
     *
     * @throws \RuntimeException
     */
    public function delete(): bool
    {
        throw new \RuntimeException('Audit logs cannot be deleted');
    }

    /**
     * Override save to prevent updates on existing records.
     *
     * @throws \RuntimeException
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \RuntimeException('Audit logs are immutable');
        }

        return parent::save($options);
    }
}
