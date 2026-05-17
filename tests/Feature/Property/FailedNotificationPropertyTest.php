<?php

namespace Tests\Feature\Property;

use App\Models\FailedNotification;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Property-based tests for FailedNotification persistence and retry behavior.
 *
 * Property 1: Failed notification logging completeness
 * For any notification with a valid type, recipient, and payload that fails
 * permanently, calling the failed() handler SHALL create a record in
 * failed_notifications containing the exact notification type, the correct
 * notifiable_id, the complete payload (type, title, message, url), the failure
 * reason from the exception, and a non-null failed_at timestamp with status 'pending'.
 *
 * **Validates: Requirements 3.3, 4.1**
 *
 * Property 2: Manual retry resolves record
 * For any failed notification record with status 'pending' and a valid
 * notifiable_id referencing an existing user, calling retry() SHALL update the
 * record's status to 'resolved' and set resolved_at to a non-null timestamp,
 * and SHALL dispatch a new AkreditasiNotification to the queue with the same
 * payload data.
 *
 * **Validates: Requirements 4.4**
 */
class FailedNotificationPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Generate 100 random notification payloads for Property 1.
     */
    public static function randomNotificationPayloadsProvider(): array
    {
        $cases = [];
        $seed = crc32('failed_notification_property_1');
        mt_srand($seed);

        $types = [
            'assessment', 'visitasi_diterima', 'visitasi_ditolak', 'pengajuan',
            'banding', 'validasi', 'ditolak', 'na1_diisi', 'na2_diisi',
            'assessment_selesai', 'input_laporan', 'tugas_baru', 'buka_kunci',
            'kartu_kendali_diunggah',
        ];

        $exceptionMessages = [
            'Connection timed out',
            'WebPush provider returned 500',
            'Broadcast driver unavailable',
            'Queue worker crashed',
            'Database connection lost',
            'SSL certificate error',
            'Rate limit exceeded',
        ];

        for ($i = 0; $i < 100; $i++) {
            $type = $types[mt_rand(0, count($types) - 1)];
            $titleLen = mt_rand(5, 60);
            $messageLen = mt_rand(10, 200);
            $hasUrl = mt_rand(0, 1) === 1;

            $cases["iteration_{$i}"] = [
                $type,
                'Title ' . substr(str_repeat('abcdefghijklmnopqrstuvwxyz', 5), 0, $titleLen),
                'Message ' . substr(str_repeat('Lorem ipsum dolor sit amet ', 10), 0, $messageLen),
                $hasUrl ? 'https://example.com/path/' . mt_rand(1, 9999) : '#',
                $exceptionMessages[mt_rand(0, count($exceptionMessages) - 1)] . ' [' . mt_rand(100, 999) . ']',
            ];
        }

        return $cases;
    }

    /**
     * Property 1: For 100 random notification payloads, invoke failed() on
     * AkreditasiNotification and assert all fields are correctly persisted.
     *
     * **Validates: Requirements 3.3, 4.1**
     *
     * @dataProvider randomNotificationPayloadsProvider
     */
    public function test_property_1_failed_handler_persists_all_fields_correctly(
        string $type,
        string $title,
        string $message,
        string $url,
        string $exceptionMessage
    ): void {
        $user = User::factory()->create(['role_id' => 3]);

        $notification = new AkreditasiNotification($type, $title, $message, $url);
        $notification->notifiableId = $user->id;

        $exception = new \RuntimeException($exceptionMessage);

        $countBefore = FailedNotification::count();
        $notification->failed($exception);
        $countAfter = FailedNotification::count();

        // Exactly one record was created
        $this->assertSame($countBefore + 1, $countAfter);

        $record = FailedNotification::where('notifiable_id', $user->id)
            ->where('notification_type', $type)
            ->latest()
            ->first();

        $this->assertNotNull($record, "FailedNotification record should exist for type=$type, user={$user->id}");

        // Notification type matches
        $this->assertSame($type, $record->notification_type);

        // Notifiable ID matches
        $this->assertSame($user->id, $record->notifiable_id);

        // Payload contains all fields
        $this->assertIsArray($record->payload);
        $this->assertSame($type, $record->payload['type']);
        $this->assertSame($title, $record->payload['title']);
        $this->assertSame($message, $record->payload['message']);
        $this->assertSame($url, $record->payload['url']);

        // Failure reason matches exception message
        $this->assertSame($exceptionMessage, $record->failure_reason);

        // failed_at is set and non-null
        $this->assertNotNull($record->failed_at);

        // Status is 'pending'
        $this->assertSame('pending', $record->status);

        // resolved_at is null (not yet resolved)
        $this->assertNull($record->resolved_at);
    }

    /**
     * Generate 100 random FailedNotification records for Property 2.
     */
    public static function randomFailedNotificationRecordsProvider(): array
    {
        $cases = [];
        $seed = crc32('failed_notification_property_2');
        mt_srand($seed);

        $types = [
            'assessment', 'visitasi_diterima', 'visitasi_ditolak', 'pengajuan',
            'banding', 'validasi', 'ditolak', 'na1_diisi', 'na2_diisi',
        ];

        for ($i = 0; $i < 100; $i++) {
            $type = $types[mt_rand(0, count($types) - 1)];
            $hasUrl = mt_rand(0, 1) === 1;

            $cases["iteration_{$i}"] = [
                $type,
                'Title ' . $i,
                'Message for iteration ' . $i . ' with some content.',
                $hasUrl ? 'https://example.com/path/' . $i : '#',
            ];
        }

        return $cases;
    }

    /**
     * Property 2: For 100 random FailedNotification records with valid users,
     * call retry() and assert status='resolved', resolved_at is set, and a
     * notification is queued.
     *
     * **Validates: Requirements 4.4**
     *
     * @dataProvider randomFailedNotificationRecordsProvider
     */
    public function test_property_2_retry_sets_status_resolved_and_queues_notification(
        string $type,
        string $title,
        string $message,
        string $url
    ): void {
        Notification::fake();

        $user = User::factory()->create(['role_id' => 3]);

        $record = FailedNotification::create([
            'notification_type' => $type,
            'notifiable_id'     => $user->id,
            'payload'           => [
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'url'     => $url,
            ],
            'failure_reason' => 'Test failure reason',
            'failed_at'      => now()->subMinutes(5),
            'status'         => 'pending',
        ]);

        $record->retry();

        // Status is updated to 'resolved'
        $record->refresh();
        $this->assertSame('resolved', $record->status);

        // resolved_at is set and non-null
        $this->assertNotNull($record->resolved_at);

        // A new AkreditasiNotification was dispatched to the user
        Notification::assertSentTo(
            $user,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) use ($type, $title, $message, $url) {
                return $notification->type === $type
                    && $notification->title === $title
                    && $notification->message === $message
                    && $notification->url === $url;
            }
        );
    }

    /**
     * Additional: retry() is a no-op when the user no longer exists.
     * Tests the code path where User::find returns null.
     */
    public function test_retry_is_noop_when_user_not_found(): void
    {
        Notification::fake();

        // The retry() method has: if ($user) { ... notify ... update status }
        // If user is null, no notification is sent and status is not updated.
        // We verify this by directly testing the guard condition.
        $this->assertNull(User::find(99999));

        // Create a record and manually set notifiable_id to a non-existent user
        // without triggering FK (we use the model's in-memory state)
        $user = User::factory()->create(['role_id' => 3]);

        $record = FailedNotification::create([
            'notification_type' => 'test',
            'notifiable_id'     => $user->id,
            'payload'           => ['type' => 'test', 'title' => 'T', 'message' => 'M', 'url' => '#'],
            'failure_reason'    => 'Test',
            'failed_at'         => now(),
            'status'            => 'pending',
        ]);

        // Simulate user not found by overriding the in-memory attribute
        // without persisting (so FK is not violated)
        $record->setRawAttributes(array_merge($record->getAttributes(), ['notifiable_id' => 99999]));

        // Call retry() — user 99999 doesn't exist, so it should be a no-op
        $record->retry();

        // No notification sent
        Notification::assertNothingSent();

        // Status remains 'pending' (not updated since user not found)
        $this->assertSame('pending', $record->status);
    }

    /**
     * Additional: dismiss() sets status to 'dismissed'.
     */
    public function test_dismiss_sets_status_to_dismissed(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $record = FailedNotification::create([
            'notification_type' => 'test',
            'notifiable_id'     => $user->id,
            'payload'           => ['type' => 'test', 'title' => 'T', 'message' => 'M', 'url' => '#'],
            'failure_reason'    => 'Test failure',
            'failed_at'         => now(),
            'status'            => 'pending',
        ]);

        $record->dismiss();

        $record->refresh();
        $this->assertSame('dismissed', $record->status);
    }
}
