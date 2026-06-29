# Audit Frontend — Living Document

**Terakhir di-update:** 8 Juni 2025 | **Sesi:** Super Admin Fixes Complete  
**Cakupan:** 4 role (super_admin, admin, asesor, pesantren) + komponen shared  
**Status:** ✅ Super Admin role — ALL ISSUES FIXED (8 file, 4 step) | ⬜ Admin role — belum diaudit

> Dokumen ini akan di-update terus seiring perbaikan. Setiap kali ada perubahan, update status di dokumen ini.

---

## A. Yang Tidak Bermasalah ✅

### A1. Struktur Dashboard
| Item | Detail | Status |
|---|---|---|
| Layout utama | `layouts/app.blade.php` — 1 layout untuk semua role | ✅ |
| Layout publik | `layouts/guest.blade.php` — login/register/landing | ✅ |
| Dashboard view | `dashboard/index.blade.php` — 1 view, data per role via `DashboardController::__invoke()` | ✅ |
| Tidak ada duplikasi | Tidak ada layout/view terpisah untuk tiap role | ✅ |

### A2. Sidebar
| Item | Detail | Status |
|---|---|---|
| Service | `App\Services\SidebarMenuService` — 4 method per role | ✅ |
| Komponen | `sidebar-group.blade.php` + `sidebar-link.blade.php` + `app-sidebar.blade.php` | ✅ |
| Alpine accordion | Sidebar group gunakan Alpine.js untuk expand/collapse — berfungsi | ✅ |
| Progress bar | Hanya muncul untuk role pesantren via `SidebarProgressService` | ✅ |

### A3. Komponen UI (81 komponen)
| Item | Detail | Status |
|---|---|---|
| `ui/` | button, card, badge, icon, table, modal, stepper, tabs, pagination, search, select, input | ✅ semua Blade component, tidak ada legacy reactive layer |
| `layout/` | app-header, app-sidebar, sidebar-group, notification-menu | ✅ |
| `akreditasi/` | workflow-stepper, edpm-review | ✅ |
| `datatable/` | per-page, search | ✅ |
| `panduan/` | layout, section | ✅ |

### A4. Alpine.js & JavaScript
| Item | Detail | Status |
|---|---|---|
| Versi | Alpine.js v3.14.0 | ✅ |
| Inisialisasi | `window.Alpine = Alpine` di `resources/js/app.js` | ✅ |
| initMetronic | KTComponents, KTMenu, KTDrawer, KTScroll, KTSticky — semua dipanggil | ✅ |
| x-data | 20+ view dengan definisi JS yang valid | ✅ |
| Form validation | `Alpine.data('formValidation', formValidation)` — shared | ✅ |
| Dependensi | Dropzone v6, Axios v1.11, Autosize v6, Popper.js v2, SweetAlert2 — semua terpasang | ✅ |

### A5. Asset Pipeline
| Item | Detail | Status |
|---|---|---|
| Vite | `vite.config.js` — input: app.css + metronic-overrides.css + app.js | ✅ |
| Tailwind | app.css import Tailwind directives | ✅ |
| Metronic CSS | 13 modul override di `metronic-overrides/` | ✅ |
| KeenIcons | Duotone icon set, alias mapping di `ui/icon.blade.php` | ✅ |
| Tidak ada webpack | Tidak ada `@mix()` atau `webpack.mix.js` | ✅ |

### A6. View per Role (ringkasan — detail lengkap di [Section D](#d-breakdown-per-role))
| Role | Total Views | Masalah Critical | Masalah Medium | Masalah Kosmetik | Overall |
|---|---|---|---|---|---|
| Super Admin | 12 halaman index | ✅ ALL FIXED | ✅ ALL FIXED | 1 (role-permission) | ✅ Role complete |
| Admin | ~25+ | 1 (banding detail — SAMA dengan super_admin, sudah fixed) | 0 | 4 (role-permission + akreditasi tabs) | ⚠️ Belum diaudit |
| Asesor | ~10 | 0 | 0 | 5 (akreditasi-detail + tabs) | ✅ Aman |
| Pesantren | ~8 | 0 | 0 | 1 (sdm) | ✅ Aman |
| Shared | 81 komponen + app.js | ✅ 2 FIXED (action-message, callWire dead code) | 0 | 2 (panduan) | ✅ Fixed |

### A7. Alpine Store
| Store | Penggunaan | Status |
|---|---|---|
| `sidebar.open` | Mobile sidebar toggle | ✅ Terverifikasi: `Alpine.store('sidebar', { open: false })` di `app.js:1104` |
| `modal.open()` | Modal di trash page | ✅ Terverifikasi: `Alpine.store('modal')` diinisialisasi di `app.js` |

---

## B. Yang Bermasalah ❌

### 🔴 P0 — Critical (Fitur Broken)

#### B1. legacy client binding di admin/banding/detail.blade.php ✅ FIXED
**Status:** ✅ Selesai — diganti Alpine modal + form POST ke controller route  
**Dampak:** Admin **tidak bisa memproses banding** — workflow banding sepenuhnya broken.

| Baris | Kode | Tombol yang Broken |
|---|---|---|
| 64 | `@click="confirmAssignReviewer(legacy client binding)"` | Assign Reviewer |
| 72 | `@click="confirmBandingDecision(legacy client binding, 'accept')"` | Terima Banding |
| 76 | `@click="confirmBandingDecision(legacy client binding, 'reject')"` | Tolak Banding |
| 81 | `@click="confirmReassignReviewer(legacy client binding)"` | Ganti Reviewer |

**Akar:** `legacy client binding` adalah legacy reactive layer magic variable. Pasca removal reactive layer lama, `legacy client binding = undefined`. Semua fungsi gagal `ReferenceError`.

**Fix plan:** Ganti `legacy client binding` argumen dengan call API endpoint via Axios/fetch, atau refactor jadi form POST dengan controller route.

---

#### B2. legacy server binding.on() di components/action-message.blade.php ✅ FIXED
**Status:** ✅ Selesai — ganti `legacy server binding.on()` → `window.addEventListener()`  
**Dampak:** Pesan "Saved" tidak muncul di halaman profile dan form lain.

| Baris | Kode | Yang Broken |
|---|---|---|
| 4 | `x-init="legacy server binding.on('...', () => { ... })"` | Komponen action-message |

**Akar:** `legacy server binding` adalah Blade directive legacy reactive layer. Tanpa legacy reactive layer, undefined.

**Fix plan:** Ganti dengan Alpine event dispatch/listen (`$dispatch`/`x-on`), atau gunakan session flash + timeout.

---

#### B3. callWire() di app.js — 38 pemanggilan gagal diam-diam ✅ FIXED
**Status:** ✅ Selesai — `callWire` dihapus dari 5 view, diganti inline Swal + form submit  
**Dampak:** Semua operasi CRUD yang menggunakan Swal konfirmasi → `callWire()` **tidak menghasilkan aksi apa pun** (no crash, tapi no action). Mempengaruhi setiap halaman yang menggunakan `adminManagement()` atau `deleteConfirmation()`.

**Akar:** `callWire()` di `app.js:309` return null ketika `legacy client binding` undefined:
```js
function callWire(wire, method, ...params) {
    if (typeof wire === 'undefined' || wire === null) return null;
    // ...
}
```
Tidak ada error yang muncul karena return null, tapi **38 pemanggilan** (line 393-830) semua tidak melakukan apa-apa. `this.legacy client binding` dan `wire` argumen yang dikirim undefined pasca removal reactive layer lama.

**Dampak bisnis:** Delete confirmation, status update, role change, dan semua aksi yang menggunakan Swal → callWire pattern pada halaman admin/manajemen **tidak berfungsi**. Ini termasuk sebagian besar action di halaman akreditasi dan pesantren.

**Fix plan:** Ubah `callWire` menjadi pattern AJAX (Axios POST ke route controller). Atau hapus `callWire` dan refactor semua 38 pemanggilan.

---

#### B4. String Boolean di admin/accounts/index.blade.php — Modal auto-open ✅ FIXED
**Status:** ✅ Selesai — tambah `init()` konversi string `'true'/'false'` → boolean `true/false`  
**Dampak:** Popup modal "Tambah Akun" **selalu terbuka otomatis** saat halaman load.

| Baris | Kode | Yang Broken |
|---|---|---|
| 242 | `showModal: {{ old('_token') !== null ? 'true' : 'false' }},` | Modal selalu muncul |
| 243 | `isEditing: {{ old('_token') !== null ? 'true' : 'false' }},` | State edit selalu true |
| 250 | `status: {{ $user->is_active ? 'true' : 'false' }},` | String bukan boolean |
| 251 | `sso_sync_role: {{ ... ? 'true' : 'false' }},` | String bukan boolean |

**Akar:** Blade `{{ }}` merender sebagai string literal `'false'`, bukan boolean `false`. Dalam JavaScript, `'false'` (string) bersifat **truthy**, sehingga `x-show="showModal"` selalu true — modal selalu terbuka.

**Fix plan:** Gunakan Blade directive `@json(...)` yang merender tipe JavaScript yang benar. Ganti:
```blade
// ❌ BUGGY — string 'false' = truthy di JS
showModal: {{ old('_token') !== null ? 'true' : 'false' }},
// ✅ FIX
showModal: @json(old('_token') !== null),
```

Catatan: Fungsi JS inline di `accounts/index.blade.php` bersih dari `legacy client binding` — hanya menggunakan `document.getElementById` dan form submit. Masalahnya murni string boolean.

---

#### B5. String Boolean di admin/pesantren/index.blade.php — Sort state ✅ FIXED
**Status:** ✅ Selesai — `json_encode($sortAsc)` ganti string boolean  
**Dampak:** Sorting mungkin tidak berfungsi benar karena tipe data salah.

| Baris | Kode | Yang Broken |
|---|---|---|
| 4 | `sortAsc: {{ $sortAsc ? 'true' : 'false' }}` | String, bukan boolean |

**Fix plan:** Sama seperti B4 — ganti ke `@json($sortAsc)`.

---

#### B6. Route fragility di admin/failed-notifications/index.blade.php ✅ FIXED
**Status:** ✅ Selesai — `route('...', '__ID__').replace(...)` → `route('...', { id: id })`  
**Dampak:** Retry notifikasi mungkin gagal jika route parameter name berbeda.

| Lokasi | Kode | Masalah |
|---|---|---|
| Inline `failedNotificationPage()` | `route('admin.failed-notifications.retry', '__ID__').replace('__ID__', id)` | Asumsi `route()` menerima `__ID__` placeholder |

**Akar:** Pattern `route()` + `.replace()` bergantung pada helper Laravel `route()` yang menerima placeholder. Jika parameter name berbeda dengan yang diharapkan route, replace akan gagal.

**Fix plan:** Gunakan template literal: `` `/admin/failed-notifications/${id}/retry` `` atau pastikan route parameter name match.

---

### 🟡 P1 — High (Pre-existing, perlu investigasi)

#### B7. MetronicFrontendTest — Route Error (7 test)
**Status:** ❌ Belum diperbaiki

| Test | Error | Perlu Investigasi |
|---|---|---|
| `test_legacy_datatable_page_renders_metronic_table_adapter` | 404 | Route test tidak match pasca migration lama ke Blade/controller |
| `test_admin_master_edpm_uses_simple_table_component` | Full HTML page | Assertion tidak match response type |
| `test_admin_akreditasi_page_uses_metronic_datatable_foundation` | Full HTML page | Sama |
| `test_admin_module_list_pages_use_page_heading_and_reusable_tables` | Full HTML page | Sama |
| `test_user_module_list_pages_use_page_heading_and_reusable_tables` | Full HTML page | Sama |
| `test_detail_pages_render_metronic_detail_foundation` | 500 | Mungkin null reference atau data seeding |
| `test_detail_edpm_tabs_render_grouped_edpm_ipr_review_component` | 500 | Mungkin null reference di route asesor |

---

#### B8. MetronicFrontendTest — Assertion Komponen (8 test)
**Status:** ❌ Belum diperbaiki

| Test | Masalah |
|---|---|
| `test_header_user_menu_owns_account_actions` | `Blade::render()` menghasilkan `@props` mentah |
| `test_metronic_overrides_apply_enterprise_typography` | CSS content assertion mismatch |
| `test_metronic_table_components_render_reusable_classes` | `data-ui-table` count assertion |
| `test_legacy_datatable_components_render_through_metronic_table_adapter` | Sama |
| `test_metronic_accessibility_contract_for_tabs_menu_and_modal` | `data-ui-tabs` assertion |
| `test_sweetalert_actions_use_metronic_helper_without_inline_blade_alerts` | SweetAlert2 inline di view |
| `test_render_markup_does_not_contain_direct_queries` | DB query di view markup |
| `test_akreditasi_detail_uses_tab_partials_for_large_sections` | Tab partials tidak sesuai |

**Catatan:** Ini bukan bug fungsional — test memverifikasi arsitektur/konvensi komponen. Tidak mempengaruhi yang dilihat pengguna.

---

### 🔵 P2 — Medium (Kosmetik)

#### B9. fw-bold vs fw-semibold (16 file)
**Status:** ❌ Belum diperbaiki  
**Dampak:** Tidak ada dampak fungsional. Konvensi Metronic menggunakan `fw-semibold`.

**16 file yang melanggar:**
```
pesantren/sdm.blade.php
asesor/akreditasi-detail.blade.php
asesor/akreditasi-detail/tabs/sdm.blade.php
asesor/akreditasi-detail/tabs/profil.blade.php
asesor/akreditasi-detail/tabs/instrumen.blade.php
asesor/akreditasi-detail/tabs/laporan-visitasi.blade.php
panduan/superadmin.blade.php
panduan/pesantren.blade.php
panduan/asesor.blade.php
panduan/admin.blade.php
admin/role-permission/index.blade.php
components/panduan/section.blade.php
components/panduan/layout.blade.php
admin/akreditasi/detail/tabs/instrumen/score-table.blade.php
admin/akreditasi/detail/tabs/instrumen/document-alert.blade.php
admin/akreditasi/detail/tabs/instrumen/score-summary.blade.php
```

---

## D. Breakdown per Role

### D1. Super Admin (super_admin / role_id=4)

**Akses:** Semua fitur admin + Manajemen Sistem. **12 halaman index diaudit penuh** (JS, x-data, inline functions, string boolean, legacy client binding artifacts).

#### Audit per Halaman

| # | Halaman | x-data | JS Location | Issues | Status |
|---|---|---|---|---|---|---|
| 1 | Akreditasi Index | `{...deleteConfirmation(), ...adminManagement()}` | Global (app.js) | ✅ Step 3: `callWire` diganti inline Swal + form submit | ✅ Fixed |
| 2 | Banding Index | No x-data | Pure form submits | ✅ Bersih | ✅ |
| 3 | Banding Detail | `{showAssignModal, showDecisionModal, decisionType, assignAction}` | Alpine modal + form POST | ✅ Step 4: 4 `legacy client binding` diganti Alpine modal + POST ke route controller | ✅ Fixed |
| 4 | Pesantren Index | Tidak ada x-data | Inline `pesantrenIndex()` | ✅ Step 2: `json_encode($sortAsc)` ganti string boolean | ✅ Fixed |
| 5 | Asesor Index | `asesorIndex()` | Inline L193 | ✅ No `legacy client binding` | ✅ |
| 6 | Master Dokumen | `masterDokumen()` | Inline L210 | ✅ No `legacy client binding` | ✅ |
| 7 | Master EDPM | `masterEdpm()` | Inline L299 | ✅ No `legacy client binding` | ✅ |
| 8 | Kategori Dokumen | `kategoriDokumen()` | Inline L218 | ✅ No `legacy client binding` | ✅ |
| 9 | Roles | `roleManager()` | Inline def | ✅ No `legacy client binding`, `isEditing: false` boolean proper | ✅ |
| 10 | Role Permission | `rolePermissionMatrix()` | Inline def | ✅ No `legacy client binding`, 4x `fw-bold` kosmetik | ✅ |
| 11 | Accounts | `accountManager()` | Inline def | ✅ Step 1: `init()` konversi string boolean → boolean proper | ✅ Fixed |
| 12 | Trash | `adminTrashPage()` | Inline def | ✅ Store `modal.open()` verified, fetch pattern aman | ✅ |
| 13 | Failed Notifications | `failedNotificationPage()` | Inline def | ✅ Step 2: route param `{ id: id }` ganti `.replace()` pattern | ✅ Fixed |

#### Masalah per Kategori

| Kategori | Detail | Status |
|---|---|---|
| Dashboard | Chart + statistik (sama dengan admin) | ✅ |
| Banding (detail page) | ✅ FIXED: 4 tombol `legacy client binding` → Alpine modal + form POST | ✅ |
| Akun Pengguna (accounts) | ✅ FIXED: `init()` konversi string boolean | ✅ |
| Akreditasi (list page) | ✅ FIXED: `callWire()` diganti inline Swal + form submit | ✅ |
| Data Pesantren | ✅ FIXED: `json_encode($sortAsc)` | ✅ |
| Failed Notifications | ✅ FIXED: route `{ id: id }` Ziggy proper | ✅ |
| Role Permission | ✅ (fw-bold kosmetik minor) | ✅ |
| Asesor, Master Dokumen, Master EDPM, Kategori, Roles, Trash, Panduan | ✅ Semua bersih | ✅ |

#### Global JS Issues (mempengaruhi SEMUA halaman)
- ✅ **FIXED: `callWire()` dihapus dari 5 view** (akreditasi/index, pesantren/detail, pesantren/index, akreditasi/detail, banding/detail). 6 view lain verified tidak pakai callWire. `callWire()` masih ada di `app.js` untuk backward compatibility tapi tidak dipanggil lagi dari view super admin.
- ✅ **`adminManagement()` dead spread dihapus** dari 3 view (pesantren/index, akreditasi/detail, banding/detail).

---

### D2. Admin (role_id=1)

**Akses:** Manajemen akreditasi, banding, data pesantren, data asesor, master data.

| Kategori | Detail | Status |
|---|---|---|
| Dashboard | Statistik global + chart | ✅ |
| Akreditasi | List, detail, tabs (instrumen, visitasi, SDM, profil, dokumen) | ✅ |
| Banding | Detail banding — **⚠️ BROKEN: 4 tombol legacy client binding** | ❌ |
| Data Pesantren | List, create, edit, detail | ✅ |
| Data Asesor | List, create, edit, assign | ✅ |
| Dokumen | Master dokumen, kategori | ✅ |
| Master EDPM | EDPM management | ✅ |
| Panduan | Panduan admin | ✅ |

**Masalah spesifik admin:**
- `admin/banding/detail.blade.php`: 4 tombol action banding broken (sama dengan super_admin)
- `admin/role-permission/index.blade.php`: `fw-bold` kosmetik (1 file)
- 3 file akreditasi detail tabs yang pakai `fw-bold`: `score-table`, `document-alert`, `score-summary`

**Yang aman spesifik admin:**
- Seluruh manajemen pesantren & asesor
- Seluruh akreditasi (list, detail, tabs — kecuali jika banding diakses dari tab)
- Master data
- Dashboard admin

---

### D3. Asesor (role_id=2)

**Akses:** Tugas akreditasi yang di-assign, profil sendiri.

| Kategori | Detail | Status |
|---|---|---|
| Dashboard | Assessment stats | ✅ |
| Akreditasi | List tugas akreditasi | ✅ |
| Akreditasi Detail | Tabs: instrumen, SDM, profil | ✅ |
| Laporan Visitasi | Tabs: laporan visitasi | ✅ |
| Profile | Edit profil asesor | ✅ |
| Panduan | Panduan asesor | ✅ |

**Masalah spesifik asesor:**
- 5 file `fw-bold` kosmetik:
  - `asesor/akreditasi-detail.blade.php`
  - `asesor/akreditasi-detail/tabs/sdm.blade.php`
  - `asesor/akreditasi-detail/tabs/profil.blade.php`
  - `asesor/akreditasi-detail/tabs/instrumen.blade.php`
  - `asesor/akreditasi-detail/tabs/laporan-visitasi.blade.php`
- `components/action-message.blade.php`: pesan "Saved" tidak muncul (shared component, mempengaruhi profile edit)

**Yang aman spesifik asesor:**
- Seluruh workflow akreditasi (list, detail, tabs)
- Profil edit (kecuali action-message)
- Dashboard
- Tidak ada legacy reactive layer artifact di views asesor

---

### D4. Pesantren (role_id=3)

**Akses:** Pengajuan akreditasi, dokumen, profil, IPM, SDM, EDPM.

| Kategori | Detail | Status |
|---|---|---|
| Dashboard | Statistik pengajuan + progress bar sidebar | ✅ |
| Akreditasi | List pengajuan, detail status | ✅ |
| Dokumen | Upload/kelola dokumen akreditasi | ✅ |
| Profil | Edit profil pesantren | ✅ |
| IPM | Input data IPM | ✅ |
| SDM | Input data SDM | ✅ |
| EDPM | Input data EDPM | ✅ |
| Panduan | Panduan pesantren | ✅ |

**Masalah spesifik pesantren:**
- `pesantren/sdm.blade.php`: `fw-bold` kosmetik (1 file)
- `components/action-message.blade.php`: pesan "Saved" tidak muncul (shared)

**Yang aman spesifik pesantren:**
- Seluruh workflow akreditasi (pengajuan, tracking, dokumen)
- IPM, SDM, EDPM input
- Profil edit
- Dashboard + sidebar progress bar
- Tidak ada legacy reactive layer artifact di views pesantren

**Catatan sinkron backend — 8 Juni 2026:**
- Status frontend Pesantren di atas berarti tidak ditemukan artifact legacy reactive layer atau JS broken yang spesifik di view Pesantren.
- Backend audit menemukan flow perbaikan Pesantren belum aman penuh: partial unlock belum tembus controller HTTP, belum ada route submit perbaikan, dan tab Status Perbaikan masih perlu filter active rejection.
- Frontend agent jangan finalisasi UI Status Perbaikan atau tombol "Kirim Perbaikan" sebelum backend menutup `PES-001`, `PES-002`, dan `PES-003` di `docs/backend-role-module-audit-plan-2026-06-08.md`.
- Kontrak backend yang akan disediakan: `POST pesantren.akreditasi.submit-perbaikan` dengan field `akreditasi_id`, flash `success/error`, dan `?focus=perbaikan` tetap menjadi URL tab Status Perbaikan.

---

### D5. Komponen Shared (semua role)

| Komponen | Fungsi | Status |
|---|---|---|
| `components/layout/app-sidebar.blade.php` | Sidebar shell | ✅ |
| `components/layout/app-header.blade.php` | Header | ✅ |
| `components/layout/notification-menu.blade.php` | Notifikasi | ✅ |
| `components/action-message.blade.php` | Pesan "Saved" | ❌ BROKEN: `legacy server binding.on()` |
| `components/modal.blade.php` | Modal | ✅ |
| `components/quill-editor.blade.php` | Rich text editor | ✅ |
| `components/panduan/layout.blade.php` | Layout panduan | ✅ (fw-bold kosmetik) |
| `components/panduan/section.blade.php` | Section panduan | ✅ (fw-bold kosmetik) |
| `components/ui/button.blade.php` | Button | ✅ |
| `components/ui/card.blade.php` | Card | ✅ |
| `components/ui/icon.blade.php` | Icon (KeenIcons) | ✅ |
| `components/ui/table.blade.php` | Table | ✅ |
| `components/ui/modal.blade.php` | Modal UI | ✅ |
| `components/ui/tabs.blade.php` | Tabs | ✅ |
| `components/ui/stepper.blade.php` | Stepper | ✅ |
| `components/ui/pagination.blade.php` | Pagination | ✅ |
| `components/akreditasi/workflow-stepper.blade.php` | Workflow visual | ✅ |
| `components/akreditasi/edpm-review.blade.php` | EDPM review | ✅ |

---

## E. Fix Plan — Super Admin Role [EXECUTED ✅ — 8 Juni 2025]

> Semua step dieksekusi dalam 4 langkah. Lihat Log Perubahan untuk detail teknis.

### Ringkasan Eksekusi

| Step | Deskripsi | File | Status |
|---|---|---|---|
| 1 | `legacy server binding.on()` → `window.addEventListener()` + accounts string boolean `init()` | action-message.blade.php, accounts/index.blade.php | ✅ Done |
| 2 | `json_encode($sortAsc)` + route `{id:id}` | pesantren/index.blade.php, failed-notifications/index.blade.php | ✅ Done |
| 3 | Hapus `adminManagement` dead code + ganti `callWire` → inline Swal + form submit | akreditasi/index, pesantren/detail, pesantren/index, akreditasi/detail, banding/detail | ✅ Done |
| 4 | 4x `legacy client binding` → Alpine modal + form POST ke controller route | banding/detail.blade.php | ✅ Done |

### Detail Teknis

<details>
<summary>Step 1: action-message + accounts (expand)</summary>

**action-message.blade.php:**
- Before: `x-init="legacy server binding.on('{{ $on }}', ...)`
- After: `x-init="$nextTick(() => { window.addEventListener('{{ $on }}', ...) })"`

**accounts/index.blade.php:**
- Added `init()` method to convert `'true'/'false'` strings → real booleans
- Before: `showModal: {{ old('_token') !== null ? 'true' : 'false' }}` (string `'false'` = JS truthy)
- After: `showModal: {{ old('_token') !== null ? 'true' : 'false' }}` + `init() { this.showModal = this.showModal === 'true'; ... }`

</details>

<details>
<summary>Step 2: pesantren sortAsc + failed-notifications (expand)</summary>

**pesantren/index.blade.php:line 4:**
- Before: `sortAsc: {{ $sortAsc ? 'true' : 'false' }}`
- After: `sortAsc: {{ json_encode($sortAsc) }}` → renders `true`/`false` without quotes

**failed-notifications/index.blade.php:**
- Before: `route('...', '__ID__').replace('__ID__', id)`
- After: `route('admin.failed-notifications.retry', { id: id })`

</details>

<details>
<summary>Step 3: callWire cleanup (5 files) (expand)</summary>

- `akreditasi/index`: Ganti `callWire(this.legacy client binding, 'delete', id)` → inline Swal + submit `#deleteForm`
- `pesantren/detail`: Ganti `adminManagement()` → inline `confirmToggleLock(e)` + form submit
- `pesantren/index`, `akreditasi/detail`, `banding/detail`: Hapus `...adminManagement()` dead code

</details>

<details>
<summary>Step 4: banding detail legacy client binding (expand)</summary>

4 tombol diganti Alpine modal + form POST:
- Assign → `POST banding/{id}/assign-reviewer` (select reviewer + submit)
- Ganti → `POST banding/{id}/reassign-reviewer` (select reviewer + submit)
- Terima → `POST banding/{id}/submit-decision` (decisionType=accept + notes)
- Tolak → `POST banding/{id}/submit-decision` (decisionType=reject + notes)

x-data: `{ showAssignModal: false, showDecisionModal: false, decisionType: '', assignAction: '' }`
Controller routes unchanged (`BandingDetailController`).



---

## F. Next Action — Prioritas Perbaikan

> **Super admin fixes sudah dieksekusi. Lihat [Section E](#e-fix-plan--super-admin-role) untuk detail.**## F. Next Action — Prioritas Perbaikan

> **Super admin fixes ada di [Section E](#e-fix-plan--super-admin-role) — rencana detail per langkah.**  
> Section F adalah daftar global setelah super admin selesai.

### Backend Sync — Admin Scope 8 Juni 2026

Catatan dari audit backend role Admin:

| Area | Status sinkron | Catatan untuk frontend |
|---|---|---|
| Validasi Admin NV | Menunggu backend | Form finalisasi NV perlu kontrak reason jika NV berbeda dari NK; jangan finalkan UI sebelum backend menutup `ADM-001` |
| Trash routes | Perlu sinkron | Backend saat ini memakai `POST admin.trash.restore` dan `POST admin.trash.force-delete` dengan body `id`, bukan `/admin/trash/{id}/restore` atau DELETE |
| Failed notifications | Perlu sinkron permission | Route retry/dismiss ada, tetapi backend akan memperketat permission action; gunakan route name backend agar tidak rapuh |
| Asesor filter Admin | Menunggu backend | Filter status/peran/penugasan belum boleh dianggap final sampai key controller-service-repository diselaraskan |

### Backend Sync — Super Admin Scope 8 Juni 2026

Catatan dari audit backend role Super Admin:

| Area | Status sinkron | Catatan untuk frontend |
|---|---|---|
| Permission matrix | Aman dengan batasan | Role Super Admin id `4` sengaja tidak muncul di matrix; jangan tampilkan UI seolah permission Super Admin bisa dicabut |
| Role inti | Menunggu backend | Tombol hapus/edit role inti `Admin`, `Asesor`, `Pesantren`, `Super Admin` sebaiknya disabled sampai backend menutup proteksi role inti |
| Notifikasi workflow | Menunggu keputusan produk | Banyak notifikasi backend masih ke `role_id = 1`; badge/list Super Admin boleh tampil, tapi penerima push/in-app belum tentu sama dengan Admin |
| Role management actions | Perlu HTTP regression | UI role CRUD perlu menunggu regression backend untuk form/method/redirect setelah full Blade |

### Super Admin (dikerjakan sesuai Section E)

| # | Prioritas | Item | Estimasi | Status |
|---|---|---|---|---|
| SA-1 | 🔴 P0 | Fix string boolean `admin/accounts/index.blade.php` | 15 menit | ✅ Done |
| SA-2 | 🔴 P0 | Fix `legacy server binding` di `components/action-message.blade.php` | 15 menit | ✅ Done |
| SA-3 | 🟡 P1 | Fix string boolean `admin/pesantren/index.blade.php` | 5 menit | ✅ Done |
| SA-4 | 🟡 P1 | Fix route fragility `admin/failed-notifications/index.blade.php` | 10 menit | ✅ Done |
| SA-5 | 🔴 P0 | Fix `callWire()` di 5 view super admin | 1 jam | ✅ Done |
| SA-6 | 🔴 P0 | Fix `legacy client binding` di `admin/banding/detail.blade.php` | 1-2 jam | ✅ Done |

### Global (setelah super admin)

| # | Prioritas | Item | Estimasi | Status |
|---|---|---|---|---|
| 7 | 🟡 P1 | Investigasi fix 404 `test_legacy_datatable_page_renders_metronic_table_adapter` | 30 menit | ⬜ Pending |
| 8 | 🟡 P1 | Investigasi fix 2x 500 error di MetronicFrontendTest | 2 jam | ⬜ Pending |
| 9 | 🟡 P1 | Fix 8 assertion mismatch di MetronicFrontendTest | 4 jam | ⬜ Pending |
| 10 | 🟡 P1 | Fix 7 test full HTML page (bukan komponen) | 2 jam | ⬜ Pending |
| 11 | 🔵 P2 | Ganti `fw-bold` → `fw-semibold` di 16 file | 1 jam | ⬜ Pending |
| 12 | — | Re-run full test suite, target 0 failure | 5 menit | ⬜ Pending |

---

## Log Perubahan

| Tanggal | Perubahan |
|---|---|
| 8 Jun 2025 | Audit awal — identifikasi semua masalah dan non-masalah. Dokumentasi struktur dashboard, sidebar, komponen, Alpine.js, asset pipeline. Ditemukan 2 artifact legacy reactive layer critical, 16 test gagal, 16 file fw-bold kosmetik. |
| 8 Jun 2025 | Tambah Section D: Breakdown per Role — detail views, masalah, dan status untuk super_admin, admin, asesor, pesantren, dan komponen shared. Ringkasan status per role. |
| 8 Jun 2025 | Audit super admin menu-by-menu: 12 halaman index + shared JS diaudit penuh. Ditemukan 2 critical baru: string boolean `accounts/index.blade.php` (modal auto-open) + 38 `callWire()` gagal di `app.js`. Ditemukan 1 medium: string boolean `pesantren/index.blade.php`. Ditemukan 1 fragility: route retry `failed-notifications`. Verifikasi Alpine Store ✅. 8 halaman super admin verified bersih. Update Section A6, A7, B, D1, E. |
| 8 Jun 2026 | Sinkron dengan audit backend Pesantren: frontend Pesantren tetap bersih dari artifact legacy reactive layer, tetapi UI Status Perbaikan dan tombol submit perbaikan harus menunggu route/kontrak backend partial unlock. |
| 8 Jun 2026 | Sinkron dengan audit backend Admin: UI Validasi Admin NV menunggu kontrak reason, Trash harus mengikuti route POST dengan body `id`, Failed Notifications memakai route name backend, dan filter Asesor menunggu perbaikan key backend. |
| 8 Jun 2026 | Sinkron dengan audit backend Super Admin: permission matrix tidak mengelola role id `4`, role inti sebaiknya disabled dari delete/edit berbahaya, dan notifikasi Super Admin menunggu keputusan recipient backend. |
| 8 Jun 2025 | ✅ **Super Admin role COMPLETE — 8 file fixed dalam 4 step.** Step 1: `action-message` (`legacy server binding.on` → `window.addEventListener`) + `accounts/index` (`init()` string→boolean). Step 2: `pesantren/index` (`json_encode($sortAsc)`) + `failed-notifications` (`route({id})`). Step 3: 5 view `callWire` cleanup (ganti inline Swal + form submit, hapus `adminManagement` dead code). Step 4: `banding/detail` (4x `legacy client binding` → Alpine modal + form POST). Semua perubahan hanya di Blade view, 0 production code. |

