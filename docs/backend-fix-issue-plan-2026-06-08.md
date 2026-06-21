# Backend Fix Issue Plan - 8 Juni 2026

Dokumen ini menjadi acuan perbaikan backend setelah audit role Pesantren, Asesor, Admin, dan Super Admin. Fokus dokumen ini adalah urutan eksekusi, prioritas, scope fix, test wajib, dan catatan sinkron dengan agent frontend.

Dokumen rujukan:

- `docs/backend-role-module-audit-plan-2026-06-08.md`
- `docs/audit-frontend-2025-06-08.md`
- `docs/perubahan-test-2025-06-08.md`

## 1. Prinsip Eksekusi

1. Backend diperbaiki role-by-role, dimulai dari flow bisnis yang paling menghambat.
2. P0 wajib ditutup lebih dulu sebelum masuk hardening P1/P2.
3. Setiap fix backend harus punya regression test, terutama HTTP Feature test karena sistem sudah full Blade.
4. Jangan mengubah frontend kecuali hanya dokumentasi sinkron atau kontrak route/field yang perlu diberitahu ke agent frontend.
5. Setelah setiap batch selesai, update dokumen audit dan dokumen ini.

## 2. Urutan Prioritas

| Urutan | Role/Area | Fokus | Prioritas | Alasan |
|---:|---|---|---|---|
| 1 | Pesantren | Partial unlock dan submit perbaikan | P0 | Flow perbaikan data bisa berhenti |
| 2 | Asesor | Jadwal visitasi dan reject dokumen | P0/P1 | Controller/view belum selaras dengan state machine/service |
| 3 | Admin | Kontrak NV dan finalisasi validasi admin | P0/P1 | Validasi akhir perlu audit reason yang benar |
| 4 | Shared HTTP Regression | Action POST semua role | P1 | Menjamin migrasi full Blade tidak memutus form/action |
| 5 | Permission Hardening | Trash, failed notifications, account | P1/P2 | Permission matrix perlu lebih presisi |
| 6 | Super Admin Governance | Role inti, role mutation, notification recipients | P1/P2 | Mencegah role governance merusak sistem |
| 7 | Query/Filter Hygiene | Sort allowlist dan filter mismatch | P2 | Mencegah query error dan filter palsu |

## 3. Batch PES - Pesantren

Tujuan: memastikan Pesantren bisa menjalankan flow perbaikan setelah berkas/section ditolak tanpa terganggu lock global.

Backlog terkait: `BE-013`, `BE-014`, `BE-015`, `BE-016`

### Scope Fix

| ID Audit | Issue | Target Fix |
|---|---|---|
| PES-001 | Controller Profile/IPM/SDM/EDPM memblokir lebih awal saat `is_locked` walau section direject sudah partial unlock | Controller harus mengizinkan update section yang sedang unlocked oleh active rejection |
| PES-002 | Belum ada route/action submit perbaikan dari Pesantren | Tambah route/controller action submit perbaikan |
| PES-003 | Menu Status Perbaikan belum membaca active structured rejection dengan benar | Filter/list perbaikan membaca active rejection, bukan hanya status `-1` |
| PES-005 | `sortField` list akreditasi tanpa allowlist | Tambah allowlist sort field |

### Test Wajib

| Test | Ekspektasi |
|---|---|
| Pesantren locked tanpa partial unlock | Update Profile/IPM/SDM/EDPM ditolak |
| Pesantren locked dengan section unlocked | Section yang direject bisa diupdate |
| Pesantren submit perbaikan | Active rejection berubah submitted dan notifikasi terkirim |
| Pesantren non-owner submit perbaikan | Ditolak |
| Status Perbaikan list | Menampilkan akreditasi dengan active rejection |
| Sort invalid | Fallback ke sort default, tidak error |

### Kontrak Backend-Frontend

- Rekomendasi route: `POST /pesantren/akreditasi/perbaikan/submit`
- Rekomendasi route name: `pesantren.akreditasi.submit-perbaikan`
- Field minimal: `akreditasi_id`
- Frontend jangan finalisasi UI tombol submit perbaikan sebelum route ini tersedia.

## 4. Batch ASE - Asesor

Tujuan: menyelaraskan action Asesor dengan state machine dan service.

Backlog terkait: `BE-017`, `BE-018`, `BE-019`, `BE-020`, `BE-021`, `BE-022`

### Scope Fix

| ID Audit | Issue | Target Fix |
|---|---|---|
| ASE-001 | `canScheduleVisitasi` memakai status `5`, service butuh status `4` | Ubah flag controller/view ke status Review Asesor `4` |
| ASE-002 | Reject dokumen flag mengizinkan status `5` dan `4`, service hanya `4` | Selaraskan flag reject hanya status `4` |
| ASE-003 | HTTP POST coverage action Asesor belum lengkap | Tambah Feature tests action Asesor |
| ASE-004 | Upload profile Asesor belum rollback file baru saat update gagal | Tambah cleanup file baru pada failure |
| ASE-005 | Sort list tugas Asesor tanpa allowlist | Tambah allowlist |
| ASE-006 | Password profile Asesor tidak konsisten dengan profile umum | Putuskan: hapus field password atau tambah validasi current password/confirmation |

### Test Wajib

| Test | Ekspektasi |
|---|---|
| Jadwal visitasi status `4` Asesor 1 | Bisa melihat dan submit jadwal |
| Jadwal visitasi status salah | Ditolak |
| Jadwal oleh Asesor 2/non-assigned | Ditolak |
| Reject dokumen status `4` Asesor 1 | Berhasil membuat rejection |
| Reject dokumen status `5` | Tidak tersedia/ditolak |
| Accept perbaikan | Hanya Asesor 1 dan rejection submitted |
| Confirm visitasi selesai | Hanya status/tanggal valid |
| Save/finalize NA/NK | Actor dan status sesuai service |
| Upload laporan individu/kelompok | Individu oleh assigned asesor, kelompok hanya Asesor 1 |

### Kontrak Backend-Frontend

- Tombol Jadwal Visitasi mengikuti status Review Asesor `4`.
- Tombol reject dokumen mengikuti status Review Asesor `4`.
- UI profile Asesor harus mengikuti keputusan password policy backend.

## 5. Batch ADM - Admin

Tujuan: memperkuat kontrak HTTP Blade untuk validasi admin, upload SK, permission action, dan list management.

Backlog terkait: `BE-023`, `BE-024`, `BE-025`, `BE-026`, `BE-027`, `BE-028`, `BE-029`, `BE-031`

### Scope Fix

| ID Audit | Issue | Target Fix |
|---|---|---|
| ADM-001 | Finalisasi NV belum punya reason saat NV berbeda dari NK | Tambah field/kontrak reason dan kirim ke `saveNV()` |
| ADM-002 | `is_nv_final` bisa true sebelum semua NV final valid | Set true hanya setelah jumlah final lengkap |
| ADM-003 | Upload sertifikat SK bisa yatim jika `issueSK()` gagal | Cleanup file baru pada exception |
| ADM-004 | Master dokumen bisa hapus file lama sebelum DB update sukses | Ubah pola rollback file |
| ADM-005 | Trash restore/force-delete masih pakai permission view | Gunakan permission mutasi eksplisit |
| ADM-006 | Failed notification retry/dismiss masih pakai permission view | Gunakan permission action eksplisit |
| ADM-007 | Filter Asesor Admin key mismatch | Selaraskan key controller-service-repository |
| ADM-008 | Banyak list/export tanpa sort allowlist | Tambah allowlist per modul |
| ADM-011 | Kontrak route Trash frontend/backend tidak sinkron | Tetapkan route final dan test HTTP |

### Test Wajib

| Test | Ekspektasi |
|---|---|
| Save NV draft | Berhasil saat status Validasi Admin |
| Finalize NV sama NK | Berhasil tanpa reason |
| Finalize NV beda NK tanpa reason | Ditolak dengan pesan jelas |
| Finalize NV beda NK dengan reason | Berhasil dan audit trail tercatat |
| Final flag | `is_nv_final` true hanya saat semua butir final |
| Issue SK gagal | File sertifikat baru dibersihkan |
| Master dokumen update gagal | File lama tetap ada, file baru dibersihkan |
| Trash view-only | Tidak bisa restore/force-delete |
| Failed notification view-only | Tidak bisa retry/dismiss |
| Filter Asesor | Status/peran/penugasan bekerja |
| Sort invalid | Fallback default |

### Kontrak Backend-Frontend

- Form NV perlu field reason saat nilai berbeda dari NK.
- Trash backend saat ini memakai `POST admin.trash.restore` dan `POST admin.trash.force-delete` dengan body `id`.
- Failed notifications gunakan route name backend dan tunggu permission hardening.

## 6. Batch SA - Super Admin

Tujuan: memperkuat Super Admin sebagai governance role tanpa membuat role inti mudah rusak.

Backlog terkait: `BE-001`, `BE-030`, `BE-032`, `BE-033`, `BE-034`, `BE-035`

### Scope Fix

| ID Audit | Issue | Target Fix |
|---|---|---|
| SA-001 | Banyak notifikasi operasional hanya ke `role_id = 1` | Putuskan apakah Super Admin ikut menerima notifikasi Admin; jika ya gunakan admin-area recipient helper |
| SA-002 | Role mutation belum konsisten super-admin-only | Semua mutation role harus super-admin-only atau keputusan delegasi didokumentasikan |
| SA-003 | Role inti belum dilindungi dari delete/rename berbahaya | Guard role id `1..4` |
| SA-004 | HTTP coverage role management belum lengkap | Tambah HTTP tests role CRUD dan permission matrix |
| SA-005 | Permission account masih kurang granular | Tambah permission update/unlink atau rename policy menjadi `account.manage` |
| SA-008 | Role/account sorting tanpa allowlist | Tambah allowlist |

### Test Wajib

| Test | Ekspektasi |
|---|---|
| Super Admin access role index | 200 OK |
| Regular Admin access role index | 403 |
| Super Admin create/update/delete role non-inti | Berhasil |
| Super Admin delete role inti `1..4` | Ditolak |
| Permission matrix save | Role non-super-admin berubah sesuai input |
| Role id `4` matrix | Tidak muncul/tidak bisa diubah |
| Notification recipient policy | Sesuai keputusan produk |

### Kontrak Backend-Frontend

- Permission matrix tidak mengelola role id `4`.
- Tombol edit/delete role inti sebaiknya disabled sampai backend guard tersedia.
- Jika Super Admin harus menerima notifikasi workflow, frontend baru boleh menyamakan indikator notifikasi dengan Admin setelah backend recipient diperbaiki.

## 7. Batch Shared - Permission Hardening

Tujuan: memastikan permission matrix benar-benar bisa membedakan hak lihat dan hak mutasi.

Backlog terkait: `BE-026`, `BE-027`, `BE-035`

| Area | Kondisi Saat Ini | Target |
|---|---|---|
| Trash | `view` bisa restore/force-delete | Pisahkan `trash.view`, `trash.restore`, `trash.purge` |
| Failed Notifications | `view` bisa retry/dismiss | Pakai `notification.retry`, tambah/putuskan `notification.dismiss` |
| Account | `account.create` dipakai create/update/unlink | Tambah `account.update`, `account.unlink_sso`, atau rename ke `account.manage` |

## 8. Batch Shared - Query dan Filter Hygiene

Tujuan: mencegah query error, filter palsu, dan parameter sort liar.

Backlog terkait: `BE-016`, `BE-021`, `BE-028`, `BE-029`, `BE-034`

| Area | Target |
|---|---|
| Pesantren akreditasi list | Allowlist `created_at`, `updated_at`, `status`, `nomor_sk`, `peringkat` |
| Asesor task list | Allowlist kolom assessment/akreditasi yang valid |
| Admin akreditasi list/export | Allowlist sort field |
| Admin pesantren/asesor/accounts/roles/master list | Allowlist sort field |
| Admin Asesor filter | Key filter controller dan repository harus sama |

## 9. Definition of Done Per Batch

Batch dianggap selesai jika:

1. Fix backend selesai tanpa mengubah behavior di luar scope.
2. Regression test role/module terkait hijau.
3. Tidak ada route/form field yang berubah tanpa catatan untuk frontend.
4. Dokumen audit dan dokumen plan ini di-update.
5. Jika ada perubahan kontrak frontend, catatan sinkron masuk ke `docs/audit-frontend-2025-06-08.md`.

## 10. Rekomendasi Mulai Eksekusi

Urutan kerja yang direkomendasikan:

1. `PES-A`: HTTP regression partial unlock dan submit perbaikan.
2. `PES-B`: Fix controller partial unlock.
3. `PES-C`: Tambah submit perbaikan route/action.
4. `ASE-A`: HTTP regression jadwal/reject.
5. `ASE-B`: Fix status flag jadwal dan reject.
6. `ADM-A`: HTTP regression NV dan finalisasi admin.
7. `ADM-B`: Fix reason NV dan final flag.
8. `SA-A`: HTTP regression role governance.
9. `SA-B`: Guard role inti.
10. Shared hardening permission dan sort/filter.

## 11. Status Saat Ini

| Area | Status Audit | Status Fix |
|---|---|---|
| Pesantren | Selesai | Belum mulai |
| Asesor | Selesai | Belum mulai |
| Admin | Selesai | Belum mulai |
| Super Admin | Selesai | Belum mulai |
| Frontend sync notes | Tercatat sebagian | Perlu update setelah tiap fix |

