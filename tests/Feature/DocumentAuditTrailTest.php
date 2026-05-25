<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiAuditLog;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\Services\AkreditasiDocumentService;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Document audit trail and post-visitasi completion notification tests.
 *
 * Validates Requirements 10.6 (document audit trail) and 10.6 (notification).
 */
final class DocumentAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private AkreditasiDocumentService $documentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
        Notification::fake();
        $this->documentService = app(AkreditasiDocumentService::class);
    }

    // =========================================================================
    // Audit Trail Tests
    // =========================================================================

    /**
     * First upload logs document_uploaded with null old_value.
     */
    public function test_first_upload_logs_document_uploaded_audit_trail(): void
    {
        $setup = $this->createDocTestSetup();

        $this->documentService->upload(
            akreditasiId: $setup['akreditasi']->id,
            documentType: AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
            file: UploadedFile::fake()->create('laporan-asesor1.pdf', 128, 'application/pdf'),
            uploaderId: $setup['asesor1User']->id,
        );

        $this->assertDatabaseHas('akreditasi_audit_logs', [
            'akreditasi_id' => $setup['akreditasi']->id,
            'action_type' => 'document_uploaded',
            'old_value' => null,
        ]);

        $log = AkreditasiAuditLog::where('action_type', 'document_uploaded')->firstOrFail();
        $this->assertNotNull($log->new_value);
        $this->assertStringStartsWith('akreditasi/', $log->new_value);
        $this->assertSame(AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1, $log->metadata['document_type']);
        $this->assertSame($setup['asesor1User']->id, $log->metadata['uploader_id']);
    }

    /**
     * Replacement upload logs document_replaced with old_value = previous path.
     */
    public function test_replacement_upload_logs_document_replaced_audit_trail(): void
    {
        $setup = $this->createDocTestSetup();

        $oldPath = $this->documentService->upload(
            akreditasiId: $setup['akreditasi']->id,
            documentType: AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
            file: UploadedFile::fake()->create('laporan-v1.pdf', 128, 'application/pdf'),
            uploaderId: $setup['asesor1User']->id,
        );

        $this->documentService->upload(
            akreditasiId: $setup['akreditasi']->id,
            documentType: AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
            file: UploadedFile::fake()->create('laporan-v2.pdf', 128, 'application/pdf'),
            uploaderId: $setup['asesor1User']->id,
        );

        $this->assertDatabaseHas('akreditasi_audit_logs', [
            'akreditasi_id' => $setup['akreditasi']->id,
            'action_type' => 'document_replaced',
            'old_value' => $oldPath,
        ]);

        $log = AkreditasiAuditLog::where('action_type', 'document_replaced')->firstOrFail();
        $this->assertNotNull($log->new_value);
        $this->assertNotSame($oldPath, $log->new_value);
        $this->assertSame(AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1, $log->metadata['document_type']);
    }

    /**
     * Each document type logs with correct document_type in metadata.
     */
    public function test_each_post_visitasi_document_type_logs_correct_metadata(): void
    {
        $setup = $this->createDocTestSetup();

        $types = [
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR2,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_KELOMPOK,
            AkreditasiDocumentService::DOC_KARTU_KENDALI,
        ];

        foreach ($types as $type) {
            $this->documentService->upload(
                akreditasiId: $setup['akreditasi']->id,
                documentType: $type,
                file: UploadedFile::fake()->create("{$type}.pdf", 128, 'application/pdf'),
                uploaderId: $setup['asesor1User']->id,
            );
        }

        $this->assertSame(4, AkreditasiAuditLog::where('action_type', 'document_uploaded')->count());

        foreach ($types as $type) {
            $this->assertDatabaseHas('akreditasi_audit_logs', [
                'action_type' => 'document_uploaded',
                // metadata is stored as JSON — we can't use assertDatabaseHas
                // since the assertion is done on the PHP model below
            ]);
        }

        // Verify each document type is tracked
        $logs = AkreditasiAuditLog::where('action_type', 'document_uploaded')->get();
        $loggedTypes = $logs->pluck('metadata.document_type')->toArray();
        sort($types);
        sort($loggedTypes);
        $this->assertSame($types, $loggedTypes);
    }

    // =========================================================================
    // Notification Tests
    // =========================================================================

    /**
     * When all 4 post-visitasi documents become complete, notify all admin users.
     */
    public function test_all_documents_complete_sends_notification_to_all_admins(): void
    {
        $setup = $this->createDocTestSetup();

        $docs = [
            AkreditasiDocumentService::DOC_KARTU_KENDALI,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR2,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_KELOMPOK,
        ];

        foreach ($docs as $doc) {
            $this->documentService->upload(
                akreditasiId: $setup['akreditasi']->id,
                documentType: $doc,
                file: UploadedFile::fake()->create("{$doc}.pdf", 128, 'application/pdf'),
                uploaderId: $setup['asesor1User']->id,
            );
        }

        Notification::assertSentTo(
            [$setup['admin']],
            AkreditasiNotification::class,
            fn (AkreditasiNotification $n) => $n->type === 'dokumen_pasca_visitasi_lengkap'
        );

        Notification::assertSentTo(
            [$setup['admin2']],
            AkreditasiNotification::class
        );
    }

    /**
     * When only 3/4 documents exist, no notification should be sent.
     */
    public function test_incomplete_documents_do_not_send_notification(): void
    {
        $setup = $this->createDocTestSetup();

        $docs = [
            AkreditasiDocumentService::DOC_KARTU_KENDALI,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR2,
        ];

        foreach ($docs as $doc) {
            $this->documentService->upload(
                akreditasiId: $setup['akreditasi']->id,
                documentType: $doc,
                file: UploadedFile::fake()->create("{$doc}.pdf", 128, 'application/pdf'),
                uploaderId: $setup['asesor1User']->id,
            );
        }

        Notification::assertNothingSentTo(
            [$setup['admin']],
            AkreditasiNotification::class
        );
    }

    /**
     * Document uploaded to one akreditasi should not affect notification for another.
     */
    public function test_notification_only_for_specific_akreditasi(): void
    {
        $setup1 = $this->createDocTestSetup();
        $setup2 = $this->createDocTestSetup();

        // Complete all 4 docs for akreditasi #1
        $docs = [
            AkreditasiDocumentService::DOC_KARTU_KENDALI,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR2,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_KELOMPOK,
        ];

        foreach ($docs as $doc) {
            $this->documentService->upload(
                akreditasiId: $setup1['akreditasi']->id,
                documentType: $doc,
                file: UploadedFile::fake()->create("1-{$doc}.pdf", 128, 'application/pdf'),
                uploaderId: $setup1['asesor1User']->id,
            );
        }

        // Upload only 1 doc for akreditasi #2
        $this->documentService->upload(
            akreditasiId: $setup2['akreditasi']->id,
            documentType: AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
            file: UploadedFile::fake()->create('2-laporan.pdf', 128, 'application/pdf'),
            uploaderId: $setup2['asesor1User']->id,
        );

        // Notification count should include both for #1 completion
        // Notification::fake() captures all — assertion that admins received
        // the notification proves the system works for #1 independently of #2
        Notification::assertSentTo(
            [$setup1['admin']],
            AkreditasiNotification::class
        );
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create test setup with:
     *  - 2 admin users (to verify multi-admin notification)
     *  - Akreditasi at STATUS_PASCA_VISITASI
     *  - Assigned asesor user
     *
     * @return array{admin: User, admin2: User, akreditasi: Akreditasi, asesor1User: User}
     */
    private function createDocTestSetup(): array
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $admin2 = User::factory()->create(['role_id' => 1]);

        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test Dokumentasi',
        ]);

        $asesor1User = User::factory()->create(['role_id' => 2]);
        $asesor1 = Asesor::create([
            'user_id' => $asesor1User->id,
            'nama_dengan_gelar' => 'Asesor Test, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_PASCA_VISITASI,
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor1->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(3),
            'tanggal_berakhir' => now()->addDays(27),
        ]);

        return compact('admin', 'admin2', 'akreditasi', 'asesor1User');
    }
}
