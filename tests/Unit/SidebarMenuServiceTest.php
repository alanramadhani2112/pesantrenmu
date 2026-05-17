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
        $this->service = new SidebarMenuService();
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

        $this->assertCount(5, $menu);

        $sectionLabels = array_column($menu, 'label');
        $this->assertSame(
            ['Monitoring', 'Penilaian & Banding', 'Master Data', 'Administrasi Sistem', 'Coming Soon'],
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
        $this->assertSame('admin.master-kategori-dokumen', $kategoriItem['route']);
        $this->assertSame('Kategori Dokumen', $kategoriItem['label']);
    }

    public function test_admin_menu_administrasi_sistem_section_items(): void
    {
        $menu = $this->service->getMenuForRole(1);
        $items = $menu[3]['items'];

        $this->assertCount(4, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(['failed_notifications', 'trash', 'akun_pengguna', 'peran_hak_akses'], $keys);
    }

    // ─── Asesor Menu (Role ID = 2) ──────────────────────────────────────────────

    public function test_asesor_menu_has_correct_sections(): void
    {
        $menu = $this->service->getMenuForRole(2);

        $this->assertCount(4, $menu);

        $sectionLabels = array_column($menu, 'label');
        $this->assertSame(
            ['Profil', 'Tugas Akreditasi', 'Dokumen', 'Coming Soon'],
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

        $this->assertCount(1, $items);
        $this->assertSame('daftar_tugas', $items[0]['key']);
    }

    public function test_asesor_menu_dokumen_includes_public_and_asesor_secret_only(): void
    {
        $menu = $this->service->getMenuForRole(2);
        $items = $menu[2]['items'];

        // 2 categories (iapm public + visitasi asesor_secret) + Semua Dokumen entry
        $this->assertCount(3, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(
            ['dokumen_asesor_iapm', 'dokumen_asesor_visitasi', 'semua_dokumen_asesor'],
            $keys
        );

        // Asesor must NOT see pesantren_secret category
        $this->assertNotContains('dokumen_asesor_kartu_kendali', $keys);
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

        $this->assertCount(4, $menu);

        $sectionLabels = array_column($menu, 'label');
        $this->assertSame(
            ['Persiapan Data', 'Proses Akreditasi', 'Dokumen', 'Coming Soon'],
            $sectionLabels
        );
    }

    public function test_pesantren_menu_persiapan_data_section_items(): void
    {
        $menu = $this->service->getMenuForRole(3);
        $items = $menu[0]['items'];

        $this->assertCount(3, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(['profil_pesantren', 'ipm', 'data_sdm'], $keys);
    }

    public function test_pesantren_menu_proses_akreditasi_section_items(): void
    {
        $menu = $this->service->getMenuForRole(3);
        $items = $menu[1]['items'];

        $this->assertCount(2, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(['edpm', 'pengajuan'], $keys);
    }

    public function test_pesantren_menu_dokumen_includes_public_and_pesantren_secret_only(): void
    {
        $menu = $this->service->getMenuForRole(3);
        $items = $menu[2]['items'];

        // 2 categories (iapm public + kartu_kendali pesantren_secret) + Semua Dokumen
        $this->assertCount(3, $items);

        $keys = array_column($items, 'key');
        $this->assertSame(
            ['dokumen_pesantren_iapm', 'dokumen_pesantren_kartu_kendali', 'semua_dokumen_pesantren'],
            $keys
        );

        // Pesantren must NOT see asesor_secret category
        $this->assertNotContains('dokumen_pesantren_visitasi', $keys);
    }

    public function test_inactive_categories_are_excluded_from_dokumen_menu(): void
    {
        DocumentCategory::query()->where('slug', 'kartu_kendali')->update(['is_active' => false]);

        // Force a fresh service so the in-memory cache is bypassed
        $service = new SidebarMenuService();
        $menu = $service->getMenuForRole(3);
        $items = $menu[2]['items'];

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

        $this->assertCount(5, $menu);

        $sectionLabels = array_column($menu, 'label');
        $this->assertSame(
            ['Monitoring', 'Penilaian & Banding', 'Master Data', 'Administrasi Sistem', 'Coming Soon'],
            $sectionLabels
        );
    }

    public function test_super_admin_menu_master_data_includes_role_permission(): void
    {
        $menu = $this->service->getMenuForRole(4);
        $masterData = collect($menu)->firstWhere('label', 'Master Data');

        $this->assertNotNull($masterData);
        $keys = array_column($masterData['items'], 'key');
        $this->assertContains('master_role_permission', $keys);

        $rolePermItem = collect($masterData['items'])->firstWhere('key', 'master_role_permission');
        $this->assertSame('admin.master-role-permission', $rolePermItem['route']);
        $this->assertSame('Peran & Hak Akses', $rolePermItem['label']);
    }

    public function test_admin_menu_does_not_include_role_permission_management(): void
    {
        // Regular admin (id=1) must NOT see master_role_permission item.
        $menu = $this->service->getMenuForRole(1);
        $masterData = collect($menu)->firstWhere('label', 'Master Data');

        $keys = array_column($masterData['items'], 'key');
        $this->assertNotContains('master_role_permission', $keys);
    }

    // ─── Coming Soon Items ──────────────────────────────────────────────────────

    public function test_pesantren_coming_soon_items_present(): void
    {
        $menu = $this->service->getMenuForRole(3);
        $soon = collect($menu)->firstWhere('label', 'Coming Soon');

        $this->assertNotNull($soon);
        $keys = array_column($soon['items'], 'key');

        $this->assertContains('soon_sertifikat_digital', $keys);
        $this->assertContains('soon_forum_mutu', $keys);
        $this->assertContains('soon_self_assessment', $keys);
        $this->assertContains('soon_benchmark', $keys);
        $this->assertContains('soon_help_center', $keys);
    }

    public function test_asesor_coming_soon_items_present(): void
    {
        $menu = $this->service->getMenuForRole(2);
        $soon = collect($menu)->firstWhere('label', 'Coming Soon');

        $this->assertNotNull($soon);
        $keys = array_column($soon['items'], 'key');

        $this->assertContains('soon_visitasi_cerdas', $keys);
        $this->assertContains('soon_mobile_companion', $keys);
        $this->assertContains('soon_sertifikat_cpd', $keys);
        $this->assertContains('soon_help_center', $keys);
    }

    public function test_admin_coming_soon_items_present(): void
    {
        $menu = $this->service->getMenuForRole(1);
        $soon = collect($menu)->firstWhere('label', 'Coming Soon');

        $this->assertNotNull($soon);
        $keys = array_column($soon['items'], 'key');

        $this->assertContains('soon_analytics_nasional', $keys);
        $this->assertContains('soon_broadcast', $keys);
        $this->assertContains('soon_export_compliance', $keys);
        $this->assertContains('soon_help_center', $keys);
    }

    public function test_coming_soon_items_are_flagged_disabled_with_badge(): void
    {
        $menu = $this->service->getMenuForRole(3);
        $soon = collect($menu)->firstWhere('label', 'Coming Soon');

        foreach ($soon['items'] as $item) {
            $this->assertTrue($item['coming_soon'] ?? false, "Item {$item['key']} missing coming_soon flag");
            $this->assertSame('Soon', $item['badge_text'] ?? null);
            $this->assertNull($item['route'], "Item {$item['key']} should have null route");
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

        foreach ($menu[0]['items'] as $item) {
            $this->assertTrue(
                $item['show_progress'],
                "Item '{$item['key']}' should show progress"
            );
        }
    }

    public function test_dokumen_items_share_documents_route(): void
    {
        $menu = $this->service->getMenuForRole(3);
        $items = $menu[2]['items'];

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
                            "Missing key '{$key}' on item '" . ($item['key'] ?? '?') . "'"
                        );
                    }
                }
            }
        }
    }
}
