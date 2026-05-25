<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiRejection;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Services\RejectionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RejectionNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected RejectionService $rejectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->rejectionService = app(RejectionService::class);
    }

    /**
     * Helper: create a pesantren user with akreditasi at given status and an Asesor 1 assigned.
     */
private function createAsesor1Setup(int $status = 5): array
    {
        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Notification Test ' . $pesantrenUser->id,
            'is_locked' => true,
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => $status,
        ]);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Notif Test, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Notif Test',
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now(),
            'tanggal_berakhir' => now()->addDays(30),
        ]);

        return [
            'pesantrenUser' => $pesantrenUser,
            'akreditasi' => $akreditasi,
            'asesorUser' => $asesorUser,
            'asesor' => $asesor,
        ];
    }

    /**
     * Task 18.1: createRejection sends notification to pesantren with item summary.
     *
     * Validates: Requirements 1.7
     */
public function test_create_rejection_notifies_pesantren_with_item_summary(): void
    {
        Notification::fake();

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;
        $pesantrenUser = $setup['pesantrenUser'];

        $items = ['profil', 'ipm.kurikulum', 'sdm'];
        $explanation = 'Data profil tidak lengkap dan kurikulum perlu diperbaiki';

        $this->rejectionService->createRejection($akreditasiId, $asesorUserId, $items, $explanation);

        Notification::assertSentTo(
            $pesantrenUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'document_rejection_created'
                    && $notification->title === 'Dokumen Ditolak'
                    && str_contains($notification->message, 'profil')
                    && str_contains($notification->message, 'ipm.kurikulum')
                    && str_contains($notification->message, 'sdm');
            }
        );
    }

    /**
     * Task 18.1: createRejection sends notification to admin users.
     *
     * Validates: Requirements 1.8
     */
public function test_create_rejection_notifies_admin_users(): void
    {
        Notification::fake();

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;

        // Create admin users
        $admin1 = User::factory()->create(['role_id' => 1]);
        $admin2 = User::factory()->create(['role_id' => 1]);

        $items = ['profil', 'sdm'];
        $explanation = 'Data profil dan SDM perlu diperbaiki';

        $this->rejectionService->createRejection($akreditasiId, $asesorUserId, $items, $explanation);

        Notification::assertSentTo(
            $admin1,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'document_rejection_created_admin'
                    && $notification->title === 'Asesor Menolak Dokumen';
            }
        );

        Notification::assertSentTo(
            $admin2,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'document_rejection_created_admin';
            }
        );
    }

    /**
     * Task 18.2: submitPerbaikan sends notification to Asesor 1.
     *
     * Validates: Requirements 3.4
     */
public function test_submit_perbaikan_notifies_asesor1(): void
    {
        Notification::fake();

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;
        $pesantrenUserId = $setup['pesantrenUser']->id;

        // Create an active rejection
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil', 'sdm'],
            'explanation' => 'Data perlu diperbaiki segera',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'status' => 'pending',
        ]);

        $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);

        Notification::assertSentTo(
            $setup['asesorUser'],
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'perbaikan_submitted'
                    && $notification->title === 'Perbaikan Disubmit'
                    && str_contains($notification->message, 'perbaikan');
            }
        );
    }

    /**
     * Task 18.2: submitPerbaikan sends notification to admin users.
     *
     * Validates: Requirements 3.5
     */
public function test_submit_perbaikan_notifies_admin_users(): void
    {
        Notification::fake();

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;
        $pesantrenUserId = $setup['pesantrenUser']->id;

        $admin = User::factory()->create(['role_id' => 1]);

        // Create an active rejection
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Data perlu diperbaiki segera',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(14),
            'status' => 'pending',
        ]);

        $this->rejectionService->submitPerbaikan($akreditasiId, $pesantrenUserId);

        Notification::assertSentTo(
            $admin,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'perbaikan_submitted_admin'
                    && $notification->title === 'Perbaikan Disubmit';
            }
        );
    }

    /**
     * Task 18.3: Auto-rejection (limit reached) sends notification to pesantren.
     *
     * Validates: Requirements 4.4
     */
public function test_auto_rejection_limit_reached_notifies_pesantren(): void
    {
        Notification::fake();

        config(['akreditasi.rejection_limit' => 2]);

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;
        $pesantrenUser = $setup['pesantrenUser'];

        // Create first rejection (count = 1)
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'First rejection explanation',
            'rejection_number' => 1,
            'status' => 'accepted',
        ]);

        // Second rejection triggers limit (count + 1 >= 2)
        $this->rejectionService->createRejection(
            $akreditasiId,
            $asesorUserId,
            ['sdm'],
            'Second rejection triggers limit'
        );

        Notification::assertSentTo(
            $pesantrenUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'rejection_limit_reached'
                    && str_contains($notification->message, 'batas maksimal');
            }
        );
    }

    /**
     * Task 18.3: Auto-rejection (limit reached) sends notification to admin.
     *
     * Validates: Requirements 4.5
     */
public function test_auto_rejection_limit_reached_notifies_admin(): void
    {
        Notification::fake();

        config(['akreditasi.rejection_limit' => 2]);

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;

        $admin = User::factory()->create(['role_id' => 1]);

        // Create first rejection (count = 1)
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'First rejection explanation',
            'rejection_number' => 1,
            'status' => 'accepted',
        ]);

        // Second rejection triggers limit
        $this->rejectionService->createRejection(
            $akreditasiId,
            $asesorUserId,
            ['sdm'],
            'Second rejection triggers limit'
        );

        Notification::assertSentTo(
            $admin,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'rejection_limit_reached_admin'
                    && str_contains($notification->message, 'batas penolakan tercapai');
            }
        );
    }

    /**
     * Task 18.4: Auto-rejection (deadline expired) sends notification to pesantren, Asesor 1, and admin.
     *
     * Validates: Requirements 8.5
     */
public function test_auto_rejection_deadline_expired_notifies_all_parties(): void
    {
        Notification::fake();

        config(['akreditasi.perbaikan_reminder_days_before' => 3]);

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;
        $pesantrenUser = $setup['pesantrenUser'];
        $asesorUser = $setup['asesorUser'];

        $admin = User::factory()->create(['role_id' => 1]);

        Carbon::setTestNow(Carbon::now());

        // Create expired rejection
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil', 'ipm.kurikulum'],
            'explanation' => 'Deadline will expire for this rejection',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->subDays(2),
            'status' => 'pending',
        ]);

        $this->rejectionService->processDeadlines();

        // Pesantren notified
        Notification::assertSentTo(
            $pesantrenUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'rejection_deadline_expired'
                    && str_contains($notification->message, 'batas waktu');
            }
        );

        // Asesor 1 notified
        Notification::assertSentTo(
            $asesorUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'rejection_deadline_expired_asesor'
                    && str_contains($notification->message, 'Pesantren tidak mengirimkan perbaikan');
            }
        );

        // Admin notified
        Notification::assertSentTo(
            $admin,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'rejection_deadline_expired_admin';
            }
        );

        Carbon::setTestNow();
    }

    /**
     * Task 18.5: createFinalRejection sends notification to pesantren with structured detail.
     *
     * Validates: Requirements 9.6
     */
public function test_final_rejection_notifies_pesantren_with_categories(): void
    {
        Notification::fake();

        $setup = $this->createAsesor1Setup(3); // status 3 for final rejection
        $pesantrenUser = $setup['pesantrenUser'];
        $akreditasiId = $setup['akreditasi']->id;

        $adminUser = User::factory()->create(['role_id' => 1]);

        $categories = [
            ['category' => 'nilai_tidak_memenuhi', 'explanation' => 'Nilai standar tidak tercapai pada beberapa komponen'],
            ['category' => 'inkonsistensi_data', 'explanation' => 'Data yang dilaporkan tidak konsisten dengan temuan lapangan'],
        ];

        $this->rejectionService->createFinalRejection($akreditasiId, $adminUser->id, $categories);

        Notification::assertSentTo(
            $pesantrenUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'final_rejection'
                    && $notification->title === 'Akreditasi Ditolak'
                    && str_contains($notification->message, 'validasi')
                    && str_contains($notification->message, 'Nilai Tidak Memenuhi Standar')
                    && str_contains($notification->message, 'Inkonsistensi Data');
            }
        );
    }

    /**
     * Task 18.6: processDeadlines sends reminder notification for approaching deadline.
     *
     * Validates: Requirements 8.3
     */
public function test_process_deadlines_sends_reminder_to_pesantren(): void
    {
        Notification::fake();

        config(['akreditasi.perbaikan_reminder_days_before' => 3]);

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;
        $pesantrenUser = $setup['pesantrenUser'];

        Carbon::setTestNow(Carbon::now());

        // Create rejection with deadline approaching (2 days from now, within 3-day threshold)
        AkreditasiRejection::create([
            'akreditasi_id' => $akreditasiId,
            'user_id' => $asesorUserId,
            'type' => 'asesor',
            'items' => ['profil'],
            'explanation' => 'Approaching deadline rejection',
            'rejection_number' => 1,
            'perbaikan_deadline' => now()->addDays(2),
            'status' => 'pending',
        ]);

        $this->rejectionService->processDeadlines();

        Notification::assertSentTo(
            $pesantrenUser,
            AkreditasiNotification::class,
            function (AkreditasiNotification $notification) {
                return $notification->type === 'perbaikan_deadline_reminder'
                    && $notification->title === 'Pengingat Deadline Perbaikan'
                    && str_contains($notification->message, 'hari');
            }
        );

        Carbon::setTestNow();
    }

    /**
     * Task 18.7 (additional): No notification sent when rejection validation fails.
     */
public function test_no_notification_sent_on_failed_rejection(): void
    {
        Notification::fake();

        $setup = $this->createAsesor1Setup();
        $akreditasiId = $setup['akreditasi']->id;
        $asesorUserId = $setup['asesorUser']->id;

        // Empty items should fail validation
        $result = $this->rejectionService->createRejection($akreditasiId, $asesorUserId, [], 'Some explanation text');

        $this->assertFalse($result['success']);
        Notification::assertNothingSent();
    }
}
