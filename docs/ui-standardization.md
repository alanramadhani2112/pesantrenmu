<!-- markdownlint-disable MD013 -->

# UI Standardization

Dokumen ini menjadi baseline UI Laravel Blade + Metronic untuk dashboard role `super admin`, `admin`, `pesantren`, dan `asesor`.

Reference theme wajib: `C:\laragon\www\dist\dist` (Metronic 8.1.8 demo42). Jika ada konflik antara gaya lokal dan theme reference, pilih markup/class dari theme reference lalu bungkus lewat komponen `x-ui.*`.

Untuk standar yang lebih ketat, clean-flat, reusable component contract, standar halaman, guardrail copy domain SPM, dan enforcement gate, gunakan `docs/ui-clean-metronic-development-plan.md` sebagai referensi lanjutan. Dokumen ini tetap baseline; dokumen clean Metronic menjadi aturan pengembangan berikutnya.

## Prinsip

- Pakai komponen `resources/views/components/ui/*` sebelum menulis markup Metronic mentah.
- Pertahankan token Metronic 8.1.8 dari `C:\laragon\www\dist\dist\assets` dan override yang sudah ada di `resources/css/metronic-overrides.css`.
- Standardisasi lewat komponen/presenter kecil. Hindari rebuild design system.
- Ikuti arah **SPM Clean Metronic** untuk pengembangan baru: clean, flat, minim ornamen, tetap domain SPM akreditasi pesantren.
- Raw Bootstrap/Metronic markup hanya boleh untuk kasus khusus yang belum punya komponen.

## Theme contract

- Shell mengikuti demo42: `kt_app_body`, `app-default`, fixed header/sidebar, `app-container`, `app-header`, `app-wrapper`, `app-main`, `app-content`.
- Asset order mengikuti theme: `plugins.bundle.css`, `style.bundle.css`, app override, lalu `plugins.bundle.js` sebelum app script.
- Page title/breadcrumb memakai pola theme: `page-title`, `page-heading`, `breadcrumb`, `breadcrumb-item`; komponen lokal boleh menambah class `spm-*` hanya sebagai adapter.
- Card/table/modal/badge harus memakai class Metronic dari theme reference; `spm-*` hanya tambahan, bukan pengganti class Metronic.

## Struktur halaman

| Tipe halaman | Komponen wajib | Catatan |
| --- | --- | --- |
| Dashboard/list/index | `x-ui.index-layout` | Gunakan slot `toolbar`, `filters`, dan `tableHeader` bila perlu. |
| Detail/form/wizard | `x-ui.page` | Judul dan subtitle masuk prop `title`/`subtitle`. |
| Header standalone legacy | `x-ui.page-header` | Hanya untuk transisi; jangan tambah pemakaian baru. |

Hindari pola baru dengan `@section('header')` atau `<x-slot name="header">` bila halaman sudah memakai `x-ui.page`/`x-ui.index-layout`.

## Status akreditasi

- Semua label, variant badge, dan tahap akreditasi harus lewat `App\Support\AkreditasiStatusPresenter`.
- Blade tidak boleh membuat `$statusVariantMap`, `$statusBadgeClass`, atau `$stageMap` lokal untuk akreditasi.
- Badge status akreditasi pakai `x-ui.status-badge`.

Contoh:

```blade
@php($status = \App\Support\AkreditasiStatusPresenter::for($akreditasi->status))

<x-ui.status-badge :variant="$status['variant']">
    {{ $status['label'] }}
</x-ui.status-badge>
```

## Tabel

- Tabel operasional pakai `x-ui.table`.
- Header pakai `x-ui.table-th`.
- Aksi baris pakai `x-ui.action-menu` dan `x-ui.action-menu-item`.
- Matrix/form table boleh pakai `x-ui.simple-table`.
- Default table mengikuti theme list page: `table align-middle table-row-dashed fs-6 gy-5`. Jangan pakai `table-striped` untuk list operasional baru.

## Modal

- Modal standar pakai `x-ui.modal`, `x-ui.modal-header`, `x-ui.modal-body`, `x-ui.modal-footer`.
- Tombol footer pakai `x-ui.button`.
- Raw `modal-header`, `modal-body`, `modal-footer` hanya untuk kasus yang belum bisa dipenuhi komponen.

## Empty state

- Empty state pakai `x-ui.empty-state`.
- Copy utama:
  - List kosong: `Belum ada data`
  - Filter kosong: `Data tidak ditemukan`
  - Tugas kosong: `Belum ada tugas`
- Tambahkan deskripsi spesifik role bila membantu aksi berikutnya.

## Batch refactor aman

Urutan aman:

1. Pindahkan mapping status akreditasi ke `AkreditasiStatusPresenter`.
2. Ubah badge status akreditasi menjadi `x-ui.status-badge`.
3. Ubah modal raw menjadi `x-ui.modal-*` tanpa mengubah Alpine state/action.
4. Ubah header list/detail ke `x-ui.index-layout`/`x-ui.page` bila tidak mengubah layout visual.

Jangan gabungkan refactor UI dengan perubahan workflow, query, permission, atau validasi bisnis.
