# PesantrenMu — Sistem Penjaminan Mutu Pesantren

Aplikasi akreditasi pesantren Muhammadiyah berbasis web yang dikembangkan oleh LabMu untuk LP2M. Mengelola alur pengajuan akreditasi dari pesantren, penilaian oleh asesor, hingga validasi dan penerbitan SK oleh admin.

## Stack

- **Backend**: Laravel 12 + PHP 8.2
- **Frontend**: Blade + controller + Alpine ringan + Metronic 8 UI
- **Database**: MySQL 8+
- **Queue**: Laravel Queue (database driver, async notifications)
- **Cache**: Redis (production) / file (local)
- **Auth**: Laravel built-in + SSO bridge LP2M

## Alur Akreditasi

```
Pengajuan (6) → Verifikasi Berkas (5) → Review Asesor (4) → Visitasi (3)
→ Penilaian Pasca Visitasi (2) → Validasi Admin (1) → Hasil Akhir (0/-1)
→ Banding (-2, jika ditolak final)
```

| Status | Label | Aktor |
|--------|-------|-------|
| 6 | Pengajuan | Pesantren submit |
| 5 | Verifikasi Berkas | Admin review kelengkapan, assign asesor |
| 4 | Review Asesor | Asesor review substansi |
| 3 | Visitasi | Ketua Kelompok jadwalkan & konfirmasi |
| 2 | Penilaian Pasca Visitasi | Asesor input NA1/NA2/NK, upload laporan |
| 1 | Validasi Admin | Admin input NV, terbitkan SK |
| 0 | Terakreditasi | Hasil akhir (Peringkat A/B/C) |
| -1 | Ditolak Final | Pesantren bisa ajukan banding |
| -2 | Banding | Admin review ulang |

## Role

| role_id | Nama | Akses |
|---------|------|-------|
| 1 | Admin | Verifikasi berkas, assign asesor, validasi akhir, terbitkan SK |
| 2 | Asesor | Review substansi, visitasi, input nilai (NA1/NA2/NK), upload laporan |
| 3 | Pesantren | Pengajuan akreditasi, upload dokumen, lihat hasil |
| 4 | Super Admin | Semua permission |

## Setup Lokal

### Prasyarat

- PHP 8.2+ dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- Node.js 18+ dan npm
- MySQL 8+
- Composer 2+

### Instalasi

```bash
# 1. Clone dan install dependencies
composer install
npm ci

# 2. Konfigurasi environment
cp .env.example .env
php artisan key:generate

# 3. Edit .env — set DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 4. Migrasi dan seed (local only)
php artisan migrate
php artisan db:seed

# 5. Storage symlink
php artisan storage:link

# 6. Build assets
npm run build
# atau untuk development:
npm run dev
```

### Akun Demo (local/testing only)

| Email | Password | Role |
|-------|----------|------|
| superadmin@spm.test | password | Super Admin |
| admin@spm.test | password | Admin |
| pesantren@spm.test | password | Pesantren |
| asesor@spm.test | password | Asesor |

> Akun demo hanya di-seed di environment `local` dan `testing`.

## Menjalankan Test

```bash
# Semua test
php artisan test

# Per module
php artisan test --filter=AkreditasiWorkflow
php artisan test --filter=Notification
php artisan test --filter=Auth
```

Test menggunakan SQLite in-memory (konfigurasi di `phpunit.xml`).

## Queue Worker

Notifikasi berjalan async via queue. Jalankan worker:

```bash
php artisan queue:work --queue=notifications,default --tries=3
```

Notifikasi yang gagal tercatat di Failed Notification Dashboard (`/admin/failed-notifications`).

## Scheduler

```bash
# Tambahkan ke crontab server:
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled commands:

| Command | Fungsi |
|---------|--------|
| `banding:check-deadlines` | Auto-reject banding yang melewati batas waktu |
| `perbaikan:check-deadlines` | Auto-reject perbaikan yang expired |
| `reminders:asesor2` | Reminder untuk Anggota Kelompok |
| `akreditasi:check-deadlines` | Cek deadline akreditasi |
| `trash:purge` | Hapus permanen data di trash |

## Notifikasi

Sistem notifikasi mencakup seluruh alur akreditasi:

- Pesantren submit pengajuan → Admin
- Admin assign asesor → Ketua Kelompok + Anggota Kelompok
- Visitasi dijadwalkan → Pesantren + Anggota Kelompok + Admin
- Scoring selesai → Admin
- Paket asesor final submitted → Admin
- SK diterbitkan → Pesantren
- Penolakan (berkas/asesor/validasi) → Pesantren
- Banding submitted → Admin
- Banding diputuskan → Pesantren
- Deadline perbaikan mendekat → Pesantren

## Keamanan

- Multi-tenant: 7 Policy files, explicit `$fillable`, `Gate::authorize()`
- SSO: token encrypted di session, `sso_sync_role` flag per user
- Security headers: HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy
- Race condition protection: `lockForUpdate`, `DB::transaction`, unique indexes
- NV audit trail: perubahan NV dari default NK tercatat dengan alasan wajib

## Deploy Production

Lihat [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) untuk panduan lengkap.

## Dokumentasi

| Dokumen | Deskripsi |
|---------|-----------|
| [`docs/business-spec-flow-lp2m-v1.md`](docs/business-spec-flow-lp2m-v1.md) | Business spec flow akreditasi LP2M |
| [`docs/architecture.md`](docs/architecture.md) | Arsitektur sistem |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Production deploy runbook |
| [`docs/local-setup.md`](docs/local-setup.md) | Setup lokal detail |
| [`docs/performance-optimization.md`](docs/performance-optimization.md) | Optimasi performa |
| [`docs/auth.md`](docs/auth.md) | Autentikasi dan SSO |
| [`docs/README.md`](docs/README.md) | Indeks dokumen current dan historical |

