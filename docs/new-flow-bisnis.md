# New Flow Bisnis Akreditasi

Dokumen ini menjadi acuan flow bisnis baru akreditasi. Dokumen ini masih draft hidup dan akan terus diperbarui sebelum diterjemahkan menjadi perubahan backend/frontend.

Catatan awal:

- Bagian struktur penilaian EDPM/komponen penilaian existing tidak diubah.
- Perubahan utama ada pada urutan proses, pembagian tanggung jawab role, dan flow penolakan/perbaikan.
- Flow ini belum berarti sudah diimplementasikan di code.

## 1. Prinsip Flow Baru

1. Pesantren tidak perlu mengisi semua data sejak awal.
2. Syarat awal pengajuan hanya Profil Pesantren.
3. IPM, EDPM/IPR, SDM, dan berkas pendukung diisi setelah Admin menerima pengajuan awal dan membuka fase Assessment Awal.
4. Pengecekan berkas dibuat dua tahap:
   - Tahap 1 oleh Admin.
   - Tahap 2 oleh Ketua Asesor sebelum visitasi.
5. Perbaikan data harus berbasis section/kategori yang ditolak, bukan membuka semua data.
6. Dokumen pasca visitasi dipisahkan hak aksesnya:
   - Kartu Kendali dari Pesantren hanya untuk Admin.
   - Laporan Visitasi dari Asesor hanya untuk Admin.
7. Super Admin bukan tahap workflow akreditasi; Super Admin adalah role governance/admin-area bypass.

## 2. Role dan Tanggung Jawab

| Role | Tanggung Jawab Utama |
|---|---|
| Pesantren | Mengisi profil awal, submit pengajuan, mengisi Assessment Awal, memperbaiki section yang ditolak, upload Kartu Kendali, menerima hasil akhir |
| Admin | Review pengajuan awal, membuka Assessment Awal, review berkas tahap 1, assign asesor, validasi akhir, memberi NV, menerbitkan SK/sertifikat |
| Ketua Asesor | Review berkas tahap 2, menentukan layak visitasi, memberi catatan perbaikan, menjadwalkan visitasi, submit visitasi selesai, input NA1, input NK, upload laporan individu dan kelompok, submit hasil visitasi |
| Anggota Asesor | Ikut visitasi, input NA2, upload laporan individu |
| Super Admin | Governance sistem, role/permission, akses admin-area, monitoring, bukan aktor tahap akreditasi tersendiri |

## 3. Flow Utama

### 3.1 Profil Awal Pesantren

Pesantren melengkapi Profil Pesantren sebagai syarat awal pengajuan akreditasi.

Data yang diperlukan pada fase ini:

- Identitas pesantren.
- Data dasar lembaga.
- Informasi kontak.
- Data awal yang diperlukan untuk validasi pengajuan.

Pada fase ini Pesantren belum wajib mengisi:

- IPM.
- EDPM/IPR.
- SDM.
- Semua dokumen pendukung Assessment Awal.

### 3.2 Pengajuan Akreditasi

Setelah Profil Pesantren cukup, Pesantren submit pengajuan akreditasi.

Output fase ini:

- Pengajuan masuk ke daftar review Admin.
- Admin bisa menerima atau menolak pengajuan awal.

### 3.3 Review Pengajuan Awal oleh Admin

Admin melakukan review awal terhadap profil dan kelayakan dasar pengajuan.

Keputusan Admin:

| Keputusan | Dampak |
|---|---|
| Diterima | Pengajuan masuk ke fase Assessment Awal |
| Ditolak | Pesantren menerima alasan penolakan dan memperbaiki Profil Pesantren |

### 3.4 Assessment Awal oleh Pesantren

Jika Admin menerima pengajuan, Admin membuka atau menjadwalkan fase Assessment Awal.

Pada fase ini Pesantren mengisi dan submit:

- IPM.
- EDPM/IPR.
- SDM.
- Berkas pendukung.

Catatan:

- Struktur penilaian EDPM existing tetap digunakan.
- Perubahan flow hanya mengubah waktu/konteks pengisian, bukan struktur EDPM.

### 3.5 Pengecekan Berkas Tahap 1 oleh Admin

Setelah Pesantren submit Assessment Awal, Admin melakukan pengecekan berkas tahap 1.

Keputusan Admin:

| Keputusan | Dampak |
|---|---|
| Lolos | Admin menunjuk Ketua Asesor dan Anggota Asesor |
| Perlu perbaikan | Admin memilih section/kategori bermasalah dan memberi catatan |
| Ditolak | Pengajuan berhenti atau masuk flow penolakan sesuai keputusan bisnis |

Jika perlu perbaikan:

- Sistem hanya membuka section yang ditolak.
- Pesantren memperbaiki section tersebut.
- Pesantren submit ulang perbaikan.
- Admin review ulang tahap 1.

### 3.6 Assign Asesor oleh Admin

Jika Assessment Awal lolos pengecekan tahap 1, Admin menunjuk:

- Ketua Asesor.
- Anggota Asesor.

Setelah asesor ditunjuk, proses masuk ke review berkas tahap 2 oleh Ketua Asesor.

### 3.7 Pengecekan Berkas Tahap 2 oleh Ketua Asesor

Ketua Asesor melakukan review kelayakan visitasi berdasarkan Assessment Awal.

Fokus Ketua Asesor:

- Mengecek kelayakan visitasi.
- Mengecek konsistensi berkas.
- Mengecek section/kategori yang berpengaruh pada kesiapan visitasi.

Keputusan Ketua Asesor:

| Keputusan | Dampak |
|---|---|
| Layak Visitasi | Ketua Asesor menjadwalkan visitasi |
| Tidak Layak Visitasi | Ketua Asesor memberi catatan perbaikan per section/kategori |

Jika tidak layak visitasi:

- Ketua Asesor mencentang kategori/section bermasalah.
- Ketua Asesor memberi catatan perbaikan.
- Sistem membuka hanya section yang perlu diperbaiki.
- Pesantren memperbaiki section tersebut.
- Pesantren submit perbaikan.
- Ketua Asesor review ulang.

### 3.8 Jadwal Visitasi

Jika Ketua Asesor menyatakan berkas layak visitasi, Ketua Asesor menjadwalkan visitasi.

Visitasi dilakukan di luar sistem.

### 3.9 Visitasi Selesai

Setelah visitasi selesai di luar sistem, Ketua Asesor klik tombol Visitasi Selesai.

Catatan:

- Hanya Ketua Asesor yang boleh submit Visitasi Selesai.
- Anggota Asesor tidak melakukan submit Visitasi Selesai.

### 3.10 Penilaian Pasca Visitasi

Setelah Visitasi Selesai, proses masuk ke fase penilaian.

Pembagian tugas:

| Aktor | Tugas |
|---|---|
| Ketua Asesor | Input NA1 |
| Anggota Asesor | Input NA2 |
| Ketua Asesor | Input NK setelah NA1 dan NA2 lengkap/final |

Aturan:

- NA1 diinput oleh Ketua Asesor.
- NA2 diinput oleh Anggota Asesor.
- NK baru boleh diinput Ketua Asesor setelah NA1 dan NA2 lengkap/final.
- Saat input NK, Ketua Asesor mengisi catatan butir/rekomendasi sesuai form/komponen penilaian existing.
- Struktur penilaian EDPM/komponen existing tidak diubah.

### 3.11 Upload Dokumen Pasca Visitasi

Pada fase pasca visitasi, dokumen wajib dipisahkan berdasarkan role dan visibility.

| Dokumen | Pengunggah | Penerima/Viewer | Tidak Boleh Melihat |
|---|---|---|---|
| Kartu Kendali | Pesantren | Admin | Asesor |
| Laporan Individu Ketua | Ketua Asesor | Admin | Pesantren |
| Laporan Kelompok | Ketua Asesor | Admin | Pesantren |
| Laporan Individu Anggota | Anggota Asesor | Admin | Pesantren |

Catatan:

- Ketua Asesor upload dua laporan: laporan individu dan laporan kelompok.
- Anggota Asesor hanya upload laporan individu.
- Pesantren tidak melihat laporan asesor.
- Asesor tidak melihat Kartu Kendali.

### 3.12 Submit Hasil Visitasi oleh Ketua Asesor

Ketua Asesor submit hasil visitasi setelah semua syarat lengkap:

- NA1 lengkap/final.
- NA2 lengkap/final.
- NK lengkap/final.
- Catatan butir/rekomendasi lengkap sesuai kebutuhan form existing.
- Laporan individu Ketua terupload.
- Laporan kelompok terupload.
- Laporan individu Anggota terupload.
- Kartu Kendali Pesantren terupload.

Output:

- Hasil visitasi masuk ke Admin untuk validasi akhir.

### 3.13 Validasi Akhir oleh Admin

Admin memverifikasi hasil kerja asesor.

Admin memberi Nilai Verifikasi atau NV.

Aturan NV:

- Default NV mirror dari NK.
- Jika Admin menyetujui NK, NV sama dengan NK.
- Jika Admin mengubah NV sehingga berbeda dari NK, Admin wajib mengisi alasan/audit reason.

Keputusan Admin:

| Keputusan | Dampak |
|---|---|
| Disetujui | Admin menerbitkan SK dan sertifikat |
| Ditolak Final | Pesantren menerima hasil penolakan dan dapat masuk flow banding jika memenuhi syarat |

### 3.14 Penerbitan SK dan Sertifikat

Jika validasi akhir disetujui, Admin menerbitkan:

- Nomor SK.
- Masa berlaku.
- Sertifikat.
- Peringkat/nilai akhir.
- Catatan rekomendasi yang boleh diketahui Pesantren.

### 3.15 Hasil Akhir untuk Pesantren

Pesantren menerima:

- Nilai akhir.
- Peringkat.
- Nomor SK.
- Sertifikat.
- Masa berlaku.
- Catatan rekomendasi yang boleh diketahui Pesantren.

Pesantren tidak menerima:

- Laporan individu asesor.
- Laporan kelompok asesor.
- Informasi internal validasi Admin yang tidak ditujukan untuk Pesantren.

## 4. Flow Penolakan dan Perbaikan

### 4.1 Penolakan Pengajuan Awal oleh Admin

Kondisi:

- Profil awal belum memenuhi syarat dasar.
- Data profil tidak valid atau belum cukup untuk membuka Assessment Awal.

Aksi Admin:

- Menolak pengajuan awal.
- Memberi alasan penolakan.

Aksi Pesantren:

- Memperbaiki Profil Pesantren.
- Submit ulang pengajuan.

Alur:

```text
Profil Awal
  -> Pengajuan
  -> Admin Review Pengajuan
    -> Ditolak Awal
    -> Pesantren Perbaiki Profil
    -> Submit Ulang Pengajuan
```

### 4.2 Penolakan atau Perbaikan Assessment Awal oleh Admin

Kondisi:

- IPM belum sesuai.
- EDPM/IPR belum sesuai.
- SDM belum sesuai.
- Berkas pendukung belum sesuai.
- Assessment Awal belum layak diteruskan ke asesor.

Aksi Admin:

- Memilih section/kategori yang perlu diperbaiki.
- Memberi catatan perbaikan.

Aksi Sistem:

- Membuka hanya section/kategori yang ditolak.
- Section lain tetap terkunci jika tidak perlu diperbaiki.

Aksi Pesantren:

- Memperbaiki section/kategori yang dibuka.
- Submit perbaikan Assessment Awal.

Alur:

```text
Assessment Awal
  -> Admin Review Tahap 1
    -> Perlu Perbaikan
    -> Sistem Unlock Section Tertentu
    -> Pesantren Perbaiki Section
    -> Submit Perbaikan
    -> Admin Review Ulang Tahap 1
```

### 4.3 Penolakan Kelayakan Visitasi oleh Ketua Asesor

Kondisi:

- Ketua Asesor menilai berkas belum layak untuk visitasi.
- Ada section/kategori yang belum sesuai untuk dibawa ke visitasi.

Aksi Ketua Asesor:

- Menolak kelayakan visitasi.
- Memilih section/kategori bermasalah.
- Memberi catatan perbaikan.

Aksi Sistem:

- Membuka hanya section/kategori yang ditolak.

Aksi Pesantren:

- Memperbaiki section/kategori tersebut.
- Submit perbaikan.

Alur:

```text
Review Asesor Tahap 2
  -> Tidak Layak Visitasi
  -> Ketua Asesor Beri Catatan Perbaikan
  -> Sistem Unlock Section Tertentu
  -> Pesantren Perbaiki Section
  -> Submit Perbaikan
  -> Ketua Asesor Review Ulang
  -> Layak Visitasi
  -> Jadwal Visitasi
```

### 4.4 Penolakan Validasi Akhir oleh Admin

Kondisi:

- Admin tidak menyetujui hasil akhir.
- Hasil visitasi/nilai/rekomendasi tidak dapat disahkan.

Aksi Admin:

- Menolak validasi akhir.
- Memilih kategori alasan.
- Memberi penjelasan penolakan.

Aksi Pesantren:

- Menerima status ditolak final.
- Bisa mengajukan banding jika memenuhi syarat dan masih dalam batas waktu.

Alur:

```text
Validasi Akhir Admin
  -> Ditolak Final
  -> Pesantren Terima Hasil Penolakan
  -> Banding Jika Memenuhi Syarat
```

### 4.5 Banding

Kondisi:

- Pesantren tidak menerima hasil penolakan final.
- Pesantren masih memenuhi syarat banding.

Aksi Pesantren:

- Mengajukan banding.

Aksi Admin/Super Admin:

- Menugaskan reviewer banding.
- Memproses keputusan banding.

Keputusan banding:

| Keputusan | Dampak |
|---|---|
| Banding diterima | Proses kembali ke validasi/admin review sesuai rule |
| Banding ditolak | Status tetap final ditolak |

## 5. Flow Ringkas End-to-End

```text
Profil Awal Pesantren
  -> Pengajuan Akreditasi
  -> Review Pengajuan oleh Admin
    -> Ditolak Awal -> Perbaiki Profil -> Submit Ulang
    -> Diterima -> Assessment Awal

Assessment Awal
  -> Pesantren isi IPM/EDPM/SDM/Berkas
  -> Admin Review Tahap 1
    -> Perlu Perbaikan -> Pesantren Perbaiki Section -> Submit Ulang
    -> Lolos -> Admin Assign Ketua Asesor dan Anggota Asesor

Review Asesor Tahap 2
  -> Ketua Asesor cek kelayakan visitasi
    -> Tidak Layak -> Pesantren Perbaiki Section -> Submit Ulang
    -> Layak Visitasi -> Ketua Asesor Jadwalkan Visitasi

Visitasi
  -> Visitasi dilakukan di luar sistem
  -> Ketua Asesor klik Visitasi Selesai

Penilaian Pasca Visitasi
  -> Ketua Asesor input NA1
  -> Anggota Asesor input NA2
  -> Ketua Asesor input NK setelah NA1 dan NA2 final
  -> Pesantren upload Kartu Kendali
  -> Ketua Asesor upload Laporan Individu dan Laporan Kelompok
  -> Anggota Asesor upload Laporan Individu
  -> Ketua Asesor submit Hasil Visitasi

Validasi Akhir Admin
  -> Admin verifikasi NV
    -> Ditolak Final -> Banding jika memenuhi syarat
    -> Disetujui -> Terbit SK/Sertifikat

Hasil Akhir
  -> Pesantren menerima nilai akhir, peringkat, SK, sertifikat, masa berlaku, dan catatan rekomendasi
```

## 6. Draft Status Konseptual

Status ini masih konseptual dan belum dipetakan ke state machine existing.

| Status Konseptual | Aktor Utama | Keterangan |
|---|---|---|
| Profil Awal | Pesantren | Pesantren melengkapi profil dasar |
| Pengajuan | Pesantren/Admin | Pengajuan masuk review Admin |
| Pengajuan Ditolak | Admin/Pesantren | Pesantren memperbaiki profil dan submit ulang |
| Assessment Awal | Pesantren | Pesantren mengisi IPM/EDPM/SDM/berkas |
| Review Admin Tahap 1 | Admin | Admin memeriksa Assessment Awal |
| Perbaikan Tahap 1 | Admin/Pesantren | Pesantren memperbaiki section yang ditolak Admin |
| Assign Asesor | Admin | Admin menunjuk Ketua dan Anggota Asesor |
| Review Asesor Tahap 2 | Ketua Asesor | Ketua Asesor cek kelayakan visitasi |
| Perbaikan Tahap 2 | Ketua Asesor/Pesantren | Pesantren memperbaiki section yang ditolak Ketua Asesor |
| Jadwal Visitasi | Ketua Asesor | Ketua Asesor menjadwalkan visitasi |
| Visitasi | Ketua Asesor/Anggota Asesor | Visitasi dilakukan di luar sistem |
| Visitasi Selesai | Ketua Asesor | Ketua Asesor submit visitasi selesai |
| Penilaian Pasca Visitasi | Ketua Asesor/Anggota Asesor | NA1, NA2, NK, catatan/rekomendasi |
| Dokumen Pasca Visitasi | Pesantren/Asesor/Admin | Kartu Kendali dan laporan visitasi |
| Submit Hasil Visitasi | Ketua Asesor | Hasil dikirim ke Admin |
| Validasi Akhir | Admin | Admin memberi NV dan keputusan akhir |
| Ditolak Final | Admin/Pesantren | Pesantren bisa banding jika memenuhi syarat |
| Selesai | Admin/Pesantren | SK, sertifikat, dan hasil akhir diterbitkan |

## 7. Pertanyaan Terbuka

1. Apakah penolakan pengajuan awal boleh benar-benar final, atau selalu berupa perbaikan profil?
2. Apakah Admin pada tahap 1 boleh menolak final sebelum masuk asesor, atau hanya meminta perbaikan?
3. Berapa batas maksimal siklus perbaikan tahap 1 dan tahap 2?
4. Apakah perbaikan tahap 1 dan tahap 2 memakai deadline berbeda?
5. Apakah Ketua Asesor boleh menolak kelayakan visitasi berkali-kali?
6. Apakah Kartu Kendali wajib sebelum Ketua Asesor submit hasil visitasi, atau cukup sebelum Admin validasi akhir?
7. Apakah laporan anggota asesor wajib sebelum Ketua Asesor submit hasil visitasi?
8. Apakah NV berbeda dari NK harus reason per butir atau boleh reason kolektif?
9. Jika banding diterima, proses kembali ke tahap Validasi Akhir atau tahap lain?

## 8. Catatan Untuk Implementasi Nanti

1. State machine existing perlu dipetakan ulang setelah flow ini disetujui.
2. Menu Pesantren perlu berubah karena IPM/EDPM/SDM bukan lagi syarat awal sebelum pengajuan.
3. Lock data Pesantren sebaiknya mengikuti section rejection/perbaikan, bukan global manual lock.
4. Visibility dokumen harus diperketat:
   - Kartu Kendali hanya Pesantren ke Admin.
   - Laporan Visitasi hanya Asesor ke Admin.
5. Action Ketua Asesor dan Anggota Asesor harus dipisah jelas.
6. Frontend tidak boleh diubah sebelum kontrak backend final.

