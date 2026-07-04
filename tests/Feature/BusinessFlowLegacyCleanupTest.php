<?php

namespace Tests\Feature;

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
        ] as $path) {
            $this->assertFileDoesNotExist($path, "{$path} should be removed.");
        }

        $jsContents = file_get_contents(resource_path('js/app.js'));
        $this->assertStringNotContainsString('ResubmissionService', $jsContents);
        $this->assertStringNotContainsString('createResubmission', $jsContents);
        $this->assertStringNotContainsString('Pengajuan Ulang', $jsContents);
        $this->assertStringNotContainsString('pengajuan ulang', $jsContents);
        $this->assertStringNotContainsString('Ajukan Ulang', $jsContents);
        $this->assertStringNotContainsString('ajukan ulang', $jsContents);
        $this->assertStringNotContainsString('confirmResubmit', $jsContents);
    }

    public function test_frontend_runtime_has_no_livewire_entry_points(): void
    {
        $markers = [
            '@livewire',
            '<livewire',
            'wire:',
            '@entangle',
            '$wire',
            'callWire',
            'adminManagement',
            'akreditasiPesantren',
            'akreditasiManagement',
            'asesorManagement',
            'fileManagement',
            'ipmManagement',
            'sdmManagement',
            'edpmManagement',
        ];

        foreach ([resource_path('js'), resource_path('views')] as $root) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

            foreach ($iterator as $file) {
                if (! $file->isFile() || ! in_array($file->getExtension(), ['js', 'php'], true)) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                foreach ($markers as $marker) {
                    $this->assertStringNotContainsString($marker, $contents, "{$marker} remains in {$file->getPathname()}");
                }
            }
        }
    }

    public function test_asesor_detail_uses_canonical_post_visitasi_document_handlers(): void
    {
        $this->markTestSkipped('Asesor AkreditasiDetail migrated to plain Blade controller.');
    }
}
