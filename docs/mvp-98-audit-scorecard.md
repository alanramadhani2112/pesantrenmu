# MVP 98 Audit Scorecard - 29 Juni 2026

## Status Dokumen

Dokumen ini **final untuk batch P0/P1/P2 MVP 98 per 2026-07-07**.

Dokumen ini sudah diperbarui berdasarkan:

- `docs/business-flow-audit-matrix.md`
- `docs/livewire-removal-audit.md`
- `docs/ui-role-audit-2026-07-07.md`
- Focused HTTP regressions, BusinessFlow suite, full test suite, dan `npm run build` dari Task 3.

## Yang Perlu Difinalisasi

- [x] Review ulang skor baseline per area dengan evidence terbaru.
- [x] Ubah item `Open` baseline menjadi status yang lebih jujur.
- [x] Tetapkan blocker resmi untuk batch execute pertama.
- [x] Putuskan arah baseline 78% tetap dipakai.
- [x] Finalisasi skor area UI setelah audit dashboard nyata.

## Keputusan Baseline

- Baseline awal 78% direvisi menjadi **91%** setelah penutupan baseline visitasi, hasil akhir pesantren, tambahan HTTP regression inti admin/asesor termasuk save `NA/NK`, guard role mutation Super Admin, split permission trash/notifikasi gagal, cleanup orphan file sertifikat SK, rollback file master dokumen saat write gagal, dan sinkronisasi docs runtime tanpa reactive layer lama aktif.
- Setelah dua audit terakhir, baseline ini masih masuk akal:
  - flow inti memang sudah cukup kuat,
  - beberapa area governance dan HTTP contract memang masih besar gap-nya,
  - legacy reactive layer ternyata bukan blocker runtime dan hanya jadi residue dokumentasi.

## Tujuan

Dokumen ini menjadi scorecard utama untuk mengukur kenaikan MVP `spm_fix` dari baseline **78%** menuju target **98%**.

## Definisi MVP pada Scorecard Ini

MVP dianggap hampir selesai bila:

- flow inti akreditasi jalan dari submit sampai SK,
- role utama usable,
- guard backend utama aman,
- permission mutasi penting tidak longgar,
- dashboard role cukup rapi untuk operasi nyata,
- tidak ada sisa reactive layer lama aktif yang mengganggu kontrak UI.

## Skor Baseline Saat Ini

| Area | Bobot | Nilai Saat Ini | Skor Bobot | Catatan |
| --- | ---: | ---: | ---: | --- |
| Workflow inti akreditasi | 25 | 90 | 22.5 | Status `6`, `5`, `2`, `-1`, `-2` baseline pass |
| Pesantren flow | 10 | 80 | 8 | Hasil akhir baseline pass; perbaikan masih perlu audit ulang |
| Asesor flow | 12 | 86 | 10.32 | Visitasi, rejection, perbaikan, NA/NK, dan beberapa finalize regression baseline pass |
| Admin flow | 18 | 80 | 14.4 | `NV`, final flag, cleanup file SK, dan rollback master dokumen sudah guarded |
| Super Admin governance | 10 | 92 | 9.2 | Role mutation, role inti, split trash, dan split failed-notification sudah guarded |
| Auth + security baseline | 10 | 85 | 8.5 | Test inti lolos |
| Frontend dashboard role | 10 | 95 | 9.5 | Browser smoke 19 route pass, issues `[]` |
| legacy reactive removal cleanliness | 5 | 100 | 5 | Runtime bersih; docs utama sinkron; residue historis ditandai historical |
| **Total** | **100** |  | **91.44** | Prior documented 88.69 + finalized UI/legacy evidence; sisa P2 non-blocking deferred |

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
| legacy reactive removal cleanliness | 100 | Tidak ada sisa aktif yang tak disengaja dan docs utama sinkron |

## Scorecard Pass Fail

### P0

| Item | Status | Catatan |
| --- | --- | --- |
| Kontrak `NV != NK` + reason | Done | UI mengirim `nvReasons[butir_id]`; HTTP regression finalisasi NV tetap pass |
| HTTP regression action inti per role | Done | `BusinessFlowHttpContractTest`, `BusinessFlow*Test`, admin/asesor HTTP tests pass |
| Audit flow per status vs implementasi | Done | Matrix baseline dan business-flow suite pass |
| Jadwal visitasi dan action asesor sinkron | Done | Confirm visitasi + finalize scoring route contract pass |
| Hasil akhir pesantren sesuai policy | Done | Tab hasil baseline pakai field final tanpa promosi raw score |

### P1

| Item | Status | Catatan |
| --- | --- | --- |
| Role mutation super-admin-only | Done | `tests/Feature/RoleMutationAuthorizationTest.php:1` |
| Proteksi role inti `1..4` | Done | Guard update/delete canonical role + regression |
| Trash mutate permission split | Done | `tests/Feature/Trash/TrashAuthorizationTest.php:1` |
| Failed notification mutate permission split | Done | `tests/Feature/FailedNotificationAuthorizationTest.php:1` |
| Rollback upload orphan file | Done | `tests/Feature/AdminIssueSkFileCleanupTest.php:1`, `tests/Feature/DocumentServiceTest.php:1` |
| Frontend-backend route contract sync | Done | Asesor dynamic POST includes `akreditasi_id`; admin NV form emits controller field names |
| UI polish dashboard role | Done | Evidence: `docs/ui-role-audit-2026-07-07.md` |
| legacy reactive audit dan cleanup | Done | Runtime bersih; docs utama sinkron; residue historis ditandai historical |

### P2

| Item | Status | Catatan |
| --- | --- | --- |
| Allowlist sorting | Deferred | Hardening non-blocking; tidak masuk Task 1-4 |
| Copy, badge, empty state consistency | Pass MVP | Browser smoke 19 route, issues `[]` |
| Docs architecture cleanup | Done | Docs utama menunjuk evidence terbaru; residue historis tetap historical |
| Minor perf/view/cache review | Deferred | Non-blocking; tidak ada temuan smoke browser |

## Evidence yang Sudah Ada

- Flow inti: `README.md`
- Arsitektur dan role: `docs/architecture.md`
- Readiness repo: `docs/production-readiness-audit.md`
- Gap backend detail: `docs/backend-role-module-audit-plan-2026-06-08.md`
- Matrix flow baseline: `docs/business-flow-audit-matrix.md`
- legacy reactive cleanup baseline: `docs/livewire-removal-audit.md`
- UI role smoke audit: `docs/ui-role-audit-2026-07-07.md`
- Test inti lulus: `tests/Feature/AkreditasiWorkflow/FullHappyPathTest.php`, `tests/Feature/ProductionReadinessTest.php`, `tests/Feature/SecurityHeadersTest.php`
- Task 3 verification: focused tests 27 passed; BusinessFlow 24 passed/1 skipped; full suite 3081 passed/3 skipped; `npm run build` pass

## Blocker Resmi Batch Execute Pertama

### Batch 1 - P0 Flow Integrity

- [x] `NV != NK` reason contract
- [x] `is_nv_final` premature final guard
- [x] asesor visitasi state/flag contract
- [x] regression HTTP action inti admin/asesor
- [x] audit hasil akhir pesantren

### Batch 2 - P1 Governance

- [x] role mutation super-admin-only
- [x] role inti protection
- [x] trash mutate permission split
- [x] failed notification mutate permission split
- [x] rollback cleanup untuk SK/master dokumen

### Batch 3 - Documentation and Contract Sync

- [x] update docs utama yang masih menyebut reactive layer lama aktif
- [x] sinkron route contract frontend-backend
- [x] audit UI role dan polish must-fix

## Gate untuk Naik dari 78 ke 98

### Gate 1 - Stabilkan Flow Bisnis

- [x] Semua status punya audit matrix baseline expected vs actual.
- [x] Semua gap P0 dipastikan tertutup.
- [x] Semua fix P0 punya test atau check yang bisa diulang.

### Gate 2 - Kunci Governance

- [x] Semua permission mutasi penting eksplisit.
- [x] Role inti aman dari salah hapus/salah edit.
- [x] Kontrak admin dan super admin tidak ambigu untuk role mutation.

### Gate 3 - Rapikan UI Role

- [x] Dashboard semua role lolos checklist usability inti.
- [x] Tidak ada CTA membingungkan atau status wording tumpang tindih.
- [x] Empty state, error state, loading state, table state konsisten untuk flow MVP.

### Gate 4 - Bersihkan sisa legacy reactive layer

- [x] Semua referensi runtime reactive layer lama terinventaris.
- [x] Jejak aktif runtime tidak ditemukan.
- [x] Dokumentasi utama sesuai implementasi final.

## Keputusan Skor Akhir

Audit UI dan batch execute awal selesai. Keputusan final per 2026-07-07:

1. item P0 selesai,
2. item P1 kritis selesai,
3. audit UI dashboard role lulus smoke browser 19 route tanpa issue,
4. dokumentasi utama menunjuk implementasi/evidence terbaru,
5. sisa P2 hardening non-blocking didefer eksplisit.









