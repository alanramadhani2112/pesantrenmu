<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    public function test_health_endpoint_and_security_headers_are_available(): void
    {
        $this->get('/up')->assertOk();

        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
    }

    public function test_bootstrap_registers_production_hardening_middleware(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertStringContainsString("health: '/up'", $bootstrap);
        $this->assertStringContainsString('TrustProxies::class', $bootstrap);
        $this->assertStringContainsString('SecurityHeaders::class', $bootstrap);
    }

    public function test_env_example_uses_production_safe_defaults(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        foreach ([
            'APP_ENV=production',
            'APP_DEBUG=false',
            'DB_CONNECTION=mysql',
            'LOG_LEVEL=warning',
            'SESSION_DRIVER=database',
            'SESSION_ENCRYPT=true',
            'SESSION_SECURE_COOKIE=true',
            'SESSION_HTTP_ONLY=true',
            'CACHE_STORE=database',
            'QUEUE_CONNECTION=database',
            'SENTRY_SEND_DEFAULT_PII=false',
            'TRUSTED_PROXIES=',
        ] as $expected) {
            $this->assertStringContainsString($expected, $env);
        }
    }

    public function test_composer_has_repeatable_production_check_and_cache_scripts(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
        $scripts = $composer['scripts'] ?? [];

        $this->assertArrayHasKey('prod:check', $scripts);
        $prodCheck = implode(' ', $scripts['prod:check']);
        $this->assertStringContainsString('ProductionReadinessTest.php', $prodCheck);
        $this->assertStringContainsString('PerformanceOptimizationTest.php', $prodCheck);
        $this->assertStringContainsString('MetronicFrontendTest.php', $prodCheck);

        $this->assertArrayHasKey('perf:cache', $scripts);
        $this->assertContains('@php artisan config:cache', $scripts['perf:cache']);
        $this->assertContains('@php artisan route:cache', $scripts['perf:cache']);
        $this->assertContains('@php artisan event:cache', $scripts['perf:cache']);
        $this->assertContains('@php artisan view:cache', $scripts['perf:cache']);
    }

    public function test_deployment_docs_cover_operational_runtime(): void
    {
        $deployment = file_get_contents(base_path('docs/DEPLOYMENT.md'));
        $audit = file_get_contents(base_path('docs/production-readiness-audit.md'));
        $combinedDocs = $deployment."\n".$audit;

        foreach ([
            'php artisan migrate --force',
            'php artisan storage:link',
            'queue:work',
            'schedule:run',
            'SENTRY_LARAVEL_DSN',
            'backup',
            'APP_DEBUG=false',
            'SESSION_ENCRYPT=true',
        ] as $expected) {
            $this->assertStringContainsString($expected, $combinedDocs);
        }
    }

    public function test_production_readiness_audit_is_current_checkpoint(): void
    {
        $audit = file_get_contents(base_path('docs/production-readiness-audit.md'));

        $this->assertStringContainsString('Tanggal Checkpoint: 27 Mei 2026', $audit);
        $this->assertStringContainsString('Status: Production candidate dengan checklist eksternal tersisa', $audit);
        $this->assertStringNotContainsString('BELUM SIAP PRODUCTION', $audit);
        $this->assertStringNotContainsString('223 test gagal', $audit);
    }
}
