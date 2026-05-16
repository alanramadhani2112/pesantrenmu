<?php

namespace Tests\Unit;

use App\Models\Akreditasi;
use App\Models\Banding;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BandingModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // --- Task 2.5: Banding model relationships ---

    public function test_banding_belongs_to_akreditasi(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'alasan' => 'Alasan banding test',
        ]);

        $this->assertInstanceOf(Akreditasi::class, $banding->akreditasi);
        $this->assertEquals($akreditasi->id, $banding->akreditasi->id);
    }

    public function test_banding_belongs_to_user(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'alasan' => 'Alasan banding test',
        ]);

        $this->assertInstanceOf(User::class, $banding->user);
        $this->assertEquals($user->id, $banding->user->id);
    }

    public function test_banding_belongs_to_reviewer(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $reviewer = User::factory()->create(['role_id' => 1]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Alasan banding test',
            'review_deadline' => now()->addDays(14),
        ]);

        $this->assertInstanceOf(User::class, $banding->reviewer);
        $this->assertEquals($reviewer->id, $banding->reviewer->id);
    }

    public function test_banding_reviewer_is_null_when_not_assigned(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'alasan' => 'Alasan banding test',
        ]);

        $this->assertNull($banding->reviewer);
    }

    // --- Task 2.5: Helper methods ---

    public function test_is_overdue_returns_true_when_past_deadline(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $user->id,
            'status' => 'under_review',
            'alasan' => 'Test',
            'review_deadline' => now()->subDays(3),
        ]);

        $this->assertTrue($banding->isOverdue());
    }

    public function test_is_overdue_returns_false_when_before_deadline(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $user->id,
            'status' => 'under_review',
            'alasan' => 'Test',
            'review_deadline' => now()->addDays(5),
        ]);

        $this->assertFalse($banding->isOverdue());
    }

    public function test_is_overdue_returns_false_when_status_is_not_under_review(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'alasan' => 'Test',
            'review_deadline' => now()->subDays(3),
        ]);

        $this->assertFalse($banding->isOverdue());
    }

    public function test_is_overdue_returns_false_when_no_deadline(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'under_review',
            'alasan' => 'Test',
        ]);

        $this->assertFalse($banding->isOverdue());
    }

    public function test_days_overdue_returns_correct_count(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-05-20 12:00:00'));

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $user->id,
            'status' => 'under_review',
            'alasan' => 'Test',
            'review_deadline' => Carbon::parse('2026-05-17 12:00:00'),
        ]);

        $this->assertEquals(3, $banding->daysOverdue());

        Carbon::setTestNow();
    }

    public function test_days_overdue_returns_zero_when_not_overdue(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $user->id,
            'status' => 'under_review',
            'alasan' => 'Test',
            'review_deadline' => now()->addDays(5),
        ]);

        $this->assertEquals(0, $banding->daysOverdue());
    }

    public function test_days_until_deadline_returns_positive_when_before_deadline(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00'));

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'under_review',
            'alasan' => 'Test',
            'review_deadline' => Carbon::parse('2026-05-20 12:00:00'),
        ]);

        $this->assertEquals(5, $banding->daysUntilDeadline());

        Carbon::setTestNow();
    }

    public function test_days_until_deadline_returns_negative_when_overdue(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-05-20 12:00:00'));

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'under_review',
            'alasan' => 'Test',
            'review_deadline' => Carbon::parse('2026-05-17 12:00:00'),
        ]);

        $this->assertEquals(-3, $banding->daysUntilDeadline());

        Carbon::setTestNow();
    }

    public function test_days_until_deadline_returns_zero_when_no_deadline(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'alasan' => 'Test',
        ]);

        $this->assertEquals(0, $banding->daysUntilDeadline());
    }

    // --- Task 2.6: Akreditasi→bandings and activeBanding relationships ---

    public function test_akreditasi_has_many_bandings(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'rejected',
            'alasan' => 'First appeal',
        ]);

        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'alasan' => 'Second appeal',
        ]);

        $this->assertCount(2, $akreditasi->bandings);
    }

    public function test_akreditasi_active_banding_returns_pending(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'alasan' => 'Active appeal',
        ]);

        $this->assertNotNull($akreditasi->activeBanding);
        $this->assertEquals($banding->id, $akreditasi->activeBanding->id);
    }

    public function test_akreditasi_active_banding_returns_under_review(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $reviewer = User::factory()->create(['role_id' => 1]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Under review appeal',
            'review_deadline' => now()->addDays(14),
        ]);

        $this->assertNotNull($akreditasi->activeBanding);
        $this->assertEquals($banding->id, $akreditasi->activeBanding->id);
    }

    public function test_akreditasi_active_banding_returns_null_when_all_decided(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'rejected',
            'alasan' => 'Rejected appeal',
            'keputusan' => 'Ditolak karena alasan tidak valid',
            'decided_at' => now(),
        ]);

        $this->assertNull($akreditasi->activeBanding);
    }

    public function test_akreditasi_active_banding_returns_latest_when_multiple_active(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        // Create first banding (older)
        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00'));
        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'alasan' => 'First appeal',
        ]);

        // Create second banding (newer)
        Carbon::setTestNow(Carbon::parse('2026-05-12 12:00:00'));
        $latest = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'alasan' => 'Second appeal',
        ]);

        Carbon::setTestNow();

        $this->assertEquals($latest->id, $akreditasi->activeBanding->id);
    }

    // --- Casts verification ---

    public function test_review_deadline_is_cast_to_datetime(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'under_review',
            'alasan' => 'Test',
            'review_deadline' => '2026-05-20 10:00:00',
        ]);

        $banding->refresh();
        $this->assertInstanceOf(Carbon::class, $banding->review_deadline);
    }

    public function test_decided_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'accepted',
            'alasan' => 'Test',
            'keputusan' => 'Diterima',
            'decided_at' => '2026-05-20 10:00:00',
        ]);

        $banding->refresh();
        $this->assertInstanceOf(Carbon::class, $banding->decided_at);
    }
}
