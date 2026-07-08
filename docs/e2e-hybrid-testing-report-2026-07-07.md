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
| HTTP hybrid E2E | PASS — 2 tests, 113 assertions |
| Browser smoke | PASS — 19 routes, issues `[]` |
| Full PHPUnit suite | PASS — 3083 passed, 3 skipped, 59099 assertions |
| Frontend build | PASS — Vite built in 3.76s |
| Whitespace check | PASS — exit 0 |

Non-blocking warning:

```txt
Browserslist: browsers data (caniuse-lite) is 7 months old.
```

## HTTP E2E Coverage

`tests/Feature/E2E/HybridAccreditationFlowTest.php` validates the canonical accreditation path plus negative-path guardrails.

### Happy Path Result Table

| # | Step | Actor | Route/Action | Expected Result | Test Result |
|---:|---|---|---|---|---|
| 1 | Submit pengajuan | Pesantren | `pesantren.akreditasi.create` | Status `6 Pengajuan`; pesantren data locked | PASS |
| 2 | Open review | Admin | `admin.akreditasi-detail.open-for-review` | Status `5 Verifikasi Berkas` | PASS |
| 3 | Approve berkas + assign asesor | Admin | `admin.akreditasi-detail.approve-berkas` | Status `4 Review Asesor`; two assessments created | PASS |
| 4 | Schedule visitasi | Ketua Kelompok | `asesor.akreditasi.schedule-visitasi` | Status `3 Visitasi`; visitasi dates saved | PASS |
| 5 | Confirm visitasi selesai | Ketua Kelompok | `asesor.akreditasi.confirm-visitasi-selesai` | Status `2 Penilaian Pasca Visitasi` | PASS |
| 6 | Upload laporan individu | Asesor 1 + Asesor 2 | `asesor.akreditasi.upload-laporan-individu` | Both individual report files stored | PASS |
| 7 | Upload laporan kelompok | Ketua Kelompok | `asesor.akreditasi.upload-laporan-kelompok` | Group report file stored | PASS |
| 8 | Upload kartu kendali | Pesantren | `pesantren.akreditasi.upload-kartu-kendali` | Kartu kendali file stored | PASS |
| 9 | Seed complete scoring | Test fixture | DB scoring rows | NA/NK/catatan prerequisites complete | PASS |
| 10 | Finalize scoring | Ketua Kelompok | `asesor.akreditasi.finalize-scoring` | Status `1 Validasi Admin` | PASS |
| 11 | Save draft NV | Admin | `admin.akreditasi-detail.save-nv` | Draft NV saved; global final flag remains false | PASS |
| 12 | Finalize NV | Admin | `admin.akreditasi-detail.finalize-nv` | All required NV final; `is_nv_final = true` | PASS |
| 13 | Issue SK | Admin | `admin.akreditasi-detail.approve` | Status `0 Selesai`; SK/certificate/score/predicate persisted | PASS |
| 14 | View final result | Pesantren | `pesantren.akreditasi-detail?tab=hasil` | Final result visible; raw `NV`/`NK` admin routes hidden | PASS |
| 15 | Audit trail | System | `akreditasi_audit_logs` | Status-change audit logs exist | PASS |

### Negative Path Result Table

| # | Scenario | Actor | Route/Action | Expected Guardrail | Test Result |
|---:|---|---|---|---|---|
| 1 | Non-admin opens admin review | Asesor | `admin.akreditasi-detail.open-for-review` | Request forbidden; status stays `6`; no transition audit | PASS |
| 2 | Admin approves berkas too early | Admin | `admin.akreditasi-detail.approve-berkas` while status `6` | Error returned; status stays `6`; no assessments created | PASS |
| 3 | Duplicate asesor assignment | Admin | `admin.akreditasi-detail.approve-berkas` with same asesor twice | Validation error on `asesor2Id`; status stays `5`; no assessments created | PASS |
| 4 | Asesor 2 schedules visitasi | Asesor 2 | `asesor.akreditasi.schedule-visitasi` | Error returned; status stays `4`; visitasi date remains null | PASS |
| 5 | Confirm visitasi before start date | Ketua Kelompok | `asesor.akreditasi.confirm-visitasi-selesai` | Error returned; status stays `3`; confirmation timestamp remains null | PASS |
| 6 | Non-owner uploads kartu kendali | Other Pesantren | `pesantren.akreditasi.upload-kartu-kendali` | Error returned; DB field remains null; uploaded temp file deleted | PASS |
| 7 | Finalize scoring before package complete | Ketua Kelompok | `asesor.akreditasi.finalize-scoring` | Error returned; status stays `2` | PASS |
| 8 | Issue SK before Validasi Admin/NV completion | Admin | `admin.akreditasi-detail.approve` | Warning returned; status stays `2`; certificate file not stored | PASS |

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
