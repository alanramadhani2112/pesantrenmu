<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit test verifying queue configuration for after_commit behavior.
 *
 * Validates: Requirement 2.4
 */
class QueueConfigTest extends TestCase
{
    public function test_database_queue_connection_has_after_commit_true(): void
    {
        $this->assertTrue(
            config('queue.connections.database.after_commit'),
            'queue.connections.database.after_commit must be true to ensure notifications '.
            'are only dispatched after the transaction commits successfully.'
        );
    }

    public function test_database_queue_connection_uses_database_driver(): void
    {
        $this->assertSame('database', config('queue.connections.database.driver'));
    }
}
