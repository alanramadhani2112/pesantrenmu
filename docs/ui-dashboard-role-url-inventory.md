<!-- markdownlint-disable MD013 MD032 MD060 -->

# UI Dashboard Role URL Inventory

Sumber: `routes/web.php` dan `app/Services/SidebarMenuService.php`.

Tujuan dokumen ini: daftar awal URL halaman yang muncul dari dashboard/sidebar per role sebelum audit/refactor UI lanjutan.

## Catatan scope

- Daftar ini fokus ke halaman UI (`GET`) yang dapat dibuka dari dashboard/sidebar atau terkait langsung dengan halaman tersebut.
- Route aksi `POST`, `PUT`, `DELETE`, export, download, modal action, dan internal API tidak masuk sebagai halaman visual utama.
- `super admin` memakai menu admin operasional + grup `Manajemen Sistem` khusus super admin.
- `admin` memakai menu admin operasional + grup `Administrasi` admin.
- URL detail yang memakai `{uuid}` atau `{id}` perlu data nyata saat browser QA.
- Dokumen/panduan dinamis memakai slug kategori dokumen aktif dari database.

## Super Admin

| Section | Menu/Page | URL | Route name | Page type | Evidence |
|---|---|---|---|---|---|
| Monitoring | Dashboard | `/dashboard` | `dashboard` | Dashboard | `SidebarMenuService::getSuperAdminMenu()` + `getAdminMenu()` |
| Monitoring | Review Akreditasi | `/admin/akreditasi` | `admin.akreditasi` | List/workflow | `SidebarMenuService` line route `admin.akreditasi` |
| Monitoring | Detail Akreditasi | `/admin/akreditasi/{uuid}` | `admin.akreditasi-detail` | Detail/workflow | `routes/web.php` |
| Operasional Akreditasi | Daftar Pesantren | `/admin/pesantren` | `admin.pesantren.index` | List | `SidebarMenuService` |
| Operasional Akreditasi | Detail Pesantren | `/admin/pesantren/{uuid}` | `admin.pesantren.detail` | Detail | `routes/web.php` |
| Operasional Akreditasi | Daftar Asesor | `/admin/asesor` | `admin.asesor.index` | List | `SidebarMenuService` |
| Operasional Akreditasi | Detail Asesor | `/admin/asesor/{uuid}` | `admin.asesor.detail` | Detail | `routes/web.php` |
| Operasional Akreditasi | Banding | `/admin/banding` | `admin.banding` | List/workflow | `SidebarMenuService` |
| Operasional Akreditasi | Detail Banding | `/admin/banding/{id}` | `admin.banding-detail` | Detail/workflow | `routes/web.php` |
| Master Data | Komponen EDPM/IPR | `/admin/master-edpm` | `admin.master-edpm` | Master/list/form | `SidebarMenuService` |
| Master Data | Kategori Dokumen | `/admin/master-kategori-dokumen` | `admin.master-kategori-dokumen.index` | Master/list/modal | `SidebarMenuService` |
| Master Data | Dokumen Wajib | `/admin/master-dokumen` | `admin.master-dokumen.index` | Master/list/modal | `SidebarMenuService` |
| Manajemen Sistem | Akun Pengguna | `/accounts` | `accounts.index` | List/modal | `SidebarMenuService::getSuperAdminMenu()` |
| Manajemen Sistem | Role Sistem | `/admin/roles` | `admin.roles.index` | List/modal | `SidebarMenuService::getSuperAdminMenu()` |
| Manajemen Sistem | Hak Akses | `/admin/master-role-permission` | `admin.role-permission.index` | Matrix/form | `SidebarMenuService::getSuperAdminMenu()` |
| Manajemen Sistem | Notifikasi Gagal | `/admin/failed-notifications` | `admin.failed-notifications` | Ops/list | `SidebarMenuService::getSuperAdminMenu()` |
| Manajemen Sistem | Arsip Akreditasi | `/admin/trash` | `admin.trash` | Ops/list | `SidebarMenuService::getSuperAdminMenu()` |
| Profile | Pengaturan Profil | `/profile` | `profile.edit` | Settings/form | `routes/web.php` |
| Panduan | Panduan Super Admin | `/panduan-superadmin` | `panduan.superadmin` | Guide/support | `routes/web.php` |
| Dokumen | Semua/Panduan Dokumen | `/documents/{doc?}` | `documents.index` | Documents/list | `routes/web.php` |

## Admin

| Section | Menu/Page | URL | Route name | Page type | Evidence |
|---|---|---|---|---|---|
| Monitoring | Dashboard | `/dashboard` | `dashboard` | Dashboard | `SidebarMenuService::getAdminMenu()` |
| Monitoring | Review Akreditasi | `/admin/akreditasi` | `admin.akreditasi` | List/workflow | `SidebarMenuService` |
| Monitoring | Detail Akreditasi | `/admin/akreditasi/{uuid}` | `admin.akreditasi-detail` | Detail/workflow | `routes/web.php` |
| Operasional Akreditasi | Daftar Pesantren | `/admin/pesantren` | `admin.pesantren.index` | List | `SidebarMenuService` |
| Operasional Akreditasi | Detail Pesantren | `/admin/pesantren/{uuid}` | `admin.pesantren.detail` | Detail | `routes/web.php` |
| Operasional Akreditasi | Daftar Asesor | `/admin/asesor` | `admin.asesor.index` | List | `SidebarMenuService` |
| Operasional Akreditasi | Detail Asesor | `/admin/asesor/{uuid}` | `admin.asesor.detail` | Detail | `routes/web.php` |
| Operasional Akreditasi | Banding | `/admin/banding` | `admin.banding` | List/workflow | `SidebarMenuService` |
| Operasional Akreditasi | Detail Banding | `/admin/banding/{id}` | `admin.banding-detail` | Detail/workflow | `routes/web.php` |
| Master Data | Komponen EDPM/IPR | `/admin/master-edpm` | `admin.master-edpm` | Master/list/form | `SidebarMenuService` |
| Master Data | Kategori Dokumen | `/admin/master-kategori-dokumen` | `admin.master-kategori-dokumen.index` | Master/list/modal | `SidebarMenuService` |
| Master Data | Dokumen Wajib | `/admin/master-dokumen` | `admin.master-dokumen.index` | Master/list/modal | `SidebarMenuService` |
| Administrasi | Akun Pengguna | `/accounts` | `accounts.index` | List/modal | `SidebarMenuService::getAdminMenu()` |
| Administrasi | Notifikasi Gagal | `/admin/failed-notifications` | `admin.failed-notifications` | Ops/list | `SidebarMenuService::getAdminMenu()` |
| Administrasi | Arsip Akreditasi | `/admin/trash` | `admin.trash` | Ops/list | `SidebarMenuService::getAdminMenu()` |
| Profile | Pengaturan Profil | `/profile` | `profile.edit` | Settings/form | `routes/web.php` |
| Panduan | Panduan Admin | `/panduan-admin` | `panduan.admin` | Guide/support | `routes/web.php` |
| Dokumen | Semua/Panduan Dokumen | `/documents/{doc?}` | `documents.index` | Documents/list | `routes/web.php` |

## Pesantren

| Section | Menu/Page | URL | Route name | Page type | Evidence |
|---|---|---|---|---|---|
| Monitoring | Dashboard | `/dashboard` | `dashboard` | Dashboard | `SidebarMenuService::getPesantrenMenu()` |
| Persiapan Akreditasi | Profil Pesantren | `/pesantren/profile` | `pesantren.profile` | Form/settings | `SidebarMenuService` |
| Persiapan Akreditasi | IPM | `/pesantren/ipm` | `pesantren.ipm` | Form/documents | `SidebarMenuService` |
| Persiapan Akreditasi | Data SDM | `/pesantren/sdm` | `pesantren.sdm` | Form/table | `SidebarMenuService` |
| Persiapan Akreditasi | EDPM/IPR | `/pesantren/edpm` | `pesantren.edpm` | Workflow/form/table | `SidebarMenuService` |
| Akreditasi | Pusat Akreditasi | `/pesantren/akreditasi` | `pesantren.akreditasi` | List/workflow | `SidebarMenuService` |
| Akreditasi | Perbaikan | `/pesantren/akreditasi/perbaikan` | `pesantren.akreditasi.perbaikan` | Filtered workflow | `routes/web.php` |
| Akreditasi | Kartu Kendali | `/pesantren/akreditasi/kartu-kendali` | `pesantren.akreditasi.kartu-kendali` | Filtered workflow | `routes/web.php` |
| Akreditasi | Hasil | `/pesantren/akreditasi/hasil` | `pesantren.akreditasi.hasil` | Filtered workflow | `routes/web.php` |
| Akreditasi | Detail Akreditasi | `/pesantren/akreditasi/{uuid}` | `pesantren.akreditasi-detail` | Detail/workflow | `routes/web.php` |
| Profile | Pengaturan Profil | `/profile` | `profile.edit` | Settings/form | `routes/web.php` |
| Panduan | Panduan Pesantren | `/panduan-pesantren` | `panduan.pesantren` | Guide/support | `routes/web.php` |
| Dokumen | Dokumen Dinamis Pesantren | `/documents/{doc?}` | `documents.index` | Documents/list | `SidebarMenuService::buildDokumenItems('pesantren')` |

## Asesor

| Section | Menu/Page | URL | Route name | Page type | Evidence |
|---|---|---|---|---|---|
| Monitoring | Dashboard | `/dashboard` | `dashboard` | Dashboard | `SidebarMenuService::getAsesorMenu()` |
| Akun Asesor | Profil Asesor | `/asesor/profile` | `asesor.profile` | Profile/form | `SidebarMenuService` |
| Workflow Akreditasi | Tugas Akreditasi | `/asesor/akreditasi` | `asesor.akreditasi` | List/workflow | `SidebarMenuService` |
| Workflow Akreditasi | Review Berkas | `/asesor/akreditasi/review-berkas` | `asesor.akreditasi.review` | Filtered workflow | `routes/web.php` |
| Workflow Akreditasi | Jadwal Visitasi | `/asesor/akreditasi/jadwal-visitasi` | `asesor.akreditasi.jadwal` | Filtered workflow | `routes/web.php` |
| Workflow Akreditasi | Input Nilai | `/asesor/akreditasi/input-nilai` | `asesor.akreditasi.nilai` | Filtered workflow/scoring | `routes/web.php` |
| Workflow Akreditasi | Laporan Visitasi | `/asesor/akreditasi/laporan-visitasi` | `asesor.akreditasi.laporan-visitasi` | Filtered workflow/report | `routes/web.php` |
| Workflow Akreditasi | Detail Akreditasi | `/asesor/akreditasi/{uuid}` | `asesor.akreditasi-detail` | Detail/workflow/scoring | `routes/web.php` |
| Profile | Pengaturan Profil | `/profile` | `profile.edit` | Settings/form | `routes/web.php` |
| Panduan | Panduan Asesor | `/panduan-asesor` | `panduan.asesor` | Guide/support | `routes/web.php` |
| Dokumen | Dokumen Dinamis Asesor | `/documents/{doc?}` | `documents.index` | Documents/list | `SidebarMenuService::buildDokumenItems('asesor')` |

## URL umum di luar role dashboard

| Page | URL | Route name | Catatan |
|---|---|---|---|
| Landing publik | `/` | none | Sebelum login |
| Login | `/login` | `login` | Auth page |
| Lupa Password | `/forgot-password` | `password.request` | Auth page |
| Redirect Panduan | `/panduan` | `panduan.index` | Mengarahkan ke panduan sesuai role |
| Lihat dokumen | `/documents/{document}/view` | `documents.view` | View dokumen, bukan list utama |
| Download dokumen | `/documents/{document}/download` | `documents.download` | Download, bukan halaman UI utama |
| Secure asesor docs | `/secure/asesor-docs/{asesorId}/{field}` | `secure.asesor-docs` | Download file privat |

## Prioritas audit UI berikutnya

1. Dashboard tiap role: `/dashboard` dengan role berbeda.
2. Detail akreditasi lintas role: `/admin/akreditasi/{uuid}`, `/pesantren/akreditasi/{uuid}`, `/asesor/akreditasi/{uuid}`.
3. Workflow list: `/admin/akreditasi`, `/pesantren/akreditasi`, `/asesor/akreditasi`.
4. Persiapan pesantren: `/pesantren/profile`, `/pesantren/ipm`, `/pesantren/sdm`, `/pesantren/edpm`.
5. Master/admin ops: akun, role, permission, master dokumen, failed notifications, trash.
