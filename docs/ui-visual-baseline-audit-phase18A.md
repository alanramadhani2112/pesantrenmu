<!-- markdownlint-disable MD013 MD032 MD060 -->

# UI Visual Baseline Audit Phase 18A

## Scope

Baseline visual QA sebelum refactor visual-final. Evidence disimpan di `.sisyphus/evidence/ui-visual-refactor/phase-18A/`.

## Screenshots Captured

| Role/Area | URL | Evidence | Status |
|---|---|---|---|
| Super admin | `/dashboard` | `.sisyphus/evidence/ui-visual-refactor/phase-18A/superadmin-dashboard-before.png` | Captured |
| Super admin | `/admin/akreditasi` | `.sisyphus/evidence/ui-visual-refactor/phase-18A/superadmin-admin-akreditasi-before.png` | Captured |
| Super admin | `/admin/akreditasi/{uuid}` | `.sisyphus/evidence/ui-visual-refactor/phase-18A/superadmin-admin-akreditasi-detail-before.png` | Captured |
| Pesantren | `/dashboard` | `.sisyphus/evidence/ui-visual-refactor/phase-18A/pesantren-dashboard-before.png` | Captured |
| Pesantren | `/pesantren/akreditasi` | `.sisyphus/evidence/ui-visual-refactor/phase-18A/pesantren-akreditasi-before.png` | Captured |
| Pesantren | `/pesantren/edpm` | `.sisyphus/evidence/ui-visual-refactor/phase-18A/pesantren-edpm-before.png` | Captured |
| Asesor | `/dashboard` | `.sisyphus/evidence/ui-visual-refactor/phase-18A/asesor-dashboard-before.png` | Captured |
| Asesor | `/asesor/akreditasi` | `.sisyphus/evidence/ui-visual-refactor/phase-18A/asesor-akreditasi-before.png` | Captured |
| Public | `/` | `.sisyphus/evidence/ui-visual-refactor/phase-18A/public-home-before.png` | Captured |
| Auth | `/login` | `.sisyphus/evidence/ui-visual-refactor/phase-18A/auth-login-before.png` | Captured |
| Auth | `/forgot-password` | `.sisyphus/evidence/ui-visual-refactor/phase-18A/auth-forgot-password-before.png` | Captured |

## Runtime Issues Found During Baseline

1. `/dashboard` 500 for super admin due orphan `akreditasi` row with missing `user` relation.
   - Fixed by null-safe owner display in `DashboardController`.
   - Regression added to `MetronicFrontendTest`.
2. `/admin/akreditasi` 500 due orphan `akreditasi` row in list view.
   - Fixed by null-safe pesantren/email display in `admin/akreditasi/index.blade.php`.
   - Regression added to `MetronicFrontendTest`.
3. Seed credential `asesor@spm.test/password` did not authenticate in current DB.
   - Browser session still reached asesor dashboard as `Asesor Demo`; screenshots captured.
   - Follow-up: normalize local seed/test credential if repeated visual QA needs deterministic login.

## Visual Issue Notes

| Area | Severity | Issue | Likely Root Cause | Suggested Phase |
|---|---|---|---|---|
| Role dashboards | Major | First fold still feels different per role; hero/cards/activity rhythm not yet fully unified visually. | Dashboard branches have role-specific composition and copy density. | Phase 18D |
| Admin akreditasi list | Major | Workflow/list page still dense: many filters, badges, stepper, and table data compete for attention. | Page combines monitoring, workflow status, and operations in one surface. | Phase 18C/18E |
| Admin akreditasi detail | Major | Detail/workflow area has many sections and tabbed scoring content; needs stronger hierarchy and calmer density. | Workflow detail grew by feature area, not shared detail-page grammar. | Phase 18F |
| Pesantren EDPM | Major | EDPM/IPR form surface remains visually heavy and long. | Large matrix/form workflow, many nested sections. | Phase 18F |
| Pesantren akreditasi | Medium | Workflow actions and status cards need stronger progression hierarchy. | Mixed dashboard/list/workflow patterns. | Phase 18C/18F |
| Asesor akreditasi | Medium | Assignment list is cleaner but still needs table/filter/action alignment check. | Asesor workflow has custom action states. | Phase 18E/18F |
| Public/auth | Medium | Public/auth surfaces need clean SPM visual alignment, but must avoid marketing redesign. | Separate public/auth templates from app shell. | Phase 18G |

## Phase 18B Recommendation

Start with component foundation before more page-specific edits:

1. `x-ui.card` / `x-ui.section-card`: reduce visual weight and normalize body/header spacing.
2. `x-ui.index-layout` / `x-ui.page`: enforce one consistent title/toolbar rhythm.
3. `x-ui.table` / `x-ui.filter-bar`: make list/filter surfaces compact by default.
4. `x-ui.button` / `x-ui.badge`: keep primary/action/status hierarchy strict.

This should make later page refactors visibly different without many one-off patches.
