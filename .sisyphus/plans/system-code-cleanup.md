# System Code Cleanup Plan

Goal: reduce dead code and maintainability risk without changing product behavior.

## Hard Rules

- Main agent only unless user explicitly changes rule.
- No broad rewrite.
- No schema changes.
- No new dependencies.
- One cleanup wave per concern.
- Each deletion needs evidence and targeted verification.
- Keep user’s pre-existing source changes separate from audit artifact commits.

## Wave 0 — Stabilize Git State

### Scope

Resolve pre-existing dirty files before cleanup:

- `app/Http/Controllers/Api/LayoutDataController.php`
- `resources/css/metronic-overrides/85-pesantren-polish.css`
- `resources/views/asesor/akreditasi-detail.blade.php`
- `resources/views/asesor/akreditasi-detail/tabs/instrumen.blade.php`
- `resources/views/asesor/akreditasi-detail/tabs/sdm.blade.php`
- `routes/web.php`

### Acceptance

- Audit artifacts can be committed separately.
- Source cleanup starts from known clean baseline or explicit user-approved diff.

### Checks

- `git status --short --branch`
- `git diff --check`

## Wave 1 — Console Command Deduplication

### Target

- `app/Console/Commands/CheckPerbaikanDeadlines.php`
- `app/Console/Commands/SendPerbaikanReminders.php`
- `app/Console/Commands/PerbaikanCheckDeadlines.php`
- `routes/console.php`
- Related tests under `tests/Feature/AkreditasiWorkflow/`

### Work

1. Verify external cron/deploy docs do not call legacy signatures.
2. Compare command behavior and output.
3. Keep one canonical command for perbaikan deadline processing.
4. Remove or redirect duplicate commands only with approval.
5. Update tests to cover canonical command.

### Acceptance

- Only intended command remains scheduled.
- No duplicate command semantics.
- Tests prove deadline processing still works.

### Checks

- `php artisan list | findstr /i "deadline perbaikan reminder"`
- `php artisan test tests/Feature/AkreditasiWorkflow/PerbaikanDeadlineExpiryTest.php`
- `php artisan test tests/Feature/E2E`

## Wave 2 — Confirmed Unused View Cleanup

### Target

- `resources/views/admin/akreditasi/detail/tabs/instrumen/scroll-actions.blade.php`
- Legacy Breeze-style components if verified unused:
  - `resources/views/components/action-message.blade.php`
  - `resources/views/components/auth-session-status.blade.php`
  - `resources/views/components/danger-button.blade.php`
  - `resources/views/components/dropdown-link.blade.php`
  - `resources/views/components/input-error.blade.php`
  - `resources/views/components/input-label.blade.php`

### Work

1. Re-run exact grep for each candidate.
2. Check dynamic include/component usage.
3. Delete one group at a time.
4. Run view cache and relevant frontend tests.

### Acceptance

- No deleted view/component is referenced.
- Blade cache passes.
- Relevant tests pass.

### Checks

- `php artisan view:clear; php artisan view:cache`
- `php artisan test tests/Feature/MetronicFrontendTest.php`
- `npm run build`

## Wave 3 — Replace Permanent Skipped Tests

### Target

- `tests/Feature/BusinessFlowLegacyCleanupTest.php`
- `tests/Feature/MetronicFrontendTest.php`
- `tests/Feature/PerformanceOptimizationTest.php`

### Work

1. For each `markTestSkipped`, decide delete vs replace.
2. If behavior migrated, write current behavior assertion.
3. Remove permanent skip.

### Acceptance

- No stale skipped contract remains for migrated behavior.
- Total skipped count decreases or each skip has current justification.

### Checks

- `php artisan test --list-tests`
- `php artisan test tests/Feature/BusinessFlowLegacyCleanupTest.php tests/Feature/MetronicFrontendTest.php tests/Feature/PerformanceOptimizationTest.php`

## Wave 4 — Large File Risk Reduction

### Target Order

1. `resources/css/metronic-overrides/85-pesantren-polish.css`
2. `tests/Feature/MetronicFrontendTest.php`
3. `app/Services/AkreditasiWorkflowService.php`
4. `resources/views/dashboard/index.blade.php`
5. `resources/js/app.js`

### Work

- Only split when touching file for real feature/bug work.
- Extract cohesive sections, not generic helper abstractions.
- Keep diffs reviewable.

### Acceptance

- Behavior unchanged.
- File size and cognitive surface decrease.
- Tests/build pass.

### Checks

- Smallest relevant PHP tests for touched domain.
- `npm run build` for CSS/JS/Blade changes.
- `php artisan view:cache` for Blade changes.

## Wave 5 — Blade Logic Reduction

### Target

- Blade files with repeated `@php`, starting from highest count and active churn.

### Work

- Move repeated mapping/formatting to controller data arrays or component props.
- Do not add presenters unless they remove meaningful duplication.

### Acceptance

- Blade has less inline decision logic.
- UI output remains same.

### Checks

- `php artisan view:cache`
- Targeted Feature tests.
- Browser smoke if page is critical.

## Wave 6 — Raw Query Duplication

### Target

- Repeated `orderByRaw('COALESCE(ipr, 0) ASC')` usage.

### Work

- Add one query scope/helper only if it removes all duplicate static raw order expressions.
- Keep SQL static; do not accept user input into raw strings.

### Acceptance

- Ordering behavior unchanged.
- Duplication removed.

### Checks

- Relevant repository/service unit or feature tests.
- `php artisan test tests/Feature/E2E`

## Commit Strategy

- One commit per wave.
- If a wave touches 3+ unrelated files, split further by concern.
- Suggested order:
  1. `docs: add system audit cleanup plan`
  2. `refactor: remove duplicate deadline commands`
  3. `refactor: remove unused blade components`
  4. `test: replace legacy skipped contracts`
