# Pesantren Form Metronic Polish Design

## Goal

Polish four Pesantren form pages so they feel consistent with existing Metronic styling and local `x-ui` components:

- `/pesantren/profile`
- `/pesantren/ipm`
- `/pesantren/sdm`
- `/pesantren/edpm`

Scope is visual/frontend only. No controller, route, database, or workflow behavior changes.

## Current Context

The pages already use `layouts.app`, `x-ui.page`, `x-ui.section-card`, `x-ui.stat-card`, alerts, buttons, and mixed Metronic classes. Form and table base styling lives in `resources/css/metronic-overrides/45-form-modal.css`; other visual polish files live under `resources/css/metronic-overrides/`.

## Approach

Use a focused form-system polish rather than a redesign.

1. Keep existing Blade structure and data flow.
2. Add/adjust CSS classes for Pesantren form surfaces.
3. Prefer Metronic-native classes: `card`, `form-control-solid`, `form-select-solid`, `table-row-dashed`, badges, light backgrounds.
4. Reuse existing `x-ui` components. Do not add new dependencies.
5. Keep mobile behavior responsive with stacked cards/tables.

## Page Details

### Profile

- Improve section spacing and form density.
- Make text inputs, selects, textareas, and file uploads visually consistent.
- Keep locked state behavior unchanged.
- Improve upload document groups using card/grid treatment where current markup allows.

### IPM

- Make each criterion card read like a Metronic upload checklist item.
- Highlight uploaded vs missing states using badge and subtle border/background.
- Keep existing SweetAlert confirmation and file validation behavior.

### SDM

- Improve compact numeric table readability.
- Keep number input width compact but consistent.
- Improve responsive table wrapping and totals emphasis.

### EDPM

- Make left stepper feel like a sticky Metronic navigation card on desktop.
- Improve active/inactive step buttons.
- Improve table input/select density and evidence URL field spacing.
- Keep Alpine step/group behavior unchanged.

## Constraints

- No new dependency.
- No data model change.
- No route/controller change.
- Do not rewrite forms into a new wizard.
- Keep existing validation messages and session alerts.

## Verification

- Add/adjust a small feature or view assertion only if markup contract changes.
- Run targeted tests relevant to touched pages/components.
- Runtime verify by opening the four URLs as a Pesantren user and checking visual rendering, form controls, locked/active status, and responsive behavior if feasible.

## Acceptance Criteria

- Four pages render without Blade/PHP errors.
- Inputs/selects/file controls share consistent Metronic density and focus states.
- Cards, badges, section headers, and tables align visually with existing Metronic dashboard/admin pages.
- Existing save/confirm flows still work.
- Sidebar/header/layout unaffected.
