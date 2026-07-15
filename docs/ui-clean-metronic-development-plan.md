<!-- markdownlint-disable MD013 -->

# SPM Clean Metronic Development Plan

Dokumen ini menjadi referensi pengembangan UI SPM agar seluruh halaman Laravel Blade + Metronic tampil lebih clean, rapi, flat, minim ornamen, dan tetap sesuai konteks Sistem Penjaminan Mutu / akreditasi pesantren.

## Purpose

- Menyatukan standar UI/UX untuk role `super admin`, `admin`, `pesantren`, dan `asesor`.
- Menjaga base Metronic 8.1.8 dari `C:\laragon\www\dist\dist`.
- Memaksa reuse komponen `x-ui.*` sebelum raw Metronic markup.
- Menjadikan standar halaman, komponen, copy, dan enforcement bisa dicek secara objektif.

## Scope

Yang distandarkan:

- Visual language clean-flat berbasis Metronic.
- Reusable component API catalog untuk `resources/views/components/ui/*.blade.php`.
- Page Standardization Spec untuk dashboard, index/list, detail, form/wizard, workflow/scoring, dan empty/error pages.
- System Context UX and Copy Rules untuk domain SPM / akreditasi pesantren.
- Enforcement Gates untuk mencegah UI/copy drift dan raw markup yang tidak perlu.
- Phase rollout untuk refactor halaman berikutnya.

## Non-goals

- Tidak mengganti Metronic dengan design system baru.
- Tidak mengubah workflow, query, permission, policy, status legal, atau validasi bisnis.
- Tidak menambah dependency frontend.
- Tidak membuat gaya marketing/e-commerce/CRM/project-management.
- Tidak membuat variasi visual per role yang memecah konsistensi sistem.

## Phase Overview

| Phase | Fokus | Output | Gate |
| --- | --- | --- | --- |
| Phase 1 | Foundation docs | Prinsip, scope, visual language | Semua aturan punya contoh dan larangan |
| Phase 2 | Component contract | Reusable Component Catalog | API diverifikasi dari source Blade |
| Phase 3 | Page standard | Page Standardization Spec | Setiap tipe halaman punya skeleton/checklist |
| Phase 4 | Copy/domain | System Context UX and Copy Rules | Copy tetap domain SPM akreditasi pesantren |
| Phase 5 | Enforcement | Blocker/Warning/Pass + grep checks | Gate bisa dijalankan developer |
| Phase 6 | Rollout | Urutan refactor halaman | Tidak mencampur UI dengan bisnis logic |

## Visual Language

Nama arah visual: **SPM Clean Metronic**.

### Prinsip wajib

- Base tetap Metronic 8.1.8 demo42.
- Gunakan white/near-white surfaces, spacing jelas, border halus, typography tegas.
- Warna hanya untuk status, action priority, dan feedback sistem.
- Icon hanya untuk action, status, navigation, atau empty state yang membantu pemahaman.
- Komponen `spm-*` hanya adapter lokal, bukan pengganti class Metronic.
- Ornamen visual tidak boleh mengalahkan tugas utama: verifikasi, review, visitasi, scoring, validasi, banding.

### Batas visual noise

- Maksimal 1 primary button per section.
- Maksimal 2 level nested card.
- Maksimal 1 icon dekoratif per empty state.
- Badge status harus relevan dengan workflow/status, bukan hiasan.
- Hindari gradient, shadow berat, warna acak, dan icon tanpa makna.
- Table list operasional pakai `table align-middle table-row-dashed fs-6 gy-5`, bukan `table-striped`.

## Metronic Preservation Contract

- Reference theme wajib: `C:\laragon\www\dist\dist`.
- Shell tetap mengikuti demo42: `kt_app_body`, `app-default`, fixed header/sidebar, `app-container`, `app-header`, `app-wrapper`, `app-main`, `app-content`.
- Asset order tetap: `plugins.bundle.css`, `style.bundle.css`, app override, lalu `plugins.bundle.js` sebelum app script.
- Page title/breadcrumb tetap memakai pola theme: `page-title`, `page-heading`, `breadcrumb`, `breadcrumb-item`.
- Card/table/modal/badge harus mempertahankan class Metronic; `spm-*` hanya tambahan.
- Jika local style konflik dengan theme reference, pilih markup/class theme lalu bungkus lewat `x-ui.*`.

## Reusable Component Catalog

Semua API di bawah diverifikasi dari source `resources/views/components/ui/*.blade.php` saat dokumen ini dibuat. Jika source berubah, katalog ini harus diperbarui sebelum refactor halaman.

| Component | Source | Verified API | Slots | Default Metronic/classes | Use | Avoid |
| --- | --- | --- | --- | --- | --- | --- |
| `x-ui.page` | `resources/views/components/ui/page.blade.php` | props: `title`, `subtitle=null`, `compact=false`, `showHeading=true` | default, `toolbar` | `d-flex flex-column`, `spm-page`, title/subtitle classes | Detail, form, wizard, dashboard container | New `@section('header')` around same page |
| `x-ui.index-layout` | `resources/views/components/ui/index-layout.blade.php` | props: `title`, `subtitle=null`, `tableHeader=null` | default, `toolbar`, `tabs`, `content` | wraps `x-ui.page compact`, optional `card card-flush spm-table-shell` | Index/list pages | Manual page shell for list pages |
| `x-ui.card` | `resources/views/components/ui/card.blade.php` | props: `title=null`, `subtitle=null`, `flush=false` | default, `toolbar` | `card`, `card-header border-0`, `card-body` | Content grouping | Nested card depth >2 |
| `x-ui.section-card` | `resources/views/components/ui/section-card.blade.php` | props: `title=null`, `subtitle=null` | default, `toolbar` | `card spm-section-card`, `card-header border-0 py-4`, `card-body p-0` | Important section grouping | Decorative sections with no task value |
| `x-ui.table` | `resources/views/components/ui/table.blade.php` | props: `title=null`, `subtitle=null`, `records=null`, `showPerPage=true`, `perPagePosition=footer`, `perPageVariant=compact`, `perPageOptions=[10,25,50]`, `tableClass=null` | `thead`, `tbody`, `filters`, `toolbar` | `card spm-table-shell`, table `table align-middle table-row-dashed fs-6 gy-5` | Operational list with pagination/filter/actions | Raw operational table |
| `x-ui.simple-table` | `resources/views/components/ui/simple-table.blade.php` | props: `dense=false`, `tableClass=null` | default | `table-responsive`, `spm-simple-table` | Matrix/form table | Main operational list |
| `x-ui.table-th` | `resources/views/components/ui/table-th.blade.php` | props: `field=null`, `sortField=null`, `sortAsc=false`, `align=start`, `minWidth=true`, `form=null` | default | `th`, sortable link, `text-start/center/end` | Table header, sorting | Manual sort icon/link duplication |
| `x-ui.button` | `resources/views/components/ui/button.blade.php` | props: `href=null`, `type=button`, `variant=primary`, `size=md`, `unstyled=false`, `icon=null`, `iconPosition=start`, `iconClass=null` | default | `btn spm-btn`, variants `primary`, `secondary`, `light`, `success`, `warning`, `danger`, `info`, `link`, `light-*` | Page/form/modal actions | Raw `<button class="btn ...">` for covered variants |
| `x-ui.icon-button` | `resources/views/components/ui/icon-button.blade.php` | props: `icon`, `label`, `href=null`, `type=button`, `variant=light`, `size=sm` | none | `btn btn-icon`, `aria-label`, `title` | Icon-only accessible actions | Icon-only button without label |
| `x-ui.badge` | `resources/views/components/ui/badge.blade.php` | props: `variant=primary`, `light=true` | default | `badge badge-light-* fw-semibold spm-badge` | Non-workflow labels/counts | Akreditasi status |
| `x-ui.status-badge` | `resources/views/components/ui/status-badge.blade.php` | props: `variant=primary`, `light=true` | default | `badge badge-light-* fw-semibold spm-status-badge` | Status labels | Raw `badge-light-*` in Blade |
| `x-ui.empty-state` | `resources/views/components/ui/empty-state.blade.php` | props: `title`, `description=null`, `illustration=null`, `variant=primary` | `icon`, `action` | centered flex, `symbol symbol-65px` fallback icon | Empty/filter/task states | Plain text empty states |
| `x-ui.modal` | `resources/views/components/ui/modal.blade.php` | props: `name`, `show=false`, `maxWidth=2xl`, `title=null`, `subtitle=null`, `icon=null`, `variant=primary` | default | Alpine dialog, `data-ui-modal=metronic`, width class `spm-modal-*` | Standard modal shell | Raw modal shell when component fits |
| `x-ui.modal-header` | `resources/views/components/ui/modal-header.blade.php` | props: `title=''`, `subtitle=null`, `icon=null`, `variant=primary`, `close=true`, `titleId=null` | none | `modal-header spm-modal-header`, optional icon, close button | Modal title/action close | Raw `modal-header` |
| `x-ui.modal-body` | `resources/views/components/ui/modal-body.blade.php` | no props | default | `modal-body spm-modal-body` | Modal content | Raw `modal-body` |
| `x-ui.modal-footer` | `resources/views/components/ui/modal-footer.blade.php` | no props | default | `modal-footer spm-modal-footer d-flex justify-content-end gap-3` | Modal action area | Raw `modal-footer` |
| `x-ui.tabs` | `resources/views/components/ui/tabs.blade.php` | no props | default | `nav nav-tabs nav-line-tabs nav-line-tabs-2x` | Detail/workflow tabs | Ad-hoc nav markup |
| `x-ui.tab` | `resources/views/components/ui/tab.blade.php` | props: `active=false`, `href=null`, `type=button` | default | `nav-link text-active-primary spm-tab-link` | Tab item/link | Non-semantic tab buttons |
| `x-ui.input` | `resources/views/components/ui/input.blade.php` | props: `model=null`, `id=null`, `type=text`, `modifier=null`, `disabled=false`, `invalid=false` | none | `form-control form-control-solid` | Text/date/number fields | Raw input without error state |
| `x-ui.select` | `resources/views/components/ui/select.blade.php` | props: `model=null`, `id=null`, `placeholder=null`, `options=[]`, `size=md`, `modifier=null`, `disabled=false`, `invalid=false` | default option slot | `form-select form-select-solid` | Form select | Raw select without standard style |
| `x-ui.textarea` | `resources/views/components/ui/textarea.blade.php` | props: `model=null`, `id=null`, `rows=4`, `modifier=null`, `disabled=false`, `invalid=false` | default | `form-control form-control-solid`, `data-kt-autosize=true` | Notes/reason fields | Raw textarea |
| `x-ui.form-field` | `resources/views/components/ui/form-field.blade.php` | props: `label=null`, `for=null`, `error=[]`, `hint=null`, `required=false` | default | `fv-row spm-form-field`, `form-label` | Label + input + error/hint | Inputs without labels/errors |
| `x-ui.filter-bar` | `resources/views/components/ui/filter-bar.blade.php` | prop: `class=''` | default | `spm-filter-bar` | Filter grouping | Scattered filters |
| `x-ui.filter-select` | `resources/views/components/ui/filter-select.blade.php` | props: `name=null`, `value=null`, `placeholder=null`, `options=[]`, `size=md`, `form=null` | default | `form-select form-select-solid spm-filter-select`, auto submit | List filters | Manual filter select repeated |
| `x-ui.toolbar` | `resources/views/components/ui/toolbar.blade.php` | no props | default | `d-flex flex-wrap align-items-center gap-2` | Action grouping | Unstructured button groups |
| `x-ui.action-menu` | `resources/views/components/ui/action-menu.blade.php` | props: `label=Aksi`, `menuId=null` | default | Metronic `menu menu-sub menu-sub-dropdown`, Alpine placement | Row actions | Multiple inline row buttons |
| `x-ui.action-menu-item` | `resources/views/components/ui/action-menu-item.blade.php` | props: `href=null`, `type=button`, `variant=default` | default | `menu-link`, variants text color | Row action item | Raw dropdown item markup |
| `x-ui.stat-card` | `resources/views/components/ui/stat-card.blade.php` | props: `label`, `value`, `variant=primary`, `icon=null` | none | wraps `x-ui.card`, optional `symbol` icon | Dashboard metrics | Decorative metrics without task meaning |
| `x-ui.metric-box` | `resources/views/components/ui/metric-box.blade.php` | props: `label`, `value`, `variant=primary`, `description=null`, `actionLabel=null`, `actionHref=null` | none | dashed border metric surface | Compact summary metrics | Primary dashboard replacement |
| `x-ui.detail-item` | `resources/views/components/ui/detail-item.blade.php` | props: `label`, `value=null`, `span=1` | default | `col-md-6/12 spm-detail-item` | Detail metadata | Free-form label/value grids |
| `x-ui.progress` | `resources/views/components/ui/progress.blade.php` | props: `value=0`, `variant=primary`, `height=8px`, `label=null`, `meta=null`, `dynamicValue=null` | none | `progress`, `progress-bar bg-*` | Completion/progress | Status substitute |
| `x-ui.stepper` | `resources/views/components/ui/stepper.blade.php` | props: `variant=pills`, `direction=row` | default | `stepper stepper-*` | Workflow stage visualization | Decorative timeline with no workflow meaning |

## Component Rules

- Use `x-ui.*` if a component covers the pattern.
- Raw Metronic allowed only when no component exists and pattern appears once.
- If same raw pattern appears in 2+ pages, create or extend a reusable `x-ui.*` component before batch refactor.
- Business-specific partials boleh ada, tetapi harus dibangun dari `x-ui.*` primitives.
- New component must have clear owner; no duplicate component with overlapping purpose.

## Reuse Decision Matrix

| Situation | Decision | Required note |
| --- | --- | --- |
| Existing `x-ui.*` covers layout/control | Use existing component | None |
| Existing component nearly fits | Extend existing component minimally | Mention source component and prop/slot change |
| Raw Metronic pattern appears once | Raw allowed | Add theme reference path/comment if complex |
| Raw Metronic pattern appears in 2+ pages | Create/reuse `x-ui.*` | Document owner and usage |
| Business-specific UI block | Use partial composed from `x-ui.*` | Partial must not own primitive styles |
| Akreditasi status label/color | Use `App\Support\AkreditasiStatusPresenter` + `x-ui.status-badge` | No local map |

## Page Migration Checklist

Page cannot pass review unless every item is checked or has an Exception Record.

- [ ] Page uses `x-ui.index-layout` for list/index/dashboard-like operational surfaces or `x-ui.page` for detail/form/wizard surfaces.
- [ ] No new `@section('header')` / `<x-slot name="header">` when page shell component fits.
- [ ] Cards use `x-ui.card` / `x-ui.section-card`; nested card depth <= 2.
- [ ] Operational list uses `x-ui.table`, `x-ui.table-th`, `x-ui.action-menu`, `x-ui.action-menu-item`.
- [ ] Matrix/form table uses `x-ui.simple-table` only when not an operational list.
- [ ] Akreditasi status uses `AkreditasiStatusPresenter` and `x-ui.status-badge`.
- [ ] Empty/filter/task states use `x-ui.empty-state` with domain-specific next-step copy.
- [ ] Modal internals use `x-ui.modal-header`, `x-ui.modal-body`, `x-ui.modal-footer` when possible.
- [ ] Page has <= 1 primary action per section.
- [ ] No raw `badge-light-*`, local status map, or generic marketing copy.

## Page Standardization Spec

### Dashboard pages

Skeleton:

```blade
<x-ui.page title="Dashboard" subtitle="Ringkasan aktivitas akreditasi sesuai peran">
    <x-slot name="toolbar">primary role action</x-slot>
    <section>role metrics via x-ui.stat-card</section>
    <section>workflow summary via x-ui.card / x-ui.table</section>
    <section>next tasks / recent activity</section>
</x-ui.page>
```

Required components: `x-ui.page`, `x-ui.stat-card`, `x-ui.card`, `x-ui.table` if listing activity, `x-ui.status-badge`, `x-ui.empty-state`.

Action placement: primary action in toolbar; secondary links inside cards; no destructive action on dashboard.

Density limits: max 6 stat cards; max 3 dashboard sections; max 1 icon per stat card.

Role consistency: same structure for `super admin`, `admin`, `pesantren`, `asesor`; content and action permission may differ.

Acceptance checklist: dashboard must answer “apa status sistem/peran saya” and “apa tindakan berikutnya” without ornamental cards.

### Index/list pages

Skeleton:

```blade
<x-ui.index-layout title="Daftar ..." subtitle="Kelola data ...">
    <x-slot name="toolbar">primary create/export action</x-slot>
    <x-slot name="content">
        <x-ui.table>filters + rows + actions</x-ui.table>
    </x-slot>
</x-ui.index-layout>
```

Required components: `x-ui.index-layout`, `x-ui.filter-bar`, `x-ui.filter-select`, `x-ui.table`, `x-ui.table-th`, `x-ui.status-badge`, `x-ui.action-menu`, `x-ui.empty-state`.

Action placement: one primary action in toolbar; row actions in `x-ui.action-menu`; destructive actions inside menu with danger variant and confirmation.

Density limits: max 1 toolbar row, max 5 filters before collapse/design reconsideration, max 1 badge cluster per row.

Role consistency: list structure same across roles; columns/actions may differ by role permission.

Acceptance checklist: list page must support scan, filter, sort, row action, and empty/filter-empty state.

## Table/List Contract

Table/list pages are operational work surfaces. They must feel like one system across `admin`, `pesantren`, and `asesor` pages, even when columns and filters differ.

### Card header

- Use `x-ui.index-layout` for page shell and `x-ui.table` for the list card.
- Left side: table title and short operational subtitle.
- Right side: exactly one dominant page/card action when needed.
- Create/add actions may use `variant="primary"`.
- Export/import actions use `variant="secondary"` or `variant="light"` unless export is the page's main workflow.
- Status counters in toolbar use `x-ui.badge`; they must not compete with the primary action.

### Filter bar

- Put search, selects, and filter actions in one compact `x-ui.filter-bar` row when viewport allows.
- Search belongs left / flex-grow; select filters belong right.
- Select filters should auto-submit when safe and current page already uses this pattern.
- If a manual apply action is needed, use a small secondary/light button labeled `Terapkan` or `Cari`.
- Do not use a full-width green/primary `Cari` button on operational list pages.
- Do not stack filters into a form-like block unless the page is a search workflow, not a list page.

### Button hierarchy in list pages

- One primary action per list card/page section.
- Search/filter submit is never the primary action for ordinary list pages.
- Row actions use `x-ui.action-menu` or one compact secondary/light button.
- Destructive row actions stay inside menu/modal with danger variant and confirmation.
- Export is secondary unless there is no create/add action and export is the user's main task.

### Table density and row actions

- Use `x-ui.table`, `x-ui.table-th`, and the clean Metronic table class: `table align-middle table-row-dashed fs-6 gy-5`.
- Keep action column on the far right with `align="end"`.
- Keep status columns centered only when they are compact badges.
- Avoid repeating large badges or icon clusters inside table rows.
- Use `x-ui.empty-state` for empty and filter-empty results.

### Bulk selection

- Checkbox column is allowed only when a visible bulk action exists or is immediately revealed after selection.
- If there is no bulk action, remove checkbox column from the list page.
- Do not keep hidden `selectedIds` state without a user-visible bulk operation.

### Allowed examples

- `resources/views/admin/accounts/index.blade.php`: compact role filter tabs + search, one primary `Tambah Akun`, simple row action column.
- `resources/views/admin/pesantren/index.blade.php`: compact search + select filters can be reused after export hierarchy is checked.

### Forbidden examples

- `resources/views/admin/asesor/index.blade.php` current drift: search + multiple selects plus primary `Cari` button that turns the filter area into a form block.
- Full-width green `Cari` below filters on a list page.
- Primary `Ekspor Data` and primary `Cari` competing in the same table card.
- Checkbox column without visible bulk action.

### Pilot target

Start Phase 11 implementation with `resources/views/admin/asesor/index.blade.php`, then compare against `resources/views/admin/accounts/index.blade.php` before touching broader admin lists.

## Button Hierarchy Contract

- `primary`: create/add/start the main workflow for the current page or section.
- `secondary` / `light`: filter apply, export, cancel, back, open supporting detail.
- `danger`: destructive or rejection action, always with clear confirmation when data changes.
- `icon-button`: compact utility only when label would be redundant and context is obvious.
- Table row actions prefer `x-ui.action-menu`; only use direct buttons for one obvious safe action.
- A card/section with more than one primary button must be refactored before it passes review.

## Role Dashboard Grammar

All role dashboards share one layout grammar:

1. Page header and role context.
2. Compact context summary, not ornamental hero noise.
3. Metrics row using consistent card density.
4. Primary work area for current role tasks.
5. Secondary insight area for chart/status summary.
6. Recent activity or operational list.

Allowed differences by role: labels, data, route targets, and workflow-specific next actions.

Not allowed: different spacing scale, different action hierarchy, decorative icon density, or role-specific visual style that makes dashboards feel like separate products.

## Spacing and Density Scale

- Default page section gap: `mb-5` or `row g-5` for dashboard/detail sections.
- Compact list/filter gap: `gap-3` and `row g-3` for table toolbars and quick actions.
- Card body padding defaults to `p-4` or `p-5`; avoid `p-6` unless the section is a single high-focus summary.
- Avoid mixing `g-6`, `p-6`, and large colored surfaces in operational pages.
- Use neutral surfaces (`bg-body`, dashed gray border) for supporting blocks; reserve colored backgrounds for alerts or critical workflow states.
- A page should not add extra vertical spacing to compensate for inconsistent component structure; fix the component composition instead.

## Icon Usage Rules

- Icons must communicate action, status, navigation, or empty-state meaning.
- Decorative icons are not allowed in dense tables or dashboard summary blocks.
- List/table rows should use icons only inside action menus or status/action affordances.
- Dashboard quick actions may use one icon per card, with neutral surface and consistent size.
- Avoid role-specific icon styles; keep `x-ui.icon` as the default icon primitive.

## Sidemenu Stability Rules

- Sidemenu structure and visual hierarchy must stay stable across roles.
- Active state, section spacing, icon size, and badge style must not vary per page.
- Sidebar badges use `x-ui.badge` or existing sidebar component patterns, not raw badge markup.
- Do not add page-specific ornament, extra shadows, or custom colors to sidemenu items.
- Navigation copy stays domain-specific and concise: role, master data, akreditasi, dokumen, panduan, profil.

### Detail pages

Skeleton:

```blade
<x-ui.page title="Detail ..." subtitle="Informasi ...">
    <x-slot name="toolbar">back/secondary action</x-slot>
    <x-ui.card title="Ringkasan">x-ui.detail-item grid</x-ui.card>
    <x-ui.tabs>domain tabs</x-ui.tabs>
</x-ui.page>
```

Required components: `x-ui.page`, `x-ui.card`, `x-ui.detail-item`, `x-ui.tabs`, `x-ui.tab`, `x-ui.status-badge`, `x-ui.empty-state`.

Action placement: back/secondary in toolbar; workflow actions near status section; destructive actions behind confirmation modal.

Density limits: max 2 card levels; tab labels <= 3 words; avoid repeating same metadata in multiple cards.

Role consistency: same entity detail uses same order: summary, status/workflow, documents/instruments, history/actions.

Acceptance checklist: detail page must show current status, owner, required next action, and supporting documents/data.

### Form/wizard pages

Skeleton:

```blade
<x-ui.page title="Form ..." subtitle="Lengkapi data ...">
    <x-ui.card title="Data utama">
        <x-ui.form-field><x-ui.input /></x-ui.form-field>
    </x-ui.card>
    <x-ui.card>actions</x-ui.card>
</x-ui.page>
```

Required components: `x-ui.page`, `x-ui.card`, `x-ui.form-field`, `x-ui.input`, `x-ui.select`, `x-ui.textarea`, `x-ui.button`, `x-ui.empty-state` for missing prerequisites.

Action placement: submit primary at footer/end; cancel/back secondary; destructive reset/delete not inline with submit.

Density limits: max 7 visible fields per card section; split long forms by domain section; required marker only for true required fields.

Role consistency: field grouping follows business domain, not role decoration.

Acceptance checklist: form must clarify required fields, validation errors, save target, and next workflow state.

### Workflow/scoring pages

Skeleton:

```blade
<x-ui.page title="Penilaian Akreditasi" subtitle="Review dan input nilai sesuai tahap">
    <x-ui.stepper>workflow stages</x-ui.stepper>
    <x-ui.card title="Status dan tugas">current assignment</x-ui.card>
    <x-ui.table>instrument/scoring rows</x-ui.table>
</x-ui.page>
```

Required components: `x-ui.page`, `x-ui.stepper`, `x-ui.card`, `x-ui.table`, `x-ui.status-badge`, `x-ui.progress`, `x-ui.button`, `x-ui.modal-*`.

Action placement: primary workflow action near current stage; scoring actions in row/detail context; finalize actions require confirmation modal.

Density limits: max 1 workflow stepper per page; status colors only from presenter; no decorative workflow icons beyond stage/status meaning.

Role consistency: admin sees validation/assignment; pesantren sees submission/document state; asesor sees review/scoring/visitasi; structure still follows status -> task -> evidence -> action.

Acceptance checklist: workflow page must show current stage, blocking requirements, evidence/data, and next permissible action.

### Empty/error pages

Skeleton:

```blade
<x-ui.empty-state title="Belum ada data" description="... sesuai role dan workflow ...">
    <x-slot name="action">optional next action</x-slot>
</x-ui.empty-state>
```

Required components: `x-ui.empty-state`, optional `x-ui.button`, optional `x-ui.status-badge` only if status context exists.

Action placement: one next-step action only; no multiple competing CTA.

Density limits: title <= 6 words; description <= 1 sentence; one icon/illustration maximum.

Role consistency: empty states explain what that role can do next; do not expose actions outside permission.

Acceptance checklist: empty/error page must state condition, role-appropriate next step, and recovery path if any.

## System Context UX and Copy Rules

UI/UX dan copy harus tetap berada di domain SPM / akreditasi pesantren. Jika aturan bisa dipakai tanpa perubahan untuk e-commerce/CRM/project dashboard, aturan itu terlalu generic.

### Tone

- Indonesian-first.
- Formal, jelas, operasional.
- Helpful, bukan marketing.
- Role-aware: `super admin`, `admin`, `pesantren`, `asesor`.
- Workflow-aware: `Pengajuan`, `Verifikasi Berkas`, `Review Asesor`, `Visitasi`, `Penilaian Pasca Visitasi`, `Validasi Admin`, `Selesai`, `Ditolak`, `Banding`.

### Vocabulary allowed

- `akreditasi`, `pesantren`, `asesor`, `visitasi`, `EDPM`, `IPM`, `SDM`, `dokumen`, `banding`, `validasi admin`, `verifikasi berkas`, `penilaian`, `instrumen`, `kartu kendali`, `laporan visitasi`.

### Vocabulary forbidden outside examples

- `project`, `customer`, `order`, `campaign`, `sales`, `ticket`, `lead`, `pipeline`, `checkout`, `cart`, `conversion`, `upsell`.

### Microcopy rules

| Surface | Rule | Allowed | Forbidden |
| --- | --- | --- | --- |
| Page title | Noun + domain object | `Daftar Akreditasi` | `Project Overview` |
| Section title | Explain task/data | `Dokumen Akreditasi` | `Awesome Insights` |
| Empty state | State condition + next step | `Belum ada pengajuan akreditasi. Ajukan akreditasi ketika data pesantren sudah lengkap.` | `No projects yet. Start your journey.` |
| Error state | State issue + recovery | `Dokumen gagal diunggah. Periksa format dan coba lagi.` | `Something went wrong!` |
| Success message | Confirm business action | `Pengajuan akreditasi berhasil dikirim.` | `Great job! Campaign launched.` |
| Destructive confirmation | Explicit object + consequence | `Hapus dokumen ini? Dokumen tidak lagi tersedia untuk verifikasi.` | `Are you sure?` |
| Filter label | Domain field | `Status Akreditasi` | `Pipeline Stage` |
| Primary button | Verb + object | `Ajukan Akreditasi`, `Simpan Penilaian` | `Get Started`, `Launch` |
| Table column | Domain data | `Nama Pesantren`, `Status`, `Asesor` | `Customer`, `Deal` |
| Workflow status | Use presenter/model language | `Verifikasi Berkas` | `In Progress` |

### Copy by page type

- Dashboard: copy answers role status and next task. Allowed: `3 pengajuan menunggu verifikasi`. Forbidden: `3 opportunities need action`.
- Index/list: copy describes data set and operation. Allowed: `Kelola daftar pesantren dan status akreditasinya`. Forbidden: `Manage your customers`.
- Detail: copy shows entity, status, evidence. Allowed: `Detail pengajuan akreditasi pesantren`. Forbidden: `Project detail`.
- Workflow/scoring: copy uses official stage/action. Allowed: `Input nilai visitasi`. Forbidden: `Complete task ticket`.
- Empty/error: copy gives role-safe recovery. Allowed: `Belum ada tugas visitasi untuk Anda.` Forbidden: `No tickets assigned`.

## Enforcement Gates

### Severity model

- **Blocker**: cannot merge/refactor page until fixed or Exception Record exists.
- **Warning**: allowed only with written exception and refactor target.
- **Pass**: meets standard.

### Blocker rules

1. Raw `badge-light-*` in app views when `x-ui.badge`/`x-ui.status-badge` fits.
2. Local `$statusVariantMap`, `$statusBadgeClass`, `$stageMap`, `$statusLabels` in Blade.
3. New `@section('header')` or `<x-slot name="header">` when `x-ui.page`/`x-ui.index-layout` fits.
4. Raw `modal-header`, `modal-body`, `modal-footer` when `x-ui.modal-*` fits.
5. Operational list table not using `x-ui.table`.
6. Page has more than one primary action in same section.
7. Nested card depth above 2.
8. Decorative icon without action, status, navigation, or empty-state meaning.
9. Duplicate component overlaps existing `x-ui.*` owner.
10. Copy/UI drifts into generic SaaS/e-commerce/CRM/marketing/project language.
11. Domain terms renamed away from existing app language without explicit product decision.

### Grep/search checks

Run these during page refactor review:

```powershell
Select-String -Path "resources\views\**\*.blade.php" -Pattern "badge-light-"
Select-String -Path "resources\views\**\*.blade.php" -Pattern "statusVariantMap","statusBadgeClass","stageMap","statusLabels"
Select-String -Path "resources\views\**\*.blade.php" -Pattern "@section\('header'\)","x-slot name=\"header\""
Select-String -Path "resources\views\**\*.blade.php" -Pattern "modal-header","modal-body","modal-footer"
Select-String -Path "resources\views\**\*.blade.php" -Pattern "table-striped","table-row-bordered"
Select-String -Path "resources\views\**\*.blade.php" -Pattern "project","customer","order","campaign","sales","ticket"
```

### Exception Record

Use only for known, time-bounded exceptions.

```text
Path:
Exception:
Reason:
Expiry / refactor target:
Owner:
```

## Audit Checklist

- [ ] Page shell uses correct `x-ui.page` or `x-ui.index-layout`.
- [ ] Component usage follows Reusable Component Catalog.
- [ ] Page type matches Page Standardization Spec.
- [ ] Copy follows System Context UX and Copy Rules.
- [ ] No blocker grep result without Exception Record.
- [ ] UI remains clean-flat: low color noise, clear hierarchy, no decorative overload.
- [ ] Business workflow and permissions unchanged.

## Rollout Phases

### Phase 1: Documentation foundation

- Create/maintain this document.
- Cross-reference `docs/ui-standardization.md`.
- Freeze vocabulary, component catalog, page specs, and enforcement gates.

### Phase 2: Component source alignment

- Compare catalog with actual `resources/views/components/ui/*.blade.php`.
- Add missing props/slots only when page refactor needs them.
- Do not add speculative components.

### Phase 3: Pilot page refactor

- Pick 1 admin list page, 1 pesantren workflow/list page, 1 asesor workflow/detail page.
- Apply page checklist and enforcement gates.
- Keep changes UI-only.

### Phase 4: Batch refactor by pattern

- Batch 1: status presenter/badges.
- Batch 2: page shell/header cleanup.
- Batch 3: tables/filter/action menus.
- Batch 4: modals and empty states.
- Batch 5: copy cleanup and role workflow consistency.

### Phase 5: Regression guard

- Run Blade cache and targeted Metronic frontend test after implementation changes.
- Keep docs-only changes to docs checks.
- Document exceptions with expiry.

### Phase 6: Periodic UI debt audit

- Run grep checks before release or large UI merge.
- Remove expired exceptions.
- Update component catalog when `x-ui.*` API changes.

## Verification Commands

```powershell
Test-Path -LiteralPath "docs\ui-clean-metronic-development-plan.md"
Select-String -Path "docs\ui-clean-metronic-development-plan.md" -Pattern "Phase 1","Phase 2","Phase 3","Phase 4","Phase 5","Phase 6"
Select-String -Path "docs\ui-clean-metronic-development-plan.md" -Pattern "x-ui.page","x-ui.index-layout","x-ui.card","x-ui.table","x-ui.button","x-ui.status-badge","x-ui.modal","x-ui.empty-state"
Select-String -Path "docs\ui-clean-metronic-development-plan.md" -Pattern "Reusable Component Catalog","Reuse Decision Matrix","Page Migration Checklist","Page Standardization Spec","System Context UX and Copy Rules","Enforcement Gates","Blocker"
Select-String -Path "docs\ui-clean-metronic-development-plan.md" -Pattern "SPM","akreditasi","pesantren","asesor","visitasi","EDPM","IPM","SDM","banding"
git diff --name-only
```
