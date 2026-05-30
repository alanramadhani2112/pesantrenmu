<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Banding;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\PesantrenService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BandingSubmitAppealsTest extends TestCase
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
            'nama_pesantren' => 'Pesantren Banding Test',
            'is_locked' => false,
        ]);

        // Create an admin user for notifications
        User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => -1,
        ]);

        // Create an asesor and assessment so the assessments check passes
        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Test, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(30),
            'tanggal_berakhir' => now()->subDays(15),
        ]);

        return ['user' => $user, 'akreditasi' => $akreditasi];
    }

    /**
     * Task 6.3: submitAppeals returns false when banding limit is reached.
     */
    public function test_submit_appeals_returns_false_when_banding_limit_reached(): void
    {
        $data = $this->createPesantrenWithRejectedAkreditasi();
        $user = $data['user'];
        $akreditasi = $data['akreditasi'];

        // Set banding limit to 1
        config(['akreditasi.banding_limit' => 1]);

        // Create an existing banding record to exhaust the limit
        Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $user->id,
            'status' => 'rejected',
            'alasan' => 'Previous banding attempt that was rejected.',
        ]);

        // Attempt to submit another appeal — should be blocked
        $result = $this->pesantrenService->submitAppeals(
            $akreditasi->id,
            $user->id,
            'Kami ingin mengajukan banding kembali karena ada bukti baru.'
        );

        $this->assertFalse($result);

        // Verify akreditasi status remains unchanged at Ditolak
        $akreditasi->refresh();
        $this->assertEquals(-1, (int) $akreditasi->status);

        // Verify no new banding record was created
        $this->assertEquals(1, Banding::where('akreditasi_id', $akreditasi->id)->count());
    }

    /**
     * Task 6.4: submitAppeals creates Banding record and moves to Banding status.
     */
    public function test_submit_appeals_creates_banding_record_alongside_status_change(): void
    {
        $data = $this->createPesantrenWithRejectedAkreditasi();
        $user = $data['user'];
        $akreditasi = $data['akreditasi'];

        $this->actingAs($user);

        $alasan = 'Kami merasa penilaian tidak adil dan meminta peninjauan ulang.';

        $result = $this->pesantrenService->submitAppeals(
            $akreditasi->id,
            $user->id,
            $alasan
        );

        $this->assertTrue($result);

        // Verify a Banding record was created
        $banding = Banding::where('akreditasi_id', $akreditasi->id)->first();
        $this->assertNotNull($banding);
        $this->assertEquals('pending', $banding->status);
        $this->assertEquals($user->id, $banding->user_id);
        $this->assertEquals($alasan, $banding->alasan);
        $this->assertEquals($akreditasi->id, $banding->akreditasi_id);

        // Verify akreditasi status was moved to Banding.
        $akreditasi->refresh();
        $this->assertEquals(-2, (int) $akreditasi->status);
    }

    /**
     * Task 11.3: submitAppeals fails (returns false) when alasan is shorter than 10 characters.
     *
     * Requirements: 2.15
     */
    public function test_pesantren_banding_requires_alasan_min_10_chars(): void
    {
        $data = $this->createPesantrenWithRejectedAkreditasi();
        $user = $data['user'];
        $akreditasi = $data['akreditasi'];

        // Alasan with only 9 characters — below the minimum
        $shortAlasan = 'Terlalu!.'; // 9 chars
        $this->assertLessThan(10, mb_strlen(trim($shortAlasan)));

        $result = $this->pesantrenService->submitAppeals(
            $akreditasi->id,
            $user->id,
            $shortAlasan
        );

        $this->assertFalse($result);

        // Status must remain at Ditolak
        $akreditasi->refresh();
        $this->assertEquals(-1, (int) $akreditasi->status);

        // No banding record should have been created
        $this->assertDatabaseMissing('bandings', ['akreditasi_id' => $akreditasi->id]);
    }

    /**
     * Task 6.5: submitAppeals changes akreditasi status from Ditolak to Banding.
     */
    public function test_submit_appeals_changes_akreditasi_status_from_ditolak_to_banding(): void
    {
        $data = $this->createPesantrenWithRejectedAkreditasi();
        $user = $data['user'];
        $akreditasi = $data['akreditasi'];

        $this->actingAs($user);

        $alasan = 'Dokumen kami belum diperiksa dengan benar oleh asesor.';

        // Verify initial status is Ditolak
        $this->assertEquals(-1, (int) $akreditasi->status);

        $result = $this->pesantrenService->submitAppeals(
            $akreditasi->id,
            $user->id,
            $alasan
        );

        $this->assertTrue($result);

        // Verify status changed from Ditolak to Banding
        $akreditasi->refresh();
        $this->assertEquals(-2, (int) $akreditasi->status);

        // Verify in database
        $this->assertDatabaseHas('akreditasis', [
            'id' => $akreditasi->id,
            'status' => -2,
        ]);
    }
}
