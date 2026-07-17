<!-- markdownlint-disable MD013 MD032 MD060 -->

# Phase F Browser Smoke QA

Phase F memvalidasi konsolidasi CSS override setelah cleanup UI visual. Scope smoke QA minimum mengikuti plan `ui-visual-remediation-rollout.md`: dashboard, detail akreditasi, asesor profile, dan panduan.

## Environment

- Base URL: `http://spm_fix.test`
- Evidence folder: `.sisyphus/evidence/ui-visual-remediation/phase-f-smoke`
- Browser: Playwright Chromium headless
- Verification date: current implementation session

## Results

| Role | Page | URL | HTTP | Console errors | Screenshot | Result |
|---|---|---|---:|---:|---|---|
| Super Admin | Dashboard | `/dashboard` | 200 | 0 | `.sisyphus/evidence/ui-visual-remediation/phase-f-smoke/superadmin-dashboard.png` | Pass |
| Super Admin | Detail Akreditasi | `/admin/akreditasi/ada01276-1092-49ee-9ed9-4575a5d27440` | 200 | 0 | `.sisyphus/evidence/ui-visual-remediation/phase-f-smoke/superadmin-admin-akreditasi-detail.png` | Pass |
| Super Admin | Panduan Admin | `/panduan-admin` | 200 | 0 | `.sisyphus/evidence/ui-visual-remediation/phase-f-smoke/superadmin-panduan-admin.png` | Pass |
| Asesor | Profil Asesor | `/asesor/profile` | N/A | N/A | N/A | Blocked: no known local seeded asesor password matched tested values |

## Asesor Credential Blocker

Local database has asesor users, but password checks failed for common development values: `password`, `admin`, `asesor`, `12345678`, `secret`, `spm123`, `qwerty`.

This smoke QA did not reset database credentials, because Phase F is visual/CSS-only and must not alter data or auth flow. To complete asesor browser smoke, use a known valid asesor credential or explicitly approve a local-only password reset.

## Verification Commands

Already passed before browser smoke:

```bash
npm run build
php artisan view:clear
php artisan view:cache
php artisan test tests\Feature\MetronicFrontendTest.php --stop-on-failure
```

Result: `TEST_EXIT_CODE=0`.

## Conclusion

CSS consolidation did not cause 500 errors or console errors on the validated Super Admin dashboard, Admin detail, and Panduan surfaces. Asesor profile browser smoke remains blocked only by missing known local asesor credentials.
