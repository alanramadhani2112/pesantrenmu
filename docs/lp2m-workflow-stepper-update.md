# LP2M Workflow Stepper Update

Date: 2026-05-25

## Summary

Dokumen ini mencatat proses standarisasi tahapan akreditasi LP2M pada halaman
daftar dan detail akreditasi. Fokus perubahan adalah memastikan semua role
melihat alur yang sama, dengan nomenklatur bisnis yang benar dan tidak
menempatkan penilaian asesor sebelum visitasi.

## Business Decision

Alur yang dipakai pada UI:

1. Pengajuan
2. Review Awal
3. Review Asesor
4. Visitasi
5. Penilaian Pasca Visitasi
6. Validasi Admin
7. Hasil Akhir

Keputusan penting:

- `status 4` ditampilkan sebagai `Review Asesor`.
- `status 3` ditampilkan sebagai `Visitasi`.
- `status 2` ditampilkan sebagai `Penilaian Pasca Visitasi`.
- `status 1` ditampilkan sebagai `Validasi Admin`.
- Nilai Ketua dan Nilai Anggota baru relevan setelah visitasi selesai.
- Nilai Kelompok baru final setelah Nilai Ketua dan Nilai Anggota final.
- Nilai Verifikasi Admin mengikuti Nilai Kelompok sebagai default, tetapi tetap editable oleh admin.

## UI Scope

Stepper akreditasi dibuat reusable melalui:

- `resources/views/components/akreditasi/workflow-stepper.blade.php`
- `resources/views/components/ui/stepper.blade.php`

Stepper diterapkan pada:

- `resources/views/livewire/pages/admin/akreditasi.blade.php`
- `resources/views/livewire/pages/admin/akreditasi-detail.blade.php`
- `resources/views/livewire/pages/asesor/akreditasi-detail.blade.php`
- `resources/views/livewire/pages/pesantren/akreditasi-detail.blade.php`

Halaman list admin memakai stepper sebagai peta alur global. Halaman detail
admin, asesor, dan pesantren memakai stepper yang sama dengan current step
berdasarkan status akreditasi.

## Role Behavior

Admin:

- Melihat stepper di daftar akreditasi.
- Melihat stepper di detail akreditasi.
- Melihat `Review Asesor` sebelum `Visitasi`.
- Melihat `Penilaian Pasca Visitasi` setelah visitasi.

Asesor:

- Melihat stepper di detail tugas akreditasi.
- Melihat pembeda antara review dokumen, visitasi, dan penilaian pasca visitasi.
- Form penilaian tidak diposisikan sebagai tahap sebelum visitasi.

Pesantren:

- Melihat stepper di detail pengajuan akreditasi.
- Mendapat bahasa tahapan yang sama dengan admin dan asesor.
- Tidak melihat istilah teknis yang membingungkan seperti assessment sebagai tahap UI utama.

## Related Backend and Test Contracts

Label status dan kontrak test disesuaikan agar konsisten:

- `App\Models\Akreditasi::getStatusLabel()`
- `tests/Feature/MetronicFrontendTest.php`
- `tests/Unit/ConflictExceptionTest.php`
- `tests/Feature/ConcurrentAccess/ConflictMessagePropertyTest.php`

Filter daftar admin juga diselaraskan:

- `Pengajuan`
- `Verifikasi Berkas`
- `Review Asesor`
- `Visitasi & Penilaian Pasca Visitasi`
- `Validasi Admin`
- `Terlambat`
- `Semua`

## Verification

Commands yang sudah dijalankan:

```bash
php artisan test tests\\Feature\\MetronicFrontendTest.php --filter "admin_akreditasi_page|workflow_stepper" --no-ansi
php artisan test tests\\Feature\\MetronicFrontendTest.php --filter "detail_pages_render_metronic_detail_foundation|workflow_stepper" --no-ansi
php artisan test tests\\Unit\\ConflictExceptionTest.php --no-ansi
php artisan test tests\\Feature\\ConcurrentAccess\\ConflictMessagePropertyTest.php --no-ansi
php artisan view:cache --no-ansi
npm run build
```

Smoke check lokal untuk UUID `f2ffe5a4-238b-4ecc-b78f-68dce23dbc2d`:

- Admin detail: stepper muncul, current step `Review Asesor`.
- Asesor detail: stepper muncul, current step `Review Asesor`.
- Pesantren detail: stepper muncul, current step `Review Asesor`.

## Notes

- Perubahan ini menyelesaikan inkonsistensi tahapan pada UI akreditasi lintas role.
- Visual polish detail lanjutan tetap bisa dilakukan setelah fondasi workflow ini stabil.
- Artefak lokal seperti cache Laravel dan hasil visual smoke tidak termasuk scope dokumentasi ini.
