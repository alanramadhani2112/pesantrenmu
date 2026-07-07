# UI Role Audit - 2026-07-07

## Scope

Browser smoke audit after asesor HTTP contract and admin NV/final-decision fixes.

## Environment

- App: `http://spm_fix.test`
- Browser: Chrome headless via CDP
- Script: `output/playwright/task4-role-smoke.mjs`
- Screenshots/report: `storage/app/visual-smoke/task4-role-smoke/`
- Fixture: existing local demo users plus authorized local update to demo akreditasi `5b032b34-8e1e-460a-b59b-1cc6eb2ac410` for Validasi Admin/NV smoke.

Seeder note: `php artisan db:seed --class=BusinessFlowTestSeeder` was not usable against current local MySQL because `roles.parameter` is missing in this schema. No destructive migration was run.

## Result

Command:

```bash
node output/playwright/task4-role-smoke.mjs
```

Result:

```txt
count: 19
issues: []
```

## Checklist

| Role | Route | Expected | Result | Evidence |
|---|---|---|---|---|
| Super Admin | `/dashboard` | Dashboard loads; no auth/error/overflow | Pass | `storage/app/visual-smoke/task4-role-smoke/super-admin-dashboard.png` |
| Super Admin | `/accounts` | User management loads | Pass | `storage/app/visual-smoke/task4-role-smoke/super-admin-accounts.png` |
| Super Admin | `/admin/master-role-permission` | Permission matrix loads | Pass | `storage/app/visual-smoke/task4-role-smoke/super-admin-role-permission.png` |
| Super Admin | `/admin/akreditasi` | Admin akreditasi list loads | Pass | `storage/app/visual-smoke/task4-role-smoke/super-admin-admin-akreditasi.png` |
| Admin | `/dashboard` | Dashboard loads | Pass | `storage/app/visual-smoke/task4-role-smoke/admin-dashboard.png` |
| Admin | `/admin/akreditasi` | Akreditasi list loads | Pass | `storage/app/visual-smoke/task4-role-smoke/admin-akreditasi.png` |
| Admin | `/admin/akreditasi/5b032b34-8e1e-460a-b59b-1cc6eb2ac410?tab=instrumen` | NV form visible; final-decision path present; no auth/error/overflow | Pass | `storage/app/visual-smoke/task4-role-smoke/admin-akreditasi-detail-instrumen.png` |
| Admin | `/admin/banding` | Banding list loads | Pass | `storage/app/visual-smoke/task4-role-smoke/admin-banding.png` |
| Asesor | `/dashboard` | Dashboard loads | Pass | `storage/app/visual-smoke/task4-role-smoke/asesor-dashboard.png` |
| Asesor | `/asesor/profile` | Profile loads | Pass | `storage/app/visual-smoke/task4-role-smoke/asesor-profile.png` |
| Asesor | `/asesor/akreditasi` | Task list loads | Pass | `storage/app/visual-smoke/task4-role-smoke/asesor-akreditasi.png` |
| Asesor | `/asesor/akreditasi/5b032b34-8e1e-460a-b59b-1cc6eb2ac410?tab=instrumen` | Detail loads | Pass | `storage/app/visual-smoke/task4-role-smoke/asesor-akreditasi-detail-instrumen.png` |
| Pesantren | `/dashboard` | Dashboard loads | Pass | `storage/app/visual-smoke/task4-role-smoke/pesantren-dashboard.png` |
| Pesantren | `/pesantren/profile` | Profile loads | Pass | `storage/app/visual-smoke/task4-role-smoke/pesantren-profile.png` |
| Pesantren | `/pesantren/ipm` | IPM loads | Pass | `storage/app/visual-smoke/task4-role-smoke/pesantren-ipm.png` |
| Pesantren | `/pesantren/sdm` | SDM loads | Pass | `storage/app/visual-smoke/task4-role-smoke/pesantren-sdm.png` |
| Pesantren | `/pesantren/edpm` | EDPM loads | Pass | `storage/app/visual-smoke/task4-role-smoke/pesantren-edpm.png` |
| Pesantren | `/pesantren/akreditasi` | Akreditasi list loads | Pass | `storage/app/visual-smoke/task4-role-smoke/pesantren-akreditasi.png` |
| Pesantren | `/pesantren/akreditasi/5b032b34-8e1e-460a-b59b-1cc6eb2ac410` | Detail loads | Pass | `storage/app/visual-smoke/task4-role-smoke/pesantren-akreditasi-detail.png` |

## Findings

No blocking findings from browser smoke.

## Notes

- CDP checks asserted: not redirected to `/login`, no visible 403/404/500 text, no horizontal overflow.
- Admin instrumen detail showed `62` NV selects and `62` reason textareas, confirming editable NV UI contract renders in browser.
- Admin instrumen detail also detected final-decision text, confirming final path is present after Validasi Admin fixture setup.
