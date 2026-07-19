<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SecurityPerformanceE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asesor;

    private User $pesantren;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(TestDataSeeder::class);

        $this->admin = User::where('email', 'bf.admin@test.local')->firstOrFail();
        $this->asesor = User::where('email', 'bf.asesor1@test.local')->firstOrFail();
        $this->pesantren = User::where('email', 'bf.pesantren@test.local')->firstOrFail();
    }

    public function test_critical_e2e_pages_emit_security_headers(): void
    {
        $pages = [
            [$this->admin, route('admin.akreditasi', absolute: false)],
            [$this->asesor, route('asesor.akreditasi', absolute: false)],
            [$this->pesantren, route('pesantren.akreditasi', absolute: false)],
        ];

        foreach ($pages as [$user, $path]) {
            $response = $this->actingAs($user)->get($path);

            $response->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');

            $csp = $response->headers->get('Content-Security-Policy', '');

            $this->assertStringContainsString("default-src 'self'", $csp);
            $this->assertStringContainsString("object-src 'none'", $csp);
            $this->assertStringContainsString("frame-ancestors 'self'", $csp);
            $this->assertStringContainsString("form-action 'self'", $csp);
        }
    }

    public function test_e2e_index_pages_stay_under_query_budget(): void
    {
        $budgets = [
            [$this->admin, route('admin.akreditasi', absolute: false), 90],
            [$this->asesor, route('asesor.akreditasi', absolute: false), 90],
            [$this->pesantren, route('pesantren.akreditasi', absolute: false), 90],
        ];

        foreach ($budgets as [$user, $path, $budget]) {
            $queries = 0;

            DB::listen(static function () use (&$queries): void {
                $queries++;
            });

            $this->actingAs($user)->get($path)->assertOk();

            $this->assertLessThanOrEqual($budget, $queries, "{$path} used {$queries} DB queries.");
        }
    }

    public function test_production_hardening_middleware_contract_stays_wired(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertStringContainsString('SecurityHeaders::class', $bootstrap);
        $this->assertStringContainsString('TrustProxies::class', $bootstrap);
        $this->assertStringContainsString("ThrottleRequests::class.':web'", $bootstrap);
    }
}
