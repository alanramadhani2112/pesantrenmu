# Implementasi Dokumen Penilaian Pasca Visitasi

Tanggal update: 23 Mei 2026

## Tujuan

Dokumen ini mencatat proses hardening yang sudah dilakukan untuk fase Penilaian
Pasca Visitasi pada workflow akreditasi LP2M. Fokusnya adalah memastikan dokumen
wajib setelah visitasi tidak bisa dilewati sebelum asesor memfinalisasi
penilaian dan sebelum admin menerbitkan SK.

## Latar Belakang

Dalam business flow LP2M, setelah visitasi selesai:

- Ketua Kelompok mengunggah laporan individu.
- Anggota Kelompok mengunggah laporan individu.
- Ketua Kelompok mengunggah laporan kelompok.
- Pesantren mengunggah kartu kendali.
- Admin mereview seluruh paket akhir sebelum finalisasi hasil dan penerbitan SK.

Sebelum hardening ini, sebagian field dokumen sudah tersedia, tetapi rule bisnis
belum terkunci penuh di service layer. Beberapa jalur lama juga masih memakai
asumsi status lama, terutama upload kartu kendali dan laporan visitasi.

## Scope Yang Dikerjakan

### 1. Canonical upload service

Upload dokumen pasca visitasi dipusatkan di `AkreditasiDocumentService`.

Method yang ditambahkan:

- `uploadKartuKendaliForPesantren()`
- `uploadLaporanIndividuForAsesor()`
- `uploadLaporanKelompokForAsesor1()`
- `missingPostVisitasiDocuments()`

Dokumen wajib didefinisikan sebagai:

- `laporan_visitasi_asesor1`
- `laporan_visitasi_asesor2`
- `laporan_visitasi_kelompok`
- `kartu_kendali`

### 2. Guard role dan status

Rule yang sekarang enforced:

- Pesantren hanya dapat mengunggah kartu kendali milik pengajuannya sendiri.
- Kartu kendali hanya dapat diunggah pada status `2` atau `Penilaian Pasca Visitasi`.
- Asesor hanya dapat mengunggah laporan individu jika ditugaskan pada akreditasi
  tersebut.
- Ketua Kelompok mengisi field `laporan_visitasi_asesor1`.
- Anggota Kelompok mengisi field `laporan_visitasi_asesor2`.
- Hanya Ketua Kelompok yang dapat mengunggah `laporan_visitasi_kelompok`.
- Semua upload dokumen pasca visitasi tetap melewati validasi tipe file dan
  ukuran file.

### 3. Blocking finalisasi asesor

`AkreditasiWorkflowService::finalizeAssessorScoring()` sekarang memastikan:

- Nilai Ketua (`NA1`) final lengkap.
- Nilai Anggota (`NA2`) final lengkap.
- Nilai Kelompok (`NK`) final lengkap.
- Catatan butir lengkap.
- Catatan rekomendasi komponen lengkap.
- Semua dokumen pasca visitasi wajib sudah ada.

Jika salah satu dokumen belum ada, status tetap `2` dan finalisasi asesor
ditolak dengan pesan dokumen yang hilang.

### 4. Blocking penerbitan SK

`AkreditasiWorkflowService::issueSK()` sekarang juga melakukan pengecekan
dokumen pasca visitasi. Ini penting karena status `1` atau `Validasi Admin`
bisa terjadi dari beberapa jalur, termasuk hasil banding yang diterima.

Dengan guard ini, SK tidak bisa diterbitkan hanya karena NV sudah final jika
paket dokumen pasca visitasi belum lengkap.

### 5. Perapihan jalur legacy

Jalur lama tetap dipertahankan untuk kompatibilitas, tetapi diselaraskan:

- `PesantrenService::uploadKartuKendali()` memakai status `2`.
- `AsesorService::uploadLaporanVisitasi()` memakai field baru:
  - `laporan_visitasi_asesor1`
  - `laporan_visitasi_asesor2`
- Handler Livewire asesor diarahkan ke `AkreditasiDocumentService`.
- Handler Livewire pesantren untuk kartu kendali diarahkan ke
  `AkreditasiDocumentService`.

### 6. Konsistensi `asesor_id`

Pada finalisasi penilaian asesor, query scoring diselaraskan agar memakai
`asesors.id`, bukan `users.id`. Ini sesuai foreign key tabel
`akreditasi_edpms` dan konsisten dengan `AssessorScoringService`.

## File Yang Terdampak

### Service layer

- `app/Services/AkreditasiDocumentService.php`
- `app/Services/AkreditasiWorkflowService.php`
- `app/Services/PesantrenService.php`
- `app/Services/AsesorService.php`

### UI handler

- `app/Livewire/Pages/Asesor/AkreditasiDetail.php`
- `resources/views/livewire/pages/pesantren/akreditasi-detail.blade.php`

### Test

- `tests/Feature/AkreditasiWorkflow/PostVisitasiDocumentsTest.php`
- `tests/Feature/AkreditasiWorkflow/FullHappyPathTest.php`
- `tests/Feature/AkreditasiWorkflow/RejectionResubmissionPathTest.php`
- `tests/Feature/Asesor/AkreditasiDetailTest.php`
- `tests/Feature/PesantrenUploadTest.php`
- `tests/Feature/NonBlockingNotificationsTest.php`

## TDD Yang Dilakukan

Test baru dibuat di:

`tests/Feature/AkreditasiWorkflow/PostVisitasiDocumentsTest.php`

Coverage utama:

- Pesantren dapat upload kartu kendali hanya setelah visitasi selesai.
- Pesantren tidak dapat upload kartu kendali untuk akreditasi milik user lain.
- Asesor hanya dapat upload laporan individu sesuai penugasannya.
- Hanya Ketua Kelompok yang dapat upload laporan kelompok.
- Jalur legacy upload kartu kendali memakai status Penilaian Pasca Visitasi.
- Finalisasi penilaian asesor tertahan bila salah satu dokumen wajib hilang.
- Finalisasi penilaian asesor berhasil bila skor dan dokumen lengkap.
- Penerbitan SK tertahan bila dokumen wajib hilang walaupun NV sudah lengkap.
- Penerbitan SK berhasil bila NV dan dokumen lengkap.

## Hasil Verifikasi

Targeted test:

```bash
php artisan test tests\Feature\AkreditasiWorkflow\PostVisitasiDocumentsTest.php --no-ansi
```

Hasil:

```text
9 passed, 39 assertions
```

Sweep workflow terkait:

```bash
php artisan test tests\Feature\AkreditasiWorkflow tests\Unit\Workflow tests\Unit\Document tests\Feature\Asesor tests\Feature\PesantrenUploadTest.php tests\Feature\NonBlockingNotificationsTest.php --no-ansi
```

Hasil:

```text
159 passed, 11859 assertions
```

Blade compile:

```bash
php artisan view:cache --no-ansi
```

Hasil:

```text
Blade templates cached successfully.
```

## Status Akhir

Fase dokumen pasca visitasi sekarang sudah robust untuk production baseline.
Sistem sudah memiliki guard di service layer, UI handler, legacy wrapper, dan
test regresi.

## Catatan Lanjutan

Pekerjaan berikutnya yang logis:

- Audit UI admin pada tab laporan visitasi agar admin melihat checklist dokumen
  dengan status lengkap/belum lengkap.
- Tambahkan audit trail eksplisit untuk upload/ganti dokumen pasca visitasi.
- Tambahkan notifikasi khusus ketika semua dokumen pasca visitasi lengkap.
- Lanjut hardening nilai verifikasi `NV`, termasuk alasan jika admin mengubah
  `NV` dari default `NK`.
