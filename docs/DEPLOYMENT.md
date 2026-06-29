# Deployment Guide — PesantrenMu SPM

Panduan deploy, konfigurasi environment, dan runbook operasional.

---

## Prasyarat

| Komponen | Versi minimum |
|----------|---------------|
| PHP | 8.2 |
| MySQL | 8.0 |
| Node.js | 22 (build only) |
| Docker | 24 (opsional) |

---

## Environment Variables

Salin `.env.example` ke `.env` dan isi semua variabel berikut.

### Wajib diisi sebelum deploy

```env
APP_NAME=PesantrenMu
APP_ENV=production
APP_KEY=                        # php artisan key:generate
APP_DEBUG=false
APP_URL=https://spm.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spm_production
DB_USERNAME=spm_user
DB_PASSWORD=<strong-password>

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

QUEUE_CONNECTION=database       # job notifikasi & deadline checks
CACHE_STORE=database

SENTRY_LARAVEL_DSN=https://xxx@sentry.io/yyy
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_SEND_DEFAULT_PII=false
```

### SSO (jika diaktifkan)

```env
SSO_SERVER_URL=https://sso.muhammadiyah.or.id
SSO_CLIENT_ID=<client-id>
SSO_CLIENT_SECRET=<client-secret>
```

### WebPush Notifications

```env
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:admin@spm.example.com
```

### Opsional

```env
AKREDITASI_POLLING_INTERVAL=10  # detik, polling presence legacy reactive layer
AKREDITASI_PRESENCE_ENABLED=false
TRASH_RETENTION_DAYS=90         # hari sebelum soft-deleted records dihapus permanen
BCRYPT_ROUNDS=12                # naikkan ke 13-14 untuk server kencang
```

---

## Deploy Manual (tanpa Docker)

```bash
# 1. Clone & install
git clone <repo> /var/www/spm
cd /var/www/spm
composer install --no-dev --optimize-autoloader --classmap-authoritative

# 2. Build assets
npm ci --no-audit --no-fund
npm run build

# 3. Environment
cp .env.example .env
# Edit .env dengan nilai production
php artisan key:generate

# 4. Database
php artisan migrate --force

# 5. Cache production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Storage link
php artisan storage:link

# 7. Permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

---

## Deploy dengan Docker

```bash
# Build image
docker build -t pesantrenmu-spm:latest .

# Run container
docker run -d \
  --name spm \
  -p 8080:8080 \
  --env-file .env \
  -e RUN_MIGRATIONS=true \
  -v spm_storage:/var/www/html/storage/app \
  pesantrenmu-spm:latest
```

> **Catatan**: Container sudah include nginx + php-fpm + queue worker via supervisord.
> Port default: **8080** (sesuaikan reverse proxy).

### Docker Compose (contoh minimal)

```yaml
services:
  app:
    image: pesantrenmu-spm:latest
    ports:
      - "8080:8080"
    env_file: .env
    environment:
      RUN_MIGRATIONS: "true"
    volumes:
      - spm_storage:/var/www/html/storage/app
    depends_on:
      db:
        condition: service_healthy

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: spm_production
      MYSQL_USER: spm_user
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    volumes:
      - spm_db:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "--silent"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  spm_storage:
  spm_db:
```

---

## Scheduled Commands

Pastikan Laravel scheduler berjalan. Tambahkan cron entry:

```cron
* * * * * www-data php /var/www/spm/artisan schedule:run >> /dev/null 2>&1
```

Atau dengan supervisor:

```ini
[program:scheduler]
command=php /var/www/html/artisan schedule:work
user=www-data
autostart=true
autorestart=true
```

### Daftar scheduled jobs

| Command | Frekuensi | Fungsi |
|---------|-----------|--------|
| `banding:check-deadlines` | Daily | Tutup banding yang melewati deadline |
| `perbaikan:check-deadlines` | Daily | Cek deadline perbaikan akreditasi |
| `reminders:asesor2` | Daily | Kirim reminder ke asesor 2 yang belum menilai |
| `akreditasi:check-deadlines` | Daily | Update status akreditasi yang timeout |
| `trash:purge` | Daily | Hapus permanen soft-deleted records > `TRASH_RETENTION_DAYS` hari |

---

## Queue Worker

Queue worker sudah dikonfigurasi di `docker/supervisord.conf`. Untuk deploy non-Docker:

```bash
# Jalankan via supervisor (direkomendasikan)
# /etc/supervisor/conf.d/spm-queue.conf
[program:spm-queue]
command=php /var/www/spm/artisan queue:work --tries=3 --timeout=90 --sleep=3 --max-jobs=500
user=www-data
autostart=true
autorestart=true
stopwaitsecs=120
```

Setelah deploy baru, restart queue worker agar kode terbaru dimuat:

```bash
php artisan queue:restart
```

---

## Runbook: Deploy Update

```bash
# 1. Aktifkan maintenance mode
php artisan down --secret="<bypass-token>"

# 2. Pull kode terbaru
git pull origin main

# 3. Update dependencies
composer install --no-dev --optimize-autoloader --classmap-authoritative

# 4. Build assets (jika ada perubahan frontend)
npm ci --no-audit --no-fund && npm run build

# 5. Jalankan migrasi
php artisan migrate --force

# 6. Clear & rebuild cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Restart queue worker
php artisan queue:restart

# 8. Nonaktifkan maintenance mode
php artisan up
```

---

## Runbook: Rollback

```bash
# 1. Aktifkan maintenance mode
php artisan down

# 2. Checkout versi sebelumnya
git checkout <previous-tag>

# 3. Rollback migrasi (hati-hati — data bisa hilang)
php artisan migrate:rollback

# 4. Rebuild cache
php artisan optimize:clear && php artisan optimize

# 5. Restart queue
php artisan queue:restart

# 6. Up
php artisan up
```

---

## Health Check

Endpoint `/up` tersedia untuk load balancer / uptime monitor:

```
GET https://spm.example.com/up
→ 200 OK  (app sehat)
→ 503     (maintenance mode aktif)
```

---

## Monitoring

- **Error tracking**: Sentry — set `SENTRY_LARAVEL_DSN` di `.env`
- **Failed jobs**: Dashboard tersedia di `/admin/failed-notifications` (role admin)
- **Logs**: `storage/logs/laravel.log` atau stdout container

---

## Backup

Backup database harus dijadwalkan di luar aplikasi (cron / managed DB snapshot):

```bash
# Contoh mysqldump harian
mysqldump -u spm_user -p spm_production | gzip > /backups/spm_$(date +%Y%m%d).sql.gz
```

Backup `storage/app` untuk file upload pesantren:

```bash
tar -czf /backups/spm_storage_$(date +%Y%m%d).tar.gz /var/www/spm/storage/app
```

Retensi backup minimal: **30 hari**.

