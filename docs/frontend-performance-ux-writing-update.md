# Frontend Performance and UX Writing Update

Date: 2026-05-24
Scope: Laravel Blade, Livewire Volt, reusable Metronic UI components

## Summary

Perubahan terakhir berfokus pada perapian frontend sistem SPM Fix
tanpa keluar dari pola komponen Metronic yang reusable.

Area utama yang disentuh:

- Optimasi loading gambar.
- Pengurangan polling yang terlalu agresif.
- Konsistensi UX writing berbahasa Indonesia.
- Standardisasi komponen modal dan badge.

## Goals

- Mempercepat pengalaman awal halaman dengan atribut loading gambar yang eksplisit.
- Menjaga UI tetap konsisten memakai komponen `x-ui.*` dan Metronic.
- Menyesuaikan istilah antarmuka dengan konteks sistem akreditasi pesantren.
- Mengurangi beban request otomatis dari halaman detail akreditasi.

## Changes

### Performance

- Added image loading hints across public, auth, error, layout,
  profile, and akreditasi pages:
  - `loading="lazy"` for non-critical images, avatars,
    document previews, and footer/error logos.
  - `loading="eager"` and `fetchpriority="high"` for critical
    landing/logo assets where appropriate.
- Added safe Metronic CSS preload hints in `resources/views/layouts/app.blade.php`:
  - `vendor/metronic/assets/plugins/global/plugins.bundle.css`
  - `vendor/metronic/assets/css/style.bundle.css`
- Changed admin akreditasi detail polling from `wire:poll.10s`
  to `wire:poll.30s` to reduce repeated background requests.

### UX Writing

Standardized visible English or less-contextual labels into Indonesian
terms suitable for the accreditation system:

| Before | After |
| --- | --- |
| Reset | Atur Ulang |
| Pending | Tertunda |
| Overdue | Terlambat |
| Assign Reviewer | Tunjuk Peninjau |
| Reviewer | Peninjau |
| Assign | Tugaskan |
| Under Review | Dalam Peninjauan |
| Accepted | Diterima |
| Rejected | Ditolak |
| Upload File | Unggah File |
| Max | Maks. |

### Reusable Metronic Components

- Replaced raw workflow step badges in asesor and pesantren
  akreditasi pages with `x-ui.badge`.
- Updated the admin akreditasi notes modal to use reusable modal components:
  - `x-ui.modal-header`
  - `x-ui.modal-body`
  - `x-ui.modal-footer`
  - `x-ui.form-field`

## Files Updated

- `resources/views/layouts/app.blade.php`
- `resources/views/components/application-logo.blade.php`
- `resources/views/components/layout/app-header.blade.php`
- `resources/views/components/layout/app-sidebar.blade.php`
- `resources/views/errors/403.blade.php`
- `resources/views/errors/404.blade.php`
- `resources/views/errors/419.blade.php`
- `resources/views/errors/429.blade.php`
- `resources/views/errors/500.blade.php`
- `resources/views/errors/503.blade.php`
- `resources/views/welcome.blade.php`
- `resources/views/livewire/pages/auth/login.blade.php`
- `resources/views/livewire/pages/admin/audit-timeline.blade.php`
- `resources/views/livewire/pages/admin/akreditasi.blade.php`
- `resources/views/livewire/pages/admin/akreditasi-detail.blade.php`
- `resources/views/livewire/pages/admin/banding.blade.php`
- `resources/views/livewire/pages/admin/banding-detail.blade.php`
- `resources/views/livewire/pages/admin/asesor/detail.blade.php`
- `resources/views/livewire/pages/asesor/akreditasi.blade.php`
- `resources/views/livewire/pages/asesor/profile.blade.php`
- `resources/views/livewire/pages/pesantren/akreditasi.blade.php`
- `resources/views/livewire/pages/pesantren/profile.blade.php`

## Verification

Verification completed after the changes:

- `lsp_diagnostics`: no diagnostics on edited Blade files.
- `php artisan view:clear`: successful.
- `php artisan test tests/Feature/MetronicFrontendTest.php`: passed.
  - 23 tests passed.
  - 579 assertions passed.
- Browser smoke test with Playwright:
  - `/` loaded successfully with zero console errors.
  - `/login` loaded successfully with zero console errors.
  - Login page logo image reported an explicit lazy loading strategy.

## Notes

- The full project test suite was not rerun in this final
  documentation step. The latest focused frontend suite passed after
  the frontend changes.
- Existing broad working-tree changes outside this frontend update were left untouched.
