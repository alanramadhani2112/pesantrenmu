<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiCatatan;
use App\Models\Banding;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Services\BandingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BandingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected BandingService $bandingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->bandingService = app(BandingService::class);
    }

    /**
     * Helper: create a pesantren user with complete supporting data.
     */
private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Lifecycle Test',
            'is_locked' => false,
        ]);

        Ipm::create([
            'user_id' => $user->id,
            'nsp_file' => 'ipm/nsp.pdf',
            'lulus_santri_file' => 'ipm/lulus.pdf',
            'kurikulum_file' => 'ipm/kurikulum.pdf',
            'buku_ajar_file' => 'ipm/buku-ajar.pdf',
        ]);

        SdmPesantren::create([
            'user_id' => $user->id,
            'tingkat' => 'spm',
        ]);

        $komponen = MasterEdpmKomponen::first() ?? MasterEdpmKomponen::create(['nama' => 'Standar Isi']);
        $butir = MasterEdpmButir::first() ?? MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Pesantren memiliki dokumen kurikulum.',
        ]);

        Edpm::create([
            'user_id' => $user->id,
            'butir_id' => $butir->id,
            'isian' => '4',
        ]);

        return $user->refresh();
    }

    /**
     * Integration test: full accept lifecycle
     * submit banding → assign reviewer → accept → akreditasi returns to Validasi Akhir Admin.
     */
public function test_full_accept_lifecycle(): void
    {
        // Setup: create a complete pesantren user with a rejected akreditasi
        $user = $this->createCompletePesantrenUser();
        $admin = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => -2,
        ]);

        // Step 1: Submit banding
        $banding = $this->bandingService->createBanding(
            $akreditasi->id,
            $user->id,
            'Kami merasa penilaian tidak adil karena beberapa dokumen belum diperiksa dengan benar.'
        );

        $this->assertNotNull($banding);
        $this->assertEquals('pending', $banding->status);
        $this->assertEquals($akreditasi->id, $banding->akreditasi_id);
        $this->assertEquals($user->id, $banding->user_id);

        // Step 2: Assign reviewer
        $result = $this->bandingService->assignReviewer($banding->id, $admin->id);

        $this->assertTrue($result);
        $banding->refresh();
        $this->assertEquals('under_review', $banding->status);
        $this->assertEquals($admin->id, $banding->reviewer_id);
        $this->assertNotNull($banding->review_deadline);

        // Step 3: Accept banding
        $keputusan = 'Setelah ditinjau ulang, kami menerima banding ini dan memberikan kesempatan evaluasi ulang.';
        $acceptedAkreditasi = $this->bandingService->acceptBanding($banding->id, $keputusan);

        // Verify banding is accepted
        $banding->refresh();
        $this->assertEquals('accepted', $banding->status);
        $this->assertEquals($keputusan, $banding->keputusan);
        $this->assertNotNull($banding->decided_at);

        // Verify the existing akreditasi returns to final admin validation.
        $this->assertNotNull($acceptedAkreditasi);
        $this->assertEquals($akreditasi->id, $acceptedAkreditasi->id);
        $this->assertEquals(1, (int) $acceptedAkreditasi->status);
        $this->assertEquals($user->id, $acceptedAkreditasi->user_id);

        // Verify in database
        $this->assertDatabaseHas('akreditasis', [
            'id' => $akreditasi->id,
            'status' => 1,
            'parent' => null,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Integration test: full reject lifecycle
     * submit banding → assign reviewer → reject → akreditasi back to Ditolak and catatan created
     */
public function test_full_reject_lifecycle(): void
    {
        // Setup: create a pesantren user with a rejected akreditasi
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Reject Test',
        ]);

        $admin = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $user->id,
            'status' => -2,
        ]);

        // Step 1: Submit banding record for an akreditasi already in Banding status.
        $banding = $this->bandingService->createBanding(
            $akreditasi->id,
            $user->id,
            'Kami keberatan dengan hasil penilaian dan meminta peninjauan ulang terhadap dokumen kami.'
        );

        $this->assertNotNull($banding);
        $this->assertEquals('pending', $banding->status);

        // Step 2: Assign reviewer
        $result = $this->bandingService->assignReviewer($banding->id, $admin->id);

        $this->assertTrue($result);
        $banding->refresh();
        $this->assertEquals('under_review', $banding->status);
        $this->assertEquals($admin->id, $banding->reviewer_id);

        // Step 3: Reject banding
        $keputusan = 'Setelah ditinjau ulang, banding ditolak karena semua dokumen telah diperiksa dengan benar.';
        $result = $this->bandingService->rejectBanding($banding->id, $keputusan);

        $this->assertTrue($result);

        // Verify banding is rejected
        $banding->refresh();
        $this->assertEquals('rejected', $banding->status);
        $this->assertEquals($keputusan, $banding->keputusan);
        $this->assertNotNull($banding->decided_at);

        // Verify akreditasi status reverted to Ditolak
        $akreditasi->refresh();
        $this->assertEquals(-1, (int) $akreditasi->status);

        // Verify AkreditasiCatatan created with rejection explanation
        $catatan = AkreditasiCatatan::where('akreditasi_id', $akreditasi->id)
            ->where('tipe', 'banding_rejected')
            ->first();

        $this->assertNotNull($catatan);
        $this->assertEquals($keputusan, $catatan->catatan);
        $this->assertEquals($admin->id, $catatan->user_id);
        $this->assertEquals($akreditasi->id, $catatan->akreditasi_id);

        // Verify in database
        $this->assertDatabaseHas('akreditasi_catatans', [
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $admin->id,
            'tipe' => 'banding_rejected',
            'catatan' => $keputusan,
        ]);
    }
}
