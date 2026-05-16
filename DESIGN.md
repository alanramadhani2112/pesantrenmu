---
version: 1.0
name: PesantrenMu
description: |
  Sistem Penjaminan Mutu PesantrenMu (SPM) - platform akreditasi pesantren Muhammadiyah.
  Multi-tenant Laravel 11 + Livewire Volt + Metronic 8 hybrid. Three roles: admin, asesor, pesantren.
  Visual identity built around the Muhammadiyah deep-green accent on a quiet enterprise canvas.
brand:
  primary_logo: /images/brand/logo-horizontal.svg
  vertical_logo: /images/brand/logo-vertical.svg
  mono_logo: /images/brand/logo-pesantrenmu.svg
  favicon: /images/brand/favicon.svg
  voice: tenang, padat, fungsional. Bahasa Indonesia (sapaan Anda). Hindari hyperbole.
audience:
  - Mudir/operator pesantren mengisi data akreditasi (formulir-heavy, mobile-friendly)
  - Asesor menilai dokumen dan butir EDPM (tabel-heavy, fokus + bukti file)
  - Admin BSPMu memutus pengajuan, master data, dan banding (dashboard + list table)

colors:
  primary:           "#005533"    # Muhammadiyah deep green (CTA, focus, active state)
  primary-deep:      "#003d24"    # active/pressed
  primary-hover:     "#006b40"
  primary-soft:      "#e6f5ee"    # tinted bg for selected row, badge bg
  primary-subtle:    "#f0faf5"    # quietest tint, section accents
  primary-on:        "#ffffff"    # ink on primary
  ink:               "#111827"    # primary text - page title, table cell value
  ink-secondary:     "#1f2937"    # body strong
  ink-mute:          "#374151"    # body
  ink-mute-2:        "#667085"    # caption, helper, table head
  ink-faint:         "#94a3b8"    # timestamp, meta
  ink-disabled:      "#cbd5e1"    # disabled control border
  canvas:            "#ffffff"    # card surface
  canvas-soft:       "#f7f9fc"    # app shell, page background
  canvas-quiet:      "#f8fafc"    # section bg / hover row
  canvas-tinted:     "#f8fbff"    # unread notification bg
  hairline:          "#edf1f6"    # default 1px border (sidebar, header)
  hairline-strong:   "#e8edf4"    # card border, dropdown border
  hairline-soft:     "#f1f5f9"    # divider list item
  success:           "#10b981"
  success-deep:      "#059669"
  success-soft:      "#ecfdf5"
  warning:           "#f59e0b"
  warning-deep:      "#d97706"
  warning-soft:      "#fffbeb"
  danger:            "#ef4444"
  danger-deep:       "#dc2626"
  danger-soft:       "#fef2f2"
  info:              "#0088ff"
  info-deep:         "#006fd1"
  info-soft:         "#eff6ff"

typography:
  fontFamilySans:
    family: "Inter"
    fallback: 'system-ui, -apple-system, "Segoe UI", sans-serif'
  scale:
    display-lg:
      size: 32px
      weight: 700
      lineHeight: 1.2
      letterSpacing: -0.01em
      use: "Auth hero, marketing hero (jarang)"
    page-title:
      size: 19px
      weight: 700
      lineHeight: 1.25
      letterSpacing: 0
      use: "spm-header-title pada layout shell, h1 page"
    section-title:
      size: 16px
      weight: 700
      lineHeight: 1.3
      use: "Card title, section header"
    body-strong:
      size: 14px
      weight: 600
      lineHeight: 1.5
      use: "Form label, button label, table column"
    body:
      size: 14px
      weight: 400
      lineHeight: 1.5
      use: "Default text, table cell, paragraph"
    body-sm:
      size: 13px
      weight: 400
      lineHeight: 1.45
      use: "Helper text, table dense, dropdown item"
    caption:
      size: 12px
      weight: 500
      lineHeight: 1.35
      use: "Breadcrumb, badge, meta caption"
    micro:
      size: 11px
      weight: 600
      lineHeight: 1.25
      use: "Timestamp, status pill text"

spacing:
  scale:
    "0": "0"
    "1": "4px"
    "2": "8px"
    "3": "12px"
    "4": "16px"
    "5": "20px"
    "6": "24px"
    "7": "28px"   # spm page main content top padding
    "8": "32px"
    "10": "40px"
  rhythm: |
    Card outer gap 24px (gap-6), section internal 16-20px, form-field 12px between fields,
    inline button group 8px, table row vertical 16px (gy-4).

radius:
  sm: 4px        # inline pill, badge
  md: 6px        # input, button (default)
  lg: 8px        # card body inner
  xl: 12px       # auth card, modal
  pill: 999px    # status badge dot

shadow:
  sm: "0 1px 2px rgba(15, 23, 42, 0.04)"
  md: "0 4px 12px rgba(15, 23, 42, 0.06)"   # card elevation
  lg: "0 16px 42px rgba(15, 23, 42, 0.12)"  # dropdown, popover
  xl: "0 18px 48px rgba(15, 23, 42, 0.08)"  # auth card

motion:
  fast: "120ms ease"      # hover bg color
  base: "180ms ease"      # button press, focus ring
  slow: "260ms ease-out"  # modal enter
  reducedMotion: |
    Honor `prefers-reduced-motion`. Disable enter/leave transitions; keep state-only color shifts.
---

# PesantrenMu DESIGN.md

This document is the visual contract for spm_fix. AI agents and human contributors should follow it when generating new pages, components, or marketing surfaces. It pairs with [`UI_GUIDELINES.md`](UI_GUIDELINES.md) (which is the engineering rule book) and the `x-ui.*` Blade components in [`resources/views/components/ui/`](resources/views/components/ui/).

## Aesthetic Direction

Quiet, instrumented, trustworthy. The product handles formal accreditation - it should feel like a government information system done well, not a SaaS marketing site. Examples to study: **Linear** (form density), **Supabase** (calm green accent), **Stripe** (table-first dashboards).

Avoid: gradient hero blobs, glassmorphism, oversized 3D illustrations, neon accents, animated orbs.

## Brand Anchors

- **Color anchor**: `#005533` Muhammadiyah deep green. Used sparingly for CTA, active nav, focus rings, and primary status only. Most surfaces should be neutral so the green carries weight.
- **Typeface**: Inter on every surface (auth, app, email). System fallback only for printable PDFs.
- **Logo lockup**: prefer horizontal logo on header (≤ 32px tall). Use vertical logo on auth split panel.
- **Tone**: Indonesian formal, sapaan "Anda". Microcopy padat, no exclamation marks, no emoji unless user-driven.

## Layout System

```
┌─────────────────────────────────── 86px header (sticky) ────────────────────────────────────┐
│  [Logo]   [Page title]                                          [Search] [Notif] [User menu] │
├──────────┬──────────────────────────────────────────────────────────────────────────────────┤
│          │  Breadcrumb (caption)                                                            │
│          │                                                                                  │
│ Sidebar  │  ┌─────────────────────────  Main content area  ──────────────────────────────┐ │
│ 280px    │  │                                                                            │ │
│ (white   │  │  <x-ui.page title="..."> + cards + tables + forms                           │ │
│  surface)│  │                                                                            │ │
│          │  └────────────────────────────────────────────────────────────────────────────┘ │
└──────────┴──────────────────────────────────────────────────────────────────────────────────┘
```

- App shell background: `canvas-soft #f7f9fc`.
- Sidebar background: `canvas #ffffff`, right border `hairline #edf1f6`.
- Header height: 86px desktop. Header title 19px / 18px mobile.
- Main content padding: `28px 24px 24px` mobile → `30px 40px 40px` xl.

Authenticated app uses [`resources/views/layouts/app.blade.php`](resources/views/layouts/app.blade.php). Auth uses `spm-auth-shell` gradient (radial primary tint at top-left over white-to-canvas vertical fade).

## Component Inventory (use these, not raw HTML)

40 `x-ui.*` components live in `resources/views/components/ui/`. **Always reach for these first.**

| Category | Components |
|---|---|
| Page chrome | `x-ui.page`, `x-ui.section-card`, `x-ui.card`, `x-ui.breadcrumb`, `x-ui.toolbar`, `x-ui.sidebar-section` |
| Forms | `x-ui.form-field`, `x-ui.input`, `x-ui.select`, `x-ui.textarea`, `x-ui.checkbox`, `x-ui.radio`, `x-ui.file-upload` |
| Buttons | `x-ui.button` (variants: primary, secondary, light, success, warning, danger, info, link, light-*), `x-ui.icon-button` |
| Tables | `x-ui.table`, `x-ui.simple-table`, `x-ui.table-th`, `x-ui.table-search`, `x-ui.table-per-page`, `x-ui.table-checkbox`, `x-ui.filter-bar`, `x-ui.filter-select` |
| Tabs/Nav | `x-ui.tabs`, `x-ui.tab` |
| Status | `x-ui.badge`, `x-ui.status-badge`, `x-ui.stat-card`, `x-ui.metric-row`, `x-ui.empty-state` |
| Overlays | `x-ui.modal`, `x-ui.modal-header`, `x-ui.modal-body`, `x-ui.modal-footer`, `x-ui.action-menu`, `x-ui.action-menu-item` |
| Detail | `x-ui.detail-item`, `x-ui.document-item`, `x-ui.index-layout` |
| Misc | `x-ui.icon` |

### Recipe: typical page

```blade
<x-ui.page title="Profil Pesantren" subtitle="Lengkapi data dasar pesantren Anda.">
    <x-slot name="toolbar">
        <x-ui.button variant="primary" wire:click="save">Simpan</x-ui.button>
    </x-slot>

    <x-ui.section-card title="Identitas">
        <div class="p-6 d-flex flex-column gap-4">
            <x-ui.form-field label="Nama Pesantren" for="nama" :error="$errors->get('nama')">
                <x-ui.input model="nama" />
            </x-ui.form-field>

            <x-ui.form-field label="Provinsi" for="provinsi" hint="Wilayah administratif tingkat 1.">
                <x-ui.select model="provinsi">...</x-ui.select>
            </x-ui.form-field>
        </div>
    </x-ui.section-card>
</x-ui.page>
```

### Recipe: list table

```blade
<x-ui.table title="Pengajuan Akreditasi" :records="$rows">
    <x-slot name="filters">
        <x-ui.table-search model="search" />
        <x-ui.filter-select model="status">...</x-ui.filter-select>
    </x-slot>

    <x-slot name="thead">
        <x-ui.table-th>Pesantren</x-ui.table-th>
        <x-ui.table-th>Status</x-ui.table-th>
        <x-ui.table-th>Aksi</x-ui.table-th>
    </x-slot>

    <x-slot name="tbody">
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row->user->pesantren->nama_pesantren }}</td>
                <td><x-ui.status-badge variant="success">Disetujui</x-ui.status-badge></td>
                <td><x-ui.action-menu>...</x-ui.action-menu></td>
            </tr>
        @endforeach
    </x-slot>
</x-ui.table>
```

## Status Vocabulary

Akreditasi state machine maps to one badge variant only. Keep this consistent across pages.

| State | Variant | Label (id) |
|---|---|---|
| Draft / belum dikirim | `secondary` | Draft |
| Diajukan, menunggu admin | `info` | Diajukan |
| Sedang dinilai asesor | `warning` | Dinilai |
| Perlu revisi | `warning` | Revisi Diperlukan |
| Disetujui / sertifikat terbit | `success` | Disetujui |
| Ditolak | `danger` | Ditolak |
| Banding aktif | `info` | Banding |
| Dibatalkan | `secondary` | Dibatalkan |

`x-ui.status-badge` defaults to light variant (`badge-light-{variant}`), which is the form we want everywhere except admin "decision" bars where solid badges are appropriate.

## Forms

- Labels: 14px, weight 600, color ink-mute (`#374151`). Always use `<x-ui.form-field label="...">`.
- Error: red 12px below input, no icon. Use `:error="$errors->get('field')"`.
- Helper hint: gray 12px below input. Use `hint="..."`.
- Inputs: `form-control form-control-solid`. Solid (no border) on canvas, focus ring picks up primary.
- Required indicator: `<span class="text-danger">*</span>` after label text. Optional fields are unmarked.
- File upload: prefer `x-ui.file-upload`. Custom drop-zones must keep `data-ui-file-upload="metronic"`.

## Buttons

- **Primary** (`variant="primary"`): one per view. Used for the dominant action - "Simpan", "Kirim", "Setujui".
- **Secondary** (`variant="light"`): cancel/back, secondary commit.
- **Light-success / light-warning / light-danger**: contextual actions in tables (approve/reject/restore) - never solid danger except in destructive confirm dialogs.
- **Link** (`variant="link"`): inline nav inside long form.
- Sizes: `sm` (toolbar inside table), default (page CTA), `lg` (auth submit).
- Icon buttons: 36px square, icon center. Use `x-ui.icon-button`.

## Cards & Sections

- Default card: white surface, hairline border `1px #e8edf4`, radius 8px, no shadow on tile cards.
- Section card: same with a `spm-section-card-accent` 4px green vertical bar before title.
- Stat card: hover does not lift; just border deepens to primary. Reserve for KPI rows on the home dashboard.

## Tables

- Header row: `text-gray-500 fw-semibold gs-0` - 12px caption, all caps avoided (use sentence case).
- Body row: 14px, ink-mute, vertical padding 16px, divider `border-row-dashed` for low chrome.
- Empty state: `<x-ui.empty-state>` inside `<td colspan>` with icon, title, optional CTA.
- Pagination: Indonesian-styled, `livewire.datatable-pagination` view, "Menampilkan N sampai M dari T data" caption.
- Per-page selector at top-left, toolbar at top-right.

## Modals

- Sizes: sm/md/lg/xl/2xl. Default 2xl.
- Use `x-ui.modal-header` (with close button), `x-ui.modal-body` (scrolls), `x-ui.modal-footer` (right-aligned buttons, secondary first, primary last).
- Destructive confirm: red primary button, double-emphasis copy ("Tindakan ini tidak dapat dibatalkan.").

## Notifications & Toasts

- Inbox bell in header dropdown (`spm-notification-item`). Unread row tint `canvas-tinted`. Read row 0.72 opacity.
- Inline alert: use `notice bg-light-{variant} border-{variant} border-dashed` for empty-state warning blocks.
- Toast: dispatch via Livewire `notify(...)`. Pass plain text, not raw HTML. (See security audit M-5.)

## Accessibility

- Color contrast minimum 4.5:1 for body. Primary on white passes. Status soft tints fail body contrast - reserve them for badge backgrounds, never primary text.
- Focus visible: 2px ring `--spm-primary` with 2px offset. Never remove `:focus-visible`.
- Form errors announced via `aria-describedby`. `x-ui.form-field` already wires this.
- Keyboard: every action menu reachable via Tab. Modal traps focus (already handled by `x-ui.modal`).
- Honor `prefers-reduced-motion` - disable card hover lift and modal slide animations.

## Surface Examples (per role)

### Pesantren (operator)
- Profile: section cards (Identitas, Lokasi, Layanan, Dokumen, Visi/Misi). One CTA at top-right "Simpan".
- IPM/SDM: table-style input dengan sticky header tingkat. File upload card per dokumen.
- EDPM: stepper (1..N komponen), draft + final save split. Progress bar di header.

### Asesor
- Akreditasi list: filter by status, dense table. Badge per row. Action menu "Lihat", "Mulai Penilaian".
- Akreditasi detail: tab Profil / IPM / SDM / EDPM / Dokumen. EDPM evaluation = side-by-side, kiri butir + bukti, kanan input nilai + catatan.

### Admin
- Dashboard home: 4 stat-card baris atas (total pesantren, akreditasi pending, banding aktif, akreditasi bulan ini), bawah tabel pengajuan terbaru.
- Master data: table CRUD dengan toolbar `+ Tambah` di kanan atas. Modal form 2xl.
- Banding: list panel kiri + detail panel kanan saat dibuka.

## Anti-patterns (jangan)

- Jangan pakai gradient di card. Gradient hanya di `spm-auth-shell` (login).
- Jangan tumpuk shadow (card di dalam card).
- Jangan pakai border 2px - hairline 1px konsisten di seluruh aplikasi.
- Jangan pakai warna non-token. Pakai variabel CSS `--spm-*` atau Bootstrap `--bs-*`.
- Jangan emoji di label/title resmi.
- Jangan inline style. Selalu lewat class atau Metronic util (`d-flex`, `gap-*`, `text-gray-*`).
- Jangan native `<input>` kecuali kasus hybrid yang sudah dilegalkan di [`UI_GUIDELINES.md`](UI_GUIDELINES.md).

## How to extend

1. Component baru → buat di `resources/views/components/ui/<name>.blade.php`, ikuti `@props([...])` + `data-ui-<name>="metronic"`.
2. Token baru → tambahkan ke `resources/css/metronic-overrides.css` `:root` block, jangan inline di komponen.
3. Pattern baru → tulis di sini sebagai recipe + tambahkan smoke test di `tests/Feature/MetronicFrontendTest.php`.
4. Setiap perubahan visual: bandingkan side-by-side dengan halaman tetangga; konsistensi > kreativitas.

## Verifikasi

Setelah generate UI baru, jalankan:

```bash
php artisan test tests/Feature/MetronicFrontendTest.php
npm run build
```

Reference: `awesome-design-md` by VoltAgent — DESIGN.md serves as a context file AI agents can read to maintain UI consistency across many touch points.
