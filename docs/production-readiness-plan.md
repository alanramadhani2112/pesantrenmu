# Production Readiness Plan — SPM LP2M

Tanggal: 25 Mei 2026

Status: Draft eksekusi untuk persiapan naik production.

Scope: Aplikasi akreditasi pesantren LP2M yang dikembangkan oleh LabMu. Dokumen ini menjadi rencana kerja lintas business flow, UI/UX, performa, keamanan, data, dan deployment.

## Tujuan

Menjadikan sistem layak production, bukan hanya berjalan di lokal. Fokus utamanya adalah memastikan proses bisnis LP2M terakomodir end-to-end, tampilan konsisten dan profesional, performa stabil, data aman, serta deployment dapat diulang tanpa langkah manual yang rapuh.

## Prinsip Keputusan

- Setiap fitur harus mengikuti business flow LP2M yang sudah disepakati.
- Validasi penting wajib ada di server-side, bukan hanya di UI.
- UI memakai reusable Blade component dan gaya Metronic yang konsisten.
- Performa harus diukur dengan data: query, render Livewire, asset, dan cache.
- Production tidak boleh bergantung pada seed demo, debug mode, atau konfigurasi lokal.
- Setiap fase selesai hanya jika test, build, dan QA checklist terkait sudah hijau.

## Ringkasan Prioritas

| Prioritas | Area | Target |
| --- | --- | --- |
| P0 | Blocker production | Flow bisnis, security, data integrity, deployment config |
| P1 | Stabilitas operasional | Performance pass, queue, scheduler, monitoring, backup |
| P2 | Kualitas pengalaman | Visual QA semua role, reusable UI, UX writing, empty state |
| P3 | Hardening lanjutan | E2E browser QA, observability, runbook incident |

## Phase 0 — Baseline dan Freeze Scope

Tujuan: mengunci baseline sebelum perbaikan production agar tidak ada perubahan liar yang sulit diaudit.

| Task | Output | Acceptance |
| --- | --- | --- |
| Audit branch dan status git | Branch kerja bersih dan jelas | `git status` bersih sebelum mulai fase berikutnya |
| Catat versi stack | PHP, Laravel, Node, MySQL, package utama terdokumentasi | Versi tercatat di dokumen readiness/deployment |
| Jalankan baseline test | Daftar test pass/fail awal | Failure yang tersisa dikategorikan P0/P1/P2 |
| Freeze business flow | Flow LP2M final menjadi acuan | Semua label dan step mengacu ke `business-spec-flow-lp2m-v1.md` |

Verifikasi:

```bash
php artisan test --no-ansi
npm run build
php artisan view:cache --no-ansi
```

## Phase 1 — Business Flow Compliance

Tujuan: memastikan sistem mengikuti alur LP2M dari pengajuan sampai hasil akhir dan banding.

Flow acuan:

```text
Pengajuan
-> Review Awal
-> Review Asesor
-> Visitasi
-> Penilaian Pasca Visitasi
-> Validasi Admin
-> Hasil Akhir
-> Banding bila ditolak final
```

Checklist:

| Area | Rule wajib | Acceptance |
| --- | --- | --- |
| Pengajuan pesantren | Profil, IPM, EDPM/IPR, SDM, dan dokumen minimum tervalidasi | Submit final gagal dengan alert jelas jika belum lengkap |
| Review awal admin | Admin bisa approve ke penugasan asesor atau kembalikan perbaikan | Tidak membuka banding pada revisi administratif |
| Review asesor | Ketua/Anggota Kelompok review berkas sebelum visitasi | Penolakan soft punya catatan bagian yang perlu diperbaiki |
| Visitasi | Visitasi terjadi sebelum input nilai asesor | Input nilai terkunci sampai visitasi dikonfirmasi selesai |
| Penilaian pasca visitasi | Asesor 1 dan Asesor 2 input nilai masing-masing secara paralel | Nilai Kelompok baru terbuka setelah Nilai Ketua dan Nilai Anggota final |
| Validasi admin | Nilai Verifikasi default mirror dari Nilai Kelompok dan editable | Perubahan NV tercatat di audit trail |
| Hasil akhir | Pesantren hanya melihat nilai akhir, peringkat, SK, masa berlaku, sertifikat, dan rekomendasi | NA/NK/NV mentah tidak tampil untuk pesantren |
| Banding | Banding hanya setelah Ditolak Final | Banding diterima kembali ke Validasi Admin |

Test target:

```bash
php artisan test tests\Feature\AkreditasiWorkflow tests\Unit\Workflow --no-ansi
php artisan test tests\Feature\BusinessStatusMappingTest.php --no-ansi
php artisan test tests\Feature\NvAuditTrailTest.php --no-ansi
```

## Phase 2 — Security dan Permission Hardening

Tujuan: memastikan setiap role hanya bisa melihat dan melakukan aksi yang sesuai.

Checklist:

| Area | Task | Acceptance |
| --- | --- | --- |
| Route protection | Audit semua route admin, asesor, pesantren, super admin | Semua route punya middleware/gate/policy yang sesuai |
| Server-side authorization | Audit semua action Livewire/service | Tombol disembunyikan bukan satu-satunya pengaman |
| Tenant isolation | Pesantren tidak bisa akses data pesantren lain | Test akses silang menghasilkan 403/not found |
| File upload | Validasi MIME, ukuran, path, ownership | Tidak ada upload file executable atau akses file milik user lain |
| Production env | `APP_DEBUG=false`, session secure, env secret aman | Tidak ada stack trace atau secret bocor |
| RBAC | Modul hak akses super admin mempengaruhi UI dan server action | Permission update langsung tercermin pada akses |

Test target:

```bash
php artisan test tests\Feature\SecurityHeadersTest.php tests\Feature\Policies tests\Feature\SsoControllerTest.php --no-ansi
php artisan test tests\Feature\RoleServiceTest.php tests\Feature\UserServiceTest.php --no-ansi
```

## Phase 3 — Data Integrity dan MySQL Readiness

Tujuan: memastikan schema, relasi, dan file storage aman untuk data production.

Checklist:

| Area | Task | Acceptance |
| --- | --- | --- |
| Migration | Fresh migrate MySQL dan migrate dari DB existing | Tidak ada migration gagal |
| Foreign key/index | Audit relasi utama dan query halaman berat | Index tersedia untuk filter/search penting |
| UUID | Pastikan route public/detail memakai UUID yang unik | Tidak ada duplicate UUID |
| Soft delete/restore | Akreditasi, user, pesantren, dokumen aman dihapus/restore | Cascade restore dan force delete teruji |
| File storage | Audit orphan file dan replacement file | File lama terhapus saat diganti, file aktif tidak hilang |
| Backup/restore | Simulasi backup database dan storage | Restore bisa dipakai membuka halaman detail |

Test target:

```bash
php artisan migrate:fresh --seed --no-ansi
php artisan test tests\Feature\Trash tests\Feature\UserModelCascadeTest.php --no-ansi
```

## Phase 4 — Performance Pass

Tujuan: mencari penyebab aplikasi terasa berat dan memperbaikinya berdasarkan pengukuran.

Checklist:

| Area | Task | Acceptance |
| --- | --- | --- |
| Query N+1 | Profiling halaman detail dan list utama | Tidak ada query berulang yang jelas bisa eager load |
| Pagination | Semua table besar memakai pagination server-side | Halaman list tidak load seluruh dataset |
| Livewire render | Audit component yang re-render berlebihan | Action kecil tidak memicu render penuh yang tidak perlu |
| Asset | Trim asset Metronic yang tidak dipakai | Bundle JS/CSS tetap terkendali setelah build |
| Cache | Config, route, event, view cache production | `php artisan optimize` aman dijalankan |
| Queue | Notifikasi dan proses berat masuk queue | Request user tidak menunggu kerja background |

Halaman prioritas profiling:

- `/admin/akreditasi`
- `/admin/master-edpm`
- `/admin/pesantren`
- `/admin/asesor`
- `/pesantren/akreditasi`
- `/pesantren/profile`
- `/pesantren/ipm`
- `/pesantren/sdm`
- `/asesor/akreditasi`
- detail akreditasi admin, asesor, dan pesantren

Verifikasi:

```bash
php artisan optimize
npm run build
php artisan test tests\Feature\MetronicFrontendTest.php --no-ansi
```

## Phase 5 — Reusable UI dan Visual QA

Tujuan: membuat semua halaman terasa konsisten seperti dashboard enterprise, bukan kumpulan markup manual.

Standar komponen:

| Komponen | Standar |
| --- | --- |
| Table/list | Header, search, filter, action, badge, pagination konsisten |
| Button | Icon + label untuk aksi penting, icon-only untuk aksi compact dengan tooltip |
| Badge | Status memakai varian yang konsisten dan densitas stabil |
| Form field | Label, helper, error, required state, spacing konsisten |
| Modal | Header/body/footer reusable dan tidak terclip |
| Upload | File upload reusable dengan validasi dan state file lama |
| Stepper | Mengikuti struktur Metronic dan flow LP2M |
| Empty state | Menjelaskan kondisi kosong dan aksi berikutnya |
| Alert | Danger/info/success sesuai konteks, tidak hanya toast |

Role QA:

| Role | Halaman prioritas |
| --- | --- |
| Admin | Akreditasi, master EDPM, pesantren, asesor, kategori dokumen, accounts, roles |
| Asesor | Daftar akreditasi, detail akreditasi, input nilai, laporan visitasi |
| Pesantren | Akreditasi, profil, IPM, SDM, EDPM/IPR, kartu kendali, hasil akhir, banding |
| Super Admin | Manajemen hak akses, akun, role permission |

Acceptance:

- Tidak ada modal terclip.
- Tidak ada action dropdown tertutup table karena z-index.
- Header, filter, search, dan export tidak berubah-ubah antar halaman.
- Button density konsisten.
- Font Inter dan hirarki dashboard enterprise konsisten.
- Tidak ada copy di luar konteks LP2M dan LabMu.

## Phase 6 — E2E Regression dan Browser QA

Tujuan: memastikan flow produksi utama benar-benar bisa dijalankan dari browser.

Scenario wajib:

1. Pesantren melengkapi profil, IPM, EDPM/IPR, SDM.
2. Pesantren submit pengajuan.
3. Admin review awal dan assign Ketua/Anggota Kelompok.
4. Asesor review berkas dan menjadwalkan visitasi.
5. Ketua Kelompok konfirmasi visitasi selesai.
6. Ketua dan Anggota mengisi nilai masing-masing.
7. Nilai Kelompok terbuka setelah nilai individu final.
8. Pesantren upload kartu kendali.
9. Asesor upload laporan individu dan kelompok.
10. Admin validasi Nilai Verifikasi.
11. Admin finalisasi hasil, SK, masa berlaku, sertifikat.
12. Pesantren melihat hasil akhir dan rekomendasi.
13. Jika ditolak final, pesantren bisa banding.

Acceptance:

- Tidak ada error console kritis.
- Tidak ada halaman blank.
- Semua aksi destructive punya confirmation.
- Semua form wajib punya error danger ketika invalid.
- Semua upload gagal/berhasil memberi feedback jelas.

## Phase 7 — Deployment dan Operational Runbook

Tujuan: production bisa dideploy, dimonitor, dan dipulihkan.

Checklist:

| Area | Task | Acceptance |
| --- | --- | --- |
| Environment | `.env.production` template final | Tidak ada nilai local/test di production |
| Deployment | Runbook deploy manual/Docker final | Deploy bisa diulang dari clean server |
| Queue worker | Supervisor/systemd aktif | Queue diproses otomatis |
| Scheduler | Cron Laravel aktif | Deadline/reminder berjalan |
| Monitoring | Sentry/log alert aktif | Error production terdeteksi |
| Backup | Backup DB dan storage terjadwal | Restore drill berhasil |
| Rollback | Strategi rollback release | Bisa kembali ke release sebelumnya |

Command deploy minimum:

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
npm ci --no-audit --no-fund
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

## Definition of Done Production

Sistem boleh naik production jika semua poin berikut terpenuhi:

- Full test suite hijau atau sisa failure terdokumentasi dan bukan blocker.
- Business flow LP2M end-to-end lolos browser QA.
- Tidak ada route/action penting tanpa authorization server-side.
- Semua upload dokumen punya validasi, ownership check, dan storage path aman.
- Halaman prioritas tidak lambat secara lokal maupun staging.
- UI utama semua role memakai reusable component dan konsisten.
- `APP_DEBUG=false`, session secure, queue worker, scheduler, cache, dan monitoring aktif.
- Migration MySQL production berhasil.
- Backup dan restore sudah diuji minimal sekali.
- Dokumentasi deployment dan rollback tersedia.

## Urutan Eksekusi Rekomendasi

1. Phase 0: Baseline dan freeze scope.
2. Phase 1: Business flow compliance.
3. Phase 4: Performance pass awal pada halaman berat.
4. Phase 2: Security dan permission hardening.
5. Phase 3: Data integrity dan MySQL readiness.
6. Phase 5: Reusable UI dan visual QA per role.
7. Phase 6: E2E browser QA.
8. Phase 7: Deployment dan operational runbook.

## Dokumen Terkait

- `docs/business-spec-flow-lp2m-v1.md`
- `docs/production-readiness-audit.md`
- `docs/pesantren-role-production-readiness.md`
- `docs/ui-qa-checklist.md`
- `docs/DEPLOYMENT.md`
- `docs/lp2m-workflow-stepper-update.md`
- `docs/post-visitasi-documents-implementation.md`
