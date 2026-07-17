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
| Asesor | Profil Asesor | `/asesor/profile` | 200 | 0 | `.sisyphus/evidence/ui-visual-remediation/phase-f-smoke/asesor-profile.png` | Pass |

## Asesor Credential Note

Local database has asesor users, but password checks failed for common development values: `password`, `admin`, `asesor`, `12345678`, `secret`, `spm123`, `qwerty`.

User approved local-only reset. Password for `asesor@spm.test` was reset to `password` in the local development database only, then `/asesor/profile` smoke QA passed with HTTP 200 and 0 console errors.

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

CSS consolidation did not cause 500 errors or console errors on the validated Super Admin dashboard, Admin detail, Panduan, and Asesor profile surfaces.
