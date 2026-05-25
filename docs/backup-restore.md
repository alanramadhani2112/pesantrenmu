# Backup & Restore

## Strategi Backup

### Database (MySQL)

**Daily backup** menggunakan `mysqldump`:

```bash
#!/bin/bash
# /etc/cron.daily/spm-backup-db
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/spm"
DB_NAME="spm_fix"
DB_USER="root"
DB_PASS="your_password"

mkdir -p "$BACKUP_DIR"

mysqldump \
  --single-transaction \
  --routines \
  --triggers \
  -u "$DB_USER" \
  -p"$DB_PASS" \
  "$DB_NAME" \
  | gzip > "$BACKUP_DIR/db_${DATE}.sql.gz"

# Hapus backup lebih dari 30 hari
find "$BACKUP_DIR" -name "db_*.sql.gz" -mtime +30 -delete
```

### File Uploads (Storage)

Upload pesantren tersimpan di `storage/app/public/`. Backup dengan rsync ke offsite:

```bash
#!/bin/bash
# /etc/cron.daily/spm-backup-storage
rsync -avz --delete \
  /var/www/spm_fix/storage/app/public/ \
  backup-server:/backups/spm/storage/
```

Atau gunakan `spatie/laravel-backup` untuk backup terintegrasi:

```bash
composer require spatie/laravel-backup
php artisan backup:run
```

### Retensi

| Tipe | Retensi |
|------|---------|
| DB daily | 30 hari |
| DB weekly | 3 bulan |
| Storage | 90 hari (sesuai `TRASH_RETENTION_DAYS`) |

## Restore Database

```bash
# 1. Stop queue worker
supervisorctl stop spm-worker

# 2. Restore dari backup
gunzip -c /var/backups/spm/db_20260520_120000.sql.gz \
  | mysql -u root -p spm_fix

# 3. Jalankan migration jika ada yang tertinggal
php artisan migrate --force

# 4. Clear cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# 5. Restart queue worker
supervisorctl start spm-worker
```

## Restore File Uploads

```bash
# Restore dari rsync backup
rsync -avz \
  backup-server:/backups/spm/storage/ \
  /var/www/spm_fix/storage/app/public/

# Pastikan symlink masih ada
php artisan storage:link
```

## Verifikasi Backup

Setelah restore, verifikasi:

```bash
# Cek jumlah record utama
php artisan tinker --execute="
echo 'Users: ' . App\Models\User::count() . PHP_EOL;
echo 'Akreditasi: ' . App\Models\Akreditasi::withTrashed()->count() . PHP_EOL;
echo 'Pesantren: ' . App\Models\Pesantren::count() . PHP_EOL;
"

# Cek health endpoint
curl https://spm.example.com/up
```

## Disaster Recovery

1. Provision server baru dengan PHP 8.2, MySQL 8, Redis
2. Clone repo: `git clone <repo> /var/www/spm_fix`
3. Install dependencies: `composer install --no-dev && npm ci && npm run build`
4. Copy `.env.production` ke `.env`
5. Restore database (lihat di atas)
6. Restore file uploads (lihat di atas)
7. Jalankan: `php artisan migrate --force && php artisan storage:link`
8. Warmup cache: `php artisan config:cache && php artisan route:cache && php artisan view:cache`
9. Start queue worker dan scheduler
10. Verifikasi health endpoint

Estimasi RTO (Recovery Time Objective): **2-4 jam** dengan backup terkini.
