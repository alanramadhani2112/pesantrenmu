<?php

namespace Tests\Feature\BusinessFlow;

use App\Models\Banding;
use App\Services\AkreditasiWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessFlowBandingTest extends TestCase
{
    use RefreshDatabase;
    use BusinessFlowTestHelpers;

    private AkreditasiWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBusinessFlowBase();
        $this->workflow = app(AkreditasiWorkflowService::class);
    }

    public function test_pesantren_can_submit_valid_banding_after_rejected_assessed_akreditasi(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.banding.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.banding.asesor1@test.local', 'BF Banding Asesor 1');
        $asesor2 = $this->createAsesorUser('bf.banding.asesor2@test.local', 'BF Banding Asesor 2');
        $akreditasi = $this->createAkreditasi($pesantren, -1, 'BF-BANDING-001');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);

        $this->workflow->submitBanding($akreditasi->id, $pesantren->id, str_repeat('Alasan banding valid. ', 4));

        $this->assertSame(-2, (int) $akreditasi->fresh()->status);
        $this->assertDatabaseHas('bandings', [
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantren->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_accept_banding_back_to_validasi_admin(): void
    {
        $admin = $this->createUser('bf.banding.accept.admin@test.local', 1, 'BF Banding Admin');
        $pesantren = $this->createCompletePesantrenUser('bf.banding.accept.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.banding.accept.asesor1@test.local', 'BF Accept Asesor 1');
        $asesor2 = $this->createAsesorUser('bf.banding.accept.asesor2@test.local', 'BF Accept Asesor 2');
        $akreditasi = $this->createAkreditasi($pesantren, -2, 'BF-BANDING-002');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);
        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantren->id,
            'status' => 'pending',
            'alasan' => str_repeat('Alasan banding valid. ', 4),
        ]);

        $this->workflow->decideBanding($banding->id, $admin->id, 'diterima', 'Banding diterima untuk validasi ulang.');

        $this->assertSame(1, (int) $akreditasi->fresh()->status);
        $this->assertSame('accepted', $banding->fresh()->status);
        $this->assertSame($admin->id, (int) $banding->fresh()->reviewer_id);
    }

    public function test_admin_can_reject_banding_back_to_ditolak(): void
    {
        $admin = $this->createUser('bf.banding.reject.admin@test.local', 1, 'BF Banding Reject Admin');
        $pesantren = $this->createCompletePesantrenUser('bf.banding.reject.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.banding.reject.asesor1@test.local', 'BF Reject Asesor 1');
        $asesor2 = $this->createAsesorUser('bf.banding.reject.asesor2@test.local', 'BF Reject Asesor 2');
        $akreditasi = $this->createAkreditasi($pesantren, -2, 'BF-BANDING-003');
        $this->assignAsesors($akreditasi, $asesor1, $asesor2);
        $banding = Banding::create([
            'akreditasi_id' => $akreditasi->id,
            'user_id' => $pesantren->id,
            'status' => 'pending',
            'alasan' => str_repeat('Alasan banding valid. ', 4),
        ]);

        $this->workflow->decideBanding($banding->id, $admin->id, 'ditolak', 'Banding ditolak karena bukti belum cukup.');

        $this->assertSame(-1, (int) $akreditasi->fresh()->status);
        $this->assertSame('rejected', $banding->fresh()->status);
    }

    public function test_banding_is_rejected_when_status_not_ditolak_or_duplicate(): void
    {
        $pesantren = $this->createCompletePesantrenUser('bf.banding.invalid.pesantren@test.local');
        $asesor1 = $this->createAsesorUser('bf.banding.invalid.asesor1@test.local', 'BF Invalid Asesor 1');
        $asesor2 = $this->createAsesorUser('bf.banding.invalid.asesor2@test.local', 'BF Invalid Asesor 2');
        $active = $this->createAkreditasi($pesantren, 1, 'BF-BANDING-INVALID-STATUS');

        try {
            $this->workflow->submitBanding($active->id, $pesantren->id, str_repeat('Alasan banding valid. ', 4));
            $this->fail('Banding submitted for non-rejected akreditasi.');
        } catch (\DomainException) {
            $this->assertNoStatusChange($active, 1);
            $this->assertDatabaseMissing('bandings', ['akreditasi_id' => $active->id]);
        }

        $rejected = $this->createAkreditasi($pesantren, -1, 'BF-BANDING-DUPLICATE');
        $this->assignAsesors($rejected, $asesor1, $asesor2);
        Banding::create([
            'akreditasi_id' => $rejected->id,
            'user_id' => $pesantren->id,
            'status' => 'rejected',
            'alasan' => str_repeat('Alasan banding existing. ', 4),
        ]);

        try {
            $this->workflow->submitBanding($rejected->id, $pesantren->id, str_repeat('Alasan banding valid. ', 4));
            $this->fail('Duplicate banding submitted.');
        } catch (\DomainException) {
            $this->assertNoStatusChange($rejected, -1);
            $this->assertSame(1, Banding::where('akreditasi_id', $rejected->id)->count());
        }
    }
}
