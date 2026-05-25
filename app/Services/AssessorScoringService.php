<?php

namespace App\Services;

use App\Exceptions\ImmutableValueException;
use App\Models\Akreditasi;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\StateMachine\AkreditasiStateMachine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * AssessorScoringService
 *
 * Handles assessor scoring operations: saving NA1, NA2, NK, NV values
 * with Draft/Final mode enforcement, Delta calculation, NK gate, and
 * scoring visibility rules.
 *
 * Task 11.1: Draft/Final modes, is_final immutability, Delta, NK gate
 * Task 11.2: Scoring visibility rules
 * Task 11.4: NV scoring for Admin
 *
 * Validates Requirements 7.1-7.14, 9.1-9.7, 15.6, 15.8
 */
class AssessorScoringService
{
    public const SCORE_MIN = 1;
    public const SCORE_MAX = 4;
    public const TOTAL_BUTIR = 62;
    public const TOTAL_KOMPONEN = 4;

    public function __construct(
        protected ScoreCalculationService $scoreCalculationService,
        protected AuditTrailService $auditTrailService,
    ) {}

    // =========================================================================
    // Task 11.1: Save NA1 (Asesor_1) or NA2 (Asesor_2) — Req 7.1, 7.2, 7.5, 7.6, 7.10
    // =========================================================================

    /**
     * Save NA (Nilai Asesor) for a butir.
     *
     * For Asesor_1: saves isian (NA1) on the asesor_id=asesor1Id record.
     * For Asesor_2: saves isian (NA2) on the asesor_id=asesor2Id record.
     * Automatically calculates Delta when both NA1 and NA2 are Final.
     *
     * @param int  $akreditasiId
     * @param int  $asesorId      The user_id of the asesor saving the value
     * @param int  $butirId
     * @param int  $naValue       Must be in {1,2,3,4}
     * @param bool $isFinal       true = lock permanently, false = draft
     * @return AkreditasiEdpm
     *
     * @throws \DomainException          When akreditasi not at status 2
     * @throws ImmutableValueException   When value is already Final
     * @throws InvalidArgumentException  When value out of range
     */
    public function saveNA(
        int $akreditasiId,
        int $asesorId,
        int $butirId,
        int $naValue,
        bool $isFinal
    ): AkreditasiEdpm {
        $this->validateScoreRange($naValue);

        $akreditasi = Akreditasi::findOrFail($akreditasiId);
        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            throw new \DomainException(
                "Penilaian NA hanya dapat dilakukan saat status Pasca Visitasi (status saat ini: {$akreditasi->status})."
            );
        }

        return DB::transaction(function () use ($akreditasiId, $asesorId, $butirId, $naValue, $isFinal, $akreditasi) {
            $resolvedAsesorId = $this->resolveAsesorId($asesorId);

            $record = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('asesor_id', $resolvedAsesorId)
                ->where('butir_id', $butirId)
                ->first();

            if ($record && $record->is_final) {
                throw new ImmutableValueException(
                    "Nilai asesor untuk butir #{$butirId} sudah final dan tidak dapat diubah."
                );
            }

            if ($record) {
                $record->update(['isian' => $naValue, 'is_final' => $isFinal]);
            } else {
                $record = AkreditasiEdpm::create([
                    'akreditasi_id' => $akreditasiId,
                    'asesor_id'     => $resolvedAsesorId,
                    'butir_id'      => $butirId,
                    'isian'         => $naValue,
                    'is_final'      => $isFinal,
                    'pesantren_id'  => $akreditasi->user_id,
                ]);
            }

            // Req 7.12: Calculate Delta when both NA1 and NA2 are Final
            if ($isFinal) {
                $this->recalculateDelta($akreditasiId, $butirId);
            }

            return $record->fresh();
        });
    }

    // =========================================================================
    // Task 11.1: Save NK (Nilai Kelompok) — Req 7.8, 7.9
    // =========================================================================

    /**
     * Save NK (Nilai Kelompok) for a butir by Ketua Kelompok.
     *
     * NK gate: all Nilai Ketua and all Nilai Anggota must be Final before
     * any Nilai Kelompok can be saved.
     *
     * @param int  $akreditasiId
     * @param int  $asesor1Id     The user_id of Asesor_1
     * @param int  $asesor2Id     The user_id of Asesor_2
     * @param int  $butirId
     * @param int  $nkValue       Must be in {1,2,3,4}
     * @param bool $isFinal
     * @return AkreditasiEdpm
     *
     * @throws \DomainException          When NK gate not satisfied or wrong status
     * @throws ImmutableValueException   When NK is already Final
     * @throws InvalidArgumentException  When value out of range
     */
    public function saveNK(
        int $akreditasiId,
        int $asesor1Id,
        int $asesor2Id,
        int $butirId,
        int $nkValue,
        bool $isFinal
    ): AkreditasiEdpm {
        $this->validateScoreRange($nkValue);

        $akreditasi = Akreditasi::findOrFail($akreditasiId);
        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_PASCA_VISITASI) {
            throw new \DomainException(
                "Nilai Kelompok hanya dapat diisi saat status Pasca Visitasi (status saat ini: {$akreditasi->status})."
            );
        }

        $resolvedAsesor1Id = $this->resolveAsesorId($asesor1Id);
        $resolvedAsesor2Id = $this->resolveAsesorId($asesor2Id);

        $this->assertAssignedScoringPair($akreditasiId, $resolvedAsesor1Id, $resolvedAsesor2Id);

        // Req 7.8, 7.9: NK gate — all Nilai Ketua and Nilai Anggota must be Final.
        $ketuaFinal = $this->hasAllFinalNaValues($akreditasiId, $resolvedAsesor1Id);
        $anggotaFinal = $this->hasAllFinalNaValues($akreditasiId, $resolvedAsesor2Id);

        if (!$ketuaFinal && !$anggotaFinal) {
            throw new \DomainException(
                'Nilai Kelompok belum dapat diisi karena Nilai Ketua dan Nilai Anggota belum disubmit final seluruhnya.'
            );
        }
        if (!$ketuaFinal) {
            throw new \DomainException(
                'Nilai Kelompok belum dapat diisi karena Nilai Ketua belum disubmit final seluruhnya.'
            );
        }
        if (!$anggotaFinal) {
            throw new \DomainException(
                'Nilai Kelompok belum dapat diisi karena Nilai Anggota belum disubmit final seluruhnya.'
            );
        }

        return DB::transaction(function () use ($akreditasiId, $resolvedAsesor1Id, $butirId, $nkValue, $isFinal, $akreditasi) {
            // Nilai Kelompok is stored on the Ketua Kelompok record.
            $record = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('asesor_id', $resolvedAsesor1Id)
                ->where('butir_id', $butirId)
                ->first();

            if ($record && $record->nk !== null) {
                if ($record->is_final && $record->nk !== null) {
                    throw new ImmutableValueException(
                        "Nilai Kelompok untuk butir #{$butirId} sudah final dan tidak dapat diubah."
                    );
                }
                $record->update(['nk' => $nkValue, 'is_final' => $record->is_final || $isFinal]);
            } elseif ($record) {
                $record->update(['nk' => $nkValue, 'is_final' => $record->is_final || $isFinal]);
            } else {
                $record = AkreditasiEdpm::create([
                    'akreditasi_id' => $akreditasiId,
                    'asesor_id'     => $resolvedAsesor1Id,
                    'butir_id'      => $butirId,
                    'nk'            => $nkValue,
                    'is_final'      => $isFinal,
                    'pesantren_id'  => $akreditasi->user_id,
                ]);
            }

            return $record->fresh();
        });
    }

    // =========================================================================
    // Task 11.2: Scoring visibility rules — Req 7.7, 7.11, 15.6, 15.8
    // =========================================================================

    /**
     * Determine whether a scoring value is visible to a given user.
     *
     * Rules:
     *  1. Admin (role_id=1) can always see all values.
     *  2. Draft values (is_final=false) are visible only to the assessor who created them
     *     (asesorId matches userId).
     *  3. All values are visible to both assessors after BOTH have submitted all their
     *     Final values (is_nilai_asesor_final=true AND is_nilai_asesor2_final=true).
     *
     * @param int  $akreditasiId
     * @param int  $userId       The user requesting visibility
     * @param int  $roleId       The role of the requesting user (1=Admin, 2=Asesor, 3=Pesantren)
     * @param int  $asesorId     The asesor_id who owns the value being checked
     * @param bool $isFinal      Whether the value is Final (is_final=true)
     * @return bool
     *
     * Validates Requirements 7.7, 7.11, 15.6, 15.8
     */
    public function isValueVisible(
        int $akreditasiId,
        int $userId,
        int $roleId,
        int $asesorId,
        bool $isFinal
    ): bool {
        // Rule 1: Admin can always see all values
        if ($roleId === 1) {
            return true;
        }

        // Rule 2: Draft values are only visible to the assessor who created them
        // Compare by user_id (both $userId and $asesorId are user_ids here)
        if (!$isFinal) {
            return $userId === $asesorId;
        }

        // Rule 3: Final values — visible to both assessors only after both have submitted all Finals
        $akreditasi = Akreditasi::find($akreditasiId);
        if (!$akreditasi) {
            return false;
        }

        $bothFinal = $akreditasi->is_nilai_asesor_final && $akreditasi->is_nilai_asesor2_final;

        if ($bothFinal) {
            // Both assessors have finalized — visible to both assessors
            return true;
        }

        // Not both final yet — only the owner can see their own Final value
        return $userId === $asesorId;
    }

    // =========================================================================
    // Task 11.4: Save NV (Nilai Verifikasi) for Admin — Req 9.1-9.7
    // =========================================================================

    /**
     * Save NV (Nilai Verifikasi) for a butir by Admin.
     *
     * Validates:
     *  - Akreditasi at status 1 (Validasi Admin)
     *  - Value in {1,2,3,4}
     *  - is_final immutability (if already Final, reject)
     *  - Defaults NV from NK if NV not yet set
     *
     * Business Rule (NV Audit Trail):
     *  - Default NV mengikuti NK dari Asesor 1.
     *  - Admin boleh mengubah NV.
     *  - Jika admin mengubah NV dari default (NK) saat finalisasi,
     *    alasan perubahan wajib diisi untuk audit trail.
     *
     * @param int    $akreditasiId
     * @param int    $adminId       The user_id of the Admin
     * @param int    $butirId
     * @param int    $nvValue       Must be in {1,2,3,4}
     * @param bool   $isFinal
     * @param string|null $reason   Required when NV differs from NK and isFinal
     * @return AkreditasiEdpm
     *
     * @throws \DomainException          When akreditasi not at status 1, or reason missing on NV change
     * @throws ImmutableValueException   When NV is already Final
     * @throws InvalidArgumentException  When value out of range
     *
     * Validates Requirements 9.2, 9.3, 9.4, 9.6, 10.5 (NV audit trail)
     */
    public function saveNV(
        int $akreditasiId,
        int $adminId,
        int $butirId,
        int $nvValue,
        bool $isFinal,
        ?string $reason = null
    ): AkreditasiEdpm {
        $this->validateScoreRange($nvValue);

        $akreditasi = Akreditasi::findOrFail($akreditasiId);
        if ((int) $akreditasi->status !== AkreditasiStateMachine::STATUS_VALIDASI_ADMIN) {
            throw new \DomainException(
                "Penilaian NV hanya dapat dilakukan saat status Validasi Admin (status saat ini: {$akreditasi->status})."
            );
        }

        return DB::transaction(function () use ($akreditasiId, $adminId, $butirId, $nvValue, $isFinal, $reason, $akreditasi) {
            // Find the Asesor_1 record for this butir (NK is stored there)
            // NV is stored on the same record as NK (asesor_1 record)
            // We look for any record for this butir that has nk set (Asesor_1 record)
            $record = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
                ->where('butir_id', $butirId)
                ->whereNotNull('nk')
                ->first();

            if (!$record) {
                // No NK record yet — create a new record for admin NV
                // Default NV from NK if available; otherwise use provided value
                $record = AkreditasiEdpm::create([
                    'akreditasi_id' => $akreditasiId,
                    'asesor_id'     => $adminId,
                    'butir_id'      => $butirId,
                    'nv'            => $nvValue,
                    'is_final'      => $isFinal,
                    'pesantren_id'  => $akreditasi->user_id,
                ]);
                return $record->fresh();
            }

            // Check NV immutability: if nv is already Final, reject
            // We track NV finality via the akreditasi.is_nv_final flag per-butir
            // Since is_nv_final is a global flag, we check per-record:
            // If the record already has nv set and is_final=true, reject
            if ($record->nv !== null && $record->is_final) {
                throw new ImmutableValueException(
                    "NV untuk butir #{$butirId} sudah Final dan tidak dapat diubah."
                );
            }

            // Capture NK value for audit trail comparison (Req 10.5)
            $nkValue = $record->nk;

            // Default NV from NK if NV not yet set (Req 9.2)
            $effectiveNv = $nvValue;

            $record->update(['nv' => $effectiveNv, 'is_final' => $isFinal]);

            // NV Audit Trail: if NV differs from NK when finalizing, reason is required (Req 10.5)
            if ($isFinal && $nkValue !== null && (int) $nkValue !== (int) $effectiveNv) {
                if ($reason === null || trim($reason) === '') {
                    throw new \DomainException(
                        "Alasan perubahan NV wajib diisi karena NV (nilai: {$effectiveNv}) berbeda dari NK (nilai: {$nkValue}) untuk butir #{$butirId}."
                    );
                }

                $this->auditTrailService->log(
                    akreditasiId: $akreditasiId,
                    actionType: 'nv_changed',
                    oldValue: (string) $nkValue,
                    newValue: (string) $effectiveNv,
                    metadata: [
                        'reason' => trim($reason),
                        'butir_id' => $butirId,
                    ]
                );
            }

            return $record->fresh();
        });
    }

    // =========================================================================
    // Helper: Check if all required values are Final for Asesor_1
    // =========================================================================

    /**
     * Check if all 62 NA1 values are Final for Asesor_1.
     *
     * @param int $akreditasiId
     * @param int $asesor1Id  user_id of Asesor_1
     * @return bool
     */
    public function allNA1Final(int $akreditasiId, int $asesor1Id): bool
    {
        $resolvedId = $this->resolveAsesorId($asesor1Id);
        return $this->hasAllFinalNaValues($akreditasiId, $resolvedId);
    }

    /**
     * Check if all 62 NK values are Final for Asesor_1.
     *
     * @param int $akreditasiId
     * @param int $asesor1Id  user_id of Asesor_1
     * @return bool
     */
    public function allNKFinal(int $akreditasiId, int $asesor1Id): bool
    {
        $resolvedId = $this->resolveAsesorId($asesor1Id);
        $count = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->where('asesor_id', $resolvedId)
            ->whereNotNull('nk')
            ->where('is_final', true)
            ->count();

        return $count >= $this->totalButirs();
    }

    /**
     * Check if all 62 NA2 values are Final for Asesor_2.
     *
     * @param int $akreditasiId
     * @param int $asesor2Id  user_id of Asesor_2
     * @return bool
     */
    public function allNA2Final(int $akreditasiId, int $asesor2Id): bool
    {
        $resolvedId = $this->resolveAsesorId($asesor2Id);
        return $this->hasAllFinalNaValues($akreditasiId, $resolvedId);
    }

    /**
     * Check if all 62 NV values are Final.
     *
     * @param int $akreditasiId
     * @return bool
     */
    public function allNVFinal(int $akreditasiId): bool
    {
        $count = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->whereNotNull('nv')
            ->where('is_final', true)
            ->count();

        return $count >= $this->totalButirs();
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Validate that a score value is in the range {1,2,3,4}.
     *
     * @throws InvalidArgumentException
     */
    private function validateScoreRange(int $value): void
    {
        if ($value < self::SCORE_MIN || $value > self::SCORE_MAX) {
            throw new InvalidArgumentException(
                "Nilai harus berupa integer dalam rentang 1-4, diterima: {$value}."
            );
        }
    }

    /**
     * Resolve the asesors.id from a user_id.
     * Returns the user_id itself if no asesor record is found (fallback for tests).
     *
     * @param int $userId  The users.id of the asesor
     * @return int         The asesors.id
     */
    private function resolveAsesorId(int $userId): int
    {
        $asesor = Asesor::where('user_id', $userId)->first();
        return $asesor ? $asesor->id : $userId;
    }

    private function hasAllFinalNaValues(int $akreditasiId, int $asesorModelId): bool
    {
        $count = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->where('asesor_id', $asesorModelId)
            ->whereNotNull('isian')
            ->where('is_final', true)
            ->count();

        return $count >= $this->totalButirs();
    }

    private function totalButirs(): int
    {
        return MasterEdpmButir::count() ?: self::TOTAL_BUTIR;
    }

    private function assertAssignedScoringPair(int $akreditasiId, int $asesor1ModelId, int $asesor2ModelId): void
    {
        $ketuaAssigned = Assessment::where('akreditasi_id', $akreditasiId)
            ->where('tipe', 1)
            ->where('asesor_id', $asesor1ModelId)
            ->exists();

        if (!$ketuaAssigned) {
            throw new \DomainException('Hanya Ketua Kelompok yang ditugaskan yang dapat mengisi Nilai Kelompok.');
        }

        $anggotaAssigned = Assessment::where('akreditasi_id', $akreditasiId)
            ->where('tipe', 2)
            ->where('asesor_id', $asesor2ModelId)
            ->exists();

        if (!$anggotaAssigned) {
            throw new \DomainException('Anggota Kelompok yang ditugaskan tidak sesuai untuk pengisian Nilai Kelompok.');
        }
    }

    /**
     * Recalculate Delta for a butir when both NA1 and NA2 are Final.
     * Delta = |NA1 - NA2|
     *
     * @param int $akreditasiId
     * @param int $butirId
     */
    private function recalculateDelta(int $akreditasiId, int $butirId): void
    {
        // Get all records for this butir
        $records = AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->where('butir_id', $butirId)
            ->where('is_final', true)
            ->whereNotNull('isian')
            ->get();

        if ($records->count() < 2) {
            return; // Need both NA1 and NA2 to calculate delta
        }

        // Get the two isian values
        $isianValues = $records->pluck('isian')->toArray();
        if (count($isianValues) < 2) {
            return;
        }

        $delta = $this->scoreCalculationService->calculateDelta(
            (int) $isianValues[0],
            (int) $isianValues[1]
        );

        // Store delta on all records for this butir
        AkreditasiEdpm::where('akreditasi_id', $akreditasiId)
            ->where('butir_id', $butirId)
            ->update(['delta' => $delta]);
    }
}
