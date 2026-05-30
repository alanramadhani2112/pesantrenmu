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
                        'show_progress' => true,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'role_sistem',
                        'label' => 'Role Sistem',
                        'route' => 'roles.index',
                        'icon' => 'shield-lock',
                        'active_pattern' => 'roles.*',
                        'tooltip' => 'Kelola katalog role sistem yang menjadi dasar hak akses pengguna',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'hak_akses',
                        'label' => 'Hak Akses',
                        'route' => 'admin.master-role-permission',
                        'icon' => 'shield-tick',
                        'active_pattern' => 'admin.master-role-permission',
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
                'label' => 'Persiapan Akreditasi',
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
                    [
                        'key' => 'edpm_ipr',
                        'label' => 'EDPM/IPR',
                        'route' => 'pesantren.edpm',
                        'icon' => 'paper',
                        'active_pattern' => 'pesantren.edpm',
                        'tooltip' => 'Isi Evaluasi Diri Penjaminan Mutu dan Indikator Pemenuhan Relatif',
                        'show_progress' => true,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Pengajuan',
                'items' => [
                    [
                        'key' => 'pengajuan',
                        'label' => 'Pengajuan Akreditasi',
                        'route' => 'pesantren.akreditasi',
                        'icon' => 'shield-lock',
                        'active_pattern' => 'pesantren.akreditasi*',
                        'tooltip' => 'Ajukan dan pantau status akreditasi pesantren',
                        'active_query_absent' => ['focus', 'statusFilter', 'tahapanFilter'],
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'status_perbaikan',
                        'label' => 'Status Perbaikan',
                        'route' => 'pesantren.akreditasi',
                        'route_query' => ['focus' => 'perbaikan'],
                        'icon' => 'messages',
                        'active_pattern' => 'pesantren.akreditasi*',
                        'active_query' => ['focus' => 'perbaikan'],
                        'tooltip' => 'Pantau catatan penolakan dan tindak lanjut perbaikan berkas',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Visitasi',
                'items' => [
                    [
                        'key' => 'kartu_kendali_visitasi',
                        'label' => 'Kartu Kendali',
                        'route' => 'pesantren.akreditasi',
                        'route_query' => ['focus' => 'kartu_kendali'],
                        'icon' => 'clipboard-check',
                        'active_pattern' => 'pesantren.akreditasi*',
                        'active_query' => ['focus' => 'kartu_kendali'],
                        'tooltip' => 'Unggah dan pantau kartu kendali setelah visitasi selesai',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Hasil Akreditasi',
                'items' => [
                    [
                        'key' => 'hasil_akhir',
                        'label' => 'Hasil Akhir',
                        'route' => 'pesantren.akreditasi',
                        'route_query' => ['focus' => 'hasil'],
                        'icon' => 'chart-line-up',
                        'active_pattern' => 'pesantren.akreditasi*',
                        'active_query' => ['focus' => 'hasil'],
                        'tooltip' => 'Lihat nilai akhir, rekomendasi, sertifikat, dan status banding dalam satu tempat',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                ],
            ],
            [
                'label' => 'Dokumen',
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
                'label' => 'Operasional Akreditasi',
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
                        'label' => 'Komponen EDPM/IPR',
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
                        'active_query_absent' => ['focus', 'statusFilter'],
                        'show_progress' => false,
                        'show_badge' => true,
                    ],
                    [
                        'key' => 'review_berkas',
                        'label' => 'Review Berkas',
                        'route' => 'asesor.akreditasi',
                        'route_query' => ['focus' => 'review'],
                        'icon' => 'eye',
                        'active_pattern' => 'asesor.akreditasi*',
                        'active_query' => ['focus' => 'review'],
                        'tooltip' => 'Review profil, IPM, SDM, EDPM/IPR, dan catatan berkas pesantren',
                        'show_progress' => false,
                        'show_badge' => false,
                    ],
                    [
                        'key' => 'jadwal_visitasi',
                        'label' => 'Penjadwalan Visitasi',
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
                        'label' => 'Input Nilai',
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
                        'icon' => 'document-up',
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
                'label' => $cat->name,
                'route' => 'documents.index',
                'route_params' => ['doc' => $cat->slug],
                'icon' => $cat->icon ?: 'document-stack',
                'active_pattern' => 'documents.index.'.$cat->slug,
                'tooltip' => $cat->description ?: ('Lihat dokumen '.$cat->name),
                'show_progress' => false,
                'show_badge' => false,
            ];
        })->all();

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
            'edpm_ipr' => 'Isi Evaluasi Diri Penjaminan Mutu dan Indikator Pemenuhan Relatif',
            'pengajuan' => 'Ajukan dan pantau status akreditasi pesantren',
            'status_perbaikan' => 'Pantau catatan penolakan dan tindak lanjut perbaikan berkas',
            'kartu_kendali_visitasi' => 'Unggah dan pantau kartu kendali setelah visitasi selesai',
            'hasil_akhir' => 'Lihat nilai akhir, rekomendasi, sertifikat, dan status banding dalam satu tempat',

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
