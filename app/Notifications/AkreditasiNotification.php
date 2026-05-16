<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * AkreditasiNotification
 *
 * Audit fix PR-1 (P0): implements ShouldQueue so the WebPush + database +
 * broadcast channels run on the queue worker instead of blocking the user
 * request thread. If the WebPush provider hiccups, the user-facing request
 * still returns fast; the notification will retry on the failed_jobs table.
 *
 * Operational requirement: a queue worker must be running in production
 * (`php artisan queue:work --tries=3`). When QUEUE_CONNECTION=sync (e.g. in
 * tests or local dev) the dispatch falls back to inline execution, so this
 * change is safe to apply across all environments.
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
     * Backoff in seconds between attempts: 10s, 30s, 60s.
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public $type;

    public $message;

    public $url;

    public $title;

    /**
     * Create a new notification instance.
     */
    public function __construct($type, $title, $message, $url = '#')
    {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
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
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
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
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ]);
    }
}
