<!-- markdownlint-disable MD013 MD032 MD060 -->

# UI Visual Audit Findings

Tanggal audit: 2026-07-16.

Sumber audit:

- `docs/ui-dashboard-role-url-inventory.md`
- `docs/ui-page-inventory-audit-matrix.md`
- `routes/web.php`
- `app/Services/SidebarMenuService.php`
- grep Blade/CSS untuk pattern visual lama.

Scope: audit visual dan bug komponen UI. Tidak ada refactor kode aplikasi di dokumen ini.

## Ringkasan hasil

UI sudah jauh lebih rapi dibanding baseline, tapi belum konsisten penuh. Masalah terbesar sekarang bukan route/business logic, melainkan campuran pattern visual lama dan baru di beberapa family halaman.

Masalah utama:

1. Halaman detail/list inti sudah mulai seragam, tapi beberapa halaman profil/detail admin/asesor masih memakai layout lama (`p-6`, `row g-6`, raw `btn`, raw icon).
2. CSS override terlalu banyak layer dan beberapa role punya override khusus, sehingga perubahan Blade kadang tidak terlihat di browser.
3. Halaman panduan/public/auth belum ikut standar `x-ui.*` secara konsisten.
4. Beberapa partial scoring/dokumen masih memakai `bg-light-*`, raw button, atau casing uppercase.
5. Komponen tab sudah distandardkan, tapi masih ada risiko conflict karena CSS tab tersebar di beberapa file.

## Severity P0 — harus diprioritaskan

### 1. Asesor Profile masih memakai pattern visual lama

File:

- `resources/views/asesor/profile.blade.php`

Temuan:

- Raw button: `class="btn btn-primary"`, `class="btn btn-light"`, `btn-light-primary`.
- Banyak density lama: `mb-6`, `p-6`, `row g-6`, `gap-6`.
- Raw icons: `<i class="ki-solid ...">`.
- Copy/label masih uppercase di beberapa section.

Dampak:

- Profil asesor terlihat beda dari profile/form lain.
- Ini halaman role penting dan sering dilihat.

Fix pattern:

- Pakai `x-ui.button`, `x-ui.icon`, `x-ui.section-card`.
- Ganti density ke `mb-5`, `p-5`, `row g-5`, `gap-5`.
- Hapus uppercase dekoratif.

### 2. Admin/Pesantren detail entity masih beda grammar

Files:

- `resources/views/admin/pesantren/detail.blade.php`
- `resources/views/admin/asesor/detail.blade.php`

Temuan:

- `row g-6`, `gap-6`, `p-6`, `px-6`.
- Label uppercase.
- Struktur detail entity tidak sepenuhnya mengikuti `docs/ui-detail-page-standard.md`.

Dampak:

- Detail pesantren/asesor di admin terasa beda dari detail akreditasi.

Fix pattern:

- Terapkan standar detail: hero + summary + section card + neutral density.
- Gunakan `x-ui.detail-item` untuk metadata.

### 3. CSS override role-specific menyebabkan perubahan visual tidak terlihat

Files:

- `resources/css/metronic-overrides/85-pesantren-polish.css`
- `resources/css/metronic-overrides/80-production-polish.css`
- `resources/css/metronic-overrides/60-visual-normalization.css`
- `resources/css/metronic-overrides/30-detail-components.css`

Temuan:

- Banyak selector tab/card/detail tersebar dan saling override.
- Contoh terbaru: asesor detail tab sudah berubah di Blade, tapi tetap tampak beda karena CSS khusus asesor mengalahkan style global.
- Masih ada rule uppercase di CSS lama.

Dampak:

- UI sulit diprediksi.
- Refactor Blade tidak selalu terlihat di browser.

Fix pattern:

- Buat satu layer final untuk component canonical.
- Kurangi selector role-specific yang mengubah bentuk komponen global.
- Role-specific hanya boleh warna kecil/copy, bukan struktur visual.

## Severity P1 — penting setelah P0

### 4. Panduan pages belum ikut SPM Clean Metronic

Files:

- `resources/views/panduan/admin.blade.php`
- `resources/views/panduan/asesor.blade.php`
- `resources/views/panduan/pesantren.blade.php`
- `resources/views/panduan/superadmin.blade.php`

Temuan:

- `px-6`, `pb-6`, `mb-6`.
- Raw icons `<i class="ki-duotone ...">`.
- Pesantren panduan masih punya `bg-light-primary` dan card visual lebih ramai.

Dampak:

- Halaman panduan terasa belum satu sistem dengan dashboard internal.

Fix pattern:

- Gunakan section card dan icon component.
- Kurangi visual accent besar.
- Samakan spacing ke `p-5`, `mb-5`.

### 5. Public landing dan auth pages masih banyak raw Metronic markup

Files:

- `resources/views/welcome.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/reset-password.blade.php`
- `resources/views/auth/verify-email.blade.php`

Temuan:

- Raw `btn` dan raw icons.
- Auth form spacing masih `mb-6`.
- Landing masih punya banyak decorative icon blocks.

Dampak:

- First impression publik belum sebersih app shell.

Fix pattern:

- Standardisasi button/icon dengan `x-ui.*` jika komponen tersedia.
- Tetap jaga landing tidak terlalu “admin dashboard”.

### 6. Pesantren EDPM/IPM workflow masih ada raw icon/button kecil

Files:

- `resources/views/pesantren/edpm.blade.php`
- `resources/views/pesantren/ipm.blade.php`

Temuan:

- EDPM navigation buttons masih raw icons `<i class="ki-solid ...">`.
- Beberapa form workflow masih terasa custom.

Fix pattern:

- Ganti icon ke `x-ui.icon`.
- Pastikan tombol pakai `x-ui.button` dan hierarchy primary/secondary jelas.

### 7. Admin akreditasi scoring partial masih memakai visual lama

Files:

- `resources/views/admin/akreditasi/detail/tabs/instrumen/score-table.blade.php`
- `resources/views/admin/akreditasi/detail/tabs/instrumen/document-alert.blade.php`
- `resources/views/admin/akreditasi/detail/tabs/laporan-visitasi.blade.php`

Temuan:

- `bg-light-primary`, `bg-light-warning`, `bg-light-success`, `bg-light-danger`.
- Raw button pada filter audit-trail masih ada di `audit-trail.blade.php`.

Fix pattern:

- Pindah ke neutral `bg-body border border-dashed border-gray-300` untuk surface besar.
- Badge/status kecil tetap boleh pakai semantic color.

## Severity P2 — rapikan saat batch berikutnya

### 8. Tab component sudah membaik, tapi CSS masih berlapis

Files:

- `resources/views/components/ui/tabs.blade.php`
- `resources/views/components/ui/tab.blade.php`
- CSS tabs di `30`, `60`, `80`, `85` override files.

Status:

- Markup sudah satu komponen.
- Asesor detail sudah pakai URL tab.
- CSS asesor sudah dinetralisir.

Risiko:

- Jika halaman lain punya role-specific tab override, visual bisa kembali beda.

Fix pattern:

- Audit CSS tab selectors dan kurangi duplikasi.
- Pastikan style canonical ada di satu file/layer terakhir.

### 9. Empty state `py-6` masih banyak, tapi tidak selalu bug

Files contoh:

- `resources/views/pesantren/akreditasi-detail.blade.php`
- beberapa detail/admin empty states.

Catatan:

- `py-6` pada empty state bisa diterima jika memang butuh ruang kosong.
- Jangan refactor otomatis kecuali terlihat terlalu kosong di browser.

## Komponen yang perlu diaudit ulang

| Component | Status | Risiko | Aksi |
|---|---|---|---|
| `x-ui.tabs` / `x-ui.tab` | Sudah satu markup | CSS override masih tersebar | Rapikan CSS layer |
| `x-ui.card` / `x-ui.section-card` | Dipakai luas | Beberapa halaman masih raw card/density lama | Migrasi bertahap |
| `x-ui.button` | Ada dan stabil | Banyak page masih raw `btn` | Batch replace aman |
| `x-ui.icon` | Ada dan stabil | Raw `ki-*` masih banyak | Replace pada non-dynamic icon |
| `x-ui.document-item` | Dipakai di detail dokumen | Butuh grid rule konsisten lintas role | Audit admin/pesantren/asesor dokumen |
| `x-ui.table` | Relatif stabil | Some table partial scoring custom | Jangan paksa untuk scoring kompleks |
| `x-ui.badge` / `x-ui.status-badge` | Stabil | Semantic colors masih dipakai untuk surface besar | Bedakan badge kecil vs panel besar |

## Rekomendasi phase refactor berikutnya

### Phase A — Profile/detail entity cleanup

Target:

- `resources/views/asesor/profile.blade.php`
- `resources/views/admin/asesor/detail.blade.php`
- `resources/views/admin/pesantren/detail.blade.php`

Alasan:

- Drift paling besar dan terlihat.
- Banyak pattern lama jelas.

### Phase B — Panduan/support pages cleanup

Target:

- `resources/views/panduan/*.blade.php`
- `resources/views/components/panduan/layout.blade.php`

Alasan:

- Semua role akan buka panduan.
- Saat ini terasa belum satu sistem.

### Phase C — Public/auth polish

Target:

- `resources/views/welcome.blade.php`
- `resources/views/auth/*.blade.php`

Alasan:

- First impression.
- Banyak raw Metronic markup.

### Phase D — Workflow scoring partial polish

Target:

- Admin akreditasi detail nested instrumen partials.
- Pesantren EDPM/IPM workflow buttons.

Alasan:

- Kompleks, butuh hati-hati supaya tidak ganggu workflow scoring.

## Kesimpulan

Masih ada UI yang belum rapi. Bukan karena standar tidak ada, tapi karena coverage belum tuntas dan CSS override lama masih hidup di beberapa area.

Prioritas terbaik berikutnya: mulai dari `asesor/profile.blade.php`, lalu detail entity admin (`admin/asesor/detail`, `admin/pesantren/detail`).
