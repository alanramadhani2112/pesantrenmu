# Hybrid E2E Testing Plan - 2026-07-07

## Goal

Menjadikan hybrid E2E sebagai landasan testing akreditasi ke depan: HTTP E2E untuk business transitions, browser smoke untuk kontrak UI kritikal.

## Strategy

- HTTP E2E menjadi source of truth untuk alur bisnis penuh.
- Browser smoke memvalidasi halaman/action kritikal bisa dirender oleh role yang benar.
- Tidak menjalankan destructive reset di local MySQL.
- Fixture HTTP memakai PHPUnit `RefreshDatabase`.
- Fixture browser memakai demo data lokal + update fixture minimal yang disetujui bila dibutuhkan.

## Canonical Flow Under Test

```txt
Pesantren isi Profil/IPM/SDM/EDPM
→ submit pengajuan
→ 6 Pengajuan
→ admin buka review
→ 5 Verifikasi Berkas
→ admin approve berkas + assign asesor
→ 4 Review Asesor
→ asesor jadwalkan visitasi
→ 3 Visitasi
→ asesor konfirmasi visitasi selesai
→ 2 Penilaian Pasca Visitasi
→ asesor finalisasi NA1/NA2/NK + laporan
→ pesantren upload kartu kendali
→ 1 Validasi Admin
→ admin save/finalize NV
→ admin terbitkan SK/sertifikat
→ 0 Selesai
→ pesantren melihat hasil akhir
```

## Test Layers

### 1. HTTP E2E

Purpose: prove status transitions, authorization, DB side effects, audit trail, scoring, and final result.

File target:

```txt
tests/Feature/E2E/HybridAccreditationFlowTest.php
```

Assertions:

- Pesantren can submit only its own accreditation.
- Admin opens review and assigns two distinct assessors.
- Ketua Kelompok schedules and confirms visitasi.
- Assigned assessors complete NA/NK and upload reports.
- Pesantren uploads kartu kendali at pasca visitasi.
- Admin saves/finalizes NV with reason when NV differs from NK.
- Admin issues SK and status becomes selesai.
- Pesantren final result page hides raw NA/NK/NV but shows final result/SK metadata.
- Audit logs exist for major status transitions.

### 2. Browser Smoke

Purpose: prove critical UI pages render for each role after full-flow changes.

Script target:

```txt
output/playwright/e2e-hybrid-browser-smoke.mjs
```

Pages:

- Super Admin: dashboard, accounts, role permission, admin akreditasi.
- Admin: dashboard, akreditasi list, akreditasi detail instrumen, banding.
- Asesor: dashboard, profile, akreditasi list, akreditasi detail instrumen.
- Pesantren: dashboard, profile, IPM, SDM, EDPM, akreditasi list, akreditasi detail/result.

Checks:

- Not redirected to `/login`.
- No visible 403/404/500 text.
- No horizontal overflow at desktop viewport.
- Admin instrumen detail renders NV controls when fixture is in Validasi Admin.
- Screenshots saved for manual audit.

## Commands

HTTP E2E:

```bash
php artisan test tests/Feature/E2E/HybridAccreditationFlowTest.php
```

Browser smoke:

```bash
node output/playwright/e2e-hybrid-browser-smoke.mjs
```

Full verification:

```bash
php artisan test
npm run build
```

## Report Output

Write execution report to:

```txt
docs/e2e-hybrid-testing-report-2026-07-07.md
```

Report must include:

- Scope.
- Commands run.
- Pass/fail summary.
- Key assertions.
- Browser screenshot/report paths.
- Known limitations.
- Follow-up items.

## Known Constraints

- Local MySQL schema may be stale; `BusinessFlowTestSeeder` can fail on missing `roles.parameter`.
- Do not run `migrate:fresh` on local MySQL during this audit unless explicitly authorized.
- Browser smoke is a rendering/role contract check, not full click-through workflow.
- File uploads are better covered by HTTP tests unless browser upload UX is the target.

## Acceptance Criteria

- Hybrid HTTP E2E test passes.
- Browser smoke completes with `issues: []` or documented non-blocking issues.
- Report markdown exists and references evidence.
- Existing full suite and build remain green if runtime code changes.
