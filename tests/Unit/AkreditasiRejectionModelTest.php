<?php

namespace Tests\Unit;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AkreditasiRejectionModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // --- Task 2.5: AkreditasiRejection model relationships ---

    public function test_rejection_belongs_to_akreditasi(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil', 'ipm.kurikulum'],
            'explanation' => 'Data profil tidak lengkap',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Akreditasi::class, $rejection->akreditasi);
        $this->assertEquals($akreditasi->id, $rejection->akreditasi->id);
    }

    public function test_rejection_belongs_to_user(): void
    {
        $pesantren = User::factory()->create(['role_id' => 3]);
        $asesor = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $pesantren->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $asesor->id,
            'type' => 'asesor',
            'items' => ['sdm'],
            'explanation' => 'SDM data perlu diperbaiki',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(User::class, $rejection->user);
        $this->assertEquals($asesor->id, $rejection->user->id);
    }

    public function test_rejection_items_cast_to_array(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $items = ['profil', 'ipm.nsp', 'edpm.butir.3'];
        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => $items,
            'explanation' => 'Multiple items need correction',
            'rejection_number' => 1,
            'status' => 'pending',
        ]);

        $rejection->refresh();
        $this->assertIsArray($rejection->items);
        $this->assertEquals($items, $rejection->items);
    }

    public function test_rejection_categories_cast_to_array(): void
    {
        $user = User::factory()->create(['role_id' => 1]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        $categories = [
            ['category' => 'nilai_tidak_memenuhi', 'explanation' => 'Nilai di bawah standar minimum'],
            ['category' => 'laporan_tidak_lengkap', 'explanation' => 'Laporan visitasi tidak lengkap'],
        ];

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'admin_final',
            'categories' => $categories,
            'rejection_number' => 1,
            'status' => 'pending',
        ]);

        $rejection->refresh();
        $this->assertIsArray($rejection->categories);
        $this->assertEquals($categories, $rejection->categories);
    }

    public function test_rejection_perbaikan_deadline_cast_to_datetime(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Profil perlu diperbaiki',
            'rejection_number' => 1,
            'perbaikan_deadline' => '2026-06-01 10:00:00',
            'status' => 'pending',
        ]);

        $rejection->refresh();
        $this->assertInstanceOf(Carbon::class, $rejection->perbaikan_deadline);
    }

    public function test_rejection_perbaikan_submitted_at_cast_to_datetime(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Profil perlu diperbaiki',
            'rejection_number' => 1,
            'perbaikan_submitted_at' => '2026-05-20 14:00:00',
            'status' => 'submitted',
        ]);

        $rejection->refresh();
        $this->assertInstanceOf(Carbon::class, $rejection->perbaikan_submitted_at);
    }

    // --- Task 2.6: Akreditasi→rejections and activeRejection relationships ---

    public function test_akreditasi_has_many_rejections(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'First rejection explanation',
            'rejection_number' => 1,
            'status' => 'accepted',
        ]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['sdm'],
            'explanation' => 'Second rejection explanation',
            'rejection_number' => 2,
            'status' => 'pending',
        ]);

        $this->assertCount(2, $akreditasi->rejections);
        $this->assertInstanceOf(AkreditasiRejection::class, $akreditasi->rejections->first());
    }

    public function test_akreditasi_active_rejection_returns_pending_asesor_rejection(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil', 'sdm'],
            'explanation' => 'Active rejection explanation',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'status' => 'pending',
        ]);

        $this->assertNotNull($akreditasi->activeRejection);
        $this->assertEquals($rejection->id, $akreditasi->activeRejection->id);
    }

    public function test_akreditasi_active_rejection_returns_null_when_no_pending(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Accepted rejection',
            'rejection_number' => 1,
            'status' => 'accepted',
        ]);

        $this->assertNull($akreditasi->activeRejection);
    }

    public function test_akreditasi_active_rejection_ignores_admin_final_type(): void
    {
        $user = User::factory()->create(['role_id' => 1]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'admin_final',
            'categories' => [['category' => 'nilai_tidak_memenuhi', 'explanation' => 'Nilai rendah']],
            'rejection_number' => 1,
            'status' => 'pending',
        ]);

        $this->assertNull($akreditasi->activeRejection);
    }

    public function test_akreditasi_active_rejection_returns_latest_when_multiple_pending(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-05-10 12:00:00'));
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'First pending rejection',
            'rejection_number' => 1,
            'status' => 'pending',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-05-12 12:00:00'));
        $latest = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['sdm'],
            'explanation' => 'Second pending rejection',
            'rejection_number' => 2,
            'status' => 'pending',
        ]);

        Carbon::setTestNow();

        $this->assertEquals($latest->id, $akreditasi->activeRejection->id);
    }

    // --- Task 2.7: Helper methods ---

    public function test_is_active_returns_true_for_asesor_pending(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Test rejection',
            'rejection_number' => 1,
            'status' => 'pending',
        ]);

        $this->assertTrue($rejection->isActive());
    }

    public function test_is_active_returns_false_for_admin_final_pending(): void
    {
        $user = User::factory()->create(['role_id' => 1]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 3,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'admin_final',
            'categories' => [['category' => 'lainnya', 'explanation' => 'Custom reason']],
            'rejection_number' => 1,
            'status' => 'pending',
        ]);

        $this->assertFalse($rejection->isActive());
    }

    public function test_is_active_returns_false_for_asesor_submitted(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Test rejection',
            'rejection_number' => 1,
            'status' => 'submitted',
        ]);

        $this->assertFalse($rejection->isActive());
    }

    public function test_is_active_returns_false_for_asesor_accepted(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Test rejection',
            'rejection_number' => 1,
            'status' => 'accepted',
        ]);

        $this->assertFalse($rejection->isActive());
    }

    public function test_is_expired_returns_true_when_past_deadline_and_pending(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Test rejection',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->subDays(3),
            'status' => 'pending',
        ]);

        $this->assertTrue($rejection->isExpired());
    }

    public function test_is_expired_returns_false_when_before_deadline(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Test rejection',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(10),
            'status' => 'pending',
        ]);

        $this->assertFalse($rejection->isExpired());
    }

    public function test_is_expired_returns_false_when_no_deadline(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Test rejection',
            'rejection_number' => 1,
            'perbaikan_deadline' => null,
            'status' => 'pending',
        ]);

        $this->assertFalse($rejection->isExpired());
    }

    public function test_is_expired_returns_false_when_past_deadline_but_not_pending(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Test rejection',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->subDays(3),
            'status' => 'submitted',
        ]);

        $this->assertFalse($rejection->isExpired());
    }

    public function test_days_until_deadline_returns_positive_days_when_before_deadline(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-05-15 12:00:00'));

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Test rejection',
            'rejection_number' => 1,
            'perbaikan_deadline' => Carbon::parse('2026-05-25 12:00:00'),
            'status' => 'pending',
        ]);

        $this->assertEquals(10, $rejection->daysUntilDeadline());

        Carbon::setTestNow();
    }

    public function test_days_until_deadline_returns_zero_when_past_deadline(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-05-25 12:00:00'));

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Test rejection',
            'rejection_number' => 1,
            'perbaikan_deadline' => Carbon::parse('2026-05-20 12:00:00'),
            'status' => 'pending',
        ]);

        $this->assertEquals(0, $rejection->daysUntilDeadline());

        Carbon::setTestNow();
    }

    public function test_days_until_deadline_returns_zero_when_no_deadline(): void
    {
        $user = User::factory()->create(['role_id' => 2]);
        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => 5,
        ]);

        $rejection = AkreditasiRejection::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Test rejection',
            'rejection_number' => 1,
            'perbaikan_deadline' => null,
            'status' => 'pending',
        ]);

        $this->assertEquals(0, $rejection->daysUntilDeadline());
    }
}
