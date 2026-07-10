# Table Standardization — Comprehensive Plan

**Date:** 2026-07-10
**Scope:** 33 tables across all roles (admin, asesor, pesantren)
**Priority:** High — visual inconsistency affects every page

---

## 1. Problem Statement

Semua table di aplikasi **tidak seragam secara visual dan struktural**. Meskipun sudah ada component library (`x-ui.table`, `x-datatable.layout`, `x-ui.simple-table`) dan 495 baris custom CSS (`20-table-system.css`), tiap halaman menemukan caranya sendiri — menghasilkan markup invalid, layout aneh, dan pengalaman yang tidak konsisten.

### Key Findings

| Category | Count | Details |
|----------|-------|---------|
| Total tables | 33 | 13 `x-ui.table`/`x-datatable.layout`, 12 `x-ui.simple-table`, 8 direct `<table>` |
| DataTables JS (`.DataTable()`) | 0 | Tidak ada sama sekali meskipun folder dinamai `x-datatable.*` |
| Metronic `data-kt-*` attributes | 0 | Bukan Metronic pattern asli |
| Nested `<thead>` (invalid HTML) | 6 | akreditasi, banding, master-dokumen, kategori, roles, role-permission |
| Missing `table-responsive` wrapper | 3 | `pesantren/akreditasi-detail.blade.php` line 154, 188, 220 |
| Double pagination | 1 | pesantren index |
| Inconsistent sort implementation | 3 cara | manual `<a>`, `<x-datatable.th>`, raw `<th>` |
| Inconsistent search input | 2 cara | `<x-datatable.search>` vs raw `<input>` |
| Inconsistent per-page selector | 3 cara | `<x-ui.table-per-page>` vs raw `<select>` vs missing |

---

## 2. Component Architecture (Current State)

### 2.1 Primary Table Component: `x-ui.table`

**File:** `resources/views/components/ui/table.blade.php`

Props: `title`, `subtitle`, `records` (paginator), `showPerPage`, `perPagePosition`, `perPageVariant`, `tableClass`

Renders:
```
card (spm-table-shell)
├── card-header (spm-table-header)
│   ├── heading (title + subtitle)
│   ├── toolbar slot
│   └── filters slot (with optional per-page)
├── card-body
│   ├── table-responsive
│   │   └── <table> (spm-datatable spm-table spm-table--list)
│   │       ├── <thead>
│   │       │   └── <tr class="spm-table-head">
│   │       │       └── {{ $thead }}  ← SLOT CONTENT GOES HERE
│   │       └── <tbody>
│   │           └── {{ $tbody }}
│   └── footer (pagination + result meta) if $records provided
```

**Critical:** Component already renders `<thead><tr>`. Slot content should be `<th>` elements only, NOT `<thead><tr>`.

### 2.2 Datatable Layout Alias: `x-datatable.layout`

**File:** `resources/views/components/datatable/layout.blade.php`

Thin wrapper around `x-ui.table` with `data-ui-table-adapter="datatable"`. Different defaults: `perPagePosition: 'footer'`, `perPageVariant: 'compact'`.

### 2.3 Simple Table: `x-ui.simple-table`

**File:** `resources/views/components/ui/simple-table.blade.php`

Minimal wrapper: `table-responsive` + `<table>` + `$slot`. No header/footer/toolbar. Caller provides full `<thead>` and `<tbody>`.

### 2.4 Supporting Components

| Component | File | Purpose |
|-----------|------|---------|
| `x-ui.table-th` | `components/ui/table-th.blade.php` | `<th>` with optional sort link |
| `x-datatable.th` | `components/datatable/th.blade.php` | Alias for `x-ui.table-th` |
| `x-datatable.search` | `components/datatable/search.blade.php` | Alias for `x-ui.table-search` |
| `x-ui.table-search` | `components/ui/table-search.blade.php` | Search input with icon |
| `x-ui.table-per-page` | `components/ui/table-per-page.blade.php` | Per-page selector (labeled/compact) |
| `x-ui.table-checkbox` | `components/ui/table-checkbox.blade.php` | Checkbox with Alpine binding |

### 2.5 CSS System

**File:** `resources/css/metronic-overrides/20-table-system.css` (495 lines)

Covers: shell, header, controls, filters, thead/tbody styling, footer/pagination, per-page, checkbox, action menu, alerts, search, tabs, pagination.

---

## 3. Bug Catalog (Per Page)

### 3.1 Structural Bugs (Invalid HTML)

#### BUG-001: Nested `<thead><tr>` in thead slot
**Affected pages (6):**
- `admin/akreditasi/index.blade.php:157` — `<thead><tr>` inside slot
- `admin/banding/index.blade.php:36` — `<tr>` inside slot (no `<thead>`, but still nested `<tr>`)
- `admin/master-dokumen/index.blade.php:29` — `<tr>` inside slot
- `admin/master-kategori-dokumen/index.blade.php:29` — `<tr>` inside slot
- `admin/roles/index.blade.php:28` — `<tr>` inside slot
- `admin/role-permission/index.blade.php:39` — `<tr>` inside slot

**Cause:** Component renders `<thead><tr class="spm-table-head">{{ $thead }}</tr></thead>`. Pages put their own `<tr>` inside the slot, resulting in `<tr>` inside `<tr>`.

**Fix:** Remove `<tr>` wrapper from slot content. Pass `<th>` elements directly.

```blade
<!-- BEFORE (broken) -->
<x-slot name="thead">
    <tr>
        <x-ui.table-th>Name</x-ui.table-th>
        <x-ui.table-th>Status</x-ui.table-th>
    </tr>
</x-slot>

<!-- AFTER (correct) -->
<x-slot name="thead">
    <x-ui.table-th>Name</x-ui.table-th>
    <x-ui.table-th>Status</x-ui.table-th>
</x-slot>
```

#### BUG-002: Missing `table-responsive` on direct tables
**Affected pages (3):**
- `pesantren/akreditasi-detail.blade.php:154`
- `pesantren/akreditasi-detail.blade.php:188`
- `pesantren/akreditasi-detail.blade.php:220`

**Fix:** Wrap in `<div class="table-responsive">` or convert to `x-ui.simple-table`.

### 3.2 Functional Bugs

#### BUG-003: Double pagination on Pesantren index
**File:** `admin/pesantren/index.blade.php:128-131`

```blade
<x-datatable.layout :records="$pesantrens">  <!-- auto pagination -->
    ...
</x-datatable.layout>
{{ $pesantrens->appends(...)->links() }}      <!-- manual pagination lagi -->
```

**Fix:** Remove manual `{{ $pesantrens->links() }}` block.

#### BUG-004: Missing per-page selector
**File:** `admin/roles/index.blade.php`

No `x-ui.table-per-page` in filters. Users cannot change page size.

**Fix:** Add `<x-ui.table-per-page>` to filter form.

#### BUG-005: Raw per-page selector (bypasses component)
**Files:**
- `admin/pesantren/index.blade.php:42` — raw `<select style="width:80px">`
- `admin/failed-notifications/index.blade.php` — raw `<select>`

**Fix:** Replace with `<x-ui.table-per-page>`.

#### BUG-006: Raw search input (bypasses component)
**Files:**
- `admin/accounts/index.blade.php:34` — raw `<input class="form-control form-control-sm">`
- `documents/index.blade.php:32` — raw `<input class="form-control form-control-sm">`

**Fix:** Replace with `<x-datatable.search>`.

### 3.3 Consistency Issues

#### ISSUE-001: Mixed component usage (`x-datatable.layout` vs `x-ui.table`)

| Page | Component |
|------|-----------|
| admin/akreditasi | `x-ui.table` |
| admin/asesor | `x-datatable.layout` |
| admin/banding | `x-ui.table` |
| admin/master-dokumen | `x-ui.table` |
| admin/kategori-dokumen | `x-ui.table` |
| admin/pesantren | `x-datatable.layout` |
| admin/roles | `x-ui.table` |
| admin/role-permission | `x-ui.table` |
| admin/trash | `x-datatable.layout` |
| admin/accounts | `x-datatable.layout` |
| admin/failed-notifications | `x-datatable.layout` |
| documents/index | `x-datatable.layout` |
| asesor/akreditasi | `x-datatable.layout` |

**Decision:** Standardize to `x-ui.table` everywhere (simpler, direct). `x-datatable.layout` adds no value — it's a wrapper that just changes defaults.

#### ISSUE-002: Three sort implementation patterns

**Pattern A — Manual `<a>` links** (akreditasi, master-dokumen, kategori, roles):
```blade
<x-ui.table-th>
    <a href="{{ route('...', ['sortField' => 'name', ...]) }}" class="text-dark text-hover-primary">
        Name <i class="ki-outline ki-arrow-{{ $sortAsc ? 'up' : 'down' }}"></i>
    </a>
</x-ui.table-th>
```

**Pattern B — `<x-ui.table-th :field>` component** (asesor, pesantren):
```blade
<x-ui.table-th field="name" :sortField="$sortField" :sortAsc="$sortAsc">
    Name
</x-ui.table-th>
```

**Pattern C — Raw `<th>` with manual link** (accounts):
```blade
<th class="min-w-200px cursor-pointer">
    <a href="..." class="text-decoration-none text-inherit">
        Name <x-ui.icon name="arrow-up" />
    </a>
</th>
```

**Decision:** Standardize to Pattern B (`<x-ui.table-th field="...">`). It already handles sort URL generation, icon state, and styling internally.

#### ISSUE-003: Checkbox implementation split

**Raw checkbox** (akreditasi):
```blade
<input type="checkbox" class="form-check-input" x-on:change="selectAllToggle($event)">
```

**Component checkbox** (asesor, pesantren):
```blade
<x-ui.table-checkbox model="selectAll" label="Pilih semua" />
```

**Decision:** Standardize to `<x-ui.table-checkbox>` with Alpine `x-model`.

#### ISSUE-004: Role-permission table overrides all classes
**File:** `admin/role-permission/index.blade.php:23`
```blade
<x-ui.table table-class="table-bordered table-sm text-nowrap">
```

This replaces the entire default class string (`table table-striped table-row-bordered align-middle gy-5 gs-7 mb-0 spm-datatable spm-table spm-table--list`), losing all spm-* styling.

**Fix:** Append instead of replace: `table-class="table-bordered table-sm text-nowrap spm-datatable spm-table spm-table--list"`, or fix the component to merge rather than replace.

---

## 4. Standardization Rules

### 4.1 Operational Tables (List/Index Pages)

Every operational table MUST follow this pattern:

```blade
<x-ui.table
    title="Daftar ..."
    subtitle="..."
    :records="$paginator"
>
    {{-- Toolbar: buttons/badges (optional) --}}
    <x-slot name="toolbar">
        <x-ui.button variant="primary" size="sm" icon="plus">Tambah</x-ui.button>
    </x-slot>

    {{-- Filters: search + selects + per-page --}}
    <x-slot name="filters">
        <form method="GET" action="{{ route('...') }}" id="xxx-filter-form">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <x-datatable.search name="search" :value="$search" form="xxx-filter-form" />

                <x-ui.select name="statusFilter" size="sm" class="w-auto min-w-180px" onchange="this.form.submit()">
                    <option value="">Semua</option>
                </x-ui.select>

                <x-ui.table-per-page name="perPage" :value="$perPage" :options="[10, 25, 50]" form="xxx-filter-form" />

                {{-- Sort hidden inputs --}}
                <input type="hidden" name="sortField" value="{{ $sortField }}">
                <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">
            </div>
        </form>
    </x-slot>

    {{-- Header: <th> only, NO <tr> wrapper --}}
    <x-slot name="thead">
        <x-ui.table-th field="name" :sortField="$sortField" :sortAsc="$sortAsc">
            Name
        </x-ui.table-th>
        <x-ui.table-th align="center">Status</x-ui.table-th>
        <x-ui.table-th align="end">Aksi</x-ui.table-th>
    </x-slot>

    {{-- Body --}}
    <x-slot name="tbody">
        @forelse($items as $item)
            <tr>
                <td>...</td>
            </tr>
        @empty
            <tr>
                <td colspan="N">
                    <x-ui.empty-state title="Data tidak ditemukan" class="py-15" />
                </td>
            </tr>
        @endforelse
    </x-slot>
</x-ui.table>
```

**Rules:**
1. **Component:** Always `x-ui.table` (not `x-datatable.layout`)
2. **Search:** Always `<x-datatable.search>` (never raw `<input>`)
3. **Per-page:** Always `<x-ui.table-per-page>` (never raw `<select>`)
4. **Sort:** Always `<x-ui.table-th field="...">` (never manual `<a>` links)
5. **Checkbox:** Always `<x-ui.table-checkbox>` (never raw `<input>`)
6. **Thead slot:** `<x-ui.table-th>` only, NO `<tr>` wrapper
7. **Pagination:** Automatic via `:records` prop — never manual `{{ $x->links() }}`
8. **Empty state:** Always `<x-ui.empty-state>` in `<td colspan>`
9. **Filter form:** Always `<form method="GET">` with `id` attribute

### 4.2 Simple/Detail Tables (Read-only, Static)

Detail page tables (tab partials, score tables, profil data) use `x-ui.simple-table`:

```blade
<x-ui.simple-table dense>
    <thead>
        <tr class="text-start text-gray-500 fw-semibold fs-7 text-uppercase gs-0">
            <th>Key</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>...</td><td>...</td></tr>
    </tbody>
</x-ui.simple-table>
```

**Rules:**
1. Caller provides full `<thead>` and `<tbody>` (simple-table passes `$slot` directly)
2. Always wrap in component (never direct `<table>` without `table-responsive`)
3. Use `dense` variant for key-value data

### 4.3 Special Cases

**Role-Permission Matrix:** Unique pattern (form-wrapped checkbox matrix). Keep custom `table-class` but ensure `spm-table` base classes are preserved.

**EDPM Tables:** Keep `x-ui.simple-table` with custom classes for sticky columns.

---

## 5. File Change List

### 5.1 Pages to Fix (14 operational tables)

| # | File | Component Change | Bugs Fixed |
|---|------|-----------------|------------|
| 1 | `admin/akreditasi/index.blade.php` | Keep `x-ui.table` | BUG-001 (nested thead), ISSUE-002 (sort), ISSUE-003 (checkbox) |
| 2 | `admin/asesor/index.blade.php` | `x-datatable.layout` → `x-ui.table` | ISSUE-001 (component) |
| 3 | `admin/banding/index.blade.php` | Keep `x-ui.table` | BUG-001 (nested tr) |
| 4 | `admin/master-dokumen/index.blade.php` | Keep `x-ui.table` | BUG-001 (nested tr) |
| 5 | `admin/master-kategori-dokumen/index.blade.php` | Keep `x-ui.table` | BUG-001 (nested tr), ISSUE-002 (sort) |
| 6 | `admin/pesantren/index.blade.php` | `x-datatable.layout` → `x-ui.table` | BUG-003 (double pagination), BUG-005 (raw per-page) |
| 7 | `admin/roles/index.blade.php` | Keep `x-ui.table` | BUG-001 (nested tr), BUG-004 (missing per-page), ISSUE-002 (sort) |
| 8 | `admin/role-permission/index.blade.php` | Keep `x-ui.table` | BUG-001 (nested tr), ISSUE-004 (class override) |
| 9 | `admin/trash/index.blade.php` | `x-datatable.layout` → `x-ui.table` | ISSUE-001 (component) |
| 10 | `admin/accounts/index.blade.php` | `x-datatable.layout` → `x-ui.table` | BUG-006 (raw search), ISSUE-002 (raw th sort) |
| 11 | `admin/failed-notifications/index.blade.php` | `x-datatable.layout` → `x-ui.table` | BUG-005 (raw per-page) |
| 12 | `documents/index.blade.php` | `x-datatable.layout` → `x-ui.table` | BUG-006 (raw search) |
| 13 | `asesor/akreditasi.blade.php` | `x-datatable.layout` → `x-ui.table` | ISSUE-001 (component) |
| 14 | `pesantren/akreditasi.blade.php` | `x-ui.simple-table` → `x-ui.table` | Full restructure needed |

### 5.2 Detail Tables to Fix (3 missing table-responsive)

| # | File | Line | Fix |
|---|------|------|-----|
| 1 | `pesantren/akreditasi-detail.blade.php` | 154 | Wrap in `x-ui.simple-table` |
| 2 | `pesantren/akreditasi-detail.blade.php` | 188 | Wrap in `x-ui.simple-table` |
| 3 | `pesantren/akreditasi-detail.blade.php` | 220 | Wrap in `x-ui.simple-table` |

### 5.3 Component Fixes (1)

| # | File | Fix |
|---|------|-----|
| 1 | `components/ui/table.blade.php` | Consider removing `<tr>` wrapper from `<thead>` and let callers provide their own `<tr>`, OR document clearly that slot = `<th>` only |

### 5.4 Files NOT Changed

- `admin/akreditasi/detail/tabs/audit-trail.blade.php` — `x-ui.simple-table` (correct)
- `admin/akreditasi/detail/tabs/instrumen/score-table.blade.php` — `x-ui.simple-table` (correct)
- `admin/akreditasi/detail/tabs/profil.blade.php` — `x-ui.simple-table` (correct)
- `admin/akreditasi/detail/tabs/sdm.blade.php` — `x-ui.simple-table` (correct)
- `admin/master-edpm/index.blade.php` — `x-ui.simple-table` x2 (correct, specialized)
- `admin/pesantren/detail.blade.php` — `x-ui.simple-table` (correct)
- `asesor/akreditasi-detail/tabs/*.blade.php` — `x-ui.simple-table` / direct tables (correct)
- `pesantren/sdm.blade.php` — `x-ui.simple-table` (correct, form table)
- `pesantren/edpm.blade.php` — direct tables (form tables, keep)
- `components/akreditasi/edpm-review.blade.php` — `x-ui.simple-table` (correct)
- `dashboard/index.blade.php` — `x-ui.simple-table` (correct)

---

## 6. Component Decision: thead Slot API

### Current Problem

`x-ui.table` renders:
```html
<thead>
    <tr class="text-start text-gray-500 fw-semibold gs-0 spm-table-head">
        {{ $thead }}
    </tr>
</thead>
```

Callers inconsistently put `<tr>` or `<thead><tr>` inside the slot.

### Option A: Keep current API (slot = `<th>` only)
- Pro: Component controls `<tr>` styling centrally
- Con: Non-obvious — callers keep making mistake
- Fix: Add comment/documentation in component

### Option B: Remove `<tr>` from component (slot provides own `<tr>`)
```html
<thead>
    {{ $thead }}
</thead>
```
- Pro: More intuitive — callers provide `<tr><th>...</th></tr>`
- Con: Loses centralized `<tr>` styling, every caller must add classes

### Recommendation: Option A + guard

Keep current API. Add runtime guard in component:

```blade
{{-- Detect if caller wrapped in <tr> and strip it --}}
@php
    $theadContent = (string) $thead;
    // If slot starts with <tr, the caller added their own row — log warning in dev
@endphp
```

And add a `<thead>` slot comment:
```blade
{{-- SLOT: Pass <th> elements only. Do NOT wrap in <tr>. Component renders <tr> automatically. --}}
```

---

## 7. Execution Plan

### Phase 1: Component Foundation (Day 1)

**Estimated: 2-3 hours**

1. [ ] Update `x-ui.table` component:
   - Add slot usage comment/documentation
   - Consider adding `<tr>` detection guard
   - Verify `tableClass` prop merging (not replacing) for role-permission fix

2. [ ] Update `x-datatable.layout`:
   - Add deprecation comment pointing to `x-ui.table`
   - Or: make it identical to `x-ui.table` (same defaults)

3. [ ] Verify `x-ui.table-th` sort component works with all existing sort parameter names:
   - Some pages use `sortField`/`sortAsc`
   - Some use `sort`/`direction`
   - Standardize to `sortField`/`sortAsc`

### Phase 2: Admin List Pages — Slice 1 (Day 1-2)

**Estimated: 3-4 hours**

Fix pages that use `<x-ui.table>` but have nested `<tr>`:

1. [ ] `admin/master-dokumen/index.blade.php`
   - Remove `<tr>` from thead slot
   - Convert manual sort `<a>` to `<x-ui.table-th field="...">`

2. [ ] `admin/master-kategori-dokumen/index.blade.php`
   - Remove `<tr>` from thead slot
   - Convert manual sort `<a>` to `<x-ui.table-th field="...">`

3. [ ] `admin/roles/index.blade.php`
   - Remove `<tr>` from thead slot
   - Convert manual sort `<a>` to `<x-ui.table-th field="...">`
   - Add `<x-ui.table-per-page>` to filters

4. [ ] `admin/banding/index.blade.php`
   - Remove `<tr>` from thead slot

5. [ ] `admin/role-permission/index.blade.php`
   - Remove `<tr>` from thead slot
   - Fix `table-class` to preserve base classes

### Phase 3: Admin List Pages — Slice 2 (Day 2)

**Estimated: 3-4 hours**

Convert `x-datatable.layout` → `x-ui.table` and fix remaining bugs:

6. [ ] `admin/akreditasi/index.blade.php`
   - Remove `<thead><tr>` from thead slot
   - Convert manual sort `<a>` to `<x-ui.table-th field="...">`
   - Convert raw checkbox to `<x-ui.table-checkbox>`

7. [ ] `admin/asesor/index.blade.php`
   - Change `x-datatable.layout` → `x-ui.table`

8. [ ] `admin/pesantren/index.blade.php`
   - Change `x-datatable.layout` → `x-ui.table`
   - Remove manual `{{ $pesantrens->links() }}` (double pagination)
   - Replace raw `<select>` per-page with `<x-ui.table-per-page>`

9. [ ] `admin/trash/index.blade.php`
   - Change `x-datatable.layout` → `x-ui.table`

10. [ ] `admin/accounts/index.blade.php`
    - Change `x-datatable.layout` → `x-ui.table`
    - Replace raw search input with `<x-datatable.search>`
    - Convert raw `<th>` sort links to `<x-ui.table-th field="...">`

11. [ ] `admin/failed-notifications/index.blade.php`
    - Change `x-datatable.layout` → `x-ui.table`
    - Replace raw `<select>` with `<x-ui.table-per-page>`

### Phase 4: Non-Admin Pages (Day 2-3)

**Estimated: 2-3 hours**

12. [ ] `documents/index.blade.php`
    - Change `x-datatable.layout` → `x-ui.table`
    - Replace raw search input with `<x-datatable.search>`

13. [ ] `asesor/akreditasi.blade.php`
    - Change `x-datatable.layout` → `x-ui.table`

14. [ ] `pesantren/akreditasi.blade.php`
    - Evaluate: can this be converted to `x-ui.table`? (currently `x-ui.simple-table`)
    - If operational (has search/pagination): convert to `x-ui.table`
    - If static: keep `x-ui.simple-table` but ensure proper thead classes

15. [ ] `pesantren/akreditasi-detail.blade.php`
    - Lines 154, 188, 220: wrap direct tables in `x-ui.simple-table`

### Phase 5: CSS Cleanup (Day 3)

**Estimated: 1-2 hours**

16. [ ] Review `20-table-system.css`:
    - Remove any dead selectors no longer used
    - Ensure all `spm-table-shell--*` variant classes are still needed
    - Verify responsive breakpoints work across all pages

17. [ ] Check other CSS files for table overrides:
    - `30-detail-components.css` — `spm-wide-table`, `spm-edpm-review-table`
    - `35-admin-modules.css` — sticky action column
    - `60-visual-normalization.css` — document table columns
    - `80-production-polish.css` — document table + edpm overrides

### Phase 6: Verification (Day 3)

**Estimated: 1-2 hours**

18. [ ] `npm run build` — verify CSS compiles
19. [ ] `php artisan test` — run test suite
20. [ ] Smoke test every affected route:
    - Visual check: thead styling consistent
    - Functional check: search, sort, filter, per-page, pagination work
    - Responsive check: mobile viewport, table scrolls horizontally
    - Empty state: test with `?search=zzzznonexistent`
    - Action menu: dropdown opens/closes correctly

---

## 8. Naming Convention Cleanup

### Current: Confusing Namespace Split

```
x-datatable.*  → resources/views/components/datatable/
x-ui.table-*   → resources/views/components/ui/
```

Both namespaces exist, some are aliases. Confusing.

### Proposed: Keep Both, Document Clearly

`x-datatable.*` are thin aliases that forward to `x-ui.*`. Keep for backward compat.

| Alias | Forwards To |
|-------|-------------|
| `x-datatable.layout` | `x-ui.table` + `data-ui-table-adapter="datatable"` |
| `x-datatable.search` | `x-ui.table-search` |
| `x-datatable.th` | `x-ui.table-th` |
| `x-datatable.per-page` | `x-ui.table-per-page` |

Long term: migrate all usage to `x-ui.*` and deprecate `x-datatable.*`.

---

## 9. Definition of Done

Per halaman:
- [ ] Pakai `x-ui.table` (bukan `x-datatable.layout` atau `x-ui.simple-table` untuk operational tables)
- [ ] Search pakai `x-datatable.search` (bukan raw `<input>`)
- [ ] Filter pakai `x-ui.select` (bukan raw `<select>`)
- [ ] Per-page pakai `x-ui.table-per-page` (bukan raw `<select>`)
- [ ] Sort pakai `x-ui.table-th field="..."` (bukan manual `<a>`)
- [ ] Checkbox pakai `x-ui.table-checkbox` (bukan raw `<input>`)
- [ ] Thead slot = `<th>` only (no `<tr>` wrapper)
- [ ] Pagination otomatis via `:records` (no manual `->links()`)
- [ ] Empty state pakai `x-ui.empty-state`
- [ ] Visual consistency check: all tables look identical in header styling, padding, row height

---

## 10. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Sort parameter naming conflict | Medium | Low | `x-ui.table-th` already handles `sortField`/`sortAsc`; standardize all controllers to same param names |
| `tableClass` prop replacing instead of merging | Low | Medium | Fix component to merge classes, not replace |
| Alpine.js model binding on checkbox breaks | Low | Medium | Test bulk select on akreditasi page after conversion |
| Per-page `form` attribute not supported in some browsers | Low | Low | Component already has fallback via JS `URL.searchParams` |
| Pagination breaks on pages with custom query params | Medium | High | Test each page's `->appends()` chain after conversion |

---

## 11. Success Metrics

After completion:
1. **Zero** nested `<thead>`/`<tr>` in any table slot
2. **Zero** raw `<input>` for search (all use `<x-datatable.search>`)
3. **Zero** raw `<select>` for per-page (all use `<x-ui.table-per-page>`)
4. **Zero** manual `->links()` pagination calls
5. **Zero** direct `<table>` without `table-responsive` wrapper
6. **One** sort pattern across all pages
7. **One** component (`x-ui.table`) for all operational tables
8. **Consistent** visual appearance across all 14 operational tables

---

## Appendix A: Quick Reference — Correct Pattern

```blade
<x-ui.table title="Daftar X" subtitle="..." :records="$items">
    <x-slot name="toolbar">
        {{-- Buttons, badges --}}
    </x-slot>

    <x-slot name="filters">
        <form method="GET" action="{{ route('x.index') }}" id="x-filter-form">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <x-datatable.search name="search" :value="$search" form="x-filter-form" />
                <x-ui.select name="status" size="sm" class="w-auto min-w-180px" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    <option value="active" @selected($status === 'active')>Aktif</option>
                </x-ui.select>
                <x-ui.table-per-page name="perPage" :value="$perPage" :options="[10, 25, 50]" form="x-filter-form" />
                <input type="hidden" name="sortField" value="{{ $sortField }}">
                <input type="hidden" name="sortAsc" value="{{ $sortAsc ? 'true' : 'false' }}">
            </div>
        </form>
    </x-slot>

    <x-slot name="thead">
        <x-ui.table-th :min-width="false" align="center" class="w-60px">
            <x-ui.table-checkbox model="selectAll" label="Pilih semua" />
        </x-ui.table-th>
        <x-ui.table-th field="name" :sortField="$sortField" :sortAsc="$sortAsc">Name</x-ui.table-th>
        <x-ui.table-th align="center">Status</x-ui.table-th>
        <x-ui.table-th align="end">Aksi</x-ui.table-th>
    </x-slot>

    <x-slot name="tbody">
        @forelse($items as $item)
            <tr>
                <td class="text-center">
                    <x-ui.table-checkbox model="selectedIds" :value="$item->id" />
                </td>
                <td><span class="text-gray-900 fw-semibold fs-6">{{ $item->name }}</span></td>
                <td class="text-center">
                    <x-ui.badge :variant="$item->status ? 'success' : 'danger'">
                        {{ $item->status ? 'Aktif' : 'Nonaktif' }}
                    </x-ui.badge>
                </td>
                <td class="text-end">
                    <x-ui.action-menu>
                        <x-ui.action-menu-item :href="route('x.edit', $item)">
                            <x-ui.icon name="pencil" class="fs-5" /> Edit
                        </x-ui.action-menu-item>
                    </x-ui.action-menu>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4">
                    <x-ui.empty-state title="Data tidak ditemukan" class="py-15" />
                </td>
            </tr>
        @endforelse
    </x-slot>
</x-ui.table>
```

## Appendix B: Controller Requirements

Controller must provide these variables for `x-ui.table` to work:

```php
public function index(Request $request)
{
    $search = $request->input('search', '');
    $sortField = $request->input('sortField', 'created_at');
    $sortAsc = $request->input('sortAsc', 'false') === 'true';
    $perPage = (int) $request->input('perPage', 10);

    $items = Model::query()
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
        ->orderBy($sortField, $sortAsc ? 'asc' : 'desc')
        ->paginate($perPage)
        ->appends(compact('search', 'sortField', 'sortAsc', 'perPage'));

    return view('admin.xxx.index', compact(
        'items', 'search', 'sortField', 'sortAsc', 'perPage'
    ));
}
```
