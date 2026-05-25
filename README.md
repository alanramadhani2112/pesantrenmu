# SPM Fix — Sistem Penjaminan Mutu Pesantren

Aplikasi akreditasi pesantren Muhammadiyah berbasis web. Mengelola alur pengajuan akreditasi dari pesantren, penilaian oleh asesor, hingga validasi dan penerbitan SK oleh admin.

## Stack

- **Backend**: Laravel 12 + PHP 8.2
- **Frontend**: Livewire Volt + Blade + Metronic UI
- **Database**: MySQL 8+
- **Queue**: Laravel Queue (database driver)
- **Cache**: Redis (production) / file (local)

## Role

| role_id | Nama | Akses |
|---------|------|-------|
| 1 | Admin | Semua fitur admin, approve/reject akreditasi |
| 2 | Asesor | Penilaian NA, NK, visitasi |
| 3 | Pesantren | Pengajuan akreditasi, upload dokumen |
| 4 | Super Admin | God mode, semua permission |

## Status Akreditasi

| Status | Label |
|--------|-------|
| 6 | Pengajuan |
| 5 | Verifikasi Berkas |
| 4 | Assessment |
| 3 | Visitasi |
| 2 | Pasca Visitasi |
| 1 | Validasi Admin |
| 0 | Selesai |
| -1 | Ditolak |
| -2 | Banding |

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

### Akun Demo (local only)

| Email | Password | Role |
|-------|----------|------|
| admin@spm.test | password | Admin |
| pesantren@spm.test | password | Pesantren |
| asesor@spm.test | password | Asesor |

> ⚠️ Akun demo **tidak** di-seed di environment production/staging.

## Menjalankan Test

```bash
php artisan test
# atau spesifik:
php artisan test tests/Feature/Pesantren/
```

Test menggunakan SQLite in-memory (konfigurasi di `phpunit.xml`).

## Queue Worker

Notifikasi berjalan async via queue. Jalankan worker:

```bash
php artisan queue:work --queue=notifications,default --tries=3
```

## Scheduler

```bash
# Tambahkan ke crontab server:
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled commands: `trash:purge`, `akreditasi:check-deadlines`, `banding:check-deadlines`, `perbaikan:check-deadlines`, `reminders:asesor2`.

## Deploy Production

Lihat [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) untuk panduan lengkap.

## Dokumentasi

- [`docs/business-spec-flow-lp2m-v1.md`](docs/business-spec-flow-lp2m-v1.md) — Business spec flow akreditasi LP2M
- [`docs/post-visitasi-documents-implementation.md`](docs/post-visitasi-documents-implementation.md) — Implementasi dokumen wajib pasca visitasi
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) — Production deploy runbook
- [`docs/local-setup.md`](docs/local-setup.md) — Setup lokal detail
- [`docs/architecture.md`](docs/architecture.md) — Arsitektur sistem
- [`docs/project-work-report.md`](docs/project-work-report.md) — Kronologi pengerjaan project
- [`.kiro/specs/`](.kiro/specs/) — Spec fitur (requirements, design, tasks)
