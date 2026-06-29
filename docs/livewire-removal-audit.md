# Livewire Removal Audit - 29 Juni 2026

## Status Dokumen

Dokumen ini **semi-final untuk baseline cleanup audit**.

Hasil scan repo saat ini menunjukkan:

- **tidak ada hit Livewire/Volt di area runtime** `app/`, `resources/`, `routes/`, `config/`
- **tidak ada hit Livewire/Volt di `tests/` aktif saat ini**
- hit yang tersisa ada di **dokumentasi lama / audit historis / tech spec lama**

## Yang Perlu Difinalisasi

- [x] Jalankan scan repo nyata untuk semua istilah Livewire/Volt.
- [x] Isi matrix temuan dari hasil scan.
- [x] Putuskan `keep/remove/replace` per temuan baseline.
- [ ] Putuskan apakah cleanup doc dilakukan di batch execute awal atau setelah flow fix P0.

## Tujuan

Dokumen ini memastikan tidak ada sisa Livewire/Volt aktif yang diam-diam masih mempengaruhi runtime, kontrak UI, atau dokumentasi.

## Hasil Scan Baseline

### Area Runtime

Scan ke `app/`, `resources/`, `routes/`, `config/` dengan kata kunci:

- `Livewire`
- `Volt`
- `wire:`
- `@livewire`

Hasil:

- **0 hit**

Makna:

- tidak ditemukan directive Livewire aktif,
- tidak ditemukan referensi Volt aktif,
- tidak ditemukan binding `wire:` aktif,
- tidak ditemukan helper `@livewire` aktif.

### Area Test

Scan ke `tests/` dengan kata kunci yang sama.

Hasil:

- **0 hit**

Makna:

- test aktif saat ini tidak lagi menggantung pada Livewire runtime.

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
| Dokumentasi arsitektur masih menyebut Livewire Volt aktif | `docs/architecture.md` | Needs Fix | Membingungkan arsitektur final repo | Update ke Blade + controller/service flow |
| Dokumen UI positioning masih mengasumsikan Livewire untuk state/behavior | `docs/frontend-ui-ux-positioning.md` | Needs Fix | Narasi arsitektur UI jadi tidak akurat | Revisi wording ke Blade + Alpine/JS bila relevan |
| Dokumen perf/UX masih menyebut `wire:poll` | `docs/frontend-performance-ux-writing-update.md` | Needs Fix | Bisa menyesatkan audit performa terbaru | Verifikasi apakah catatan historis perlu dipertahankan atau diganti |
| Tech spec lama masih menyebut Livewire Volt dan route berbasis komponen Livewire | `docs/tech-spec-pesantrenmu.md` | Needs Fix | Tech spec tidak sinkron dengan implementasi sekarang | Update atau arsipkan sebagai legacy |
| Audit frontend lama memuat jejak artifact Livewire historis | `docs/audit-frontend-2025-06-08.md` | Keep | Berguna sebagai jejak audit migrasi | Pertahankan, tapi jelas sebagai historical audit |
| Source runtime aktif | `app/`, `resources/`, `routes/`, `config/` | Pass | Tidak ada bug aktif yang terdeteksi dari keyword scan | Tidak perlu action code cleanup berbasis keyword ini |
| Test runtime aktif | `tests/` | Pass | Tidak ada ketergantungan test aktif pada Livewire | Tidak perlu cleanup test berbasis keyword ini |
| Dependency package composer/npm | `composer.json`, `package.json` | Pass | Tidak ada package Livewire/Volt aktif | Tidak perlu uninstall package Livewire |

## Bukti Scan

- Runtime scan `app/resources/routes/config`: **0 hit**
- Test scan `tests`: **0 hit**
- `composer.json`: tidak ada package `livewire/livewire`
- `package.json`: tidak ada dependency frontend untuk Livewire/Volt

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

Repo dinyatakan bersih dari Livewire untuk baseline saat ini bila:

- tidak ada directive/runtime hook aktif yang tak disengaja,
- tidak ada dependency Livewire aktif,
- dokumen arsitektur utama tidak lagi menyebut Livewire sebagai arsitektur aktif,
- audit historis yang masih menyebut Livewire diberi konteks sebagai catatan lama.

## Kesimpulan Baseline

Kesimpulan saat ini:

- **cleanup Livewire bukan blocker runtime utama**
- **cleanup Livewire sekarang terutama adalah pekerjaan sinkronisasi dokumentasi**
- prioritas execute yang lebih tinggi tetap ada di flow bisnis P0, terutama `Validasi Admin`, `Visitasi`, dan regression HTTP action inti

## Action Plan

### Batch ringan

- [ ] Update `docs/architecture.md`
- [ ] Update `docs/frontend-ui-ux-positioning.md`
- [ ] Review `docs/frontend-performance-ux-writing-update.md`
- [ ] Review `docs/tech-spec-pesantrenmu.md`

### Batch setelah P0 flow

- [ ] Rapikan semua wording dokumentasi yang masih menyebut Livewire sebagai kondisi aktif
- [ ] Re-scan repo untuk memastikan tidak ada hit runtime baru
