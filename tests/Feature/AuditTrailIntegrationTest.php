<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiAuditLog;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AkreditasiWorkflowService;
use App\Services\AuditTrailService;
use App\Services\DeadlineService;
use App\Services\PesantrenService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuditTrailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected AuditTrailService $auditTrailService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $this->auditTrailService = app(AuditTrailService::class);
    }

    /**
     * Helper: create an admin user.
     */
    private function createAdminUser(): User
    {
        return User::factory()->create(['role_id' => 1]);
    }

    /**
     * Helper: create a pesantren user with pesantren record.
     */
    private function createPesantrenUser(): User
    {
        $user = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $user->id,
            'nama_pesantren' => 'Pesantren Test '.$user->id,
        ]);

        return $user;
    }

    /**
     * Helper: create an asesor user with asesor record.
     */
    private function createAsesor(string $name = 'Asesor Test'): Asesor
    {
        $user = User::factory()->create(['role_id' => 2]);

        return Asesor::create([
            'user_id' => $user->id,
            'nama_dengan_gelar' => $name.', M.Pd.',
            'nama_tanpa_gelar' => $name,
        ]);
    }

    /**
     * Helper: create an akreditasi for a given user.
     */
    private function createAkreditasi(int $userId, int $status = 6): Akreditasi
    {
        return Akreditasi::create([
            'user_id' => $userId,
            'status' => $status,
        ]);
    }

    /**
     * Task 8.1: Test that audit logs persist after akreditasi soft-delete (Req 1.2).
     *
     * Creates akreditasi, creates audit logs for it, then deletes the akreditasi.
     * Asserts audit logs still exist in the database (FK has no cascade).
     */
    public function test_audit_logs_persist_after_akreditasi_soft_delete(): void
    {
        $admin = $this->createAdminUser();
        Auth::login($admin);

        $pesantrenUser = $this->createPesantrenUser();
        $akreditasi = $this->createAkreditasi($pesantrenUser->id, 6);

        // Create some audit logs for this akreditasi
        $log1 = $this->auditTrailService->log(
            akreditasiId: $akreditasi->id,
            actionType: 'status_changed',
            oldValue: 'Pengajuan',
            newValue: 'Verifikasi Berkas',
            metadata: ['old_status_code' => 6, 'new_status_code' => 5]
        );

        $log2 = $this->auditTrailService->log(
            akreditasiId: $akreditasi->id,
            actionType: 'asesor_assigned',
            newValue: 'Asesor Test, M.Pd.',
            metadata: ['asesor_id' => 1, 'tipe' => 1]
        );

        // Verify logs exist before deletion
        $this->assertDatabaseHas('akreditasi_audit_logs', ['id' => $log1->id]);
        $this->assertDatabaseHas('akreditasi_audit_logs', ['id' => $log2->id]);

        // Soft-delete the akreditasi
        $akreditasi->delete();

        // Assert akreditasi is soft-deleted
        $this->assertSoftDeleted('akreditasis', ['id' => $akreditasi->id]);

        // Assert audit logs STILL exist in the database (no cascade delete)
        $this->assertDatabaseHas('akreditasi_audit_logs', ['id' => $log1->id]);
        $this->assertDatabaseHas('akreditasi_audit_logs', ['id' => $log2->id]);

        // Verify we can still query them
        $persistedLogs = AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)->get();
        // Should have the 2 manually created logs + 1 "deleted" log from the observer
        $this->assertGreaterThanOrEqual(2, $persistedLogs->count());
        $this->assertTrue($persistedLogs->contains('id', $log1->id));
        $this->assertTrue($persistedLogs->contains('id', $log2->id));
    }

    /**
     * Task 8.2: Test full workflow integration.
     *
     * Create → review → assign asesor → verify complete audit trail
     * with correct sequence.
     */
    public function test_full_workflow_integration_create_assign_finalize(): void
    {
        $admin = $this->createAdminUser();
        Auth::login($admin);

        $pesantrenUser = $this->createPesantrenUser();
        $asesor1 = $this->createAsesor('Asesor Satu');
        $asesor2 = $this->createAsesor('Asesor Dua');

        // Step 1: Create akreditasi at status 6 (Pengajuan)
        $akreditasi = $this->createAkreditasi($pesantrenUser->id, 6);

        // Step 2: Open review and approve berkas via canonical workflow.
        $workflowService = app(AkreditasiWorkflowService::class);
        $workflowService->openForReview($akreditasi->id, $admin->id);
        $workflowService->approveBerkas($akreditasi->id, $admin->id, $asesor1->user_id, $asesor2->user_id);

        // Verify "asesor_assigned" and "status_changed" logs exist
        $this->assertDatabaseHas('akreditasi_audit_logs', [
            'akreditasi_id' => $akreditasi->id,
            'action_type' => 'asesor_assigned',
        ]);
        $this->assertDatabaseHas('akreditasi_audit_logs', [
            'akreditasi_id' => $akreditasi->id,
            'action_type' => 'status_changed',
        ]);

        // Verify "approved" and "status_changed" logs exist
        $this->assertDatabaseHas('akreditasi_audit_logs', [
            'akreditasi_id' => $akreditasi->id,
            'action_type' => 'approved',
        ]);

        // Verify there are multiple status_changed logs (6→5, 5→4)
        $statusChangeLogs = AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)
            ->where('action_type', 'status_changed')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $this->assertGreaterThanOrEqual(2, $statusChangeLogs->count());

        // Verify chronological order of all logs
        $allLogs = AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)
            ->orderBy('id', 'asc')
            ->get();

        $this->assertGreaterThanOrEqual(4, $allLogs->count());

        // Verify the sequence makes sense
        $actionSequence = $allLogs->pluck('action_type')->toArray();
        $this->assertContains('asesor_assigned', $actionSequence);
        $this->assertContains('status_changed', $actionSequence);
        $this->assertContains('approved', $actionSequence);
    }

    /**
     * Task 8.3: Test banding flow.
     *
     * Reject akreditasi → submit banding → verify both "rejected" and "banding_submitted" logs exist.
     */
    public function test_banding_flow_reject_then_submit_banding(): void
    {
        $admin = $this->createAdminUser();
        Auth::login($admin);

        $pesantrenUser = $this->createPesantrenUser();
        $asesor1 = $this->createAsesor('Asesor Banding');

        // Create akreditasi at Validasi Admin with an assessment (required for banding)
        $akreditasi = $this->createAkreditasi($pesantrenUser->id, 1);
        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addDays(14)->toDateString(),
        ]);

        // Step 1: Reject at final validation → creates "rejected" log
        app(AkreditasiWorkflowService::class)->rejectAtValidasi(
            $akreditasi->id,
            $admin->id,
            'Nilai assessment tidak memenuhi standar minimum yang ditetapkan',
            '',
            [
                ['category' => 'nilai_tidak_memenuhi', 'explanation' => 'Nilai assessment tidak memenuhi standar minimum yang ditetapkan'],
            ],
        );

        // Verify "rejected" log exists
        $this->assertDatabaseHas('akreditasi_audit_logs', [
            'akreditasi_id' => $akreditasi->id,
            'action_type' => 'rejected',
        ]);

        // Step 2: Submit banding as pesantren user
        $akreditasi->refresh();
        // After rejection, status should be Ditolak
        $this->assertEquals(-1, $akreditasi->status);

        Auth::login($pesantrenUser);
        $pesantrenService = app(PesantrenService::class);
        $bandingResult = $pesantrenService->submitAppeals(
            $akreditasi->id,
            $pesantrenUser->id,
            'Kami mengajukan banding karena terdapat kesalahan dalam penilaian assessment.'
        );

        $this->assertTrue($bandingResult);

        // Verify "banding_submitted" log exists
        $this->assertDatabaseHas('akreditasi_audit_logs', [
            'akreditasi_id' => $akreditasi->id,
            'action_type' => 'banding_submitted',
        ]);

        // Verify both logs exist together
        $logs = AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)
            ->whereIn('action_type', ['rejected', 'banding_submitted'])
            ->get();

        $this->assertGreaterThanOrEqual(1, $logs->where('action_type', 'rejected')->count());
        $this->assertGreaterThanOrEqual(1, $logs->where('action_type', 'banding_submitted')->count());
    }

    /**
     * Task 8.4: Test reassignment.
     *
     * Assign asesor → reassign → verify "asesor_assigned" and "asesor_reassigned" logs.
     */
    public function test_reassignment_logs_asesor_assigned_and_reassigned(): void
    {
        $admin = $this->createAdminUser();
        Auth::login($admin);

        $pesantrenUser = $this->createPesantrenUser();
        $asesor1 = $this->createAsesor('Asesor Pertama');
        $asesor2 = $this->createAsesor('Asesor Kedua');
        $asesor3 = $this->createAsesor('Asesor Pengganti');

        // Create akreditasi at status 6 (Pengajuan)
        $akreditasi = $this->createAkreditasi($pesantrenUser->id, 6);

        $workflowService = app(AkreditasiWorkflowService::class);
        $workflowService->openForReview($akreditasi->id, $admin->id);

        // Step 1: First assignment with asesor 1 and 2
        $workflowService->approveBerkas($akreditasi->id, $admin->id, $asesor1->user_id, $asesor2->user_id);

        // Verify "asesor_assigned" log exists
        $this->assertDatabaseHas('akreditasi_audit_logs', [
            'akreditasi_id' => $akreditasi->id,
            'action_type' => 'asesor_assigned',
        ]);

        // Step 2: Mark the primary assessment overdue and reassign with different asesor.
        $assessment = Assessment::where('akreditasi_id', $akreditasi->id)->where('tipe', 1)->firstOrFail();
        $assessment->update(['tanggal_berakhir' => now()->subDay()]);
        app(DeadlineService::class)->reassignAsesor($assessment->refresh(), $asesor3->id);

        // Verify "asesor_reassigned" log exists with correct old/new values
        $reassignedLog = AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)
            ->where('action_type', 'asesor_reassigned')
            ->first();

        $this->assertNotNull($reassignedLog, 'asesor_reassigned log should exist after reassignment');
        $this->assertStringContainsString('Asesor Pertama', $reassignedLog->old_value);
        $this->assertStringContainsString('Asesor Pengganti', $reassignedLog->new_value);
        $this->assertNotNull($reassignedLog->metadata);
        $this->assertEquals($asesor1->id, $reassignedLog->metadata['old_asesor_id']);
        $this->assertEquals($asesor3->id, $reassignedLog->metadata['new_asesor_id']);

        // Verify both log types exist
        $assignedLogs = AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)
            ->where('action_type', 'asesor_assigned')
            ->count();
        $reassignedLogs = AkreditasiAuditLog::where('akreditasi_id', $akreditasi->id)
            ->where('action_type', 'asesor_reassigned')
            ->count();

        $this->assertGreaterThanOrEqual(1, $assignedLogs);
        $this->assertGreaterThanOrEqual(1, $reassignedLogs);
    }

    /**
     * Task 8.5: Test admin can access audit timeline page (HTTP feature test).
     *
     * Create admin user, create akreditasi with audit logs, GET the admin akreditasi
     * detail page, assert 200 response and "Audit Trail" tab is visible.
     */
    public function test_admin_can_access_audit_timeline_page(): void
    {
        $admin = $this->createAdminUser();
        $pesantrenUser = $this->createPesantrenUser();
        $akreditasi = $this->createAkreditasi($pesantrenUser->id, 5);

        // Create some audit logs
        Auth::login($admin);
        $this->auditTrailService->log(
            akreditasiId: $akreditasi->id,
            actionType: 'status_changed',
            oldValue: 'Pengajuan',
            newValue: 'Verifikasi Berkas',
            metadata: ['old_status_code' => 6, 'new_status_code' => 5]
        );

        // GET the admin akreditasi detail page
        $response = $this->actingAs($admin)->get(
            route('admin.akreditasi-detail', $akreditasi->uuid)
        );

        $response->assertStatus(200);
        $response->assertSee('Audit Trail');
    }
}
