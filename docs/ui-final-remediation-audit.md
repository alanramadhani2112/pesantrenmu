<!-- markdownlint-disable MD013 MD032 MD060 -->

# UI Final Remediation Audit

Final audit setelah Phase A-F `ui-visual-remediation-rollout`.

## Scope

Audit ini mengecek sisa pattern visual lama setelah fase berikut selesai:

- Phase A: Asesor profile cleanup.
- Phase B: Admin entity detail cleanup.
- Phase C: Panduan/support cleanup.
- Phase D: Public/auth polish.
- Phase E: Workflow/scoring cleanup.
- Phase F: CSS override consolidation + browser smoke QA.

Audit ini hanya mencatat sisa temuan. Tidak ada refactor kode aplikasi di dokumen ini.

## Status Phase A-F

| Area | Status | Catatan |
|---|---|---|
| Asesor profile | Done | Raw button/icon/density utama sudah dibersihkan. Smoke QA asesor `/asesor/profile` pass setelah reset password lokal. |
| Admin entity detail | Done | `admin/asesor/detail` dan `admin/pesantren/detail` sudah memakai density lebih netral. |
| Panduan/support | Done | Layout dan role guide density sudah diturunkan; pesantren intro dinetralkan. |
| Public/auth | Done | Landing dan auth actions sudah memakai `x-ui.*` sejauh aman. |
| Workflow/scoring | Done | Scoring notices/table/action surfaces dan EDPM icons dibersihkan. |
| CSS overrides | Done | Tab style duplikat dikurangi, table shadow dimatikan, section header gradient pesantren dinetralkan. |

## Verification Completed

- `npm run build` passed for CSS changes.
- `php artisan view:clear` passed.
- `php artisan view:cache` passed.
- `php artisan test tests\Feature\MetronicFrontendTest.php --stop-on-failure` passed with `TEST_EXIT_CODE=0`.
- Phase F browser smoke QA documented in `docs/ui-phase-f-browser-smoke.md`.

## Remaining Pattern Scan Summary

Final grep still finds some matches. Most are acceptable because they live in reusable components, shell/layout code, auth/error shell spacing, or Metronic-required patterns. Remaining items are backlog, not blocking regressions.

| Pattern group | Remaining count | Main locations | Classification |
|---|---:|---|---|
| Density markers (`p-6`, `mb-6`, `px-6`, `py-6`, `row g-6`, `gap-6`) | 40 | error pages, reusable components, document viewer, detail tab shell padding, some legacy support components | Backlog / acceptable per layout context |
| Raw button/icon markers | 18 | layout shell, error illustrations, panduan shell controls, `laporan-visitasi` partial icons, reusable components | Mostly shell/component exceptions; review before changing |
| `bg-light-*`, uppercase, old table markers | 20 | reusable components, app shell, action menu, role-permission/account pages, one laporan visitasi partial | Mostly component/status semantics; minor backlog |
| CSS shadow/uppercase/gradient references | 221 | CSS foundations, sidebar, landing/auth, role polish files | Expected CSS inventory; needs selective cleanup only with browser evidence |

## Non-blocking Backlog

### 1. Error pages shell density/icons

Files:

- `resources/views/errors/403.blade.php`
- `resources/views/errors/404.blade.php`
- `resources/views/errors/419.blade.php`
- `resources/views/errors/429.blade.php`
- `resources/views/errors/500.blade.php`
- `resources/views/errors/503.blade.php`

Notes:

- Still use `p-6`, `px-6`, and some raw illustration icons.
- Buttons are already `x-ui.button` from previous cleanup.
- Treat as low-risk polish only; not blocking app dashboard consistency.

### 2. Reusable component and shell exceptions

Files include:

- `resources/views/components/ui/*`
- `resources/views/components/layout/app-header.blade.php`
- `resources/views/components/layout/app-sidebar.blade.php`
- `resources/views/components/panduan/layout.blade.php`

Notes:

- Some raw Metronic icon/button primitives remain because they are shell controls or reusable component internals.
- Do not blanket-replace without browser QA because shell controls can depend on Metronic-specific markup.

### 3. Detail/workflow lower-priority leftovers

Files include:

- `resources/views/admin/akreditasi/detail/tabs/laporan-visitasi.blade.php`
- `resources/views/admin/master-edpm/index.blade.php`
- `resources/views/documents/index.blade.php`
- `resources/views/pesantren/akreditasi-detail.blade.php`

Notes:

- Remaining matches are isolated and not the main visual inconsistencies found earlier.
- Refactor only after browser screenshots show actual visual drift.

### 4. CSS inventory still large

Files include:

- `resources/css/metronic-overrides/85-pesantren-polish.css`
- `resources/css/metronic-overrides/60-visual-normalization.css`
- `resources/css/metronic-overrides/90-sidebar-brand-guard.css`
- `resources/css/metronic-overrides/55-landing*.css`

Notes:

- Many `box-shadow`, `linear-gradient`, and uppercase rules are foundational or page-family specific.
- Phase F intentionally avoided broad CSS deletion.
- Future CSS cleanup should be screenshot-led, not grep-led.

## Recommendation

Stop broad remediation here. Next work should be browser-led, page-specific polish:

1. Pick one visible page family from screenshots.
2. Capture before screenshot.
3. Patch only visible issues.
4. Capture after screenshot.
5. Commit small.

Suggested next focus if user wants more polish:

- Error page visual polish.
- Panduan mobile/sidebar shell QA.
- `laporan-visitasi` detail tab visual polish.
- Final cross-role screenshot review for dashboard/list/detail/profile only.

## Conclusion

Phase A-F remediation is functionally complete. Remaining grep matches are mostly shell/component exceptions or low-priority polish. Further changes should be driven by browser screenshots, not automatic search replacement.
