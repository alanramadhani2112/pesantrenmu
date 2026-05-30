<?php

namespace Tests\Unit\Document;

use App\Models\Akreditasi;
use App\Services\AkreditasiDocumentService;
use App\Services\AuditTrailService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Property-Based Test: Property 12 — Document Visibility Matrix
 *
 * For any combination of (user_role, document_type, akreditasi_status), the
 * system SHALL grant view access if and only if the combination matches the
 * defined visibility rules:
 *   - Kartu Kendali: visible only to Admin (role_id=1)
 *   - Laporan Visitasi (all types): visible only to Admin
 *   - Sertifikat SK: visible to Pesantren (role_id=3) only after status = 0
 *   - Asesor (role_id=2) cannot view Kartu Kendali
 *   - Pesantren cannot view any Laporan Visitasi
 *
 * **Validates: Requirements 8.5, 8.6, 8.7, 15.1, 15.2, 15.3, 15.4, 15.5**
 */
#[Group('akreditasi-workflow-redesign')]
class Property12DocumentVisibilityTest extends TestCase
{
    private AkreditasiDocumentService $svc;

    /** All valid akreditasi status values. */
    private const VALID_STATUSES = [-2, -1, 0, 1, 2, 3, 4, 5, 6];

    /** All role IDs in the domain. */
    private const ROLE_ADMIN = 1;

    private const ROLE_ASESOR = 2;

    private const ROLE_PESANTREN = 3;

    private const ROLE_SUPER_ADMIN = 4;

    private const ALL_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_ASESOR,
        self::ROLE_PESANTREN,
        self::ROLE_SUPER_ADMIN,
    ];

    /** All document types under test. */
    private const ALL_DOC_TYPES = [
        AkreditasiDocumentService::DOC_KARTU_KENDALI,
        AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
        AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR2,
        AkreditasiDocumentService::DOC_LAPORAN_VISITASI_KELOMPOK,
        AkreditasiDocumentService::DOC_SERTIFIKAT,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $auditTrail = $this->createMock(AuditTrailService::class);
        $this->svc = new AkreditasiDocumentService($auditTrail);
    }

    // -------------------------------------------------------------------------
    // Reference implementation of the visibility matrix
    // -------------------------------------------------------------------------

    /**
     * Reference implementation of the visibility matrix.
     *
     * Returns true if the given role should be able to view the document type
     * for an akreditasi at the given status.
     */
    private function expectedCanView(int $roleId, string $documentType, int $status): bool
    {
        $isAdmin = ($roleId === self::ROLE_ADMIN || $roleId === self::ROLE_SUPER_ADMIN);
        $isPesantren = ($roleId === self::ROLE_PESANTREN);

        return match ($documentType) {
            AkreditasiDocumentService::DOC_KARTU_KENDALI => $isAdmin,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1 => $isAdmin,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR2 => $isAdmin,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_KELOMPOK => $isAdmin,
            AkreditasiDocumentService::DOC_SERTIFIKAT => $isPesantren && ($status === 0),
            default => false,
        };
    }

    /**
     * Build a mock Akreditasi model with the given status.
     * Uses a plain stdClass to avoid database dependency.
     */
    private function makeAkreditasi(int $status): Akreditasi
    {
        /** @var Akreditasi $akreditasi */
        $akreditasi = new Akreditasi;
        $akreditasi->status = $status;

        return $akreditasi;
    }

    // =========================================================================
    // Part A — Exhaustive check: all combinations of role × doc_type × status
    // =========================================================================

    /**
     * Property 12 — Exhaustive: for every combination of (role, doc_type, status),
     * canView() returns the expected value from the visibility matrix.
     *
     * Total combinations: 4 roles × 5 doc types × 9 statuses = 180 checks.
     *
     * **Validates: Requirements 8.5, 8.6, 8.7, 15.1, 15.2, 15.3, 15.4, 15.5**
     */
    public function test_property12_exhaustive_all_combinations(): void
    {
        $checkedCount = 0;

        foreach (self::ALL_ROLES as $roleId) {
            foreach (self::ALL_DOC_TYPES as $docType) {
                foreach (self::VALID_STATUSES as $status) {
                    $akreditasi = $this->makeAkreditasi($status);
                    $userId = $roleId * 100; // arbitrary user ID

                    $expected = $this->expectedCanView($roleId, $docType, $status);
                    $actual = $this->svc->canView($userId, $roleId, $docType, $akreditasi);

                    $this->assertSame(
                        $expected,
                        $actual,
                        sprintf(
                            'canView(userId=%d, roleId=%d, docType=%s, status=%d) should return %s.',
                            $userId,
                            $roleId,
                            $docType,
                            $status,
                            $expected ? 'true' : 'false'
                        )
                    );

                    $checkedCount++;
                }
            }
        }

        // Sanity: 4 roles × 5 doc types × 9 statuses = 180 combinations
        $this->assertSame(180, $checkedCount, 'Expected exactly 180 combinations to be checked.');
    }

    // =========================================================================
    // Part B — Property-based: random combinations (100+ iterations)
    // =========================================================================

    /**
     * Property 12 — Random: for at least 200 randomly generated combinations
     * of (role, doc_type, status), canView() returns the expected value.
     *
     * **Validates: Requirements 8.5, 8.6, 8.7, 15.1, 15.2, 15.3, 15.4, 15.5**
     */
    public function test_property12_random_combinations_at_least_100_iterations(): void
    {
        $iterations = 200;
        $roles = self::ALL_ROLES;
        $docTypes = self::ALL_DOC_TYPES;
        $statuses = self::VALID_STATUSES;

        for ($i = 0; $i < $iterations; $i++) {
            $roleId = $roles[random_int(0, count($roles) - 1)];
            $docType = $docTypes[random_int(0, count($docTypes) - 1)];
            $status = $statuses[random_int(0, count($statuses) - 1)];
            $userId = random_int(1, 9999);

            $akreditasi = $this->makeAkreditasi($status);

            $expected = $this->expectedCanView($roleId, $docType, $status);
            $actual = $this->svc->canView($userId, $roleId, $docType, $akreditasi);

            $this->assertSame(
                $expected,
                $actual,
                sprintf(
                    'Iteration %d: canView(userId=%d, roleId=%d, docType=%s, status=%d) should return %s.',
                    $i,
                    $userId,
                    $roleId,
                    $docType,
                    $status,
                    $expected ? 'true' : 'false'
                )
            );
        }
    }

    // =========================================================================
    // Part C — Specific rule assertions (documentation / regression)
    // =========================================================================

    /**
     * Property 12 — Kartu Kendali: only Admin (role_id=1) can view.
     *
     * Asesor (role_id=2) and Pesantren (role_id=3) must be denied.
     *
     * **Validates: Requirements 15.1, 8.5**
     */
    public function test_property12_kartu_kendali_only_admin_can_view(): void
    {
        $docType = AkreditasiDocumentService::DOC_KARTU_KENDALI;

        foreach (self::VALID_STATUSES as $status) {
            $akreditasi = $this->makeAkreditasi($status);

            // Admin can view
            $this->assertTrue(
                $this->svc->canView(1, self::ROLE_ADMIN, $docType, $akreditasi),
                "Admin must be able to view Kartu Kendali at status {$status}."
            );

            // Asesor cannot view
            $this->assertFalse(
                $this->svc->canView(2, self::ROLE_ASESOR, $docType, $akreditasi),
                "Asesor must NOT be able to view Kartu Kendali at status {$status}."
            );

            // Pesantren cannot view
            $this->assertFalse(
                $this->svc->canView(3, self::ROLE_PESANTREN, $docType, $akreditasi),
                "Pesantren must NOT be able to view Kartu Kendali at status {$status}."
            );
        }
    }

    /**
     * Property 12 — Laporan Visitasi (all types): only Admin can view.
     *
     * Asesor and Pesantren must be denied for all laporan visitasi types.
     *
     * **Validates: Requirements 15.2, 15.3, 15.4, 8.6**
     */
    public function test_property12_laporan_visitasi_only_admin_can_view(): void
    {
        $laporanTypes = [
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR2,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_KELOMPOK,
        ];

        foreach ($laporanTypes as $docType) {
            foreach (self::VALID_STATUSES as $status) {
                $akreditasi = $this->makeAkreditasi($status);

                // Admin can view
                $this->assertTrue(
                    $this->svc->canView(1, self::ROLE_ADMIN, $docType, $akreditasi),
                    "Admin must be able to view {$docType} at status {$status}."
                );

                // Asesor cannot view
                $this->assertFalse(
                    $this->svc->canView(2, self::ROLE_ASESOR, $docType, $akreditasi),
                    "Asesor must NOT be able to view {$docType} at status {$status}."
                );

                // Pesantren cannot view
                $this->assertFalse(
                    $this->svc->canView(3, self::ROLE_PESANTREN, $docType, $akreditasi),
                    "Pesantren must NOT be able to view {$docType} at status {$status}."
                );
            }
        }
    }

    /**
     * Property 12 — Sertifikat SK: only Pesantren can view, and only at status = 0.
     *
     * Admin and Asesor must be denied. Pesantren must be denied at all statuses
     * except 0.
     *
     * **Validates: Requirements 15.5**
     */
    public function test_property12_sertifikat_only_pesantren_after_status_0(): void
    {
        $docType = AkreditasiDocumentService::DOC_SERTIFIKAT;

        foreach (self::VALID_STATUSES as $status) {
            $akreditasi = $this->makeAkreditasi($status);

            // Pesantren: only allowed at status 0
            $expectedPesantren = ($status === 0);
            $this->assertSame(
                $expectedPesantren,
                $this->svc->canView(3, self::ROLE_PESANTREN, $docType, $akreditasi),
                "Pesantren canView(sertifikat, status={$status}) should be ".($expectedPesantren ? 'true' : 'false').'.'
            );

            // Admin must NOT view sertifikat (Req 15.5: view by Pesantren only)
            $this->assertFalse(
                $this->svc->canView(1, self::ROLE_ADMIN, $docType, $akreditasi),
                "Admin must NOT be able to view Sertifikat at status {$status}."
            );

            // Asesor must NOT view sertifikat
            $this->assertFalse(
                $this->svc->canView(2, self::ROLE_ASESOR, $docType, $akreditasi),
                "Asesor must NOT be able to view Sertifikat at status {$status}."
            );
        }
    }

    /**
     * Property 12 — Sertifikat: Pesantren denied at all non-zero statuses.
     *
     * Run 200 iterations with random non-zero statuses.
     *
     * **Validates: Requirements 15.5**
     */
    public function test_property12_sertifikat_pesantren_denied_before_status_0(): void
    {
        $docType = AkreditasiDocumentService::DOC_SERTIFIKAT;
        $nonZero = array_filter(self::VALID_STATUSES, fn ($s) => $s !== 0);
        $nonZero = array_values($nonZero);
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            $status = $nonZero[random_int(0, count($nonZero) - 1)];
            $akreditasi = $this->makeAkreditasi($status);

            $this->assertFalse(
                $this->svc->canView(3, self::ROLE_PESANTREN, $docType, $akreditasi),
                "Iteration {$i}: Pesantren must NOT view Sertifikat at non-zero status {$status}."
            );
        }
    }

    /**
     * Property 12 — Asesor cannot view Kartu Kendali regardless of status.
     *
     * Run 200 iterations with random statuses.
     *
     * **Validates: Requirements 8.5, 15.1**
     */
    public function test_property12_asesor_cannot_view_kartu_kendali(): void
    {
        $docType = AkreditasiDocumentService::DOC_KARTU_KENDALI;
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            $status = self::VALID_STATUSES[random_int(0, count(self::VALID_STATUSES) - 1)];
            $akreditasi = $this->makeAkreditasi($status);

            $this->assertFalse(
                $this->svc->canView(2, self::ROLE_ASESOR, $docType, $akreditasi),
                "Iteration {$i}: Asesor must NOT view Kartu Kendali at status {$status}."
            );
        }
    }

    /**
     * Property 12 — Pesantren cannot view any Laporan Visitasi regardless of status.
     *
     * Run 200 iterations with random statuses and laporan types.
     *
     * **Validates: Requirements 8.6, 15.2, 15.3, 15.4**
     */
    public function test_property12_pesantren_cannot_view_laporan_visitasi(): void
    {
        $laporanTypes = [
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR2,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_KELOMPOK,
        ];
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            $status = self::VALID_STATUSES[random_int(0, count(self::VALID_STATUSES) - 1)];
            $docType = $laporanTypes[random_int(0, count($laporanTypes) - 1)];
            $akreditasi = $this->makeAkreditasi($status);

            $this->assertFalse(
                $this->svc->canView(3, self::ROLE_PESANTREN, $docType, $akreditasi),
                "Iteration {$i}: Pesantren must NOT view {$docType} at status {$status}."
            );
        }
    }

    /**
     * Property 12 — Admin can view all Laporan Visitasi and Kartu Kendali
     * at any status.
     *
     * Run 200 iterations with random statuses and admin-viewable doc types.
     *
     * **Validates: Requirements 8.7, 15.1, 15.2, 15.3, 15.4**
     */
    public function test_property12_admin_can_view_all_laporan_and_kartu_kendali(): void
    {
        $adminViewableTypes = [
            AkreditasiDocumentService::DOC_KARTU_KENDALI,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR1,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_ASESOR2,
            AkreditasiDocumentService::DOC_LAPORAN_VISITASI_KELOMPOK,
        ];
        $iterations = 200;

        for ($i = 0; $i < $iterations; $i++) {
            $status = self::VALID_STATUSES[random_int(0, count(self::VALID_STATUSES) - 1)];
            $docType = $adminViewableTypes[random_int(0, count($adminViewableTypes) - 1)];
            $akreditasi = $this->makeAkreditasi($status);

            $this->assertTrue(
                $this->svc->canView(1, self::ROLE_ADMIN, $docType, $akreditasi),
                "Iteration {$i}: Admin must be able to view {$docType} at status {$status}."
            );
        }
    }

    /**
     * Property 12 — Unknown document type is always denied for all roles.
     *
     * **Validates: Requirements 15.1–15.5 (deny-by-default)**
     */
    public function test_property12_unknown_document_type_always_denied(): void
    {
        $unknownType = 'unknown_document_type';

        foreach (self::ALL_ROLES as $roleId) {
            foreach (self::VALID_STATUSES as $status) {
                $akreditasi = $this->makeAkreditasi($status);

                $this->assertFalse(
                    $this->svc->canView($roleId, $roleId, $unknownType, $akreditasi),
                    "Unknown document type must always be denied for roleId={$roleId} at status={$status}."
                );
            }
        }
    }
}
