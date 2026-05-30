<?php

namespace Tests\Feature;

use App\Livewire\Pages\Asesor\AkreditasiDetail;
use App\Services\AkreditasiService;
use App\Services\AkreditasiWorkflowService;
use App\Services\AsesorService;
use App\Services\RejectionService;
use Tests\TestCase;

class BusinessFlowLegacyCleanupTest extends TestCase
{
    public function test_legacy_business_flow_mutation_entry_points_are_removed(): void
    {
        $this->assertFalse(method_exists(AkreditasiService::class, 'approvePengajuan'));
        $this->assertFalse(method_exists(AkreditasiService::class, 'rejectPengajuan'));
        $this->assertFalse(method_exists(AkreditasiService::class, 'rescheduleVisitasi'));
        $this->assertFalse(method_exists(AkreditasiService::class, 'finalizeAkreditasi'));

        $this->assertFalse(method_exists(AsesorService::class, 'processVisitasi'));
        $this->assertFalse(method_exists(AsesorService::class, 'finalizeVerification'));
        $this->assertFalse(method_exists(AsesorService::class, 'uploadLaporanVisitasi'));

        $this->assertFalse(method_exists(RejectionService::class, 'createRejection'));
        $this->assertFalse(method_exists(RejectionService::class, 'createFinalRejection'));
    }

    public function test_legacy_resubmission_flow_is_removed_from_active_application(): void
    {
        $this->assertFileDoesNotExist(app_path('Services/ResubmissionService.php'));
        $this->assertFalse(method_exists(AkreditasiWorkflowService::class, 'createResubmission'));

        foreach ([
            resource_path('views/livewire/pages/pesantren/akreditasi.blade.php'),
            resource_path('views/livewire/pages/pesantren/akreditasi-detail.blade.php'),
            resource_path('views/livewire/pages/admin/akreditasi.blade.php'),
            resource_path('views/livewire/pages/admin/akreditasi-detail.blade.php'),
            resource_path('js/app.js'),
        ] as $path) {
            $contents = file_get_contents($path);

            $this->assertStringNotContainsString('ResubmissionService', $contents, $path);
            $this->assertStringNotContainsString('createResubmission', $contents, $path);
            $this->assertStringNotContainsString('Pengajuan Ulang', $contents, $path);
            $this->assertStringNotContainsString('pengajuan ulang', $contents, $path);
            $this->assertStringNotContainsString('Ajukan Ulang', $contents, $path);
            $this->assertStringNotContainsString('ajukan ulang', $contents, $path);
            $this->assertStringNotContainsString('confirmResubmit', $contents, $path);
        }
    }

    public function test_asesor_detail_uses_canonical_post_visitasi_document_handlers(): void
    {
        $this->assertFalse(method_exists(AkreditasiDetail::class, 'finalizeVerification'));
        $this->assertFalse(method_exists(AkreditasiDetail::class, 'uploadLaporanVisitasi'));

        $this->assertTrue(method_exists(AkreditasiDetail::class, 'finalizeScoring'));
        $this->assertTrue(method_exists(AkreditasiDetail::class, 'uploadLaporanIndividu'));
        $this->assertTrue(method_exists(AkreditasiDetail::class, 'uploadLaporanKelompok'));
    }
}
