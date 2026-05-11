# Local Setup

Target lokal project ini:

- `APP_URL=http://spm_fix.test`
- `DB_CONNECTION=mysql`
- `DB_DATABASE=spm_fix`
- `DB_USERNAME=root`
- `DB_PASSWORD=` 

## Prerequisites

- Laragon aktif
- MySQL jalan di `127.0.0.1:3306`
- domain `spm_fix.test` mengarah ke `127.0.0.1`

## First Run

```bash
php artisan config:clear
php artisan migrate:fresh --seed
npm run build
```

## Demo Accounts

- `admin@spm.test` / `password`
- `pesantren@spm.test` / `password`
- `asesor@spm.test` / `password`

## Notes

- SQLite lama tetap tersedia sebagai backup lokal.
- Testing tetap memakai SQLite in-memory.
- Asset Metronic dipakai dari `public/vendor/metronic`.
- Asset QA screenshot disimpan di `output/` dan tidak ikut version control.
