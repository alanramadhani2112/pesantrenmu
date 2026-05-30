# Production Readiness Audit - SPM LP2M

Tanggal Checkpoint: 27 Mei 2026

Status: Production candidate dengan checklist eksternal tersisa

Scope: Sistem akreditasi pesantren LP2M yang dikembangkan oleh LabMu. Audit ini menggantikan catatan lama 17 Mei 2026 dan memisahkan kondisi repository yang sudah dijaga oleh code/test dari pekerjaan deployment yang wajib dilakukan di server.

## Ringkasan Eksekutif

Secara repository, aplikasi sudah berada di jalur production-candidate: environment template sudah production-like, middleware hardening sudah aktif, health endpoint tersedia, script cache production tersedia, dan performa/frontend sudah mulai dikunci oleh test.

Namun status ini belum berarti boleh langsung go-live dari laptop lokal. Production masih membutuhkan konfigurasi eksternal di server: domain HTTPS final, database MySQL production, SMTP, queue worker, scheduler, Sentry/log monitoring, backup database/storage, dan verifikasi full regression di staging.

## Status Repository

| Area | Status | Catatan |
| --- | --- | --- |
| Laravel runtime | Siap dijaga | `.env.example` memakai `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`. |
| Database | Siap MySQL | Template memakai `DB_CONNECTION=mysql`; migration tetap wajib dijalankan di server dengan `php artisan migrate --force`. |
| Session | Siap production | `SESSION_DRIVER=database`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`. |
| Cache | Siap production-like | `CACHE_STORE=database`; Redis bisa menjadi upgrade berikutnya jika server sudah tersedia. |
| Queue | Perlu runtime server | `QUEUE_CONNECTION=database` sudah siap, tetapi `queue:work` harus dijalankan oleh Supervisor/systemd. |
| Scheduler | Perlu cron server | Scheduled command sudah terdaftar; server harus menjalankan `php artisan schedule:run` setiap menit. |
| Security headers | Aktif | `SecurityHeaders` dan `TrustProxies` terdaftar di `bootstrap/app.php`. |
| Health check | Aktif | Endpoint `/up` tersedia untuk load balancer/uptime monitor. |
| Asset/performance | Siap guard | `composer perf:cache` tersedia untuk cache config, route, event, view, dan optimized autoload. |
| Monitoring | Perlu konfigurasi | Package Sentry sudah ada; `SENTRY_LARAVEL_DSN` harus diisi di production. |

## Guard yang Sudah Dikunci

- `.env.example` sudah memakai default production-safe: `APP_DEBUG=false`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`.
- `TRUSTED_PROXIES` tersedia agar reverse proxy/load balancer production tidak memakai fallback lokal.
- Middleware `SecurityHeaders` memberi header dasar untuk response HTML/JSON tanpa mengganggu file download.
- Middleware `TrustProxies` membaca trusted proxy dari env.
- Endpoint `/up` tersedia untuk health check.
- Composer script `perf:cache` menjalankan cache production: config, route, event, view, dan autoload optimize.
- Composer script `prod:check` menjalankan guard readiness utama secara repeatable.

## Checklist Server yang Masih Wajib

| Item | Status Repo | Aksi di Server |
| --- | --- | --- |
| Domain HTTPS final | Belum bisa diverifikasi dari repo | Set `APP_URL=https://domain-production` dan pasang SSL. |
| App key | Harus unik per server | Jalankan `php artisan key:generate` sekali saat provisioning. |
| Database | Template MySQL tersedia | Buat database/user production, isi env, lalu `php artisan migrate --force`. |
| Storage | Link command terdokumentasi | Jalankan `php artisan storage:link`; pastikan permission folder storage. |
| Queue worker | Command terdokumentasi | Jalankan `php artisan queue:work database --queue=notifications,default --tries=3 --timeout=90`. |
| Scheduler | Command terdokumentasi | Tambahkan cron: `* * * * * cd /path/app && php artisan schedule:run >> /dev/null 2>&1`. |
| SMTP | Belum bisa diverifikasi dari repo | Set `MAIL_MAILER=smtp` dan kredensial provider email. |
| Sentry/log monitoring | Package tersedia | Set `SENTRY_LARAVEL_DSN`, sample rate, dan alerting. |
| Backup | Dokumen tersedia | Aktifkan backup database dan storage, lalu lakukan restore drill. |
| Cache production | Script tersedia | Jalankan `composer perf:cache` setelah deploy dan setelah perubahan config/route/view. |

## Perintah Deploy Minimum

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
npm ci --no-audit --no-fund
npm run build
php artisan migrate --force
php artisan storage:link
composer perf:cache
```

## Perintah Runtime Server

Queue worker:

```bash
php artisan queue:work database --queue=notifications,default --tries=3 --timeout=90
```

Scheduler cron:

```bash
* * * * * cd /path/to/spm && php artisan schedule:run >> /dev/null 2>&1
```

Health check:

```bash
curl -f https://domain-production/up
```

## Gate Sebelum Go-Live

- Full test suite hijau, atau sisa failure tertulis jelas sebagai non-blocker.
- Browser QA end-to-end flow LP2M selesai di staging.
- Tidak ada route/action penting tanpa authorization server-side.
- Upload dokumen lolos validasi MIME, ukuran, ownership, dan storage path.
- Halaman prioritas admin, pesantren, asesor tidak lambat di staging.
- UI role utama sudah memakai reusable Metronic component.
- `APP_DEBUG=false`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, queue worker, scheduler, dan monitoring aktif.
- Backup database/storage berjalan dan restore drill berhasil.

## Verifikasi Repository

Jalankan sebelum membuat release:

```bash
composer prod:check
vendor/bin/pint --test
npm run build
php artisan view:cache
```

## Kesimpulan

Repository sekarang lebih dekat ke layak produksi karena guard runtime, security header, health check, cache script, dan dokumentasi deploy sudah eksplisit. Sisa pekerjaan utama bukan lagi menebak-nebak di kode, tetapi mengunci staging/production environment dan menjalankan QA penuh sesuai business flow LP2M.
