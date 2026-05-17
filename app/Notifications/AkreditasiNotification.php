<?php

namespace App\Notifications;

use App\Models\FailedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * AkreditasiNotification
 *
 * Implements ShouldQueue so the WebPush + database + broadcast channels run
 * on the queue worker instead of blocking the user request thread.
 *
 * Queue: 'notifications' (dedicated queue for independent scaling/monitoring).
 * Retries: 3 attempts with exponential backoff (10s, 60s, 300s).
 * On permanent failure: writes to failed_notifications table via failed().
 */
class AkreditasiNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the notification job may be attempted before it lands
     * in failed_jobs. Three attempts is enough to ride out brief WebPush
     * provider blips without spamming users on a permanent outage.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds between attempts: 10s, 60s, 300s (exponential).
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public $type;

    public $message;

    public $url;

    public $title;

    /**
     * The notifiable's primary key, stored for use in the failed() handler.
     * We cannot rely on the notifiable object being available in failed()
     * because the notification may have been serialized/deserialized.
     */
    public ?int $notifiableId = null;

    /**
     * Create a new notification instance.
     */
    public function __construct($type, $title, $message, $url = '#')
    {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;

        // Route to the dedicated notifications queue
        $this->onQueue('notifications');
    }

    /**
     * Called by Laravel when all retry attempts are exhausted.
     * Persists a record to failed_notifications for admin review.
     */
    public function failed(\Throwable $exception): void
    {
        FailedNotification::create([
            'notification_type' => $this->type,
            'notifiable_id'     => $this->notifiableId,
            'payload'           => [
                'type'    => $this->type,
                'title'   => $this->title,
                'message' => $this->message,
                'url'     => $this->url,
            ],
            'failure_reason' => $exception->getMessage(),
            'failed_at'      => now(),
            'status'         => 'pending',
        ]);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Capture the notifiable ID so failed() can reference it
        $this->notifiableId = $notifiable->getKey();

        return ['database', WebPushChannel::class, 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'    => $this->type,
            'title'   => $this->title,
            'message' => $this->message,
            'url'     => $this->url,
        ];
    }

    /**
     * Get the web push representation of the notification.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $notification
     * @return \NotificationChannels\WebPush\WebPushMessage
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon('/logo.png')
            ->body($this->message)
            ->action('Lihat Detail', $this->url);
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'type'    => $this->type,
            'title'   => $this->title,
            'message' => $this->message,
            'url'     => $this->url,
        ]);
    }
}
