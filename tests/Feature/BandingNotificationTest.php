<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Banding;
use App\Models\Edpm;
use App\Models\Ipm;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Services\BandingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BandingNotificationTest extends TestCase
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
     * Helper: create a pesantren user with basic data.
     */
private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Notification Test ' . $user->id,
        ]);
        return $user;
    }

    /**
     * Helper: create a pesantren user with COMPLETE data for createSubmission compatibility.
     */
private function createCompletePesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Complete Notification Test',
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
     * Task 11.4: assignReviewer sends notifications to reviewer and pesantren user.
     *
     * Verifies that when a reviewer is assigned to a pending banding:
     * - The reviewer receives a notification with type 'banding_review'
     * - The pesantren user receives a notification with type 'banding_under_review'
     */
public function test_assign_reviewer_sends_notification_to_reviewer(): void
    {
        Notification::fake();

        $pesantrenUser = $this->createPesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'pending',
            'alasan' => 'Kami merasa penilaian tidak adil dan meminta peninjauan ulang.',
        ]);

        $this->bandingService->assignReviewer($banding->id, $reviewer->id);

        // Verify reviewer receives notification with type 'banding_review'
        Notification::assertSentTo(
            $reviewer,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'banding_review'
                    && $notification->title === 'Tugas Review Banding'
                    && str_contains($notification->message, 'ditugaskan untuk mereview banding');
            }
        );
    }

    /**
     * Task 11.4: assignReviewer sends notification to pesantren user.
     */
public function test_assign_reviewer_sends_notification_to_pesantren_user(): void
    {
        Notification::fake();

        $pesantrenUser = $this->createPesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'pending',
            'alasan' => 'Kami merasa penilaian tidak adil dan meminta peninjauan ulang.',
        ]);

        $this->bandingService->assignReviewer($banding->id, $reviewer->id);

        // Verify pesantren user receives notification with type 'banding_under_review'
        Notification::assertSentTo(
            $pesantrenUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'banding_under_review'
                    && $notification->title === 'Banding Sedang Direview'
                    && str_contains($notification->message, 'sedang dalam proses review');
            }
        );
    }

    /**
     * Task 11.4: assignReviewer sends notifications to BOTH reviewer and pesantren user.
     */
public function test_assign_reviewer_sends_notifications_to_both_reviewer_and_pesantren_user(): void
    {
        Notification::fake();

        $pesantrenUser = $this->createPesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => 2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'status' => 'pending',
            'alasan' => 'Kami merasa penilaian tidak adil dan meminta peninjauan ulang.',
        ]);

        $this->bandingService->assignReviewer($banding->id, $reviewer->id);

        // Verify both users received notifications
        Notification::assertSentTo($reviewer, AkreditasiNotification::class);
        Notification::assertSentTo($pesantrenUser, AkreditasiNotification::class);

        // Verify correct notification types
        Notification::assertSentTo(
            $reviewer,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'banding_review';
            }
        );

        Notification::assertSentTo(
            $pesantrenUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'banding_under_review';
            }
        );
    }

    /**
     * Task 11.5: acceptBanding sends notification to pesantren user.
     *
     * Verifies that when a banding is accepted:
     * - The pesantren user receives a notification with type 'banding_accepted'
     * - The notification message indicates the appeal returns to final admin validation
     */
public function test_accept_banding_sends_notification_to_pesantren_user(): void
    {
        Notification::fake();

        $pesantrenUser = $this->createCompletePesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Kami merasa penilaian tidak adil dan meminta peninjauan ulang.',
            'review_deadline' => now()->addDays(14),
        ]);

        $keputusan = 'Setelah ditinjau ulang, kami menerima banding ini dan memberikan kesempatan evaluasi ulang.';
        $this->bandingService->acceptBanding($banding->id, $keputusan);

        // Verify pesantren user receives notification with type 'banding_accepted'
        Notification::assertSentTo(
            $pesantrenUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'banding_accepted'
                    && $notification->title === 'Banding Diterima'
                    && str_contains($notification->message, 'diterima')
                    && str_contains($notification->message, 'Validasi Akhir Admin');
            }
        );
    }

    /**
     * Task 11.6: rejectBanding sends notification to pesantren user with explanation.
     *
     * Verifies that when a banding is rejected:
     * - The pesantren user receives a notification with type 'banding_rejected'
     * - The notification message includes the rejection explanation
     */
public function test_reject_banding_sends_notification_to_pesantren_user_with_explanation(): void
    {
        Notification::fake();

        $pesantrenUser = $this->createPesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Kami merasa penilaian tidak adil dan meminta peninjauan ulang.',
            'review_deadline' => now()->addDays(14),
        ]);

        $keputusan = 'Setelah ditinjau ulang, banding ditolak karena semua dokumen telah diperiksa dengan benar.';
        $this->bandingService->rejectBanding($banding->id, $keputusan);

        // Verify pesantren user receives notification with type 'banding_rejected'
        Notification::assertSentTo(
            $pesantrenUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) use ($keputusan) {
                return $notification->type === 'banding_rejected'
                    && $notification->title === 'Banding Ditolak'
                    && str_contains($notification->message, 'ditolak')
                    && str_contains($notification->message, $keputusan);
            }
        );
    }

    /**
     * Task 11.6 (additional): rejectBanding notification message contains the full rejection explanation.
     */
public function test_reject_banding_notification_contains_rejection_explanation(): void
    {
        Notification::fake();

        $pesantrenUser = $this->createPesantrenUser();
        $reviewer = User::factory()->create(['role_id' => 1]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => -2,
        ]);

        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantrenUser->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'under_review',
            'alasan' => 'Kami keberatan dengan hasil penilaian.',
            'review_deadline' => now()->addDays(14),
        ]);

        $keputusan = 'Dokumen yang diajukan tidak memenuhi standar minimum yang ditetapkan dalam pedoman akreditasi.';
        $this->bandingService->rejectBanding($banding->id, $keputusan);

        // Verify the notification message includes the rejection explanation text
        Notification::assertSentTo(
            $pesantrenUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) use ($keputusan) {
                // The message format is: 'Pengajuan banding Anda ditolak. Alasan: ' . $keputusan
                return $notification->type === 'banding_rejected'
                    && str_contains($notification->message, 'Alasan: ')
                    && str_contains($notification->message, $keputusan);
            }
        );
    }
}
