# Next Plan — SPM Fix Production Readiness

Tanggal: 30 Mei 2026
Status: Siap masuk fase QA & deployment

---

## Status Saat Ini

| Area | Status |
|------|--------|
| Business flow LP2M V1 | ✅ 100% compliant |
| Notification flow | ✅ 16/16 triggers |
| Security & data integrity | ✅ Semua P0/P1 resolved |
| UI/UX landing & login | ✅ Redesigned |
| Icon system | ✅ Semua invalid icons fixed |
| Dead code cleanup | ✅ Done |
| Automated tests | ✅ 63 workflow tests pass |
| README | ✅ Updated |
| Git | ✅ Pushed ke pesantrenmu/main |

---

## Phase 1 — Visual QA Menyeluruh

**Target**: Semua halaman utama per role berfungsi dan tampil benar secara visual.

### 1.1 Admin
- [ ] Dashboard — stat cards, quick actions, recent activity
- [ ] Akreditasi list — filter, search, action menu, catatan modal
- [ ] Akreditasi detail — semua tab (berkas, asesor, visitasi, penilaian, validasi)
- [ ] Assign asesor — form, validasi, notifikasi
- [ ] Validasi akhir — input NV, alasan perubahan, terbitkan SK
- [ ] Daftar Pesantren — list, detail, search
- [ ] Daftar Asesor — list, detail, search
- [ ] Banding — list, detail, putuskan banding
- [ ] Master EDPM — list komponen
- [ ] Failed Notifications — dashboard, retry
- [ ] Trash — list, restore, purge

### 1.2 Asesor
- [ ] Dashboard — tugas aktif, stat cards
- [ ] Daftar Tugas — filter, search
- [ ] Review Berkas — approve/reject dengan catatan
- [ ] Penjadwalan Visitasi — form jadwal, konfirmasi selesai
- [ ] Input Nilai — NA1/NA2/NK per komponen, catatan butir
- [ ] Laporan Visitasi — upload laporan individu/kelompok
- [ ] Final Submit — submit paket asesor
- [ ] Profil Asesor — edit data pribadi

### 1.3 Pesantren
- [ ] Dashboard — status pengajuan, quick actions
- [ ] Profil Pesantren — edit, upload dokumen NSP
- [ ] IPM — isi data, upload dokumen
- [ ] Data SDM — tambah/edit SDM
- [ ] EDPM/IPR — isi per komponen, upload kartu kendali
- [ ] Pengajuan Akreditasi — submit, cancel, lihat status
- [ ] Kartu Kendali — upload setelah visitasi
- [ ] Hasil Akhir — lihat nilai, peringkat, SK, sertifikat, catatan rekomendasi
- [ ] Banding — submit banding, lihat status

### 1.4 Responsive & Cross-browser
- [ ] Mobile (375px) — semua halaman utama
- [ ] Tablet (768px) — dashboard, list, detail
- [ ] Desktop (1280px+) — semua halaman

---

## Phase 2 — Production Preparation

### 2.1 Environment Config
- [ ] Buat `.env.production` dari `.env.example`
- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Set `APP_URL` ke domain production
- [ ] Konfigurasi database production (host, user, password, db name)
- [ ] Konfigurasi mail (SMTP/Mailgun/SES)
- [ ] Konfigurasi queue driver (`QUEUE_CONNECTION=database`)
- [ ] Konfigurasi SSO (`SSO_BASE_URL`, `SSO_CLIENT_ID`, `SSO_CLIENT_SECRET`)
- [ ] Konfigurasi Sentry (`SENTRY_LARAVEL_DSN`)
- [ ] Set `TRUSTED_PROXIES` sesuai infrastruktur
- [ ] Set `SESSION_SECURE_COOKIE=true`

### 2.2 Docker
- [ ] `docker build` — pastikan build sukses tanpa error
- [ ] `docker compose up` — pastikan semua service berjalan
- [ ] Verifikasi PHP-FPM + Nginx berjalan
- [ ] Verifikasi queue worker berjalan via supervisor
- [ ] Verifikasi scheduler berjalan via cron
- [ ] Test health check endpoint

### 2.3 Database
- [ ] Jalankan `php artisan migrate --force` di production
- [ ] Seed roles: `php artisan db:seed --class=RoleSeeder`
- [ ] Seed permissions: `php artisan db:seed --class=PermissionSeeder`
- [ ] Seed master EDPM: `php artisan db:seed --class=MasterEdpmSeeder`
- [ ] Seed document categories: `php artisan db:seed --class=DocumentCategorySeeder`
- [ ] **JANGAN** jalankan `DatabaseSeeder` penuh (akan seed demo accounts)

### 2.4 Storage & Assets
- [ ] `php artisan storage:link`
- [ ] Set permission `storage/` dan `bootstrap/cache/` writable
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `npm run build` — pastikan assets ter-build

---

## Phase 3 — Staging Deployment

### 3.1 Deploy
- [ ] Push ke branch `staging` atau tag `v1.0.0-rc`
- [ ] Deploy ke server staging
- [ ] Jalankan migration & seed
- [ ] Verifikasi semua service berjalan

### 3.2 Smoke Test di Staging
- [ ] Login semua role (admin, asesor, pesantren)
- [ ] Submit pengajuan akreditasi (pesantren)
- [ ] Assign asesor (admin)
- [ ] Input nilai (asesor)
- [ ] Validasi akhir + terbitkan SK (admin)
- [ ] Verifikasi notifikasi terkirim
- [ ] Verifikasi queue worker memproses jobs
- [ ] Verifikasi Sentry menerima error (trigger test error)

---

## Phase 4 — Production Deployment

### 4.1 Deploy
- [ ] Tag release `v1.0.0`
- [ ] Deploy ke production server
- [ ] Jalankan migration & seed (roles, permissions, master data)
- [ ] Verifikasi semua service berjalan
- [ ] Verifikasi SSL/HTTPS aktif

### 4.2 Post-Deploy Verification
- [ ] Login semua role
- [ ] Smoke test alur pengajuan
- [ ] Verifikasi notifikasi email terkirim
- [ ] Verifikasi Sentry aktif
- [ ] Monitor queue worker 24 jam pertama

---

## Phase 5 — Post-Deploy

### 5.1 User Acceptance Testing
- [ ] Demo ke stakeholder LP2M
- [ ] Walkthrough alur akreditasi end-to-end
- [ ] Kumpulkan feedback
- [ ] Prioritaskan bug fixes dari feedback

### 5.2 Dokumentasi
- [ ] User manual Admin (PDF/Notion)
- [ ] User manual Asesor (PDF/Notion)
- [ ] User manual Pesantren (PDF/Notion)
- [ ] Operational runbook (backup, restore, queue monitoring, incident response)

### 5.3 Monitoring
- [ ] Setup Sentry alerts (error rate threshold)
- [ ] Setup uptime monitoring
- [ ] Setup queue monitoring (failed jobs alert)
- [ ] Backup database schedule

---

## Estimasi Waktu

| Phase | Estimasi |
|-------|----------|
| Phase 1 — Visual QA | 2-3 hari |
| Phase 2 — Production Prep | 1-2 hari |
| Phase 3 — Staging Deploy | 1 hari |
| Phase 4 — Production Deploy | 1 hari |
| Phase 5 — Post-Deploy | Ongoing |

**Total estimasi ke production: ~5-7 hari kerja**
