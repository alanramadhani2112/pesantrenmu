<?php

namespace Tests\Unit;

use App\Models\Akreditasi;
use App\Models\AkreditasiAuditLog;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AuditTrailService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuditTrailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditTrailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = app(AuditTrailService::class);
    }

    private function createUserAndAkreditasi(): array
    {
        $user = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test',
            'is_locked' => true,
        ]);
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        return compact('user', 'pesantrenUser', 'akreditasi');
    }

    public function test_log_creates_audit_log_with_correct_fields(): void
    {
        $setup = $this->createUserAndAkreditasi();
        Auth::login($setup['user']);

        $log = $this->service->log(
            $setup['akreditasi']->id,
            'status_changed',
            'Pengajuan',
            'Assessment',
            ['old_status_code' => 6, 'new_status_code' => 5]
        );

        $this->assertInstanceOf(AkreditasiAuditLog::class, $log);
        $this->assertEquals($setup['akreditasi']->id, $log->akreditasi_id);
        $this->assertEquals($setup['user']->id, $log->user_id);
        $this->assertEquals('status_changed', $log->action_type);
        $this->assertEquals('Pengajuan', $log->old_value);
        $this->assertEquals('Assessment', $log->new_value);
        $this->assertEquals(['old_status_code' => 6, 'new_status_code' => 5], $log->metadata);
        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->user_agent);
        $this->assertNotNull($log->created_at);
    }

    public function test_log_captures_ip_and_user_agent_from_request(): void
    {
        $setup = $this->createUserAndAkreditasi();
        Auth::login($setup['user']);

        $log = $this->service->log(
            $setup['akreditasi']->id,
            'approved',
            null,
            'A'
        );

        // In testing context, request()->ip() returns 127.0.0.1
        $this->assertNotEquals('system', $log->ip_address);
        $this->assertNotNull($log->created_at);
    }

    public function test_log_throws_invalid_argument_for_unknown_action_type(): void
    {
        $setup = $this->createUserAndAkreditasi();
        Auth::login($setup['user']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid action type 'unknown_action'");

        $this->service->log(
            $setup['akreditasi']->id,
            'unknown_action',
            null,
            null
        );
    }

    public function test_log_accepts_all_valid_action_types(): void
    {
        $setup = $this->createUserAndAkreditasi();
        Auth::login($setup['user']);

        $validTypes = AuditTrailService::ALLOWED_ACTION_TYPES;

        foreach ($validTypes as $type) {
            $log = $this->service->log(
                $setup['akreditasi']->id,
                $type,
                null,
                null
            );
            $this->assertEquals($type, $log->action_type);
        }

        $this->assertDatabaseCount('akreditasi_audit_logs', count($validTypes));
    }

    public function test_log_succeeds_when_unauthenticated_with_null_user_id(): void
    {
        $setup = $this->createUserAndAkreditasi();
        // No Auth::login() call - unauthenticated (system/observer context)

        $log = $this->service->log(
            $setup['akreditasi']->id,
            'deleted',
            null,
            null,
            ['status' => 6, 'pesantren_name' => 'Test']
        );

        $this->assertInstanceOf(AkreditasiAuditLog::class, $log);
        $this->assertNull($log->user_id);
        $this->assertEquals('deleted', $log->action_type);
    }

    public function test_get_timeline_returns_paginated_results_in_desc_order(): void
    {
        $setup = $this->createUserAndAkreditasi();
        Auth::login($setup['user']);

        // Create multiple logs
        $this->service->log($setup['akreditasi']->id, 'status_changed', 'Pengajuan', 'Assessment');
        sleep(1); // Ensure different timestamps
        $this->service->log($setup['akreditasi']->id, 'asesor_assigned', null, 'Dr. Test');
        sleep(1);
        $this->service->log($setup['akreditasi']->id, 'approved', null, 'A');

        $timeline = $this->service->getTimeline($setup['akreditasi']->id);

        $this->assertEquals(3, $timeline->total());
        // Verify descending order
        $items = $timeline->items();
        $this->assertEquals('approved', $items[0]->action_type);
        $this->assertEquals('asesor_assigned', $items[1]->action_type);
        $this->assertEquals('status_changed', $items[2]->action_type);
    }

    public function test_get_timeline_filters_by_action_type_string(): void
    {
        $setup = $this->createUserAndAkreditasi();
        Auth::login($setup['user']);

        $this->service->log($setup['akreditasi']->id, 'status_changed', 'A', 'B');
        $this->service->log($setup['akreditasi']->id, 'approved', null, 'A');
        $this->service->log($setup['akreditasi']->id, 'rejected', null, null);

        $timeline = $this->service->getTimeline($setup['akreditasi']->id, [
            'action_type' => 'approved',
        ]);

        $this->assertEquals(1, $timeline->total());
        $this->assertEquals('approved', $timeline->items()[0]->action_type);
    }

    public function test_get_timeline_filters_by_action_type_array(): void
    {
        $setup = $this->createUserAndAkreditasi();
        Auth::login($setup['user']);

        $this->service->log($setup['akreditasi']->id, 'status_changed', 'A', 'B');
        $this->service->log($setup['akreditasi']->id, 'approved', null, 'A');
        $this->service->log($setup['akreditasi']->id, 'rejected', null, null);

        $timeline = $this->service->getTimeline($setup['akreditasi']->id, [
            'action_type' => ['approved', 'rejected'],
        ]);

        $this->assertEquals(2, $timeline->total());
    }

    public function test_get_timeline_filters_by_user_id(): void
    {
        $setup = $this->createUserAndAkreditasi();
        $otherUser = User::factory()->create(['role_id' => 2]);

        Auth::login($setup['user']);
        $this->service->log($setup['akreditasi']->id, 'status_changed', 'A', 'B');

        Auth::login($otherUser);
        $this->service->log($setup['akreditasi']->id, 'approved', null, 'A');

        $timeline = $this->service->getTimeline($setup['akreditasi']->id, [
            'user_id' => $otherUser->id,
        ]);

        $this->assertEquals(1, $timeline->total());
        $this->assertEquals($otherUser->id, $timeline->items()[0]->user_id);
    }

    public function test_get_timeline_filters_by_date_range(): void
    {
        $setup = $this->createUserAndAkreditasi();
        Auth::login($setup['user']);

        // Create log with specific date
        $log1 = new AkreditasiAuditLog();
        $log1->akreditasi_id = $setup['akreditasi']->id;
        $log1->user_id = $setup['user']->id;
        $log1->action_type = 'status_changed';
        $log1->ip_address = '127.0.0.1';
        $log1->user_agent = 'test';
        $log1->created_at = '2024-01-15 10:00:00';
        $log1->save();

        $log2 = new AkreditasiAuditLog();
        $log2->akreditasi_id = $setup['akreditasi']->id;
        $log2->user_id = $setup['user']->id;
        $log2->action_type = 'approved';
        $log2->ip_address = '127.0.0.1';
        $log2->user_agent = 'test';
        $log2->created_at = '2024-02-20 10:00:00';
        $log2->save();

        $timeline = $this->service->getTimeline($setup['akreditasi']->id, [
            'date_from' => '2024-02-01',
            'date_to' => '2024-02-28',
        ]);

        $this->assertEquals(1, $timeline->total());
        $this->assertEquals('approved', $timeline->items()[0]->action_type);
    }

    public function test_get_timeline_eager_loads_user_relationship(): void
    {
        $setup = $this->createUserAndAkreditasi();
        Auth::login($setup['user']);

        $this->service->log($setup['akreditasi']->id, 'status_changed', 'A', 'B');

        $timeline = $this->service->getTimeline($setup['akreditasi']->id);

        $this->assertTrue($timeline->items()[0]->relationLoaded('user'));
        $this->assertEquals($setup['user']->id, $timeline->items()[0]->user->id);
    }

    public function test_get_timeline_respects_per_page_parameter(): void
    {
        $setup = $this->createUserAndAkreditasi();
        Auth::login($setup['user']);

        for ($i = 0; $i < 5; $i++) {
            $this->service->log($setup['akreditasi']->id, 'status_changed', 'A', 'B');
        }

        $timeline = $this->service->getTimeline($setup['akreditasi']->id, [], 2);

        $this->assertEquals(5, $timeline->total());
        $this->assertCount(2, $timeline->items());
    }

    public function test_log_with_nullable_fields(): void
    {
        $setup = $this->createUserAndAkreditasi();
        Auth::login($setup['user']);

        $log = $this->service->log(
            $setup['akreditasi']->id,
            'finalized',
            null,
            null,
            null
        );

        $this->assertNull($log->old_value);
        $this->assertNull($log->new_value);
        $this->assertNull($log->metadata);
    }
}
