# Repo Hygiene Cleanup Plan

Status: moderately messy, recoverable. Core runtime is healthy, but repo hygiene needs a small cleanup pass after the Blade/Alpine migration.

## P0 — correctness / deploy safety

1. Deduplicate scheduled deadline jobs.
   - Keep `perbaikan:check-deadlines` scheduled once.
   - Unschedule duplicate aliases that call the same `RejectionService::processDeadlines()`.
   - Keep alias command classes for compatibility.

2. Fix locked IPM rejection update bug.
   - `PesantrenService::updateIpm()` must resolve `RejectionService` before checking unlocked IPM sections.
   - Add regression coverage for status-4 locked IPM updates.

3. Move route action closures into controllers.
   - Email verification notification.
   - Secure asesor document download.
   - Panduan redirect.
   - Leave route group closures alone.

## P1 — repo hygiene

1. Remove tracked local/session artifacts.
   - `.handoff.md`
   - `.sisyphus/**`
   - `.opencode/**`

2. Extend ignore rules.
   - `.handoff.md`
   - `.opencode/`
   - `.sisyphus/`

3. Delete placeholder Laravel tests.
   - `tests/Feature/ExampleTest.php`
   - `tests/Unit/ExampleTest.php`

## P2 — docs source of truth

1. Add `docs/README.md` as current docs index.
2. Keep root README pointed only at current docs.
3. Remove README link to ignored `.kiro/specs/`.
4. Fix stale doc path casing (`docs/DEPLOYMENT.md`).
5. Mark old audit/plan docs as historical in the docs index; archive/move later if needed.

## Intentionally skipped

- Deleting alias command classes: possible external cron compatibility risk.
- Splitting route files: aesthetic unless route churn continues.
- Repository/service refactor: broader behavior risk.
- Removing Metronic vendored assets: currently intentional and tested.
- Rewriting old docs: indexing/marking is enough for this batch.

## Verification

```bash
git diff --check
php artisan schedule:list
php artisan route:clear
php artisan route:cache
php artisan route:clear
php artisan test tests/Feature/PerbaikanCheckDeadlinesTest.php tests/Feature/AkreditasiWorkflow/PerbaikanDeadlineExpiryTest.php tests/Feature/Pesantren/IpmServiceTest.php tests/Feature/MetronicFrontendTest.php
php artisan test
npm run build
```

## Commit plan

1. `Document repo hygiene cleanup plan`
2. `Remove tracked local agent artifacts`
3. `Deduplicate perbaikan deadline schedule`
4. `Fix locked IPM rejection updates`
5. `Move route closures to controllers`
6. `Remove placeholder tests and index docs`
