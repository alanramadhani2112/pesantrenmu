# Panduan Penggunaan Sistem PesantrenMu

**Sistem Penjaminan Mutu Pesantren Muhammadiyah**
Versi: 1.0 | Tanggal: 1 Juni 2026

---

## Daftar Isi

1. [Pendahuluan](#pendahuluan)
2. [Login dan Navigasi](#login-dan-navigasi)
3. [Role: Pesantren](#role-pesantren)
4. [Role: Asesor](#role-asesor)
5. [Role: Admin](#role-admin)
6. [Notifikasi](#notifikasi)
7. [FAQ](#faq)

---

## Pendahuluan

PesantrenMu adalah sistem akreditasi pesantren Muhammadiyah yang dikelola oleh LP2M. Sistem ini mengelola seluruh alur akreditasi dari pengajuan hingga penerbitan hasil akhir.

### Alur Akreditasi

`
Pengajuan -> Review Awal -> Review Asesor -> Visitasi
-> Penilaian Pasca Visitasi -> Validasi Admin -> Hasil Akhir
`

### Role Pengguna

| Role | Fungsi Utama |
|------|-------------|
| Pesantren | Mengajukan akreditasi, melengkapi data, melihat hasil |
| Asesor | Menilai dokumen, melakukan visitasi, input nilai |
| Admin | Memverifikasi berkas, menugaskan asesor, menerbitkan SK |
| Super Admin | Mengelola role, permission, dan konfigurasi sistem |

---

## Login dan Navigasi

### Cara Login

1. Buka halaman login di alamat sistem
2. Masukkan email dan password yang sudah didaftarkan
3. Klik tombol **Masuk Dashboard**
4. Sistem akan mengarahkan ke dashboard sesuai role Anda

### Navigasi

- **Sidebar kiri**: Menu utama sesuai role
- **Header**: Notifikasi dan profil pengguna
- **Dashboard**: Ringkasan statistik dan aksi cepat

---

## Role: Pesantren

### A. Persiapan Data

Sebelum mengajukan akreditasi, pesantren wajib melengkapi:

#### 1. Profil Pesantren
- **Menu**: Persiapan Akreditasi > Profil Pesantren
- **Data wajib**: Nama pesantren, NSP, alamat, provinsi, kota/kabupaten, tahun pendirian, nama mudir, layanan satuan pendidikan
- **Dokumen**: Sertifikat NSP, dokumen profil lainnya
- **Catatan**: Data akan terkunci setelah pengajuan aktif

#### 2. IPM (Indikator Pemenuhan Mutlak)
- **Menu**: Persiapan Akreditasi > IPM
- **Data wajib**: Upload dokumen NSP, lulus santri, kurikulum, buku ajar
- **Format**: PDF, maksimal 2MB per file

#### 3. Data SDM
- **Menu**: Persiapan Akreditasi > Data SDM
- **Data wajib**: Jumlah santri (L/P), ustadz dirosah (L/P), ustadz non-dirosah, pamong, musyrif, tendik
- **Catatan**: Minimal 1 SDM harus terisi

#### 4. EDPM/IPR
- **Menu**: Persiapan Akreditasi > EDPM/IPR
- **Data wajib**: Isi evaluasi diri per butir (62 butir total: 40 IK + 22 IPR)
- **Komponen**: Mutu Lulusan (8), Proses Pembelajaran (10), Mutu Ustaz (10), Manajemen Pesantren (12), IPR (22)

### B. Pengajuan Akreditasi

#### Cara Submit Pengajuan
- **Menu**: Pengajuan > Pengajuan Akreditasi
- **Syarat**: Semua data (Profil, IPM, SDM, EDPM) harus lengkap
- **Langkah**:
  1. Pastikan indikator kesiapan data di sidebar menunjukkan "lengkap"
  2. Klik tombol **Ajukan Akreditasi**
  3. Sistem akan memvalidasi kelengkapan data
  4. Jika lengkap, pengajuan masuk ke antrean review admin
  5. Data pesantren otomatis terkunci

#### Status Pengajuan
| Status | Arti |
|--------|------|
| Pengajuan | Menunggu review admin |
| Verifikasi Berkas | Admin sedang memeriksa |
| Review Asesor | Asesor sedang menelaah |
| Visitasi | Visitasi dijadwalkan/berlangsung |
| Penilaian Pasca Visitasi | Asesor mengisi nilai |
| Validasi Admin | Admin memvalidasi hasil |
| Terakreditasi | Hasil akhir diterbitkan |
| Ditolak | Pengajuan ditolak final |

### C. Perbaikan Dokumen

Jika asesor meminta perbaikan:
- **Menu**: Pengajuan > Status Perbaikan
- Lihat catatan perbaikan dari asesor
- Perbaiki bagian yang ditandai
- Klik **Kirim Perbaikan** setelah selesai
- Batas waktu perbaikan: 14 hari

### D. Upload Kartu Kendali

Setelah visitasi selesai:
- **Menu**: Visitasi > Kartu Kendali
- Upload dokumen kartu kendali (PDF, max 5MB)
- Hanya bisa diupload saat status Penilaian Pasca Visitasi

### E. Melihat Hasil Akhir

- **Menu**: Hasil Akreditasi > Hasil Akhir
- Informasi yang ditampilkan:
  - Nilai akhir
  - Peringkat (Terakreditasi Unggul/Baik/Cukup)
  - Nomor SK
  - Masa berlaku
  - Sertifikat (unduh PDF)
  - Catatan rekomendasi asesor per komponen

### F. Mengajukan Banding

Jika pengajuan ditolak final:
- Banding hanya tersedia untuk status **Ditolak Final**
- Batas waktu: 14 hari sejak penolakan
- Masukkan alasan banding (min 10, max 1000 karakter)
- Admin akan mereview dan memutuskan

---

## Role: Asesor

### A. Menerima Tugas

- Asesor menerima notifikasi saat ditugaskan oleh admin
- **Menu**: Daftar Tugas
- Dua peran: **Ketua Kelompok** (Asesor 1) atau **Anggota Kelompok** (Asesor 2)

### B. Review Berkas

- **Menu**: Daftar Tugas > klik akreditasi > tab berkas
- Telaah profil, IPM, SDM, EDPM, dan dokumen pesantren
- Jika ada masalah substansi:
  - Tandai bagian bermasalah
  - Beri catatan spesifik
  - Pesantren akan memperbaiki bagian yang ditandai
- Jika lolos: Ketua Kelompok jadwalkan visitasi

### C. Penjadwalan Visitasi (Ketua Kelompok)

- **Menu**: Daftar Tugas > Detail > Jadwalkan Visitasi
- Isi tanggal mulai dan tanggal akhir
- Tambahkan catatan visitasi jika perlu
- Setelah visitasi selesai di lapangan, konfirmasi di sistem

### D. Input Nilai

#### Ketua Kelompok (NA1 + NK)
- Input Nilai Ketua (NA1) per butir (62 butir, skala 1-4)
- Input Nilai Kelompok (NK) setelah NA1 dan NA2 final
- Isi Catatan Butir per butir
- Isi Catatan Rekomendasi per komponen

#### Anggota Kelompok (NA2)
- Input Nilai Anggota (NA2) per butir (62 butir, skala 1-4)

**Catatan**: NA1 dan NA2 dapat diisi paralel. NK terkunci sampai keduanya final.

### E. Upload Laporan Visitasi

- **Menu**: Daftar Tugas > Detail > tab Laporan Visitasi
- **Ketua Kelompok**: Upload laporan individu + laporan kelompok
- **Anggota Kelompok**: Upload laporan individu
- Format: PDF/DOCX, max 5MB

### F. Final Submit

- Hanya Ketua Kelompok yang dapat melakukan final submit
- Syarat: Semua NA1, NA2, NK, catatan, dan dokumen wajib lengkap
- Setelah submit, status berubah ke Validasi Admin
- Admin menerima notifikasi

---
## Role: Admin

### A. Dashboard

- **Menu**: Monitoring > Dashboard
- Statistik: pengajuan berjalan, perlu verifikasi, sedang dinilai, visitasi, terakreditasi, ditolak
- Quick actions: Kelola Akreditasi, Data Pesantren, Data Asesor, Master EDPM

### B. Verifikasi Berkas (Review Awal)

- **Menu**: Monitoring > Akreditasi
- Filter berdasarkan tahap: Pengajuan, Verifikasi, Review Asesor, Visitasi, Validasi, Terlambat
- Untuk pengajuan baru (status Pengajuan):
  1. Klik **Buka untuk Review**
  2. Periksa kelengkapan administratif
  3. Jika lengkap: **Setujui Berkas** + assign 2 asesor (Ketua + Anggota)
  4. Jika tidak lengkap: **Tolak Berkas** dengan catatan

### C. Assign Asesor

- Saat menyetujui berkas, admin wajib memilih:
  - **Ketua Kelompok** (Asesor 1): review substansi, jadwal visitasi, input NA1+NK
  - **Anggota Kelompok** (Asesor 2): input NA2
- Kedua asesor harus berbeda
- Asesor menerima notifikasi penugasan

### D. Validasi Akhir

Setelah asesor submit paket final:
- **Menu**: Akreditasi > Detail > tab Nilai
- Review: NA1, NA2, NK, catatan butir, rekomendasi, kartu kendali, laporan
- Input **Nilai Verifikasi (NV)** per butir:
  - Default NV = NK
  - Jika mengubah NV dari default, wajib isi alasan (audit trail)
- Setelah semua NV final, sistem otomatis hitung:
  - Nilai akhir
  - Peringkat (A >= 86, B >= 71, C < 71)

### E. Terbitkan SK

- Syarat: Semua NV final + dokumen pasca visitasi lengkap
- Isi:
  - Nomor SK
  - Masa berlaku (tanggal mulai + akhir)
  - Upload sertifikat (PDF)
  - Catatan rekomendasi admin (opsional)
- Klik **Terbitkan SK**
- Pesantren menerima notifikasi hasil

### F. Penolakan Final

- Jika hasil tidak dapat disahkan:
  - Klik **Tolak** dengan alasan (max 2000 karakter)
  - Status berubah ke Ditolak Final
  - Pesantren dapat mengajukan banding

### G. Banding

- **Menu**: Operasional > Banding
- Lihat daftar banding yang masuk
- Klik detail untuk review
- Keputusan:
  - **Terima**: Proses kembali ke Validasi Akhir Admin
  - **Tolak**: Status tetap Ditolak Final

### H. Master Data

- **Komponen EDPM/IPR**: Kelola komponen dan butir pernyataan
- **Daftar Pesantren**: Lihat semua pesantren terdaftar
- **Daftar Asesor**: Lihat dan kelola data asesor
- **Kategori Dokumen**: Kelola kategori dokumen wajib

### I. Administrasi (Super Admin)

- **Akun Pengguna**: Kelola akun admin, asesor, pesantren
- **Role Sistem**: Kelola katalog role
- **Hak Akses**: Matriks permission per role
- **Notifikasi Gagal**: Pantau notifikasi yang gagal terkirim
- **Arsip Akreditasi**: Kelola data terhapus (restore/hapus permanen)

---

## Notifikasi

Sistem mengirim notifikasi otomatis pada setiap tahap:

| Kejadian | Penerima |
|----------|----------|
| Pengajuan baru | Admin |
| Asesor ditugaskan | Ketua + Anggota Kelompok |
| Visitasi dijadwalkan | Pesantren + Anggota + Admin |
| Visitasi selesai | Pesantren + Anggota + Admin |
| Paket asesor final | Admin |
| SK diterbitkan | Pesantren |
| Penolakan (berkas/asesor/validasi) | Pesantren |
| Banding diajukan | Admin |
| Banding diputuskan | Pesantren |
| Deadline perbaikan mendekat | Pesantren |

Notifikasi muncul di:
- Bell icon di header (in-app)
- Web Push notification (jika diizinkan browser)

---

## FAQ

### Umum

**Q: Bagaimana jika lupa password?**
A: Klik "Lupa Password" di halaman login, masukkan email, ikuti instruksi reset.

**Q: Apakah data bisa diedit setelah pengajuan?**
A: Tidak. Data terkunci setelah pengajuan aktif, kecuali bagian yang dibuka untuk perbaikan oleh asesor.

### Pesantren

**Q: Berapa lama proses akreditasi?**
A: Tergantung kelengkapan data dan jadwal visitasi. Sistem tidak membatasi durasi total.

**Q: Apa bedanya Ditolak dan Perbaikan?**
A: Perbaikan = asesor minta revisi bagian tertentu (bukan penolakan). Ditolak = keputusan final admin yang membuka opsi banding.

**Q: Apakah peringkat C berarti gagal?**
A: Tidak. A, B, dan C semuanya berarti Terakreditasi (Unggul/Baik/Cukup).

### Asesor

**Q: Kapan bisa input NK?**
A: Setelah NA1 (Nilai Ketua) dan NA2 (Nilai Anggota) keduanya final.

**Q: Siapa yang upload laporan kelompok?**
A: Hanya Ketua Kelompok.

### Admin

**Q: Apakah NV harus sama dengan NK?**
A: Default NV = NK, tapi admin boleh mengubah dengan alasan yang tercatat di audit trail.

**Q: Apa yang terjadi jika banding diterima?**
A: Proses kembali ke tahap Validasi Akhir Admin untuk direview ulang.

---

*Dokumen ini dibuat otomatis berdasarkan spesifikasi bisnis LP2M V1.*
*Terakhir diperbarui: 1 Juni 2026*
