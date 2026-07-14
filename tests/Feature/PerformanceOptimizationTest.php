<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Assessment;
use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_performance_cache_script_and_docs_exist(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
        $scripts = $composer['scripts'] ?? [];

        $this->assertArrayHasKey('perf:cache', $scripts);
        $this->assertArrayHasKey('perf:clear', $scripts);
        $this->assertContains('@php artisan config:cache', $scripts['perf:cache']);
        $this->assertContains('@php artisan route:cache', $scripts['perf:cache']);
        $this->assertContains('@php artisan event:cache', $scripts['perf:cache']);
        $this->assertContains('@php artisan view:cache', $scripts['perf:cache']);

        $docs = file_get_contents(base_path('docs/performance-optimization.md'));
        $this->assertStringContainsString('APP_DEBUG=false', $docs);
        $this->assertStringContainsString('CACHE_STORE=database', $docs);
        $this->assertStringContainsString('SESSION_DRIVER=database', $docs);
        $this->assertStringContainsString('OPcache', $docs);
    }

    public function test_app_and_guest_layouts_follow_metronic_bundle_order_while_public_pages_stay_light(): void
    {
        foreach ([
            resource_path('views/layouts/app.blade.php'),
            resource_path('views/layouts/guest.blade.php'),
        ] as $path) {
            $source = file_get_contents($path);

            $this->assertStringContainsString('vendor/metronic/assets/plugins/global/plugins.bundle.css', $source);
            $this->assertStringContainsString('vendor/metronic/assets/css/style.bundle.css', $source);
            $this->assertStringContainsString('vendor/metronic/assets/plugins/global/plugins.bundle.js', $source);
            $this->assertStringContainsString('vendor/metronic/assets/js/scripts.bundle.js', $source);
        }

        foreach ([
            resource_path('views/welcome.blade.php'),
            resource_path('views/errors/403.blade.php'),
            resource_path('views/errors/404.blade.php'),
            resource_path('views/errors/419.blade.php'),
            resource_path('views/errors/429.blade.php'),
            resource_path('views/errors/500.blade.php'),
            resource_path('views/errors/503.blade.php'),
        ] as $path) {
            $source = file_get_contents($path);

            $this->assertStringNotContainsString('vendor/metronic/assets/plugins/global/plugins.bundle.js', $source);
        }
    }

    public function test_detail_page_polling_is_visible_and_throttled(): void
    {
        $this->markTestSkipped('Detail pages migrated to Blade — no polling contract remains.');
    }

    public function test_asesor_dashboard_uses_single_aggregate_status_query(): void
    {
        $this->seed(RoleSeeder::class);

        $asesorUser = User::factory()->create(['role_id' => 2, 'name' => 'Asesor Cepat']);
        $asesor = Asesor::create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Asesor Cepat, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Cepat',
        ]);

        $pesantrenUser = User::factory()->create(['role_id' => 3, 'name' => 'Pesantren Cepat']);
        Pesantren::create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pesantren Cepat',
        ]);

        foreach ([5, 4, 3, 0, -1] as $status) {
            $akreditasi = Akreditasi::create([
                'user_id' => $pesantrenUser->id,
                'status' => $status,
            ]);

            Assessment::create([
                'akreditasi_id' => $akreditasi->id,
                'asesor_id' => $asesor->id,
                'tipe' => 1,
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_berakhir' => now()->addDays(7)->toDateString(),
            ]);
        }

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->actingAs($asesorUser)
            ->get('/dashboard')
            ->assertOk();

        $aggregateQueries = collect($queries)->filter(fn (string $sql): bool => str_contains($sql, 'sum(case')
            && str_contains($sql, 'assessments')
            && str_contains($sql, 'akreditasis')
        );

        $assessmentCountQueries = collect($queries)->filter(fn (string $sql): bool => str_contains($sql, 'count(*) as aggregate')
            && str_contains($sql, 'assessments')
        );

        $this->assertCount(1, $aggregateQueries);
        $this->assertLessThanOrEqual(1, $assessmentCountQueries->count());
    }
}
