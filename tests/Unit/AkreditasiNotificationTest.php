<?php

namespace Tests\Unit;

use App\Models\FailedNotification;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for AkreditasiNotification — verifies ShouldQueue implementation,
 * queue configuration, retry logic, and backoff values.
 *
 * Validates: Requirements 2.1, 2.2, 3.1, 3.2
 */
class AkreditasiNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_implements_should_queue(): void
    {
        $notification = new AkreditasiNotification('test', 'Test Title', 'Test message');

        $this->assertInstanceOf(ShouldQueue::class, $notification);
    }

    public function test_tries_is_3(): void
    {
        $notification = new AkreditasiNotification('test', 'Test Title', 'Test message');

        $this->assertSame(3, $notification->tries);
    }

    public function test_queue_is_notifications(): void
    {
        $notification = new AkreditasiNotification('test', 'Test Title', 'Test message');

        $this->assertSame('notifications', $notification->queue);
    }

    public function test_backoff_returns_correct_values(): void
    {
        $notification = new AkreditasiNotification('test', 'Test Title', 'Test message');

        $this->assertSame([10, 60, 300], $notification->backoff());
    }

    public function test_constructor_preserves_all_parameters(): void
    {
        $notification = new AkreditasiNotification(
            'assessment',
            'Update Status',
            'Pengajuan Anda telah diverifikasi.',
            'https://example.com/akreditasi'
        );

        $this->assertSame('assessment', $notification->type);
        $this->assertSame('Update Status', $notification->title);
        $this->assertSame('Pengajuan Anda telah diverifikasi.', $notification->message);
        $this->assertSame('https://example.com/akreditasi', $notification->url);
    }

    public function test_constructor_defaults_url_to_hash(): void
    {
        $notification = new AkreditasiNotification('test', 'Title', 'Message');

        $this->assertSame('#', $notification->url);
    }

    public function test_notifiable_id_is_null_before_via_called(): void
    {
        $notification = new AkreditasiNotification('test', 'Title', 'Message');

        $this->assertNull($notification->notifiableId);
    }

    public function test_via_captures_notifiable_id(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create(['role_id' => 3]);

        $notification = new AkreditasiNotification('test', 'Title', 'Message');
        $notification->via($user);

        $this->assertSame($user->id, $notification->notifiableId);
    }

    public function test_failed_creates_failed_notification_record(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create(['role_id' => 3]);

        $notification = new AkreditasiNotification(
            'assessment',
            'Update Status',
            'Pengajuan Anda telah diverifikasi.',
            'https://example.com'
        );
        $notification->notifiableId = $user->id;

        $exception = new \RuntimeException('WebPush provider timeout');
        $notification->failed($exception);

        $this->assertDatabaseHas('failed_notifications', [
            'notification_type' => 'assessment',
            'notifiable_id'     => $user->id,
            'failure_reason'    => 'WebPush provider timeout',
            'status'            => 'pending',
        ]);

        $record = FailedNotification::where('notifiable_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('assessment', $record->payload['type']);
        $this->assertSame('Update Status', $record->payload['title']);
        $this->assertSame('Pengajuan Anda telah diverifikasi.', $record->payload['message']);
        $this->assertSame('https://example.com', $record->payload['url']);
        $this->assertNotNull($record->failed_at);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create(['role_id' => 3]);

        $notification = new AkreditasiNotification(
            'visitasi_diterima',
            'Jadwal Visitasi',
            'Visitasi dijadwalkan.',
            'https://example.com/visitasi'
        );

        $array = $notification->toArray($user);

        $this->assertSame([
            'type'    => 'visitasi_diterima',
            'title'   => 'Jadwal Visitasi',
            'message' => 'Visitasi dijadwalkan.',
            'url'     => 'https://example.com/visitasi',
        ], $array);
    }
}
