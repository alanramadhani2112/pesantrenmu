<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;

class FailedNotification extends Model
{
    protected $table = 'failed_notifications';

    protected $fillable = [
        'notification_type',
        'notifiable_id',
        'payload',
        'failure_reason',
        'failed_at',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'failed_at'   => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * The recipient user of the failed notification.
     */
    public function notifiable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }

    /**
     * Re-queue the notification and mark this record as resolved.
     *
     * If the user no longer exists, this is a no-op (graceful degradation).
     */
    public function retry(): void
    {
        $payload = $this->payload;
        $user = User::find($this->notifiable_id);

        if ($user) {
            $user->notify(new \App\Notifications\AkreditasiNotification(
                $payload['type'],
                $payload['title'],
                $payload['message'],
                $payload['url'] ?? '#'
            ));

            $this->update([
                'status'      => 'resolved',
                'resolved_at' => now(),
            ]);
        }
    }

    /**
     * Mark this record as dismissed (admin acknowledged, no retry needed).
     */
    public function dismiss(): void
    {
        $this->update(['status' => 'dismissed']);
    }
}
