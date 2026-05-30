<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Banding;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Services\PesantrenService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BandingRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected PesantrenService $pesantrenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->pesantrenService = app(PesantrenService::class);
        Notification::fake();
    }

    /**
     * Helper: create a pesantren user with a rejected akreditasi that has assessments.
     */
    private function createPesantrenWithRejectedAkreditasi(): array
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Regression Test',
            'is_locked' => false,
        ]);

        // Create admin users for notifications
        $admin1 = User::factory()->create(['role_id' => 1]);
        $admin2 = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => -1,
        ]);

        // Create an asesor and assessment so the assessments check passes
        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Regression, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Regression',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(30),
            'tanggal_berakhir' => now()->subDays(15),
        ]);

        return [
            'user' => $user,
            'akreditasi' => $akreditasi,
            'admins' => [$admin1, $admin2],
        ];
    }

    /**
     * Task 12.2: Integration test — submitAppeals follows the canonical
     * banding transition (-1→-2) and notifies admin.
     */
    public function test_submit_appeals_uses_canonical_banding_status_and_notification(): void
    {
        $data = $this->createPesantrenWithRejectedAkreditasi();
        $user = $data['user'];
        $akreditasi = $data['akreditasi'];
        $admins = $data['admins'];

        $alasan = 'Kami merasa penilaian tidak sesuai dengan kondisi sebenarnya di pesantren kami.';

        // Verify initial status is Ditolak
        $this->assertEquals(-1, (int) $akreditasi->status);

        $result = $this->pesantrenService->submitAppeals(
            $akreditasi->id,
            $user->id,
            $alasan
        );

        $this->assertTrue($result);

        // Akreditasi status changes from Ditolak to Banding
        $akreditasi->refresh();
        $this->assertEquals(-2, (int) $akreditasi->status);

        // Backward compatibility: admin users receive notification
        Notification::assertSentTo(
            $admins[0],
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'banding_submitted';
            }
        );

        Notification::assertSentTo(
            $admins[1],
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'banding_submitted';
            }
        );

        // New behavior: a Banding record is also created
        $banding = Banding::where('akreditasi_id', $akreditasi->id)->first();
        $this->assertNotNull($banding);
        $this->assertEquals('pending', $banding->status);
        $this->assertEquals($user->id, $banding->user_id);
        $this->assertEquals($alasan, $banding->alasan);
    }

    /**
     * Task 12.3: Integration test — banding limit=0 disallows all appeals.
     */
    public function test_banding_limit_zero_disallows_all_appeals(): void
    {
        $data = $this->createPesantrenWithRejectedAkreditasi();
        $user = $data['user'];
        $akreditasi = $data['akreditasi'];

        // Set banding limit to 0 — no appeals allowed at all
        config(['akreditasi.banding_limit' => 0]);

        $alasan = 'Kami ingin mengajukan banding atas keputusan penolakan akreditasi.';

        $result = $this->pesantrenService->submitAppeals(
            $akreditasi->id,
            $user->id,
            $alasan
        );

        // Should be rejected
        $this->assertFalse($result);

        // Akreditasi status should remain Ditolak
        $akreditasi->refresh();
        $this->assertEquals(-1, (int) $akreditasi->status);

        // No banding record should be created
        $this->assertEquals(0, Banding::where('akreditasi_id', $akreditasi->id)->count());
    }

    /**
     * Task 12.4: Integration test — multiple bandings for same akreditasi blocked when limit=1.
     */
    public function test_multiple_bandings_for_same_akreditasi_blocked_when_limit_is_one(): void
    {
        $data = $this->createPesantrenWithRejectedAkreditasi();
        $user = $data['user'];
        $akreditasi = $data['akreditasi'];

        // Set banding limit to 1
        config(['akreditasi.banding_limit' => 1]);

        // Create an existing banding record (simulating a previous appeal that was rejected)
        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'rejected',
            'alasan' => 'Pengajuan banding pertama kami berdasarkan bukti yang ada.',
            'keputusan' => 'Banding ditolak karena bukti tidak cukup.',
            'decided_at' => now()->subDays(5),
        ]);

        // Verify there's already 1 banding record
        $this->assertEquals(1, Banding::where('akreditasi_id', $akreditasi->id)->count());

        // Akreditasi is still rejected after previous banding was rejected.
        $this->assertEquals(-1, (int) $akreditasi->status);

        // Second appeal should be blocked (limit=1, already have 1 banding record)
        $alasan2 = 'Pengajuan banding kedua dengan bukti tambahan yang baru ditemukan.';

        $result2 = $this->pesantrenService->submitAppeals(
            $akreditasi->id,
            $user->id,
            $alasan2
        );

        $this->assertFalse($result2);

        // Akreditasi status should remain Ditolak
        $akreditasi->refresh();
        $this->assertEquals(-1, (int) $akreditasi->status);

        // Still only 1 banding record
        $this->assertEquals(1, Banding::where('akreditasi_id', $akreditasi->id)->count());
    }
}
