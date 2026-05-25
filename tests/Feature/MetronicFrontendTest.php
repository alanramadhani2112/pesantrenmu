<?php

namespace Tests\Feature;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Document;
use App\Models\Pesantren;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class MetronicFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_page_uses_lp2m_positioning_without_legacy_dikdasmen_copy(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('Dikembangkan oleh LabMu untuk LP2M')
            ->assertSee('Sistem Akreditasi Pesantren Muhammadiyah')
            ->assertSee('spm-landing-container', false)
            ->assertDontSee('Dikdasmen', false)
            ->assertDontSee('didaksmen', false)
            ->assertDontSee('didaksemen', false);
    }

    public function test_login_page_loads_metronic_foundation_assets(): void
    {
        $this->withoutVite();

        $this->get('/login')
            ->assertOk()
            ->assertSee('vendor/metronic/assets/plugins/global/plugins.bundle.css', false)
            ->assertSee('vendor/metronic/assets/css/style.bundle.css', false)
            ->assertDontSee('vendor/metronic/assets/plugins/global/plugins.bundle.js', false)
            ->assertDontSee('vendor/metronic/assets/js/scripts.bundle.js', false)
            ->assertDontSee('fonts.bunny.net', false);
    }

    public function test_unused_full_metronic_public_assets_are_removed(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('assets'));
        $this->assertFileExists(public_path('vendor/metronic/assets/css/style.bundle.css'));
        $this->assertFileExists(public_path('vendor/metronic/assets/plugins/global/plugins.bundle.css'));
    }

    public function test_metronic_ui_components_render_reusable_classes(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.button variant="primary">Masuk</x-ui.button>
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
        $this->assertStringContainsString('badge badge-light-success', $html);
        $this->assertStringContainsString('card', $html);
        $this->assertStringContainsString('Ringkasan', $html);
        $this->assertStringContainsString('data-ui-breadcrumb="metronic"', $html);
        $this->assertStringContainsString('breadcrumb', $html);
        $this->assertStringContainsString('data-ui-sidebar-section="metronic"', $html);
        $this->assertStringContainsString('spm-sidebar-section-compact', $html);
        $this->assertStringContainsString('data-ui-tabs="metronic"', $html);
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
        $this->assertStringContainsString('wire:model.live="selectedIds"', $html);
        $this->assertStringContainsString('data-ui-file-upload="metronic"', $html);
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
            <x-sidebar-link href="/pesantren/profile" :active="false" icon="hat" progressStatus="complete">
                Profil Pesantren
            </x-sidebar-link>
            BLADE);

        $this->assertStringContainsString('spm-sidebar-progress-dot', $progressHtml);
        $this->assertStringContainsString('Kesiapan data lengkap', $progressHtml);
        $this->assertStringNotContainsString('>Lengkap<', $progressHtml);
    }

    public function test_metronic_overrides_apply_enterprise_typography_and_hide_navigation_progress(): void
    {
        $css = file_get_contents(resource_path('css/metronic-overrides.css'));

        $this->assertStringContainsString('--bs-font-sans-serif: "Inter"', $css);
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
        $this->assertStringContainsString('.spm-sidebar-progress-dot', $css);
        $this->assertStringContainsString('.spm-table-shell--document-category', $css);
        $this->assertStringContainsString('.spm-detail-page', $css);
        $this->assertStringContainsString('.spm-stat-card', $css);
        $this->assertStringContainsString('.spm-workflow-stepper', $css);
        $this->assertStringContainsString('#nprogress', $css);
        $this->assertStringContainsString('display: none !important;', $css);
    }

    public function test_akreditasi_workflow_stepper_renders_metronic_detail_contract(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-akreditasi.workflow-stepper :status="$status" />
            BLADE, ['status' => Akreditasi::STATUS_ASSESSMENT]);

        $this->assertStringContainsString('data-akreditasi-workflow="metronic"', $html);
        $this->assertStringContainsString('data-ui-stepper="metronic"', $html);
        $this->assertStringContainsString('spm-workflow-stepper', $html);
        $this->assertStringContainsString('Alur Proses Akreditasi', $html);
        $this->assertStringContainsString('Review Asesor', $html);
        $this->assertStringContainsString('Penilaian Pasca Visitasi', $html);
        $this->assertStringContainsString('Nilai Verifikasi mengikuti Nilai Kelompok', $html);
        $this->assertStringContainsString('current spm-workflow-step', $html);

        foreach ([
            'resources/views/livewire/pages/admin/akreditasi-detail.blade.php',
            'resources/views/livewire/pages/asesor/akreditasi-detail.blade.php',
            'resources/views/livewire/pages/pesantren/akreditasi-detail.blade.php',
        ] as $path) {
            $this->assertStringContainsString(
                '<x-akreditasi.workflow-stepper',
                file_get_contents(base_path($path)),
                "{$path} should include the reusable akreditasi workflow stepper."
            );
        }
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
        $this->assertStringContainsString('spm-table-header', $html);
        $this->assertStringContainsString('spm-table-heading', $html);
        $this->assertStringContainsString('spm-table-controls', $html);
        $this->assertStringContainsString('spm-table-filter-row', $html);
        $this->assertStringContainsString('spm-table-actions', $html);
        $this->assertStringContainsString('spm-table-utility-row', $html);
        $this->assertStringContainsString('spm-table-footer', $html);
        $this->assertStringContainsString('table table-row-dashed', $html);
        $this->assertStringContainsString('spm-datatable', $html);
        $this->assertStringContainsString('form-control form-control-solid', $html);
        $this->assertStringContainsString('Ekspor Data', $html);
        $this->assertStringContainsString('wire:model.live.debounce.300ms="search"', $html);
        $this->assertStringContainsString("wire:click=\"sortBy('created_at')\"", $html);
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
        $this->assertStringContainsString('table table-row-dashed', $html);
        $this->assertStringContainsString('data-ui-table-search="metronic"', $html);
        $this->assertStringContainsString('data-ui-table-per-page="metronic"', $html);
        $this->assertStringContainsString("wire:click=\"sortBy('name')\"", $html);
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
            ->assertSee('data-ui-table="metronic"', false)
            ->assertSee('data-ui-table-adapter="datatable"', false)
            ->assertSee('data-ui-table-search="metronic"', false);
    }

    public function test_admin_master_edpm_uses_simple_table_component(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
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

    public function test_admin_master_edpm_view_uses_reusable_metronic_controls(): void
    {
        $view = file_get_contents(resource_path('views/livewire/pages/admin/master-edpm.blade.php'));

        $this->assertStringContainsString('<x-ui.tabs', $view);
        $this->assertStringContainsString('<x-ui.tab', $view);
        $this->assertStringContainsString('<x-ui.icon-button', $view);
        $this->assertStringContainsString('<x-ui.button', $view);
        $this->assertStringContainsString('<x-ui.simple-table', $view);
        $this->assertStringNotContainsString('<svg', $view);
        $this->assertStringNotContainsString('rounded-t-lg', $view);
        $this->assertStringNotContainsString('inline-flex items-center gap-2 px-3', $view);
    }

    public function test_module_list_views_use_reusable_metronic_list_controls(): void
    {
        $views = [
            'resources/views/livewire/pages/admin/pesantren/index.blade.php' => [
                '<x-ui.filter-select',
                '<x-ui.status-badge',
                '<x-ui.action-menu',
                '<x-ui.empty-state',
            ],
            'resources/views/livewire/pages/admin/banding.blade.php' => [
                '<x-ui.filter-select',
                '<x-ui.badge',
                '<x-ui.action-menu',
                '<x-ui.empty-state',
            ],
            'resources/views/livewire/pages/admin/asesor/index.blade.php' => [
                '<x-ui.filter-select',
                '<x-ui.status-badge',
                '<x-ui.action-menu',
                '<x-ui.empty-state',
            ],
            'resources/views/livewire/pages/accounts/index.blade.php' => [
                '<x-ui.tabs',
                '<x-ui.status-badge',
                '<x-ui.action-menu',
                '<x-ui.empty-state',
            ],
            'resources/views/livewire/pages/admin/master/dokumen.blade.php' => [
                '<x-ui.status-badge',
                '<x-ui.action-menu',
                '<x-ui.empty-state',
            ],
            'resources/views/livewire/pages/pesantren/akreditasi.blade.php' => [
                '<x-ui.filter-select',
                '<x-ui.status-badge',
                '<x-ui.action-menu',
                '<x-ui.empty-state',
            ],
            'resources/views/livewire/pages/asesor/akreditasi.blade.php' => [
                '<x-ui.filter-select',
                '<x-ui.status-badge',
                '<x-ui.action-menu',
                '<x-ui.empty-state',
            ],
        ];

        foreach ($views as $path => $expectedComponents) {
            $view = file_get_contents(base_path($path));

            foreach ($expectedComponents as $component) {
                $this->assertStringContainsString($component, $view, "{$path} should use {$component}");
            }

            $this->assertStringNotContainsString('<button x-ref="btn"', $view, "{$path} should use x-ui.action-menu instead of custom dropdown triggers.");
            $this->assertStringNotContainsString('x-teleport="body"', $view, "{$path} should use x-ui.action-menu instead of custom teleported menus.");
            $this->assertStringNotContainsString('<select wire:model.live', $view, "{$path} should use x-ui.filter-select for list filters.");
        }
    }

    public function test_module_list_tables_do_not_use_legacy_tailwind_table_scaffolding(): void
    {
        $views = [
            'resources/views/livewire/pages/admin/akreditasi.blade.php',
            'resources/views/livewire/pages/admin/pesantren/index.blade.php',
            'resources/views/livewire/pages/admin/asesor/index.blade.php',
            'resources/views/livewire/pages/accounts/index.blade.php',
            'resources/views/livewire/pages/roles/index.blade.php',
            'resources/views/livewire/pages/admin/master/dokumen.blade.php',
            'resources/views/livewire/pages/admin/banding.blade.php',
            'resources/views/livewire/pages/pesantren/akreditasi.blade.php',
            'resources/views/livewire/pages/asesor/akreditasi.blade.php',
        ];

        foreach ($views as $path) {
            $view = file_get_contents(base_path($path));

            foreach (['py-5 px-4', 'py-8 px', '<tr class="hover:bg-gray', 'rounded border-gray-300', 'h-4 w-4'] as $legacyMarker) {
                $this->assertStringNotContainsString($legacyMarker, $view, "{$path} should not contain legacy table marker {$legacyMarker}");
            }
        }

        foreach ([
            'resources/views/livewire/pages/admin/akreditasi.blade.php',
            'resources/views/livewire/pages/admin/pesantren/index.blade.php',
            'resources/views/livewire/pages/admin/asesor/index.blade.php',
        ] as $path) {
            $view = file_get_contents(base_path($path));

            $this->assertStringContainsString('<x-ui.table-checkbox', $view, "{$path} should use the reusable Metronic table checkbox.");
        }
    }

    public function test_view_only_catatan_modals_use_reusable_metronic_structure(): void
    {
        foreach ([
            'resources/views/livewire/pages/pesantren/akreditasi.blade.php',
            'resources/views/livewire/pages/asesor/akreditasi.blade.php',
        ] as $path) {
            $view = file_get_contents(base_path($path));

            $this->assertStringContainsString('<x-ui.modal-header', $view, "{$path} should use the reusable modal header.");
            $this->assertStringContainsString('<x-ui.modal-body', $view, "{$path} should use the reusable modal body.");
            $this->assertStringContainsString('<x-ui.modal-footer', $view, "{$path} should use the reusable modal footer.");
            $this->assertStringNotContainsString('<div class="p-0 overflow-hidden rounded-4 spm-modal-content-scroll">', $view, "{$path} should not wrap modal content in a custom standalone shell.");
        }
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

        $views = [
            'resources/views/livewire/pages/accounts/index.blade.php',
            'resources/views/livewire/pages/roles/index.blade.php',
            'resources/views/livewire/pages/admin/master/dokumen.blade.php',
            'resources/views/livewire/pages/asesor/akreditasi.blade.php',
            'resources/views/livewire/pages/admin/master-edpm.blade.php',
            'resources/views/livewire/pages/admin/akreditasi.blade.php',
            'resources/views/livewire/pages/admin/akreditasi-detail.blade.php',
            'resources/views/livewire/profile/delete-user-form.blade.php',
        ];

        foreach ($views as $path) {
            $view = file_get_contents(base_path($path));

            $this->assertStringContainsString('<x-ui.modal-header', $view, "{$path} should use the reusable modal header.");
            $this->assertStringContainsString('<x-ui.modal-body', $view, "{$path} should use the reusable modal body.");
            $this->assertStringContainsString('<x-ui.modal-footer', $view, "{$path} should use the reusable modal footer.");
            $this->assertStringContainsString('<x-ui.form-field', $view, "{$path} should use reusable form fields.");
            $this->assertStringNotContainsString('<x-input-label', $view, "{$path} should not use legacy Breeze input labels inside standardized modals.");
            $this->assertStringNotContainsString('<x-text-input', $view, "{$path} should not use legacy Breeze text inputs inside standardized modals.");
            $this->assertStringNotContainsString('<textarea wire:model', $view, "{$path} should use x-ui.textarea.");
        }
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

            <x-ui.modal name="sample-modal" :show="true" maxWidth="lg" focusable>
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
    }

    public function test_sweetalert_actions_use_metronic_helper_without_inline_blade_alerts(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('window.SpmSwal', $script);
        $this->assertStringContainsString('buttonsStyling: false', $script);
        $this->assertStringContainsString('btn btn-primary', $script);
        $this->assertStringContainsString('btn btn-danger', $script);
        $this->assertStringNotContainsString('confirmButtonColor', $script);

        $paths = collect(glob(resource_path('views/livewire/pages/**/*.blade.php')))
            ->merge(glob(resource_path('views/livewire/pages/*.blade.php')))
            ->merge(glob(resource_path('views/livewire/profile/*.blade.php')))
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
                ->assertDontSeeVolt('layout.onboarding-guide')
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
            } else {
                $response->assertSee('id="monthlyChart"', false);
            }
        }
    }

    public function test_header_uses_route_aware_title_and_breadcrumb_for_module_pages(): void
    {
        $this->withoutVite();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

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
            ->assertSee('table table-row-dashed', false)
            ->assertSee('form-control form-control-solid', false)
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
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
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
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

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
            [$pesantren, '/documents/iapm', 'data-module-page="dokumen"'],
            [$asesor, '/asesor/akreditasi', 'data-module-page="asesor-akreditasi"'],
            [$asesor, '/documents/iapm', 'data-module-page="dokumen"'],
        ] as [$user, $uri, $marker]) {
            $this->actingAs($user)
                ->get($uri)
                ->assertOk()
                ->assertSee($marker, false)
                ->assertSee('spm-page-title', false)
                ->assertSee('data-ui-table="metronic"', false);
        }
    }

    public function test_detail_pages_render_metronic_detail_foundation(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

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

    public function test_pesantren_input_pages_render_reusable_metronic_form_foundation(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
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
            'resources/views/livewire/pages/admin/pesantren/detail.blade.php' => [
                '<x-ui.page',
                '<x-ui.section-card',
                '<x-ui.detail-item',
                '<x-ui.simple-table',
                '<x-ui.document-item',
                '<x-ui.button',
            ],
            'resources/views/livewire/pages/admin/akreditasi-detail.blade.php' => [
                '<x-ui.page',
                '<x-ui.tabs',
                '<x-ui.section-card',
                '<x-ui.simple-table',
                '<x-ui.document-item',
                '<x-ui.form-field',
                '<x-ui.button',
            ],
            'resources/views/livewire/pages/pesantren/akreditasi-detail.blade.php' => [
                '<x-ui.page',
                '<x-ui.tabs',
                '<x-ui.section-card',
                '<x-ui.simple-table',
                '<x-ui.document-item',
                '<x-ui.form-field',
                '<x-ui.button',
            ],
            'resources/views/livewire/pages/asesor/akreditasi-detail.blade.php' => [
                '<x-ui.page',
                '<x-ui.tabs',
                '<x-ui.section-card',
                '<x-ui.simple-table',
                '<x-ui.document-item',
                '<x-ui.form-field',
                '<x-ui.button',
            ],
            'resources/views/livewire/pages/pesantren/ipm.blade.php' => [
                '<x-ui.page',
                '<x-ui.section-card',
                '<x-ui.form-field',
                '<x-ui.file-upload',
                '<x-ui.document-item',
                '<x-ui.button',
            ],
            'resources/views/livewire/pages/pesantren/sdm.blade.php' => [
                '<x-ui.page',
                '<x-ui.section-card',
                '<x-ui.simple-table',
                '<x-ui.input',
                '<x-ui.button',
            ],
            'resources/views/livewire/pages/pesantren/edpm.blade.php' => [
                '<x-ui.page',
                '<x-ui.section-card',
                '<x-ui.simple-table',
                '<x-ui.select',
                '<x-ui.input',
                '<x-ui.textarea',
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
            $view = file_get_contents(base_path($path));

            foreach ($expectedComponents as $component) {
                $this->assertStringContainsString($component, $view, "{$path} should use {$component}");
            }

            foreach ($legacyMarkers as $marker) {
                $this->assertStringNotContainsString($marker, $view, "{$path} should not contain legacy UI marker {$marker}");
            }
        }
    }
}
