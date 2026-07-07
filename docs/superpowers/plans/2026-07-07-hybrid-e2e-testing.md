# Hybrid E2E Testing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add repeatable hybrid E2E coverage for the full accreditation flow and document the run result.

**Architecture:** Use PHPUnit HTTP tests for the canonical business workflow because those are deterministic and isolated with `RefreshDatabase`. Use Chrome/CDP browser smoke only for critical UI rendering and role/session contracts, avoiding destructive local MySQL resets.

**Tech Stack:** Laravel 12, PHPUnit 11, Blade, Chrome CDP via Node.js stdlib/WebSocket, local `spm_fix.test`.

---

## Files

- Create: `tests/Feature/E2E/HybridAccreditationFlowTest.php`
  - Full HTTP E2E happy path across Pesantren/Admin/Asesor actors.
- Create: `output/playwright/e2e-hybrid-browser-smoke.mjs`
  - Browser smoke for critical role pages with screenshots/report JSON.
- Create: `docs/e2e-hybrid-testing-report-2026-07-07.md`
  - Markdown report with commands, pass/fail, evidence, limitations.
- Keep: `docs/e2e-hybrid-testing-plan-2026-07-07.md`
  - Strategy doc already written and approved.

---

### Task 1: HTTP E2E test

**Files:**
- Create: `tests/Feature/E2E/HybridAccreditationFlowTest.php`

- [ ] **Step 1: Create test file**

Use `BusinessFlowTestHelpers` and real HTTP routes where controller contracts matter. Directly seed scoring rows after visitasi to avoid duplicating 62 UI submissions.

Test assertions:

- Pesantren submits pengajuan via `route('pesantren.akreditasi.create')`.
- Admin opens review, approves berkas, assigns two assessors.
- Asesor 1 schedules and confirms visitasi.
- Asesor package is completed and finalized.
- Admin saves a draft NV, finalizes all NV, then issues SK.
- Pesantren can load final result page and raw `NV`/`NK` labels are not exposed.
- Status reaches `0`, SK fields exist, audit logs exist.

- [ ] **Step 2: Run focused HTTP E2E**

```bash
php artisan test tests/Feature/E2E/HybridAccreditationFlowTest.php
```

Expected: PASS.

---

### Task 2: Browser smoke script

**Files:**
- Create: `output/playwright/e2e-hybrid-browser-smoke.mjs`

- [ ] **Step 1: Create browser script**

Use the existing CDP style from `output/playwright/task4-role-smoke.mjs` with real login, not cookie injection.

Routes:

- Super Admin: `/dashboard`, `/accounts`, `/admin/master-role-permission`, `/admin/akreditasi`
- Admin: `/dashboard`, `/admin/akreditasi`, `/admin/akreditasi/{uuid}?tab=instrumen`, `/admin/banding`
- Asesor: `/dashboard`, `/asesor/profile`, `/asesor/akreditasi`, `/asesor/akreditasi/{uuid}?tab=instrumen`
- Pesantren: `/dashboard`, `/pesantren/profile`, `/pesantren/ipm`, `/pesantren/sdm`, `/pesantren/edpm`, `/pesantren/akreditasi`, `/pesantren/akreditasi/{uuid}`

Checks per page:

- `location.pathname` is not `/login`.
- body text does not contain visible `403`, `404`, `500`, `Forbidden`, `Not Found`, `Server Error`.
- no horizontal overflow.
- screenshot saved.

- [ ] **Step 2: Run browser smoke**

```bash
node output/playwright/e2e-hybrid-browser-smoke.mjs
```

Expected: JSON summary with `issues: []`.

---

### Task 3: Verification + report

**Files:**
- Create: `docs/e2e-hybrid-testing-report-2026-07-07.md`

- [ ] **Step 1: Run verification**

```bash
php artisan test tests/Feature/E2E/HybridAccreditationFlowTest.php
node output/playwright/e2e-hybrid-browser-smoke.mjs
php artisan test
npm run build
```

Expected:

- E2E test passes.
- Browser smoke has no issues or documents non-blocking fixture limits.
- Full test suite passes.
- Build passes.

- [ ] **Step 2: Write report**

Report includes:

- Scope.
- Commands run.
- Results.
- Key E2E assertions.
- Browser report/screenshot paths.
- Known limitations.
- Follow-up items.

- [ ] **Step 3: Check whitespace**

```bash
git diff --check
```

Expected: exit 0, no whitespace errors.

---

## Self-review

- Spec coverage: HTTP flow, browser smoke, and markdown report all mapped to tasks.
- Placeholder scan: no TBD/TODO placeholders.
- Scope: single implementation batch; no destructive DB reset.
