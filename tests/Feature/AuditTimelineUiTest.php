<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuditTimelineUiTest extends TestCase
{
    private function metronicOverrideCss(): string
    {
        $entry = file_get_contents(resource_path('css/metronic-overrides.css'));
        $modules = collect(glob(resource_path('css/metronic-overrides/*.css')) ?: [])
            ->sort()
            ->map(fn (string $path): string => file_get_contents($path))
            ->implode("\n");

        return $entry."\n".$modules;
    }

    public function test_audit_timeline_uses_metronic_enterprise_stepper_contract(): void
    {
        $view = file_get_contents(resource_path('views/admin/akreditasi/detail/tabs/audit-trail.blade.php'));
        $css = $this->metronicOverrideCss();

        $this->assertStringContainsString('data-ui-audit-timeline="metronic"', $view);
        $this->assertStringContainsString('spm-audit-summary-grid', $view);
        $this->assertStringContainsString('spm-audit-stepper-panel', $view);
        $this->assertStringContainsString("\$loop->first ? 'current' : 'completed'", $view);
        $this->assertStringNotContainsString('x-collapse', $view);

        $this->assertStringContainsString('.spm-audit-summary-grid', $css);
        $this->assertStringContainsString('.spm-audit-stepper-panel', $css);
        $this->assertStringContainsString('.spm-audit-stepper .stepper-item.current', $css);
    }
}
