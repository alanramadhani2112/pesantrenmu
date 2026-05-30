<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banding extends Model
{
    protected $fillable = [
        'akreditasi_id',
        'user_id',
        'reviewer_id',
        'status',
        'alasan',
        'keputusan',
        'review_deadline',
        'decided_at',
    ];

    protected $casts = [
        'review_deadline' => 'datetime',
        'decided_at' => 'datetime',
    ];

    /**
     * The akreditasi this banding belongs to.
     */
    public function akreditasi(): BelongsTo
    {
        return $this->belongsTo(Akreditasi::class);
    }

    /**
     * The pesantren user who submitted the banding.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin reviewer assigned to this banding.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Check if this banding is overdue.
     * Returns true if status is 'under_review' AND review_deadline is not null AND now() is past review_deadline.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'under_review'
            && $this->review_deadline !== null
            && now()->greaterThan($this->review_deadline);
    }

    /**
     * Get the number of days past the deadline.
     * Returns 0 if not overdue.
     */
    public function daysOverdue(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return (int) $this->review_deadline->diffInDays(now(), false);
    }

    /**
     * Get the number of days until the deadline.
     * Returns negative if overdue, 0 if no deadline set.
     */
    public function daysUntilDeadline(): int
    {
        if ($this->review_deadline === null) {
            return 0;
        }

        return (int) now()->diffInDays($this->review_deadline, false);
    }
}
