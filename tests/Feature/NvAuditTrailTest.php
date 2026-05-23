<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\AkreditasiAuditLog;
use App\Models\AkreditasiEdpm;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\MasterEdpmButir;
use App\Models\MasterEdpmKomponen;
use App\Models\Pesantren;
use App\Models\User;
use App\Services\AssessorScoringService;
use App\StateMachine\AkreditasiStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NvAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private AssessorScoringService $scoringService;

    /** @var array<int, MasterEdpmButir> */
    private array $butirs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->scoringService = app(AssessorScoringService::class);
        $this->seedMasterEdpmData();
    }

    /**
     * NV = NK → no reason needed, no audit trail logged.
     *
     * Validates Requirement 10.5: default NV mengikuti NK.
     */
    public function test_nv_matching_nk_does_not_require_reason_and_does_not_log_audit_trail(): void
    {
        $setup = $this->createNvTestSetup();

        $this->scoringService->saveNV(
            akreditasiId: $setup['akreditasi']->id,
            adminId: $setup['admin']->id,
            butirId: $this->butirs[0]->id,
            nvValue: 3,
            isFinal: true,
        );

        $this->assertDatabaseCount('akreditasi_audit_logs', 0);
        $this->assertDatabaseHas('akreditasi_edpms', [
            'akreditasi_id' => $setup['akreditasi']->id,
            'butir_id' => $this->butirs[0]->id,
            'nv' => 3,
            'is_final' => true,
        ]);
    }

    /**
     * NV ≠ NK + isFinal + no reason → throws DomainException.
     *
     * Validates Requirement 10.5: alasan perubahan NV wajib saat finalisasi.
     */
    public function test_nv_different_from_nk_when_final_without_reason_throws_exception(): void
    {
        $setup = $this->createNvTestSetup();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Alasan perubahan NV wajib diisi');

        $this->scoringService->saveNV(
            akreditasiId: $setup['akreditasi']->id,
            adminId: $setup['admin']->id,
            butirId: $this->butirs[0]->id,
            nvValue: 4,
            isFinal: true,
        );
    }

    /**
     * NV ≠ NK + isFinal + reason → NV saved, audit trail logged.
     *
     * Validates Requirement 10.5: audit trail menyimpan old_value (NK),
     * new_value (NV), reason, butir_id, ip_address, user_agent.
     */
    public function test_nv_change_with_reason_logs_audit_trail(): void
    {
        $setup = $this->createNvTestSetup();

        $reason = 'Nilai visitasi lapangan menunjukkan kualitas lebih tinggi dari penilaian awal.';

        $record = $this->scoringService->saveNV(
            akreditasiId: $setup['akreditasi']->id,
            adminId: $setup['admin']->id,
            butirId: $this->butirs[0]->id,
            nvValue: 4,
            isFinal: true,
            reason: $reason,
        );

        $this->assertSame(4, (int) $record->nv);
        $this->assertTrue((bool) $record->is_final);

        $this->assertDatabaseHas('akreditasi_audit_logs', [
            'akreditasi_id' => $setup['akreditasi']->id,
            'action_type' => 'nv_changed',
            'old_value' => '3',
            'new_value' => '4',
        ]);

        $log = AkreditasiAuditLog::where('action_type', 'nv_changed')->firstOrFail();
        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->user_agent);
        $this->assertSame($reason, $log->metadata['reason']);
        $this->assertSame($this->butirs[0]->id, $log->metadata['butir_id']);
    }

    /**
     * NV ≠ NK as draft → no reason needed, no audit trail logged.
     *
     * Business rule: audit trail enforced only when isFinal=true.
     */
    public function test_nv_different_from_nk_as_draft_does_not_require_reason(): void
    {
        $setup = $this->createNvTestSetup();

        $record = $this->scoringService->saveNV(
            akreditasiId: $setup['akreditasi']->id,
            adminId: $setup['admin']->id,
            butirId: $this->butirs[0]->id,
            nvValue: 4,
            isFinal: false,
        );

        $this->assertSame(4, (int) $record->nv);
        $this->assertFalse((bool) $record->is_final);
        $this->assertDatabaseCount('akreditasi_audit_logs', 0);
    }

    /**
     * Empty string reason should be treated as no reason and throw.
     */
    public function test_nv_change_with_empty_reason_throws_exception(): void
    {
        $setup = $this->createNvTestSetup();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Alasan perubahan NV wajib diisi');

        $this->scoringService->saveNV(
            akreditasiId: $setup['akreditasi']->id,
            adminId: $setup['admin']->id,
            butirId: $this->butirs[0]->id,
            nvValue: 4,
            isFinal: true,
            reason: '   ',
        );
    }

    /**
     * Whitespace-only reason should also be treated as empty.
     */
    public function test_nv_change_with_whitespace_only_reason_throws_exception(): void
    {
        $setup = $this->createNvTestSetup();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Alasan perubahan NV wajib diisi');

        $this->scoringService->saveNV(
            akreditasiId: $setup['akreditasi']->id,
            adminId: $setup['admin']->id,
            butirId: $this->butirs[0]->id,
            nvValue: 4,
            isFinal: true,
            reason: null,
        );
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function seedMasterEdpmData(): void
    {
        $komponen = MasterEdpmKomponen::create([
            'nama' => 'MUTU LULUSAN',
            'ipr' => null,
        ]);

        $butir = MasterEdpmButir::create([
            'komponen_id' => $komponen->id,
            'no_sk' => '1',
            'nomor_butir' => '1.1',
            'butir_pernyataan' => 'Butir 1.1 - Mutu Lulusan',
        ]);

        $this->butirs[] = $butir;
    }

    /**
     * Create test setup with admin, akreditasi at status 1 (Validasi Admin),
     * and one AkreditasiEdpm record with NK=3 from Asesor 1.
     *
     * @return array{admin: User, akreditasi: Akreditasi}
     */
    private function createNvTestSetup(): array
    {
        $admin = User::factory()->create(['role_id' => 1]);

        $pesantrenUser = User::factory()->create(['role_id' => 3]);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Test NV Audit Trail',
        ]);

        $asesorUser = User::factory()->create(['role_id' => 2]);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor Test, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Test',
        ]);

        $akreditasi = Akreditasi::create([
            'user_id' => $pesantrenUser->id,
            'status' => AkreditasiStateMachine::STATUS_VALIDASI_ADMIN,
        ]);

        Assessment::create([
            'akreditasi_id' => $akreditasi->id,
            'asesor_id' => $asesor->id,
            'tipe' => 1,
            'tanggal_mulai' => now()->subDays(3),
            'tanggal_berakhir' => now()->addDays(27),
        ]);

        // Asesor 1 EDPM record with NK = 3 (simulating finalized NK)
        AkreditasiEdpm::create([
            'akreditasi_id' => $akreditasi->id,
            'pesantren_id' => $akreditasi->user_id,
            'asesor_id' => $asesor->id,
            'butir_id' => $this->butirs[0]->id,
            'isian' => 3,
            'nk' => 3,
            'is_final' => true,
        ]);

        return compact('admin', 'akreditasi');
    }
}
