# Optimasi Performa Laravel SPM

Dokumen ini menjadi checklist singkat untuk mode lokal cepat dan deployment production-like.

## Runtime Env

Gunakan nilai berikut untuk simulasi production di lokal:

```env
APP_DEBUG=false
LOG_LEVEL=warning
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

Redis belum menjadi default karena ekstensi `phpredis` dan service Redis belum tersedia di lokal saat audit.

## Cache Command

Jalankan:

```bash
composer perf:cache
```

Perintah ini membersihkan cache lama, mengoptimalkan autoload Composer, lalu membuat `config`, `route`, `event`, dan `view` cache.

Untuk kembali ke mode development biasa:

```bash
composer perf:clear
```

## OPcache

Untuk Laragon atau PHP-FPM production, aktifkan OPcache di `php.ini`:

```ini
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
realpath_cache_size=4096K
```

Di production permanen, `validate_timestamps=0` boleh dipakai jika deploy selalu menjalankan reload PHP-FPM setelah rilis.

## Verifikasi

Setelah optimasi:

```bash
php artisan about --no-ansi
npm run build
php artisan test tests/Feature/PerformanceOptimizationTest.php --no-ansi
```

## Status Implementasi

Terakhir diverifikasi pada 26 Mei 2026:

- `APP_DEBUG=false`, `CACHE_STORE=database`, dan `SESSION_DRIVER=database` sudah aktif di `.env` lokal.
- `config`, `route`, `event`, dan `view` cache sudah aktif melalui `composer perf:cache`.
- Duplicate index lama di `akreditasis` dihapus, lalu composite index performa ditambahkan untuk assessment, catatan EDPM akreditasi, dan banding.
- Bundle awal Vite turun karena SweetAlert2 dipisah menjadi dynamic import.
- `plugins.bundle.css` dan `plugins.bundle.js` tidak diload global di layout app, guest, dan landing.
- Polling detail akreditasi diperlambat dan hanya berjalan saat komponen terlihat: admin `45s`, asesor `30s`.

Baseline warm request lokal setelah cache:

```text
/      349-393 ms
/login 296-371 ms
```
