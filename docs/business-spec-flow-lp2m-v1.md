# Business Spec Flow LP2M V1

## Status

Dokumen ini adalah acuan bisnis V1 untuk alur akreditasi pesantren LP2M
(Lembaga Pengembangan Pesantren Muhammadiyah). Dokumen ini bukan alur
Dikdasmen.

Spec ini disusun dari diskusi bisnis project dan dikalibrasi dengan workbook
proses riil LP2M:

`C:\Users\LENOVO\Downloads\00 Rekap LK Akreditasi PM2025 untuk labsmu.xlsx`

Workbook tersebut memuat istilah hasil asesmen seperti `NA 1`, `NA 2`,
`Delta`, `NK`, `NV`, `CATATAN BUTIR (NK)`, dan
`CATATAN REKOMENDASI KOMPONEN (NK)`.

## Tujuan

- Menjadikan proses akreditasi LP2M sebagai workflow yang jelas dari pengajuan
  sampai hasil akhir.
- Memisahkan revisi administratif, perbaikan substansi, penolakan final, dan
  banding.
- Memastikan hasil akhir untuk pesantren tidak hanya berisi nilai dan sertifikat,
  tetapi juga catatan rekomendasi asesor per komponen.
- Menjadi acuan audit UI, business logic, policy, status, notifikasi, dan test.

## Aktor

| Aktor | Tanggung jawab |
| --- | --- |
| Pesantren | Mengisi data, submit pengajuan, memperbaiki data, upload kartu kendali, melihat hasil akhir, mengajukan banding bila ditolak final. |
| Admin Review Awal | Memeriksa kelengkapan administratif awal dan menunjuk asesor bila berkas layak. |
| Ketua Kelompok | Review substansi, menjadwalkan visitasi, konfirmasi visitasi selesai, input Nilai Ketua, input Nilai Kelompok setelah nilai dua asesor final, catatan butir, catatan rekomendasi komponen, upload laporan individu, upload laporan kelompok, final submit hasil asesor. |
| Anggota Kelompok | Review substansi, input Nilai Anggota, upload laporan individu. |
| Admin Validasi Akhir | Review paket akhir, input NV, finalisasi hasil, input SK, upload sertifikat, dan memutuskan banding. |

## Status Bisnis

| Status | Label bisnis | Makna |
| --- | --- | --- |
| `6` | Pengajuan | Pesantren sudah submit data akreditasi. |
| `5` | Review Awal Admin | Admin memeriksa kelengkapan administratif awal. |
| `4` | Review Asesor | Asesor menelaah berkas dan bukti pesantren. |
| `3` | Visitasi | Visitasi lapangan sudah dijadwalkan dan sedang berjalan. |
| `2` | Penilaian Pasca Visitasi | Visitasi selesai, asesor dan pesantren melengkapi nilai serta dokumen pasca visitasi. |
| `1` | Validasi Akhir Admin | Admin mereview hasil asesor dan mengisi nilai verifikasi. |
| `0` | Selesai / Terakreditasi | Hasil akhir disahkan dan sertifikat tersedia. |
| `-1` | Ditolak Final | Keputusan akhir tidak disahkan. |
| `-2` | Banding | Pesantren mengajukan keberatan atas penolakan final. |

## Prinsip Bisnis

- `A`, `B`, dan `C` semuanya berarti pesantren terakreditasi.
- Label hasil ke pesantren adalah `Terakreditasi Unggul`, `Terakreditasi Baik`,
  dan `Terakreditasi Cukup`.
- Review awal admin bukan penolakan final.
- Perbaikan substansi oleh asesor bukan penolakan final.
- Banding hanya tersedia setelah `Ditolak Final`.
- Jika banding diterima, proses kembali ke `Validasi Akhir Admin`.
- Pesantren tidak melihat nilai mentah `NA1`, `NA2`, `NK`, atau `NV`.
- Pesantren melihat nilai akhir, peringkat, SK, masa berlaku, sertifikat, dan
  catatan rekomendasi asesor per komponen.

## Nomenklatur UI

Istilah workbook tetap dipertahankan untuk audit data, ekspor, dan rumus.
Namun istilah utama pada UI menggunakan bahasa peran agar lebih jelas.

| Istilah workbook | Istilah UI |
| --- | --- |
| `Asesor 1` | Ketua Kelompok |
| `Asesor 2` | Anggota Kelompok |
| `NA1` | Nilai Ketua |
| `NA2` | Nilai Anggota |
| `NK` | Nilai Kelompok |
| `NV` | Nilai Verifikasi Admin |

## Flow Utama

### 1. Persiapan Data Pesantren

Pesantren mengisi data awal:

- Profil pesantren.
- IPM.
- EDPM.
- Data SDM.
- Bukti dan dokumen pendukung.

Data masih editable sampai pesantren melakukan submit pengajuan.

### 2. Pengajuan

Pesantren submit pengajuan akreditasi. Sistem memeriksa kelengkapan minimum.
Jika lengkap, pengajuan masuk ke antrean review awal admin.

### 3. Review Awal Admin

Admin Review Awal memeriksa kelengkapan administratif dan validitas awal berkas.

Outcome:

- `Lolos administratif`: admin menunjuk Ketua Kelompok dan Anggota Kelompok.
- `Revisi administratif`: pengajuan dikembalikan ke pesantren untuk diperbaiki.

Revisi administratif tidak membuka banding.

### 4. Review Asesor

Asesor menelaah seluruh isi pengajuan pesantren, termasuk profil, IPM, EDPM,
SDM, bukti, dan lampiran.

Outcome:

- `Lolos substansi`: Ketua Kelompok menjadwalkan visitasi.
- `Perbaikan substansi`: asesor menandai bagian bermasalah dan memberi catatan
  spesifik.

Perbaikan substansi bersifat terarah dan bukan penolakan final.

### 5. Visitasi

Ketua Kelompok menjadwalkan visitasi. Visitasi dilakukan di luar sistem.
Setelah visitasi selesai, Ketua Kelompok mengonfirmasi penyelesaian visitasi
di sistem.

### 6. Penilaian Pasca Visitasi

Setelah visitasi selesai, paket pasca visitasi harus dilengkapi.

Ketua Kelompok wajib mengisi dan mengunggah:

- `Nilai Ketua` (`NA1`).
- `Nilai Kelompok` (`NK`).
- `Catatan Butir (NK)`.
- `Catatan Rekomendasi Komponen (NK)`.
- `Laporan Individu Ketua Kelompok`.
- `Laporan Kelompok`.

Anggota Kelompok wajib mengisi dan mengunggah:

- `Nilai Anggota` (`NA2`).
- `Laporan Individu Anggota Kelompok`.

Pesantren wajib mengunggah:

- `Kartu Kendali`.

Nilai Ketua dan Nilai Anggota dapat diisi paralel oleh masing-masing asesor.
Nilai Kelompok baru terbuka setelah seluruh Nilai Ketua dan seluruh Nilai
Anggota disubmit final. Setelah seluruh paket lengkap, Ketua Kelompok melakukan
final submit hasil asesor.

Status implementasi per 23 Mei 2026:

- Upload kartu kendali hanya bisa dilakukan oleh pesantren pemilik pengajuan
  pada status `Penilaian Pasca Visitasi`.
- Upload laporan individu hanya bisa dilakukan oleh asesor yang ditugaskan.
- Upload laporan kelompok hanya bisa dilakukan oleh Ketua Kelompok.
- Final submit hasil asesor diblokir bila salah satu dokumen pasca visitasi
  wajib belum diunggah.
- Penerbitan SK juga diblokir bila dokumen pasca visitasi belum lengkap,
  meskipun `NV` sudah final.

### 7. Validasi Akhir Admin

Admin Validasi Akhir mereview paket akhir:

- Berkas pesantren.
- Input `Nilai Ketua` (`NA1`), `Nilai Anggota` (`NA2`), `Delta`, dan `Nilai Kelompok` (`NK`).
- Catatan butir.
- Catatan rekomendasi komponen.
- Kartu kendali.
- Laporan individu Ketua Kelompok.
- Laporan individu Anggota Kelompok.
- Laporan kelompok.

Admin mengisi `NV` (Nilai Verifikasi).

Rule `NV`:

- Default `NV` mengikuti `NK` dari Ketua Kelompok.
- Admin boleh mengubah `NV`.
- Jika admin mengubah `NV` dari default, sistem wajib menyimpan alasan perubahan
  untuk audit trail.

### 8. Perhitungan Hasil

Setelah seluruh `NV` final, sistem menghitung:

- Nilai akhir.
- Peringkat.
- Status hasil.

Peringkat internal dapat disimpan sebagai `A`, `B`, atau `C`. Tampilan bisnis
ke pesantren menggunakan label:

| Peringkat | Label ke pesantren |
| --- | --- |
| `A` | Terakreditasi Unggul |
| `B` | Terakreditasi Baik |
| `C` | Terakreditasi Cukup |

### 9. Keputusan Akhir

Jika hasil valid dan dapat disahkan, admin menyetujui hasil akhir dan mengisi:

- Nomor SK.
- Masa berlaku.
- Sertifikat akreditasi.

Jika ada masalah material yang membuat hasil tidak dapat disahkan, admin
menetapkan `Ditolak Final`.

### 10. Banding

Banding hanya tersedia untuk status `Ditolak Final`.

Flow banding:

- Pesantren mengajukan banding.
- Status menjadi `Banding`.
- Admin mereview banding.
- Jika banding diterima, proses kembali ke `Validasi Akhir Admin`.
- Jika banding ditolak, status kembali atau tetap `Ditolak Final`.

## Output Akhir Pesantren

Pesantren menerima:

- Nilai akhir.
- Peringkat akreditasi.
- Status terakreditasi.
- Nomor SK.
- Masa berlaku.
- Sertifikat akreditasi.
- Catatan rekomendasi asesor per komponen.

Pesantren tidak menerima tampilan nilai mentah:

- `Nilai Ketua` (`NA1`).
- `Nilai Anggota` (`NA2`).
- `Nilai Kelompok` (`NK`).
- `NV`.

## Matrix Implementasi

| Tahap | Aktor | Aksi bisnis | Data atau dokumen wajib | Output | Halaman terdampak |
| --- | --- | --- | --- | --- | --- |
| Persiapan data | Pesantren | Mengisi dan menyimpan data awal | Profil, IPM, SDM, EDPM, bukti | Draft siap submit | `/pesantren/profile`, `/pesantren/ipm`, `/pesantren/sdm`, `/pesantren/edpm` |
| Pengajuan | Pesantren | Submit akreditasi | Semua data minimum lengkap | Status `6` | `/pesantren/akreditasi`, `/pesantren/akreditasi/{uuid}` |
| Review awal | Admin Review Awal | Review administratif, assign asesor, atau kembalikan revisi | Paket data pesantren | Status `5`, lalu `4` bila lolos | `/admin/akreditasi`, `/admin/akreditasi/{uuid}` |
| Review substansi | Ketua Kelompok dan Anggota Kelompok | Telaah berkas dan catatan perbaikan | Profil, IPM, SDM, EDPM, bukti | Lolos substansi atau perbaikan | `/asesor/akreditasi`, `/asesor/akreditasi/{uuid}` |
| Penjadwalan visitasi | Ketua Kelompok | Jadwalkan visitasi | Tanggal mulai, tanggal akhir, catatan | Status `3` | `/asesor/akreditasi/{uuid}`, `/admin/akreditasi/{uuid}` |
| Konfirmasi visitasi | Ketua Kelompok | Konfirmasi visitasi selesai | Visitasi telah terlaksana | Status `2` | `/asesor/akreditasi/{uuid}` |
| Pasca visitasi | Ketua Kelompok, Anggota Kelompok, Pesantren | Input nilai, catatan, rekomendasi, dan upload dokumen | Nilai Ketua, Nilai Anggota, Nilai Kelompok, catatan butir, rekomendasi komponen, laporan individu, laporan kelompok, kartu kendali | Paket pasca visitasi lengkap | `/asesor/akreditasi/{uuid}`, `/pesantren/akreditasi/{uuid}` |
| Final submit asesor | Ketua Kelompok | Submit paket hasil asesor | Semua nilai dan dokumen wajib lengkap | Status `1`, notifikasi admin | `/asesor/akreditasi/{uuid}` |
| Validasi akhir | Admin Validasi Akhir | Review paket akhir dan isi NV | Paket akhir lengkap | NV draft atau final | `/admin/akreditasi/{uuid}` |
| Keputusan akhir | Admin Validasi Akhir | Setujui hasil atau tolak final | Semua NV final, keputusan valid | Status `0` atau `-1` | `/admin/akreditasi/{uuid}` |
| Hasil akhir | Pesantren | Melihat hasil resmi | Hasil akhir sudah disahkan | Nilai, peringkat, SK, sertifikat, rekomendasi | `/pesantren/akreditasi/{uuid}` |
| Banding | Pesantren dan Admin | Ajukan dan putuskan banding | Alasan banding | Status `-2`, lalu `1` atau `-1` | `/pesantren/akreditasi/{uuid}`, `/admin/banding`, `/admin/banding/{id}` |

## RACI Ringkas

| Aktivitas | Pesantren | Admin Review Awal | Ketua Kelompok | Anggota Kelompok | Admin Validasi Akhir |
| --- | --- | --- | --- | --- | --- |
| Isi data awal | R/A | I | I | I | I |
| Review administratif | I | R/A | I | I | I |
| Assign asesor | I | R/A | I | I | I |
| Review substansi | I | I | R/A | R | I |
| Jadwal visitasi | I | I | R/A | C | I |
| Konfirmasi visitasi selesai | I | I | R/A | C | I |
| Input Nilai Ketua | I | I | R/A | C | I |
| Input Nilai Anggota | I | I | C | R/A | I |
| Input Nilai Kelompok | I | I | R/A | C | I |
| Upload kartu kendali | R/A | I | I | I | I |
| Upload laporan kelompok | I | I | R/A | C | I |
| Validasi NV | I | I | I | I | R/A |
| Terbitkan SK | I | I | I | I | R/A |
| Ajukan banding | R/A | I | I | I | C |
| Putuskan banding | I | I | I | I | R/A |

Keterangan:

- `R`: Responsible.
- `A`: Accountable.
- `C`: Consulted.
- `I`: Informed.

## Acceptance Criteria

### Persiapan dan Pengajuan

- Pesantren tidak dapat submit jika profil, IPM, SDM, atau EDPM belum memenuhi
  kelengkapan minimum.
- Setelah submit, pengajuan masuk ke status `6`.
- Data pesantren terkunci untuk perubahan bebas setelah pengajuan aktif.

### Review Awal Admin

- Admin dapat membuka review awal dari status `6`.
- Admin dapat mengembalikan pengajuan untuk revisi administratif tanpa membuka
  banding.
- Admin dapat assign dua asesor berbeda dan melanjutkan ke status `4`.

### Review Asesor

- Asesor dapat menandai bagian yang perlu diperbaiki dan memberi catatan.
- Pesantren hanya memperbaiki bagian yang dibuka.
- Perbaikan substansi tidak dianggap `Ditolak Final`.

### Visitasi dan Penilaian Pasca Visitasi

- Hanya Ketua Kelompok yang dapat menjadwalkan visitasi.
- Hanya Ketua Kelompok yang dapat mengonfirmasi visitasi selesai.
- Nilai Ketua dan Nilai Anggota dapat diisi paralel oleh asesor masing-masing.
- Nilai Kelompok terkunci sampai seluruh Nilai Ketua dan Nilai Anggota
  disubmit final.
- Ketua Kelompok wajib mengisi `NA1`, `NK`, `Catatan Butir (NK)`, dan
  `Catatan Rekomendasi Komponen (NK)`.
- Anggota Kelompok wajib mengisi `NA2`.
- Pesantren wajib mengunggah kartu kendali.
- Laporan individu Ketua Kelompok, laporan individu Anggota Kelompok, dan
  laporan kelompok wajib tersedia sebelum final submit asesor.

### Validasi Akhir Admin

- Admin melihat semua berkas, nilai, catatan, rekomendasi, kartu kendali, dan
  laporan visitasi.
- `NV` default mengikuti `NK`.
- Admin dapat mengubah `NV`.
- Perubahan `NV` dari default wajib memiliki alasan audit.
- Semua `NV` harus final sebelum hasil akhir diterbitkan.

### Hasil Akhir

- Peringkat `A`, `B`, dan `C` semuanya menghasilkan status terakreditasi.
- Pesantren hanya melihat nilai akhir, peringkat, status, nomor SK, masa berlaku,
  sertifikat, dan catatan rekomendasi asesor per komponen.
- Pesantren tidak melihat `NA1`, `NA2`, `NK`, atau `NV`.

### Banding

- Banding hanya muncul untuk status `Ditolak Final`.
- Jika banding diterima, status kembali ke `Validasi Akhir Admin`.
- Jika banding ditolak, status tetap atau kembali ke `Ditolak Final`.

## Gap Existing yang Harus Diaudit

- Banding diterima harus tetap diaudit agar konsisten kembali ke
  `Validasi Akhir Admin` di seluruh service, UI, notifikasi, dan test.
- Review awal admin perlu dipisahkan lebih tegas dari penolakan final.
- Laporan kelompok wajib tampil sebagai dokumen review admin.
- Catatan rekomendasi asesor harus diposisikan sebagai output resmi hasil akhir.
- Rule `A/B/C` sebagai terakreditasi perlu disinkronkan dengan alasan penolakan
  berbasis nilai minimum.
- Perubahan `NV` dari `NK` perlu alasan audit yang eksplisit.
