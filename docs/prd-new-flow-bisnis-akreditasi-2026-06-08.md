# PRD - New Flow Bisnis Akreditasi

Tanggal: 8 Juni 2026

Status: Draft finalisasi untuk rebuild Akreditasi V2

Pemilik produk: Tim LP2M/SPM

Tujuan dokumen: menjadi acuan bersama untuk backend, frontend, QA, dan sinkronisasi antar agent sebelum implementasi rebuild Akreditasi V2 dimulai.

Rujukan:

- New Flow Bisnis Akreditasi
- Backend Role Module Audit Plan
- Backend Fix Issue Plan
- Audit Frontend

## Problem Statement

Flow akreditasi saat ini terlalu membebani Pesantren di awal karena Pesantren harus melengkapi banyak data sebelum pengajuan dapat diproses. Di sisi lain, pembagian tanggung jawab antara Admin, Ketua Asesor, dan Anggota Asesor belum cukup tajam untuk memisahkan review administratif, review kelayakan visitasi, proses visitasi, penilaian, dan validasi akhir.

Sistem juga membutuhkan aturan bisnis yang dapat dikonfigurasi oleh Super Admin, seperti deadline per fase, batas maksimal siklus perbaikan, kewajiban dokumen, kebijakan notifikasi, dan aturan lock/unlock. Tanpa pengaturan ini, perubahan kebijakan akreditasi akan terlalu bergantung pada perubahan code.

Rework sistem tidak boleh mengubah struktur input dan rumus penilaian yang sudah menjadi dasar akreditasi existing. Perubahan utama berada pada urutan workflow, pembagian tanggung jawab actor, penguncian/unlock section, visibility dokumen, konfigurasi Super Admin, audit trail, dan stabilitas backend contract.

Masalah utama yang ingin diselesaikan:

- Pesantren harus bisa mengajukan akreditasi hanya dengan profil awal yang cukup.
- IPM, EDPM/IPR, SDM, dan berkas pendukung harus masuk fase Assessment Awal setelah Admin menerima pengajuan.
- Admin perlu melakukan review tahap 1 sebelum menunjuk asesor.
- Ketua Asesor perlu melakukan review tahap 2 untuk menentukan layak visitasi.
- Perbaikan harus berbasis section/kategori yang ditolak, bukan membuka seluruh data.
- Ketua Asesor dan Anggota Asesor harus punya jobdesk penilaian yang jelas.
- Dokumen Kartu Kendali dan laporan asesor harus dipisahkan visibility-nya.
- Admin perlu memverifikasi hasil asesor dengan NV yang mirror dari NK, dengan audit reason jika NV berbeda.
- Super Admin perlu menu pengaturan flow agar deadline, batas revisi, dan aturan lain dapat dikelola tanpa perubahan code.
- Input existing untuk Pesantren, Asesor, dan Admin tidak boleh berubah secara bisnis, termasuk field, opsi jawaban, skala nilai, validasi, format dokumen, dan rumus penilaian.

## Solution

Sistem akan mengadopsi flow bisnis akreditasi baru yang lebih bertahap:

1. Pesantren melengkapi Profil Pesantren sebagai syarat awal pengajuan.
2. Pesantren submit pengajuan akreditasi.
3. Admin review pengajuan awal.
4. Jika diterima, Admin membuka atau menjadwalkan fase Assessment Awal.
5. Pesantren mengisi IPM, EDPM/IPR, SDM, dan berkas pendukung pada fase Assessment Awal.
6. Admin melakukan review berkas tahap 1.
7. Jika lolos, Admin menunjuk Ketua Asesor dan Anggota Asesor.
8. Ketua Asesor melakukan review berkas tahap 2 untuk menentukan layak visitasi.
9. Jika layak, Ketua Asesor menjadwalkan visitasi.
10. Visitasi dilakukan di luar sistem.
11. Ketua Asesor menandai Visitasi Selesai.
12. Ketua Asesor input NA1.
13. Anggota Asesor input NA2.
14. Setelah NA1 dan NA2 final, Ketua Asesor input NK dan catatan/rekomendasi sesuai form penilaian existing.
15. Pesantren upload Kartu Kendali untuk Admin.
16. Ketua Asesor upload laporan individu dan laporan kelompok untuk Admin.
17. Anggota Asesor upload laporan individu untuk Admin.
18. Ketua Asesor submit hasil visitasi.
19. Admin memverifikasi hasil asesor dan memberi NV.
20. Jika disetujui, Admin menerbitkan SK, masa berlaku, sertifikat, nilai akhir, peringkat, dan catatan rekomendasi yang boleh dilihat Pesantren.
21. Jika ditolak final, Pesantren dapat mengajukan banding sesuai aturan.

Super Admin akan mendapatkan menu pengaturan akreditasi untuk mengelola:

- Deadline workflow.
- Maksimal siklus perbaikan.
- Aturan submit.
- Aturan nilai.
- Aturan notifikasi.
- Aturan dokumen.
- Aturan lock/unlock.
- Kebijakan banding.

MVP Akreditasi V2 dianggap selesai hanya jika satu siklus akreditasi dapat berjalan end-to-end sampai Admin menerbitkan SK dan sertifikat, serta Pesantren dapat melihat atau mengunduh hasil akhirnya. Flow yang belum sampai penerbitan SK dan sertifikat tidak dianggap MVP, tetapi hanya milestone backend workflow.

## Product Requirements

### Preservasi Input dan Formula Existing

- Semua input existing milik Pesantren, Asesor, dan Admin wajib dipertahankan secara bisnis.
- Rework tidak boleh menghapus, mengganti makna, mengganti skala, mengganti opsi jawaban, atau mengganti aturan validasi input existing tanpa PRD terpisah.
- Input Pesantren yang terkait Profil Pesantren, IPM, EDPM/IPR, SDM, berkas pendukung, perbaikan section, Kartu Kendali, dan banding harus tetap mempertahankan struktur data existing.
- Input Ketua Asesor dan Anggota Asesor yang terkait review, catatan section, NA1, NA2, NK, rekomendasi, laporan individu, dan laporan kelompok harus tetap mempertahankan struktur data existing.
- Input Admin yang terkait review, approval, rejection, assignment asesor, NV, validasi akhir, SK, sertifikat, masa berlaku, peringkat, dan catatan rekomendasi harus tetap mempertahankan struktur data existing.
- Struktur komponen EDPM/IPR, butir penilaian, bobot, rumus NA1, NA2, NK, NV, konversi nilai akhir, dan peringkat akreditasi tidak boleh berubah dari sistem existing.
- Perubahan frontend boleh memperbaiki layout, grouping, instruksi, dan urutan tampil sesuai workflow baru, tetapi tidak boleh mengubah kontrak input bisnis.
- Sebelum implementasi, tim wajib melakukan inventory input existing per role dan menjadikannya baseline regression untuk sistem baru.

### Pengajuan Awal Pesantren

- Pesantren dapat submit pengajuan akreditasi setelah Profil Pesantren memenuhi field wajib minimum.
- Pesantren hanya boleh memiliki satu pengajuan akreditasi aktif yang belum terminal.
- IPM, EDPM/IPR, SDM, dan berkas pendukung tidak menjadi syarat submit pengajuan awal.
- Sistem harus menampilkan status kelengkapan profil yang cukup untuk pengajuan awal.
- Admin dapat menerima atau menolak pengajuan awal dengan alasan yang tercatat.
- Jika pengajuan awal ditolak, Pesantren hanya perlu memperbaiki profil dan submit ulang.

### Assessment Awal

- Assessment Awal hanya terbuka setelah Admin menerima pengajuan awal.
- Admin dapat menentukan tanggal atau deadline Assessment Awal sesuai aturan yang berlaku.
- Pada fase Assessment Awal, Pesantren mengisi IPM, EDPM/IPR, SDM, dan berkas pendukung.
- Pesantren dapat submit Assessment Awal setelah semua syarat pada fase tersebut terpenuhi.
- Sistem harus mencatat kapan Assessment Awal dibuka, deadline-nya, dan kapan Pesantren submit.

### Review Admin Tahap 1

- Admin melakukan review berkas tahap 1 setelah Pesantren submit Assessment Awal.
- Admin dapat approve tahap 1, meminta perbaikan section/kategori tertentu, atau menolak sesuai aturan bisnis.
- Permintaan perbaikan tahap 1 harus menyimpan section/kategori, catatan, actor, timestamp, dan siklus perbaikan.
- Sistem hanya membuka section/kategori yang diminta untuk diperbaiki.
- Setelah Pesantren submit perbaikan tahap 1, Admin dapat review ulang sampai batas siklus perbaikan tercapai.

### Assignment Asesor

- Admin hanya dapat menunjuk asesor setelah Review Admin Tahap 1 lolos.
- Assignment wajib membedakan Ketua Asesor dan Anggota Asesor.
- Satu proses akreditasi harus memiliki Ketua Asesor sebelum masuk Review Asesor Tahap 2.
- Sistem harus mencegah role Anggota Asesor mengambil aksi yang hanya menjadi wewenang Ketua Asesor.
- Reassign asesor hanya boleh dilakukan Admin dengan alasan wajib dan audit trail.
- Reassign setelah scoring dimulai hanya boleh sebagai exception sesuai policy, dengan perlindungan audit atas nilai/catatan yang sudah dibuat asesor lama.

### Review Asesor Tahap 2

- Ketua Asesor melakukan review kelayakan visitasi setelah assignment asesor selesai.
- Ketua Asesor dapat menyatakan layak visitasi atau tidak layak visitasi.
- Jika tidak layak visitasi, Ketua Asesor memilih section/kategori bermasalah dan memberi catatan perbaikan.
- Perbaikan tahap 2 harus terpisah dari perbaikan tahap 1 untuk kebutuhan laporan, notifikasi, deadline, dan batas siklus.
- Pesantren hanya dapat memperbaiki section yang dibuka pada perbaikan tahap 2.

### Visitasi

- Ketua Asesor menjadwalkan visitasi hanya setelah Review Asesor Tahap 2 menyatakan layak visitasi.
- Visitasi dilakukan di luar sistem.
- Ketua Asesor menjadi satu-satunya role yang dapat menandai Visitasi Selesai.
- Anggota Asesor tidak dapat menjadwalkan visitasi atau menandai Visitasi Selesai.

### Penilaian Pasca Visitasi

- Ketua Asesor input NA1.
- Anggota Asesor input NA2.
- Ketua Asesor input NK hanya setelah NA1 dan NA2 final.
- NK menggunakan struktur komponen penilaian EDPM/IPR existing.
- Rumus NA1, NA2, NK, NV, nilai akhir, dan peringkat akreditasi wajib sama dengan sistem existing.
- Ketua Asesor wajib mengisi catatan butir/rekomendasi sesuai kebutuhan form penilaian existing.
- Ketua Asesor dapat submit hasil visitasi hanya setelah nilai, catatan, rekomendasi, dan dokumen yang diwajibkan sudah lengkap.

### Dokumen Pasca Visitasi

- Pesantren upload Kartu Kendali untuk Admin.
- Kartu Kendali tidak boleh terlihat oleh Asesor.
- Ketua Asesor upload laporan individu dan laporan kelompok untuk Admin.
- Anggota Asesor upload laporan individu untuk Admin.
- Laporan asesor tidak boleh terlihat oleh Pesantren.
- Admin dapat melihat semua dokumen pasca visitasi yang ditujukan untuk validasi akhir.

### Validasi Akhir Admin

- Admin menerima hasil visitasi setelah Ketua Asesor submit hasil visitasi.
- NV default mirror dari NK.
- Admin dapat approve NV sama dengan NK tanpa alasan tambahan.
- Jika Admin mengubah NV sehingga berbeda dari NK, sistem wajib meminta alasan sesuai aturan Super Admin.
- Admin dapat menerbitkan SK, masa berlaku, sertifikat, nilai akhir, peringkat, dan catatan rekomendasi yang boleh dilihat Pesantren.
- Jika validasi akhir ditolak, Pesantren menerima status ditolak final dan dapat masuk flow banding jika memenuhi aturan.

### Pengaturan Super Admin

- Super Admin dapat mengatur deadline per fase workflow.
- Super Admin dapat mengatur maksimal siklus perbaikan tahap 1 dan tahap 2.
- Super Admin dapat mengatur akibat ketika batas siklus perbaikan tercapai.
- Super Admin dapat mengatur syarat submit pada tiap fase.
- Super Admin dapat mengatur apakah Kartu Kendali wajib sebelum Ketua Asesor submit hasil visitasi atau sebelum Admin validasi akhir.
- Super Admin dapat mengatur apakah laporan individu/kelompok wajib sebelum submit hasil visitasi.
- Super Admin dapat mengatur kebijakan NV, termasuk boleh/tidaknya override dan mode alasan override.
- Super Admin dapat mengatur penerima notifikasi workflow, termasuk apakah Super Admin menerima notifikasi Admin.
- Super Admin dapat mengatur reminder deadline dan overdue action.
- Semua perubahan pengaturan Super Admin wajib memiliki audit log.

## Finalization Contracts

Bagian ini mengunci keputusan yang sebelumnya masih terbuka agar PRD dapat dipakai sebagai blueprint rebuild, bukan hanya dokumen diskusi.

### MVP Boundary

MVP wajib mencakup alur end-to-end berikut:

```text
Profil minimum Pesantren
  -> Pengajuan awal
  -> Review awal Admin
  -> Assessment Awal
  -> Review Admin Tahap 1
  -> Assignment Ketua Asesor dan Anggota Asesor
  -> Review Ketua Asesor Tahap 2
  -> Jadwal Visitasi
  -> Visitasi Selesai
  -> NA1, NA2, NK
  -> Dokumen pasca visitasi
  -> Submit hasil visitasi
  -> Validasi akhir Admin dan NV
  -> SK dan sertifikat terbit
  -> Pesantren melihat hasil akhir
```

Tidak boleh ada release MVP yang berhenti sebelum SK dan sertifikat dapat diterbitkan dan diakses Pesantren.

### State Machine V2

State machine V2 harus memakai status bernama sebagai sumber kebenaran. Status numerik lama hanya boleh dipakai sebagai compatibility layer jika masih diperlukan untuk migrasi atau laporan.

| Status V2 | Aktor utama | Makna |
|---|---|---|
| `draft_profile` | Pesantren | Pesantren melengkapi profil minimum sebelum pengajuan awal |
| `initial_submitted` | Pesantren/Admin | Pengajuan awal masuk antrean review Admin |
| `initial_rejected` | Admin/Pesantren | Pengajuan awal ditolak dan Pesantren memperbaiki profil |
| `assessment_open` | Pesantren/Admin | Assessment Awal terbuka untuk IPM, EDPM/IPR, SDM, dan berkas |
| `admin_stage_1_review` | Admin | Assessment Awal sudah disubmit dan menunggu review Admin |
| `admin_stage_1_correction` | Admin/Pesantren | Section tertentu dibuka untuk perbaikan tahap 1 |
| `admin_stage_1_limit_review` | Admin/Super Admin | Batas perbaikan tahap 1 tercapai dan butuh keputusan eksplisit |
| `assessor_assignment` | Admin | Review tahap 1 lolos dan Admin menunjuk asesor |
| `assessor_stage_2_review` | Ketua Asesor | Ketua Asesor menilai kelayakan visitasi |
| `assessor_stage_2_correction` | Ketua Asesor/Pesantren | Section tertentu dibuka untuk perbaikan tahap 2 |
| `assessor_stage_2_limit_review` | Ketua Asesor/Admin/Super Admin | Batas perbaikan tahap 2 tercapai dan butuh keputusan eksplisit |
| `visitasi_scheduled` | Ketua Asesor | Jadwal visitasi sudah ditetapkan |
| `visitasi_completed` | Ketua Asesor | Visitasi offline sudah ditandai selesai |
| `post_visitasi_scoring` | Ketua Asesor/Anggota Asesor | NA1, NA2, NK, rekomendasi, dan dokumen dilengkapi |
| `visitasi_result_submitted` | Ketua Asesor/Admin | Hasil visitasi dikirim ke Admin |
| `admin_final_validation` | Admin | Admin memverifikasi NV dan keputusan akhir |
| `administrative_rejected` | Admin/Pesantren | Pengajuan ditolak sebelum validasi akhir karena aturan administratif atau limit perbaikan |
| `final_rejected` | Admin/Pesantren | Validasi akhir ditolak dan dapat masuk banding jika memenuhi aturan |
| `appeal_submitted` | Pesantren/Admin/Super Admin | Banding diajukan dan diproses sesuai policy |
| `final_approved` | Admin | Validasi akhir disetujui, menunggu penerbitan SK/sertifikat |
| `completed` | Admin/Pesantren | SK, sertifikat, nilai akhir, peringkat, dan masa berlaku sudah terbit |

### Workflow Transition Contract

Semua transisi status wajib melewati workflow/action service. Controller, Blade, JavaScript, dan menu hanya boleh memanggil aksi yang sudah diekspos backend.

| Dari | Aksi | Aktor | Validasi wajib | Ke |
|---|---|---|---|---|
| `draft_profile` | Submit pengajuan awal | Pesantren | Profil minimum lengkap | `initial_submitted` |
| `initial_submitted` | Tolak pengajuan awal | Admin | Alasan penolakan wajib | `initial_rejected` |
| `initial_rejected` | Submit ulang pengajuan awal | Pesantren | Profil minimum diperbaiki | `initial_submitted` |
| `initial_submitted` | Terima pengajuan awal | Admin | Profil minimum valid | `assessment_open` |
| `assessment_open` | Submit Assessment Awal | Pesantren | IPM, EDPM/IPR, SDM, dan berkas wajib lengkap sesuai policy | `admin_stage_1_review` |
| `admin_stage_1_review` | Minta perbaikan tahap 1 | Admin | Section, catatan, deadline, dan siklus tercatat | `admin_stage_1_correction` |
| `admin_stage_1_correction` | Submit perbaikan tahap 1 | Pesantren | Hanya section terbuka yang berubah | `admin_stage_1_review` |
| `admin_stage_1_correction` | Batas perbaikan tahap 1 tercapai | Sistem | Siklus perbaikan mencapai limit policy | `admin_stage_1_limit_review` |
| `admin_stage_1_limit_review` | Approve by exception | Admin | Alasan exception wajib | `assessor_assignment` |
| `admin_stage_1_limit_review` | Tolak administratif | Admin | Alasan final administratif wajib | `administrative_rejected` |
| `admin_stage_1_limit_review` | Eskalasi policy | Admin/Sistem | Policy membutuhkan review governance | `admin_stage_1_limit_review` |
| `admin_stage_1_review` | Tolak administratif | Admin | Alasan penolakan wajib dan policy mengizinkan reject sebelum asesor | `administrative_rejected` |
| `admin_stage_1_review` | Approve tahap 1 | Admin | Syarat administratif lengkap | `assessor_assignment` |
| `assessor_assignment` | Assign asesor | Admin | Ketua Asesor dan Anggota Asesor valid dan aktif | `assessor_stage_2_review` |
| `assessor_stage_2_review` | Reassign asesor | Admin | Alasan wajib, asesor pengganti valid, audit assignment lama/baru | `assessor_stage_2_review` |
| `assessor_stage_2_review` | Minta perbaikan tahap 2 | Ketua Asesor | Section, catatan, deadline, dan siklus tercatat | `assessor_stage_2_correction` |
| `assessor_stage_2_correction` | Reassign asesor | Admin | Alasan wajib, active correction tetap terjaga | `assessor_stage_2_correction` |
| `assessor_stage_2_correction` | Submit perbaikan tahap 2 | Pesantren | Hanya section terbuka yang berubah | `assessor_stage_2_review` |
| `assessor_stage_2_correction` | Batas perbaikan tahap 2 tercapai | Sistem | Siklus perbaikan mencapai limit policy | `assessor_stage_2_limit_review` |
| `assessor_stage_2_limit_review` | Layak visitasi by exception | Ketua Asesor | Alasan exception wajib dan Admin melihat keputusan | `visitasi_scheduled` |
| `assessor_stage_2_limit_review` | Tolak administratif | Ketua Asesor/Admin | Alasan final administratif wajib | `administrative_rejected` |
| `assessor_stage_2_limit_review` | Eskalasi policy | Ketua Asesor/Sistem | Policy membutuhkan review governance | `assessor_stage_2_limit_review` |
| `assessor_stage_2_review` | Nyatakan layak visitasi | Ketua Asesor | Semua blocker visitasi selesai | `visitasi_scheduled` |
| `visitasi_scheduled` | Ubah jadwal visitasi | Ketua Asesor | Alasan perubahan jadwal tercatat | `visitasi_scheduled` |
| `visitasi_scheduled` | Reassign asesor | Admin | Alasan wajib, jadwal dan assignment lama/baru diaudit | `visitasi_scheduled` |
| `visitasi_scheduled` | Tandai visitasi selesai | Ketua Asesor | Tanggal visitasi valid | `visitasi_completed` |
| `visitasi_completed` | Buka penilaian pasca visitasi | Sistem | Assignment asesor valid | `post_visitasi_scoring` |
| `post_visitasi_scoring` | Reassign asesor exception | Admin/Super Admin sesuai policy | Alasan wajib, nilai/catatan lama preserved, re-finalization rule jelas | `post_visitasi_scoring` |
| `post_visitasi_scoring` | Finalisasi NA1 | Ketua Asesor | Input NA1 lengkap sesuai formula existing | `post_visitasi_scoring` |
| `post_visitasi_scoring` | Finalisasi NA2 | Anggota Asesor | Input NA2 lengkap sesuai formula existing | `post_visitasi_scoring` |
| `post_visitasi_scoring` | Finalisasi NK | Ketua Asesor | NA1 dan NA2 final, formula existing terpenuhi | `post_visitasi_scoring` |
| `post_visitasi_scoring` | Submit hasil visitasi | Ketua Asesor | NA1, NA2, NK, rekomendasi, laporan asesor wajib lengkap; Kartu Kendali mengikuti policy | `visitasi_result_submitted` |
| `visitasi_result_submitted` | Buka validasi akhir | Admin/Sistem | Paket hasil visitasi lengkap dan Kartu Kendali terpenuhi jika policy mewajibkan sebelum validasi | `admin_final_validation` |
| `admin_final_validation` | Tolak final | Admin | Kategori dan alasan final wajib | `final_rejected` |
| `final_rejected` | Submit banding | Pesantren | Masih dalam deadline dan memenuhi policy banding | `appeal_submitted` |
| `appeal_submitted` | Terima banding | Admin/Super Admin sesuai policy | Keputusan dan alasan tercatat | `admin_final_validation` |
| `appeal_submitted` | Tolak banding | Admin/Super Admin sesuai policy | Keputusan dan alasan tercatat | `final_rejected` |
| `admin_final_validation` | Approve final | Admin | NV valid, reason ada bila NV berbeda dari NK | `final_approved` |
| `final_approved` | Terbitkan SK dan sertifikat | Admin | Nomor SK, masa berlaku, nilai akhir, peringkat, dan file/sertifikat valid | `completed` |

### Actor Permission Matrix

Permission matrix ini adalah kontrak produk. Backend policy/gate wajib mengikuti matrix ini, dan frontend hanya menampilkan aksi yang backend nyatakan tersedia.

| Aksi | Pesantren | Admin | Ketua Asesor | Anggota Asesor | Super Admin |
|---|---|---|---|---|---|
| Mengisi profil minimum | Ya, miliknya | Lihat | Tidak | Tidak | Lihat/governance |
| Submit pengajuan awal | Ya | Tidak | Tidak | Tidak | Tidak |
| Review pengajuan awal | Tidak | Ya | Tidak | Tidak | Governance only |
| Buka Assessment Awal | Tidak | Ya | Tidak | Tidak | Governance only |
| Isi IPM/EDPM/IPR/SDM/berkas | Ya, saat fase terbuka | Lihat/review | Lihat saat assigned | Lihat saat assigned | Lihat/governance |
| Minta perbaikan tahap 1 | Tidak | Ya | Tidak | Tidak | Governance only |
| Submit perbaikan section | Ya, section terbuka | Tidak | Tidak | Tidak | Tidak |
| Assign asesor | Tidak | Ya | Tidak | Tidak | Governance only |
| Review kelayakan visitasi | Tidak | Lihat | Ya | Lihat | Governance only |
| Minta perbaikan tahap 2 | Tidak | Tidak | Ya | Tidak | Governance only |
| Jadwalkan visitasi | Tidak | Lihat | Ya | Tidak | Governance only |
| Tandai visitasi selesai | Tidak | Lihat | Ya | Tidak | Governance only |
| Input/finalisasi NA1 | Tidak | Lihat | Ya | Tidak | Governance only |
| Input/finalisasi NA2 | Tidak | Lihat | Tidak | Ya | Governance only |
| Input/finalisasi NK | Tidak | Lihat | Ya | Tidak | Governance only |
| Upload Kartu Kendali | Ya | Lihat | Tidak | Tidak | Lihat/governance |
| Upload laporan individu Ketua | Tidak | Lihat | Ya | Tidak | Lihat/governance |
| Upload laporan kelompok | Tidak | Lihat | Ya | Tidak | Lihat/governance |
| Upload laporan individu Anggota | Tidak | Lihat | Tidak | Ya | Lihat/governance |
| Submit hasil visitasi | Tidak | Tidak | Ya | Tidak | Governance only |
| Input/finalisasi NV | Tidak | Ya | Tidak | Tidak | Governance only |
| Terbitkan SK/sertifikat | Tidak | Ya | Tidak | Tidak | Governance only |
| Mengubah setting workflow | Tidak | Tidak | Tidak | Tidak | Ya |

Super Admin tidak menjadi aktor tahap akreditasi. Jika Super Admin memiliki akses aksi Admin karena bypass, sistem tetap wajib mencatat audit actor asli sebagai Super Admin dan membedakan aksi governance dari aksi operasional.

### Role Interaction Matrix

Matrix ini menjelaskan interaksi antar role. Setiap interaksi harus memiliki trigger, penerima, respons yang diharapkan, data yang berpindah, notifikasi, dan audit trail.

| Fase | Trigger | Dari | Ke | Respons wajib | Data/artefak |
|---|---|---|---|---|---|
| Pengajuan awal | Pesantren submit profil minimum | Pesantren | Admin | Admin review terima/tolak | Snapshot profil minimum, timestamp submit |
| Pengajuan awal ditolak | Admin menolak pengajuan awal | Admin | Pesantren | Pesantren memperbaiki profil dan submit ulang | Alasan penolakan awal |
| Assessment Awal dibuka | Admin menerima pengajuan awal | Admin | Pesantren | Pesantren mengisi IPM, EDPM/IPR, SDM, berkas | Deadline assessment, daftar field wajib |
| Assessment Awal disubmit | Pesantren submit assessment | Pesantren | Admin | Admin review tahap 1 | IPM, EDPM/IPR, SDM, berkas pendukung |
| Perbaikan tahap 1 | Admin meminta revisi section | Admin | Pesantren | Pesantren mengubah hanya section terbuka | Section, catatan, deadline, siklus |
| Limit tahap 1 tercapai | Sistem mendeteksi siklus perbaikan habis | Sistem | Admin, Super Admin jika policy aktif | Admin memutus approve by exception, reject administratif, atau eskalasi | Riwayat siklus, section, alasan keputusan |
| Tahap 1 lolos | Admin approve tahap 1 | Admin | Ketua Asesor, Anggota Asesor | Asesor menerima assignment | Assignment Ketua/Anggota, jadwal review |
| Reassign asesor | Admin mengganti Ketua/Anggota Asesor | Admin | Asesor lama, asesor baru, Pesantren jika berdampak jadwal | Assignment baru aktif dan assignment lama tersimpan audit | Alasan reassign, actor, assignment lama/baru |
| Review tahap 2 | Ketua Asesor review kelayakan | Ketua Asesor | Pesantren/Admin | Pesantren revisi atau proses lanjut visitasi | Catatan kelayakan, section bermasalah |
| Perbaikan tahap 2 | Ketua Asesor meminta revisi section | Ketua Asesor | Pesantren | Pesantren mengubah hanya section terbuka | Section, catatan, deadline, siklus |
| Limit tahap 2 tercapai | Sistem mendeteksi siklus perbaikan habis | Sistem | Ketua Asesor, Admin, Super Admin jika policy aktif | Ketua Asesor/Admin memutus layak by exception, reject administratif, atau eskalasi | Riwayat siklus, section, alasan keputusan |
| Visitasi dijadwalkan | Ketua Asesor menentukan jadwal | Ketua Asesor | Pesantren, Anggota Asesor, Admin | Semua role melihat jadwal | Tanggal mulai, tanggal akhir, catatan |
| Visitasi selesai | Ketua Asesor menandai selesai | Ketua Asesor | Admin, Pesantren, Anggota Asesor | Fase scoring terbuka | Timestamp visitasi selesai |
| NA1 final | Ketua Asesor finalisasi NA1 | Ketua Asesor | Admin, Anggota Asesor | NK tetap terkunci sampai NA2 final | Nilai NA1 per butir |
| NA2 final | Anggota Asesor finalisasi NA2 | Anggota Asesor | Ketua Asesor, Admin | Ketua Asesor dapat finalisasi NK | Nilai NA2 per butir |
| NK final | Ketua Asesor finalisasi NK | Ketua Asesor | Admin | Admin menunggu paket hasil visitasi | NK, catatan butir, rekomendasi |
| Dokumen pasca visitasi | Pesantren/Asesor upload dokumen | Pesantren/Asesor | Admin | Admin melihat kelengkapan dokumen | Kartu Kendali, laporan individu/kelompok |
| Hasil visitasi disubmit | Ketua Asesor submit paket hasil | Ketua Asesor | Admin | Admin memvalidasi NV | NA1, NA2, NK, rekomendasi, dokumen |
| Validasi akhir | Admin approve/reject final | Admin | Pesantren, Asesor | Pesantren menerima hasil atau banding | NV, alasan override/reject, hasil final |
| SK/sertifikat terbit | Admin menerbitkan hasil resmi | Admin | Pesantren | Pesantren melihat/mengunduh hasil | Nomor SK, masa berlaku, sertifikat, nilai akhir |
| Reject administratif | Admin/Ketua Asesor menutup proses sebelum validasi akhir | Admin/Ketua Asesor | Pesantren, Super Admin jika policy aktif | Pesantren menerima alasan dan opsi next step sesuai policy | Alasan administratif, stage asal, eligibility pengajuan ulang/banding jika diizinkan |
| Setting berubah | Super Admin mengubah policy | Super Admin | Sistem/Admin terdampak | Workflow memakai policy baru sesuai effective date | Old value, new value, alasan, effective date |

Aturan interaksi:

- Setiap handoff antar role harus mengubah status atau membuat task/actionable state yang jelas.
- Tidak boleh ada interaksi yang hanya berupa perubahan tampilan tanpa state, notification, atau audit trail.
- Setiap role hanya boleh melihat data yang dibutuhkan untuk respons pada fase tersebut.
- Jika satu role gagal merespons sampai deadline, overdue action mengikuti policy Super Admin.

### Required Field Matrix By Role

Matrix ini mendefinisikan field requirement bisnis. Nama field final harus mengikuti inventory existing; perubahan nama teknis boleh dilakukan di environment baru hanya jika mapping-nya eksplisit dan tidak mengubah makna bisnis.

#### Pesantren Fields

| Fase | Field group | Required MVP | Catatan |
|---|---|---|---|
| Profil minimum pengajuan awal | `nama_pesantren`, `ns_pesantren`, `alamat`, `layanan_satuan_pendidikan`, `provinsi_kode`, `tahun_pendirian` | Ya | Baseline dari final rules existing; field minimum final harus diverifikasi lewat inventory |
| Profil lengkap | `kota_kabupaten`, `provinsi`, `kabupaten_kode`, `nama_mudir`, `jenjang_pendidikan_mudir`, `telp_pesantren`, `hp_wa`, `email_pesantren`, `persyarikatan`, `visi`, `misi`, `luas_tanah`, `luas_bangunan` | Sesuai policy submit fase | Tidak boleh dihapus; required/tidaknya dikontrol policy |
| Unit pendidikan | `layanan_satuan_pendidikan`, `jumlah_rombel` per unit | Ya untuk SDM | Menjadi dasar field SDM per tingkat/unit |
| Dokumen profil utama | `status_kepemilikan_tanah`, `sertifikat_nsp`, `rk_anggaran`, `silabus_rpp`, `peraturan_kepegawaian`, `file_lk_iapm`, `laporan_tahunan` | Sesuai policy dokumen | Format existing PDF/JPG/JPEG/PNG dan batas ukuran harus dipertahankan kecuali policy berubah |
| Dokumen profil pendukung | `dok_profil`, `dok_nsp`, `dok_renstra`, `dok_rk_anggaran`, `dok_kurikulum`, `dok_silabus_rpp`, `dok_kepengasuhan`, `dok_peraturan_kepegawaian`, `dok_sarpras`, `dok_laporan_tahunan`, `dok_sop` | Sesuai policy dokumen | Masuk Assessment Awal, bukan syarat awal kecuali policy menetapkan |
| IPM | `nsp_file`, `lulus_santri_file`, `kurikulum_file`, `buku_ajar_file` | Ya pada Assessment Awal jika policy aktif | Format existing PDF/JPG/JPEG/PNG |
| SDM | `santri_l`, `santri_p`, `ustadz_dirosah_l`, `ustadz_dirosah_p`, `ustadz_non_dirosah_l`, `ustadz_non_dirosah_p`, `pamong_l`, `pamong_p`, `musyrif_l`, `musyrif_p`, `tendik_l`, `tendik_p` per unit | Ya pada Assessment Awal | Integer minimal 0; mengikuti unit pendidikan yang dipilih |
| EDPM/IPR | `evaluasis[butir_id]`, `links[butir_id]`, `catatans[komponen_id]` | Ya pada Assessment Awal | Nilai 1-4 dan link bukti wajib saat submit final EDPM |
| Perbaikan section | Section/kategori yang dibuka, nilai/file/catatan sesuai section | Ya jika diminta | Hanya field di correction section yang boleh berubah |
| Kartu Kendali | `kartu_kendali` | Ya sesuai policy post-visitasi | Default wajib sebelum Admin validasi akhir; bisa dikonfigurasi menjadi wajib sebelum submit hasil visitasi |
| Banding | `alasan`, dokumen/field pendukung jika policy menuntut | Ya jika banding | Hanya setelah final rejection dan dalam deadline |

#### Ketua Asesor Fields

| Fase | Field group | Required MVP | Catatan |
|---|---|---|---|
| Review tahap 2 | `perbaikan[]`, `catatan` | Ya jika tidak layak visitasi | Catatan minimal substantif; section harus spesifik |
| Jadwal visitasi | `tanggal_mulai`, `tanggal_akhir`, `catatan_visitasi` | Ya | Tanggal akhir tidak boleh sebelum tanggal mulai; rentang mengikuti policy |
| Visitasi selesai | `akreditasi_id`, timestamp sistem | Ya | Hanya Ketua Asesor |
| NA1 | `butir_id`, `value`, `is_final` | Ya | Nilai 1-4 per butir, formula existing |
| NK | `butir_id`, `value`, `is_final` | Ya setelah NA1 dan NA2 final | Nilai 1-4 per butir, formula existing |
| Catatan/rekomendasi | `asesorButirCatatans`, `asesorCatatans`, `asesorCatatanNks` atau mapping existing setara | Ya sesuai form existing | Tidak boleh mengubah struktur catatan existing |
| Laporan individu Ketua | `laporan_individu_file` | Ya sebelum submit hasil visitasi | Format existing PDF/DOCX |
| Laporan kelompok | `laporan_kelompok_file` | Ya sebelum submit hasil visitasi | Hanya Ketua Asesor |
| Submit hasil visitasi | Paket scoring dan dokumen lengkap | Ya | Tidak boleh submit jika NA1/NA2/NK/dokumen wajib belum lengkap |

#### Anggota Asesor Fields

| Fase | Field group | Required MVP | Catatan |
|---|---|---|---|
| NA2 | `butir_id`, `value`, `is_final` | Ya | Nilai 1-4 per butir, formula existing |
| Catatan penilaian anggota | Catatan per butir/komponen sesuai form existing | Sesuai form existing | Tidak boleh mengubah struktur catatan existing |
| Laporan individu Anggota | `laporan_individu_file` | Ya sebelum Ketua Asesor submit hasil visitasi | Format existing PDF/DOCX |

#### Admin Fields

| Fase | Field group | Required MVP | Catatan |
|---|---|---|---|
| Review pengajuan awal | `decision`, `reason` jika tolak | Ya | Alasan wajib saat tolak |
| Buka Assessment Awal | `assessment_opened_at`, `assessment_deadline` | Ya | Deadline default dari setting Super Admin |
| Review tahap 1 approve | `decision`, audit metadata | Ya | Hanya setelah syarat assessment lengkap |
| Review tahap 1 correction | `berkasRejectionSections`, `berkasRejectionCatatan`, deadline/siklus | Ya jika perbaikan | Section harus spesifik |
| Assignment asesor | `ketua_asesor_id`, `anggota_asesor_id` | Ya | Tidak boleh sama, asesor harus aktif |
| Reassign asesor | Asesor lama, asesor baru, alasan | Ya jika reassign | Audit wajib |
| NV | `adminNvs[butir_id]`, `is_final`, `override_reason` bila NV berbeda dari NK | Ya | NV default mirror NK; reason mode mengikuti setting |
| Final rejection | `rejectionCategories[].category`, `rejectionCategories[].explanation` | Ya jika tolak final | Alasan minimal substantif |
| SK/sertifikat | `nomor_sk`, `masa_berlaku`, `masa_berlaku_akhir`, `sertifikat_file`, `catatan_rekomendasi_admin` | Ya saat approve final | Sertifikat PDF; masa akhir setelah masa mulai |

#### Super Admin Fields

| Fase | Field group | Required MVP | Catatan |
|---|---|---|---|
| Deadline policy | Nilai deadline per fase dan satuan hari | Ya | Validasi range wajib |
| Correction policy | Maksimal siklus tahap 1/tahap 2, action saat limit | Ya | Default 2 siklus |
| Submit requirement policy | Field/dokumen wajib per fase | Ya | Tidak boleh menghapus input existing |
| Document policy | Kartu Kendali/laporan asesor wajib kapan | Ya | Mengontrol gate submit hasil/validasi |
| NV policy | Override allowed, reason mode kolektif/per-butir | Ya | Formula tetap tidak berubah |
| Notification policy | Penerima event, reminder, overdue | Ya | Super Admin default menerima notifikasi Admin |
| Lock/unlock policy | Emergency override enabled, reason required | Ya | Bukan primary workflow |
| Banding policy | Deadline, eligibility, return point | Ya | Default return ke validasi akhir jika banding diterima |
| Setting change reason | `reason` opsional atau wajib untuk sensitive setting | Ya untuk setting sensitif | Semua perubahan tetap audit old/new value |

### Role Task Inbox Contract

Setiap role harus memiliki daftar tugas yang berasal dari state machine, bukan query status ad hoc.

| Role | Task utama yang harus muncul | Empty state yang benar |
|---|---|---|
| Pesantren | Lengkapi profil, submit pengajuan, isi Assessment Awal, perbaiki section, upload Kartu Kendali, lihat hasil/banding | Tidak ada tugas aktif untuk Pesantren saat semua aksi menunggu role lain |
| Admin | Review pengajuan awal, buka Assessment Awal, review tahap 1, assign/reassign asesor, limit review, validasi NV, terbit SK/sertifikat, proses banding | Tidak ada pengajuan yang membutuhkan aksi Admin |
| Ketua Asesor | Review tahap 2, jadwalkan visitasi, tandai selesai, input NA1, input NK, upload laporan, submit hasil | Tidak ada tugas Ketua Asesor pada assignment aktif |
| Anggota Asesor | Lihat jadwal, input NA2, upload laporan individu | Tidak ada tugas Anggota Asesor pada assignment aktif |
| Super Admin | Review setting, audit perubahan, monitor overdue/escalation, governance role/permission | Tidak ada governance action yang membutuhkan perhatian |

### Document Visibility Matrix

| Dokumen | Uploader | Viewer wajib | Hidden dari | Waktu tersedia |
|---|---|---|---|---|
| Profil Pesantren | Pesantren | Pesantren, Admin, assigned Asesor, Super Admin | Role lain/non-owner | Sejak pengajuan awal |
| IPM | Pesantren | Pesantren, Admin, assigned Asesor, Super Admin | Role lain/non-owner | Assessment Awal |
| EDPM/IPR | Pesantren | Pesantren, Admin, assigned Asesor, Super Admin | Role lain/non-owner | Assessment Awal |
| SDM | Pesantren | Pesantren, Admin, assigned Asesor, Super Admin | Role lain/non-owner | Assessment Awal |
| Berkas pendukung | Pesantren | Pesantren, Admin, assigned Asesor, Super Admin | Role lain/non-owner | Assessment Awal |
| Kartu Kendali | Pesantren | Pesantren uploader, Admin, Super Admin | Ketua Asesor, Anggota Asesor, Pesantren lain | Pasca visitasi |
| Laporan individu Ketua | Ketua Asesor | Uploader, Admin, Super Admin | Pesantren, Anggota Asesor bila bukan penerima policy | Pasca visitasi |
| Laporan kelompok | Ketua Asesor | Uploader, Admin, Super Admin | Pesantren | Pasca visitasi |
| Laporan individu Anggota | Anggota Asesor | Uploader, Admin, Super Admin | Pesantren, Ketua Asesor bila bukan penerima policy | Pasca visitasi |
| SK | Admin | Pesantren pemilik, Admin, Super Admin | Pesantren lain/non-owner | Setelah `completed` |
| Sertifikat | Admin/Sistem | Pesantren pemilik, Admin, Super Admin | Pesantren lain/non-owner | Setelah `completed` |

### Audit Trail Matrix

Setiap event audit minimal menyimpan actor, role actor, akreditasi, aksi, status asal, status tujuan, timestamp, metadata relevan, dan alasan jika aksi bersifat koreksi, penolakan, override, atau perubahan policy.

| Event | Audit wajib |
|---|---|
| Submit pengajuan awal | Ya |
| Terima/tolak pengajuan awal | Ya, alasan wajib saat tolak |
| Buka Assessment Awal atau ubah deadline | Ya |
| Submit Assessment Awal | Ya |
| Minta perbaikan tahap 1/tahap 2 | Ya, section, catatan, deadline, siklus |
| Submit perbaikan | Ya, section yang berubah |
| Batas siklus perbaikan tercapai | Ya, stage, siklus, section, policy yang dipakai |
| Keputusan limit perbaikan | Ya, keputusan, actor, alasan, status tujuan |
| Approve tahap 1 | Ya |
| Assign/reassign asesor | Ya, asesor lama/baru dan alasan reassign |
| Layak/tidak layak visitasi | Ya |
| Jadwal/reschedule visitasi | Ya, alasan reschedule wajib |
| Visitasi selesai | Ya |
| Finalisasi NA1/NA2/NK | Ya |
| Upload/hapus/ganti dokumen pasca visitasi | Ya |
| Submit hasil visitasi | Ya |
| Simpan/finalisasi NV | Ya, alasan wajib bila NV berbeda dari NK |
| Validasi akhir approve/reject | Ya |
| Terbit SK/sertifikat | Ya |
| Submit/putuskan banding | Ya |
| Ubah Super Admin setting | Ya, old value, new value, actor, optional reason |
| Manual lock/unlock atau emergency override | Ya, alasan wajib |

### Notification Matrix

Notifikasi MVP minimal berupa in-app notification. Channel tambahan seperti email/push boleh mengikuti kemampuan existing, tetapi tidak boleh menggantikan in-app notification.

| Event | Penerima |
|---|---|
| Pengajuan awal disubmit | Admin, Super Admin jika policy aktif |
| Pengajuan awal diterima/ditolak | Pesantren |
| Assessment Awal dibuka | Pesantren |
| Deadline Assessment Awal mendekat/overdue | Pesantren, Admin |
| Assessment Awal disubmit | Admin |
| Perbaikan tahap 1 diminta | Pesantren |
| Perbaikan tahap 1 disubmit | Admin |
| Limit perbaikan tahap 1 tercapai | Admin, Super Admin jika policy aktif |
| Asesor di-assign | Ketua Asesor, Anggota Asesor |
| Perbaikan tahap 2 diminta | Pesantren |
| Perbaikan tahap 2 disubmit | Ketua Asesor |
| Limit perbaikan tahap 2 tercapai | Ketua Asesor, Admin, Super Admin jika policy aktif |
| Visitasi dijadwalkan/diubah | Pesantren, Ketua Asesor, Anggota Asesor, Admin |
| Visitasi selesai | Admin, Pesantren, assigned Asesor |
| NA1/NA2/NK final | Ketua Asesor, Anggota Asesor, Admin sesuai konteks |
| Dokumen pasca visitasi diupload | Admin |
| Hasil visitasi disubmit | Admin |
| Validasi akhir approve/reject | Pesantren, Ketua Asesor, Anggota Asesor |
| Reject administratif sebelum validasi akhir | Pesantren, Admin, Super Admin jika policy aktif |
| SK/sertifikat terbit | Pesantren |
| Banding diajukan | Admin, Super Admin jika policy aktif |
| Setting workflow berubah | Super Admin, audit log; Admin diberi notifikasi jika setting berdampak operasional |

### Super Admin Default Policy Baseline

Semua item berikut wajib configurable pada MVP. Nilai default dipakai sebagai baseline awal dan dapat diubah dari menu Super Admin.

| Policy | Default MVP | Catatan |
|---|---|---|
| Deadline review pengajuan awal | 5 hari kerja | Berlaku sejak `initial_submitted` |
| Deadline Assessment Awal | 14 hari kalender | Berlaku sejak `assessment_open` |
| Deadline review Admin tahap 1 | 5 hari kerja | Berlaku sejak `admin_stage_1_review` |
| Deadline perbaikan tahap 1 | 7 hari kalender | Berlaku sejak `admin_stage_1_correction` |
| Maksimal siklus perbaikan tahap 1 | 2 siklus | Setelah limit, butuh keputusan Admin |
| Deadline review Asesor tahap 2 | 5 hari kerja | Berlaku sejak assignment selesai |
| Deadline perbaikan tahap 2 | 7 hari kalender | Berlaku sejak `assessor_stage_2_correction` |
| Maksimal siklus perbaikan tahap 2 | 2 siklus | Setelah limit, butuh keputusan Ketua Asesor dan Admin |
| Deadline input NA1/NA2/NK | 7 hari kalender setelah visitasi selesai | Boleh dipisah per role di settings |
| Kartu Kendali wajib | Sebelum Admin validasi akhir | Policy dapat diubah menjadi sebelum submit hasil visitasi |
| Laporan individu/kelompok asesor wajib | Sebelum Ketua Asesor submit hasil visitasi | Wajib untuk paket hasil visitasi |
| NV override | Diizinkan dengan alasan wajib | Mode alasan default kolektif, dapat diatur per-butir |
| Super Admin menerima notifikasi Admin | Aktif | Agar governance mendapat sinyal operasional |
| Banding deadline | 7 hari kalender setelah final rejection | Hanya setelah `final_rejected` |
| Emergency lock/unlock | Aktif dengan alasan wajib | Tidak boleh menjadi primary workflow |

### Data Model Impact

Rebuild harus menyediakan domain record yang eksplisit untuk workflow, bukan hanya mengandalkan status tunggal pada akreditasi.

| Domain record | Tujuan |
|---|---|
| Accreditation application atau Akreditasi V2 | Aggregate utama proses akreditasi |
| Workflow transition log | Riwayat transisi state machine |
| Workflow settings | Policy Super Admin yang efektif |
| Correction request | Perbaikan tahap 1 dan tahap 2 berbasis section |
| Correction section | Section/kategori/butir yang dibuka untuk revisi |
| Assessor assignment | Ketua Asesor dan Anggota Asesor per akreditasi |
| Visitasi schedule | Jadwal, reschedule, dan status visitasi |
| Scoring session | NA1, NA2, NK, NV, finalization state |
| Post visitasi document | Kartu Kendali dan laporan asesor dengan visibility |
| Final validation | Keputusan akhir Admin, NV, reason, SK metadata |
| Certificate/SK record | Nomor SK, masa berlaku, sertifikat, dan publikasi hasil |
| Appeal/Banding | Lifecycle banding setelah final rejection |
| Audit log | Audit lintas workflow, setting, dokumen, scoring, dan override |

### Backend Architecture Guardrails

- State machine V2 adalah otoritas transisi status.
- Workflow action service adalah satu-satunya tempat aksi bisnis mengubah status.
- Satu Pesantren hanya boleh memiliki satu akreditasi aktif non-terminal pada satu waktu.
- Controller harus tipis: validasi request, panggil service/action, redirect/response.
- Blade dan JavaScript tidak boleh menentukan kelayakan aksi bisnis secara mandiri.
- Policy/gate wajib mengecek actor, role, ownership, assignment, dan status.
- Semua business rule yang configurable harus membaca policy dari settings resolver.
- Rumus nilai harus berada di scoring engine/service yang terisolasi dan punya regression test.
- Lock/unlock data harus berbasis workflow dan correction section, bukan toggle global bebas.
- Query list harus memakai tenant/assignment scope dan allowlist sort/filter.
- Upload dokumen harus transaction-aware: file baru dibersihkan jika service/DB gagal.
- Semua perubahan penting wajib idempotent-safe atau memiliki guard stale state.

### Frontend Architecture Guardrails

- Frontend mengikuti action availability dari backend, bukan menduplikasi rule status.
- Form action, method, field name, flash key, dan validation key adalah kontrak backend-frontend.
- Role menu hanya menampilkan aksi yang tersedia untuk role dan status saat itu.
- UI boleh memperjelas grouping atau instruksi, tetapi tidak boleh mengubah input bisnis existing.
- Tombol aksi destruktif atau final harus memakai konfirmasi dan tetap divalidasi backend.
- Tidak boleh ada ketergantungan legacy reactive layer, `legacy client binding`, atau action client-side yang tidak punya route/controller.
- Komponen frontend harus mendukung locked state, partial unlock state, dan read-only state per section.

### Rework Environment and Migration Strategy

Karena sistem akan dirework di environment baru, strategi default adalah membangun core Akreditasi V2 secara bersih sambil mempertahankan kontrak input, formula, dan data referensi existing.

Prioritas migrasi:

1. Migrasi user, role, permission, pesantren, asesor, dan master data yang masih valid.
2. Migrasi struktur input dan master EDPM/IPR sebagai baseline immutable untuk formula.
3. Migrasi dokumen dan riwayat akreditasi lama hanya jika ada kebutuhan legal/audit.
4. Akreditasi aktif dari sistem lama tidak otomatis dimigrasikan ke workflow V2 tanpa mapping dan sign-off terpisah.
5. Jika akreditasi aktif harus dimigrasikan, wajib ada mapping status lama ke status V2 dan verifikasi manual per kasus.
6. Sistem lama dapat diperlakukan sebagai read-only archive sampai data penting selesai divalidasi.

Mapping konseptual awal dari status existing ke V2:

| Status existing | Makna existing | Kandidat mapping V2 |
|---|---|---|
| `6` | Pengajuan | `initial_submitted` atau `assessment_open` tergantung kelengkapan data |
| `5` | Verifikasi berkas | `admin_stage_1_review` |
| `4` | Review/assessment asesor | `assessor_stage_2_review` |
| `3` | Visitasi | `visitasi_scheduled` |
| `2` | Pasca visitasi | `post_visitasi_scoring` |
| `1` | Validasi admin | `admin_final_validation` |
| `0` | Selesai | `completed` |
| `-1` | Ditolak | `initial_rejected`, `administrative_rejected`, `final_rejected`, atau correction state sesuai audit |
| `-2` | Banding | `appeal_submitted` |

Mapping di atas belum boleh digunakan untuk migrasi otomatis tanpa data audit, karena status lama tidak cukup detail untuk membedakan pengajuan awal, perbaikan tahap 1, perbaikan tahap 2, dan final rejection.

### Acceptance Criteria Per Module

| Modul | Acceptance criteria minimum |
|---|---|
| Workflow/state machine | Semua transisi valid tercakup test; transisi salah ditolak; audit tercatat |
| Pesantren input | Semua input existing tetap tersedia; hanya fase dan lock/unlock yang berubah |
| Admin workflow | Admin dapat review awal, tahap 1, assignment, NV, validasi akhir, SK, dan sertifikat |
| Asesor workflow | Ketua dan Anggota Asesor hanya bisa menjalankan aksi sesuai assignment dan peran |
| Scoring | Formula existing menghasilkan output sama untuk input sama |
| Super Admin settings | Setting MVP dapat diubah, tervalidasi, dipakai workflow, dan diaudit |
| Dokumen | Visibility Kartu Kendali, laporan asesor, SK, dan sertifikat sesuai matrix |
| Notification | Event MVP mengirim notifikasi ke penerima yang benar |
| Frontend | Tidak ada aksi tanpa backend route/policy; menu dan tombol sesuai status/role |
| Migration | Data referensi dan input baseline tervalidasi sebelum environment baru dipakai |

### Implementation Readiness Deliverables

Sebelum rebuild Akreditasi V2 dimulai, tim wajib membuat dokumen teknis turunan berikut. PRD ini menjadi sumber keputusan produk; dokumen turunan menjadi sumber eksekusi teknis backend, frontend, QA, dan migrasi.

| Deliverable | Tujuan | Minimal isi | Owner utama |
|---|---|---|---|
| Input Inventory Existing | Memastikan input lama tidak berubah secara bisnis | Field name existing, label UI, tipe data, validasi, required phase, upload format, role owner, editable/readonly state, mapping V2 | System analyst + backend |
| Formula Baseline / Scoring Contract | Menjaga rumus nilai tidak berubah | Komponen EDPM/IPR, butir, bobot, skala nilai, NA1, NA2, NK, NV, nilai akhir, peringkat, golden sample input-output | Backend + QA + product |
| State Machine Technical Spec | Mengubah flow PRD menjadi kontrak service | Enum status, action name, allowed transition, payload, guard/policy, side effect, audit event, notification event, failure behavior | Backend |
| Data Model / ERD Akreditasi V2 | Mengunci struktur data baru yang bersih | Tabel/record, relasi, unique constraint, index, lifecycle data, soft delete/archive policy, ownership/tenant boundary | Backend + DBA |
| Backend-Frontend Contract | Mencegah frontend membuat rule sendiri | Route/action name, HTTP method, payload, validation key, flash/session response, action availability, readonly/locked/partial unlock state | Backend + frontend |
| Migration / Rebuild Strategy | Menentukan data lama dipakai, dimigrasikan, atau diarsipkan | Migrated data, archived data, active akreditasi handling, master EDPM/IPR migration, dokumen lama, rollback plan | System analyst + backend |
| QA Matrix Per Role | Menjamin flow diuji dari sudut role nyata | Scenario happy path, correction, limit review, role boundary, document visibility, NV override, SK/sertifikat, Super Admin settings, banding | QA |

### Input Inventory Existing Requirements

Dokumen inventory input harus dibuat dari sistem existing, bukan dari asumsi. Inventory ini menjadi baseline yang dipakai untuk membandingkan sistem baru.

| Kolom inventory | Wajib | Catatan |
|---|---|---|
| Role owner | Ya | Pesantren, Admin, Ketua Asesor, Anggota Asesor, Super Admin |
| Modul/form | Ya | Contoh: Profil Pesantren, IPM, SDM, EDPM/IPR, Validasi Admin |
| Field key existing | Ya | Nama field/database/request existing |
| Label UI existing | Ya | Label yang dilihat user |
| Tipe data | Ya | String, integer, date, file, enum, array, rich text, URL |
| Required condition | Ya | Required saat draft, submit, correction, finalization, atau policy tertentu |
| Validation rule | Ya | Min/max, mimes, size, enum values, date relation, unique rule |
| Editable phase | Ya | Fase workflow saat field dapat diubah |
| Readonly/locked phase | Ya | Fase workflow saat field hanya bisa dilihat |
| Correction unlock mapping | Ya | Section/kategori yang membuka field saat revisi |
| Visibility | Ya | Role yang boleh melihat field/dokumen |
| V2 mapping | Ya | Field/record baru yang menampung data existing |
| Regression sample | Ya | Contoh nilai valid dan invalid untuk test |

Tidak boleh ada field existing yang hilang dari inventory. Jika field ingin dihapus, diganti makna, digabung, atau diubah validasinya, harus dibuat PRD/perubahan produk terpisah.

### Formula Baseline Requirements

Formula baseline adalah dokumen paling kritis setelah PRD ini. Rebuild boleh mengganti workflow dan arsitektur, tetapi tidak boleh mengubah hasil nilai untuk input yang sama.

Formula baseline wajib memuat:

- Daftar komponen EDPM/IPR existing.
- Daftar butir per komponen.
- Skala nilai valid per butir.
- Bobot atau aturan agregasi jika ada.
- Cara menghitung NA1.
- Cara menghitung NA2.
- Cara menentukan NK setelah NA1 dan NA2 final.
- Cara membuat NV default mirror dari NK.
- Cara menangani NV override.
- Cara menghitung nilai akhir.
- Cara menentukan peringkat akreditasi.
- Aturan pembulatan, batas bawah/atas, dan edge case nilai kosong.
- Golden sample minimal 3 kasus: nilai tinggi, nilai tengah, nilai batas/edge.

Golden sample harus berisi input per butir dan output final yang diharapkan. Sistem baru tidak boleh dinyatakan siap jika hasil formula berbeda dari baseline.

### State Machine Technical Spec Requirements

State Machine Technical Spec harus menerjemahkan State Machine V2 menjadi kontrak implementasi.

| Elemen | Wajib dijelaskan |
|---|---|
| Status enum | Nama status, terminal/non-terminal, role utama, deskripsi |
| Action/service method | Nama aksi backend yang memicu transisi |
| Payload | Field request minimal dan optional |
| Actor guard | Role, ownership, assignment, admin-area, super-admin governance |
| Precondition | Status asal, data wajib, deadline, correction limit, scoring finality |
| Side effect | Lock/unlock section, create task, create document requirement, update score flag |
| Audit event | Nama event dan metadata |
| Notification event | Penerima dan isi minimal |
| Failure behavior | Domain error, stale state, forbidden, validation error, rollback |
| Test case | Minimal happy path dan forbidden path |

Tidak boleh ada transisi workflow yang hanya hidup di controller, Blade, atau JavaScript tanpa action/service di spec ini.

### Data Model / ERD Requirements

ERD Akreditasi V2 harus menghindari pola lama yang terlalu bergantung pada satu status besar dan field tersebar.

Model data minimal harus menjelaskan:

- Aggregate utama akreditasi.
- Workflow transition log.
- Workflow settings dan effective policy.
- Correction request dan correction section.
- Assessor assignment dengan role Ketua/Anggota.
- Visitasi schedule dan reschedule log.
- Scoring session untuk NA1, NA2, NK, NV.
- Document record dengan uploader, intended viewer, category, visibility, dan requirement policy.
- Final validation record.
- SK/certificate record.
- Appeal/banding record.
- Audit log.
- Notification/task inbox record jika dipisahkan dari notification existing.

Setiap tabel/record harus punya ownership boundary yang jelas agar Pesantren tidak bisa membaca/menulis data Pesantren lain dan Asesor tidak bisa membaca assignment Asesor lain.

### Backend-Frontend Contract Requirements

Kontrak backend-frontend wajib dibuat sebelum frontend final dikerjakan.

| Area | Kontrak wajib |
|---|---|
| Route/action | Route name, URL, method, controller/action |
| Payload | Field request, tipe data, required condition |
| Validation response | Error key, pesan, redirect/JSON behavior |
| Success response | Flash key, redirect target, task refresh |
| Action availability | Backend-provided boolean/list aksi yang boleh tampil |
| Locked state | Readonly, locked, partial unlock, correction-only state |
| Document upload | Field file, max size, mimes, visibility, replacement behavior |
| Scoring UI | Save draft, finalization, immutable value behavior |
| Admin validation | NV mirror, override reason, finalize behavior |
| SK/certificate | Upload/generate, publish state, Pesantren access |

Frontend tidak boleh menyimpulkan izin aksi hanya dari angka status atau class CSS. Backend harus menyediakan action availability yang sudah melewati policy.

### Migration / Rebuild Strategy Requirements

Strategi rebuild harus menjawab nasib data existing sebelum environment baru dipakai.

| Area | Keputusan wajib |
|---|---|
| User/role/permission | Migrasi penuh, mapping role, super admin governance |
| Pesantren/asesor profile | Migrasi field existing dan validasi completeness |
| Master EDPM/IPR | Migrasi sebagai baseline formula immutable |
| Master dokumen/kategori | Migrasi atau rebuild dari policy baru |
| Akreditasi selesai | Archive read-only atau migrasi ke `completed` |
| Akreditasi aktif | Complete manual di sistem lama, archive, atau migrasi manual per kasus |
| Dokumen lama | Migrasi file, archive link, atau tidak dibawa |
| Audit lama | Archive jika tidak bisa dinormalisasi |
| Cutover | Tanggal freeze data, fallback, rollback, dan ownership PIC |

Default PRD ini: akreditasi aktif tidak otomatis dimigrasikan ke V2 tanpa mapping dan sign-off manual.

### QA Matrix Requirements

QA matrix harus menutup minimal scenario berikut:

| Area | Scenario wajib |
|---|---|
| Happy path | Pengajuan awal sampai SK/sertifikat diakses Pesantren |
| Initial rejection | Admin tolak pengajuan awal, Pesantren submit ulang |
| Stage 1 correction | Admin buka section tertentu, Pesantren hanya bisa ubah section itu |
| Stage 1 limit | Siklus habis, masuk limit review, keputusan diaudit |
| Assignment | Ketua/Anggota berbeda, reassign diaudit |
| Stage 2 correction | Ketua Asesor minta revisi, Pesantren hanya ubah section itu |
| Visitasi | Ketua jadwalkan, reschedule, tandai selesai |
| Scoring | NA1, NA2, NK terkunci sesuai aturan |
| NV | Mirror NK, override wajib alasan, finalisasi NV |
| Dokumen | Kartu Kendali hidden dari Asesor, laporan asesor hidden dari Pesantren |
| Final approval | Admin terbit SK/sertifikat, Pesantren bisa akses |
| Final rejection | Pesantren menerima alasan dan bisa banding jika eligible |
| Super Admin settings | Perubahan setting memengaruhi workflow dan diaudit |
| Role boundary | User salah role/owner/assignment selalu ditolak |
| Frontend contract | Semua tombol/form memakai route backend nyata, tidak `legacy client binding` |

## Acceptance Criteria

- MVP hanya dianggap selesai jika flow berjalan end-to-end sampai SK dan sertifikat terbit serta dapat diakses Pesantren.
- Rebuild tidak boleh masuk implementasi besar sebelum deliverable readiness minimal tersedia: input inventory, formula baseline, state machine technical spec, ERD, backend-frontend contract, migration strategy, dan QA matrix.
- Pesantren dapat mengajukan akreditasi hanya dengan Profil Pesantren yang memenuhi field wajib minimum.
- Pesantren tidak dapat membuat pengajuan baru jika masih memiliki akreditasi aktif non-terminal.
- IPM, EDPM/IPR, SDM, dan berkas pendukung baru dapat disubmit dalam fase Assessment Awal setelah Admin menerima pengajuan awal.
- State Machine V2 menolak semua transisi yang tidak sesuai status, actor, ownership, assignment, atau policy.
- Setiap interaksi antar role menghasilkan task/actionable state, notifikasi, dan audit trail yang sesuai Role Interaction Matrix.
- Setiap role hanya dapat mengisi field yang menjadi tanggung jawabnya sesuai Required Field Matrix By Role.
- Batas siklus perbaikan tahap 1 dan tahap 2 tidak membuat sistem auto-reject diam-diam; selalu masuk limit review dengan keputusan dan alasan yang diaudit.
- Admin dapat melakukan Review Tahap 1, meminta perbaikan berbasis section, dan hanya section tersebut yang terbuka untuk Pesantren.
- Admin hanya dapat assign Ketua Asesor dan Anggota Asesor setelah Review Tahap 1 lolos.
- Admin dapat reassign asesor hanya dengan alasan wajib, audit trail, dan aturan preservasi nilai/catatan jika scoring sudah dimulai.
- Ketua Asesor dapat melakukan Review Tahap 2 dan meminta perbaikan berbasis section tanpa membuka seluruh data.
- Ketua Asesor menjadi satu-satunya role yang dapat menjadwalkan visitasi, menandai Visitasi Selesai, input NA1, input NK, dan submit hasil visitasi.
- Anggota Asesor hanya dapat input NA2 dan upload laporan individu sesuai assignment-nya.
- NK terkunci sampai NA1 dan NA2 final.
- Kartu Kendali hanya terlihat oleh Pesantren sebagai uploader dan Admin sebagai penerima.
- Laporan asesor hanya terlihat oleh Asesor sebagai uploader dan Admin sebagai penerima, tidak terlihat oleh Pesantren.
- Admin NV default sama dengan NK, dan alasan wajib tersedia jika NV berbeda dari NK.
- Admin dapat menerbitkan SK dan sertifikat setelah validasi akhir approve.
- Super Admin dapat mengubah aturan workflow tanpa perubahan code untuk deadline, siklus perbaikan, dokumen wajib, notifikasi, lock/unlock, NV, dan banding.
- Super Admin settings MVP memiliki default value, validasi, audit trail, dan benar-benar dipakai oleh workflow action service.
- Input bisnis existing untuk Pesantren, Asesor, dan Admin tetap sama meskipun urutan workflow dan ownership actor berubah.
- Rumus penilaian EDPM/IPR, butir, bobot, NA1, NA2, NK, NV, nilai akhir, dan peringkat menghasilkan output yang sama dengan sistem existing untuk data input yang sama.
- Frontend tidak memiliki aksi workflow yang hanya hidup di JavaScript tanpa route, policy, dan backend service.
- Semua aksi approval, rejection, correction, scoring, document upload, setting change, final validation, SK issuance, dan banding memiliki audit trail.

## User Stories

1. As a Pesantren user, I want to submit an accreditation request after completing only the required profile fields, so that I can start the process without filling every assessment form upfront.

2. As a Pesantren user, I want to see which profile fields are required before submission, so that I know what must be completed first.

3. As a Pesantren user, I want to receive a clear rejection reason if my initial submission is rejected, so that I can fix the profile and submit again.

4. As a Pesantren user, I want IPM, EDPM/IPR, SDM, and supporting documents to become available after Admin accepts my initial submission, so that I fill them at the correct stage.

5. As a Pesantren user, I want a clear Assessment Awal deadline, so that I know when my IPM, EDPM/IPR, SDM, and documents must be submitted.

6. As a Pesantren user, I want to submit Assessment Awal after completing IPM, EDPM/IPR, SDM, and required documents, so that Admin can review my readiness.

7. As a Pesantren user, I want to see only the sections that require correction, so that I do not accidentally modify unrelated approved data.

8. As a Pesantren user, I want to submit corrections after fixing rejected sections, so that Admin or Ketua Asesor can review them again.

9. As a Pesantren user, I want to receive notifications when Admin opens Assessment Awal, requests correction, approves stage 1, or rejects final validation, so that I can respond on time.

10. As a Pesantren user, I want to upload Kartu Kendali after visitasi, so that Admin receives required post-visitasi evidence.

11. As a Pesantren user, I want Kartu Kendali to be hidden from Asesor, so that role visibility follows the business rule.

12. As a Pesantren user, I want to see my final result, SK, certificate, validity period, ranking, and allowed recommendations, so that I understand my accreditation outcome.

13. As a Pesantren user, I want to submit an appeal when final rejection qualifies for banding, so that I can challenge the final decision.

14. As an Admin user, I want to review initial profile submissions, so that only eligible Pesantren enter Assessment Awal.

15. As an Admin user, I want to reject initial submissions with a clear reason, so that Pesantren can correct their profile.

16. As an Admin user, I want to open or schedule Assessment Awal after accepting an initial submission, so that Pesantren can start filling IPM, EDPM/IPR, SDM, and documents.

17. As an Admin user, I want to review Assessment Awal in stage 1, so that I can validate administrative completeness before assigning asesor.

18. As an Admin user, I want to mark specific sections or categories for correction, so that Pesantren only edits the problematic data.

19. As an Admin user, I want to approve stage 1 and assign Ketua Asesor and Anggota Asesor, so that the process moves to asesor review.

20. As an Admin user, I want to monitor stage 1 correction cycles, so that repeated failures are handled according to configured limits.

21. As an Admin user, I want to receive submitted visitasi results from Ketua Asesor, so that I can perform final validation.

22. As an Admin user, I want NV to mirror NK by default, so that verification starts from the assessor team's group score.

23. As an Admin user, I want to change NV when needed, so that I can correct or verify final values.

24. As an Admin user, I want to provide a required reason when NV differs from NK, so that audit trail is complete.

25. As an Admin user, I want to issue SK, validity period, and certificate after approving final validation, so that accreditation can be completed.

26. As an Admin user, I want to reject final validation with categories and explanations, so that Pesantren receives a clear final decision.

27. As an Admin user, I want to receive Kartu Kendali from Pesantren but keep it hidden from Asesor, so that document visibility follows policy.

28. As an Admin user, I want to receive visitasi reports from Asesor but keep them hidden from Pesantren, so that internal assessment documents remain restricted.

29. As a Ketua Asesor, I want to review Assessment Awal after Admin approves stage 1, so that I can decide whether the Pesantren is ready for visitasi.

30. As a Ketua Asesor, I want to reject visitasi readiness by selecting problematic sections and writing notes, so that Pesantren can correct specific issues.

31. As a Ketua Asesor, I want to review corrected sections after Pesantren submits improvements, so that I can decide readiness again.

32. As a Ketua Asesor, I want to schedule visitasi only after documents are ready, so that visitasi is based on complete preparation.

33. As a Ketua Asesor, I want to mark Visitasi Selesai after the offline visitasi is completed, so that the scoring phase can begin.

34. As a Ketua Asesor, I want to input NA1, so that my individual assessment is captured.

35. As a Ketua Asesor, I want NK to remain locked until NA1 and NA2 are final, so that group scoring is based on both assessors' final inputs.

36. As a Ketua Asesor, I want to input NK and recommendation notes using the existing EDPM/scoring components, so that the current scoring structure is preserved.

37. As a Ketua Asesor, I want to upload my individual report and group report, so that Admin receives required visitasi documentation.

38. As a Ketua Asesor, I want to submit hasil visitasi only after all required scores, notes, and documents are complete, so that Admin receives a complete package.

39. As an Anggota Asesor, I want to input NA2, so that my individual assessment is captured.

40. As an Anggota Asesor, I want to upload only my individual report, so that my responsibility is clear and separate from Ketua Asesor.

41. As an Anggota Asesor, I want to be prevented from scheduling visitasi, marking visitasi complete, inputting NK, or submitting final visitasi results, so that role responsibilities remain controlled.

42. As a Super Admin, I want to configure workflow deadlines, so that policy can change without code changes.

43. As a Super Admin, I want to configure maximum correction cycles, so that the process does not loop indefinitely.

44. As a Super Admin, I want to configure required documents for post-visitasi submission, so that business rules can be adjusted centrally.

45. As a Super Admin, I want to configure whether Kartu Kendali is required before Ketua Asesor submits hasil visitasi or before Admin final validation, so that the policy is explicit.

46. As a Super Admin, I want to configure whether Admin can change NV from NK, so that scoring governance can be controlled.

47. As a Super Admin, I want to configure whether NV difference requires per-butir or collective reason, so that audit requirements are clear.

48. As a Super Admin, I want to configure whether Super Admin receives Admin workflow notifications, so that governance users receive the right operational signals.

49. As a Super Admin, I want to configure reminder timing for deadlines, so that users are warned before overdue events.

50. As a Super Admin, I want to configure manual global lock and emergency override policies, so that exceptional cases can be handled with auditability.

51. As a Super Admin, I want all setting changes to be audited, so that governance changes can be traced.

52. As a system auditor, I want every approval, rejection, correction request, scoring change, document upload, and SK issuance to have audit logs, so that accountability is preserved.

53. As a system user, I want notifications for actions that require my response, so that I do not miss deadlines or required tasks.

54. As a system user, I want role-specific menus to reflect only the actions available to me, so that I do not see actions I cannot perform.

55. As a developer, I want the new flow to be represented by testable workflow modules, so that backend behavior is stable through future frontend changes.

## Implementation Decisions

- The EDPM/IPR structure and existing scoring components are not redesigned in this PRD. The new flow changes when EDPM/IPR is filled and reviewed, not the content structure.

- The business input contract for Pesantren, Asesor, and Admin must be preserved from the existing system. Field meaning, option sets, scoring scales, required rules, upload requirements, and validation behavior should remain equivalent unless a future PRD explicitly approves a change.

- The scoring formula is a non-negotiable compatibility contract. NA1, NA2, NK, NV, final score conversion, and accreditation ranking must produce the same result as the existing system for the same input data.

- A scoring engine or scoring service should encapsulate the existing formula so that workflow changes do not change assessment math.

- An input inventory should be created before implementation to map existing Pesantren inputs, Asesor inputs, Admin inputs, validation rules, document fields, and scoring fields into the new workflow.

- The workflow should distinguish initial submission, Assessment Awal, Admin stage 1 review, Asesor stage 2 review, Visitasi, Pasca Visitasi scoring, Admin final validation, final rejection, banding, and selesai.

- Super Admin remains a governance role and not an accreditation stage actor.

- A dedicated Super Admin setting area should be introduced for accreditation workflow policy.

- Workflow settings should be explicit and structured rather than only free-form key/value when the setting is core to workflow enforcement.

- The settings module should support at least these groups:
  - Deadline workflow.
  - Correction cycle limits.
  - Submit requirements.
  - Scoring/NV policy.
  - Notification policy.
  - Document requirement policy.
  - Lock/unlock policy.
  - Banding policy.

- Workflow setting changes must be audited with actor, old value, new value, timestamp, and optional reason.

- Perbaikan must be section/category-based. A correction request should know which sections are unlocked and which actor requested the correction.

- Admin stage 1 correction and Ketua Asesor stage 2 correction should be distinguishable for reporting, deadline, notification, and cycle limit purposes.

- The system should allow configurable maximum correction cycles for Admin stage 1 and Asesor stage 2.

- When a correction limit is reached, the MVP default is to block automatic resubmission and require an explicit decision. Stage 1 requires Admin decision; Stage 2 requires Ketua Asesor decision with Admin visibility. Super Admin settings may later configure auto reject or escalation behavior, but automatic rejection must never happen without audit trail.

- Visitasi scheduling is owned by Ketua Asesor after Asesor stage 2 approval.

- Visitasi Selesai is submitted only by Ketua Asesor.

- NA1 is owned by Ketua Asesor.

- NA2 is owned by Anggota Asesor.

- NK is owned by Ketua Asesor and is gated by final NA1 and final NA2.

- Ketua Asesor submits hasil visitasi only after all configured requirements are satisfied.

- Kartu Kendali is uploaded by Pesantren and visible to Admin only.

- Laporan Individu Ketua, Laporan Kelompok, and Laporan Individu Anggota are visible to Admin only and hidden from Pesantren.

- Admin NV defaults to mirroring NK.

- Admin may change NV only if permitted by Super Admin settings.

- If NV differs from NK, reason is required according to configured reason mode.

- Banding remains available only after final rejection and within configured rules.

- If banding is accepted, the preferred default is to return to Validasi Akhir Admin unless product rules later define another return point.

- Backend contract should be finalized before frontend implementation begins.

- Role menus should be updated only after the backend state/permission contract is stable.

- The implementation should introduce or adapt deep workflow modules that can be tested independently:
  - Workflow policy/settings resolver.
  - Workflow transition/action service.
  - Correction/rejection service.
  - Deadline/reminder service.
  - Document visibility/requirement service.
  - Scoring submission gate.
  - Audit trail service.
  - Notification recipient resolver.

## Testing Decisions

- Tests should verify external behavior and business outcomes, not internal implementation details.

- Feature tests should cover HTTP actions because the application has moved to full Blade forms/controllers.

- State machine tests should cover every allowed transition and representative forbidden transitions for wrong status, wrong actor, wrong owner, wrong assignment, and stale state.

- Service tests should cover workflow rules that are independent from UI details.

- Policy tests should cover role boundaries and visibility.

- Notification tests should cover event recipients, especially Admin versus Super Admin behavior.

- Audit trail tests should cover sensitive decisions and setting changes.

- Deadline tests should cover reminder, overdue, correction deadline, and banding deadline behavior.

- Document visibility tests should cover Kartu Kendali and laporan visitasi restrictions.

- Scoring tests should cover NA1, NA2, NK, NV mirroring, NV override reason, and final submission gates.

- Formula regression tests should compare known existing input examples against the new scoring implementation and prove that NA1, NA2, NK, NV, final score, and ranking remain unchanged.

- Input contract regression tests should verify that required fields, option sets, scoring scales, upload requirements, and validation behavior for Pesantren, Asesor, and Admin remain equivalent to the existing system.

- Role interaction tests should verify each handoff creates the correct next task, notification recipient, audit log, and available action for the receiving role.

- Field requirement tests should verify required fields per role and phase, including Pesantren profile minimum, Assessment Awal, correction sections, NA1, NA2, NK, NV, SK/certificate, and Super Admin settings.

- Active application tests should verify that one Pesantren cannot create multiple active non-terminal accreditation processes.

- Reassignment tests should verify Ketua/Anggota Asesor replacement, required reason, audit log, notification, and preservation of existing scoring/catatan when reassignment happens after scoring starts.

- End-to-end MVP tests should prove a full accreditation cycle can reach SK/certificate publication and Pesantren can access the final result.

- Frontend contract tests should assert that workflow buttons/forms post to real backend routes and do not depend on legacy reactive layer `legacy client binding` or client-only business decisions.

- Migration/inventory tests should verify that required master data, input baseline, and formula baseline are present before the new environment is declared ready.

- Readiness review should verify every Implementation Readiness Deliverable exists, has an owner, and is linked to test coverage or migration validation.

- Super Admin settings tests should cover:
  - Default values.
  - Update authorization.
  - Validation of setting ranges.
  - Audit log creation.
  - Effective setting used by workflow actions.

- Regression tests should be added for:
  - Pesantren initial submission with profile only.
  - Admin opens Assessment Awal.
  - Pesantren submits Assessment Awal.
  - Admin requests stage 1 correction.
  - Pesantren submits stage 1 correction.
  - Admin assigns asesor.
  - Ketua Asesor requests stage 2 correction.
  - Pesantren submits stage 2 correction.
  - Ketua Asesor schedules visitasi.
  - Ketua Asesor marks visitasi selesai.
  - Ketua Asesor inputs NA1.
  - Anggota Asesor inputs NA2.
  - Ketua Asesor inputs NK after NA1/NA2 final.
  - Document upload visibility.
  - Ketua Asesor submits hasil visitasi.
  - Admin final validation and SK issuance.
- Final rejection and banding.
  - Administrative rejection before final validation and correction limit review.

## Out of Scope

- Redesigning the EDPM/IPR scoring structure.

- Changing the existing scoring formula, scoring components, scoring butir, scoring scales, final score conversion, or accreditation ranking logic.

- Changing existing Pesantren, Asesor, or Admin business inputs, unless a later PRD explicitly defines the change.

- Changing the visual frontend implementation before backend contracts are approved.

- Removing existing banding behavior unless a later PRD explicitly changes banding rules.

- Replacing the full authentication, SSO, or role system.

- Changing certificate design or printed SK format.

- Changing public document taxonomy beyond visibility and requirement rules needed by this flow.

- Implementing the PRD in this document creation step.

## Further Notes

- This PRD intentionally separates product flow from current implementation details, but it now defines State Machine V2 as the target workflow contract for rebuild. A later technical design should map the named V2 statuses to database schema, services, routes, and UI screens.

- Decisions now locked in this PRD:
  - MVP must reach SK and certificate publication for Pesantren.
  - Super Admin settings are part of MVP.
  - Existing input contracts for Pesantren, Asesor, and Admin must be preserved.
  - Existing EDPM/IPR scoring structure, butir, weights, formulas, final score conversion, and ranking must be preserved.
  - Default deadline, correction cycle, document, NV, notification, lock/unlock, and banding policies are defined in Super Admin Default Policy Baseline.
  - State Machine V2 uses named statuses, not legacy numeric status as the primary contract.

- Remaining decisions before implementation are limited to:
  - Exact Profil Pesantren minimum field list for initial submission.
  - Exact inventory of existing Pesantren, Asesor, and Admin inputs that must be preserved.
  - Golden sample scoring data used for formula regression tests.
  - Whether active akreditasi from the old environment will be migrated, archived, or completed manually.
  - Exact certificate/SK rendering mechanism if the new environment changes storage or PDF generation.

- The current canonical state path in the existing system is simpler than the proposed flow. The new flow needs a careful state-machine mapping before implementation.

- This PRD should remain synchronized with the new flow document, backend role-module audit plan, backend fix issue plan, and frontend audit document.

