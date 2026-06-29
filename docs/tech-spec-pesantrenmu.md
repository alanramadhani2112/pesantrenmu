# Technical Spesification — PesantrenMu

Sistem Penjaminan Mutu Pesantren Muhammadiyah. Aplikasi akreditasi berbasis web yang dikembangkan LabMu untuk LP2M Pimpinan Pusat Muhammadiyah.

---

## 1. Technology Stack

### Backend

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| Framework | Laravel | 12.x |
| Bahasa | PHP | ≥ 8.2 |
| ORM | Eloquent | — |
| Queue | Database driver | — |
| Cache | Redis (production) / file (local) | — |
| Realtime | Laravel WebSockets / polling | — |
| Error tracking | Sentry | 4.x |
| Export | Maatwebsite Excel | 3.x |
| Web push | laravel-notification-channels/webpush | 10.x |
| Auth | Laravel Breeze + SSO LP2M bridge | — |

### Frontend

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| UI runtime | Blade + controller + Alpine kecil | Laravel native |
| UI Kit | Metronic 8 | 8.x |
| CSS | TailwindCSS | 3.x |
| Bundler | Vite | 7.x |
| Icon library | Ki (Solid / Duotone) | — |
| Modal / Alert | SweetAlert2 | 11.x |
| File upload | Dropzone | 6.x |
| HTTP client | Axios | 1.x |

### Infrastructure

| Layer | Teknologi |
|-------|-----------|
| Database | MySQL 8.x |
| Web server | Nginx |
| OS (VPS) | Ubuntu 22.04+ |
| PHP runtime | PHP-FPM 8.3 |
| Queue worker | Supervisor + `artisan queue:work` |
| Dev env | Laragon (Windows) |

---

## 2. Role & Permission System

### Role (4 level)

| role_id | Nama | Parameter | Deskripsi |
|---------|------|-----------|-----------|
| 1 | admin | `admin` | Verifikasi berkas, assign asesor, validasi akhir, terbitkan SK |
| 2 | asesor | `asesor` | Review substansi, visitasi, penilaian, upload laporan |
| 3 | pesantren | `pesantren` | Submit pengajuan akreditasi, isi data pesantren/IPM/SDM/EDPM |
| 4 | super_admin | `super_admin` | God mode — bypass semua permission check |

### Permission Group

| Group | Key | Deskripsi |
|-------|-----|-----------|
| akreditasi | `akreditasi.approve`, `akreditasi.assign`, `akreditasi.view` | Manajemen akreditasi |
| asesor | `asesor.view`, `asesor.manage` | Manajemen asesor |
| pesantren | `pesantren.view`, `pesantren.manage` | Manajemen pesantren |
| banding | `banding.view`, `banding.manage` | Manajemen banding |
| master | `master.role`, `master.edpm`, `master.kategori`, `master.dokumen` | Master data |
| account | `account.view` | Manajemen akun |
| trash | `trash.view` | Lihat trash |
| notification | `notification.view` | Lihat failed notifications |

### Middleware Auth Chain

```
auth → verified → role:{role} → permission:{key}
```

- `EnsureUserHasRole` — gate 403 jika role tidak cocok. Super admin bypass.
- `EnsureUserHasPermission` — gate 403 jika permission tidak dimiliki. Super admin bypass.
- `ThrottleRequests:web` — rate-limit global semua web routes.
- `SecurityHeaders` — X-Frame-Options, X-Content-Type-Options, Referrer-Policy, HSTS.
- `TrustProxies` — untuk deployment di balik reverse proxy (Nginx).

### DB Schema: Role & Permission

```
roles: id, name, parameter, timestamps
permissions: id, key, label, group, description, timestamps
role_permission: role_id, permission_id, timestamps
users: ... role_id (FK → roles.id)
```

---

## 3. Business Process: Alur Akreditasi

### State Machine

```
        admin          asesor          ketua_kelompok    asesor         admin
Pengajuan ──→ Verifikasi ──→ Review ──→ Visitasi ──→ Pasca ──→ Validasi ──→ Selesai
   (6)         Berkas (5)    Asesor (4)   (3)       Visitasi (2)  Admin (1)     (0)
                 │               │                                    │
                 └──→ Ditolak ←──┘                                    │
                       (-1)                                           │
                        │  (pesantren submit banding)                 │
                        └──→ Banding (-2) ──→ Validasi (1) / Ditolak (-1)
```

### Status Detail

| Status | Kode | Aktor | Aksi |
|--------|------|-------|------|
| Pengajuan | 6 | Pesantren | Submit formulir akreditasi + kartu kendali |
| Verifikasi Berkas | 5 | Admin | Review kelengkapan, assign asesor (ketua + anggota) |
| Review Asesor | 4 | Asesor | Review substansi, bisa minta perbaikan atau tolak |
| Visitasi | 3 | Ketua Kelompok | Jadwalkan visitasi, konfirmasi kehadiran |
| Penilaian Pasca Visitasi | 2 | Asesor | Input NA1, NA2, NK, upload laporan visitasi |
| Validasi Admin | 1 | Admin | Input NV, terbitkan SK, tentukan peringkat |
| Selesai (Terakreditasi) | 0 | — | Hasil final: Peringkat A / B / C |
| Ditolak Final | -1 | — | Pesantren bisa ajukan banding |
| Banding | -2 | Admin | Admin review ulang keputusan |

### Nilai Akreditasi

- **NA1**: Nilai Akhir 1 (dari asesor 1)
- **NA2**: Nilai Akhir 2 (dari asesor 2)
- **NK**: Nilai Ketua Kelompok
- **NV**: Nilai Validasi (input admin di tahap validasi)

### State Machine Implementation

`App\StateMachine\AkreditasiStateMachine`:
- **Optimistic locking**: UPDATE dengan WHERE `updated_at` = nilai saat load. Jika 0 row terpengaruh → `StaleStateException`.
- **Audit trail**: Setiap transisi tercatat di `AkreditasiAuditLog` (from_status, to_status, actor, timestamp, IP, user-agent).
- **Event dispatch**: `AkreditasiTransitioned` di-fire setelah transaksi commit agar listener melihat committed state.

### Banding Flow

1. Pesantren submit banding dengan alasan (`BandingSubmitted` event)
2. Admin assign reviewer, set `review_deadline`
3. Reviewer putuskan (diterima → kembali ke Validasi, ditolak → tetap Ditolak)
4. `BandingDecided` event dispatch

---

## 4. Data Model

### Core Models (28)

#### User & Auth
| Model | File | Key Relations |
|-------|------|---------------|
| `User` | `app/Models/User.php` | HasOne: Pesantren, Asesor, Profile, Ipm, UserOnboarding. HasMany: SdmPesantren, Edpm, EdpmCatatan, Akreditasi. BelongsToMany: Document |
| `Role` | `app/Models/Role.php` | HasMany: User. BelongsToMany: Permission |
| `Permission` | `app/Models/Permission.php` | BelongsToMany: Role |
| `Profile` | `app/Models/Profile.php` | BelongsTo: User |
| `UserOnboarding` | `app/Models/UserOnboarding.php` | BelongsTo: User |

#### Pesantren
| Model | File | Key Relations |
|-------|------|---------------|
| `Pesantren` | `app/Models/Pesantren.php` | BelongsTo: User. HasMany: PesantrenUnit |
| `PesantrenUnit` | `app/Models/PesantrenUnit.php` | BelongsTo: Pesantren. HasMany: SdmPesantren |
| `Ipm` | `app/Models/Ipm.php` | BelongsTo: User |
| `SdmPesantren` | `app/Models/SdmPesantren.php` | BelongsTo: User, PesantrenUnit |
| `Edpm` | `app/Models/Edpm.php` | BelongsTo: User, MasterEdpmButir |
| `EdpmCatatan` | `app/Models/EdpmCatatan.php` | BelongsTo: User |

#### Asesor
| Model | File | Key Relations |
|-------|------|---------------|
| `Asesor` | `app/Models/Asesor.php` | BelongsTo: User |

#### Akreditasi
| Model | File | Key Relations |
|-------|------|---------------|
| `Akreditasi` | `app/Models/Akreditasi.php` | BelongsTo: User. HasMany: AkreditasiEdpm, AkreditasiCatatan, Assessment, AkreditasiAuditLog, AkreditasiRejection, Banding |
| `AkreditasiAuditLog` | `app/Models/AkreditasiAuditLog.php` | BelongsTo: Akreditasi, User |
| `AkreditasiEdpm` | `app/Models/AkreditasiEdpm.php` | BelongsTo: Akreditasi, MasterEdpmButir, Asesor |
| `AkreditasiEdpmCatatan` | `app/Models/AkreditasiEdpmCatatan.php` | BelongsTo: Akreditasi, MasterEdpmButir, User |
| `AkreditasiCatatan` | `app/Models/AkreditasiCatatan.php` | BelongsTo: Akreditasi, User |
| `AkreditasiRejection` | `app/Models/AkreditasiRejection.php` | BelongsTo: Akreditasi, User |
| `Assessment` | `app/Models/Assessment.php` | BelongsTo: Akreditasi |

#### Banding
| Model | File | Key Relations |
|-------|------|---------------|
| `Banding` | `app/Models/Banding.php` | BelongsTo: Akreditasi, User (submitter), User (reviewer) |
| `AkreditasiBandingEdpm` | `app/Models/AkreditasiBandingEdpm.php` | BelongsTo: Akreditasi |
| `AkreditasiBandingEdpmCatatan` | `app/Models/AkreditasiBandingEdpmCatatan.php` | — |

#### Master
| Model | File | Key Relations |
|-------|------|---------------|
| `MasterEdpmKomponen` | `app/Models/MasterEdpmKomponen.php` | HasMany: MasterEdpmButir |
| `MasterEdpmButir` | `app/Models/MasterEdpmButir.php` | BelongsTo: MasterEdpmKomponen |
| `Document` | `app/Models/Document.php` | BelongsTo: DocumentCategory. BelongsToMany: User |
| `DocumentCategory` | `app/Models/DocumentCategory.php` | HasMany: Document |

#### Lain-lain
| Model | File | Key Relations |
|-------|------|---------------|
| `FailedNotification` | `app/Models/FailedNotification.php` | — |
| `PermissionAuditLog` | `app/Models/PermissionAuditLog.php` | BelongsTo: User |

### UUID Auto-generation

`User::boot()` → `static::creating` → jika `uuid` kosong, generate `Str::uuid()`. Ini mencegah `UrlGenerationException` pada route parameter `{uuid}`.

### Cascade Delete (User)

`User::deleting()` → dalam 1 transaksi DB:
1. Delete pesantren (cascade ke units, file storage)
2. Delete asesor
3. Delete semua akreditasi (cascade ke edpm, catatan, assessment, audit log, rejection, banding)
4. Delete IPM, SDM, EDPM, EDPM Catatan, Profile

---

## 5. Events & Listeners (10 Events)

| Event | Trigger | Listener Action |
|-------|---------|----------------|
| `AkreditasiTransitioned` | StateMachine.transition() | Notifikasi ke pesantren + asesor + admin via WebPush |
| `AsesorAssigned` | Admin assign asesor ke akreditasi | Notifikasi ke asesor + pesantren |
| `AsesorPackageSubmitted` | Asesor submit paket penilaian | Notifikasi ke admin |
| `BandingSubmitted` | Pesantren submit banding | Notifikasi ke admin |
| `BandingDecided` | Admin putuskan banding | Notifikasi ke pesantren |
| `PerbaikanDeadlineApproaching` | Cron / scheduler | Reminder ke pesantren (deadline mendekat) |
| `PerbaikanSubmitted` | Pesantren submit perbaikan | Notifikasi ke asesor |
| `ScoringCompleted` | Nilai final dihitung | Notifikasi ke admin |
| `SKIssued` | Admin terbitkan SK | Notifikasi ke pesantren |
| `VisitasiScheduled` | Ketua kelompok jadwalkan visitasi | Notifikasi ke asesor + pesantren |

---

## 6. Routes & UI Structure

### Public
```
GET  /                          welcome page (landing)
GET  /register                  form registrasi
GET  /login                     form login
GET  /forgot-password           form lupa password
GET  /reset-password/{token}    form reset password
POST /logout                    logout
```

### Authenticated (semua role)
```
GET  /dashboard                 home (controller/view runtime)
GET  /profile                   edit profil
GET  /roles                     master role (permission: master.role)
GET  /accounts                  daftar akun (permission: account.view)
GET  /documents/{doc?}          dokumen master
GET  /panduan                   redirect ke panduan sesuai role
GET  /secure/asesor-docs/{id}/{field}   download file asesor
```

### Admin Panel (`/admin/*`)
```
GET  /admin/master-edpm             master EDPM (permission: master.edpm)
GET  /admin/master-kategori-dokumen  master kategori dokumen (permission: master.kategori)
GET  /admin/master-document          master dokumen (permission: master.dokumen)
GET  /admin/master-role-permission   master role-permission (permission: master.role)
GET  /admin/akreditasi               daftar akreditasi
GET  /admin/akreditasi/{uuid}        detail akreditasi
GET  /admin/asesor                   daftar asesor (permission: asesor.view)
GET  /admin/asesor/{uuid}            detail asesor (permission: asesor.view)
GET  /admin/banding                  daftar banding (permission: banding.view)
GET  /admin/banding/{id}             detail banding (permission: banding.view)
GET  /admin/pesantren                daftar pesantren (permission: pesantren.view)
GET  /admin/pesantren/{uuid}         detail pesantren (permission: pesantren.view)
GET  /admin/failed-notifications     failed notifications (permission: notification.view)
GET  /admin/trash                    trash / soft-deleted records (permission: trash.view)
```

### Asesor Panel (`/asesor/*`)
```
GET  /asesor/profile             profil asesor
GET  /asesor/akreditasi          daftar akreditasi yang di-assign
GET  /asesor/akreditasi/{uuid}   detail akreditasi (controller/view runtime)
```

### Pesantren Panel (`/pesantren/*`)
```
GET  /pesantren/profile           profil pesantren
GET  /pesantren/ipm               isian IPM
GET  /pesantren/sdm               data SDM
GET  /pesantren/edpm              isian EDPM
GET  /pesantren/akreditasi        daftar akreditasi
GET  /pesantren/akreditasi/{uuid} detail akreditasi
```

### Panduan (role-gated)
```
GET  /panduan-admin            panduan untuk admin
GET  /panduan-asesor           panduan untuk asesor
GET  /panduan-pesantren        panduan untuk pesantren
GET  /panduan-superadmin       panduan untuk super admin
```

### UI Components (Metronic 8 / Blade runtime)

Layout menggunakan Metronic 8 dengan sidebar kustom. Struktur direktori:

```
resources/views/
├── livewire/
│   ├── home.blade.php
│   ├── pages/
│   │   ├── admin/
│   │   │   ├── akreditasi.blade.php
│   │   │   ├── akreditasi-detail.blade.php
│   │   │   ├── akreditasi-detail/  (sub-components)
│   │   │   ├── asesor/
│   │   │   ├── pesantren/
│   │   │   ├── banding.blade.php
│   │   │   ├── banding-detail.blade.php
│   │   │   ├── trash.blade.php
│   │   │   ├── master-edpm.blade.php
│   │   │   ├── master/
│   │   │   ├── audit-timeline.blade.php
│   │   │   └── failed-notification-dashboard.blade.php
│   │   ├── asesor/
│   │   ├── pesantren/
│   │   ├── roles/
│   │   ├── accounts/
│   │   ├── dokumen/
│   │   └── auth/
│   └── partials/ (sidebar, header, footer)
├── components/ (Blade components)
├── layouts/ (app, guest, panduan)
└── panduan/ (admin, asesor, pesantren, superadmin)
```

---

## 7. Middleware & Security

### Custom Middleware

| Class | Alias | Fungsi |
|-------|-------|--------|
| `EnsureUserHasRole` | `role` | Abort 403 jika user tidak punya role yang diminta. Super admin bypass |
| `EnsureUserHasPermission` | `permission` | Abort 403 jika role user tidak punya permission. Super admin bypass |
| `SecurityHeaders` | — | Tambahkan security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, HSTS, CSP) |
| `TrustProxies` | — | Trust proxy headers dari Nginx reverse proxy |

### Middleware Pipeline

Semua web routes mendapat `ThrottleRequests:web` secara global (via `bootstrap/app.php`).

### File Security

Download file sensitif (KTP, ijazah, kartu NBM) melalui route `secure.asesor-docs`:
- Validasi: hanya field yang diizinkan (`ktp_file`, `ijazah_file`, `kartu_nbm_file`)
- Authorization: hanya asesor pemilik file atau admin/super admin
- Disk: `local` (non-public)

---

## 8. Queue & Notifications

### Queue System

- Driver: `database`
- Worker: Supervisor dengan `artisan queue:work --tries=3`
- Jobs: dispatch notifikasi WebPush, event listener side effects

### Notification Channel

`laravel-notification-channels/webpush` — push notification ke browser via VAPID keys. Satu class notification untuk semua trigger akreditasi:
- `AkreditasiNotification.php`

### Failed Notification Dashboard

`/admin/failed-notifications` — monitoring notifikasi yang gagal dikirim (permission: `notification.view`).

---

## 9. Error Monitoring

**Sentry (sentry/sentry-laravel 4.x)**: exception handler integration di `bootstrap/app.php`:
```php
->withExceptions(function (Exceptions $exceptions): void {
    Integration::handles($exceptions);
})
```

---

## 10. Testing

### Test Suites

```
tests/
├── Feature/          # Integration tests
│   ├── UserModelCascadeTest.php
│   ├── ProductionReadinessTest.php
│   └── PerformanceOptimizationTest.php
├── Unit/             # Unit tests
└── TestCase.php
```

### Key Tests

- `UserModelCascadeTest`: UUID auto-generation, cascade delete behavior
- `ProductionReadinessTest`: pre-deploy check
- `PerformanceOptimizationTest`: route cache, config cache, view cache

### Composer Scripts

- `composer test`: `php artisan test`
- `composer prod:check`: `php artisan test tests/Feature/ProductionReadinessTest.php tests/Feature/PerformanceOptimizationTest.php --stop-on-failure`
- `composer perf:cache`: cache semua config/route/event/view
- `composer perf:clear`: clear semua cache

---

## 11. Deployment

### Environment

- **Dev**: Laragon (Windows) — PHP 8.2, MySQL, file cache
- **Production**: VPS Ubuntu 22.04 — Nginx, PHP-FPM 8.3, MySQL 8, Redis, Supervisor

### VPS Details

- Host: `103.93.132.10`
- Path: `/var/www/pesantrenmu/`
- DB: `spm_fix` (MySQL)
- Git remote: `pesantrenmu` → `https://github.com/alanramadhani2112/pesantrenmu.git`

### Deployment Flow

1. Local → commit → push ke `pesantrenmu/main`
2. SSH ke VPS → `git pull` → `composer install --no-dev` → `php artisan optimize` → restart queue worker

---

## 12. Key Architectural Decisions

1. **Optimistic locking on state machine**: UPDATE with `updated_at` match. Prevents race conditions tanpa pessimistic lock overhead.
2. **Audit trail on every transition**: `AkreditasiAuditLog` mencatat from/to status + actor + IP + user-agent. State machine bypasses `Auth::id()` untuk akurasi actor dari background jobs.
3. **UUID routing**: Parameter route menggunakan UUID bukan auto-increment ID. Mencegah enumeration attack.
4. **Permission bypass untuk Super Admin**: `User::hasPermission()` selalu return true. Tidak perlu assign permission satu per satu.
5. **Cascade delete dalam transaksi**: `User::deleting()` wraps semua cascade dalam 1 DB transaction — mencegah partial state.
6. **Mass-assignment hardening**: Semua model pakai `$fillable` allowlist (bukan `$guarded`). Pesantren form hanya bisa tulis field di `PESANTREN_FILLABLE`.
7. **Web Push via VAPID**: Notifikasi real-time ke browser tanpa polling.
8. **Breeze + SSO bridge**: Auth built-in Laravel dengan jembatan ke SSO LP2M.

