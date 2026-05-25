# Changelog

Format mengikuti [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [Unreleased]

### Security
- **C-2**: Ganti `$guarded = []` dengan explicit `$fillable` di 7 model (Pesantren, Asesor, Document, Ipm, PesantrenUnit, SdmPesantren, AkreditasiCatatan)
- **C-1**: SSO token tidak lagi dikirim via URL path — disimpan di session
- **H-2**: Tambah Policy files untuk semua model utama (Akreditasi, Pesantren, Asesor, Banding, Document, Ipm, SdmPesantren)
- **H-3**: SSO role sync hanya terjadi jika `sso_sync_role=true` per user
- **H-4**: Demo seeder hanya berjalan di environment `local`/`testing`
- **L-3**: `Profile.access_token` dienkripsi di DB via Laravel `encrypted` cast
- **M-3**: Hapus fallback hardcoded `'secretKey'` dan `'someId'` dari `config/sso.php`
- **M-4**: `TrustProxies` baca `TRUSTED_PROXIES` dari env (tidak lagi hardcode `'*'`)
- **PR-6**: Tambah `SecurityHeaders` middleware (HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, CORP, COOP)
- **PR-16**: `URL::forceScheme('https')` aktif di environment production

### Fixed
- **PM-1/PM-2**: Orphan file race condition di upload profil dan IPM — store-new → DB update → delete-old on success
- **PM-3**: File cleanup saat Pesantren, Ipm, Akreditasi dihapus via `deleting` hooks
- **PM-4**: Race condition pembuatan dua row Pesantren untuk satu user — unique index + `firstOrCreate`
- **PM-7**: `cancelSubmission` tidak transactional dan `findOrFail` throw 404 saat double-click — fix ke `find` + `DB::transaction`, return `bool`
- **PM-8**: `toggleDataLock` lost-update race — fix dengan `lockForUpdate` + `DB::transaction`
- **PM-11**: Race condition duplikasi row IPM/SDM — unique index pada `ipms.user_id` dan `sdm_pesantrens.(user_id,tingkat)`
- **PM-12**: `checkDataCompleteness` terlalu longgar — wajib layanan satuan pendidikan + SDM non-zero
- **PM-18**: SDM save loop tidak transactional — bungkus dalam `DB::transaction`
- **PM-23**: `Akreditasi.$fillable` ekspos field admin — tambah `PESANTREN_FILLABLE` constant
- **PM-24**: `akreditasis.uuid` tidak unique di DB — tambah unique index
- **PM-25**: Provinsi nama/kode bisa drift — derive nama dari kode di server
- **PM-26**: Crash `is_locked` saat pesantren null — fix dengan `?->is_locked ?? false`
- **PM-27**: `uploadKartuKendali` tidak hapus file lama — hapus setelah transaction commit
- **L-2**: `ChecksSectionLock` tidak cek `auth()->check()` — tambah guard
- **L-6**: `submitAppeals` max bound menggunakan `$alasan` bukan `$trimmed`
- **P-6**: User cascade delete tidak transactional — bungkus dalam `DB::transaction`

### Performance
- **P-1**: Tambah 6 indexes pada tabel `akreditasis` (user_id, parent, status, uuid, composite)
- **P-2**: Unique composite index pada `akreditasi_edpms.(akreditasi_id, asesor_id, butir_id)`
- **P-3**: `AkreditasiRepository` eager-load `assessment1`+`assessment2` langsung, drop bare `assessments`
- **P-4**: `AsesorRepository` drop 4-level eager-load, gunakan `withCount`
- **P-7**: `Home.php` 6 sequential COUNT → 1 aggregated `selectRaw`
- **P-8**: Sidebar badges di-cache 30 detik
- **P-9**: `MasterEdpmButir::count()` di-cache forever
- **P-10/P-11**: Unique indexes pada `edpms.(user_id,butir_id)` dan `sdm_pesantrens.(user_id,tingkat)`
- **P-12**: `PesantrenRepository` drop `with(['pesantren','akreditasis'])`, gunakan `withCount`
- **P-13/P-14/P-18**: Composite indexes pada `documents`, `notifications`, `assessments`

### Added
- Non-blocking notifications: `AkreditasiNotification` implements `ShouldQueue`, `FailedNotificationDashboard`
- Soft-delete restore flow: `TrashService`, `PurgeTrashCommand`, halaman admin `/trash`
- Performance indexes: migration `2026_05_21_000010`
- Unique index `pesantrens.user_id`: migration `2026_05_21_000009`
- Unique index `akreditasis.uuid`: migration `2026_05_21_000011`
- Unique index `ipms.user_id`: migration `2026_05_21_000012`
- `docs/architecture.md` — dokumentasi arsitektur sistem
- `docs/deployment.md` — production deploy runbook
- `CHANGELOG.md` — file ini

### Changed
- `LOG_STACK=daily`, `LOG_DEPRECATIONS_CHANNEL=daily` di `.env.example`
- `SESSION_DRIVER=database`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true` di `.env.example`
- Profile validation rules diperketat: `layanan.*` Rule::in, max lengths, integer bounds, `provinsi_kode` validated

---

## Versi Sebelumnya

Lihat `docs/project-work-report.md` untuk riwayat pengembangan sebelumnya.
