<?php

namespace App\Services;

use App\Models\Akreditasi;
use App\Models\Assessment;
use App\Models\User;
use App\Notifications\AkreditasiNotification;
use App\StateMachine\AkreditasiStateMachine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * AkreditasiDocumentService
 *
 * Manages akreditasi workflow document uploads with MIME type/size validation
 * and visibility enforcement per the Document Visibility Matrix (Req 15.1–15.8).
 *
 * Role IDs:
 *   Admin      = 1
 *   Asesor     = 2  (tipe=1 → Asesor_1, tipe=2 → Asesor_2)
 *   Pesantren  = 3
 *   Super_Admin = 4
 *
 * Document type constants map to columns on the akreditasis table.
 */
class AkreditasiDocumentService
{
    // -------------------------------------------------------------------------
    // MIME type and size constants
    // -------------------------------------------------------------------------

    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /** Maximum file size for general documents (5 MB). */
    public const MAX_SIZE_BYTES = 5 * 1024 * 1024;

    /** Maximum file size for sertifikat SK (10 MB). */
    public const SK_MAX_SIZE_BYTES = 10 * 1024 * 1024;

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function __construct(
        protected AuditTrailService $auditTrailService,
    ) {}

    // -------------------------------------------------------------------------
    // Document type constants (map to akreditasis table columns)
    // -------------------------------------------------------------------------

    public const DOC_KARTU_KENDALI              = 'kartu_kendali';
    public const DOC_LAPORAN_VISITASI_ASESOR1   = 'laporan_visitasi_asesor1';
    public const DOC_LAPORAN_VISITASI_ASESOR2   = 'laporan_visitasi_asesor2';
    public const DOC_LAPORAN_VISITASI_KELOMPOK  = 'laporan_visitasi_kelompok';
    public const DOC_SERTIFIKAT                 = 'sertifikat_path';

    public const REQUIRED_POST_VISITASI_DOCUMENTS = [
        self::DOC_LAPORAN_VISITASI_ASESOR1,
        self::DOC_LAPORAN_VISITASI_ASESOR2,
        self::DOC_LAPORAN_VISITASI_KELOMPOK,
        self::DOC_KARTU_KENDALI,
    ];

    // -------------------------------------------------------------------------
    // Role ID constants
    // -------------------------------------------------------------------------

    private const ROLE_ADMIN     = 1;
    private const ROLE_ASESOR    = 2;
    private const ROLE_PESANTREN = 3;

    // -------------------------------------------------------------------------
    // Task 5.2 — validateFile()
    // -------------------------------------------------------------------------

    /**
     * Validate a file's MIME type and size for the given document type.
     *
     * Returns an array of error strings. An empty array means the file is valid.
     *
     * @param  UploadedFile  $file
     * @param  string        $documentType  One of the DOC_* constants
     * @return string[]
     */
    public function validateFile(UploadedFile $file, string $documentType): array
    {
        $errors = [];

        // MIME type validation
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            $errors[] = 'Tipe file tidak diizinkan. Hanya PDF dan DOCX yang diterima.';
        }

        // Size validation — sertifikat uses a larger limit
        $maxBytes = ($documentType === self::DOC_SERTIFIKAT)
            ? self::SK_MAX_SIZE_BYTES
            : self::MAX_SIZE_BYTES;

        if ($file->getSize() > $maxBytes) {
            $maxMb = $maxBytes / (1024 * 1024);
            $errors[] = "Ukuran file melebihi batas maksimum {$maxMb} MB.";
        }

        return $errors;
    }

    // -------------------------------------------------------------------------
    // Task 5.3 — upload()
    // -------------------------------------------------------------------------

    /**
     * Store a document file and return the stored path.
     *
     * If a previous file exists for the same akreditasi + document type, it is
     * deleted from storage before the new file is saved (re-upload replacement).
     *
     * @param  int           $akreditasiId
     * @param  string        $documentType  One of the DOC_* constants
     * @param  UploadedFile  $file
     * @param  int           $uploaderId    ID of the user performing the upload
     * @return string        The stored file path
     *
     * @throws \RuntimeException  If the file cannot be stored
     */
    public function upload(
        int $akreditasiId,
        string $documentType,
        UploadedFile $file,
        int $uploaderId
    ): string {
        $akreditasi = Akreditasi::findOrFail($akreditasiId);

        // Capture existing path BEFORE deletion (for audit trail comparison)
        $existingPath = $akreditasi->{$documentType};

        // Delete previous file if it exists (re-upload replacement)
        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        // Store the new file under a structured directory
        $directory = "akreditasi/{$akreditasiId}/{$documentType}";
        $storedPath = $file->store($directory, 'public');

        if ($storedPath === false) {
            throw new \RuntimeException("Gagal menyimpan file untuk dokumen '{$documentType}'.");
        }

        // Persist the path on the akreditasi record
        $akreditasi->update([$documentType => $storedPath]);

        // Audit Trail: log document upload or replacement (Req 10.6)
        $actionType = $existingPath ? 'document_replaced' : 'document_uploaded';
        $this->auditTrailService->log(
            akreditasiId: $akreditasiId,
            actionType: $actionType,
            oldValue: $existingPath ?? null,
            newValue: $storedPath,
            metadata: [
                'document_type' => $documentType,
                'uploader_id' => $uploaderId,
            ]
        );

        // Check if all post-visitasi docs are now complete and notify admins
        $this->checkDocumentsComplete($akreditasiId);

        return $storedPath;
    }

    /**
     * Check if all post-visitasi documents are complete and notify admins.
     *
     * Business Rule (Req 10.6):
     *  - When all 4 REQUIRED_POST_VISITASI_DOCUMENTS become available,
     *    notify all admin users via AkreditasiNotification.
     */
    private function checkDocumentsComplete(int $akreditasiId): void
    {
        $akreditasi = Akreditasi::findOrFail($akreditasiId);

        foreach (self::REQUIRED_POST_VISITASI_DOCUMENTS as $doc) {
            if (empty($akreditasi->{$doc})) {
                return;
            }
        }

        $admins = User::whereHas('role', fn ($q) => $q->where('id', self::ROLE_ADMIN))->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new AkreditasiNotification(
                'dokumen_pasca_visitasi_lengkap',
                'Dokumen Penilaian Pasca Visitasi Lengkap',
                "Semua dokumen penilaian pasca visitasi telah lengkap dan siap untuk divalidasi.",
                route('admin.akreditasi-detail', $akreditasi->uuid)
            ));
        }
    }

    public function uploadKartuKendaliForPesantren(
        int $akreditasiId,
        int $pesantrenUserId,
        UploadedFile $file
    ): string {
        $akreditasi = Akreditasi::findOrFail($akreditasiId);

        if ((int) $akreditasi->user_id !== $pesantrenUserId) {
            throw new \DomainException('Pesantren hanya dapat mengunggah Kartu Kendali untuk pengajuan miliknya.');
        }

        $this->assertPascaVisitasi($akreditasi, 'Kartu Kendali hanya dapat diunggah pada tahap Penilaian Pasca Visitasi.');

        return $this->validatedUpload($akreditasi, self::DOC_KARTU_KENDALI, $file, $pesantrenUserId);
    }

    public function uploadLaporanIndividuForAsesor(
        int $akreditasiId,
        int $asesorUserId,
        UploadedFile $file
    ): string {
        $akreditasi = Akreditasi::findOrFail($akreditasiId);
        $assessment = $this->findAssignedAssessment($akreditasi->id, $asesorUserId);

        if (!$assessment) {
            throw new \DomainException('Hanya asesor yang ditugaskan yang dapat mengunggah laporan individu.');
        }

        $this->assertPascaVisitasi($akreditasi, 'Laporan individu hanya dapat diunggah pada tahap Penilaian Pasca Visitasi.');

        $documentType = (int) $assessment->tipe === 1
            ? self::DOC_LAPORAN_VISITASI_ASESOR1
            : self::DOC_LAPORAN_VISITASI_ASESOR2;

        return $this->validatedUpload($akreditasi, $documentType, $file, $asesorUserId);
    }

    public function uploadLaporanKelompokForAsesor1(
        int $akreditasiId,
        int $asesorUserId,
        UploadedFile $file
    ): string {
        $akreditasi = Akreditasi::findOrFail($akreditasiId);
        $assessment = $this->findAssignedAssessment($akreditasi->id, $asesorUserId);

        if (!$assessment || (int) $assessment->tipe !== 1) {
            throw new \DomainException('Hanya Ketua Kelompok yang ditugaskan yang dapat mengunggah laporan kelompok.');
        }

        $this->assertPascaVisitasi($akreditasi, 'Laporan kelompok hanya dapat diunggah pada tahap Penilaian Pasca Visitasi.');

        return $this->validatedUpload($akreditasi, self::DOC_LAPORAN_VISITASI_KELOMPOK, $file, $asesorUserId);
    }

    /**
     * @return string[]
     */
    public function missingPostVisitasiDocuments(Akreditasi $akreditasi): array
    {
        return array_values(array_filter(
            self::REQUIRED_POST_VISITASI_DOCUMENTS,
            fn (string $documentType) => empty($akreditasi->{$documentType})
        ));
    }

    // -------------------------------------------------------------------------
    // Task 5.4 — canView()
    // -------------------------------------------------------------------------

    /**
     * Enforce the Document Visibility Matrix.
     *
     * Returns true if the given user (identified by userId + roleId) is allowed
     * to view the specified document type for the given akreditasi.
     *
     * Visibility rules (Requirements 15.1–15.8):
     *   kartu_kendali              → view: Admin only
     *   laporan_visitasi_asesor1   → view: Admin only
     *   laporan_visitasi_asesor2   → view: Admin only
     *   laporan_visitasi_kelompok  → view: Admin only
     *   sertifikat_path            → view: Pesantren only, and only when status = 0
     *
     * Super_Admin (role_id=4) is treated as Admin for visibility purposes.
     *
     * @param  int         $userId
     * @param  int         $roleId
     * @param  string      $documentType  One of the DOC_* constants
     * @param  Akreditasi  $akreditasi
     * @return bool
     */
    public function canView(
        int $userId,
        int $roleId,
        string $documentType,
        Akreditasi $akreditasi
    ): bool {
        $isAdmin     = ($roleId === self::ROLE_ADMIN || $roleId === 4); // Super_Admin also has admin access
        $isPesantren = ($roleId === self::ROLE_PESANTREN);
        $isAsesor    = ($roleId === self::ROLE_ASESOR);

        return match ($documentType) {
            // Kartu Kendali: only Admin can view (Req 15.1, 8.5)
            self::DOC_KARTU_KENDALI => $isAdmin,

            // Laporan Visitasi (all types): only Admin can view (Req 15.2, 15.3, 15.4, 8.6)
            self::DOC_LAPORAN_VISITASI_ASESOR1  => $isAdmin,
            self::DOC_LAPORAN_VISITASI_ASESOR2  => $isAdmin,
            self::DOC_LAPORAN_VISITASI_KELOMPOK => $isAdmin,

            // Sertifikat SK: only Pesantren can view, and only after status = 0 (Req 15.5)
            self::DOC_SERTIFIKAT => $isPesantren && ($akreditasi->status === 0),

            // Unknown document type: deny by default
            default => false,
        };
    }

    // -------------------------------------------------------------------------
    // Task 5.5 — getDocument()
    // -------------------------------------------------------------------------

    /**
     * Return the stored file path for a document type, or null if not uploaded.
     *
     * @param  int     $akreditasiId
     * @param  string  $documentType  One of the DOC_* constants
     * @return string|null
     */
    public function getDocument(int $akreditasiId, string $documentType): ?string
    {
        $akreditasi = Akreditasi::find($akreditasiId);

        if (!$akreditasi) {
            return null;
        }

        $path = $akreditasi->{$documentType};

        return ($path !== null && $path !== '') ? $path : null;
    }

    private function validatedUpload(
        Akreditasi $akreditasi,
        string $documentType,
        UploadedFile $file,
        int $uploaderId
    ): string {
        $errors = $this->validateFile($file, $documentType);
        if (!empty($errors)) {
            throw new \DomainException(implode(' ', $errors));
        }

        return $this->upload($akreditasi->id, $documentType, $file, $uploaderId);
    }

    private function assertPascaVisitasi(Akreditasi $akreditasi, string $message): void
    {
        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            throw new \DomainException($message);
        }
    }

    private function findAssignedAssessment(int $akreditasiId, int $asesorUserId): ?Assessment
    {
        return Assessment::query()
            ->where('akreditasi_id', $akreditasiId)
            ->whereHas('asesor', fn ($query) => $query->where('user_id', $asesorUserId))
            ->first();
    }
}
