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
     * @return array<int, array{label: string, items: array}>
     */
    public function getMenuForRole(int $roleId): array
    {
        return match ($roleId) {
            self::ROLE_SUPER_ADMIN => $this->getSuperAdminMenu(),
            self::ROLE_ADMIN => $this->getAdminMenu(),
            self::ROLE_ASESOR => $this->getAsesorMenu(),
            self::ROLE_PESANTREN => $this->getPesantrenMenu(),
            default => [],
        };
    }

    /**
     * Super admin sees the admin operational menu plus exclusive system
     * management entries for role catalog and permission matrix.
     */
    private function getSuperAdminMenu(): array
    {
        $menu = $this->getAdminMenu();

        foreach ($menu as $idx => $group) {
            if (($group['label'] ?? null) === 'Administrasi') {
                $menu[$idx]['label'] = 'Manajemen Sistem';
                $menu[$idx]['items'] = [
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
                        'key' => 'role_sistem',
                        'label' => 'Role Sistem',
                        'route' => 'admin.roles.index',
                        'icon' => 'lock-2',
                        'active_pattern' => 'admin.roles.*',
                        'tooltip' => 'Kelola katalog role sistem yang menjadi dasar hak akses pengguna',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'hak_akses',
                        'label' => 'Hak Akses',
                        'route' => 'admin.role-permission.index',
                        'icon' => 'shield-tick',
                        'active_pattern' => 'admin.role-permission.*',
                        'tooltip' => 'Kelola matriks permission tiap role secara dinamis',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
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
                        'label' => 'Arsip Akreditasi',
                        'route' => 'admin.trash',
                        'icon' => 'trash',
                        'active_pattern' => 'admin.trash',
                        'tooltip' => 'Kelola data akreditasi terhapus dengan masa retensi sebelum dihapus permanen',
                        'show_progress' => false,
                        'show_badge' => true,
                    ],
                ];
                break;
            }
        }

        return $menu;
    }

    /**
     * Returns tooltip text for a given menu item key.
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
                'label' => 'Monitoring',
                'items' => [
                    [
                        'key' => 'dashboard_pesantren',
                        'label' => 'Dashboard',
                        'route' => 'dashboard',
                        'icon' => 'category',
                        'active_pattern' => 'dashboard',
                        'tooltip' => 'Lihat ringkasan status dan aktivitas akreditasi pesantren',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Persiapan Akreditasi',
                'items' => [
                    [
                        'key' => 'profil_pesantren',
                        'label' => 'Profil Pesantren',
                        'route' => 'pesantren.profile',
                        'icon' => 'teacher',
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
                        'tooltip' => 'Kelola Indikator Pemenuhan Mutlak dan dokumen pendukung pesantren',
                        'show_progress' => true,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'data_sdm',
                        'label' => 'Data SDM',
                        'route' => 'pesantren.sdm',
                        'icon' => 'people',
                        'active_pattern' => 'pesantren.sdm',
                        'tooltip' => 'Kelola data sumber daya manusia pesantren',
                        'show_progress' => true,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'edpm_ipr',
                        'label' => 'EDPM/IPR',
                        'route' => 'pesantren.edpm',
                        'icon' => 'document',
                        'active_pattern' => 'pesantren.edpm',
                        'tooltip' => 'Isi Evaluasi Diri Penjaminan Mutu dan Indikator Pemenuhan Relatif',
                        'show_progress' => true,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Akreditasi',
                'items' => [
                    [
                        'key' => 'pusat_akreditasi',
                        'label' => 'Pusat Akreditasi',
                        'route' => 'pesantren.akreditasi',
                        'icon' => 'lock-2',
                        'active_pattern' => 'pesantren.akreditasi*',
                        'tooltip' => 'Ajukan, pantau perbaikan, kartu kendali, dan hasil akreditasi dalam satu tempat',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Panduan',
                'items' => $this->buildDokumenItems('pesantren', ['kartu_kendali']),
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
                        'icon' => 'category',
                        'active_pattern' => 'dashboard',
                        'tooltip' => 'Lihat ringkasan dan statistik sistem akreditasi',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'akreditasi_admin',
                        'label' => 'Review Akreditasi',
                        'route' => 'admin.akreditasi',
                        'icon' => 'shield-tick',
                        'active_pattern' => 'admin.akreditasi*',
                        'tooltip' => 'Review dan kelola pengajuan akreditasi pesantren',
                        'show_progress' => false,
                        'show_badge' => true,
                    ],
                ],
            ],
            [
                'label' => 'Operasional Akreditasi',
                'items' => [
                    [
                        'key' => 'daftar_pesantren',
                        'label' => 'Daftar Pesantren',
                        'route' => 'admin.pesantren.index',
                        'icon' => 'people',
                        'active_pattern' => 'admin.pesantren.*',
                        'tooltip' => 'Lihat dan kelola data seluruh pesantren terdaftar',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'daftar_asesor',
                        'label' => 'Daftar Asesor',
                        'route' => 'admin.asesor.index',
                        'icon' => 'profile-user',
                        'active_pattern' => 'admin.asesor.*',
                        'tooltip' => 'Lihat dan kelola data asesor akreditasi',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'banding',
                        'label' => 'Banding',
                        'route' => 'admin.banding',
                        'icon' => 'lock-2',
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
                        'label' => 'Komponen EDPM/IPR',
                        'route' => 'admin.master-edpm',
                        'icon' => 'data',
                        'active_pattern' => 'admin.master-edpm.*',
                        'tooltip' => 'Kelola komponen dan butir Evaluasi Diri Penjaminan Mutu',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'master_kategori_dokumen',
                        'label' => 'Kategori Dokumen',
                        'route' => 'admin.master-kategori-dokumen.index',
                        'icon' => 'abstract-26',
                        'active_pattern' => 'admin.master-kategori-dokumen.*',
                        'tooltip' => 'Atur kategori dan visibilitas dokumen per role',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'master_dokumen',
                        'label' => 'Dokumen Wajib',
                        'route' => 'admin.master-dokumen.index',
                        'icon' => 'files-tablet',
                        'active_pattern' => 'admin.master-dokumen.*',
                        'tooltip' => 'Kelola katalog dokumen wajib yang diunggah pesantren dan asesor',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Administrasi',
                'items' => [
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
                        'label' => 'Arsip Akreditasi',
                        'route' => 'admin.trash',
                        'icon' => 'trash',
                        'active_pattern' => 'admin.trash',
                        'tooltip' => 'Kelola data akreditasi terhapus dengan masa retensi sebelum dihapus permanen',
                        'show_progress' => false,
                        'show_badge' => true,
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
                        'icon' => 'people',
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
                        'icon' => 'lock-2',
                        'active_pattern' => 'asesor.akreditasi*',
                        'tooltip' => 'Lihat dan kelola tugas penilaian akreditasi Anda',
                        'active_query_absent' => ['focus', 'statusFilter'],
                        'show_progress' => false,
                        'show_badge' => true,
                    ],
                    [
                        'key' => 'review_berkas',
                        'label' => 'Review Berkas',
                        'route' => 'asesor.akreditasi',
                        'route_query' => ['statusFilter' => 'belum', 'focus' => 'review'],
                        'icon' => 'eye',
                        'active_pattern' => 'asesor.akreditasi*',
                        'active_query' => ['statusFilter' => 'belum', 'focus' => 'review'],
                        'tooltip' => 'Review profil, IPM, SDM, EDPM/IPR, dan catatan berkas pesantren',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'jadwal_visitasi',
                        'label' => 'Atur Jadwal Visitasi',
                        'route' => 'asesor.akreditasi',
                        'route_query' => ['statusFilter' => 'belum', 'focus' => 'jadwal'],
                        'icon' => 'calendar-tick',
                        'active_pattern' => 'asesor.akreditasi*',
                        'active_query' => ['statusFilter' => 'belum', 'focus' => 'jadwal'],
                        'tooltip' => 'Atur jadwal visitasi dan catatan awal untuk pesantren yang ditugaskan',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'input_nilai',
                        'label' => 'Input Nilai Visitasi',
                        'route' => 'asesor.akreditasi',
                        'route_query' => ['statusFilter' => 'penilaian', 'focus' => 'nilai'],
                        'icon' => 'pencil',
                        'active_pattern' => 'asesor.akreditasi*',
                        'active_query' => ['statusFilter' => 'penilaian', 'focus' => 'nilai'],
                        'tooltip' => 'Lanjutkan pengisian instrumen dan nilai hasil visitasi',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'laporan_visitasi_workflow',
                        'label' => 'Laporan Visitasi',
                        'route' => 'asesor.akreditasi',
                        'route_query' => ['statusFilter' => 'penilaian', 'focus' => 'laporan_visitasi'],
                        'icon' => 'file-up',
                        'active_pattern' => 'asesor.akreditasi*',
                        'active_query' => ['statusFilter' => 'penilaian', 'focus' => 'laporan_visitasi'],
                        'tooltip' => 'Unggah laporan visitasi individu dan kelompok setelah penilaian selesai',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Dokumen',
                'items' => $this->buildDokumenItems('asesor', ['visitasi']),
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
     * @param  array<int, string>  $excludeSlugs
     * @return array<int, array<string, mixed>>
     */
    private function buildDokumenItems(string $roleScope, array $excludeSlugs = []): array
    {
        $categories = $this->getDocumentCategoriesForRole($roleScope)
            ->reject(fn (DocumentCategory $cat) => in_array($cat->slug, $excludeSlugs, true));

        $items = $categories->map(function (DocumentCategory $cat) use ($roleScope) {
            $key = 'dokumen_'.$roleScope.'_'.$cat->slug;

            return [
                'key' => $key,
                'label' => $cat->slug === 'iapm' ? 'Panduan IAPM' : $cat->name,
                'route' => 'documents.index',
                'route_params' => ['doc' => $cat->slug],
                'icon' => $cat->icon ?: 'files-tablet',
                'active_pattern' => 'documents.index.'.$cat->slug,
                'tooltip' => $cat->slug === 'iapm' ? 'Baca panduan IAPM yang dibagikan admin' : ($cat->description ?: ('Lihat dokumen '.$cat->name)),
                'show_progress' => false,
                'show_badge' => false,
            ];
        })->all();

        if ($roleScope !== 'pesantren') {
            $items[] = [
            'key' => 'semua_dokumen_'.$roleScope,
            'label' => 'Semua Dokumen',
            'route' => 'documents.index',
            'route_params' => ['doc' => 'all'],
            'icon' => 'document',
            'active_pattern' => 'documents.index.all',
            'tooltip' => 'Lihat seluruh dokumen yang tersedia untuk Anda',
            'show_progress' => false,
            'show_badge' => false,
            ];
        }

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
            'dashboard_pesantren' => 'Lihat ringkasan status dan aktivitas akreditasi pesantren',
            'profil_pesantren' => 'Kelola data profil dan informasi dasar pesantren Anda',
            'ipm' => 'Kelola Indikator Pemenuhan Mutlak dan dokumen pendukung pesantren',
            'data_sdm' => 'Kelola data sumber daya manusia pesantren',
            'edpm_ipr' => 'Isi Evaluasi Diri Penjaminan Mutu dan Indikator Pemenuhan Relatif',
            'pusat_akreditasi' => 'Ajukan, pantau perbaikan, kartu kendali, dan hasil akreditasi dalam satu tempat',

            // Admin tooltips
            'dashboard' => 'Lihat ringkasan dan statistik sistem akreditasi',
            'akreditasi_admin' => 'Review dan kelola pengajuan akreditasi pesantren',
            'daftar_pesantren' => 'Lihat dan kelola data seluruh pesantren terdaftar',
            'banding' => 'Kelola pengajuan banding dari pesantren',
            'daftar_asesor' => 'Lihat dan kelola data asesor akreditasi',
            'master_edpm' => 'Kelola komponen dan butir Evaluasi Diri Penjaminan Mutu',
            'master_kategori_dokumen' => 'Atur kategori dan visibilitas dokumen per role',
            'master_dokumen' => 'Kelola katalog dokumen wajib yang diunggah pesantren dan asesor',
            'akun_pengguna' => 'Kelola akun admin, asesor, dan pesantren beserta status aktivasinya',
            'role_sistem' => 'Kelola katalog role sistem yang menjadi dasar hak akses pengguna',
            'hak_akses' => 'Kelola matriks permission tiap role secara dinamis',
            'failed_notifications' => 'Pantau dan kelola notifikasi yang gagal terkirim',
            'trash' => 'Kelola data akreditasi terhapus dengan masa retensi sebelum dihapus permanen',

            // Asesor tooltips
            'profil_asesor' => 'Kelola data profil dan informasi asesor Anda',
            'daftar_tugas' => 'Lihat dan kelola tugas penilaian akreditasi Anda',
            'review_berkas' => 'Review profil, IPM, SDM, EDPM/IPR, dan catatan berkas pesantren',
            'jadwal_visitasi' => 'Atur jadwal visitasi dan catatan awal untuk pesantren yang ditugaskan',
            'input_nilai' => 'Lanjutkan pengisian instrumen dan nilai hasil visitasi',
            'laporan_visitasi_workflow' => 'Unggah laporan visitasi individu dan kelompok setelah penilaian selesai',
        ];
    }
}
