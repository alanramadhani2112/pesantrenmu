<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AkreditasiRejection extends Model
{
    protected $fillable = [
        'akreditasi_id',
        'user_id',
        'type',
        'items',
        'categories',
        'explanation',
        'rejection_number',
        'perbaikan_deadline',
        'perbaikan_submitted_at',
        'status',
    ];

    protected $casts = [
        'items' => 'array',
        'categories' => 'array',
        'perbaikan_deadline' => 'datetime',
        'perbaikan_submitted_at' => 'datetime',
    ];

    /**
     * Get the akreditasi that this rejection belongs to.
     */
    public function akreditasi(): BelongsTo
    {
        return $this->belongsTo(Akreditasi::class);
    }

    /**
     * Get the user (Asesor 1 or Admin) who created this rejection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this rejection is active (pending perbaikan).
     */
    public function isActive(): bool
    {
        return $this->type === 'asesor' && $this->status === 'pending';
    }

    /**
     * Check if perbaikan deadline has passed.
     */
    public function isExpired(): bool
    {
        return $this->perbaikan_deadline !== null
            && now()->greaterThan($this->perbaikan_deadline)
            && $this->status === 'pending';
    }

    /**
     * Get remaining days until perbaikan deadline.
     */
    public function daysUntilDeadline(): int
    {
        if ($this->perbaikan_deadline === null) {
            return 0;
        }
        return max(0, (int) now()->diffInDays($this->perbaikan_deadline, false));
    }
}
