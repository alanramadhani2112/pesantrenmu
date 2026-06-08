<?php

namespace Tests\Unit;

use App\Models\DocumentCategory;
use App\Services\SidebarMenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarMenuServiceTest extends TestCase
{
    use RefreshDatabase;

    private SidebarMenuService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SidebarMenuService;
        $this->seedDocumentCategories();
    }

    /**
     * Seed the three default categories that the dynamic Dokumen group
     * depends on so structural assertions stay deterministic.
     */
    private function seedDocumentCategories(): void
    {
        DocumentCategory::query()->delete();

        DocumentCategory::create([
            'name' => 'IAPM',
            'slug' => 'iapm',
            'description' => 'Instrumen Akreditasi Pesantren Muhammadiyah.',
            'icon' => 'document-stack',
            'visibility' => DocumentCategory::VISIBILITY_PUBLIC,
            'pesantren_can_upload' => false,
            'asesor_can_upload' => false,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        DocumentCategory::create([
            'name' => 'Kartu Kendali Visitasi',
            'slug' => 'kartu_kendali',
            'description' => 'Kartu kendali pelaksanaan visitasi.',
            'icon' => 'clipboard-check',
            'visibility' => DocumentCategory::VISIBILITY_PESANTREN_SECRET,
            'pesantren_can_upload' => true,
            'asesor_can_upload' => false,
            'is_active' => true,
            'sort_order' => 20,
        ]);

        DocumentCategory::create([
            'name' => 'Laporan Visitasi',
            'slug' => 'visitasi',
            'description' => 'Laporan hasil visitasi asesor.',
            'icon' => 'document-up',
            'visibility' => DocumentCategory::VISIBILITY_ASESOR_SECRET,
            'pesantren_can_upload' => false,
            'asesor_can_upload' => true,
            'is_active' => true,
            'sort_order' => 30,
        ]);
    }

    // ─── Admin Menu (Role ID = 1) ───────────────────────────────────────────────

    public function test_admin_menu_has_correct_sections(): void
    {
        $menu = $this->service->getMenuForRole(1);

        $this->assertCount(4, $menu);

        $sectionLabels = array_column($menu, 'label');
        $this->assertSame(
            ['Monitoring', 'Operasional Akreditasi', 'Master Data', 'Administrasi'],
            $sectionLabels
        );
    }

    public function test_admin_menu_monitoring_section_items(): void
    {
        $menu = $this->service->getMenuForRole(1);
        $monitoringItems = $menu[0]['items'];

        $this->assertCount(2, $monitoringItems);

        $keys = array_column($monitoringItems, 'key');
        $this->assertSame(['dashboard', 'akreditasi_admin'], $keys);
    }

    public function test_admin_menu_penilaian_banding_section_items(): void
    {
        $menu = $this->service->getMenuForRole(1);
        $items = $menu[1]['items'];

        $this->assertCount(3, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(['daftar_pesantren', 'daftar_asesor', 'banding'], $keys);
    }

    public function test_admin_menu_master_data_section_items(): void
    {
        $menu = $this->service->getMenuForRole(1);
        $items = $menu[2]['items'];

        $this->assertCount(3, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(
            ['master_edpm', 'master_kategori_dokumen', 'master_dokumen'],
            $keys
        );
    }

    public function test_admin_menu_master_kategori_dokumen_routes_correctly(): void
    {
        $menu = $this->service->getMenuForRole(1);
        $items = $menu[2]['items'];

        $kategoriItem = collect($items)->firstWhere('key', 'master_kategori_dokumen');

        $this->assertNotNull($kategoriItem);
        $this->assertSame('admin.master-kategori-dokumen.index', $kategoriItem['route']);
        $this->assertSame('Kategori Dokumen', $kategoriItem['label']);
    }

    public function test_admin_menu_administrasi_sistem_section_items(): void
    {
        $menu = $this->service->getMenuForRole(1);
        $items = $menu[3]['items'];

        $this->assertCount(3, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(['akun_pengguna', 'failed_notifications', 'trash'], $keys);
    }

    // ─── Asesor Menu (Role ID = 2) ──────────────────────────────────────────────

    public function test_asesor_menu_has_correct_sections(): void
    {
        $menu = $this->service->getMenuForRole(2);

        $this->assertCount(3, $menu);

        $sectionLabels = array_column($menu, 'label');
        $this->assertSame(
            ['Profil', 'Tugas Akreditasi', 'Dokumen'],
            $sectionLabels
        );
    }

    public function test_asesor_menu_profil_section_items(): void
    {
        $menu = $this->service->getMenuForRole(2);
        $items = $menu[0]['items'];

        $this->assertCount(1, $items);
        $this->assertSame('profil_asesor', $items[0]['key']);
    }

    public function test_asesor_menu_tugas_section_items(): void
    {
        $menu = $this->service->getMenuForRole(2);
        $items = $menu[1]['items'];

        $this->assertCount(5, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(
            ['daftar_tugas', 'review_berkas', 'jadwal_visitasi', 'input_nilai', 'laporan_visitasi_workflow'],
            $keys
        );
    }

    public function test_asesor_menu_dokumen_includes_public_and_asesor_secret_only(): void
    {
        $menu = $this->service->getMenuForRole(2);
        $items = $menu[2]['items'];

        // IAPM public + Semua Dokumen. Laporan Visitasi is represented in the workflow section.
        $this->assertCount(2, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(
            ['dokumen_asesor_iapm', 'semua_dokumen_asesor'],
            $keys
        );

        // Asesor must NOT see pesantren_secret category
        $this->assertNotContains('dokumen_asesor_kartu_kendali', $keys);
        $this->assertNotContains('dokumen_asesor_visitasi', $keys);
    }

    public function test_asesor_dokumen_items_use_documents_route_with_slug_param(): void
    {
        $menu = $this->service->getMenuForRole(2);
        $items = $menu[2]['items'];

        $iapmItem = collect($items)->firstWhere('key', 'dokumen_asesor_iapm');

        $this->assertSame('documents.index', $iapmItem['route']);
        $this->assertSame(['doc' => 'iapm'], $iapmItem['route_params']);
    }

    // ─── Pesantren Menu (Role ID = 3) ───────────────────────────────────────────

    public function test_pesantren_menu_has_correct_sections(): void
    {
        $menu = $this->service->getMenuForRole(3);

        $this->assertCount(5, $menu);

        $sectionLabels = array_column($menu, 'label');
        $this->assertSame(
            ['Persiapan Akreditasi', 'Pengajuan', 'Visitasi', 'Hasil Akreditasi', 'Dokumen'],
            $sectionLabels
        );
    }

    public function test_pesantren_menu_persiapan_data_section_items(): void
    {
        $menu = $this->service->getMenuForRole(3);
        $items = $menu[0]['items'];

        $this->assertCount(4, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(['profil_pesantren', 'ipm', 'data_sdm', 'edpm_ipr'], $keys);
    }

    public function test_pesantren_menu_proses_akreditasi_section_items(): void
    {
        $menu = $this->service->getMenuForRole(3);
        $items = $menu[1]['items'];

        $this->assertCount(2, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(['pengajuan', 'status_perbaikan'], $keys);
    }

    public function test_pesantren_menu_visitasi_and_hasil_items_follow_business_flow(): void
    {
        $menu = $this->service->getMenuForRole(3);

        $visitasiKeys = array_column($menu[2]['items'], 'key');
        $hasilKeys = array_column($menu[3]['items'], 'key');

        $this->assertSame(['kartu_kendali_visitasi'], $visitasiKeys);
        $this->assertSame(['hasil_akhir'], $hasilKeys);
    }

    public function test_pesantren_menu_dokumen_includes_public_and_pesantren_secret_only(): void
    {
        $menu = $this->service->getMenuForRole(3);
        $items = $menu[4]['items'];

        // IAPM public + Semua Dokumen. Kartu Kendali is represented in the Visitasi section.
        $this->assertCount(2, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(
            ['dokumen_pesantren_iapm', 'semua_dokumen_pesantren'],
            $keys
        );

        // Pesantren must NOT see asesor_secret category
        $this->assertNotContains('dokumen_pesantren_visitasi', $keys);
        $this->assertNotContains('dokumen_pesantren_kartu_kendali', $keys);
    }

    public function test_inactive_categories_are_excluded_from_dokumen_menu(): void
    {
        DocumentCategory::query()->where('slug', 'kartu_kendali')->update(['is_active' => false]);

        // Force a fresh service so the in-memory cache is bypassed
        $service = new SidebarMenuService;
        $menu = $service->getMenuForRole(3);
        $items = $menu[4]['items'];

        $keys = array_column($items, 'key');
        $this->assertSame(
            ['dokumen_pesantren_iapm', 'semua_dokumen_pesantren'],
            $keys
        );
    }

    // ─── Section Headers ────────────────────────────────────────────────────────

    public function test_all_section_headers_are_non_empty_strings(): void
    {
        $roles = [1, 2, 3];

        foreach ($roles as $roleId) {
            $menu = $this->service->getMenuForRole($roleId);

            foreach ($menu as $section) {
                $this->assertArrayHasKey('label', $section);
                $this->assertIsString($section['label']);
                $this->assertNotEmpty($section['label']);
            }
        }
    }

    public function test_unknown_role_returns_empty_menu(): void
    {
        $this->assertSame([], $this->service->getMenuForRole(999));
        $this->assertSame([], $this->service->getMenuForRole(0));
    }

    // ─── Super Admin Menu (Role ID = 4) ─────────────────────────────────────────

    public function test_super_admin_menu_inherits_admin_sections_plus_coming_soon(): void
    {
        $menu = $this->service->getMenuForRole(4);

        $this->assertCount(4, $menu);

        $sectionLabels = array_column($menu, 'label');
        $this->assertSame(
            ['Monitoring', 'Operasional Akreditasi', 'Master Data', 'Manajemen Sistem'],
            $sectionLabels
        );
    }

    public function test_super_admin_menu_system_section_includes_role_and_permission_management(): void
    {
        $menu = $this->service->getMenuForRole(4);
        $system = collect($menu)->firstWhere('label', 'Manajemen Sistem');

        $this->assertNotNull($system);
        $keys = array_column($system['items'], 'key');
        $this->assertContains('role_sistem', $keys);
        $this->assertContains('hak_akses', $keys);

        $roleItem = collect($system['items'])->firstWhere('key', 'role_sistem');
        $rolePermItem = collect($system['items'])->firstWhere('key', 'hak_akses');

        $this->assertSame('admin.roles.index', $roleItem['route']);
        $this->assertSame('Role Sistem', $roleItem['label']);
        $this->assertSame('admin.role-permission.index', $rolePermItem['route']);
        $this->assertSame('Hak Akses', $rolePermItem['label']);
    }

    public function test_admin_menu_does_not_include_role_permission_management(): void
    {
        // Regular admin (id=1) must NOT see master_role_permission item.
        $menu = $this->service->getMenuForRole(1);
        $masterData = collect($menu)->firstWhere('label', 'Master Data');
        $adminSection = collect($menu)->firstWhere('label', 'Administrasi');

        $keys = array_column($masterData['items'], 'key');
        $adminKeys = array_column($adminSection['items'], 'key');

        $this->assertNotContains('hak_akses', $keys);
        $this->assertNotContains('role_sistem', $adminKeys);
        $this->assertNotContains('hak_akses', $adminKeys);
    }

    // ─── Coming Soon Items (removed — feature not implemented) ──────────────────

    public function test_no_coming_soon_section_in_any_role_menu(): void
    {
        foreach ([1, 2, 3, 4] as $roleId) {
            $menu = $this->service->getMenuForRole($roleId);
            $soon = collect($menu)->firstWhere('label', 'Coming Soon');
            $this->assertNull($soon, "Role $roleId should not have a Coming Soon section");
        }
    }

    // ─── Tooltips ────────────────────────────────────────────────────────────────

    public function test_tooltip_returns_correct_text_for_static_keys(): void
    {
        $this->assertSame(
            'Lihat ringkasan dan statistik sistem akreditasi',
            $this->service->getTooltip('dashboard')
        );

        $this->assertSame(
            'Atur kategori dan visibilitas dokumen per role',
            $this->service->getTooltip('master_kategori_dokumen')
        );
    }

    public function test_tooltip_returns_null_for_unknown_key(): void
    {
        $this->assertNull($this->service->getTooltip('nonexistent_key_xyz'));
    }

    public function test_static_menu_items_have_matching_tooltip_in_lookup(): void
    {
        $roles = [1, 2, 3];

        foreach ($roles as $roleId) {
            $menu = $this->service->getMenuForRole($roleId);

            foreach ($menu as $section) {
                foreach ($section['items'] as $item) {
                    // Dynamic Dokumen items derive tooltip from category description,
                    // so they are intentionally absent from the static lookup.
                    if (str_starts_with($item['key'], 'dokumen_') ||
                        str_starts_with($item['key'], 'semua_dokumen') ||
                        str_starts_with($item['key'], 'soon_') ||
                        ($item['coming_soon'] ?? false)) {
                        continue;
                    }

                    $tooltipFromService = $this->service->getTooltip($item['key']);
                    $this->assertSame(
                        $item['tooltip'],
                        $tooltipFromService,
                        "Tooltip mismatch for key '{$item['key']}'"
                    );
                }
            }
        }
    }

    // ─── Badge / Progress Flags ─────────────────────────────────────────────────

    public function test_admin_badge_items_have_show_badge_true(): void
    {
        $menu = $this->service->getMenuForRole(1);

        $akreditasiItem = $menu[0]['items'][1];
        $this->assertSame('akreditasi_admin', $akreditasiItem['key']);
        $this->assertTrue($akreditasiItem['show_badge']);

        $bandingItem = $menu[1]['items'][2];
        $this->assertSame('banding', $bandingItem['key']);
        $this->assertTrue($bandingItem['show_badge']);
    }

    public function test_asesor_badge_item_has_show_badge_true(): void
    {
        $menu = $this->service->getMenuForRole(2);

        $tugasItem = $menu[1]['items'][0];
        $this->assertSame('daftar_tugas', $tugasItem['key']);
        $this->assertTrue($tugasItem['show_badge']);
    }

    public function test_pesantren_persiapan_items_show_progress(): void
    {
        $menu = $this->service->getMenuForRole(3);

        $progressMap = collect($menu[0]['items'])->pluck('show_progress', 'key')->all();

        $this->assertTrue($progressMap['profil_pesantren']);
        $this->assertTrue($progressMap['ipm']);
        $this->assertTrue($progressMap['data_sdm']);
        $this->assertTrue($progressMap['edpm_ipr']);
    }

    public function test_kartu_kendali_menu_targets_pascha_visitasi_context(): void
    {
        $menu = $this->service->getMenuForRole(3);

        $item = $menu[2]['items'][0];

        $this->assertSame('kartu_kendali_visitasi', $item['key']);
        $this->assertSame(['focus' => 'kartu_kendali'], $item['route_query']);
        $this->assertSame(['focus' => 'kartu_kendali'], $item['active_query']);
    }

    public function test_dokumen_items_share_documents_route(): void
    {
        $menu = $this->service->getMenuForRole(3);
        $items = $menu[4]['items'];

        foreach ($items as $item) {
            $this->assertSame('documents.index', $item['route']);
            $this->assertArrayHasKey('route_params', $item);
            $this->assertArrayHasKey('doc', $item['route_params']);
        }
    }

    // ─── Required Item Keys ─────────────────────────────────────────────────────

    public function test_all_static_menu_items_have_required_keys(): void
    {
        $requiredKeys = [
            'key',
            'label',
            'route',
            'icon',
            'active_pattern',
            'tooltip',
            'show_progress',
            'show_badge',
        ];

        foreach ([1, 2, 3] as $roleId) {
            $menu = $this->service->getMenuForRole($roleId);

            foreach ($menu as $section) {
                foreach ($section['items'] as $item) {
                    foreach ($requiredKeys as $key) {
                        $this->assertArrayHasKey(
                            $key,
                            $item,
                            "Missing key '{$key}' on item '".($item['key'] ?? '?')."'"
                        );
                    }
                }
            }
        }
    }
}
