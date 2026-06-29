# MVP 98 Percent Plan - 29 Juni 2026

## Tujuan

Mendorong sistem `spm_fix` dari kondisi MVP fungsional saat ini menuju **MVP 98% siap demo besar / siap hardening staging** dengan fokus pada:

1. stabilitas flow bisnis inti,
2. penutupan gap backend per modul,
3. sinkron frontend-backend,
4. UI polish seluruh dashboard role,
5. pembersihan sisa legacy reactive layer.

## Baseline Sekarang

### Estimasi Kelayakan MVP Saat Ini

- Estimasi MVP saat ini: **78%**
- Definisi MVP pada plan ini: **alur inti akreditasi end-to-end sampai hasil akhir + SK terbit, role utama usable, guard backend utama aman, UI dashboard role cukup rapi untuk dipakai operasional**

### Dasar Penilaian

- Flow bisnis inti terdokumentasi di `README.md` dan `docs/architecture.md`.
- Happy path workflow inti lulus di `tests/Feature/AkreditasiWorkflow/FullHappyPathTest.php`.
- Readiness repository lulus pada checkpoint `ProductionReadinessTest`.
- Audit backend per role sudah memetakan gap P0/P1/P2 di `docs/backend-role-module-audit-plan-2026-06-08.md`.
- Audit frontend dan positioning UI sudah tersedia di `docs/audit-frontend-2025-06-08.md` dan `docs/frontend-ui-ux-positioning.md`.

### Target 98% Artinya

Status 98% pada plan ini berarti:

- semua flow bisnis inti role utama lulus audit dan regression,
- tidak ada gap P0 tersisa,
- gap P1 yang menyentuh data integrity, permission, dan route contract sudah ditutup,
- dashboard semua role sudah konsisten, usable, dan cukup polished,
- sisa legacy reactive layer sudah dihapus atau dinyatakan aman dengan bukti,
- hasil regression backend dan UI check menghasilkan blocker minor saja.

## Status Plan Saat Ini

Plan ini **belum final sebagai plan eksekusi tervalidasi**.

Plan ini baru final pada level:

- struktur kerja,
- pemecahan area audit,
- urutan prioritas besar,
- definisi target 98%.

Plan ini belum final pada level evidence karena dokumen turunan masih perlu diisi dari hasil audit repo nyata.

## Yang Perlu Difinalisasi Sebelum Eksekusi

### 1. Finalisasi Evidence Flow Bisnis

- [ ] Isi `docs/business-flow-audit-matrix.md` untuk semua status `6` sampai `-2`.
- [ ] Konfirmasi tiap status dari source nyata: route, controller, service, view, policy, test.
- [ ] Tandai gap yang masih hidup vs gap yang sudah tertutup.

### 2. Finalisasi Scorecard MVP

- [ ] Ubah skor baseline per area jika evidence terbaru mengoreksi estimasi awal.
- [ ] Tandai item `Open` menjadi `Pass`, `Fail`, atau `Deferred with reason`.
- [ ] Tetapkan daftar blocker resmi untuk fase eksekusi pertama.

### 3. Finalisasi Audit UI Dashboard

- [ ] Isi checklist per role dari halaman nyata.
- [ ] Pisahkan `must-fix` vs `nice-to-have`.
- [ ] Tetapkan urutan halaman mana yang dipoles dulu.

### 4. Finalisasi Audit legacy reactive layer Removal

- [ ] Jalankan scan repo nyata untuk `legacy reactive layer`, `legacy UI component layer`, `legacy-poll:`, dan `@livewire`.
- [ ] Kelompokkan temuan menjadi runtime, docs, test, atau dead residue.
- [ ] Putuskan mana yang harus dihapus sebelum phase execute.

### 5. Finalisasi Scope Execute

- [ ] Tetapkan apakah execute dimulai dari backend flow atau legacy reactive layer cleanup.
- [ ] Tetapkan batch kerja kecil per phase.
- [ ] Tetapkan definisi done per batch.

## Ringkasan Flow Bisnis Saat Ini

### Alur Status Inti

- `6` Pengajuan
- `5` Verifikasi Berkas
- `4` Review Asesor
- `3` Visitasi
- `2` Penilaian Pasca Visitasi
- `1` Validasi Admin
- `0` Terakreditasi
- `-1` Ditolak Final
- `-2` Banding

### Ringkasan Peran

- `Pesantren`: isi data, upload dokumen, submit, lihat hasil, banding.
- `Asesor`: review substansi, visitasi, input nilai, upload laporan.
- `Admin`: review berkas, assign asesor, validasi admin, finalisasi, terbitkan SK.
- `Super Admin`: kontrol penuh governance dan modul sistem.

## Temuan Per Modul

### 1. Workflow Akreditasi Inti

Status: **cukup kuat, belum final**

Kekuatan:

- state machine ada,
- happy path end-to-end sudah lulus,
- dokumen pasca visitasi sudah punya guard,
- audit trail dan locking sudah ada.

Masalah:

- kontrak finalisasi NV admin belum rapat untuk kasus NV berbeda dari NK,
- beberapa action nyata di HTTP belum cukup terlindungi oleh regression,
- beberapa flag view/action masih berisiko tidak sinkron dengan state service.

Dampak:

- flow inti terlihat sehat di service, tapi masih bisa retak di level form, route, redirect, dan permission.

### 2. Modul Pesantren

Status: **usable, perlu audit ulang per status**

Kekuatan:

- alur input dasar dan submit sudah ada,
- tenant boundary pada level model/policy sudah diaudit sebelumnya.

Masalah potensial yang perlu cek ulang:

- partial unlock dan submit perbaikan,
- status perbaikan dan visibilitas menu,
- sinkron hasil akhir yang boleh dilihat pesantren vs data mentah internal.

Dampak:

- user pesantren bisa mengalami alur yang membingungkan setelah reject/perbaikan/banding jika contract UI belum sinkron.

### 3. Modul Asesor

Status: **cukup kuat, rawan mismatch action**

Kekuatan:

- review, scoring, visitasi, dan upload laporan sudah tercover di business flow.

Masalah:

- jadwal visitasi perlu diselaraskan penuh dengan status bisnis,
- beberapa aksi reject/accept/finalize/save nilai butuh regression HTTP,
- profile upload rollback perlu dicek ulang untuk error path.

Dampak:

- asesor bisa tertahan atau salah jalur pada titik operasional yang kritis.

### 4. Modul Admin

Status: **modul paling kritis untuk ditutup gap-nya**

Kekuatan:

- admin memegang kontrol verifikasi, validasi NV, final decision, dan SK.

Masalah:

- kontrak reason NV,
- final flag NV global,
- orphan file pada upload sertifikat/master dokumen,
- trash route contract,
- permission retry/dismiss failed notifications,
- HTTP coverage action admin belum lengkap.

Dampak:

- walau happy path lulus, area admin masih jadi sumber risiko bisnis utama.

### 5. Modul Super Admin / Governance

Status: **fungsi ada, governance belum rapat**

Kekuatan:

- super admin sudah jadi god mode pada banyak area,
- menu dan bypass model sudah ada.

Masalah:

- mutasi role belum konsisten super-admin-only,
- role inti `1..4` belum cukup dilindungi,
- coverage CRUD role dan permission matrix perlu dipertebal,
- kebijakan notifikasi operasional untuk super admin perlu diputuskan tegas.

Dampak:

- bukan blocker happy path user biasa, tapi blocker governance sistem.

### 6. Frontend / Dashboard Role

Status: **fungsional, belum seragam polished**

Masalah:

- masih ada risiko kontrak frontend-backend tidak sinkron,
- kemungkinan sisa pola legacy reactive layer/migration residue,
- dashboard role belum dipastikan konsisten secara spacing, CTA hierarchy, loading/error state, empty state, badge status, dan data density.

Dampak:

- sistem bisa lolos backend test, tapi tetap terasa belum siap dipakai intensif.

### 7. legacy reactive layer Removal

Status: **harus diaudit ulang sampai bersih**

Masalah:

- dokumentasi lama masih menyebut legacy reactive UI layer,
- perlu cek apakah masih ada dependency runtime, directive, event, polling, binding, atau referensi legacy reactive layer yang tersisa.

Dampak:

- sisa legacy reactive layer bisa membuat kontrak UI membingungkan, dead code, atau bug tersembunyi.

## Prioritas P0 P1 P2

### P0 - Wajib Beres Dulu

1. Rapikan kontrak `NV` admin ketika nilai berbeda dari `NK`.
2. Tutup regression HTTP untuk action inti per role pada flow akreditasi.
3. Audit dan perbaiki action asesor yang harus sinkron dengan state bisnis, terutama visitasi dan finalisasi.
4. Audit flow bisnis per status dari submit sampai SK dan banding, lalu tandai mismatch implementasi vs aturan.
5. Pastikan hasil akhir yang dilihat pesantren sesuai policy bisnis dan tidak bocor data internal mentah.

### P1 - Sangat Penting

1. Kunci mutasi role sebagai super-admin-only atau tetapkan delegasi resmi dengan bukti.
2. Lindungi role inti `1..4` dari rename/delete berbahaya.
3. Pisahkan permission mutasi untuk trash dan failed notifications.
4. Rapikan rollback file upload untuk SK/master dokumen/profile yang rawan orphan.
5. Sinkronkan seluruh route contract frontend-backend yang masih mismatch.
6. UI polish dashboard semua role untuk usability inti.
7. Audit dan hapus seluruh sisa legacy reactive layer yang tidak dipakai.

### P2 - Penyempurnaan

1. Allowlist sorting field di semua list sensitif.
2. Konsistensi copywriting, badge, color semantics, icon, tab order, dan empty states.
3. Rapikan dokumen arsitektur agar tidak lagi menyebut legacy reactive layer jika memang sudah full Blade.
4. Review minor performance dan cache/view contracts setelah UI final.

## Checklist Action Plan

## Phase 1 - Audit Ulang Flow Bisnis Nyata

- [ ] Petakan flow bisnis per status: `6, 5, 4, 3, 2, 1, 0, -1, -2`.
- [ ] Cocokkan tiap status dengan route, controller action, service method, view action, dan policy.
- [ ] Buat tabel `expected business rule` vs `implemented behavior`.
- [ ] Tandai mismatch per role: Pesantren, Asesor, Admin, Super Admin.
- [ ] Tetapkan daftar bug flow yang benar-benar masih hidup pada code terbaru.

## Phase 2 - Fix P0 Backend Flow

- [ ] Finalisasi kontrak NV admin + alasan perubahan.
- [ ] Tambah regression HTTP untuk action inti admin.
- [ ] Tambah regression HTTP untuk action inti asesor.
- [ ] Cek flow pesantren untuk submit, perbaikan, banding, dan hasil akhir.
- [ ] Pastikan semua blocker P0 berubah menjadi verified pass.

## Phase 3 - Fix P1 Governance dan Integrity

- [ ] Kunci role mutation policy.
- [ ] Proteksi role inti.
- [ ] Pisahkan permission trash mutate.
- [ ] Pisahkan permission failed notification mutate.
- [ ] Rapikan rollback upload file penting.
- [ ] Audit route contract frontend-backend dan sinkronkan.

## Phase 4 - UI Polish Dashboard All Roles

- [ ] Audit dashboard `Super Admin`.
- [ ] Audit dashboard `Admin`.
- [ ] Audit dashboard `Asesor`.
- [ ] Audit dashboard `Pesantren`.
- [ ] Audit dashboard global shared layout, sidebar, topbar, cards, tables, forms, tabs, badges, alerts.
- [ ] Ratakan visual hierarchy, spacing, CTA priority, empty state, loading state, error state.
- [ ] Pastikan wording status konsisten dengan flow bisnis.
- [ ] Buat daftar polish `must-fix` vs `nice-to-have`.

## Phase 5 - Recheck and Takeout All legacy reactive layer

- [ ] Cari semua referensi `legacy reactive layer`, `legacy UI component layer`, `legacy-poll:`, `@livewire`, event, polling, binding.
- [ ] Bedakan mana yang aktif runtime, mana yang hanya jejak dokumentasi/test lama.
- [ ] Hapus dependency, config, asset, dan referensi yang sudah mati.
- [ ] Rapikan docs arsitektur agar sesuai implementasi final.
- [ ] Jalankan regression setelah cleanup.

## Phase 6 - Final Recheck menuju 98%

- [ ] Jalankan regression backend inti.
- [ ] Jalankan regression auth, permission, workflow, notifications, production readiness.
- [ ] Review ulang dashboard role dengan checklist UI.
- [ ] Review ulang business flow dari sisi user nyata per role.
- [ ] Tetapkan persentase akhir MVP dan daftar residual issues jika masih ada.

## Flow Bisnis Audit Check and Fixing

### Checklist Audit Per Status

#### Status 6 - Pengajuan
- [ ] Pesantren hanya bisa submit jika data minimum lengkap.
- [ ] Admin menerima antrian pengajuan secara benar.
- [ ] Tidak ada bypass upload/submit lintas tenant.

#### Status 5 - Verifikasi Berkas
- [ ] Admin bisa approve/reject/open review secara konsisten.
- [ ] Assign asesor valid dan tidak salah role.
- [ ] Alasan reject dan jalur perbaikan sinkron dengan UI.

#### Status 4 - Review Asesor
- [ ] Asesor hanya lihat item penugasannya.
- [ ] Jadwal visitasi hanya bisa dibuat pada state yang benar.
- [ ] Reject/accept/finalize dokumen konsisten dengan service rule.

#### Status 3 - Visitasi
- [ ] Visitasi bisa dijadwalkan ulang bila diizinkan bisnis.
- [ ] Konfirmasi selesai visitasi sinkron dengan syarat state.
- [ ] Semua pihak yang relevan melihat status yang sama.

#### Status 2 - Penilaian Pasca Visitasi
- [ ] Input `NA1/NA2/NK` valid.
- [ ] Dokumen pasca visitasi wajib lengkap.
- [ ] Finalisasi asesor tidak bisa bypass guard dokumen.

#### Status 1 - Validasi Admin
- [ ] Input `NV` valid.
- [ ] Alasan perubahan `NV != NK` tercatat benar.
- [ ] Flag final global tidak menyala prematur.
- [ ] SK tidak bisa terbit bila syarat belum lengkap.

#### Status 0 - Terakreditasi
- [ ] Pesantren hanya lihat hasil yang diizinkan.
- [ ] SK/sertifikat/rekomendasi tampil konsisten.

#### Status -1 - Ditolak Final
- [ ] Alasan penolakan tampil benar.
- [ ] Jalur banding hanya terbuka bila memenuhi rule.

#### Status -2 - Banding
- [ ] Submit banding hanya pada window valid.
- [ ] Reviewer banding valid.
- [ ] Keputusan banding hanya kembali ke state yang sah.

## Deliverables

### Dokumen yang perlu dihasilkan

1. `docs/mvp-98-audit-scorecard.md`
   - skor MVP per area,
   - checklist pass/fail,
   - residual risk.

2. `docs/business-flow-audit-matrix.md`
   - matriks flow bisnis per status,
   - expected vs actual,
   - bug list,
   - evidence file/test.

3. `docs/ui-polish-dashboard-plan.md`
   - checklist polish semua role,
   - severity visual/usability,
   - before/after target.

4. `docs/livewire-removal-audit.md`
   - semua temuan referensi legacy reactive layer,
   - status aktif/mati,
   - tindakan remove/keep.

## Definition of Done for 98%

Sistem bisa dianggap mencapai target plan ini bila:

- seluruh P0 selesai dan verified,
- P1 kritis governance/data integrity selesai,
- dashboard semua role lulus audit usability inti,
- tidak ada sisa legacy reactive layer aktif yang tidak disengaja,
- flow bisnis per status punya bukti pass/fail yang jelas,
- persentase MVP dinilai ulang minimal **95-98%**, dengan sisa issue hanya minor polish atau non-blocking improvement.

## Urutan Eksekusi Rekomendasi

1. Audit flow bisnis nyata per status.
2. Tutup P0 workflow/backend.
3. Tutup P1 governance/integrity.
4. Audit dan polish dashboard semua role.
5. Recheck dan takeout seluruh legacy reactive layer.
6. Jalankan final regression dan nilai ulang MVP.

