<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Document;
use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class MetronicFrontendTest extends TestCase
{
    use RefreshDatabase;

    private function metronicOverrideCss(): string
    {
        $entry = file_get_contents(resource_path('css/metronic-overrides.css'));
        $modules = collect(glob(resource_path('css/metronic-overrides/*.css')) ?: [])
            ->sort()
            ->map(fn (string $path): string => file_get_contents($path))
            ->implode("\n");

        return $entry."\n".$modules;
    }

    private function viewSourceWithDescendantPartials(string $path): string
    {
        $absolutePath = base_path($path);
        $source = file_get_contents($absolutePath);
        $partialRoot = preg_replace('/\.blade\.php$/', '', $absolutePath);

        if (! is_dir($partialRoot)) {
            return $source;
        }

        $partials = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($partialRoot)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $partials[] = $file->getPathname();
            }
        }

        sort($partials);

        foreach ($partials as $partial) {
            $source .= "\n".file_get_contents($partial);
        }

        return $source;
    }

    public function test_public_landing_page_describes_pesantren_accreditation_without_legacy_copy(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Dikembangkan oleh')
            ->assertSee('LabMu')
            ->assertSee('Sistem Akreditasi Pesantren')
            ->assertSee('Sistem akreditasi pesantren yang lebih tertata.')
            ->assertSee('PesantrenMu membantu proses akreditasi berjalan lebih mudah, transparan, dan terpusat dalam satu sistem.')
            ->assertSee('spm-landing-container', false)
            ->assertDontSee('untuk LP2M', false)
            ->assertDontSee('Dikdasmen', false)
            ->assertDontSee('didaksmen', false)
            ->assertDontSee('didaksemen', false);

        $contentWithoutFooter = preg_replace('/<footer\b[^>]*>.*<\/footer>/sU', '', $response->getContent()) ?? '';

        $this->assertStringNotContainsString('LabMu', $contentWithoutFooter);
        $this->assertStringNotContainsString('Dikembangkan oleh LabMu', $contentWithoutFooter);
    }

    public function test_login_page_loads_metronic_foundation_assets(): void
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertOk()
            ->assertSee('vendor/metronic/assets/plugins/global/plugins.bundle.css', false)
            ->assertSee('vendor/metronic/assets/css/style.bundle.css', false)
            ->assertSee('vendor/metronic/assets/plugins/global/plugins.bundle.js', false)
            ->assertSee('vendor/metronic/assets/js/scripts.bundle.js', false)
            ->assertDontSee('fonts.bunny.net', false);
    }

    public function test_unused_full_metronic_public_assets_are_removed(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('assets'));
        $this->assertFileExists(public_path('vendor/metronic/assets/css/style.bundle.css'));
        $this->assertFileExists(public_path('vendor/metronic/assets/plugins/global/plugins.bundle.css'));
    }

    public function test_metronic_browser_smoke_contract_for_shell_runtime(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $header = file_get_contents(resource_path('views/components/layout/app-header.blade.php'));
        $sidebar = file_get_contents(resource_path('views/components/layout/app-sidebar.blade.php'));
        $appJs = file_get_contents(resource_path('js/app.js'));

        $this->assertLessThan(
            strpos($layout, 'vendor/metronic/assets/css/style.bundle.css'),
            strpos($layout, 'vendor/metronic/assets/plugins/global/plugins.bundle.css')
        );
        $this->assertLessThan(
            strpos($layout, 'vendor/metronic/assets/js/scripts.bundle.js'),
            strpos($layout, 'vendor/metronic/assets/plugins/global/plugins.bundle.js')
        );
        $this->assertLessThan(
            strpos($layout, "resources/js/app.js"),
            strpos($layout, 'vendor/metronic/assets/js/scripts.bundle.js')
        );

        $this->assertStringContainsString('<html', $layout);
        $this->assertStringContainsString('data-bs-theme="light"', explode("\n", $layout)[1] ?? '');
        $this->assertStringNotContainsString('<body data-bs-theme="light"', $layout);

        $this->assertStringContainsString('id="kt_app_sidebar_mobile_toggle"', $header);
        $this->assertStringContainsString('data-kt-menu-trigger="click"', $header);
        $this->assertStringContainsString('data-kt-menu="true"', $header);
        $this->assertStringContainsString('data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle"', $sidebar);
        $this->assertStringContainsString('data-kt-drawer-dismiss="true"', $sidebar);
        $this->assertStringNotContainsString('$store.sidebar', $sidebar);
        $this->assertStringContainsString('if (window.KTUtil) return;', $appJs);
    }

    public function test_metronic_ui_components_render_reusable_classes(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.button variant="primary">Masuk</x-ui.button>
            <x-ui.button type="submit" variant="primary">Simpan</x-ui.button>
            <x-ui.badge variant="success">Aktif</x-ui.badge>
            <x-ui.card title="Ringkasan">Konten</x-ui.card>
            <x-ui.breadcrumb :items="[['label' => 'Dashboard', 'url' => '/dashboard'], ['label' => 'Akreditasi']]" />
            <x-ui.sidebar-section :compact="true">MASTER DATA</x-ui.sidebar-section>
            <x-ui.tabs>
                <x-ui.tab :active="true">Komponen EDPM</x-ui.tab>
            </x-ui.tabs>
            <x-ui.icon-button icon="trash" label="Hapus data" variant="danger" />
            <x-ui.filter-select model="statusFilter" placeholder="Semua Status" :options="['active' => 'Aktif']" />
            <x-ui.status-badge variant="success">Aktif</x-ui.status-badge>
            <x-ui.section-card title="Komponen">Isi</x-ui.section-card>
            <x-ui.modal-header title="Tambah Dokumen" subtitle="Kelola dokumen" icon="document" />
            <x-ui.modal-body>
                <x-ui.form-field label="Nama" :error="['Nama wajib diisi']">
                    <x-ui.input model="name" placeholder="Nama" />
                </x-ui.form-field>
                <x-ui.select model="role_id" placeholder="Pilih Role" :options="[1 => 'Admin']" />
                <x-ui.textarea model="notes">Catatan</x-ui.textarea>
                <x-ui.checkbox model="status" label="Aktif" />
                <x-ui.radio model="status" value="1" label="Aktif" />
                <x-ui.table-checkbox model="selectedIds" value="1" label="Pilih data" />
                <x-ui.file-upload model="file" id="file" hint="PDF atau DOC" />
            </x-ui.modal-body>
            <x-ui.modal-footer>
                <x-ui.button>Simpan</x-ui.button>
            </x-ui.modal-footer>
            BLADE);

        $this->assertStringContainsString('btn btn-primary', $html);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('data-spm-submit-button="true"', $html);
        $this->assertStringContainsString('badge badge-light-success', $html);
        $this->assertStringContainsString('card', $html);
        $this->assertStringContainsString('Ringkasan', $html);
        $this->assertStringContainsString('data-ui-breadcrumb="metronic"', $html);
        $this->assertStringContainsString('breadcrumb', $html);
        $this->assertStringContainsString('data-ui-sidebar-section="metronic"', $html);
        $this->assertStringContainsString('spm-sidebar-section-compact', $html);
        $this->assertStringContainsString('data-ui-tabs="metronic"', $html);
        $this->assertStringContainsString('nav nav-tabs nav-line-tabs', $html);
        $this->assertStringContainsString('nav-line-tabs', $html);
        $this->assertStringContainsString('data-ui-tab="metronic"', $html);
        $this->assertStringContainsString('data-ui-icon-button="metronic"', $html);
        $this->assertStringContainsString('btn-icon', $html);
        $this->assertStringContainsString('aria-label="Hapus data"', $html);
        $this->assertStringContainsString('data-ui-filter-select="metronic"', $html);
        $this->assertStringContainsString('form-select form-select-solid', $html);
        $this->assertStringContainsString('data-ui-status-badge="metronic"', $html);
        $this->assertStringContainsString('data-ui-section-card="metronic"', $html);
        $this->assertStringContainsString('data-ui-modal-header="metronic"', $html);
        $this->assertStringContainsString('data-ui-modal-body="metronic"', $html);
        $this->assertStringContainsString('data-ui-modal-footer="metronic"', $html);
        $this->assertStringContainsString('data-ui-form-field="metronic"', $html);
        $this->assertStringContainsString('data-ui-input="metronic"', $html);
        $this->assertStringContainsString('data-ui-select="metronic"', $html);
        $this->assertStringContainsString('data-ui-textarea="metronic"', $html);
        $this->assertStringContainsString('data-ui-checkbox="metronic"', $html);
        $this->assertStringContainsString('data-ui-radio="metronic"', $html);
        $this->assertStringContainsString('form-check form-check-custom form-check-solid', $html);
        $this->assertStringContainsString('form-check-input h-22px w-22px', $html);
        $this->assertStringContainsString('data-ui-table-checkbox="metronic"', $html);
        $this->assertStringContainsString('x-model="selectedIds"', $html);
        $this->assertStringContainsString('data-ui-file-upload="metronic"', $html);
    }

    public function test_keenicons_component_renders_supported_names_and_complete_duotone_paths(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.icon name="notification-bing" />
            <x-ui.icon name="lock-2" />
            <x-ui.icon name="eye-slash" />
            <x-ui.icon name="disconnect" />
            <x-ui.icon name="warning-2" />
            <x-ui.icon name="camera" />
            <x-ui.icon name="building" />
            <x-ui.icon name="cloud-upload" />
            <x-ui.icon name="layers" />
            BLADE);

        $this->assertStringContainsString('ki-notification-bing', $html);
        $this->assertMatchesRegularExpression('/ki-notification-bing[^>]*>.*class="path3"/s', $html);
        $this->assertMatchesRegularExpression('/ki-lock-2[^>]*>.*class="path5"/s', $html);
        $this->assertMatchesRegularExpression('/ki-eye-slash[^>]*>.*class="path4"/s', $html);
        $this->assertMatchesRegularExpression('/ki-disconnect[^>]*>.*class="path5"/s', $html);
        $this->assertStringContainsString('ki-information-5', $html);
        $this->assertStringContainsString('ki-file-up', $html);
        $this->assertStringContainsString('ki-category', $html);
        $this->assertStringContainsString('ki-data', $html);
        $this->assertStringNotContainsString('ki-warning-2', $html);
        $this->assertStringNotContainsString('ki-camera', $html);
        $this->assertStringNotContainsString('ki-building', $html);
        $this->assertStringNotContainsString('ki-cloud-upload', $html);
        $this->assertStringNotContainsString('ki-layers', $html);
    }

    public function test_sidebar_link_component_uses_metronic_sidebar_contract(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-sidebar-link href="/admin/akreditasi" :active="true" icon="shield">
                Akreditasi
            </x-sidebar-link>
            <x-sidebar-link href="/admin/master-edpm" :active="false" icon="none">
                Komponen
            </x-sidebar-link>
            <x-sidebar-link href="/documents/kartu_kendali" :active="false" icon="clipboard-check">
                Kartu Kendali
            </x-sidebar-link>
            <x-sidebar-link href="/documents/visitasi" :active="false" icon="document-up">
                Laporan Visitasi
            </x-sidebar-link>
            <x-sidebar-link href="/admin/failed-notifications" :active="false" icon="notification-bing">
                Notifikasi Gagal
            </x-sidebar-link>
            BLADE);

        $this->assertStringContainsString('spm-sidebar-link active', $html);
        $this->assertStringContainsString('spm-sidebar-icon', $html);
        $this->assertStringContainsString('ki-duotone ki-shield-tick', $html);
        $this->assertStringContainsString('ki-duotone ki-check-circle', $html);
        $this->assertStringContainsString('ki-duotone ki-arrow-up', $html);
        $this->assertStringContainsString('ki-duotone ki-notification', $html);
        $this->assertStringContainsString('spm-sidebar-link-child', $html);
        $this->assertStringContainsString('Komponen', $html);

        $progressHtml = Blade::render(<<<'BLADE'
            <x-sidebar-link href="/pesantren/profile" :active="false" icon="hat">
                Profil Pesantren
            </x-sidebar-link>
            BLADE);

        $this->assertStringNotContainsString('spm-sidebar-progress-dot', $progressHtml);
        $this->assertStringNotContainsString('Kesiapan data lengkap', $progressHtml);
    }

    public function test_sidebar_shell_uses_metronic_drawer_markup_without_button_display_conflict(): void
    {
        $source = file_get_contents(resource_path('views/components/layout/app-sidebar.blade.php'));
        $css = $this->metronicOverrideCss();

        $this->assertStringContainsString('data-kt-drawer="true"', $source);
        $this->assertStringContainsString('data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle"', $source);
        $this->assertStringContainsString('hover-scroll-overlay-y my-5', $source);
        $this->assertStringContainsString('spm-sidebar-mobile-dismiss', $source);
        $this->assertStringContainsString('data-kt-drawer-dismiss="true"', $source);
        $this->assertStringContainsString('ki-duotone ki-cross-circle', $source);
        $this->assertStringNotContainsString('$store.sidebar', $source);
        $this->assertStringNotContainsString('spm-drawer-open', $source);
        $this->assertStringNotContainsString('spm-sidebar-backdrop', $source);
        $this->assertStringNotContainsString('class="btn-icon btn-active-color-primary d-lg-none"', $source);
        $this->assertStringNotContainsString('<x-ui.button', substr($source, strpos($source, 'id="kt_app_sidebar_logo"'), 1400));

        $this->assertStringContainsString('Metronic Sidebar Shell Normalization', $css);
        $this->assertStringContainsString('.spm-app-sidebar .app-sidebar-logo', $css);
        $this->assertStringContainsString('[data-ui-button="metronic"].d-lg-none', $css);
        $this->assertStringContainsString('.spm-btn.d-lg-none', $css);
        $this->assertStringContainsString('display: none !important;', $css);
    }

    public function test_header_user_menu_owns_account_actions(): void
    {
        $header = file_get_contents(resource_path('views/components/layout/app-header.blade.php'));
        $sidebar = file_get_contents(resource_path('views/components/layout/app-sidebar.blade.php'));

        $this->assertStringContainsString('@include(\'components.layout.notification-menu\')', $header);
        $this->assertStringNotContainsString('spm-header-user-menu', $header);
        $this->assertStringNotContainsString('aria-label="Menu pengguna"', $header);
        $this->assertStringContainsString('data-kt-menu-trigger="click"', $header);
        $this->assertStringContainsString('data-kt-menu-placement="bottom-end"', $header);
        $this->assertStringContainsString('data-kt-menu="true"', $header);
        $this->assertStringContainsString("route('profile.edit')", $header);
        $this->assertStringContainsString("route('logout')", $header);
        $this->assertStringContainsString('Pengaturan Profil', $header);
        $this->assertStringContainsString('Keluar', $header);

        $this->assertStringNotContainsString('id="kt_app_sidebar_user_menu"', $sidebar);
        $this->assertStringNotContainsString('x-on:mouseenter="open = true"', $sidebar);
    }

    public function test_metronic_overrides_apply_enterprise_typography_and_hide_navigation_progress(): void
    {
        $css = $this->metronicOverrideCss();
        $appCss = file_get_contents(resource_path('css/app.css'));
        $appJs = file_get_contents(resource_path('js/app.js'));
        $appLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $tailwindConfig = file_get_contents(base_path('tailwind.config.js'));

        $this->assertStringContainsString('--bs-font-sans-serif: "Inter"', $css);
        $this->assertStringContainsString("sans: ['Inter'", $tailwindConfig);
        $this->assertStringContainsString('@fontsource/inter/latin-400.css', $appCss);
        $this->assertStringContainsString('@fontsource/inter/latin-500.css', $appCss);
        $this->assertStringContainsString('@fontsource/inter/latin-600.css', $appCss);
        $this->assertStringContainsString('@import "./keenicons-lite.css";', $appCss);
        $this->assertFileExists(resource_path('css/keenicons-lite.css'));
        $keeniconsCss = file_get_contents(resource_path('css/keenicons-lite.css'));
        $this->assertStringContainsString('font-family: "keenicons-duotone"', $keeniconsCss);
        $this->assertStringContainsString('/vendor/metronic/assets/plugins/global/fonts/keenicons/keenicons-duotone.woff', $keeniconsCss);
        $this->assertStringContainsString('.ki-notification .path1:before', $keeniconsCss);
        $this->assertStringContainsString('.ki-burger-menu .path1:before', $keeniconsCss);
        $this->assertStringContainsString('.ki-setting-2 .path1:before', $keeniconsCss);
        $this->assertStringContainsString('.ki-check.ki-outline:before', $keeniconsCss);
        $this->assertStringContainsString('.ki-home.ki-solid:before', $keeniconsCss);
        $this->assertStringNotContainsString('@fontsource/inter/latin-700.css', $appCss);
        $this->assertStringNotContainsString('@fontsource/inter/latin-800.css', $appCss);
        $this->assertStringContainsString('font-size: 15px;', $css);
        $this->assertStringContainsString('.spm-page-title', $css);
        $this->assertStringContainsString('[data-ui-table="metronic"] .table tbody td', $css);
        $this->assertStringContainsString('.spm-table-header', $css);
        $this->assertStringContainsString('.spm-table-controls', $css);
        $this->assertStringContainsString('.spm-table-filter-row', $css);
        $this->assertStringContainsString('.spm-table-actions', $css);
        $this->assertStringContainsString('[data-ui-table-checkbox="metronic"]', $css);
        $this->assertStringContainsString('.spm-card-title', $css);
        $this->assertStringContainsString('Visual Audit Normalization V1', $css);
        $this->assertStringNotContainsString('.spm-sidebar-progress-dot', $css);
        $this->assertStringContainsString('.spm-table-shell--document-category', $css);
        $this->assertStringContainsString('.spm-detail-page', $css);
        $this->assertStringContainsString('.spm-stat-card', $css);
        $this->assertStringContainsString('.spm-workflow-stepper', $css);
        $this->assertStringContainsString('.spm-navigate-progress', $css);
        $this->assertStringContainsString('body.spm-is-navigating .spm-navigate-progress', $css);
        $this->assertStringContainsString('.spm-navigate-progress-bar', $css);
        $this->assertStringContainsString('showPageLoadingOverlay', $appJs);
        $this->assertStringContainsString('hidePageLoadingOverlay', $appJs);
        $this->assertStringContainsString('spm-is-navigating', $appJs);
        $this->assertStringNotContainsString('spm-navigation-loading', $appJs);
        $this->assertStringContainsString('initSubmitLockGuard', $appJs);
        $this->assertStringContainsString('data-spm-submit-lock', $appJs);
        $this->assertStringContainsString('data-spm-submit-button', $appJs);
        $this->assertStringContainsString('stopImmediatePropagation', $appJs);
        $this->assertStringContainsString('releaseAllSubmitLockGuards', $appJs);
        $this->assertStringContainsString('!result.isConfirmed', $appJs);
        $this->assertStringContainsString('.spm-submit-locking', $css);
        $this->assertStringContainsString('data-kt-app-page-loading-enabled="false"', $appLayout);
        $this->assertStringContainsString('class="spm-navigate-progress"', $appLayout);
        $this->assertStringContainsString('class="spm-navigate-progress-bar"', $appLayout);
        $this->assertStringContainsString('Final Typography Guard', $css);
        $this->assertStringContainsString('Production UI Polish V2', $css);
        $this->assertStringContainsString('body .fw-bold', $css);
        $this->assertStringContainsString('font-weight: 600 !important;', $css);
        $this->assertStringContainsString('"keenicons-duotone"', $css);
        $this->assertStringContainsString('"keenicons-outline"', $css);
        $this->assertStringContainsString('"keenicons-solid"', $css);
        $this->assertStringContainsString('Brand Chroming Guard', $css);
        $this->assertStringContainsString('--spm-info: #0e9384;', $css);
        $this->assertStringContainsString('--bs-primary: var(--spm-primary) !important;', $css);
        $this->assertStringContainsString('--bs-primary-rgb: var(--spm-primary-rgb) !important;', $css);
        $this->assertStringContainsString('--bs-info: var(--spm-info) !important;', $css);
        $this->assertStringContainsString('.spm-app-shell .btn.btn-primary', $css);
        $this->assertStringContainsString('.spm-app-shell .spm-sidebar-icon .ki-duotone [class^="path"]::before', $css);
        $this->assertStringNotContainsString('--spm-info: #0088ff;', $css);
        $this->assertStringNotContainsString('#3b82f6', $css);
        $this->assertStringNotContainsString('#0d6efd', $css);
        $this->assertStringNotContainsString('#009ef7', $css);
        // Font-weight 700+ check excludes guest/landing pages (55-landing-v3, 56-auth-v3)
        // which intentionally use 700 for editorial serif headings outside the app shell.
        $appShellCss = collect(glob(resource_path('css/metronic-overrides/*.css')) ?: [])
            ->reject(fn (string $p): bool => str_contains($p, 'landing-v3') || str_contains($p, 'auth-v3'))
            ->map(fn (string $p): string => file_get_contents($p))
            ->implode("\n");
        $this->assertDoesNotMatchRegularExpression('/font-weight:\s*(?:650|700|750|800|900)\b/', $appShellCss);
    }

    public function test_frontend_runtime_uses_single_blade_alpine_boot_path(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $appJs = file_get_contents(resource_path('js/app.js'));
        $views = '';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $views .= "\n".file_get_contents($file->getPathname());
            }
        }

        $failedNotifications = file_get_contents(resource_path('views/admin/failed-notifications/index.blade.php'));

        $this->assertSame(1, substr_count($layout, 'resources/js/app.js'));
        $this->assertSame(1, substr_count($appJs, "document.addEventListener('DOMContentLoaded', initMetronic)"));
        $this->assertStringContainsString('if (window.KTUtil) return;', $appJs);
        $this->assertStringNotContainsString('quillEditor', $appJs);
        $this->assertFileDoesNotExist(resource_path('views/components/quill-editor.blade.php'));
        $this->assertStringNotContainsString("Alpine.store('modal')", $views);
        $this->assertStringNotContainsString('form.action = route(', $failedNotifications);
        $this->assertStringNotContainsString("route('admin.failed-notifications.retry', {", $failedNotifications);
        $this->assertStringNotContainsString('x-html="catatan.catatan', $views);
    }

    public function test_metronic_overrides_are_split_into_architecture_modules(): void
    {
        $entry = file_get_contents(resource_path('css/metronic-overrides.css'));

        foreach ([
            '00-foundation.css',
            '10-layout-header.css',
            '20-table-system.css',
            '30-detail-components.css',
            '40-sidebar.css',
            '45-form-modal.css',
            '50-dashboard.css',
            '55-landing.css',
            '70-button-typography-density.css',
            '80-production-polish.css',
            '90-sidebar-brand-guard.css',
        ] as $module) {
            $this->assertFileExists(resource_path("css/metronic-overrides/{$module}"));
            $this->assertStringContainsString("@import \"./metronic-overrides/{$module}\";", $entry);
        }

        $this->assertLessThanOrEqual(
            40,
            substr_count($entry, "\n"),
            'The stable metronic-overrides.css entry should remain a short import aggregator.'
        );
    }

    public function test_blade_views_do_not_use_bootstrap_bold_utility_classes(): void
    {
        $violations = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $contents = file_get_contents($path);

            if (preg_match('/\bfw-bold(?:er)?\b/', $contents) === 1) {
                $violations[] = str_replace(base_path(DIRECTORY_SEPARATOR), '', $path);
            }
        }

        $this->assertSame(
            [],
            $violations,
            'Use fw-semibold in Blade markup; the CSS guard only exists for third-party or legacy output.'
        );
    }

    public function test_render_markup_does_not_contain_direct_queries(): void
    {
        $views = [
            'resources/views/asesor/akreditasi-detail.blade.php',
        ];

        foreach ($views as $path) {
            $contents = $this->viewSourceWithDescendantPartials($path);
            $renderMarkup = str_contains($contents, '?>')
                ? substr($contents, strpos($contents, '?>') + 2)
                : $contents;

            foreach (['::query(', 'DB::', 'auth()->user()->', '->latest()->first(', '->paginate('] as $forbidden) {
                $this->assertStringNotContainsString($forbidden, $renderMarkup, "{$path} should keep data access in the service layer, not render markup.");
            }
        }
    }

    public function test_akreditasi_detail_uses_tab_partials_for_large_sections(): void
    {
        $detailViews = [
            'asesor' => [
                'path' => 'views/asesor/akreditasi-detail.blade.php',
                'includePrefix' => 'asesor.akreditasi-detail.tabs',
                'tabs' => ['profil', 'ipm', 'sdm', 'edpm', 'instrumen', 'laporan-visitasi'],
                'maxLines' => 500,
            ],
        ];

        foreach ($detailViews as $role => $detailView) {
            $view = file_get_contents(resource_path($detailView['path']));

            foreach ($detailView['tabs'] as $tab) {
                $this->assertStringContainsString(
                    "@include('{$detailView['includePrefix']}.{$tab}')",
                    $view,
                    "{$role} akreditasi detail should include the {$tab} tab partial."
                );
                $this->assertFileExists(resource_path(str_replace('.blade.php', "/tabs/{$tab}.blade.php", $detailView['path'])));
            }

            $this->assertLessThan(
                $detailView['maxLines'],
                substr_count($view, "\n"),
                "The {$role} akreditasi detail shell should stay small enough to scan."
            );
        }
    }

    public function test_instrumen_tabs_use_nested_partials_and_view_helpers(): void
    {
        $this->markTestSkipped('Instrumen tab migrated to single-file Blade view — no nested partials remain.');
    }

    public function test_akreditasi_workflow_stepper_renders_metronic_detail_contract(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-akreditasi.workflow-stepper :status="$status" />
            BLADE, ['status' => Akreditasi::STATUS_ASSESSMENT]);

        $this->assertStringContainsString('data-akreditasi-workflow="metronic"', $html);
        $this->assertStringContainsString('data-ui-stepper="metronic"', $html);
        $this->assertStringContainsString('data-ui-stepper-mode="visual"', $html);
        $this->assertStringContainsString('spm-workflow-stepper', $html);
        $this->assertStringContainsString('Alur Proses Akreditasi', $html);
        $this->assertStringContainsString('Review Asesor', $html);
        $this->assertStringContainsString('Penilaian Pasca Visitasi', $html);
        $this->assertStringContainsString('Nilai Verifikasi mengikuti Nilai Kelompok', $html);
        $this->assertStringContainsString('current spm-workflow-step', $html);

        $this->assertStringContainsString(
            '<x-akreditasi.workflow-stepper',
            file_get_contents(base_path('resources/views/asesor/akreditasi-detail.blade.php')),
            'asesor akreditasi-detail should include the reusable akreditasi workflow stepper.'
        );

        $auditTrail = file_get_contents(resource_path('views/admin/akreditasi/detail/tabs/audit-trail.blade.php'));
        $this->assertStringContainsString('data-ui-audit-stepper="metronic"', $auditTrail);
        $this->assertStringNotContainsString('data-kt-stepper-element', $auditTrail);
    }

    public function test_edpm_review_component_groups_edpm_and_ipr_with_metronic_tables(): void
    {
        $komponens = collect([
            (object) [
                'id' => 1,
                'nama' => 'Mutu Lulusan',
                'ipr' => null,
                'butirs' => collect([
                    (object) ['id' => 11, 'no_sk' => '1', 'nomor_butir' => '1', 'butir_pernyataan' => 'Santri memiliki capaian kompetensi utama.'],
                ]),
            ],
            (object) [
                'id' => 2,
                'nama' => 'Indikator Pemenuhan Relatif',
                'ipr' => 1,
                'butirs' => collect([
                    (object) ['id' => 21, 'no_sk' => '', 'nomor_butir' => '1', 'butir_pernyataan' => 'Pesantren memiliki ruang belajar yang mencukupi.'],
                ]),
            ],
        ]);

        $html = Blade::render(<<<'BLADE'
            <x-akreditasi.edpm-review
                :komponens="$komponens"
                :evaluasis="$evaluasis"
                :links="$links"
                :catatans="$catatans"
            />
            BLADE, [
            'komponens' => $komponens,
            'evaluasis' => [11 => 4, 21 => 3],
            'links' => [11 => 'https://example.test/bukti'],
            'catatans' => [1 => 'Catatan mutu lulusan'],
        ]);

        $this->assertStringContainsString('data-akreditasi-edpm-review="metronic"', $html);
        $this->assertStringContainsString('data-ui-edpm-component="metronic"', $html);
        $this->assertStringContainsString('data-ui-simple-table="metronic"', $html);
        $this->assertStringContainsString('Komponen EDPM', $html);
        $this->assertStringContainsString('Komponen IPR', $html);
        $this->assertStringContainsString('Mutu Lulusan', $html);
        $this->assertStringContainsString('Indikator Pemenuhan Relatif', $html);
        $this->assertStringContainsString('Catatan mutu lulusan', $html);
        $this->assertStringContainsString('Bukti', $html);
    }

    public function test_metronic_table_components_render_reusable_classes(): void
    {
        $records = new LengthAwarePaginator([], 0, 10);

        $html = Blade::render(<<<'BLADE'
            <x-ui.table title="Akreditasi" :records="$records">
                <x-slot name="filters">
                    <x-ui.table-search placeholder="Cari Pesantren..." />
                </x-slot>

                <x-slot name="toolbar">
                    <x-ui.button icon="document">Ekspor Data</x-ui.button>
                </x-slot>

                <x-slot name="thead">
                    <x-ui.table-th field="created_at" sortField="created_at" :sortAsc="false">
                        Tahap Akreditasi
                    </x-ui.table-th>
                </x-slot>

                <x-slot name="tbody">
                    <tr><td>Pengajuan</td></tr>
                </x-slot>
            </x-ui.table>
            BLADE, ['records' => $records]);

        $this->assertStringContainsString('data-ui-table="metronic"', $html);
        $this->assertStringContainsString('spm-table-shell--standard', $html);
        $this->assertStringContainsString('spm-table-header', $html);
        $this->assertStringContainsString('spm-table-heading', $html);
        $this->assertStringContainsString('spm-table-controls', $html);
        $this->assertStringContainsString('spm-table-dom-row', $html);
        $this->assertStringContainsString('spm-table-dom-start', $html);
        $this->assertStringContainsString('spm-table-dom-end', $html);
        $this->assertStringContainsString('spm-table-filter-row', $html);
        $this->assertStringContainsString('spm-table-actions', $html);
        $this->assertStringContainsString('spm-table-body-wrap', $html);
        $this->assertStringContainsString('spm-table-scroll', $html);
        $this->assertStringNotContainsString('spm-table-utility-row', $html);
        $this->assertStringContainsString('spm-table-footer', $html);
        $this->assertStringContainsString('Menampilkan 0-0 dari 0 entri', $html);
        $this->assertStringContainsString('table table-striped table-row-bordered align-middle gy-5 gs-7', $html);
        $this->assertStringContainsString('spm-table--metronic-docs', $html);
        $this->assertStringContainsString('spm-datatable', $html);
        $this->assertStringContainsString('spm-table-search-input', $html);
        $this->assertStringContainsString('entri', $html);
        $this->assertStringContainsString('Ekspor Data', $html);
        $this->assertStringContainsString('name="search"', $html);
        $this->assertStringContainsString('url.searchParams.set(this.name, this.value)', $html);
        $this->assertStringNotContainsString('<form method="GET" class="spm-table-controls">', $html);
    }

    public function test_legacy_datatable_components_render_through_metronic_table_adapter(): void
    {
        $records = new LengthAwarePaginator([], 0, 10);

        $html = Blade::render(<<<'BLADE'
            <x-datatable.layout title="Pesantren" :records="$records">
                <x-slot name="filters">
                    <x-datatable.search placeholder="Cari Pesantren..." />
                </x-slot>

                <x-slot name="thead">
                    <x-datatable.th field="name" sortField="name" :sortAsc="true">
                        Nama Pesantren
                    </x-datatable.th>
                </x-slot>

                <x-slot name="tbody">
                    <tr><td>Pondok Mutu</td></tr>
                </x-slot>
            </x-datatable.layout>
            BLADE, ['records' => $records]);

        $this->assertStringContainsString('data-ui-table="metronic"', $html);
        $this->assertStringContainsString('data-ui-table-adapter="datatable"', $html);
        $this->assertStringContainsString('table table-striped table-row-bordered align-middle gy-5 gs-7', $html);
        $this->assertStringContainsString('spm-table-footer--datatable', $html);
        $this->assertStringContainsString('spm-table-per-page--compact', $html);
        $this->assertStringContainsString('data-ui-table-search="metronic"', $html);
        $this->assertStringContainsString('data-ui-table-per-page="metronic"', $html);
        $this->assertStringContainsString('spm-table-dom-row', $html);
        $this->assertStringNotContainsString('spm-table-dom-end', $html);
        $this->assertStringContainsString('sortField="name"', $html);
    }

    public function test_datatable_can_render_metronic_footer_length_menu(): void
    {
        $records = new LengthAwarePaginator([], 0, 10);

        $html = Blade::render(<<<'BLADE'
            <x-datatable.layout
                title="Pesantren"
                :records="$records"
            >
                <x-slot name="filters">
                    <x-datatable.search placeholder="Cari Pesantren..." />
                </x-slot>

                <x-slot name="thead">
                    <x-datatable.th field="name" sortField="name" :sortAsc="true">
                        Nama Pesantren
                    </x-datatable.th>
                </x-slot>

                <x-slot name="tbody">
                    <tr><td>Pondok Mutu</td></tr>
                </x-slot>
            </x-datatable.layout>
            BLADE, ['records' => $records]);

        $this->assertStringContainsString('table table-striped table-row-bordered align-middle gy-5 gs-7', $html);
        $this->assertStringContainsString('spm-table-footer--datatable', $html);
        $this->assertStringContainsString('spm-table-footer-start', $html);
        $this->assertStringContainsString('spm-table-footer-end', $html);
        $this->assertStringContainsString('data-ui-table-per-page="metronic"', $html);
        $this->assertStringContainsString('spm-table-per-page--compact', $html);
        $this->assertStringContainsString('aria-label="Jumlah entri per halaman"', $html);
        $this->assertStringContainsString('Menampilkan 0-0 dari 0 entri', $html);
        $this->assertGreaterThan(
            strpos($html, 'spm-table-footer--datatable'),
            strpos($html, 'data-ui-table-per-page="metronic"')
        );
        $this->assertStringNotContainsString('<span class="spm-table-per-page-label">Tampilkan</span>', $html);
    }

    public function test_simple_table_component_supports_non_paginated_tables(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.simple-table table-class="spm-edpm-table">
                <thead>
                    <tr>
                        <x-ui.table-th :min-width="false" class="spm-edpm-col-sk">No SK</x-ui.table-th>
                        <th>Butir</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>001</td><td>Pernyataan mutu</td></tr>
                </tbody>
            </x-ui.simple-table>
            BLADE);

        $this->assertStringContainsString('data-ui-simple-table="metronic"', $html);
        $this->assertStringContainsString('table table-row-dashed', $html);
        $this->assertStringContainsString('spm-simple-table', $html);
        $this->assertStringContainsString('spm-edpm-table', $html);
        $this->assertStringContainsString('spm-edpm-col-sk', $html);
        $this->assertStringNotContainsString('min-w-125px', $html);
    }

    public function test_legacy_datatable_page_renders_metronic_table_adapter(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create(['role_id' => 4]);

        $this->actingAs($admin)
            ->get('/roles')
            ->assertOk()
            ->assertSee('data-module-page="roles"', false)
            ->assertSee('Manajemen Role')
            ->assertSee('role-modal', false);
    }

    public function test_admin_master_edpm_uses_simple_table_component(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@spm.test')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/master-edpm')
            ->assertOk()
            ->assertSee('data-ui-tabs="metronic"', false)
            ->assertSee('data-ui-icon-button="metronic"', false)
            ->assertSee('data-ui-section-card="metronic"', false)
            ->assertSee('data-ui-simple-table="metronic"', false)
            ->assertSee('spm-simple-table', false)
            ->assertSee('spm-edpm-table-wrap', false)
            ->assertSee('spm-edpm-col-action', false)
            ->assertSee('spm-edpm-statement', false)
            ->assertSee('data-ui-modal-header="metronic"', false)
            ->assertSee('data-ui-form-field="metronic"', false);
    }

    public function test_form_modal_views_use_reusable_metronic_form_controls(): void
    {
        $componentExpectations = [
            'resources/views/components/modal.blade.php' => [
                'spm-modal-overlay',
                'spm-modal-panel',
                'spm-modal-lg',
            ],
            'resources/views/components/ui/modal-header.blade.php' => [
                'spm-modal-header',
                'spm-modal-close',
            ],
            'resources/views/components/ui/modal-body.blade.php' => [
                'spm-modal-body',
            ],
            'resources/views/components/ui/modal-footer.blade.php' => [
                'spm-modal-footer',
            ],
            'resources/views/components/ui/form-field.blade.php' => [
                'spm-form-field',
            ],
        ];

        foreach ($componentExpectations as $path => $expectedClasses) {
            $component = file_get_contents(base_path($path));

            foreach ($expectedClasses as $expectedClass) {
                $this->assertStringContainsString($expectedClass, $component, "{$path} should expose {$expectedClass} for consistent Metronic modal spacing.");
            }
        }

        // Legacy profile views removed; testing profile.edit.blade.php instead
        $views = [
            'resources/views/profile/edit.blade.php',
        ];

        foreach ($views as $path) {
            $view = file_get_contents(base_path($path));

            $this->assertStringContainsString('<x-app-layout', $view, "{$path} should use the app layout.");
            $this->assertStringContainsString('<x-ui.form-field', $view, "{$path} should use reusable form fields.");
            $this->assertStringContainsString('data-kt-image-input="true"', $view, "{$path} should expose the Metronic image input root contract.");
            $this->assertStringContainsString('data-kt-image-input-action="change"', $view, "{$path} should keep the Metronic image input change action.");
            $this->assertStringContainsString('data-kt-image-input-action="cancel"', $view, "{$path} should keep the Metronic image input cancel action.");
            $this->assertStringNotContainsString('<x-input-label', $view, "{$path} should not use legacy Breeze input labels.");
            $this->assertStringNotContainsString('<x-text-input', $view, "{$path} should not use legacy Breeze text inputs.");
        }

        $asesorProfile = file_get_contents(resource_path('views/asesor/profile.blade.php'));
        $this->assertStringContainsString('data-kt-image-input="true"', $asesorProfile);
        $this->assertStringContainsString('data-kt-image-input-action="change"', $asesorProfile);
        $this->assertStringContainsString('data-kt-image-input-action="cancel"', $asesorProfile);
    }

    public function test_metronic_accessibility_contract_for_tabs_menu_and_modal(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.tabs>
                <x-ui.tab :active="true">Overview</x-ui.tab>
                <x-ui.tab>Detail</x-ui.tab>
            </x-ui.tabs>

            <x-ui.action-menu label="Aksi Data" menu-id="aksi-data-menu">
                <x-ui.action-menu-item href="/items/1/edit">Edit</x-ui.action-menu-item>
                <x-ui.action-menu-item type="button">Hapus</x-ui.action-menu-item>
            </x-ui.action-menu>

            <x-ui.modal name="sample-modal" title="Sample Modal" :show="true" maxWidth="lg" focusable>
                <x-ui.modal-body>Isi modal</x-ui.modal-body>
            </x-ui.modal>
            BLADE);

        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('data-ui-tab="metronic"', $html);
        $this->assertStringContainsString('role="tab"', $html);
        $this->assertStringContainsString('aria-selected="true"', $html);
        $this->assertStringContainsString('aria-selected="false"', $html);

        $this->assertStringContainsString('data-ui-action-menu="metronic"', $html);
        $this->assertStringContainsString('x-data="spmActionMenu', $html);
        $this->assertStringNotContainsString('data-kt-menu="true"', $html);
        $this->assertStringContainsString('aria-controls="aksi-data-menu"', $html);
        $this->assertStringContainsString('id="aksi-data-menu"', $html);
        $this->assertStringContainsString('role="menu"', $html);
        $this->assertStringContainsString('role="menuitem"', $html);

        $this->assertStringContainsString('data-ui-modal="metronic"', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('aria-labelledby="spm-modal-sample-modal-title"', $html);
        $this->assertStringContainsString('id="spm-modal-sample-modal-title"', $html);
    }

    public function test_sweetalert_actions_use_metronic_helper_without_inline_blade_alerts(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('window.SpmSwal', $script);
        $this->assertStringContainsString('buttonsStyling: false', $script);
        $this->assertStringContainsString('btn btn-primary', $script);
        $this->assertStringContainsString('btn btn-danger', $script);
        $this->assertStringNotContainsString('confirmButtonColor', $script);

        $paths = collect(glob(resource_path('views/**/*.blade.php')) ?: [])
            ->merge(glob(resource_path('views/*.blade.php')) ?: [])
            ->unique();

        foreach ($paths as $path) {
            $view = file_get_contents($path);

            $this->assertStringNotContainsString('Swal.fire', $view, "{$path} should use the global Metronic SweetAlert helper or browser events.");
            $this->assertStringNotContainsString('confirmButtonColor', $view, "{$path} should not style SweetAlert buttons inline.");
        }
    }

    public function test_dashboard_uses_reusable_metronic_components_for_each_role(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $pesantren = User::factory()->create(['role_id' => 3]);
        $asesorUser = User::factory()->create(['role_id' => 2]);

        Asesor::query()->create([
            'user_id' => $asesorUser->id,
            'nama_dengan_gelar' => 'Dr. Asesor Uji, M.Pd.',
            'nama_tanpa_gelar' => 'Asesor Uji',
            'nbm_nia' => 'NBM-UJI-001',
            'nomor_induk_asesor_pm' => 'APM-UJI-001',
            'whatsapp' => '081200000001',
            'nik' => '3400000000000002',
            'tempat_lahir' => 'Yogyakarta',
            'tanggal_lahir' => '1985-01-01',
            'unit_kerja' => 'Majelis Dikdasmen',
            'jabatan_utama' => 'Asesor',
            'jenis_kelamin' => 'L',
            'alamat_kantor' => 'Jl. Kantor',
            'alamat_rumah' => 'Jl. Rumah',
            'provinsi' => 'DI Yogyakarta',
            'kota_kabupaten' => 'Yogyakarta',
            'status_perkawinan' => 'Menikah',
            'profesi' => 'Dosen',
            'pendidikan_terakhir' => 'S3',
            'telp_kantor' => '0274000000',
            'tahun_terbit_sertifikat' => '2024',
            'email_pribadi' => 'asesor.uji@spm.test',
            'layanan_satuan_pendidikan' => ['spm'],
            'riwayat_pendidikan' => [],
            'pengalaman_pelatihan' => [],
            'pengalaman_bekerja' => [],
            'pengalaman_berorganisasi' => [],
            'karya_publikasi' => [],
        ]);

        Akreditasi::query()->create(['user_id' => $pesantren->id, 'status' => 1]);
        Akreditasi::query()->create(['user_id' => $pesantren->id, 'status' => 3]);

        $users = [
            'admin' => User::factory()->create(['role_id' => 1]),
            'pesantren' => $pesantren,
            'asesor' => $asesorUser,
        ];

        foreach ($users as $role => $user) {
            $response = $this->actingAs($user)->get('/dashboard');

            $response
                ->assertOk()
                ->assertDontSee('fonts.bunny.net', false)
                ->assertSee('data-ui-sidebar="metronic"', false)
                ->assertSee('data-ui-sidebar-section="metronic"', false)
                ->assertSee('id="kt_app_header"', false)
                ->assertDontSee('id="kt_app_toolbar"', false)
                ->assertDontSee('open-onboarding-guide', false)
                ->assertDontSee('onboarding-modal-title', false)
                ->assertSee('data-ui-breadcrumb="metronic"', false)
                ->assertSee('data-dashboard-page="metronic"', false)
                ->assertSee('data-ui-page="metronic"', false)
                ->assertSee('data-ui-badge="metronic"', false)
                ->assertSee('data-ui-card="metronic"', false)
                ->assertSee('spm-dashboard-stat', false);

            $response->assertSee(ucfirst($role), false);

            if ($role === 'asesor') {
                $response->assertSee('data-ui-empty-state="metronic"', false);
            } elseif ($role === 'admin') {
                $response->assertSee('id="monthlyChart"', false);
            }
        }
    }

    public function test_header_uses_route_aware_title_and_breadcrumb_for_module_pages(): void
    {
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@spm.test')->firstOrFail();

        $response = $this->actingAs($admin)->get('/admin/master-edpm');

        $response
            ->assertOk()
            ->assertSee('data-ui-breadcrumb="metronic"', false)
            ->assertSee('spm-breadcrumb', false)
            ->assertSee('Master Data', false);

        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/spm-header-title[^>]*>\s*Komponen EDPM\/IPR\s*</s', $html);
        $this->assertDoesNotMatchRegularExpression('/spm-header-title[^>]*>\s*Dashboard\s*</s', $html);
    }

    public function test_admin_akreditasi_page_uses_metronic_datatable_foundation(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create(['role_id' => 1]);
        $pesantrenUser = User::factory()->create([
            'name' => 'User Pondok Mutu',
            'role_id' => 3,
        ]);

        Pesantren::query()->create([
            'user_id' => $pesantrenUser->id,
            'nama_pesantren' => 'Pondok Mutu Muhammadiyah',
        ]);

        Akreditasi::query()->create([
            'user_id' => $pesantrenUser->id,
            'status' => 6,
        ]);

        $this->actingAs($admin)
            ->get('/admin/akreditasi')
            ->assertOk()
            ->assertSee('data-admin-akreditasi-page="metronic"', false)
            ->assertSee('data-akreditasi-workflow="metronic"', false)
            ->assertSee('Tahapan Akreditasi LP2M', false)
            ->assertSee('current spm-workflow-step', false)
            ->assertSee('data-ui-table="metronic"', false)
            ->assertSee('spm-table-shell', false)
            ->assertSee('spm-table-footer', false)
            ->assertSee('form-control-solid', false)
            ->assertSee('data-ui-action-menu="metronic"', false)
            ->assertSee('Pengajuan (1)', false)
            ->assertSee('Review Asesor (0)', false)
            ->assertSee('Visitasi & Penilaian Pasca Visitasi (0)', false)
            ->assertSeeText('Review Berkas & Asesor')
            ->assertSeeText('Visitasi & Penilaian')
            ->assertDontSee('Assessment (', false)
            ->assertDontSee('Visitasi & Pasca Visitasi', false)
            ->assertSee('Pondok Mutu Muhammadiyah');
    }

    public function test_admin_module_list_pages_use_page_heading_and_reusable_tables(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@spm.test')->firstOrFail();
        $superAdmin = User::query()->where('email', 'superadmin@spm.test')->firstOrFail();

        foreach ([
            '/admin/akreditasi' => 'data-admin-akreditasi-page="metronic"',
            '/admin/pesantren' => 'data-module-page="admin-pesantren"',
            '/admin/asesor' => 'data-module-page="admin-asesor"',
            '/admin/master-document' => 'data-module-page="master-dokumen"',
            '/accounts' => 'data-module-page="accounts"',
        ] as $uri => $marker) {
            $this->actingAs($admin)
                ->get($uri)
                ->assertOk()
                ->assertSee($marker, false)
                ->assertSee('spm-page-title', false)
                ->assertSee('data-ui-table="metronic"', false);
        }

        $this->actingAs($superAdmin)
            ->get('/roles')
            ->assertOk()
            ->assertSee('data-module-page="roles"', false)
            ->assertSee('spm-page-title', false)
            ->assertSee('data-ui-table="metronic"', false);

        $this->actingAs($admin)
            ->get('/admin/master-edpm')
            ->assertOk()
            ->assertSee('data-module-page="master-edpm"', false)
            ->assertSee('spm-page-title', false)
            ->assertSee('data-ui-simple-table="metronic"', false);
    }

    public function test_user_module_list_pages_use_page_heading_and_reusable_tables(): void
    {
        $this->seed(DatabaseSeeder::class);

        Document::query()->create([
            'title' => 'Panduan IAPM',
            'type' => 'iapm',
            'file_path' => 'documents/panduan-iapm.pdf',
            'status' => 1,
            'is_pesantren' => true,
            'is_asesor' => true,
        ]);

        $pesantren = User::query()->where('email', 'pesantren@spm.test')->firstOrFail();
        $asesor = User::query()->where('email', 'asesor@spm.test')->firstOrFail();

        foreach ([
            [$pesantren, '/pesantren/akreditasi', 'data-module-page="pesantren-akreditasi"'],
            [$asesor, '/asesor/akreditasi', 'data-module-page="asesor-akreditasi"'],
        ] as [$user, $uri, $marker]) {
            $this->actingAs($user)
                ->get($uri)
                ->assertOk()
                ->assertSee($marker, false)
                ->assertSee('spm-page-title', false)
                ->assertSee('data-ui-table="metronic"', false);
        }

        $this->actingAs($pesantren)
            ->get('/documents/iapm')
            ->assertOk()
            ->assertSee('data-module-page="dokumen"', false)
            ->assertSee('spm-page-title', false)
            ->assertSee('spm-iapm-viewer-card', false)
            ->assertSee('Panduan IAPM dibagikan admin sebagai bahan baca/acuan', false)
            ->assertDontSee('data-ui-table="metronic"', false)
            ->assertDontSee('Kartu Kendali Visitasi', false);
    }

    public function test_detail_pages_render_metronic_detail_foundation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@spm.test')->firstOrFail();
        $pesantren = User::query()->where('email', 'pesantren@spm.test')->firstOrFail();
        $asesor = User::query()->where('email', 'asesor@spm.test')->firstOrFail();
        $akreditasi = Akreditasi::query()->where('user_id', $pesantren->id)->firstOrFail();

        $pages = [
            [$admin, "/admin/pesantren/{$pesantren->uuid}", [
                'data-ui-page="metronic"',
                'data-ui-section-card="metronic"',
                'data-ui-simple-table="metronic"',
                'data-ui-document-item="metronic"',
                'data-ui-detail-item="metronic"',
            ]],
            [$admin, "/admin/akreditasi/{$akreditasi->uuid}", [
                'data-ui-page="metronic"',
                'data-akreditasi-workflow="metronic"',
                'Tahapan Akreditasi LP2M',
                'Review Asesor',
                'Penilaian Pasca Visitasi',
                'data-ui-tabs="metronic"',
                'data-ui-section-card="metronic"',
                'data-ui-simple-table="metronic"',
                'data-ui-document-item="metronic"',
            ]],
            [$pesantren, "/pesantren/akreditasi/{$akreditasi->uuid}", [
                'data-ui-page="metronic"',
                'data-akreditasi-workflow="metronic"',
                'Tahapan Akreditasi LP2M',
                'Review Asesor',
                'Penilaian Pasca Visitasi',
                'data-ui-tabs="metronic"',
                'data-ui-section-card="metronic"',
                'data-ui-simple-table="metronic"',
                'data-ui-document-item="metronic"',
            ]],
            [$asesor, "/asesor/akreditasi/{$akreditasi->uuid}", [
                'data-ui-page="metronic"',
                'data-akreditasi-workflow="metronic"',
                'Tahapan Akreditasi LP2M',
                'Review Asesor',
                'Penilaian Pasca Visitasi',
                'data-ui-tabs="metronic"',
                'data-ui-section-card="metronic"',
                'data-ui-simple-table="metronic"',
                'data-ui-document-item="metronic"',
            ]],
        ];

        foreach ($pages as [$user, $uri, $markers]) {
            $response = $this->actingAs($user)->get($uri)->assertOk();

            foreach ($markers as $marker) {
                $response->assertSee($marker, false);
            }
        }
    }

    public function test_detail_edpm_tabs_render_grouped_edpm_ipr_review_component_for_each_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@spm.test')->firstOrFail();
        $pesantren = User::query()->where('email', 'pesantren@spm.test')->firstOrFail();
        $asesor = User::query()->where('email', 'asesor@spm.test')->firstOrFail();
        $akreditasi = Akreditasi::query()->where('user_id', $pesantren->id)->firstOrFail();

        foreach ([
            [$admin, "/admin/akreditasi/{$akreditasi->uuid}?activeTab=edpm_pesantren"],
            [$pesantren, "/pesantren/akreditasi/{$akreditasi->uuid}?activeTab=edpm"],
            [$asesor, "/asesor/akreditasi/{$akreditasi->uuid}?activeTab=edpm_pesantren"],
        ] as [$user, $uri]) {
            $this->actingAs($user)
                ->get($uri)
                ->assertOk()
                ->assertSee('data-akreditasi-edpm-review="metronic"', false)
                ->assertSee('data-ui-edpm-component="metronic"', false)
                ->assertSee('Komponen EDPM', false)
                ->assertSee('Komponen IPR', false)
                ->assertSee('Mutu Lulusan', false)
                ->assertSee('Indikator Pemenuhan Relatif', false)
                ->assertSee('spm-detail-tabs-shell', false)
                ->assertSee('spm-detail-tab-content', false)
                ->assertSee('nav nav-tabs nav-line-tabs', false)
                ->assertSee('spm-tabs-nav', false)
                ->assertSee('spm-tab-link', false);
        }
    }

    public function test_pesantren_input_pages_render_reusable_metronic_form_foundation(): void
    {
        $this->seed(DatabaseSeeder::class);
        $pesantren = User::query()->where('email', 'pesantren@spm.test')->firstOrFail();

        $pages = [
            '/pesantren/ipm' => [
                'data-module-page="pesantren-ipm"',
                'data-ui-page="metronic"',
                'data-ui-section-card="metronic"',
                'data-ui-form-field="metronic"',
                'data-ui-file-upload="metronic"',
                'data-ui-document-item="metronic"',
            ],
            '/pesantren/sdm' => [
                'data-module-page="pesantren-sdm"',
                'data-ui-page="metronic"',
                'data-ui-section-card="metronic"',
                'data-ui-simple-table="metronic"',
                'data-ui-input="metronic"',
            ],
            '/pesantren/edpm' => [
                'data-module-page="pesantren-edpm"',
                'data-ui-page="metronic"',
                'data-ui-section-card="metronic"',
                'data-ui-simple-table="metronic"',
                'data-ui-select="metronic"',
                'data-ui-input="metronic"',
                'Status EDPM',
                'Komponen EDPM',
                'Komponen IPR',
                'MUTU LULUSAN',
                'B. INDIKATOR PEMENUHAN RELATIF',
                'spm-edpm-workspace',
                'spm-edpm-step-btn',
                'spm-edpm-nav',
            ],
        ];

        foreach ($pages as $uri => $markers) {
            $response = $this->actingAs($pesantren)->get($uri)->assertOk();

            foreach ($markers as $marker) {
                $response->assertSee($marker, false);
            }
        }
    }

    public function test_detail_and_input_views_use_reusable_metronic_components_without_legacy_markup(): void
    {
        $views = [
            'resources/views/asesor/akreditasi-detail.blade.php' => [
                '<x-ui.page',
                '<x-ui.section-card',
                '<x-ui.stat-card',
                '<x-ui.status-badge',
                '<x-ui.alert',
                '<x-ui.button',
            ],
        ];

        $legacyMarkers = [
            '<svg',
            'rounded-[',
            'rounded-2xl',
            'rounded-3xl',
            'bg-gradient',
            'border-l-4',
            '<button type="button"',
            '<button type="submit"',
            '<input type="file"',
            '<x-input-label',
            '<x-text-input',
            'x-input-error',
        ];

        foreach ($views as $path => $expectedComponents) {
            $view = $this->viewSourceWithDescendantPartials($path);

            foreach ($expectedComponents as $component) {
                $this->assertStringContainsString($component, $view, "{$path} should use {$component}");
            }

            foreach ($legacyMarkers as $marker) {
                $this->assertStringNotContainsString($marker, $view, "{$path} should not contain legacy UI marker {$marker}");
            }
        }
    }
}
