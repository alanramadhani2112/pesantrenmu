# UI Polish Dashboard Plan - 29 Juni 2026

## Status Dokumen

Dokumen ini **belum final**.

## Yang Perlu Difinalisasi

- [ ] Petakan halaman dashboard nyata per role.
- [ ] Isi checklist global dan per role dari screenshot/halaman nyata.
- [ ] Tetapkan `must-fix` vs `nice-to-have`.
- [ ] Tetapkan urutan halaman polish untuk batch execute.

## Tujuan

Dokumen ini fokus pada audit dan polish UI untuk semua dashboard role agar sistem terasa siap dipakai operasional, bukan hanya lolos backend test.

## Prinsip Audit UI

Audit melihat:

- visual hierarchy,
- spacing,
- CTA priority,
- consistency komponen,
- loading state,
- empty state,
- error state,
- table readability,
- form usability,
- status clarity.

## Dashboard yang Harus Dicek

- [ ] Super Admin dashboard
- [ ] Admin dashboard
- [ ] Asesor dashboard
- [ ] Pesantren dashboard
- [ ] Shared layout: sidebar, topbar, breadcrumb, tabs, cards, alerts, tables, forms

## Checklist Global

| Area | Check | Status | Notes |
| --- | --- | --- | --- |
| Navigation | Sidebar role konsisten dan tidak membingungkan | Pending |  |
| Context | Judul halaman, breadcrumb, subtitle jelas | Pending |  |
| CTA | Tombol primer/sekunder tidak tumpang tindih | Pending |  |
| Status badge | Warna dan label sesuai flow bisnis | Pending |  |
| Cards | Informasi penting tampil dulu | Pending |  |
| Tables | Header, filter, sort, action mudah dipahami | Pending |  |
| Forms | Label, helper, validation, action footer rapi | Pending |  |
| Empty states | Ada pesan dan CTA yang relevan | Pending |  |
| Loading states | Tidak terasa freeze / ambigu | Pending |  |
| Error states | Pesan error membantu user lanjut | Pending |  |
| Accessibility baseline | Kontras, focus state, target klik wajar | Pending |  |

## Checklist Per Role

### Super Admin

- [ ] Dashboard menunjukkan fungsi governance dengan jelas.
- [ ] Menu sistem tidak bercampur dengan task operasional harian.
- [ ] Role management, permission matrix, account management punya hierarchy jelas.
- [ ] Warning untuk aksi berbahaya terlihat kuat.

### Admin

- [ ] Dashboard menonjolkan antrian kerja utama.
- [ ] Halaman detail akreditasi mudah dibaca walau datanya padat.
- [ ] Area validasi NV dan keputusan akhir tidak membingungkan.
- [ ] CTA approve/reject/reassign/reschedule/finalize konsisten.
- [ ] Area upload SK dan dokumen punya feedback yang jelas.

### Asesor

- [ ] Penugasan aktif terlihat jelas.
- [ ] Jadwal visitasi, penilaian, dan upload laporan mudah diikuti.
- [ ] Perbedaan draft vs final jelas.
- [ ] Status progress penilaian tidak ambigu.

### Pesantren

- [ ] Dashboard menonjolkan langkah berikutnya yang harus dilakukan.
- [ ] Progress status mudah dipahami user non-teknis.
- [ ] Upload dokumen, hasil akhir, perbaikan, dan banding mudah ditemukan.
- [ ] Tidak menampilkan data internal yang membingungkan.

## Severity UI

### Must Fix

- CTA yang salah prioritas
- Status wording yang salah atau ambigu
- Halaman padat tanpa hierarchy
- Form penting tanpa validation feedback jelas
- Empty state yang membuat user buntu
- Action penting tersembunyi atau terlalu mirip dengan action sekunder

### Nice to Have

- Ikon lebih konsisten
- Density tabel lebih rapi
- Copywriting lebih pendek
- Skeleton/loading lebih halus

## Deliverable Audit UI

| Dokumen / Artefak | Isi |
| --- | --- |
| Screenshot audit per role | bukti before |
| Checklist pass/fail | hasil audit global + per role |
| Must-fix list | item yang wajib dibereskan sebelum scoring naik |
| Nice-to-have list | item polish minor |

## Action Plan

### Phase A - Inventory

- [ ] Petakan semua halaman dashboard dan detail page utama per role.
- [ ] Ambil screenshot dan daftar area prioritas.

### Phase B - Audit

- [ ] Isi checklist global.
- [ ] Isi checklist per role.
- [ ] Tandai `must-fix` vs `nice-to-have`.

### Phase C - Fix

- [ ] Kerjakan issues must-fix dari shared layout dulu.
- [ ] Lanjut ke halaman detail akreditasi admin.
- [ ] Lanjut ke dashboard asesor dan pesantren.
- [ ] Tutup polish governance pages super admin.

### Phase D - Recheck

- [ ] Review ulang seluruh dashboard setelah fix.
- [ ] Pastikan wording status sinkron dengan flow bisnis final.
