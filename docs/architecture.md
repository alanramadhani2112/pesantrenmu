# Architecture — SPM Fix

## Overview

SPM Fix adalah aplikasi akreditasi pesantren Muhammadiyah. Arsitektur menggunakan Laravel 12 dengan Livewire Volt sebagai layer UI, Repository pattern untuk data access, dan Service layer untuk business logic.

## Layer Architecture

```
Browser
  └── Livewire Volt (Blade pages) / Livewire Components
        └── Service Layer (PesantrenService, AkreditasiService, ...)
              └── Repository Layer (Eloquent implementations)
                    └── Eloquent Models
                          └── MySQL Database
```

### Livewire Volt Pages

Semua halaman menggunakan Volt single-file components di `resources/views/livewire/pages/`:

- `admin/` — halaman admin (akreditasi, asesor, pesantren, master data)
- `asesor/` — halaman asesor (penilaian, profil)
- `pesantren/` — halaman pesantren (profil, IPM, SDM, EDPM, akreditasi)
- `layout/` — komponen layout (sidebar, navbar, notification)

### Service Layer

| Service | Tanggung Jawab |
|---------|----------------|
| `PesantrenService` | CRUD profil, IPM, SDM, EDPM, submission akreditasi |
| `AkreditasiService` | Approve/reject/finalize akreditasi, asesor assignment |
| `AkreditasiWorkflowService` | Orkestrasi canonical workflow akreditasi, transition status, finalisasi asesor, penerbitan SK |
| `AkreditasiDocumentService` | Upload dan validasi dokumen workflow akreditasi, termasuk dokumen pasca visitasi |
| `AsesorService` | Profil asesor, penilaian NA/NK, visitasi |
| `AssessorScoringService` | Draft/final NA, NK, NV, delta, dan immutability nilai |
| `ScoreCalculationService` | Perhitungan nilai akhir dan peringkat akreditasi |
| `DocumentService` | Master dokumen template |
| `UserService` | Manajemen akun |
| `RejectionService` | Alur penolakan terstruktur + partial unlock |
| `BandingService` | Alur banding (appeal) |
| `TrashService` | Restore/force-delete akreditasi yang di-soft-delete |
| `AuditTrailService` | Log perubahan status akreditasi |
| `DeadlineService` | Reminder dan eskalasi deadline assessment |
| `OnboardingService` | Panduan onboarding pesantren baru |
| `SidebarProgressService` | Progress bar sidebar pesantren |

### Repository Pattern

Interface di `app/Repositories/Contracts/`, implementasi Eloquent di `app/Repositories/Eloquent/`.

## Role & Permission

### Role IDs (konstanta di `Role` model)

```php
Role::ID_ADMIN       = 1
Role::ID_ASESOR      = 2
Role::ID_PESANTREN   = 3
Role::ID_SUPER_ADMIN = 4
```

### Middleware

- `role:admin` — hanya admin (role_id=1) dan super admin (role_id=4)
- `role:asesor` — hanya asesor (role_id=2)
- `role:pesantren` — hanya pesantren (role_id=3)
- `permission:xxx` — granular permission dari tabel `permissions`

### Gate & Policy

`AuthServiceProvider` mendaftarkan Gate untuk setiap permission key dari DB. Policy files di `app/Policies/` untuk model utama (Akreditasi, Pesantren, Asesor, Banding, Document, Ipm, SdmPesantren).

## Alur Akreditasi

Acuan bisnis resmi untuk workflow akreditasi LP2M ada di
[`docs/business-spec-flow-lp2m-v1.md`](business-spec-flow-lp2m-v1.md). Ringkasan
di bawah hanya gambaran arsitektur status.

```
Pesantren mengisi data (Profil + IPM + SDM + EDPM)
  -> Pengajuan (status=6)
  -> Review Awal Admin (status=5)
  -> Review Asesor (status=4)
  -> Visitasi (status=3)
  -> Penilaian Pasca Visitasi (status=2)
  -> Validasi Akhir Admin (status=1)
  -> Selesai / Terakreditasi (status=0)
  -> Ditolak Final (status=-1)
  -> Banding (status=-2)
```

Catatan transisi banding: `Banding (-2)` hanya boleh kembali ke
`Validasi Akhir Admin (1)` jika diterima, atau `Ditolak Final (-1)` jika
ditolak. Banding diterima tidak membuat pengajuan baru dan tidak kembali ke
Visitasi.

### Dokumen Penilaian Pasca Visitasi

Detail implementasi dokumen pasca visitasi dicatat di
[`docs/post-visitasi-documents-implementation.md`](post-visitasi-documents-implementation.md).

Dokumen wajib sebelum finalisasi asesor dan penerbitan SK:

- `laporan_visitasi_asesor1`
- `laporan_visitasi_asesor2`
- `laporan_visitasi_kelompok`
- `kartu_kendali`

Guard utama ada di `AkreditasiDocumentService` dan
`AkreditasiWorkflowService`. Upload hanya diizinkan pada status
`Penilaian Pasca Visitasi (2)`, sedangkan penerbitan SK di status `Validasi Admin (1)`
tetap memeriksa ulang kelengkapan dokumen untuk mencegah jalur bypass.

## Database Cascade Rules

Dari `User::boot()` deleting hook:
- `User` → delete `Pesantren`, `Asesor`, `Akreditasi[]`, `Ipm`, `SdmPesantren[]`, `Edpm[]`, `EdpmCatatan[]`, `Profile`

Dari `Pesantren::boot()` deleting hook:
- `Pesantren` → delete `PesantrenUnit[]` + hapus 18 file kolom dari storage

Dari `Ipm::boot()` deleting hook:
- `Ipm` → hapus 4 file kolom dari storage

Dari `Akreditasi::boot()` deleting hook:
- `Akreditasi` → delete `Assessment[]`, `AkreditasiEdpm[]`, `AkreditasiEdpmCatatan[]` + hapus file (sertifikat, kartu_kendali, laporan_visitasi)

## Queue & Async

Semua notifikasi (`AkreditasiNotification`) berjalan async via queue `notifications`. Konfigurasi di `config/queue.php` dengan `after_commit=true` agar notifikasi hanya dikirim setelah transaction DB commit.

Failed notifications dicatat di tabel `failed_notifications` dan bisa di-retry via dashboard admin `/admin/failed-notifications`.

## SSO Integration

SSO via Muhammadiyah ID menggunakan OAuth2 Authorization Code flow:

```
/sso/preflight → redirect ke IdP
IdP callback → /sso/auth (exchange code → token, simpan di session)
/sso/login (ambil token dari session, fetch user info, login)
```

Token disimpan encrypted di tabel `profiles.access_token` (Laravel `encrypted` cast).

## File Storage

Semua upload saat ini menggunakan disk `public` (symlink ke `public/storage`). Untuk production multi-node, set `FILESYSTEM_DISK=s3` dan konfigurasi `AWS_*` env vars.

File cleanup otomatis saat record dihapus via `deleting` hooks di model.

## Scheduled Commands

| Command | Jadwal | Fungsi |
|---------|--------|--------|
| `trash:purge` | Daily | Hapus permanen akreditasi > 90 hari di trash |
| `akreditasi:check-deadlines` | Daily | Reminder + eskalasi deadline assessment |
| `banding:check-deadlines` | Daily | Reminder deadline review banding |
| `perbaikan:check-deadlines` | Daily | Cek deadline perbaikan |
| `reminders:asesor2` | Daily | Reminder asesor 2 yang belum selesai |
