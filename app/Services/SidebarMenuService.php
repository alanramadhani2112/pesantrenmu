<?php

namespace App\Services;

use App\Models\DocumentCategory;
use Illuminate\Support\Collection;

class SidebarMenuService
{
    /**
     * Role ID constants.
     */
    private const ROLE_ADMIN = 1;
    private const ROLE_ASESOR = 2;
    private const ROLE_PESANTREN = 3;
    private const ROLE_SUPER_ADMIN = 4;

    /**
     * Cached document categories per role-scope to avoid duplicate queries
     * within the same request lifecycle.
     *
     * @var array<string, Collection<int, DocumentCategory>>
     */
    private array $documentCategoriesByRole = [];

    /**
     * Returns the menu configuration for a given role.
     * Each section contains a label and an array of menu items.
     *
     * @param int $roleId
     * @return array<int, array{label: string, items: array}>
     */
    public function getMenuForRole(int $roleId): array
    {
        return match ($roleId) {
            self::ROLE_SUPER_ADMIN => $this->getSuperAdminMenu(),
            self::ROLE_ADMIN => $this->withComingSoon($this->getAdminMenu(), 'admin'),
            self::ROLE_ASESOR => $this->withComingSoon($this->getAsesorMenu(), 'asesor'),
            self::ROLE_PESANTREN => $this->withComingSoon($this->getPesantrenMenu(), 'pesantren'),
            default => [],
        };
    }

    /**
     * Super admin sees the full admin menu plus an exclusive
     * "Peran & Hak Akses" CRUD entry, then the global Coming Soon group.
     */
    private function getSuperAdminMenu(): array
    {
        $menu = $this->getAdminMenu();

        // Inject master_role_permission into the Master Data group.
        foreach ($menu as $idx => $group) {
            if (($group['label'] ?? null) === 'Master Data') {
                $menu[$idx]['items'][] = [
                    'key' => 'master_role_permission',
                    'label' => 'Peran & Hak Akses',
                    'route' => 'admin.master-role-permission',
                    'icon' => 'shield-tick',
                    'active_pattern' => 'admin.master-role-permission',
                    'tooltip' => 'Kelola matriks peran dan permission tiap role secara dinamis',
                    'show_progress' => false,
                    'show_badge' => false,
                ];
                break;
            }
        }

        return $this->withComingSoon($menu, 'super_admin');
    }

    /**
     * Append the "Coming Soon" group containing roadmap items for the role.
     *
     * @param  array<int, array<string, mixed>>  $menu
     */
    private function withComingSoon(array $menu, string $roleScope): array
    {
        $items = $this->buildComingSoonItems($roleScope);
        if (empty($items)) {
            return $menu;
        }

        $menu[] = [
            'label' => 'Coming Soon',
            'items' => $items,
        ];

        return $menu;
    }

    /**
     * Build the disabled "Soon" roadmap items per role scope.
     *
     * Items are intentionally rendered as disabled links with a "Soon" badge
     * so users can see what is planned for the next development cycle.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildComingSoonItems(string $roleScope): array
    {
        $catalog = match ($roleScope) {
            'pesantren' => [
                ['key' => 'soon_sertifikat_digital', 'label' => 'Sertifikat Akreditasi Digital', 'icon' => 'award', 'tooltip' => 'Unduh sertifikat akreditasi digital lengkap dengan QR verifikasi (segera hadir).'],
                ['key' => 'soon_forum_mutu', 'label' => 'Forum Mutu Antar-Pesantren', 'icon' => 'messages', 'tooltip' => 'Diskusi dan berbagi praktik baik penjaminan mutu antar pesantren (segera hadir).'],
                ['key' => 'soon_self_assessment', 'label' => 'Self-Assessment Mandiri', 'icon' => 'check-circle', 'tooltip' => 'Evaluasi diri pra-akreditasi sebelum pengajuan resmi (segera hadir).'],
                ['key' => 'soon_benchmark', 'label' => 'Benchmark Pesantren Sejenis', 'icon' => 'chart-line-up', 'tooltip' => 'Bandingkan capaian Anda dengan pesantren sejenis secara anonim (segera hadir).'],
            ],
            'asesor' => [
                ['key' => 'soon_visitasi_cerdas', 'label' => 'Penjadwalan Visitasi Cerdas', 'icon' => 'calendar-tick', 'tooltip' => 'Saran jadwal visitasi otomatis berdasarkan lokasi dan kalender Anda (segera hadir).'],
                ['key' => 'soon_mobile_companion', 'label' => 'Mobile Companion Visitasi', 'icon' => 'phone', 'tooltip' => 'Aplikasi pendamping visitasi offline-first untuk lapangan (segera hadir).'],
                ['key' => 'soon_sertifikat_cpd', 'label' => 'Sertifikat Asesor & CPD', 'icon' => 'medal-star', 'tooltip' => 'Pelacakan jam Continuing Professional Development asesor (segera hadir).'],
            ],
            'admin', 'super_admin' => [
                ['key' => 'soon_analytics_nasional', 'label' => 'Dashboard Analytics Nasional', 'icon' => 'chart-pie-simple', 'tooltip' => 'Heatmap dan tren akreditasi nasional (segera hadir).'],
                ['key' => 'soon_broadcast', 'label' => 'Broadcast Notifikasi', 'icon' => 'notification', 'tooltip' => 'Pengumuman terjadwal per role dan wilayah (segera hadir).'],
                ['key' => 'soon_export_compliance', 'label' => 'Export Laporan Kemenag/BAN', 'icon' => 'file-down', 'tooltip' => 'Ekspor laporan dalam format Kemenag dan BAN-PT (segera hadir).'],
            ],
            default => [],
        };

        // Universal item available to every role.
        $catalog[] = [
            'key' => 'soon_help_center',
            'label' => 'Pusat Bantuan & FAQ',
            'icon' => 'information',
            'tooltip' => 'Knowledge base dan tanya jawab seputar penggunaan sistem (segera hadir).',
        ];

        return array_map(static function (array $item): array {
            return [
                'key' => $item['key'],
                'label' => $item['label'],
                'route' => null,
                'icon' => $item['icon'],
                'active_pattern' => '__never__',
                'tooltip' => $item['tooltip'],
                'show_progress' => false,
                'show_badge' => false,
                'coming_soon' => true,
                'badge_text' => 'Soon',
            ];
        }, $catalog);
    }

    /**
     * Returns tooltip text for a given menu item key.
     *
     * @param string $menuKey
     * @return string|null
     */
    public function getTooltip(string $menuKey): ?string
    {
        $tooltips = $this->getAllTooltips();

        return $tooltips[$menuKey] ?? null;
    }

    /**
     * Get menu configuration for Pesantren role.
     */
    private function getPesantrenMenu(): array
    {
        return [
            [
                'label' => 'Persiapan Data',
                'items' => [
                    [
                        'key' => 'profil_pesantren',
                        'label' => 'Profil Pesantren',
                        'route' => 'pesantren.profile',
                        'icon' => 'hat',
                        'active_pattern' => 'pesantren.profile',
                        'tooltip' => 'Kelola data profil dan informasi dasar pesantren Anda',
                        'show_progress' => true,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'ipm',
                        'label' => 'IPM',
                        'route' => 'pesantren.ipm',
                        'icon' => 'document',
                        'active_pattern' => 'pesantren.ipm',
                        'tooltip' => 'Kelola dokumen Instrumen Penilaian Mutu pesantren',
                        'show_progress' => true,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'data_sdm',
                        'label' => 'Data SDM',
                        'route' => 'pesantren.sdm',
                        'icon' => 'users',
                        'active_pattern' => 'pesantren.sdm',
                        'tooltip' => 'Kelola data sumber daya manusia pesantren',
                        'show_progress' => true,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Proses Akreditasi',
                'items' => [
                    [
                        'key' => 'edpm',
                        'label' => 'EDPM',
                        'route' => 'pesantren.edpm',
                        'icon' => 'paper',
                        'active_pattern' => 'pesantren.edpm',
                        'tooltip' => 'Isi Evaluasi Diri Penjaminan Mutu pesantren',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'pengajuan',
                        'label' => 'Pengajuan',
                        'route' => 'pesantren.akreditasi',
                        'icon' => 'shield-lock',
                        'active_pattern' => 'pesantren.akreditasi*',
                        'tooltip' => 'Ajukan dan pantau status akreditasi pesantren',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Dokumen',
                'items' => $this->buildDokumenItems('pesantren'),
            ],
        ];
    }

    /**
     * Get menu configuration for Admin role.
     */
    private function getAdminMenu(): array
    {
        return [
            [
                'label' => 'Monitoring',
                'items' => [
                    [
                        'key' => 'dashboard',
                        'label' => 'Dashboard',
                        'route' => 'dashboard',
                        'icon' => 'grid',
                        'active_pattern' => 'dashboard',
                        'tooltip' => 'Lihat ringkasan dan statistik sistem akreditasi',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'akreditasi_admin',
                        'label' => 'Akreditasi',
                        'route' => 'admin.akreditasi',
                        'icon' => 'shield',
                        'active_pattern' => 'admin.akreditasi*',
                        'tooltip' => 'Kelola dan review pengajuan akreditasi pesantren',
                        'show_progress' => false,
                        'show_badge' => true,
                    ],
                ],
            ],
            [
                'label' => 'Penilaian & Banding',
                'items' => [
                    [
                        'key' => 'daftar_pesantren',
                        'label' => 'Daftar Pesantren',
                        'route' => 'admin.pesantren.index',
                        'icon' => 'users',
                        'active_pattern' => 'admin.pesantren.*',
                        'tooltip' => 'Lihat dan kelola data seluruh pesantren terdaftar',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'daftar_asesor',
                        'label' => 'Daftar Asesor',
                        'route' => 'admin.asesor.index',
                        'icon' => 'user-circle',
                        'active_pattern' => 'admin.asesor.*',
                        'tooltip' => 'Lihat dan kelola data asesor akreditasi',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'banding',
                        'label' => 'Banding',
                        'route' => 'admin.banding',
                        'icon' => 'shield-lock',
                        'active_pattern' => 'admin.banding*',
                        'tooltip' => 'Kelola pengajuan banding dari pesantren',
                        'show_progress' => false,
                        'show_badge' => true,
                    ],
                ],
            ],
            [
                'label' => 'Master Data',
                'items' => [
                    [
                        'key' => 'master_edpm',
                        'label' => 'Komponen EDPM',
                        'route' => 'admin.master-edpm',
                        'icon' => 'data',
                        'active_pattern' => 'admin.master-edpm',
                        'tooltip' => 'Kelola komponen dan butir Evaluasi Diri Penjaminan Mutu',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'master_kategori_dokumen',
                        'label' => 'Kategori Dokumen',
                        'route' => 'admin.master-kategori-dokumen',
                        'icon' => 'abstract-26',
                        'active_pattern' => 'admin.master-kategori-dokumen',
                        'tooltip' => 'Atur kategori dan visibilitas dokumen per role',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'master_dokumen',
                        'label' => 'Dokumen Wajib',
                        'route' => 'admin.master-dokumen',
                        'icon' => 'document-stack',
                        'active_pattern' => 'admin.master-dokumen',
                        'tooltip' => 'Kelola katalog dokumen wajib yang diunggah pesantren dan asesor',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Administrasi Sistem',
                'items' => [
                    [
                        'key' => 'failed_notifications',
                        'label' => 'Notifikasi Gagal',
                        'route' => 'admin.failed-notifications',
                        'icon' => 'notification-bing',
                        'active_pattern' => 'admin.failed-notifications',
                        'tooltip' => 'Pantau dan kelola notifikasi yang gagal terkirim',
                        'show_progress' => false,
                        'show_badge' => true,
                    ],
                    [
                        'key' => 'trash',
                        'label' => 'Sampah Akreditasi',
                        'route' => 'admin.trash',
                        'icon' => 'trash',
                        'active_pattern' => 'admin.trash',
                        'tooltip' => 'Kelola data akreditasi terhapus dengan masa retensi sebelum dihapus permanen',
                        'show_progress' => false,
                        'show_badge' => true,
                    ],
                    [
                        'key' => 'akun_pengguna',
                        'label' => 'Akun Pengguna',
                        'route' => 'accounts.index',
                        'icon' => 'profile-user',
                        'active_pattern' => 'accounts.*',
                        'tooltip' => 'Kelola akun admin, asesor, dan pesantren beserta status aktivasinya',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'peran_hak_akses',
                        'label' => 'Peran & Hak Akses',
                        'route' => 'roles.index',
                        'icon' => 'shield-lock',
                        'active_pattern' => 'roles.*',
                        'tooltip' => 'Kelola peran (role) yang tersedia di sistem',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get menu configuration for Asesor role.
     */
    private function getAsesorMenu(): array
    {
        return [
            [
                'label' => 'Profil',
                'items' => [
                    [
                        'key' => 'profil_asesor',
                        'label' => 'Profil Asesor',
                        'route' => 'asesor.profile',
                        'icon' => 'users',
                        'active_pattern' => 'asesor.profile',
                        'tooltip' => 'Kelola data profil dan informasi asesor Anda',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Tugas Akreditasi',
                'items' => [
                    [
                        'key' => 'daftar_tugas',
                        'label' => 'Daftar Tugas',
                        'route' => 'asesor.akreditasi',
                        'icon' => 'shield-lock',
                        'active_pattern' => 'asesor.akreditasi*',
                        'tooltip' => 'Lihat dan kelola tugas penilaian akreditasi Anda',
                        'show_progress' => false,
                        'show_badge' => true,
                    ],
                ],
            ],
            [
                'label' => 'Dokumen',
                'items' => $this->buildDokumenItems('asesor'),
            ],
        ];
    }

    /**
     * Build the dynamic Dokumen menu items for a non-admin role scope.
     *
     * Items are sourced from active DocumentCategory rows whose visibility
     * matches the role scope. A trailing "Semua Dokumen" entry is always
     * appended so users can browse the unfiltered list.
     *
     * @param  string  $roleScope  'pesantren' | 'asesor'
     * @return array<int, array<string, mixed>>
     */
    private function buildDokumenItems(string $roleScope): array
    {
        $categories = $this->getDocumentCategoriesForRole($roleScope);

        $items = $categories->map(function (DocumentCategory $cat) use ($roleScope) {
            $key = 'dokumen_' . $roleScope . '_' . $cat->slug;

            return [
                'key' => $key,
                'label' => $cat->name,
                'route' => 'documents.index',
                'route_params' => ['doc' => $cat->slug],
                'icon' => $cat->icon ?: 'document-stack',
                'active_pattern' => 'documents.index.' . $cat->slug,
                'tooltip' => $cat->description ?: ('Lihat dokumen ' . $cat->name),
                'show_progress' => false,
                'show_badge' => false,
            ];
        })->all();

        $items[] = [
            'key' => 'semua_dokumen_' . $roleScope,
            'label' => 'Semua Dokumen',
            'route' => 'documents.index',
            'route_params' => ['doc' => 'all'],
            'icon' => 'document',
            'active_pattern' => 'documents.index.all',
            'tooltip' => 'Lihat seluruh dokumen yang tersedia untuk Anda',
            'show_progress' => false,
            'show_badge' => false,
        ];

        return $items;
    }

    /**
     * Lazily load and memoise the active DocumentCategory rows visible to a
     * given role scope. Failures (e.g. table missing during initial migrate)
     * degrade gracefully to an empty collection.
     *
     * @return Collection<int, DocumentCategory>
     */
    private function getDocumentCategoriesForRole(string $roleScope): Collection
    {
        if (isset($this->documentCategoriesByRole[$roleScope])) {
            return $this->documentCategoriesByRole[$roleScope];
        }

        try {
            $query = DocumentCategory::query()->active()->ordered();

            $query = match ($roleScope) {
                'pesantren' => $query->visibleToPesantren(),
                'asesor' => $query->visibleToAsesor(),
                default => $query->where('id', 0),
            };

            $categories = $query->get();
        } catch (\Throwable $e) {
            $categories = collect();
        }

        return $this->documentCategoriesByRole[$roleScope] = $categories;
    }

    /**
     * Get all tooltip texts indexed by menu key.
     */
    private function getAllTooltips(): array
    {
        return [
            // Pesantren tooltips
            'profil_pesantren' => 'Kelola data profil dan informasi dasar pesantren Anda',
            'ipm' => 'Kelola dokumen Instrumen Penilaian Mutu pesantren',
            'data_sdm' => 'Kelola data sumber daya manusia pesantren',
            'edpm' => 'Isi Evaluasi Diri Penjaminan Mutu pesantren',
            'pengajuan' => 'Ajukan dan pantau status akreditasi pesantren',

            // Admin tooltips
            'dashboard' => 'Lihat ringkasan dan statistik sistem akreditasi',
            'akreditasi_admin' => 'Kelola dan review pengajuan akreditasi pesantren',
            'daftar_pesantren' => 'Lihat dan kelola data seluruh pesantren terdaftar',
            'banding' => 'Kelola pengajuan banding dari pesantren',
            'daftar_asesor' => 'Lihat dan kelola data asesor akreditasi',
            'master_edpm' => 'Kelola komponen dan butir Evaluasi Diri Penjaminan Mutu',
            'master_kategori_dokumen' => 'Atur kategori dan visibilitas dokumen per role',
            'master_dokumen' => 'Kelola katalog dokumen wajib yang diunggah pesantren dan asesor',
            'akun_pengguna' => 'Kelola akun admin, asesor, dan pesantren beserta status aktivasinya',
            'peran_hak_akses' => 'Kelola peran (role) yang tersedia di sistem',
            'failed_notifications' => 'Pantau dan kelola notifikasi yang gagal terkirim',
            'trash' => 'Kelola data akreditasi terhapus dengan masa retensi sebelum dihapus permanen',

            // Asesor tooltips
            'profil_asesor' => 'Kelola data profil dan informasi asesor Anda',
            'daftar_tugas' => 'Lihat dan kelola tugas penilaian akreditasi Anda',
        ];
    }
}
