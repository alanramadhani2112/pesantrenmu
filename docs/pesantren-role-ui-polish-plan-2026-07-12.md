# Pesantren Role UI Polish Plan

Date: 2026-07-12
Scope: all Pesantren-facing pages
Mode: planning only, no implementation

## Goals

- Make all Pesantren pages feel like one consistent product journey.
- Keep Metronic/SPM design system, not a new visual system.
- Prioritize clarity, next action, completion status, and mobile usability.
- Avoid backend/workflow changes unless required by missing UI data.

## Source Pages

| Page | Route | View |
| --- | --- | --- |
| Dashboard | `/dashboard` | `resources/views/dashboard/index.blade.php` |
| Profil Pesantren | `/pesantren/profile` | `resources/views/pesantren/profile.blade.php` |
| IPM Mutlak | `/pesantren/ipm` | `resources/views/pesantren/ipm.blade.php` |
| Data SDM | `/pesantren/sdm` | `resources/views/pesantren/sdm.blade.php` |
| EDPM/IPR | `/pesantren/edpm` | `resources/views/pesantren/edpm.blade.php` |
| Pengajuan Akreditasi | `/pesantren/akreditasi` | `resources/views/pesantren/akreditasi.blade.php` |
| Detail Akreditasi | `/pesantren/akreditasi/{uuid}` | `resources/views/pesantren/akreditasi-detail.blade.php` |
| Dokumen | `/documents/{doc?}` | `resources/views/documents/index.blade.php` |
| Panduan Pesantren | `/panduan-pesantren` | `resources/views/panduan/pesantren.blade.php` |

## Design Direction

Use refined operational dashboard style:

- Calm institutional tone: green SPM brand, warm neutral surfaces, low-noise borders.
- Strong hierarchy: one primary action per page, secondary actions grouped.
- Journey-first UX: show what is complete, what is blocked, and what to do next.
- Mobile-first tables/forms: horizontal overflow only where unavoidable.
- No decorative-only animation; use subtle state transitions and hover/focus affordance.

## Shared Standards

### Page Header

- Every Pesantren page should have title, concise subtitle, and one clear toolbar area.
- Locked/active state should use consistent badge placement.
- Primary CTA should be visible only when actionable.

### Cards

- Use `x-ui.card`, `x-ui.section-card`, `x-ui.stat-card`, `x-ui.metric-row`.
- Same card radius, border, body padding, and section rhythm.
- Avoid mixing raw Bootstrap card patterns with SPM components.

### Forms

- Keep labels visible.
- Use consistent section grouping: identity, documents, scoring/input, submit actions.
- Sticky or repeated submit actions only if long forms make save controls hard to reach.
- Disabled/locked state must be obvious and non-confusing.

### Tables

- Operational tables use `x-ui.table`.
- Detail/static tables use `x-ui.simple-table`.
- Per-page selector stays in footer, matching Metronic DataTables pattern.
- Empty states use `x-ui.empty-state`.

### Mobile

- Check at 375px width.
- Cards stack cleanly.
- Buttons become full-width only when space is tight.
- Long tables keep readable horizontal scroll.
- No layout overflow outside intended table scroll.

## Page-by-Page Plan

### 1. Dashboard

Current role: entry point, readiness tracker, status summary, charts, activity.

Polish tasks:

- Make hero show next best action: complete profile, upload documents, submit akreditasi, or monitor status.
- Convert quick actions into a compact journey launcher with completion hints.
- Make readiness tracker the primary dashboard module.
- Reduce duplicate status/stat cards.
- Improve empty chart states for Pesantren users with actionable copy.
- Make recent activity clearer on mobile.

Acceptance:

- User knows next action within 5 seconds.
- Readiness progress is visually dominant but not noisy.
- No duplicate meaning between readiness, status, and stat cards.

### 2. Profil Pesantren

Current role: large profile + documents form.

Polish tasks:

- Add clearer top summary: locked state, document count, service unit count.
- Improve long-form scanning with stronger section anchors.
- Standardize upload rows: current file, replace action, file constraints.
- Make document completion feel trackable, not just file inputs.
- Keep submit/draft actions easy to find after long scroll.

Acceptance:

- User can distinguish required identity fields, unit fields, and document uploads.
- Existing uploaded documents are easy to verify.
- Locked state prevents confusion.

### 3. IPM Mutlak

Current role: upload four mandatory IPM documents.

Polish tasks:

- Present IPM as four requirement cards with status.
- Make missing vs uploaded state visually consistent.
- Add helper copy for file type/size.
- Keep save action near the cards.

Acceptance:

- User immediately sees which IPM criteria are incomplete.
- Uploaded document links are easy to open.

### 4. Data SDM

Current role: numeric input matrix per unit.

Polish tasks:

- Improve table density and horizontal scroll affordance.
- Add category-level summary totals.
- Make row totals and column totals visually distinct.
- Ensure number inputs are usable on mobile.

Acceptance:

- Totals are easy to read.
- Wide matrix does not break layout.
- User understands values are per unit and gender.

### 5. EDPM/IPR

Current role: stepped assessment input with evidence links.

Polish tasks:

- Improve stepper readability and active state.
- Make EDPM vs IPR toggle clearer.
- Standardize per-butir table with compact but accessible controls.
- Add component progress hint if data is available or cheap to derive.
- Improve save/draft/navigation button layout on mobile.

Acceptance:

- User can move through components without losing context.
- Active component and group are obvious.
- Inputs remain usable inside table layout.

### 6. Pengajuan Akreditasi

Current role: submission list, filters, status/action menu.

Polish tasks:

- Align table footer controls with Metronic pattern already implemented.
- Improve focus tabs: Semua, Perbaikan, Kartu Kendali, Hasil.
- Make incomplete-data warning more actionable.
- Clarify row action priority: detail, delete/cancel, banding, catatan.
- Improve status/tahapan badge semantics.

Acceptance:

- User understands whether they can submit now.
- Table actions are predictable and not overcrowded.

### 7. Detail Akreditasi

Current role: stage tracking and tabbed detail.

Polish tasks:

- Make workflow stepper the main orientation element.
- Improve tab labels and active state.
- Keep Kartu Kendali upload visible only when relevant.
- Standardize profile/IPM/SDM/EDPM detail tables.
- Improve result/banding section hierarchy.

Acceptance:

- User knows current stage and what is expected from Pesantren.
- Detail tabs are readable and not overwhelming.

### 8. Dokumen

Current role: document library for Pesantren.

Polish tasks:

- Keep standardized `x-ui.table`.
- Improve category badge and empty state.
- Make file names truncate consistently.
- Ensure action button is clear: view/download.

Acceptance:

- User can find document quickly.
- Empty category explains that admin has not shared documents yet.

### 9. Panduan Pesantren

Current role: help/manual.

Polish tasks:

- Improve reading flow and section navigation.
- Add clearer “what this page helps with” introduction.
- Make instructions scannable with steps and callouts.
- Align screenshots/media cards if present.

Acceptance:

- User can use guide as a step-by-step helper without reading long paragraphs.

## Implementation Order

1. Audit all pages in browser as Pesantren user.
2. Capture screenshots desktop and mobile for before state.
3. Implement shared CSS/component-level refinements only if reused across pages.
4. Polish Dashboard first.
5. Polish form pages: Profile, IPM, SDM, EDPM.
6. Polish operational pages: Akreditasi list/detail, Documents.
7. Polish Panduan last.
8. Run verification and visual QA.

## Files Likely Touched

- `resources/views/dashboard/index.blade.php`
- `resources/views/pesantren/profile.blade.php`
- `resources/views/pesantren/ipm.blade.php`
- `resources/views/pesantren/sdm.blade.php`
- `resources/views/pesantren/edpm.blade.php`
- `resources/views/pesantren/akreditasi.blade.php`
- `resources/views/pesantren/akreditasi-detail.blade.php`
- `resources/views/documents/index.blade.php`
- `resources/views/panduan/pesantren.blade.php`
- `resources/css/metronic-overrides/*.css`

## Verification

Minimum checks:

- `php artisan view:cache`
- `php artisan view:clear`
- `php artisan test tests/Feature/MetronicFrontendTest.php --stop-on-failure`
- `npm run build`
- `git diff --check`

Browser QA:

- Desktop: `/dashboard`, all Pesantren sidebar pages.
- Mobile: 375px viewport, all Pesantren sidebar pages.
- States: active data, empty data, locked data, incomplete readiness.

## Non-Goals

- No backend workflow redesign.
- No scoring/formula changes.
- No route or permission changes.
- No new dependency unless existing components cannot solve the problem.
- No unrelated admin/asesor page polish unless shared component change requires it.

## Open Questions

- Which Pesantren test account should be used for browser QA?
- Should we capture before/after screenshots into `docs/`?
- Should polish be one commit or split per phase?
