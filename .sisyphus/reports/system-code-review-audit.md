# System Code Review Audit

Tanggal: 2026-07-20
Scope: audit kode dan test health. Tidak ada perubahan source app.

## Ringkasan

Status umum: layak lanjut. Tidak ada temuan Critical dari bukti audit saat ini.

Prioritas utama:

1. Working tree sudah dirty sebelum audit. Pisahkan perubahan user dari pekerjaan audit sebelum cleanup berikutnya.
2. Ada command perbaikan deadline/reminder yang tampak duplikat atau legacy.
3. Beberapa file sudah terlalu besar: CSS override, service, Blade, dan test monolith.
4. Ada partial/component lama yang kemungkinan tidak terpakai.
5. Ada skipped tests lama yang sekarang jadi debt.

## Batasan Audit

- Main agent only.
- Audit/report only.
- Tidak install dependency.
- Tidak ubah schema/migration.
- Tidak hapus file.
- Tidak cleanup source app.

## Kondisi Git Saat Audit Dimulai

Working tree sudah memiliki perubahan non-audit:

- `app/Http/Controllers/Api/LayoutDataController.php`
- `resources/css/metronic-overrides/85-pesantren-polish.css`
- `resources/views/asesor/akreditasi-detail.blade.php`
- `resources/views/asesor/akreditasi-detail/tabs/instrumen.blade.php`
- `resources/views/asesor/akreditasi-detail/tabs/sdm.blade.php`
- `routes/web.php`

Catatan: file di atas bukan perubahan audit ini. Audit hanya menambah artifact `.sisyphus/*`.

## Evidence Ringkas

### Inventory

- Tracked files: 860
- `app`: 154
- `config`: 15
- `database`: 114
- `resources`: 174
- `routes`: 5
- `tests`: 180
- Controllers: 38
- Services: 22
- Models: 28
- Views: 151
- Routes: 150
- PHPUnit test files: 173
- Playwright spec files: 6
- Listed PHPUnit tests: 3180

### Validation

- `npm run build`: PASS
- `php artisan view:cache`: PASS
- `php artisan test tests/Feature/E2E`: PASS
- `php artisan test tests/Feature/AsesorAkreditasiMenuContextTest.php`: PASS, 6 tests / 25 assertions
- `npm run test:e2e:ci`: PASS, 17 Chromium tests
- `git diff --check`: PASS
- LSP diagnostics sample on `app`, `resources/views`, `tests`: no diagnostics in scanned files

Warning only:

- Browserslist/caniuse-lite data 7 months old.

## Findings

### Critical

None found from current evidence.

### High

#### H1 — Working tree dirty before audit

Impact: audit/cleanup commit can accidentally mix unrelated source changes.

Evidence:

- Existing modified files listed under “Kondisi Git Saat Audit Dimulai”.

Fix:

- Commit/stash user changes before cleanup waves.
- Keep audit artifact commit separate from source cleanup commit.

#### H2 — Probable duplicate/legacy console commands

Impact: duplicate scheduled-job semantics can confuse ops, tests, and incident debugging.

Candidates:

| File | Signature | Evidence | Confidence |
|---|---|---|---|
| `app/Console/Commands/CheckPerbaikanDeadlines.php` | `akreditasi:check-perbaikan-deadlines` | Not scheduled in `routes/console.php`; overlaps `perbaikan:check-deadlines`; only one test reference found | Probable |
| `app/Console/Commands/SendPerbaikanReminders.php` | `akreditasi:send-perbaikan-reminders` | Not scheduled; grep only self/log refs; also calls `RejectionService::processDeadlines()` | Probable |

Active scheduled commands in `routes/console.php`:

- `banding:check-deadlines`
- `perbaikan:check-deadlines`
- `reminders:asesor2`
- `akreditasi:check-deadlines`
- `trash:purge`

Fix:

- Verify deployment cron/docs first.
- If unused, remove legacy command and obsolete tests together.
- If still needed, rename/merge semantics and add explicit scheduler/test coverage.

#### H3 — Oversized files and service surfaces

Impact: high regression risk, hard review, hard ownership.

Largest files:

| File | Lines | Note |
|---|---:|---|
| `resources/css/metronic-overrides/85-pesantren-polish.css` | 1303 | Currently modified; split before further growth |
| `resources/css/metronic-overrides/60-visual-normalization.css` | 1265 | Large global-ish visual surface |
| `tests/Feature/MetronicFrontendTest.php` | 1261 | Test monolith |
| `app/Services/AkreditasiWorkflowService.php` | 1204 | Core service monolith |
| `resources/css/keenicons-lite.css` | 1121 | Likely generated/vendor-like; lower priority |
| `resources/views/dashboard/index.blade.php` | 788 | Role dashboard complexity |
| `resources/js/app.js` | 785 | Frontend bootstrap surface |

Largest services by method count:

- `AkreditasiWorkflowService.php`: 23
- `PesantrenService.php`: 23
- `BandingService.php`: 17
- `AsesorService.php`: 16
- `OnboardingService.php`: 16
- `AssessorScoringService.php`: 15

Fix:

- Extract by business capability, not generic helper.
- Preserve tests first.
- Use one cleanup wave per file family.

### Medium

#### M1 — Probable unused admin partial

File:

- `resources/views/admin/akreditasi/detail/tabs/instrumen/scroll-actions.blade.php`

Evidence:

- Grep for `scroll-actions|instrumen.scroll-actions` found no include of this partial.
- Similar markup exists inline in `resources/views/asesor/akreditasi-detail/tabs/instrumen.blade.php`.

Fix:

- Verify no dynamic include in runtime.
- Delete partial if still unused.
- Run `php artisan view:cache` and relevant admin detail tests.

#### M2 — Legacy Breeze-style components likely unused

Candidates:

- `resources/views/components/action-message.blade.php`
- `resources/views/components/auth-session-status.blade.php`
- `resources/views/components/danger-button.blade.php`
- `resources/views/components/dropdown-link.blade.php`
- `resources/views/components/input-error.blade.php`
- `resources/views/components/input-label.blade.php`

Evidence:

- Specific grep for `action-message|danger-button|dropdown-link|input-error|input-label|auth-session-status` in `resources/views` returned no matches.

Risk:

- Blade components can be referenced dynamically or from tests. Verify before deleting.

#### M3 — Blade contains too much inline PHP

Evidence:

- `@php`: 129 matches in 83 Blade files.
- Highest counts:
  - `resources/views/pesantren/akreditasi-detail.blade.php`: 7
  - `resources/views/pesantren/sdm.blade.php`: 6
  - `resources/views/asesor/profile.blade.php`: 4
  - `resources/views/asesor/akreditasi.blade.php`: 4
  - `resources/views/pesantren/profile.blade.php`: 4
  - `resources/views/dashboard/index.blade.php`: 4

Fix:

- Move repeated mapping/format logic to controller/view-model/component props.
- Do not extract one-off display glue unless it reduces lines or removes duplication.

#### M4 — Repeated raw ordering expression

Pattern:

- `orderByRaw('COALESCE(ipr, 0) ASC')`

Files:

- `app/Repositories/Eloquent/EdpmRepository.php`
- `app/Repositories/Eloquent/MasterEdpmRepository.php`
- `app/Services/AsesorService.php`
- `app/Services/PesantrenService.php`

Risk:

- Low SQL injection risk because expression is static.
- Medium maintainability risk because ordering semantics are duplicated.

Fix:

- If behavior must stay identical, add one local query scope/helper.
- Avoid abstraction unless it removes all duplicate uses.

#### M5 — Skipped legacy tests remain

Evidence:

- `tests/Feature/BusinessFlowLegacyCleanupTest.php:90`: `Asesor AkreditasiDetail migrated to plain Blade controller.`
- `tests/Feature/MetronicFrontendTest.php:575`: `Instrumen tab migrated to single-file Blade view — no nested partials remain.`
- `tests/Feature/PerformanceOptimizationTest.php:95`: `Detail pages migrated to Blade — no polling contract remains.`

Fix:

- Delete obsolete skipped contracts or replace with current Blade/controller contracts.
- Do not leave permanent skips for migrated behavior.

### Low

#### L1 — Browserslist data stale

Evidence:

- `npm run build` warning: caniuse-lite/Browserslist data 7 months old.

Fix:

- Update browserslist database when safe in frontend dependency maintenance window.

## Security Notes

- No critical auth gap found from scanned evidence.
- Route middleware uses `auth`, `verified`, role, and permission guards heavily.
- Duplicate route name scan: no duplicates.
- Route action class/method scan: no missing controller classes/methods.
- Missing route-name scan was attempted but failed due shell quoting. Do not treat as passed.
- Empty catch blocks: none found.
- App-level suppression patterns: none found from audit.
- Raw SQL usage mostly static aggregate/order expressions; no immediate injection finding.

## Dead Code Confidence Table

| Candidate | Confidence | Action |
|---|---|---|
| `app/Console/Commands/SendPerbaikanReminders.php` | Probable | Verify external cron/docs, then delete or merge |
| `app/Console/Commands/CheckPerbaikanDeadlines.php` | Probable | Compare with `PerbaikanCheckDeadlines.php`, delete/merge if duplicate |
| `resources/views/admin/akreditasi/detail/tabs/instrumen/scroll-actions.blade.php` | Probable | Verify no dynamic include, then delete |
| Legacy Breeze components listed in M2 | Suspected/Probable | Verify with grep/tests before delete |

Explicit non-dead examples:

- `resources/views/components/akreditasi/edpm-review.blade.php` is used.
- `resources/views/components/akreditasi/workflow-stepper.blade.php` is used.
- `resources/views/components/datatable.*` are used.

## Recommended Next Move

Do cleanup in waves:

1. Stabilize git: separate existing dirty source changes from audit artifacts.
2. Remove/merge duplicate console commands after verifying deployment cron.
3. Delete confirmed unused partial/components with view/cache/tests.
4. Replace permanent skipped tests with current contracts.
5. Split highest-risk large files only when touching them for feature work.
