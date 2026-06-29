# Backend Role-Module Audit Plan — 8 Juni 2026

Status: living document  
Cakupan: backend Laravel setelah migrasi legacy reactive layer ke full Blade/controller  
Koordinasi: disinkronkan dengan `docs/audit-frontend-2025-06-08.md`

Dokumen ini menjadi rencana kerja backend sebelum masuk perbaikan frontend penuh. Fokusnya adalah memastikan setiap role, modul, route, controller, policy, service, dan test backend aman setelah legacy reactive layer dihapus.

---

## 1. Tujuan

1. Memastikan proses bisnis akreditasi tidak berubah setelah migrasi legacy reactive layer ke Blade/controller.
2. Memastikan semua role hanya bisa mengakses data dan aksi yang sesuai.
3. Menutup gap backend per role dan per modul sebelum perbaikan frontend difinalkan.
4. Menyediakan status sinkronisasi agar agent backend dan agent frontend tidak mengerjakan area yang saling bertabrakan.

---

## 2. Definisi Tingkat Kritis

| Level | Nama | Definisi | Contoh |
|---|---|---|---|
| P0 | Critical | Data bocor, aksi lintas role, workflow bisnis berhenti, atau state transition salah | Asesor bisa melihat akreditasi asesor lain, admin tidak bisa proses banding, pesantren bisa upload ke akreditasi orang lain |
| P1 | High | Backend action valid tapi guard/test kurang kuat, atau modul penting belum punya regression HTTP coverage | Controller POST belum dites setelah Blade migration, super admin tidak menerima notifikasi operasional |
| P2 | Medium | Inkonsistensi operasional, edge case, atau coverage kurang di modul non-kritis | Badge/count tidak sinkron, redirect context tidak eksplisit |
| P3 | Low | Dokumentasi, hygiene, naming, atau cleanup test yang tidak mempengaruhi flow | Komentar lama legacy reactive layer di docs, test frontend contract yang belum dipilah |

---

## 3. Status Backend Saat Ini

Ringkasan hasil audit awal:

| Area | Status | Catatan |
|---|---|---|
| State machine akreditasi | Aman | Flow tetap `6 -> 5 -> 4 -> 3 -> 2 -> 1 -> 0`, dengan `-1` ditolak dan `-2` banding |
| Role middleware | Aman | Super admin bypass role gate; admin/asesor/pesantren dipisah route group |
| Permission gate | Aman | Super admin bypass permission; role lain pakai pivot permission |
| Policy tenant boundary | Aman | Akreditasi, banding, pesantren, asesor, dokumen punya policy utama |
| Asesor assignment scope | Sudah diperbaiki | Search tugas asesor dan endpoint catatan sudah diproteksi |
| Auth registration test | Sudah diperbaiki | Test sekarang seed role pesantren |
| Frontend contract | Belum masuk scope backend | Ditangani di dokumen frontend |

Verifikasi terakhir:

```text
171 passed, 1 skipped, 7281 assertions
```

Batch yang sudah mencakup: role/permission, tenant policy, dashboard, state machine, akreditasi workflow, banding, rejection, post-visitasi document, reassign asesor, auth registration/password reset, dan legacy cleanup.

---

## 4. Matriks Audit Per Role dan Modul

### 4.1 Super Admin

| Modul | Backend Surface | Risiko | Prioritas | Status |
|---|---|---:|---|---|
| Dashboard | `DashboardController`, stats global | Rendah | P2 | Perlu smoke HTTP per role |
| Role Management | `RoleController`, `RolePermissionController` | Hak akses sistem salah | P1 | Perlu regression HTTP CRUD |
| Account Management | `AccountController`, user service | User role/status salah | P1 | Perlu regression HTTP CRUD/toggle |
| Akreditasi Admin | `Admin\AkreditasiController`, `Admin\AkreditasiDetailController` | State transition salah | P0 | Service test kuat, perlu tambah HTTP POST coverage |
| Banding | `BandingController`, `BandingDetailController`, `BandingService` | Keputusan banding salah | P0 | Service test kuat, perlu HTTP POST coverage dan sinkron dengan frontend `legacy client binding` fix |
| Pesantren Management | `PesantrenController`, `PesantrenService` | Manual lock mengganggu flow | P1 | Rekomendasi revisi lock policy, lihat Section 6 |
| Asesor Management | `AsesorController`, `AsesorService` | Toggle/status/export salah | P1 | Perlu HTTP coverage |
| Master EDPM/Dokumen/Kategori | Master controllers/services | Master data rusak | P1 | Perlu CRUD regression |
| Trash | `TrashController`, `TrashService` | Restore/force delete salah | P1 | Sudah banyak test, perlu HTTP endpoint check |
| Failed Notifications | `FailedNotificationController` | Retry/dismiss salah | P2 | Perlu endpoint coverage |

Catatan super admin:
- Akses backend utama aman.
- Perlu perbaikan operasional: beberapa notifikasi masih mengirim ke `role_id = 1` saja. Jika super admin harus menerima notifikasi admin, ubah query penerima menjadi admin area users.

#### 4.1.1 Audit Scope Super Admin - 8 Juni 2026

Audit backend Super Admin sudah dicek dari route, middleware, Gate, policy, sidebar menu, role/permission seeder, controller role management, account management, dashboard, secure file route, notification recipient, dan test terarah. Secara bisnis, Super Admin bukan tahap workflow akreditasi tersendiri; Super Admin adalah admin-area bypass/governance role yang bisa mengakses seluruh modul Admin plus manajemen role dan hak akses.

Verifikasi yang dijalankan:

```text
php artisan test tests/Feature/RoleMiddlewareTest.php tests/Feature/PermissionMiddlewareTest.php tests/Unit/PermissionSystemTest.php tests/Unit/SidebarMenuServiceTest.php tests/Unit/Policies/TenantPolicyTest.php tests/Feature/AdminDashboardTest.php tests/Feature/Trash/TrashAuthorizationTest.php tests/Feature/DocumentServiceTest.php tests/Feature/Property/FailedNotificationPropertyTest.php tests/Feature/BandingLifecycleTest.php tests/Feature/AkreditasiWorkflow/FullHappyPathTest.php --stop-on-failure
294 passed, 2209 assertions
```

Ringkasan aman:

| Area | Status | Catatan |
|---|---|---|
| Role middleware bypass | Aman | `EnsureUserHasRole` memberi bypass untuk `isSuperAdmin()`, sehingga Super Admin bisa masuk route `role:admin` |
| Permission bypass | Aman | `Gate::before` dan `User::hasPermission()` membuat Super Admin lolos semua permission |
| Admin area boundary | Aman | `canAccessAdminArea()` mengakui Admin dan Super Admin; dashboard, controller Admin, dan secure file route memakai pola ini |
| Tenant policy | Aman | Test policy membuktikan Super Admin bypass Pesantren/Akreditasi policy |
| Sidebar menu | Aman | Super Admin mewarisi menu Admin dan mendapat section `Manajemen Sistem` berisi Akun Pengguna, Role Sistem, Hak Akses, Notifikasi Gagal, dan Arsip Akreditasi |
| Role permission matrix | Aman untuk akses | `RolePermissionController@index/save` eksplisit `isSuperAdmin()` dan exclude role super admin dari matrix agar god-mode tidak bisa dicabut dari UI |
| Secure asesor docs | Aman secara rule | Route `secure/asesor-docs` mengizinkan asesor owner atau `canAccessAdminArea()`, sehingga Admin/Super Admin bisa akses sesuai kebutuhan audit |
| Workflow akreditasi/banding | Aman di service | Super Admin lewat permission/bypass yang sama dengan Admin; full happy path dan banding lifecycle tetap hijau |

Gap Super Admin:

| ID | Modul | Prioritas | Temuan | Dampak | Rekomendasi |
|---|---|---:|---|---|---|
| SA-001 | Notification recipients | P1 | Banyak service/listener masih mengirim notifikasi operasional ke `role_id = 1` saja | Super Admin bisa mengakses dashboard/failed notification, tetapi bisa tidak menerima notifikasi workflow penting secara langsung | Putuskan kebijakan produk: jika Super Admin harus ikut menerima sinyal operasional, ubah penerima menjadi admin area users (`role_id IN (1,4)` atau helper query) dan tambah regression notification |
| SA-002 | Role mutation policy | P1 | `RoleController@index` sudah `isSuperAdmin()`, tetapi `store/update/destroy` hanya `Gate::authorize('master.role')` | Jika permission `master.role` pernah didelegasikan ke non-super-admin, mutasi role bisa dilakukan tanpa konsistensi super-admin-only | Selaraskan semua method RoleController ke `isSuperAdmin()` atau dokumenkan bahwa `master.role` memang boleh didelegasikan |
| SA-003 | Canonical role protection | P1 | `RoleService::deleteRole()` dan update role belum melindungi role inti id `1..4` dari rename/delete | Super Admin bisa tidak sengaja merusak role dasar dan membuat user punya role kosong/semantik role berubah | Tambah guard untuk role inti: tidak boleh delete, dan rename/parameter harus dibatasi atau perlu konfirmasi/audit khusus |
| SA-004 | Role/permission HTTP coverage | P1 | Test model/menu kuat, tetapi HTTP regression untuk store/update/delete role dan save permission matrix belum lengkap | Setelah migrasi Blade, field form/method/redirect role management bisa rusak tanpa tertangkap service/unit test | Tambah HTTP tests Super Admin untuk role CRUD, role matrix save, regular admin forbidden, asesor/pesantren forbidden |
| SA-005 | Account permission granularity | P2 | Account update/unlink memakai permission `account.create`; tidak ada permission `account.update`/`account.unlink_sso` | Jika permission matrix dipersempit, hak create bisa sekaligus menjadi hak update/unlink | Tambah permission granular atau dokumenkan `account.create` sebagai `account.manage`; tambah HTTP tests |
| SA-006 | Notification action permission | P1 | Sama dengan Admin: retry/dismiss failed notifications masih berbasis view route/controller belum action-specific | Super Admin aman karena god-mode, tetapi governance permission untuk role lain lemah | Ikuti `ADM-006`/`BE-027`: action permission eksplisit untuk retry/dismiss |
| SA-007 | Trash mutation permission | P1 | Sama dengan Admin: restore/force-delete masih berbasis view route/controller belum action-specific | Super Admin aman karena god-mode, tetapi permission matrix tidak bisa membedakan view vs mutate | Ikuti `ADM-005`/`BE-026`: pisahkan restore/purge dari view |
| SA-008 | Query sorting governance | P2 | Role/account/admin list masih meneruskan `sortField` request langsung ke `orderBy()` | Query error/inkonsistensi di modul governance | Ikuti allowlist sorting Admin, khususnya RoleRepository dan Account/UserRepository |

Catatan sinkron frontend:

- Frontend Super Admin boleh memakai menu `Role Sistem` dan `Hak Akses` sebagai super-admin-only; backend juga menolak regular admin.
- Jangan menganggap permission matrix bisa mengubah role super admin; backend sengaja exclude role id `4` dari matrix.
- Jika frontend membuat tombol delete role, role inti `Admin`, `Asesor`, `Pesantren`, `Super Admin` sebaiknya diberi disabled state sampai backend menutup `SA-003`.
- Notifikasi workflow untuk Super Admin belum bisa dianggap setara Admin sampai `SA-001` diputuskan dan diimplementasikan.

#### 4.1.2 Plan Perbaikan Gap Super Admin

Tujuan batch ini: memastikan Super Admin aman sebagai governance role, tidak mudah merusak role inti, dan menerima sinyal operasional sesuai keputusan produk.

##### Batch SA-A - Super Admin HTTP Regression

Backlog: `BE-030`, `BE-032`, `BE-034`

Test yang perlu dibuat dulu:

| Area | Skenario minimal |
|---|---|
| Role Management | Super Admin bisa akses index; regular admin/asesor/pesantren forbidden |
| Role CRUD | Super Admin create/update role non-inti; delete role non-inti; role inti tidak bisa delete |
| Permission Matrix | Super Admin bisa save matrix role non-super-admin; role id `4` tidak muncul/terubah |
| Account Management | Super Admin bisa create/update/toggle/delete akun sesuai policy; self-delete tetap gagal |

##### Batch SA-B - Role Governance

Backlog: `BE-030`, `BE-032`, `BE-033`

Perubahan target:

- Semua mutation `RoleController` konsisten super-admin-only.
- Role inti id `1..4` tidak bisa dihapus.
- Rename/ubah parameter role inti dikunci atau wajib mekanisme khusus dengan audit log.
- Tambah allowlist sort field di RoleRepository.

##### Batch SA-C - Notification Recipient Policy

Backlog: `BE-001`

Perubahan target:

- Putuskan apakah Super Admin menerima semua notifikasi Admin.
- Jika ya, ganti query penerima dari `role_id = 1` menjadi admin area recipient helper.
- Tambah tests untuk pengajuan baru, submit perbaikan, rejection, banding, post-visitasi document complete, scoring finalized, dan deadline notification.

##### Batch SA-D - Permission Granularity Sync

Backlog: `BE-026`, `BE-027`, `BE-035`

Perubahan target:

- Trash/failed notification memakai permission mutasi eksplisit.
- Account update/unlink SSO punya permission jelas atau rename policy menjadi `account.manage`.
- Sinkronkan label di permission matrix agar frontend tidak menampilkan hak yang ambigu.

### 4.2 Admin

| Modul | Backend Surface | Risiko | Prioritas | Status |
|---|---|---:|---|---|
| Dashboard | `DashboardController` | Count salah | P2 | Sudah ada test sebagian |
| Akreditasi List/Detail | Admin controllers + workflow service | State/permission salah | P0 | Perlu HTTP POST coverage semua tombol Blade |
| Verifikasi Berkas | `openForReview`, `approveBerkas`, `rejectBerkas` | Status/assignment salah | P0 | Service aman, HTTP perlu diperkuat |
| Validasi Admin | `saveAdminNv`, `finalizeAllNv`, `approve`, `reject` | SK/nilai/status salah | P0 | Service aman, HTTP perlu diperkuat |
| Banding | `assignReviewer`, `reassignReviewer`, `submitDecision` | Banding tidak jalan | P0 | Backend route ada, perlu HTTP test dan sinkron frontend |
| Pesantren | list/detail/toggle/export | Manual lock mengganggu flow | P1 | Rekomendasi revisi lock policy, lihat Section 6 |
| Asesor | list/detail/toggle/export | Assignment/status salah | P1 | Perlu HTTP regression |
| Master Data | EDPM, dokumen, kategori | Master data invalid | P1 | Perlu CRUD regression |
| Notifications | failed notifications, workflow notifications | Notifikasi tidak terkirim | P2 | Perlu penerima super admin/admin dicek |

#### 4.2.1 Audit Scope Admin - 8 Juni 2026

Audit backend Admin sudah dicek dari route, controller, service, repository, policy, permission, menu, dan test terarah. Fokus audit ini adalah backend setelah migrasi full Blade: apakah form/action Blade sudah selaras dengan route/service, apakah permission mutasi cukup spesifik, dan apakah flow bisnis Admin masih mengikuti state machine.

Verifikasi yang dijalankan:

```text
php artisan test tests/Feature/AdminDashboardTest.php tests/Feature/PermissionMiddlewareTest.php tests/Feature/RoleMiddlewareTest.php tests/Feature/RoleServiceTest.php tests/Unit/PermissionSystemTest.php tests/Feature/MasterEdpmServiceTest.php tests/Feature/DocumentServiceTest.php tests/Feature/DocumentAuditTrailTest.php tests/Feature/Property/FailedNotificationPropertyTest.php tests/Feature/Trash tests/Feature/ReassignAsesorTest.php tests/Feature/AkreditasiWorkflow/FullHappyPathTest.php tests/Feature/AkreditasiWorkflow/PostVisitasiDocumentsTest.php tests/Feature/BandingLifecycleTest.php tests/Feature/BandingRegressionTest.php tests/Feature/BandingNotificationTest.php tests/Feature/AuditTrailIntegrationTest.php tests/Feature/NvAuditTrailTest.php --stop-on-failure
961 passed, 7127 assertions
```

Ringkasan aman:

| Area | Status | Catatan |
|---|---|---|
| Route group Admin | Aman untuk role boundary | Route utama Admin berada di `auth`, `verified`, `role:admin`; super admin bypass tetap berlaku melalui middleware role |
| Permission middleware | Aman | Test membuktikan user tanpa permission 403, user dengan permission lolos, super admin bypass permission |
| Dashboard status count | Aman | Count visitasi memisahkan status `3` dan `2` sesuai test |
| Akreditasi workflow service | Kuat | `openForReview`, `approveBerkas`, `rejectBerkas`, `issueSK`, `rejectAtValidasi`, post-visitasi document, dan full happy path sudah hijau di service/workflow tests |
| Banding service | Kuat | Submit banding, assign/reassign reviewer, accept/reject decision, uniqueness, limit, dan notification sudah hijau |
| Trash service | Kuat di service | Restore, force delete, preview, count, purge, dan cascade sudah banyak property/flow test |
| Master EDPM/Dokumen | Cukup kuat di service | CRUD service dan audit trail dokumen sudah hijau |
| Role/permission model | Aman secara model | Admin punya semua permission kecuali `master.role`; super admin god-mode; role matrix diuji |

Gap Admin:

| ID | Modul | Prioritas | Temuan | Dampak | Rekomendasi |
|---|---|---:|---|---|---|
| ADM-001 | Validasi Admin - NV | P0 | Form/controller `saveAdminNv` dan `finalizeAllNv` hanya mengirim `adminNvs`, sedangkan `AssessorScoringService::saveNV()` mewajibkan alasan saat NV final berbeda dari NK | Jika admin perlu mengubah NV berbeda dari NK, finalisasi bisa gagal/tidak punya audit reason yang benar | Tambah field reason per butir atau reason kolektif yang dipetakan ke butir berbeda, lalu tambah HTTP regression untuk NV sama NK, NV beda NK tanpa reason, dan NV beda NK dengan reason |
| ADM-002 | Validasi Admin - final flag | P1 | `finalizeAllNv()` mengubah `akreditasis.is_nv_final = true` setelah loop, tanpa memastikan semua butir valid/final berhasil diproses | Status global bisa terlihat final walau jumlah NV final belum lengkap; `issueSK()` tetap menolak, tetapi UI/flow bisa membingungkan | Validasi jumlah final terhadap total butir sebelum set `is_nv_final`, dan tampilkan blocker yang eksplisit |
| ADM-003 | Upload SK | P1 | `approve()` menyimpan file sertifikat sebelum `workflowService->issueSK()`. Jika service menolak karena stale state/domain precondition, file baru bisa menjadi orphan | Storage bisa berisi file sertifikat tanpa SK terbit | Tambah cleanup file baru pada catch `DomainException`, `ConflictException`, dan `StaleStateException`, atau pindahkan store file ke service transaction-aware pattern |
| ADM-004 | Master Dokumen upload | P1 | `DocumentService::saveDocument()` menghapus file lama setelah file baru tersimpan tetapi sebelum DB update selesai | Jika update DB gagal, file lama bisa hilang dan file baru bisa yatim | Ubah pola menjadi simpan file baru, update DB, baru hapus file lama; rollback file baru saat update gagal |
| ADM-005 | Trash permission | P1 | Route `restore` dan `force-delete` memakai middleware `permission:trash.view`, controller belum `Gate::authorize('trash.restore')` atau permission khusus force delete | User dengan view trash bisa melakukan mutasi jika permission matrix dipersempit di masa depan | Pisahkan permission `trash.restore` dan `trash.force-delete`, tambah Gate di controller, dan HTTP tests untuk view-only vs mutating permission |
| ADM-006 | Failed Notifications permission | P1 | Route `retry` dan `dismiss` memakai `permission:notification.view`, controller belum memakai `notification.retry`/permission dismiss | User yang hanya boleh melihat notifikasi bisa retry/dismiss jika matrix dipersempit | Gunakan `notification.retry` untuk retry, tambah `notification.dismiss` atau kebijakan yang eksplisit, dan HTTP tests |
| ADM-007 | Asesor list filter | P2 | `Admin\AsesorController` mengirim filter key `filterStatus`, `filterPeran`, `filterPenugasan`, sementara repository membaca `status`, `peran`, `penugasan` | Filter status/peran/penugasan daftar asesor tidak bekerja konsisten | Selaraskan key controller-service-repository dan tambah regression untuk masing-masing filter |
| ADM-008 | Query sorting Admin | P2 | Banyak list/export Admin meneruskan `sortField` request langsung ke `orderBy()` (`akreditasi`, `pesantren`, `asesor`, `accounts`, `roles`, `master dokumen`, `kategori`) | Query error/inkonsistensi bila query param tidak valid; risiko meningkat di export karena field juga dipakai di export query | Tambah allowlist per modul dan fallback ke default |
| ADM-009 | Role mutation super-admin only | P1 | `RoleController@index` membatasi `isSuperAdmin()`, tetapi `store/update/destroy` hanya `Gate::authorize('master.role')` | Jika permission `master.role` pernah diberikan ke non-super-admin, mutasi role bisa dilakukan tanpa konsistensi super-admin-only controller | Tambahkan `abort_unless(auth()->user()?->isSuperAdmin(), 403)` pada semua mutasi role atau putuskan bahwa `master.role` memang boleh didelegasikan |
| ADM-010 | HTTP coverage action Admin | P1 | Service tests kuat, tetapi belum semua action POST Admin punya HTTP regression lengkap setelah Blade migration | Form name, method, redirect, permission, dan route contract bisa rusak tanpa terdeteksi service tests | Tambah HTTP tests untuk open review, approve/reject berkas, save/finalize NV, approve/reject final, reschedule, reassign, trash, notification retry/dismiss, account, dan master data |
| ADM-011 | Backend-frontend route contract Trash | P1 | Backend route trash saat ini `POST /admin/trash/restore` dan `POST /admin/trash/force-delete` dengan body `id`, sedangkan frontend audit pernah mencatat pola `/admin/trash/{id}/restore` dan DELETE | Tombol frontend bisa diarahkan ke endpoint yang tidak ada meskipun service trash aman | Sinkronkan frontend ke route backend saat ini atau ubah backend route bersama HTTP tests |

Catatan sinkron frontend:

- Tombol/flow NV Admin jangan dianggap final sampai `ADM-001` ditutup karena frontend perlu menyediakan reason saat NV berbeda dari NK.
- Trash frontend harus mengikuti route backend saat ini: `admin.trash.restore` dan `admin.trash.force-delete` menerima `POST` dengan field `id`.
- Failed notification frontend boleh memakai route `admin.failed-notifications.retry` dan `dismiss`, tetapi backend akan diperketat permission-nya pada batch Admin.
- Filter daftar asesor perlu menunggu `ADM-007` agar UI filter tidak terlihat bekerja padahal backend mengabaikan key.

#### 4.2.2 Plan Perbaikan Gap Admin

Tujuan batch ini: menutup kontrak HTTP Blade untuk action Admin, memperjelas permission mutasi operasional, dan menjaga workflow validasi admin tetap auditable.

##### Batch ADM-A - HTTP Regression Admin

Backlog: `BE-003`, `BE-023`, `BE-024`, `BE-028`, `BE-031`

Test yang perlu dibuat dulu:

| Area | Skenario minimal |
|---|---|
| Verifikasi berkas | Admin bisa open review status `6`, approve berkas status `5`, reject berkas status `5`; wrong status gagal |
| Validasi Admin NV | Save draft NV, finalize semua NV, NV beda NK wajib reason, final flag hanya true jika semua final |
| Final SK | Terbit SK sukses saat dokumen pasca visitasi dan NV lengkap; stale/domain failure tidak meninggalkan file |
| Banding | Assign/reassign/decision melalui HTTP, permission wrong actor gagal |
| Trash | View-only tidak bisa restore/force-delete; permission mutasi bisa restore/force-delete |
| Failed notifications | View-only tidak bisa retry/dismiss; retry permission bisa retry |
| Asesor filter | Filter status/peran/penugasan menghasilkan query sesuai |

##### Batch ADM-B - NV Audit Contract

Backlog: `BE-023`, `BE-024`

Perubahan target:

- Tambah input reason untuk NV yang berbeda dari NK.
- Controller memetakan reason ke `AssessorScoringService::saveNV(..., true, $reason)`.
- `finalizeAllNv()` tidak set `is_nv_final` sebelum jumlah NV final lengkap.
- Flash error menampilkan butir yang gagal, bukan membiarkan exception menjadi 500.

##### Batch ADM-C - Permission Tightening

Backlog: `BE-026`, `BE-027`, `BE-030`

Perubahan target:

- Trash restore/force-delete memakai permission mutasi, bukan sekadar view.
- Failed notifications retry/dismiss memakai permission action yang sesuai.
- RoleController konsisten super-admin-only untuk index dan mutation, atau dokumen keputusan delegasi `master.role` diperjelas.

##### Batch ADM-D - Storage Rollback

Backlog: `BE-025`

Perubahan target:

- File sertifikat SK dibersihkan jika `issueSK()` gagal.
- Master dokumen menghapus file lama hanya setelah DB update sukses.
- File baru master dokumen dibersihkan bila DB update/create gagal.

##### Batch ADM-E - Query Hygiene dan Frontend Sync

Backlog: `BE-028`, `BE-029`, `BE-031`

Perubahan target:

- Tambah allowlist `sortField` di semua list/export Admin.
- Selaraskan filter key Asesor.
- Sinkronkan route trash dan failed notification dengan frontend agent.

### 4.3 Asesor

| Modul | Backend Surface | Risiko | Prioritas | Status |
|---|---|---:|---|---|
| Dashboard | `DashboardController` filtered by assignment | Data asesor lain muncul | P0 | Perlu HTTP smoke per assignment |
| Tugas Akreditasi | `Asesor\AkreditasiController@index`, repository search | Data leak | P0 | Sudah diperbaiki dan dites |
| Detail Akreditasi | `show`, `AsesorService::getAkreditasiDetailAsesor` | Asesor lain bisa lihat detail | P0 | Policy/service aman, perlu tetap regression |
| Catatan JSON | `showCatatan` | Data leak | P0 | Sudah diperbaiki dan dites |
| Jadwal Visitasi | `scheduleVisitasi` | Non-ketua menjadwalkan | P0 | Service guard ada, perlu HTTP test |
| Reject Dokumen | `rejectDocument`, `RejectionService` | Non-ketua/rejection salah | P0 | Service guard ada, perlu HTTP test |
| Accept Perbaikan | `acceptPerbaikan` | Perbaikan diterima actor salah | P0 | Service guard ada, perlu HTTP test |
| Scoring | `saveEdpm`, `saveNA`, `saveNK`, `finalizeScoring` | Nilai salah/actor salah | P0 | Service kuat, perlu HTTP/API regression |
| Upload Laporan | `uploadLaporanIndividu`, `uploadLaporanKelompok` | Upload actor/status salah | P0 | Service test ada, perlu HTTP regression |
| Profile | `Asesor\ProfileController` | User lain update profile | P1 | Perlu HTTP regression |

#### 4.3.1 Audit Scope Asesor — 8 Juni 2026

Audit backend Asesor sudah dicek dari route, controller, service, repository, policy, menu, dan test terarah.

Verifikasi yang dijalankan:

```text
php artisan test tests/Feature/AsesorAkreditasiMenuContextTest.php tests/Feature/RejectionServiceTest.php tests/Feature/RejectionRegressionTest.php tests/Feature/RejectionNotificationTest.php tests/Feature/AkreditasiWorkflow/PostVisitasiDocumentsTest.php tests/Feature/AkreditasiWorkflow/FullHappyPathTest.php tests/Unit/Workflow/Property9NKPreconditionGateTest.php --stop-on-failure
52 passed, 394 assertions
```

Ringkasan aman:

| Area | Status | Catatan |
|---|---|---|
| Route group Asesor | Aman | Semua route utama berada di middleware `auth`, `verified`, `role:asesor` |
| Dashboard Asesor | Aman untuk assignment scope | Query dashboard memakai `assessments.asesor_id` dari profil asesor login |
| List tugas Asesor | Aman setelah perbaikan | Repository membatasi `Assessment::where('asesor_id', $asesorId)` dan search sudah digrup agar tidak bocor ke asesor lain |
| Detail akreditasi | Aman untuk tenant boundary | `AsesorService::getAkreditasiDetailAsesor()` hanya mengembalikan data jika asesor login memang assigned |
| Catatan JSON | Aman setelah perbaikan | Endpoint sudah `Gate::authorize('view', $akreditasi)` |
| Reject dokumen | Aman di service | `RejectionService::createDocumentRejection()` memastikan actor adalah Asesor 1 dan status benar |
| Accept perbaikan | Aman di service | `RejectionService::acceptPerbaikan()` memastikan actor adalah Asesor 1 dan rejection sudah submitted |
| Confirm visitasi selesai | Aman di service | `AkreditasiWorkflowService::confirmVisitasiSelesai()` memastikan status visitasi dan actor Asesor 1 |
| Scoring NA/NK/finalize | Aman di service | Gate assignment, status pasca visitasi, immutability final, dan NK gate sudah dites |
| Upload laporan | Aman di service | Laporan individu dibatasi asesor assigned; laporan kelompok dibatasi Asesor 1; status pasca visitasi dicek |
| Dokumen menu Asesor | Aman | Visibility dokumen memakai `document_categories.visibility`, asesor tidak melihat kategori rahasia pesantren |

Temuan gap:

| ID | Area | Prioritas | Temuan | Dampak | Rekomendasi |
|---|---|---|---|---|---|
| ASE-001 | Jadwal Visitasi | P0 | `AkreditasiWorkflowService::scheduleVisitasi()` mensyaratkan status Review Asesor `4`, tetapi `Asesor\AkreditasiController@show` mengatur `canScheduleVisitasi` hanya untuk status Verifikasi Berkas `5` | Normal flow penjadwalan visitasi bisa tidak muncul di detail saat status yang benar (`4`) | Selaraskan flag controller/view ke status `4`, dan tambah HTTP/view regression bahwa Asesor 1 bisa melihat/menjalankan jadwal pada status Review Asesor |
| ASE-002 | Reject Dokumen | P1 | `canSubmitDocumentRejection` mengizinkan status `5` dan `4`, sementara service rejection hanya menerima status `4` | UI bisa menawarkan aksi yang pasti gagal bila ada data status `5` assigned | Selaraskan flag ke status `4` saja, kecuali bisnis memang ingin admin-verifikasi rejection dari asesor |
| ASE-003 | HTTP coverage action Asesor | P1 | Test service kuat, tetapi HTTP POST coverage untuk schedule, reject document, accept perbaikan, confirm visitasi, finalize scoring, save NA/NK, dan upload laporan belum lengkap | Regression setelah Blade migration bisa lolos meskipun form/action route rusak | Tambah Feature tests HTTP untuk success, wrong actor, wrong status, dan non-assigned asesor |
| ASE-004 | Profile upload rollback | P1 | `Asesor\ProfileController@update` menyimpan foto/dokumen baru sebelum update DB, tetapi tidak menghapus file baru jika `updateProfile()` gagal | File yatim bisa tertinggal di `public/asesor_docs` atau `local/asesor_private_docs` | Tambah rollback file baru pada failure, mirip pola Pesantren profile |
| ASE-005 | Query sorting list tugas | P2 | `sortField` dari request diteruskan ke repository `orderBy()` tanpa allowlist | Risiko query error/inkonsistensi bila query param tidak sesuai kolom; assignment boundary tetap terjaga | Batasi sort field assessment list ke allowlist kolom valid |
| ASE-006 | Password di profile asesor | P2 | Profile asesor bisa mengubah password via field optional tanpa konfirmasi/current password, berbeda dari profile umum | Inkonsistensi kebijakan akun dan potensi salah input | Putuskan apakah field password asesor dihapus dan diarahkan ke Pengaturan Profil umum, atau tambah confirmation/current password |

Catatan sinkron frontend:

- Frontend agent jangan finalisasi tombol/visibility Jadwal Visitasi sebelum backend menutup `ASE-001`.
- Jika frontend memperbaiki halaman detail Asesor lebih dulu, gunakan status Review Asesor (`4`) sebagai konteks jadwal, tetapi tetap tunggu backend tests untuk kontrak final.
- Tombol reject dokumen sebaiknya hanya muncul pada status Review Asesor (`4`) sampai ada keputusan bisnis lain.
- Perubahan profile asesor yang menyentuh password sebaiknya menunggu keputusan backend `ASE-006`.

#### 4.3.2 Plan Perbaikan Gap Asesor

Tujuan batch ini: memastikan workflow Asesor berjalan dari HTTP Blade sampai service tanpa data leak antar asesor, dan memastikan flag/tombol controller selaras dengan state machine.

##### Batch ASE-A — Regression Tests Dulu

Prioritas: P0/P1  
Backlog: `BE-004`, `BE-005`, `BE-017`, `BE-018`, `BE-019`, `BE-020`, `BE-021`, `BE-022`

Rencana test:

| Test Area | Skenario Minimal |
|---|---|
| Jadwal visitasi view/action | Asesor 1 pada status `4` bisa melihat/menjalankan jadwal; Asesor 2 dan non-assigned gagal |
| Reject dokumen | Asesor 1 status `4` bisa reject; Asesor 2/non-assigned/status salah gagal |
| Accept perbaikan | Asesor 1 bisa accept rejection `submitted`; status `pending`/non-owner gagal |
| Confirm visitasi selesai | Asesor 1 bisa confirm jika tanggal mulai sudah tercapai; Asesor 2/non-assigned/tanggal belum mulai gagal |
| Save NA/NK | Asesor assigned bisa save NA pada status `2`; NK hanya Asesor 1 setelah NA1 dan NA2 final |
| Finalize scoring | Asesor 1 bisa finalize hanya jika dokumen pasca visitasi dan nilai lengkap |
| Upload laporan | Assigned asesor bisa upload laporan individu; hanya Asesor 1 bisa upload laporan kelompok |
| Profile upload rollback | File baru dibersihkan ketika update profile gagal |
| Sort allowlist | sort field invalid fallback dan tidak membuat query error |

##### Batch ASE-B — Selaraskan Flag Jadwal dan Reject Dokumen

Prioritas: P0/P1  
Backlog: `BE-017`, `BE-018`

Perubahan behavior:
- `canScheduleVisitasi` mengikuti status Review Asesor (`4`) dan Asesor 1.
- `canSubmitDocumentRejection` mengikuti status Review Asesor (`4`) dan Asesor 1, kecuali ada keputusan bisnis lain.

Catatan frontend:
- Tidak perlu route baru.
- Blade boleh tetap menggunakan variable controller yang sama setelah backend diselaraskan.

##### Batch ASE-C — HTTP Coverage Action Asesor

Prioritas: P1  
Backlog: `BE-004`, `BE-005`, `BE-019`

Target:
- Semua route POST Asesor punya regression test HTTP minimal.
- Test harus membuktikan actor assignment, tipe asesor, status workflow, dan response `success/error` atau JSON 422 sesuai kontrak.

##### Batch ASE-D — Profile Asesor Hygiene

Prioritas: P1/P2  
Backlog: `BE-020`, `BE-022`

Perubahan:
- Tambah rollback file baru saat profile update gagal.
- Putuskan kebijakan password asesor: tetap di profile asesor dengan validation lebih kuat, atau arahkan ke Pengaturan Profil umum.

##### Batch ASE-E — Sort Allowlist dan Verifikasi

Prioritas: P2  
Backlog: `BE-021`

Perubahan:
- Tambah allowlist sort untuk list tugas asesor.
- Rerun targeted Asesor suite.

### 4.4 Pesantren

| Modul | Backend Surface | Risiko | Prioritas | Status |
|---|---|---:|---|---|
| Dashboard | `DashboardController`, `SidebarProgressService` | Data/count salah | P2 | Ada test sebagian |
| Profile | `Pesantren\ProfileController`, `PesantrenService` | Update saat locked | P0 | Service guard ada, perlu HTTP regression |
| IPM | `IpmController`, `PesantrenService::updateIpm` | Upload/update saat locked | P0 | Perlu HTTP regression |
| SDM | `SdmController`, `updateSdm` | Update saat locked | P0 | Perlu HTTP regression |
| EDPM | `EdpmController`, `saveEdpmEvaluation`, `saveEdpmDraft` | Partial/locked write | P0 | Service guard ada, perlu HTTP regression |
| Akreditasi List | `AkreditasiController@index` | Filter/status salah | P2 | Ada test sederhana |
| Create/Delete/Cancel | `submitPengajuan`, `deleteSubmission`, `cancelSubmission` | Duplikasi/owner salah | P0 | Service test ada, perlu HTTP regression |
| Banding | `submitAppeals`, `BandingService` | Banding actor/status salah | P0 | Service test ada, perlu HTTP regression |
| Catatan JSON | `showCatatan` | Pesantren baca catatan orang lain | P0 | Ownership query ada, perlu regression |
| Upload Kartu Kendali | `uploadKartuKendali` | Upload ke akreditasi orang lain/status salah | P0 | Service test ada, perlu HTTP regression |

#### 4.4.1 Audit Scope Pesantren — 8 Juni 2026

Audit backend Pesantren sudah dicek dari route, controller, service, repository, policy, menu, dan test terarah.

Verifikasi yang dijalankan:

```text
php artisan test tests/Feature/Pesantren tests/Feature/PesantrenAkreditasiTest.php tests/Feature/PesantrenAkreditasiWorkflowTest.php tests/Feature/PesantrenAkreditasiMenuContextTest.php tests/Feature/PesantrenUploadTest.php tests/Feature/BandingSubmitAppealsTest.php tests/Feature/AkreditasiWorkflow/PostVisitasiDocumentsTest.php --stop-on-failure
120 passed, 252 assertions
```

Ringkasan aman:

| Area | Status | Catatan |
|---|---|---|
| Route group Pesantren | Aman | Semua route utama berada di middleware `auth`, `verified`, `role:pesantren` |
| Akreditasi list/detail/catatan | Aman untuk tenant boundary | Query dibatasi `user_id = auth()->id()` atau `Auth::id()` |
| Create/delete/cancel pengajuan | Aman di service | Owner dan status dicek; delete/cancel hanya status pengajuan |
| Banding | Aman di service | Submit banding dibatasi owner dan lifecycle banding |
| Upload kartu kendali | Aman di service | Owner dan status pasca visitasi dicek; file rollback saat gagal |
| Dokumen menu | Aman | Visibility dokumen memakai `document_categories.visibility`, bukan legacy flag |

Temuan gap:

| ID | Area | Prioritas | Temuan | Dampak | Rekomendasi |
|---|---|---|---|---|---|
| PES-001 | Partial unlock Profile/IPM/SDM/EDPM | P0 | Controller Pesantren menolak langsung saat `pesantren.is_locked = true`, sebelum memanggil service yang sudah punya partial unlock via `RejectionService::isSectionUnlocked()` | Pesantren tidak bisa memperbaiki section yang ditolak asesor/admin lewat HTTP Blade walaupun service mendukung | Hapus global early-return di controller dan pindahkan keputusan edit ke service/section lock helper; tambah HTTP regression locked/unlocked/partial unlock |
| PES-002 | Submit perbaikan | P0 | `AkreditasiWorkflowService::submitPerbaikan()` ada, tetapi tidak ada route/controller action Pesantren untuk submit perbaikan | Setelah data diperbaiki, rejection tidak berubah dari `pending` ke `submitted`, sehingga asesor tidak punya sinyal backend bahwa perbaikan siap direview | Tambah POST route Pesantren submit perbaikan dengan ownership check dan panggil workflow service |
| PES-003 | Menu status perbaikan | P1 | `focus=perbaikan` di `Pesantren\AkreditasiController@index` dipetakan ke status `-1`, padahal perbaikan aktif dari structured rejection bisa tetap di status `4` atau `5` dengan rejection `pending` | Menu Status Perbaikan bisa kosong atau salah konteks saat pesantren sebenarnya sedang diminta revisi | Filter perbaikan berdasarkan active rejection milik user, bukan hanya status `-1` |
| PES-004 | HTTP coverage | P1 | Test Pesantren yang lulus masih banyak service-level; HTTP POST untuk profile/IPM/SDM/EDPM partial unlock dan submit perbaikan belum ada | Regression setelah Blade migration bisa lolos walaupun flow perbaikan HTTP rusak | Tambah Feature tests HTTP sesuai BE-006 dan BE-007 |
| PES-005 | Query sorting list akreditasi | P2 | `sortField` dari request diteruskan ke repository `orderBy()` tanpa allowlist | Risiko query error/inkonsistensi bila query param tidak sesuai kolom; tenant boundary tetap terjaga karena `where user_id` diterapkan lebih dulu | Batasi sort field ke allowlist kolom yang valid |

Catatan sinkron frontend:

- Jangan finalisasi UI Status Perbaikan sebelum backend memperbaiki `PES-001`, `PES-002`, dan `PES-003`.
- Frontend boleh tetap merapikan tampilan read-only detail Pesantren, tetapi form perbaikan/submit perbaikan perlu menunggu route dan kontrak backend.
- Tombol/form upload kartu kendali aman dari sisi backend, tetapi frontend sebaiknya hanya menonjolkan pada status pasca visitasi.

#### 4.4.2 Plan Perbaikan Gap Pesantren

Tujuan batch ini: memastikan flow Pesantren setelah diminta revisi benar-benar berjalan dari HTTP Blade sampai service, tanpa membuka akses lintas pesantren dan tanpa mengubah state machine utama.

Urutan perbaikan disusun kecil agar setiap batch tetap bisa dites dan disinkronkan dengan frontend.

##### Batch PES-A — Regression Tests Dulu

Prioritas: P0  
Backlog: `BE-006`, `BE-013`, `BE-014`, `BE-015`, `BE-016`

Rencana test:

| Test Area | Skenario Minimal |
|---|---|
| Profile partial unlock | Pesantren locked + active rejection item `profil` bisa POST profile; locked tanpa rejection tetap gagal; foreign user tidak bisa menyentuh data |
| IPM partial unlock | Pesantren locked + item `ipm.nsp` hanya bisa update `nsp_file`; item IPM lain tetap tidak berubah |
| SDM partial unlock | Pesantren locked + item `sdm` bisa POST SDM; locked tanpa item `sdm` gagal |
| EDPM partial unlock | Pesantren locked + item `edpm.butir.{id}` hanya bisa update butir itu; butir lain tetap tidak berubah |
| Submit perbaikan | Owner bisa submit active rejection `pending -> submitted`; non-owner gagal; tanpa active rejection gagal |
| Status perbaikan list | `focus=perbaikan` menampilkan akreditasi dengan active rejection milik user, bukan hanya status `-1` |
| Sort allowlist | sort field invalid tidak membuat query error dan fallback ke `created_at` |

Output:
- Feature test baru atau perluasan test di area Pesantren.
- Test awal boleh merah untuk `PES-001`, `PES-002`, dan `PES-003`, lalu dibuat hijau di batch berikutnya.

##### Batch PES-B — Partial Unlock HTTP

Prioritas: P0  
Backlog: `BE-013`

Perubahan behavior:
- Controller `Pesantren\ProfileController`, `IpmController`, `SdmController`, dan `EdpmController` tidak boleh lagi melakukan global early-return hanya karena `pesantren.is_locked = true`.
- Keputusan boleh/tidak edit dikembalikan ke `PesantrenService`, karena service sudah punya logika partial unlock via `RejectionService::isSectionUnlocked()`.
- Untuk upload file, controller tetap wajib rollback file baru bila service mengembalikan `false`.

Kontrak backend:
- Jika section tidak dibuka: redirect back dengan flash `error`.
- Jika section dibuka: simpan hanya section/item yang memang masuk active rejection.
- Tidak ada perubahan route name frontend untuk form profile/IPM/SDM/EDPM.

Catatan frontend:
- Form boleh tetap memakai route lama.
- Frontend boleh menampilkan indikator "dibuka untuk perbaikan", tetapi keputusan final tetap dari backend.

##### Batch PES-C — Submit Perbaikan Pesantren

Prioritas: P0  
Backlog: `BE-014`

Route yang disarankan:

```text
POST /pesantren/akreditasi/perbaikan/submit
name: pesantren.akreditasi.submit-perbaikan
field: akreditasi_id
```

Controller behavior:
- Validasi `akreditasi_id` required integer.
- Ambil akreditasi milik `Auth::id()`.
- Panggil `AkreditasiWorkflowService::submitPerbaikan($akreditasiId, Auth::id())`.
- Success: flash `success` dan redirect back atau ke `pesantren.akreditasi?focus=perbaikan`.
- Failure: flash `error` berisi pesan domain yang aman untuk user.

Catatan domain:
- Implementasi awal mengikuti service saat ini: active rejection adalah type `asesor` dengan status `pending`.
- Jika nanti admin verifikasi juga memakai partial unlock terstruktur, perlu perluasan `RejectionRepository::findActiveByAkreditasi()` atau method baru agar type `admin_verifikasi` ikut lifecycle submit perbaikan.

Catatan frontend:
- Setelah route ini ada, frontend agent boleh menambahkan tombol "Kirim Perbaikan" pada Status Perbaikan.
- Form frontend wajib mengirim `akreditasi_id` dan CSRF token.

##### Batch PES-D — Status Perbaikan List

Prioritas: P1  
Backlog: `BE-015`

Perubahan behavior:
- `focus=perbaikan` tidak lagi dipetakan langsung ke status `-1`.
- List harus mengambil akreditasi milik pesantren yang memiliki active rejection perbaikan.
- Status `-1` tetap bisa muncul di konteks penolakan/banding, tetapi bukan satu-satunya definisi "perbaikan".

Opsi implementasi:
- Tambah parameter khusus di repository seperti `contextFilter = perbaikan`.
- Atau tambah method khusus `getPerbaikanAkreditasisByUserId()`.

Rekomendasi:
- Pilih method khusus agar makna query jelas dan tidak menambah overload pada `statusFilter`.

Catatan frontend:
- Tab Status Perbaikan boleh tetap memakai query `?focus=perbaikan`.
- Label/kosong-state frontend harus menunggu data dari backend, bukan menyimpulkan dari status `-1`.

##### Batch PES-E — Sort Allowlist

Prioritas: P2  
Backlog: `BE-016`

Perubahan:
- Batasi `sortField` list akreditasi Pesantren ke allowlist, misalnya `created_at`, `updated_at`, `status`, `nomor_sk`, `peringkat`.
- Jika request mengirim field di luar allowlist, fallback ke `created_at`.

Catatan:
- Ini bukan data-leak, karena query tetap diawali `where user_id`.
- Tetap perlu ditutup supaya URL/query aneh tidak merusak halaman.

##### Batch PES-F — Verifikasi dan Handoff Frontend

Prioritas: P1  
Backlog: `BE-011`

Target test:

```text
php artisan test tests/Feature/Pesantren tests/Feature/PesantrenAkreditasiTest.php tests/Feature/PesantrenAkreditasiWorkflowTest.php tests/Feature/PesantrenAkreditasiMenuContextTest.php tests/Feature/PesantrenUploadTest.php tests/Feature/BandingSubmitAppealsTest.php tests/Feature/AkreditasiWorkflow/PostVisitasiDocumentsTest.php --stop-on-failure
```

Tambahkan test baru yang relevan ke command targeted ini setelah dibuat.

Handoff untuk frontend:
- Route baru `pesantren.akreditasi.submit-perbaikan`.
- Field wajib `akreditasi_id`.
- Flash keys tetap `success` dan `error`.
- `?focus=perbaikan` tetap kontrak URL untuk tab Status Perbaikan.
- Form profile/IPM/SDM/EDPM tidak berubah route, tetapi backend sekarang bisa menerima partial unlock.

---

## 5. Modul Shared yang Harus Diaudit

| Modul | Backend Surface | Prioritas | Catatan |
|---|---|---|---|
| Auth | register, login, reset password, email verification | P1 | Registration test sudah diperbaiki |
| SSO | `SsoController`, `routes/sso/sso.php` | P1 | Pastikan role/status/sso linkage aman |
| Profile Umum | `ProfileController` | P1 | Update info/password/photo semua role |
| Documents | `DocumentController`, `DocumentPolicy` | P1 | Pastikan visibility role tetap benar |
| Internal API | `_api/sidebar-badges`, notifications, onboarding | P1 | Session-auth only; perlu role-scoped assertions |
| Secure File | `secure/asesor-docs/{asesorId}/{field}` | P0 | Owner asesor atau admin-area only |
| Notifications | listener + services | P2 | Cek penerima admin vs super admin |
| Deadline Commands | perbaikan/banding/assessment reminders | P1 | Pastikan actor fallback dan recipients benar |

---

## 6. Rekomendasi Lock Data Pesantren

Masukan produk: kunci data pesantren dari sisi admin/super admin terasa tidak cocok karena dapat mengganggu flow. Setelah audit awal, rekomendasi backend adalah **mengurangi manual global lock** dan menggantinya dengan lock yang lebih terarah.

### 6.1 Masalah Saat Ini

| Area | Kondisi Sekarang | Risiko Flow |
|---|---|---|
| Pengajuan akreditasi | `submitPengajuan()` otomatis set `pesantren.is_locked = true` | Masih masuk akal untuk menjaga data snapshot saat proses berjalan |
| Admin detail akreditasi | `admin.akreditasi-detail.toggle-lock` bisa toggle lock data pesantren | Admin bisa membuka/menutup seluruh data tanpa konteks section |
| Admin list pesantren | `admin.pesantren.toggle-lock` bisa toggle lock data pesantren | Bisa mengunci pesantren di luar konteks workflow aktif |
| Perbaikan | `RejectionService` sudah punya partial unlock per section | Ini lebih cocok daripada global lock manual |

### 6.2 Rekomendasi Utama

Gunakan model berikut:

1. **Lock otomatis tetap ada hanya sebagai workflow guard.**
   Data pesantren dikunci saat pengajuan aktif agar snapshot akreditasi stabil.

2. **Hilangkan atau turunkan manual global lock admin/super admin.**
   Tombol admin/super admin sebaiknya tidak menjadi aksi bebas untuk mengunci semua data. Jika masih dibutuhkan, jadikan emergency override dengan alasan wajib, audit log, dan konfirmasi kuat.

3. **Utamakan partial unlock berbasis perbaikan.**
   Saat admin/asesor meminta revisi, hanya section yang diminta revisi yang dibuka: `profil`, `ipm.*`, `sdm`, atau `edpm.butir.*`.

4. **Tambahkan status “maintenance/admin review hold” bila memang butuh stop sementara.**
   Jika bisnis butuh menahan perubahan, jangan pakai `is_locked` global yang sama. Buat flag/record terpisah seperti `data_edit_hold_reason`, `held_by`, `held_until`, atau audit event.

5. **Frontend tidak perlu menonjolkan tombol kunci global.**
   Untuk agent frontend: jangan jadikan toggle lock sebagai primary action. Jika backend belum diubah, tampilkan sebagai aksi administratif sekunder saja.

### 6.3 Perubahan Backend yang Disarankan

| ID | Perubahan | Prioritas | Catatan |
|---|---|---|---|
| LOCK-001 | Audit dua route manual lock: `admin.pesantren.toggle-lock` dan `admin.akreditasi-detail.toggle-lock` | P1 | Putuskan apakah dinonaktifkan, dibatasi, atau dijadikan emergency override |
| LOCK-002 | Tambah audit log wajib untuk setiap manual unlock/lock | P1 | Minimal simpan actor, alasan, status lama, status baru |
| LOCK-003 | Ubah service agar perbaikan memakai partial unlock sebagai jalur utama | P0 | Jalur ini sudah ada, perlu HTTP regression penuh |
| LOCK-004 | Tambah HTTP tests locked/unlocked/partial unlock per section | P0 | Sinkron dengan BE-006 |
| LOCK-005 | Update frontend contract: tombol lock global bukan primary flow | P2 | Koordinasi dengan frontend agent |

### 6.4 Keputusan Sementara

Rekomendasi sementara: **jangan hapus lock otomatis pengajuan**, tetapi **hapus/limit manual global lock dari admin/super admin**. Ini menjaga integritas data akreditasi tanpa membuat admin bisa mengganggu workflow pesantren secara tidak sengaja.

---

## 7. Urutan Perbaikan Backend

### Fase 0 — Freeze dan Sinkronisasi

Status: wajib sebelum lanjut role-by-role.

Checklist:
- Catat baseline test backend terakhir.
- Pisahkan scope backend vs frontend.
- Pastikan agent frontend tidak mengubah service/controller/policy tanpa koordinasi.
- Pastikan agent backend tidak mengubah Blade/CSS/JS kecuali untuk test hook minimal.

Output:
- Dokumen ini.
- Update singkat ke dokumen frontend bila ada backend dependency yang mempengaruhi frontend.

### Fase 1 — Super Admin dan Admin

Alasan: role ini punya akses paling luas dan blast radius paling besar.

Checklist:
- HTTP tests untuk role/permission admin route.
- HTTP tests untuk akreditasi admin POST actions.
- HTTP tests untuk banding admin POST actions.
- Audit notifikasi admin-area recipients.
- Audit master data CRUD route.
- Audit account/role/permission management.

Target:
- Tidak ada route admin penting yang hanya mengandalkan UI untuk guard.
- Super admin dan admin behavior terdokumentasi berbeda bila perlu.

### Fase 2 — Asesor

Alasan: banyak aksi workflow penting terjadi di asesor, terutama assignment, visitasi, scoring, dan upload laporan.

Checklist:
- HTTP tests untuk list/search assignment.
- HTTP tests untuk detail unauthorized assignment.
- HTTP tests untuk schedule/confirm/reject/accept/finalize.
- HTTP tests untuk save NA/NK dan upload laporan.
- Pastikan query repository selalu ter-scope `asesor_id`.

Target:
- Tidak ada data leak antar asesor.
- Non-ketua tidak bisa melakukan aksi ketua.

### Fase 3 — Pesantren

Alasan: pesantren adalah owner data dan sumber pengajuan.

Checklist:
- HTTP tests untuk profile/IPM/SDM/EDPM saat locked/unlocked.
- HTTP tests create/delete/cancel akreditasi.
- HTTP tests banding dan upload kartu kendali.
- HTTP tests JSON catatan dan detail ownership.

Target:
- Pesantren tidak bisa membaca atau menulis data pesantren lain.
- Lock/perbaikan section tetap dihormati setelah Blade migration.

### Fase 4 — Shared Backend

Checklist:
- Auth + SSO + profile umum.
- Internal API.
- Secure file endpoint.
- Deadline commands.
- Notification recipients.

Target:
- Backend support untuk UI Blade stabil.
- Tidak ada leftover legacy reactive action dependency di backend.

### Fase 5 — Handoff ke Frontend

Checklist:
- Jalankan backend targeted suite.
- Catat route/action yang sudah dipastikan aman.
- Beri daftar frontend dependencies: route names, expected flash keys, expected validation errors.
- Tandai modul yang frontend agent boleh lanjutkan.

---

## 8. Sinkronisasi Dengan Agent Frontend

Gunakan aturan berikut:

| Area | Owner Utama | Boleh disentuh backend agent? | Boleh disentuh frontend agent? |
|---|---|---|---|
| Controller/service/policy/repository | Backend | Ya | Tidak, kecuali koordinasi |
| Route names dan method HTTP | Backend | Ya | Tidak, kecuali sudah disepakati |
| Blade form/action/method field | Frontend | Hanya jika perlu test/backend hook | Ya |
| Alpine/JS/CSS/Metronic component | Frontend | Tidak | Ya |
| Test Feature backend | Backend | Ya | Boleh jika test frontend butuh fixture |
| MetronicFrontendTest | Frontend | Tidak dulu | Ya |
| Dokumentasi status | Keduanya | Ya | Ya |

Sinkronisasi wajib dilakukan bila:
- Frontend agent mengubah form action, method, field name, atau route name.
- Backend agent mengubah validation rule, flash message, redirect route, atau response JSON.
- Salah satu agent menemukan P0/P1 yang menyentuh workflow role lain.
- Frontend agent mengubah posisi/visibility tombol lock data pesantren.

---

## 9. Format Laporan Tiap Batch

Setiap batch perbaikan role harus ditutup dengan format ini:

```text
Role:
Modul:
Prioritas:
File backend yang disentuh:
File test yang disentuh:
Perubahan behavior:
Risiko frontend:
Test yang dijalankan:
Status:
Next sync note untuk frontend agent:
```

---

## 10. Backlog Awal

| ID | Role | Modul | Prioritas | Task | Status |
|---|---|---|---|---|---|
| BE-001 | Admin/Super Admin | Notifications | P2 | Audit dan putuskan apakah super admin harus menerima semua notifikasi admin | Pending |
| BE-002 | Admin/Super Admin | Banding | P0 | Tambah HTTP tests untuk assign/reassign/decision banding | Pending |
| BE-003 | Admin/Super Admin | Akreditasi | P0 | Tambah HTTP tests untuk open review, approve/reject berkas, approve/reject final | Pending |
| BE-004 | Asesor | Akreditasi | P0 | Tambah HTTP tests untuk schedule, reject document, accept perbaikan, confirm visitasi | Pending |
| BE-005 | Asesor | Scoring | P0 | Tambah HTTP/API tests untuk save NA/NK/finalize scoring | Pending |
| BE-006 | Pesantren | Profile/IPM/SDM/EDPM | P0 | Tambah HTTP tests locked/unlocked perbaikan section | Pending |
| BE-007 | Pesantren | Akreditasi | P0 | Tambah HTTP tests create/delete/cancel/banding/upload kartu kendali | Pending |
| BE-008 | Shared | Internal API | P1 | Audit `_api/*` response scoping per role | Pending |
| BE-009 | Shared | Secure Files | P0 | Tambah HTTP tests `secure/asesor-docs` owner/admin/super admin/forbidden | Pending |
| BE-010 | Shared | SSO/Auth/Profile | P1 | Audit HTTP coverage auth, SSO linkage, profile update | Pending |
| BE-011 | Docs | Sync | P3 | Update dokumen ini setelah setiap batch role selesai | Ongoing |
| BE-012 | Admin/Super Admin/Pesantren | Data Lock Policy | P1 | Revisi kebijakan manual global lock menjadi workflow-managed/partial unlock atau emergency override auditable | Pending |
| BE-013 | Pesantren | Partial Unlock HTTP | P0 | Perbaiki controller Profile/IPM/SDM/EDPM agar partial unlock dari rejection bisa berjalan di HTTP Blade | Pending |
| BE-014 | Pesantren | Submit Perbaikan | P0 | Tambah route/controller/test untuk Pesantren men-submit perbaikan setelah section direvisi | Pending |
| BE-015 | Pesantren | Status Perbaikan | P1 | Ubah filter menu perbaikan agar membaca active rejection, bukan hanya status `-1` | Pending |
| BE-016 | Pesantren | Akreditasi List Sorting | P2 | Tambah allowlist sort field untuk list akreditasi Pesantren | Pending |
| BE-017 | Asesor | Jadwal Visitasi | P0 | Selaraskan flag `canScheduleVisitasi` dengan status Review Asesor `4` dan tambah regression HTTP/view | Pending |
| BE-018 | Asesor | Reject Dokumen | P1 | Selaraskan flag reject dokumen dengan status service yang valid dan tambah regression | Pending |
| BE-019 | Asesor | Workflow HTTP Actions | P1 | Tambah HTTP tests untuk schedule/reject/accept/confirm/finalize/save NA/NK/upload laporan | Pending |
| BE-020 | Asesor | Profile Upload | P1 | Tambah rollback file baru jika update profile asesor gagal | Pending |
| BE-021 | Asesor | Assessment List Sorting | P2 | Tambah allowlist sort field untuk list tugas asesor | Pending |
| BE-022 | Asesor | Password Profile Policy | P2 | Putuskan kebijakan password di profile asesor: hapus/redirect ke profile umum atau tambah validasi kuat | Pending |
| BE-023 | Admin | Validasi Admin NV | P0 | Tambah kontrak reason untuk NV berbeda dari NK dan regression HTTP finalisasi NV | Pending |
| BE-024 | Admin | Workflow HTTP Actions | P1 | Tambah HTTP tests untuk open review, approve/reject berkas, save/finalize NV, approve/reject final, reschedule, reassign | Pending |
| BE-025 | Admin | Storage Rollback | P1 | Bersihkan file sertifikat SK/master dokumen jika service/DB update gagal | Pending |
| BE-026 | Admin | Trash Permission | P1 | Pisahkan permission restore/force-delete dari view dan tambah Gate/controller tests | Pending |
| BE-027 | Admin | Failed Notifications Permission | P1 | Gunakan permission retry/dismiss yang eksplisit dan tambah HTTP regression | Pending |
| BE-028 | Admin | Asesor Management Filter | P2 | Selaraskan filter key controller-service-repository untuk status/peran/penugasan | Pending |
| BE-029 | Admin | Admin List Sorting | P2 | Tambah allowlist sort field untuk semua list/export Admin | Pending |
| BE-030 | Admin/Super Admin | Role Mutation Policy | P1 | Konsistenkan mutasi role sebagai super-admin-only atau dokumenkan delegasi `master.role` | Pending |
| BE-031 | Admin/Frontend Sync | Trash Route Contract | P1 | Sinkronkan route trash frontend dengan backend atau ubah backend route beserta HTTP tests | Pending |
| BE-032 | Super Admin | Canonical Role Protection | P1 | Cegah delete/rename berbahaya pada role inti id `1..4` dan tambah regression | Pending |
| BE-033 | Super Admin | Role Management HTTP | P1 | Tambah HTTP tests role CRUD dan permission matrix save untuk super admin vs non-super-admin | Pending |
| BE-034 | Super Admin | Role Sorting | P2 | Tambah allowlist sort field pada RoleRepository/list role sistem | Pending |
| BE-035 | Super Admin/Admin | Account Permission Granularity | P2 | Putuskan permission update/unlink SSO akun: tambah permission granular atau rename menjadi `account.manage` | Pending |

---

## 11. Catatan Untuk Frontend Agent

Backend route names saat ini menjadi kontrak utama untuk Blade form:

- Admin akreditasi: `admin.akreditasi*`, `admin.akreditasi-detail*`
- Admin banding: `admin.banding*`, `admin.banding-detail`
- Asesor akreditasi: `asesor.akreditasi*`, `asesor.akreditasi-detail`
- Pesantren akreditasi: `pesantren.akreditasi*`, `pesantren.akreditasi-detail`
- Internal API: `api.sidebar-badges`, `api.notifications*`, `api.onboarding*`

Jika frontend mengganti field name form, backend tests perlu ikut diperbarui. Jika backend mengganti validation key atau route redirect, frontend perlu menyesuaikan form dan flash handling.

Catatan khusus lock data pesantren:

- Jangan jadikan tombol lock global sebagai aksi utama admin/super admin.
- Jika tombol masih dipertahankan sementara, tampilkan sebagai aksi administratif sekunder.
- Jika frontend ingin menyembunyikan tombol lock global, sinkronkan dulu dengan backend karena route `admin.pesantren.toggle-lock` dan `admin.akreditasi-detail.toggle-lock` masih ada.

Catatan khusus Admin:

- Route trash backend saat ini bukan `/admin/trash/{id}/restore`; gunakan `POST admin.trash.restore` dan `POST admin.trash.force-delete` dengan field `id`, kecuali backend nanti ikut diubah.
- Failed notification action tetap `POST admin.failed-notifications.retry` dan `POST admin.failed-notifications.dismiss`, tetapi permission backend akan diperketat.
- Form finalisasi NV Admin perlu menunggu kontrak reason dari `ADM-001` jika frontend ingin mendukung NV berbeda dari NK.
- Filter daftar asesor jangan dianggap selesai sebelum backend menutup `ADM-007`.

Catatan khusus Super Admin:

- Role id `4` sengaja tidak muncul di permission matrix; jangan buat UI yang memberi kesan permission Super Admin bisa dicabut dari matrix.
- Tombol hapus/edit role inti sebaiknya nonaktif sampai backend menutup `SA-003`.
- Jika frontend menampilkan badge/notifikasi workflow untuk Super Admin, sinkronkan dengan backend karena penerima notifikasi masih banyak `role_id = 1`.

---

## 12. Log Perubahan

| Tanggal | Perubahan |
|---|---|
| 8 Jun 2026 | Dokumen plan backend dibuat. Memetakan role super admin, admin, asesor, pesantren, shared backend, prioritas P0-P3, backlog awal, dan aturan sinkronisasi dengan agent frontend. |
| 8 Jun 2026 | Menambahkan rekomendasi lock data pesantren: pertahankan lock otomatis pengajuan, batasi/hapus manual global lock admin/super admin, utamakan partial unlock berbasis perbaikan, dan tambahkan backlog BE-012. |
| 8 Jun 2026 | Menambahkan hasil audit backend scope Pesantren: route/menu utama aman untuk tenant boundary, tetapi ditemukan gap P0 pada partial unlock HTTP dan submit perbaikan, serta P1 pada filter Status Perbaikan. |
| 8 Jun 2026 | Menambahkan hasil audit backend scope Asesor: assignment boundary aman, tetapi ditemukan gap P0 pada flag Jadwal Visitasi, P1 pada reject dokumen/HTTP coverage/profile upload rollback, dan P2 pada sort/password policy. |
| 8 Jun 2026 | Menambahkan hasil audit backend scope Admin: service inti hijau 961 test, tetapi ditemukan gap P0 pada kontrak reason NV, P1 pada permission mutasi trash/failed notification/role, storage rollback, HTTP coverage, dan sinkron route trash frontend. |
| 8 Jun 2026 | Menambahkan hasil audit backend scope Super Admin: akses/bypass/menu/policy aman dan 294 test hijau, tetapi ditemukan gap P1 pada penerima notifikasi Super Admin, proteksi role inti, HTTP coverage role management, dan permission governance. |

