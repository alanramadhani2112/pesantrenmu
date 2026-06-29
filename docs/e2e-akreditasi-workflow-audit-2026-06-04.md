# E2E Akreditasi Workflow Audit - 2026-06-04

## Summary

Audit dijalankan pada environment lokal `APP_ENV=local`, DB `mysql`, dengan run ID `E2E-20260604-154424`.

State canonical happy path berhasil divalidasi lewat service layer dan diverifikasi ulang lewat browser lintas role:

`6 Pengajuan -> 5 Verifikasi Berkas -> 4 Review Asesor -> 3 Visitasi -> 2 Penilaian Pasca Visitasi -> 1 Validasi Admin -> 0 Selesai`

Data utama:

- Akreditasi ID: `24`
- UUID: `d838de26-ec95-46ab-8979-9989423ef050`
- Pesantren: `e2e-20260604-154424-happypath@e2e.spm.test`
- Ketua Kelompok: `e2e-20260604-154424.ketua@e2e.spm.test`
- Anggota Kelompok: `e2e-20260604-154424.anggota@e2e.spm.test`
- Admin: `admin@spm.test`
- Super Admin: `superadmin@spm.test`
- Hasil akhir: status `0 Selesai`, nilai `76`, peringkat `B`, nomor SK `SK/E2E-20260604-154424`

Evidence JSON dan script audit:

- `output/playwright/e2e-akreditasi-audit/evidence.json`
- `output/playwright/e2e-akreditasi-audit/run_e2e_audit.php`

## Flow Evidence

| Actor | Action | Before | After | Evidence |
| --- | --- | ---: | ---: | --- |
| Pesantren | Submit pengajuan setelah profil/IPM/SDM/EDPM lengkap | - | `6` | `evidence.json`, workflow row 1 |
| Admin | Open for review | `6` | `5` | `evidence.json`, workflow row 2 |
| Admin | Approve berkas dan assign dua asesor | `5` | `4` | `evidence.json`, workflow row 3 |
| Asesor Ketua | Buat perbaikan substansi | `4` | `4` | rejection `pending`, lalu `submitted` dan `accepted` |
| Asesor Ketua | Jadwalkan visitasi | `4` | `3` | `evidence.json`, workflow row 4 |
| Asesor Ketua | Konfirmasi visitasi selesai | `3` | `2` | `evidence.json`, workflow row 5 |
| Asesor Ketua/Anggota/Pesantren | Final NA1, NA2, NK, catatan, laporan, kartu kendali | `2` | `2` | Dokumen lengkap sebelum finalisasi |
| Asesor Ketua | Final submit paket asesor | `2` | `1` | `evidence.json`, workflow row 6 |
| Admin | Final NV dan terbitkan SK | `1` | `0` | `evidence.json`, workflow row 7 |
| Pesantren/Admin | Banding diterima | `-1 -> -2` | `1` | banding ID `3` |
| Pesantren/Admin | Banding ditolak dan duplicate blocked | `-1 -> -2` | `-1` | duplicate error: hanya 1 banding |
| Pesantren | Banding setelah 14 hari | `-1` | blocked | error: masa pengajuan banding berakhir |

## Browser Evidence

Screenshots disimpan di:

- `output/playwright/e2e-akreditasi-audit/01-pesantren-detail.png`
- `output/playwright/e2e-akreditasi-audit/02-asesor-ketua-detail.png`
- `output/playwright/e2e-akreditasi-audit/03-asesor-anggota-detail.png`
- `output/playwright/e2e-akreditasi-audit/04-admin-detail.png`
- `output/playwright/e2e-akreditasi-audit/05-admin-banding-detail.png`
- `output/playwright/e2e-akreditasi-audit/06-superadmin-rbac.png`
- `output/playwright/e2e-akreditasi-audit/07-superadmin-admin-detail.png`

Observed browser checkpoints:

- Pesantren detail menampilkan `Selesai`, stepper sampai `Hasil Akhir`, dan riwayat perbaikan `Diterima`.
- Asesor Ketua detail menampilkan role `Ketua Kelompok`.
- Asesor Anggota detail menampilkan role `Anggota Kelompok`.
- Admin detail menampilkan `Selesai`, `2 Asesor`, jadwal visitasi, riwayat perbaikan, dan tombol admin.
- Admin banding detail menampilkan banding `Diterima` dan status akreditasi kembali `Validasi Admin`.
- Super admin bisa membuka RBAC dan detail akreditasi admin area.

## Automated Test Results

Commands executed:

```powershell
php artisan test tests/Feature/AkreditasiWorkflow tests/Feature/AkreditasiStateMachineTransitionTest.php tests/Feature/AkreditasiWorkflow/PostVisitasiDocumentsTest.php tests/Feature/BusinessStatusMappingTest.php --stop-on-failure
```

Result: `66 passed`, `239 assertions`.

```powershell
php artisan test tests/Feature/legacy reactive layer/PesantrenProfileFlowTest.php tests/Feature/legacy reactive layer/PesantrenIpmFlowTest.php tests/Feature/legacy reactive layer/PesantrenSdmFlowTest.php tests/Feature/legacy reactive layer/PesantrenEdpmUiTest.php tests/Feature/legacy reactive layer/PesantrenRejectionUiTest.php tests/Feature/legacy reactive layer/AsesorRejectionUiTest.php tests/Feature/legacy reactive layer/AdminRejectionUiTest.php tests/Feature/legacy reactive layer/AdminReassignAsesorUiTest.php tests/Feature/legacy reactive layer/RolePermissionMatrixTest.php tests/Feature/AdminBandingDetailTest.php
```

Result setelah follow-up fix: `64 passed`, `257 assertions`.

Additional focused regression:

```powershell
php artisan test tests/Feature/RejectionServiceTest.php tests/Feature/legacy reactive layer/PesantrenIpmFlowTest.php tests/Feature/AdminBandingDetailTest.php tests/Feature/AkreditasiWorkflow/FullHappyPathTest.php tests/Feature/AkreditasiWorkflow/BandingPathTest.php --stop-on-failure
```

Result: `37 passed`, `189 assertions`.

```powershell
php artisan test tests/Feature/RejectionServiceTest.php --stop-on-failure
```

Result: `12 passed`, `59 assertions`.

## Follow-up Fix Verification

- `resources/views/components/ui/file-upload.blade.php`: marker `data-ui-file-upload="metronic"` ditambahkan dan dibuktikan oleh `PesantrenIpmFlowTest`.
- `app/Services/RejectionService.php`: `rejectBerkas` kini menjadi revisi administratif status `5`, tidak soft-delete, membuka lock pesantren, dan menerima Admin/Super Admin.
- `app/Services/RejectionService.php`: auto-reject deadline tetap lewat state machine dan kini memiliki fallback aktor audit agar scheduled job tidak gagal ketika data lokal/test belum punya admin user.
- `app/Services/AkreditasiWorkflowService.php`: pesan unauthorized `rejectBerkas` disesuaikan menjadi Admin atau Super Admin.
- `resources/views/livewire/pages/admin/banding-detail.blade.php`: detail banding eager-load `asesor` dan menampilkan nama asesor, bukan nilai kosong.

## Issues Found During Audit

### P1 - Review awal admin rejection menjadi penolakan final dan soft delete

- File: `app/Services/RejectionService.php`
- Method: `rejectBerkas`
- Actual: status berubah dari `5` ke `-1`, lalu akreditasi di-soft-delete.
- Expected dari `docs/business-spec-flow-lp2m-v1.md`: revisi administratif bukan penolakan final dan tidak membuka banding.
- Impact: proses "revisi administratif" tidak tersedia sebagai perbaikan bertahap pada pengajuan yang sama; pesantren melihat rejection terminal, walau banding memang diblokir karena belum ada asesor.
- Follow-up status: fixed and covered by `RejectionServiceTest::test_admin_berkas_revision_keeps_pengajuan_active_and_unlocks_pesantren`.

### P1 - Super admin bisa akses route admin tetapi gagal pada service reject berkas

- File: `app/Services/RejectionService.php`
- Method: `rejectBerkas`
- Actual: super admin gagal dengan pesan `Hanya Admin yang dapat menolak berkas.`
- Expected: super admin mewarisi kemampuan admin sesuai route middleware dan `User::canAccessAdminArea()`.
- Impact: inkonsistensi antara route authorization dan service authorization.
- Follow-up status: fixed and covered by `RejectionServiceTest::test_super_admin_can_request_berkas_revision`.

### P2 - IPM file upload component kehilangan marker reusable Metronic

- File: `resources/views/components/ui/file-upload.blade.php`
- Failing test: `tests/Feature/legacy reactive layer/PesantrenIpmFlowTest.php`
- Actual: markup wrapper hanya memuat `class="spm-file-upload"`.
- Expected: markup mengandung `data-ui-file-upload="metronic"`.
- Impact: kontrak UI/test untuk reusable component pecah, walaupun upload PDF IPM masih berhasil.
- Follow-up status: fixed and covered by `PesantrenIpmFlowTest::test_ipm_uses_reusable_metronic_file_components_without_emoji_locks`.

### P2 - Admin banding detail menampilkan `-` untuk asesor walau assignment ada

- File: `resources/views/livewire/pages/admin/banding-detail.blade.php`
- Actual: section `Penugasan Asesor` menampilkan `Tipe 1 -` dan `Tipe 2 -`.
- DB evidence: banding ID `3` memiliki assessment dengan asesor `E2E-20260604-154424 Ketua Asesor, M.Pd.` dan `E2E-20260604-154424 Anggota Asesor, M.Pd.`
- Root cause observed: view memakai `$assessment->nilai ?? '-'`, bukan nama asesor.
- Impact: admin tidak melihat nama tim asesor pada detail banding.
- Follow-up status: fixed and covered by `AdminBandingDetailTest::test_detail_shows_assigned_assessor_names`.

## Acceptance Notes

- Core state machine dan service workflow utama lulus.
- Role UI dan authorization utama lulus, kecuali gap super admin pada service action `rejectBerkas`.
- Browser verification dilakukan pada hasil data E2E yang dibuat lewat service layer, bukan dengan mengklik setiap transisi satu per satu di UI.
- Data audit dibiarkan di DB lokal dengan prefix `E2E-20260604-154424` agar dapat direview ulang.

