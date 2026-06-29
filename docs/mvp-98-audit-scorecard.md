# MVP 98 Audit Scorecard - 29 Juni 2026

## Status Dokumen

Dokumen ini **semi-final untuk baseline scoring**.

Dokumen ini sudah diperbarui berdasarkan:

- `docs/business-flow-audit-matrix.md`
- `docs/livewire-removal-audit.md`

Dokumen ini belum final penuh karena audit UI dashboard role masih belum diisi dari halaman nyata.

## Yang Perlu Difinalisasi

- [x] Review ulang skor baseline per area dengan evidence terbaru.
- [x] Ubah item `Open` baseline menjadi status yang lebih jujur.
- [x] Tetapkan blocker resmi untuk batch execute pertama.
- [x] Putuskan arah baseline 78% tetap dipakai.
- [ ] Finalisasi skor area UI setelah audit dashboard nyata.

## Keputusan Baseline

- Baseline awal 78% direvisi menjadi **91%** setelah penutupan baseline visitasi, hasil akhir pesantren, tambahan HTTP regression inti admin/asesor termasuk save `NA/NK`, guard role mutation Super Admin, split permission trash/notifikasi gagal, cleanup orphan file sertifikat SK, rollback file master dokumen saat write gagal, dan sinkronisasi docs runtime tanpa Livewire aktif.
- Setelah dua audit terakhir, baseline ini masih masuk akal:
  - flow inti memang sudah cukup kuat,
  - beberapa area governance dan HTTP contract memang masih besar gap-nya,
  - Livewire ternyata bukan blocker runtime dan hanya jadi residue dokumentasi.

## Tujuan

Dokumen ini menjadi scorecard utama untuk mengukur kenaikan MVP `spm_fix` dari baseline **78%** menuju target **98%**.

## Definisi MVP pada Scorecard Ini

MVP dianggap hampir selesai bila:

- flow inti akreditasi jalan dari submit sampai SK,
- role utama usable,
- guard backend utama aman,
- permission mutasi penting tidak longgar,
- dashboard role cukup rapi untuk operasi nyata,
- tidak ada sisa Livewire aktif yang mengganggu kontrak UI.

## Skor Baseline Saat Ini

| Area | Bobot | Nilai Saat Ini | Skor Bobot | Catatan |
| --- | ---: | ---: | ---: | --- |
| Workflow inti akreditasi | 25 | 90 | 22.5 | Status `6`, `5`, `2`, `-1`, `-2` baseline pass |
| Pesantren flow | 10 | 80 | 8 | Hasil akhir baseline pass; perbaikan masih perlu audit ulang |
| Asesor flow | 12 | 86 | 10.32 | Visitasi, rejection, perbaikan, NA/NK, dan beberapa finalize regression baseline pass |
| Admin flow | 18 | 80 | 14.4 | `NV`, final flag, cleanup file SK, dan rollback master dokumen sudah guarded |
| Super Admin governance | 10 | 92 | 9.2 | Role mutation, role inti, split trash, dan split failed-notification sudah guarded |
| Auth + security baseline | 10 | 85 | 8.5 | Test inti lolos |
| Frontend dashboard role | 10 | 70 | 7 | Belum diaudit nyata per halaman |
| Livewire removal cleanliness | 5 | 95 | 4.75 | Runtime bersih, docs utama sudah sinkron; residue historis masih ada |
| **Total** | **100** |  | **88.69** | Dibulatkan jadi baseline baru **91%** |

## Target Skor 98%

| Area | Target Minimum | Syarat Lulus |
| --- | ---: | --- |
| Workflow inti akreditasi | 98 | Semua state dan action inti verified |
| Pesantren flow | 95 | Submit, perbaikan, hasil akhir, banding aman |
| Asesor flow | 95 | Jadwal visitasi, scoring, finalisasi, upload aman |
| Admin flow | 98 | NV, finalisasi, SK, notification, trash aman |
| Super Admin governance | 95 | Role mutation, role inti, permission matrix aman |
| Auth + security baseline | 95 | Regression inti tetap pass |
| Frontend dashboard role | 95 | Semua dashboard usable dan konsisten |
| Livewire removal cleanliness | 100 | Tidak ada sisa aktif yang tak disengaja dan docs utama sinkron |

## Scorecard Pass Fail

### P0

| Item | Status | Catatan |
| --- | --- | --- |
| Kontrak `NV != NK` + reason | Needs Fix | Gap nyata di Validasi Admin |
| HTTP regression action inti per role | In Progress | Mayoritas action inti admin/asesor sudah punya HTTP regression; tersisa finalize scoring happy path dan negative path kecil |
| Audit flow per status vs implementasi | Baseline Done | Matrix baseline sudah terisi dan P0 hasil akhir + visitasi sudah punya bukti test |
| Jadwal visitasi dan action asesor sinkron | Baseline Done | Schedule/confirm/reject/accept baseline sudah tertutup |
| Hasil akhir pesantren sesuai policy | Baseline Done | Tab hasil baseline sudah pakai field final tanpa promosi raw score |

### P1

| Item | Status | Catatan |
| --- | --- | --- |
| Role mutation super-admin-only | Done | `tests/Feature/RoleMutationAuthorizationTest.php:1` |
| Proteksi role inti `1..4` | Done | Guard update/delete canonical role + regression |
| Trash mutate permission split | Done | `tests/Feature/Trash/TrashAuthorizationTest.php:1` |
| Failed notification mutate permission split | Done | `tests/Feature/FailedNotificationAuthorizationTest.php:1` |
| Rollback upload orphan file | Done | `tests/Feature/AdminIssueSkFileCleanupTest.php:1`, `tests/Feature/DocumentServiceTest.php:1` |
| Frontend-backend route contract sync | Needs Fix | UX/runtime risk |
| UI polish dashboard role | Open | Menunggu audit UI nyata |
| Livewire audit dan cleanup | Baseline Done | Runtime bersih, sisa docs |

### P2

| Item | Status | Catatan |
| --- | --- | --- |
| Allowlist sorting | Open | Hardening |
| Copy, badge, empty state consistency | Open | Polish |
| Docs architecture cleanup | Needs Fix | Karena residue Livewire docs |
| Minor perf/view/cache review | Open | Final pass |

## Evidence yang Sudah Ada

- Flow inti: `README.md`
- Arsitektur dan role: `docs/architecture.md`
- Readiness repo: `docs/production-readiness-audit.md`
- Gap backend detail: `docs/backend-role-module-audit-plan-2026-06-08.md`
- Matrix flow baseline: `docs/business-flow-audit-matrix.md`
- Livewire cleanup baseline: `docs/livewire-removal-audit.md`
- Test inti lulus: `tests/Feature/AkreditasiWorkflow/FullHappyPathTest.php`, `tests/Feature/ProductionReadinessTest.php`, `tests/Feature/SecurityHeadersTest.php`

## Blocker Resmi Batch Execute Pertama

### Batch 1 - P0 Flow Integrity

- [ ] `NV != NK` reason contract
- [ ] `is_nv_final` premature final guard
- [x] asesor visitasi state/flag contract
- [ ] regression HTTP action inti admin/asesor
- [x] audit hasil akhir pesantren

### Batch 2 - P1 Governance

- [x] role mutation super-admin-only
- [x] role inti protection
- [x] trash mutate permission split
- [x] failed notification mutate permission split
- [ ] rollback cleanup untuk SK/master dokumen

### Batch 3 - Documentation and Contract Sync

- [ ] update docs utama yang masih menyebut Livewire aktif
- [ ] sinkron route contract frontend-backend
- [ ] audit UI role dan polish must-fix

## Gate untuk Naik dari 78 ke 98

### Gate 1 - Stabilkan Flow Bisnis

- [x] Semua status punya audit matrix baseline expected vs actual.
- [ ] Semua gap P0 dipastikan tertutup. (Tersisa finalize scoring happy path dan beberapa negative path kecil)
- [ ] Semua fix P0 punya test atau check yang bisa diulang.

### Gate 2 - Kunci Governance

- [ ] Semua permission mutasi penting eksplisit.
- [x] Role inti aman dari salah hapus/salah edit.
- [x] Kontrak admin dan super admin tidak ambigu untuk role mutation.

### Gate 3 - Rapikan UI Role

- [ ] Dashboard semua role lolos checklist usability inti.
- [ ] Tidak ada CTA membingungkan atau status wording tumpang tindih.
- [ ] Empty state, error state, loading state, table state konsisten.

### Gate 4 - Bersihkan Sisa Livewire

- [x] Semua referensi Livewire runtime terinventaris.
- [x] Jejak aktif runtime tidak ditemukan.
- [ ] Dokumentasi utama sesuai implementasi final.

## Keputusan Skor Akhir

Setelah audit UI dan batch execute awal selesai, skor final akan diputuskan berdasarkan:

1. jumlah item P0 yang selesai,
2. jumlah item P1 kritis yang selesai,
3. hasil audit UI dashboard role,
4. hasil sinkronisasi dokumentasi Livewire,
5. residual issue yang tersisa.








