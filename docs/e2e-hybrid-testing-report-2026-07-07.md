# Hybrid E2E Testing Report - 2026-07-07

## Scope

Hybrid E2E verification for the accreditation system:

- HTTP E2E for full business transitions and persistence.
- Browser smoke for critical role pages and UI rendering.
- Full test suite and frontend build verification after adding E2E coverage.

## Artifacts

- Plan: `docs/e2e-hybrid-testing-plan-2026-07-07.md`
- Implementation plan: `docs/superpowers/plans/2026-07-07-hybrid-e2e-testing.md`
- HTTP E2E test: `tests/Feature/E2E/HybridAccreditationFlowTest.php`
- Browser smoke script: `output/playwright/e2e-hybrid-browser-smoke.mjs`
- Browser smoke JSON/screenshots: `storage/app/visual-smoke/e2e-hybrid/`

## Commands Run

```bash
php artisan test tests/Feature/E2E/HybridAccreditationFlowTest.php
node output/playwright/e2e-hybrid-browser-smoke.mjs
php artisan test
npm run build
git diff --check
```

## Results

| Check | Result |
|---|---:|
| HTTP hybrid E2E | PASS — 1 test, 66 assertions |
| Browser smoke | PASS — 19 routes, issues `[]` |
| Full PHPUnit suite | PASS — 3082 passed, 3 skipped, 58902 assertions |
| Frontend build | PASS — Vite built in 3.85s |
| Whitespace check | PASS — exit 0 |

Non-blocking warning:

```txt
Browserslist: browsers data (caniuse-lite) is 7 months old.
```

## HTTP E2E Coverage

`tests/Feature/E2E/HybridAccreditationFlowTest.php` validates the canonical accreditation path:

1. Pesantren submits accreditation through `pesantren.akreditasi.create`.
2. Admin opens review through `admin.akreditasi-detail.open-for-review`.
3. Admin approves documents and assigns two assessors through `admin.akreditasi-detail.approve-berkas`.
4. Ketua Kelompok schedules visitasi through `asesor.akreditasi.schedule-visitasi`.
5. Ketua Kelompok confirms visitasi completion through `asesor.akreditasi.confirm-visitasi-selesai`.
6. Asesor uploads individual report and group report through HTTP upload routes.
7. Pesantren uploads kartu kendali through `pesantren.akreditasi.upload-kartu-kendali`.
8. Asesor finalizes scoring through `asesor.akreditasi.finalize-scoring`.
9. Admin saves draft NV through `admin.akreditasi-detail.save-nv`.
10. Admin finalizes all NV through `admin.akreditasi-detail.finalize-nv`.
11. Admin issues SK through `admin.akreditasi-detail.approve`.
12. Pesantren can load the final detail/result page.

Key assertions:

- Status transitions reach `0 Selesai`.
- Pesantren data locks after submission.
- Two assessments are created.
- Upload routes store files and set DB fields.
- NV final flag becomes true only after finalization.
- SK fields, score, and predicate are persisted.
- Status-change audit logs exist.

## Browser Smoke Coverage

`output/playwright/e2e-hybrid-browser-smoke.mjs` drives Chrome/CDP against `http://spm_fix.test` with real login forms.

Routes covered:

- Super Admin: dashboard, accounts, role permission, admin akreditasi.
- Admin: dashboard, akreditasi list, akreditasi detail instrumen, banding.
- Asesor: dashboard, profile, akreditasi list, akreditasi detail instrumen.
- Pesantren: dashboard, profile, IPM, SDM, EDPM, akreditasi list, akreditasi detail.

Checks per route:

- Not redirected to `/login`.
- No visible `403`, `404`, `500`, `Forbidden`, `Not Found`, or `Server Error` text.
- No horizontal overflow at desktop viewport.
- Screenshot saved.

Browser smoke result:

```json
{
  "count": 19,
  "issues": []
}
```

## Known Limitations

- Browser smoke verifies rendering/role access, not a full click-through workflow.
- Full browser workflow is intentionally avoided because HTTP E2E is more stable for business transitions and file upload assertions.
- Browser smoke depends on local demo users at `spm_fix.test`.
- Local MySQL schema may still be stale for `BusinessFlowTestSeeder` because `roles.parameter` is missing; this report does not require destructive local DB reset.

## Follow-up

- Keep HTTP E2E as the regression gate for business-flow changes.
- Add specific browser interactions only when UI behavior itself changes.
- Consider creating a project `/run` skill later to standardize browser smoke setup and fixture prep.
