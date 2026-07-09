# UI Polish Metronic Plan — 2026-07-09

## Goal

Polish UI per halaman dengan urutan role:

1. Super Admin
2. Admin
3. Asesor
4. Pesantren

Target bukan redesign besar. Target: konsistensi, reuse component, standar Metronic, icon valid, table/form/modal/action pattern seragam, tanpa mengubah business logic.

## Current Baseline

Evidence saat ini:

- Browser smoke 19 route pass tanpa blocking issue: `docs/ui-role-audit-2026-07-07.md`
- Token brand tersedia: `docs/brand-tokens.md`
- Metronic asset policy tersedia: `docs/metronic-asset-strategy.md`
- UI direction tersedia: `docs/frontend-ui-ux-positioning.md`
- Component dasar sudah ada di `resources/views/components/ui/`
- Full test suite terakhir: 3104 passed, 3 skipped

## Non-Goals

- Tidak mengganti layout total.
- Tidak copy HTML Metronic mentah per halaman.
- Tidak tambah plugin Metronic baru kecuali ada kebutuhan nyata.
- Tidak ubah workflow, policy, permission, route, atau DB schema.
- Tidak buat design system besar “untuk nanti”.

## UI Standards

### 1. Page Structure

Setiap halaman utama pakai pola:

- `x-ui.page` atau layout sepadan
- page title + subtitle/help text jelas
- toolbar kanan untuk action utama
- filter/search di area konsisten
- content card dengan spacing Metronic

### 2. Table Standard

Semua table operasional wajib konsisten:

- card wrapper Metronic
- search/filter/per-page di atas table
- header sortable bila backend mendukung
- status pakai `x-ui.status-badge`/`x-ui.badge`
- action kanan pakai `x-ui.action-menu`
- empty state pakai `x-ui.empty-state`
- pagination pakai `x-ui.pagination`
- responsive wrapper tanpa horizontal overflow global

### 3. Form Standard

- input/select/textarea pakai component `x-ui.*` bila sudah tersedia
- label, help text, validation error dekat field
- destructive action wajib confirmation
- primary action jelas; secondary action tidak bersaing visual

### 4. Modal Standard

- `x-ui.modal`, `modal-header`, `modal-body`, `modal-footer`
- action order konsisten: cancel kiri/secondary, submit kanan/primary
- error/required context ada di body, bukan hanya toast

### 5. Icon Standard

- semua icon lewat `x-ui.icon` bila memungkinkan
- nama icon valid sesuai icon set yang aktif
- icon-only button wajib label/tooltip atau accessible text
- destructive icon pakai warna danger; jangan hanya icon tanpa label di flow kritis

### 6. Component Reuse Rule

Buat/revisi component hanya jika pola muncul di minimal 3 tempat atau sudah jelas jadi primitive UI:

- table shell
- action menu
- filter bar
- status badge
- page header/toolbar
- empty state
- form field
- modal

Satu halaman unik tetap boleh inline Blade jika tidak berulang.

## Execution Order

## Phase 0 — UI Inventory and Component Gap

Goal: tahu halaman mana sudah pakai component, mana masih legacy markup.

Scope:

- daftar semua view per role
- audit komponen `resources/views/components/ui/`
- audit icon usage
- audit table/action/menu pattern

Deliverable:

- checklist halaman + gap pattern
- list component yang perlu dibuat/diperbaiki

Suggested checks:

```bash
npm run build
php artisan test tests/Feature/MetronicFrontendTest.php
```

## Phase 1 — Super Admin Polish

Priority karena Super Admin mengelola governance dan konfigurasi global.

Pages:

- `/dashboard`
- `/accounts`
- `/admin/master-role-permission`
- `/admin/roles`
- `/admin/failed-notifications`
- `/admin/trash`

Focus:

- user/account table konsisten
- role table konsisten
- permission matrix lebih mudah dipindai
- canonical role action tetap tersembunyi/disabled jelas
- failed notification action menu konsisten
- trash restore/purge action jelas dan destructive-safe

Tests/checks:

- existing role/permission/failed notification/trash feature tests
- visual smoke Super Admin route set

## Phase 2 — Admin Polish

Priority karena Admin menjalankan flow operasional utama.

Pages:

- `/dashboard`
- `/admin/akreditasi`
- `/admin/akreditasi/{id}` semua tab utama
- `/admin/asesor`
- `/admin/pesantren`
- `/admin/banding`
- `/admin/master-dokumen`
- `/admin/master-kategori-dokumen`
- `/admin/master-edpm`

Focus:

- akreditasi list: filter, status badge, action menu, pagination
- akreditasi detail: tabs/sections konsisten, NV table readable, final action prominent
- master data pages: table toolbar/action/modal same pattern
- banding: status and decision action clarity
- document download links tetap authenticated route

Tests/checks:

- `php artisan test tests/Feature/Admin*HttpTest.php`
- `php artisan test tests/Feature/Master*Test.php tests/Feature/Document*Test.php`
- visual smoke Admin route set

## Phase 3 — Asesor Polish

Priority setelah Admin karena Asesor butuh low-friction data entry.

Pages:

- `/dashboard`
- `/asesor/profile`
- `/asesor/akreditasi`
- `/asesor/akreditasi/{id}` semua tab utama

Focus:

- task list: status/deadline/action clarity
- detail tabs: instrumen scoring readable, save/finalize distinction clear
- laporan visitasi upload area consistent
- profile form componentized

Tests/checks:

- `php artisan test tests/Feature/Asesor*HttpTest.php tests/Feature/Asesor*VisibilityTest.php`
- visual smoke Asesor route set

## Phase 4 — Pesantren Polish

Priority terakhir karena guided flow harus rapi setelah admin/asesor pattern stabil.

Pages:

- `/dashboard`
- `/pesantren/profile`
- `/pesantren/ipm`
- `/pesantren/sdm`
- `/pesantren/edpm`
- `/pesantren/akreditasi`
- `/pesantren/akreditasi/{id}`

Focus:

- guided completion/progress clarity
- form sections and upload controls consistent
- akreditasi status and next action obvious
- hasil akhir and banding entry point readable
- empty/error states friendly and actionable

Tests/checks:

- `php artisan test tests/Feature/Pesantren*Test.php tests/Feature/Pesantren/*Test.php`
- visual smoke Pesantren route set

## Phase 5 — Cross-Role Visual QA

Goal: final confidence before staging.

Checklist:

- desktop 1280px no overflow
- tablet 768px no broken table/action menu
- mobile 375px usable for key pages
- no visible 403/404/500 text on authorized route
- no invalid/missing icons
- all destructive actions have confirmation
- all status badges use approved variants
- empty state exists for major list pages

Recommended route smoke:

- reuse/update `output/playwright/task4-role-smoke.mjs`
- screenshot output to `storage/app/visual-smoke/ui-polish-2026-07-09/`

## Definition of Done

Per page done when:

- uses shared UI component where pattern repeats
- Metronic class usage consistent
- table/form/action/modal pattern matches standards above
- icon valid and accessible
- desktop/mobile basic layout okay
- no business logic changed
- relevant tests pass
- `npm run build` pass

Per phase done when:

- role route smoke pass
- no blocking visual issue
- focused tests pass
- changed pages listed in execution notes

## Suggested Branch Strategy

Use small branches/PRs or commits per phase:

1. `ui-polish-super-admin`
2. `ui-polish-admin`
3. `ui-polish-asesor`
4. `ui-polish-pesantren`
5. `ui-polish-final-smoke`

If working solo locally, still commit per phase. Easier rollback.

## Risks

- Over-componentization slows work.
  - Mitigation: component only for repeated patterns.
- Metronic plugin temptation adds JS risk.
  - Mitigation: Blade/Alpine first; no plugin unless required.
- UI polish accidentally changes behavior.
  - Mitigation: view-only diffs preferred; focused feature tests after each phase.
- Table standardization touches many pages.
  - Mitigation: start with one canonical table, then repeat.

## Recommended First Implementation Slice

Start with Phase 0 + one Super Admin page group:

1. audit components and icon usage
2. standardize `/accounts`
3. standardize `/admin/roles`
4. standardize `/admin/master-role-permission`
5. run Super Admin smoke

Reason: governance pages are high-value, low workflow complexity, good place to lock reusable table/action/form patterns before Admin akreditasi detail.
