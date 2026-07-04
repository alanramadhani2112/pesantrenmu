# legacy reactive layer Removal Audit - 29 Juni 2026

## Status Dokumen

Dokumen ini **semi-final untuk baseline cleanup audit**.

Hasil scan repo saat ini menunjukkan:

- **tidak ada Livewire runtime frontend aktif** di `app/`, `resources/views/`, `routes/`, `config/`
- **tidak ada package Livewire aktif** di `composer.json` / `package.json`
- sisa Livewire-era JavaScript di `resources/js/app.js` sudah dihapus dari runtime
- hit yang tersisa hanya boleh berada di **dokumentasi lama / audit historis / vendor optional stubs**

## Yang Perlu Difinalisasi

- [x] Jalankan scan repo nyata untuk semua istilah legacy reactive layer.
- [x] Isi matrix temuan dari hasil scan.
- [x] Putuskan `keep/remove/replace` per temuan baseline.
- [ ] Putuskan apakah cleanup doc dilakukan di batch execute awal atau setelah flow fix P0.

## Tujuan

Dokumen ini memastikan tidak ada sisa legacy reactive layer aktif yang diam-diam masih mempengaruhi runtime, kontrak UI, atau dokumentasi.

## Hasil Scan Baseline

### Area Runtime

Scan ke `app/`, `resources/`, `routes/`, `config/` dengan kata kunci:

- `legacy reactive layer`
- `legacy UI component layer`
- `legacy-poll:`
- `@livewire`

Hasil:

- **0 hit**

Makna:

- tidak ditemukan directive reactive layer lama aktif,
- tidak ditemukan referensi legacy UI component layer aktif,
- tidak ditemukan binding `legacy-poll:` aktif,
- tidak ditemukan helper `@livewire` aktif.

### Area Test

Scan ke `tests/` dengan kata kunci yang sama.

Hasil:

- **0 hit**

Makna:

- test aktif saat ini tidak lagi menggantung pada runtime reactive layer lama.

### Area Dokumentasi

Hit yang tersisa ada di dokumen berikut:

- `docs/architecture.md`
- `docs/frontend-ui-ux-positioning.md`
- `docs/frontend-performance-ux-writing-update.md`
- `docs/tech-spec-pesantrenmu.md`
- `docs/audit-frontend-2025-06-08.md`

Makna:

- residue utama sekarang adalah **residue dokumentasi**, bukan residue runtime.

## Matrix Audit

| Jenis Temuan | Lokasi | Status | Dampak | Tindakan |
| --- | --- | --- | --- | --- |
| Dokumentasi arsitektur utama | `docs/architecture.md` | Pass | Sudah sinkron ke Blade + controller/runtime aktif | Pertahankan sinkronisasi docs utama |
| Dokumen UI positioning utama | `docs/frontend-ui-ux-positioning.md` | Pass | Sudah sinkron ke Blade/controller + Alpine kecil | Pertahankan wording runtime aktif |
| Dokumen perf/UX utama | `docs/frontend-performance-ux-writing-update.md` | Pass | Sudah diganti menjadi catatan polling legacy non-legacy reactive layer | Pertahankan sebagai historical note seperlunya |
| Tech spec utama | `docs/tech-spec-pesantrenmu.md` | Pass | Sudah sinkron ke Blade/controller runtime | Pertahankan sinkronisasi docs utama |
| Audit frontend lama memuat jejak artifact legacy reactive layer historis | `docs/audit-frontend-2025-06-08.md` | Keep | Berguna sebagai jejak audit migrasi | Pertahankan, tapi jelas sebagai historical audit |
| Source runtime aktif | `app/`, `resources/`, `routes/`, `config/` | Pass | Tidak ada bug aktif yang terdeteksi dari keyword scan | Tidak perlu action code cleanup berbasis keyword ini |
| Test runtime aktif | `tests/` | Pass | Tidak ada ketergantungan test aktif pada legacy reactive layer | Tidak perlu cleanup test berbasis keyword ini |
| Dependency package composer/npm | `composer.json`, `package.json` | Pass | Tidak ada package legacy reactive layer aktif | Tidak perlu uninstall package legacy reactive layer |

## Bukti Scan

- Runtime scan `app/resources/routes/config`: **0 hit**
- Test scan `tests`: **0 hit**
- `composer.json`: tidak ada package `livewire/livewire`
- `package.json`: tidak ada dependency frontend untuk legacy reactive layer

## Keputusan Baseline

### Keep

- `docs/audit-frontend-2025-06-08.md`
  - alasan: dokumen audit historis, bukan contract arsitektur final

### Replace / Update

- `docs/architecture.md`
- `docs/frontend-ui-ux-positioning.md`
- `docs/frontend-performance-ux-writing-update.md`
- `docs/tech-spec-pesantrenmu.md`

Alasan:

- dokumen-dokumen ini berpotensi dibaca sebagai kondisi arsitektur aktif sekarang.

### No Action Needed

- source code runtime
- test aktif
- dependency composer/npm

## Exit Criteria

Repo dinyatakan bersih dari legacy reactive layer untuk baseline saat ini bila:

- tidak ada directive/runtime hook aktif yang tak disengaja,
- tidak ada dependency reactive layer lama aktif,
- dokumen arsitektur utama tidak lagi menyebut legacy reactive layer sebagai arsitektur aktif,
- audit historis yang masih menyebut legacy reactive layer diberi konteks sebagai catatan lama.

## Kesimpulan Baseline

Kesimpulan saat ini:

- **cleanup legacy reactive layer bukan blocker runtime utama**
- **cleanup legacy reactive layer sekarang terutama adalah pekerjaan sinkronisasi dokumentasi**
- prioritas execute yang lebih tinggi tetap ada di flow bisnis P0, terutama `Validasi Admin`, `Visitasi`, dan regression HTTP action inti

## Action Plan

### Batch ringan

- [ ] Update `docs/architecture.md`
- [ ] Update `docs/frontend-ui-ux-positioning.md`
- [ ] Review `docs/frontend-performance-ux-writing-update.md`
- [ ] Review `docs/tech-spec-pesantrenmu.md`

### Batch setelah P0 flow

- [x] Rapikan semua wording dokumentasi utama yang masih menyebut legacy reactive layer sebagai kondisi aktif
- [ ] Re-scan repo untuk memastikan tidak ada hit runtime baru



