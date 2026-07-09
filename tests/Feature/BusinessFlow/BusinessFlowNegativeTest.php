<?php

namespace Tests\Feature\BusinessFlow;

use App\Exceptions\InvalidTransitionException;
use App\Services\AkreditasiWorkflowService;
use App\StateMachine\AkreditasiStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessFlowNegativeTest extends TestCase
{
    use BusinessFlowTestHelpers;
    use RefreshDatabase;

    private AkreditasiWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBusinessFlowBase();
        $this->workflow = app(AkreditasiWorkflowService::class);
    }

    public function test_incomplete_pesantren_cannot_submit_pengajuan(): void
    {
        $pesantren = $this->createIncompletePesantrenUser();

        try {
            $this->workflow->submitPengajuan($pesantren->id);
            $this->fail('Incomplete pesantren submitted akreditasi.');
        } catch (\DomainException) {
            $this->assertDatabaseMissing('akreditasis', ['user_id' => $pesantren->id]);
            $this->assertFalse((bool) $pesantren->pesantren->fresh()->is_locked);
        }
    }

    public function test_admin_cannot_assign_same_asesor_twice(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.neg.assign.pesantren@test.local');
        $admin = $this->createUser('bf.neg.assign.admin@test.local', 1, 'BF Neg Admin');
        $asesor = $this->createAsesorUser('bf.neg.assign.asesor@test.local', 'BF Neg Asesor');
        $akreditasi = $this->createAkreditasi($pesantren, 5, 'BF-NEG-008');

        try {
            $this->workflow->approveBerkas($akreditasi->id, $admin->id, $asesor->id, $asesor->id);
            $this->fail('Same asesor assigned twice.');
        } catch (\DomainException) {
            $this->assertNoStatusChange($akreditasi, 5);
            $this->assertDatabaseMissing('assessments', ['akreditasi_id' => $akreditasi->id]);
        }
    }

    public function test_unassigned_asesor_cannot_schedule_visitasi(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.neg.unassigned.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.neg.unassigned.asesor1@test.local', 'BF Assigned 1');
        $asesor2 = $this->createAsesorUser('bf.neg.unassigned.asesor2@test.local', 'BF Assigned 2');
        $unassigned = $this->createAsesorUser('bf.neg.unassigned.asesor3@test.local', 'BF Unassigned');
        $akreditasi = $this->createAkreditasi($pesantren, 4, 'BF-NEG-003');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);

        try {
            $this->workflow->scheduleVisitasi($akreditasi->id, $unassigned->id, [
                'tanggal_mulai' => now()->addDays(8)->toDateString(),
                'tanggal_akhir' => now()->addDays(9)->toDateString(),
                'catatan_visitasi' => 'Unassigned must fail',
            ]);
            $this->fail('Unassigned asesor scheduled visitasi.');
        } catch (\DomainException) {
            $this->assertNoStatusChange($akreditasi, 4);
            $this->assertNull($akreditasi->fresh()->tgl_visitasi);
        }
    }

    public function test_invalid_visitasi_date_range_is_rejected(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.neg.visitasi.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.neg.visitasi.asesor1@test.local', 'BF Visitasi 1');
        $asesor2 = $this->createAsesorUser('bf.neg.visitasi.asesor2@test.local', 'BF Visitasi 2');
        $akreditasi = $this->createAkreditasi($pesantren, 4, 'BF-NEG-007');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);

        try {
            $this->workflow->scheduleVisitasi($akreditasi->id, $asesor1->id, [
                'tanggal_mulai' => now()->addDays(8)->toDateString(),
                'tanggal_akhir' => now()->addDays(30)->toDateString(),
                'catatan_visitasi' => 'Too long',
            ]);
            $this->fail('Invalid visitasi range accepted.');
        } catch (\DomainException) {
            $this->assertNoStatusChange($akreditasi, 4);
            $this->assertNull($akreditasi->fresh()->tgl_visitasi);
        }
    }

    public function test_finalize_scoring_requires_complete_scores_and_documents(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.neg.scoring.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.neg.scoring.asesor1@test.local', 'BF Scoring 1');
        $asesor2 = $this->createAsesorUser('bf.neg.scoring.asesor2@test.local', 'BF Scoring 2');
        $akreditasi = $this->createAkreditasi($pesantren, 2, 'BF-NEG-009');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);

        try {
            $this->workflow->finalizeAssessorScoring($akreditasi->id, $asesor1->id);
            $this->fail('Incomplete scoring finalized.');
        } catch (\DomainException) {
            $this->assertNoStatusChange($akreditasi, 2);
            $this->assertFalse((bool) $akreditasi->fresh()->is_nilai_asesor_final);
        }
    }

    public function test_issue_sk_requires_validasi_admin_status_and_nv(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.neg.sk.pesantren@test.local');
        $admin = $this->createUser('bf.neg.sk.admin@test.local', 1, 'BF SK Admin');
        $akreditasi = $this->createAkreditasi($pesantren, 2, 'BF-NEG-010');

        try {
            $this->workflow->issueSK($akreditasi->id, $admin->id, [
                'nomor_sk' => '',
                'masa_berlaku' => now()->toDateString(),
                'masa_berlaku_akhir' => now()->addYear()->toDateString(),
                'sertifikat_path' => 'bf/sertifikat-invalid.pdf',
            ]);
            $this->fail('Invalid SK issued.');
        } catch (\DomainException) {
            $this->assertNoStatusChange($akreditasi, 2);
            $this->assertNull($akreditasi->fresh()->nomor_sk);
            $this->assertNull($akreditasi->fresh()->sertifikat_path);
        }
    }

    public function test_terminal_status_cannot_mutate_back_into_workflow(): void
    {
        $admin = $this->createUser('bf.neg.terminal.admin@test.local', 1, 'BF Terminal Admin');
        $pesantren = $this->createCompletePesantrenUser('bf.neg.terminal.pesantren@test.local');
        $akreditasi = $this->createAkreditasi($pesantren, 0, 'BF-NEG-004');

        try {
            app(AkreditasiStateMachine::class)->transition($akreditasi, 1, $admin);
            $this->fail('Terminal status mutated.');
        } catch (InvalidTransitionException) {
            $this->assertNoStatusChange($akreditasi, 0);
            $this->assertNoTransitionAudit($akreditasi, 1);
        }
    }

    public function test_invalid_state_transitions_are_blocked(): void
    {
        $admin = $this->createUser('bf.neg.transition.admin@test.local', 1, 'BF Transition Admin');
        $pesantren = $this->createCompletePesantrenUser('bf.neg.transition.pesantren@test.local');

        foreach ([[6, 4], [6, 0], [5, 3], [4, 1], [3, 0], [-1, 0]] as [$from, $to]) {
            $akreditasi = $this->createAkreditasi($pesantren, $from, "BF-NEG-006-$from-$to");

            try {
                app(AkreditasiStateMachine::class)->transition($akreditasi, $to, $admin);
                $this->fail("Invalid transition $from -> $to accepted.");
            } catch (InvalidTransitionException) {
                $this->assertNoStatusChange($akreditasi, $from);
                $this->assertNoTransitionAudit($akreditasi, $to);
            }
        }
    }
}
