# Business Flow Audit Matrix - 29 Juni 2026

## Status Dokumen

Dokumen ini **semi-final untuk baseline audit backend**.

Sudah diisi dengan evidence awal dari source code dan test inti.

Masih perlu finalisasi lanjutan untuk:

- audit view detail per halaman,
- audit browser nyata per role,
- verifikasi apakah seluruh gap audit lama masih 100% reproduktif.

## Yang Perlu Difinalisasi

- [x] Isi kolom `Actual` dari code terbaru.
- [x] Isi `Status` baseline: `Pass`, `Fail`, `Unknown`, atau `Needs Fix`.
- [x] Isi `Evidence` awal dengan file atau test nyata.
- [ ] Verifikasi mismatch lewat audit browser nyata.
- [ ] Tandai flow mana yang jadi batch execute pertama.

## Tujuan

Dokumen ini memetakan **aturan bisnis yang diharapkan** vs **implementasi yang ada** untuk seluruh status akreditasi.

## Cara Pakai

- `Pass` = rule utama sudah terlihat benar dari code/test.
- `Needs Fix` = ada gap nyata atau audit lama kuat dan masih relevan.
- `Unknown` = belum cukup bukti dari code/test yang dibaca.

## Matrix Per Status

| Status | Nama Status | Role Utama | Expected Business Rule | Actual | Status | Evidence | Fix |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 6 | Pengajuan | Pesantren | Pesantren hanya bisa submit jika data minimum lengkap | `submitPengajuan()` cek kelengkapan data dan menolak jika masih ada akreditasi aktif status `6..1` | Pass | `app/Services/AkreditasiWorkflowService.php:75`, `app/Http/Controllers/Pesantren/AkreditasiController.php:52` | Tambah/cek HTTP regression submit bila perlu |
| 5 | Verifikasi Berkas | Admin | Admin review berkas, approve/reject/open review, assign asesor | Action admin tersedia, pakai `Gate::authorize('akreditasi.approve')`, approve minta 2 asesor berbeda, reject minta section + catatan | Pass | `app/Http/Controllers/Admin/AkreditasiDetailController.php:412`, `app/Http/Controllers/Admin/AkreditasiDetailController.php:437`, `app/Services/AkreditasiWorkflowService.php:131`, `app/Services/AkreditasiWorkflowService.php:175`, `app/Services/AkreditasiWorkflowService.php:289` | Tambah regression HTTP penuh per action |
| 4 | Review Asesor | Asesor | Asesor review substansi dan hanya akses penugasan sendiri | Controller punya action scoring/reject/save/finalize, tapi audit lama menandai flag jadwal visitasi dan action HTTP masih perlu diselaraskan | Needs Fix | `app/Http/Controllers/Asesor/AkreditasiController.php:143`, `app/Http/Controllers/Asesor/AkreditasiController.php:456`, `docs/backend-role-module-audit-plan-2026-06-08.md:804` | Audit dan sinkronkan action flags + regression |
| 3 | Visitasi | Asesor/Admin | Visitasi hanya bisa dijadwalkan pada state valid | Service mensyaratkan status `4` lalu transisi ke `3`, dan HTTP test baseline membuktikan asesor 1 bisa menjadwalkan dari tahap assessment | Pass | `app/Services/AkreditasiWorkflowService.php:363`, `tests/Feature/AsesorVisitasiVisibilityTest.php:1` | Tambah negative regression untuk actor/status salah |
| 2 | Penilaian Pasca Visitasi | Asesor | Input nilai dan upload laporan wajib lengkap sebelum finalisasi | Finalisasi asesor ada dan happy path test lulus; test juga membuktikan finalisasi gagal bila dokumen belum lengkap | Pass | `app/Services/AkreditasiWorkflowService.php:626`, `tests/Feature/AkreditasiWorkflow/FullHappyPathTest.php:246`, `tests/Feature/AkreditasiWorkflow/PostVisitasiDocumentsTest.php:1` | Audit UX upload/finalize bila perlu |
| 1 | Validasi Admin | Admin | Admin input NV, validasi akhir, lalu issue SK jika syarat lengkap | Flow ada, tapi `finalizeAllNv()` set `is_nv_final = true` setelah loop dan kontrak `NV != NK` belum rapat | Needs Fix | `app/Http/Controllers/Admin/AkreditasiDetailController.php:345`, `app/Services/AssessorScoringService.php:1`, `docs/backend-role-module-audit-plan-2026-06-08.md:810`, `docs/backend-role-module-audit-plan-2026-06-08.md:811` | Tutup kontrak reason NV + guard final global |
| 0 | Terakreditasi | Pesantren/Admin | Pesantren melihat hasil akhir yang diizinkan | Tab hasil kini memakai field final `akreditasi` dan test baseline memastikan label hasil akhir tampil tanpa promosi raw `NK/NV/NA1/NA2` | Pass | `resources/views/pesantren/akreditasi-detail.blade.php:1`, `tests/Feature/PesantrenHasilAkhirVisibilityTest.php:1` | Tambah audit browser nyata jika perlu |
| -1 | Ditolak Final | Pesantren/Admin | Penolakan final tampil benar dan bisa membuka banding bila valid | Jalur reject final ada; pesantren punya action banding dengan validasi alasan minimal 50 karakter | Pass | `app/Http/Controllers/Pesantren/AkreditasiController.php:85`, `app/Services/AkreditasiWorkflowService.php:956` | Audit UI jalur banding dari hasil akhir |
| -2 | Banding | Pesantren/Admin | Banding hanya pada window valid dan kembali ke state sah | Banding service ada, test banding banyak, dan keputusan kembali ke `1` atau `-1` | Pass | `app/Services/BandingService.php:1`, `tests/Feature/AkreditasiWorkflow/BandingPathTest.php:1`, `tests/Unit/Banding/Property14BandingUniquenessTest.php:1` | Tambah audit HTTP reviewer/decision bila perlu |

## Audit Per Role

### Pesantren

| Flow | Expected | Status | Evidence | Fix |
| --- | --- | --- | --- | --- |
| Lengkapi profil/IPM/SDM/EDPM | Semua data minimum terisi sebelum submit | Pass | `app/Services/AkreditasiWorkflowService.php:75` | Pertahankan regression submit |
| Submit pengajuan | Tidak bisa lintas tenant / bypass | Pass | `app/Http/Controllers/Pesantren/AkreditasiController.php:52` | Audit browser ringan |
| Perbaikan setelah reject | Hanya section yang valid dibuka | Needs Fix | `docs/backend-role-module-audit-plan-2026-06-08.md:802` | Audit ulang partial unlock dan submit perbaikan |
| Lihat hasil akhir | Hanya data akhir yang boleh terlihat | Pass | `resources/views/pesantren/akreditasi-detail.blade.php:1`, `tests/Feature/PesantrenHasilAkhirVisibilityTest.php:1` | Audit browser ringan |
| Ajukan banding | Hanya pada jendela valid | Pass | `app/Http/Controllers/Pesantren/AkreditasiController.php:85`, `tests/Unit/Banding/Property14BandingUniquenessTest.php:1` | Audit tombol/entry point UI |

### Asesor

| Flow | Expected | Status | Evidence | Fix |
| --- | --- | --- | --- | --- |
| Lihat penugasan | Hanya data penugasan sendiri | Pass | policy tests ada di `tests/Unit/Policies/TenantPolicyTest.php:167` | Audit browser ringan |
| Review substansi | Action sesuai state | Baseline Done | `tests/Feature/AsesorRejectDocumentHttpTest.php:1`, `tests/Feature/AsesorAcceptPerbaikanHttpTest.php:1`, `tests/Feature/AsesorFinalizeScoringHttpTest.php:1`, `tests/Feature/AsesorSaveNaHttpTest.php:1`, `tests/Feature/AsesorSaveNkHttpTest.php:1` | Tambah happy-path finalize scoring penuh |
| Jadwal visitasi | Hanya pada status yang benar | Pass | `app/Services/AkreditasiWorkflowService.php:363`, `tests/Feature/AsesorVisitasiVisibilityTest.php:1` | Tambah negative regression |
| Input nilai | Simpan/final konsisten | Pass | `app/Http/Controllers/Asesor/AkreditasiController.php:456`, `tests/Feature/AkreditasiWorkflow/FullHappyPathTest.php:246` | Audit UX/save-final contract |
| Upload laporan | Wajib lengkap sebelum finalisasi | Pass | `tests/Feature/AkreditasiWorkflow/PostVisitasiDocumentsTest.php:1` | Audit feedback UI |

### Admin

| Flow | Expected | Status | Evidence | Fix |
| --- | --- | --- | --- | --- |
| Review berkas | Approve/reject/open review valid | Baseline Done | `tests/Feature/AdminOpenForReviewHttpTest.php:1`, `tests/Feature/AdminApproveBerkasHttpTest.php:1`, `tests/Feature/AdminRejectBerkasHttpTest.php:1` | Tambah edge-case regression bila perlu |
| Assign/reassign asesor | Valid dan terlacak | Pass | `app/Services/AkreditasiWorkflowService.php:175`, `routes/web.php:138` | Audit stale-state path |
| Validasi NV | `NV != NK` wajib punya reason | Pass | `app/Http/Controllers/Admin/AkreditasiDetailController.php:345`, `tests/Feature/AdminFinalizeNvHttpTest.php:1` | Pertahankan regression |
| Finalisasi admin | Tidak set final global prematur | Pass | `app/Http/Controllers/Admin/AkreditasiDetailController.php:345`, `tests/Feature/AdminFinalizeNvHttpTest.php:1` | Pertahankan regression |
| Issue SK | Tidak orphan file jika gagal | Pass | `app/Http/Controllers/Admin/AkreditasiDetailController.php:552`, `tests/Feature/AdminIssueSkFileCleanupTest.php:1` | Pertahankan regression |
| Trash ops | Permission mutasi eksplisit | Pass | `routes/web.php:215`, `routes/web.php:218`, `tests/Feature/Trash/TrashAuthorizationTest.php:1` | Pertahankan regression |
| Failed notifications | Retry/dismiss tidak terlalu longgar | Pass | outes/web.php:202, outes/web.php:205, 	ests/Feature/FailedNotificationAuthorizationTest.php:1 | Pertahankan regression |
| Master dokumen | Tidak orphan file saat write gagal | Pass | pp/Services/DocumentService.php:1, 	ests/Feature/DocumentServiceTest.php:1 | Pertahankan regression |

### Super Admin

| Flow | Expected | Status | Evidence | Fix |
| --- | --- | --- | --- | --- |
| Role management | Hanya super admin yang bisa mutasi | Pass | `app/Http/Controllers/Admin/RoleController.php:30`, `tests/Feature/RoleMutationAuthorizationTest.php:1` | Pertahankan regression |
| Role inti | Role `1..4` tidak bisa rusak | Pass | `app/Http/Controllers/Admin/RoleController.php:68`, `tests/Feature/RoleMutationAuthorizationTest.php:1` | Pertahankan regression |
| Permission matrix | Tidak mengaburkan god-mode | Pass | `docs/backend-role-module-audit-plan-2026-06-08.md:96` | Audit browser nanti |
| Notifikasi operasional | Kebijakan penerima jelas | Needs Fix | `docs/backend-role-module-audit-plan-2026-06-08.md:104` | Putuskan policy admin-area recipients |

## Batch Execute Pertama yang Disarankan

### Batch 1 - P0 Flow Integrity

- [ ] `NV != NK` reason contract
- [ ] `is_nv_final` premature final guard
- [x] asesor visitasi state/flag contract
- [ ] asesor/admin HTTP regression untuk action inti
- [x] audit hasil akhir pesantren

### Batch 2 - P1 Governance

- [x] role mutation super-admin-only
- [x] role inti protection
- [x] trash mutate permission split
- [x] failed notification mutate permission split
- [ ] SK/master document rollback cleanup

### Batch 3 - UI dan Contract Sync

- [ ] route contract frontend-backend
- [ ] hasil akhir pesantren UI
- [ ] dashboard polish per role







