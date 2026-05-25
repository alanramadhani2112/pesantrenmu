# Role Pesantren Production Readiness

Tanggal: 19 Mei 2026

Scope: Profil Pesantren, IPM, dan Data SDM untuk role `pesantren`.

Status: selesai untuk checkpoint ini. Fitur sudah punya validasi server-side, komponen UI Metronic reusable, dan targeted test coverage yang hijau.

## Tujuan

Checkpoint ini dibuat untuk memastikan alur utama role pesantren layak dipakai di production dari sisi perilaku, validasi, dan UI/UX dasar. Fokusnya bukan menambah fitur baru, tetapi mengunci form input penting supaya tidak menyimpan data kosong, tidak konsisten, atau sulit dipahami pengguna.

## Keputusan UX

1. Form pesantren harus memberi error yang terlihat jelas, bukan hanya gagal diam-diam.
2. Alert validasi memakai pola danger summary di atas form, lalu field yang bermasalah diberi state invalid.
3. Komponen input, checkbox, file upload, table, section card, button, dan badge harus memakai reusable `x-ui`/Metronic.
4. Draft dan final submit dipisah pada Profil Pesantren.
5. Emoji tidak dipakai sebagai icon atau status UI. Status lock/correction memakai teks dan icon Metronic.
6. Data SDM hanya boleh diisi jika Profil sudah punya layanan satuan pendidikan/unit.

## Perubahan Fitur

### Profil Pesantren

File utama: `resources/views/livewire/pages/pesantren/profile.blade.php`

Yang dikunci:

- Edit mode menampilkan aksi `Batal`, `Submit Draft`, dan `Submit`.
- `Submit Draft` boleh menyimpan data belum lengkap.
- `Submit` final wajib mengisi data inti profil.
- `layanan_satuan_pendidikan` wajib untuk final submit dan untuk readiness progress.
- Error final submit menampilkan alert danger `Data profil belum lengkap`.
- Field invalid memakai class Metronic `is-invalid`.
- Checkbox layanan memakai reusable `x-ui.checkbox`.
- Wilayah selector memakai entangle Livewire yang benar untuk kode dan nama provinsi/kabupaten.

### IPM

File utama: `resources/views/livewire/pages/pesantren/ipm.blade.php`

Yang dikunci:

- Empat dokumen IPM wajib lengkap sebelum final save jika data belum terkunci:
  - NSP
  - Lulus Santri
  - Kurikulum
  - Buku Ajar
- Semua upload divalidasi sebagai PDF maksimal 2 MB.
- Jika validasi gagal, muncul alert danger `Data IPM belum lengkap`.
- Jika tidak ada file baru yang dipilih, pengguna mendapat alert info `Tidak Ada Perubahan`.
- Jika data terkunci, save ditolak kecuali section sedang dibuka untuk koreksi.
- File lama dihapus dari disk ketika diganti dengan file baru.
- File upload memakai reusable `x-ui.file-upload`.
- Teks lock/correction tidak lagi memakai emoji.

### Data SDM

File utama: `resources/views/livewire/pages/pesantren/sdm.blade.php`

Yang dikunci:

- Data SDM hanya bisa disimpan jika Profil punya unit pendidikan.
- Jika belum ada unit, save berhenti dan memunculkan alert Metronic `Profil Belum Lengkap`.
- Semua field angka wajib:
  - `required`
  - `integer`
  - `min:0`
  - `max:999999`
- Jika validasi gagal, muncul alert danger `Data SDM belum valid`.
- Nilai disanitasi ke integer sebelum disimpan.
- Data disimpan per `pesantren_unit_id`, bukan row kosong tanpa unit.
- Jika unit id tidak ditemukan, save ditolak dengan alert `Unit Tidak Valid`.
- Table memakai reusable `x-ui.simple-table`.
- Input angka memakai reusable `x-ui.input`.
- Empty state mengarahkan pengguna kembali ke Profil.
- Teks lock/correction tidak lagi memakai emoji.

## Test Coverage

Test baru/terkait:

- `tests/Feature/Livewire/PesantrenProfileFlowTest.php`
- `tests/Feature/Livewire/PesantrenIpmFlowTest.php`
- `tests/Feature/Livewire/PesantrenSdmFlowTest.php`
- `tests/Feature/SidebarProgressServiceTest.php`
- `tests/Feature/MetronicFrontendTest.php`

Coverage perilaku:

| Area | Coverage |
| --- | --- |
| Profil | Draft vs final submit, required fields, alert danger, checkbox reusable, wilayah entangle, save complete profile |
| IPM | Required 4 dokumen, PDF upload success, reusable file upload, no emoji lock |
| SDM | Integer non-negative validation, save per unit, no empty row without profile units, reusable table/input, no emoji lock |
| Sidebar progress | Profil/IPM/SDM completeness status |
| Metronic frontend | Component contract, table/list/detail/input foundation |

## Verifikasi Terakhir

Perintah yang sudah dijalankan:

```bash
php artisan test tests\Feature\Livewire\PesantrenProfileFlowTest.php tests\Feature\Livewire\PesantrenIpmFlowTest.php tests\Feature\Livewire\PesantrenSdmFlowTest.php tests\Feature\SidebarProgressServiceTest.php tests\Feature\MetronicFrontendTest.php --no-ansi
php artisan view:cache --no-ansi
npm run build
```

Hasil:

```text
49 tests passed
706 assertions passed
Blade templates cached successfully
Vite build completed successfully
```

Build asset:

```text
app CSS: 42.88 kB
metronic overrides CSS: 44.84 kB
app JS: 304.45 kB
app JS gzip: 99.87 kB
```

## Browser QA

Domain lokal yang dicek:

- `http://spm_fix.test/pesantren/profile`
- `http://spm_fix.test/pesantren/ipm`
- `http://spm_fix.test/pesantren/sdm`

Akun:

- Email: `pesantren@spm.test`
- Password: `password`

Hasil browser QA:

| Halaman | Hasil |
| --- | --- |
| Profil | Heading tampil, section card tampil, edit mode punya input dan checkbox reusable, tidak ada overflow, tidak ada console error |
| IPM | `data-module-page="pesantren-ipm"` tampil, 4 file upload reusable, tidak ada overflow, tidak ada console error |
| SDM | `data-module-page="pesantren-sdm"` tampil, 6 table reusable, 36 input reusable, tidak ada overflow, tidak ada console error |

Catatan: browser automation sempat timeout saat mencoba klik validasi Profil, tetapi halaman tetap normal setelah dicek ulang. Perilaku validasi Profil sudah dikunci oleh PHPUnit.

## File Referensi

Implementation:

- `resources/views/livewire/pages/pesantren/profile.blade.php`
- `resources/views/livewire/pages/pesantren/ipm.blade.php`
- `resources/views/livewire/pages/pesantren/sdm.blade.php`
- `app/Services/PesantrenService.php`
- `app/Services/SidebarProgressService.php`
- `resources/views/components/ui/checkbox.blade.php`
- `resources/js/app.js`
- `resources/css/metronic-overrides.css`

Test:

- `tests/Feature/Livewire/PesantrenProfileFlowTest.php`
- `tests/Feature/Livewire/PesantrenIpmFlowTest.php`
- `tests/Feature/Livewire/PesantrenSdmFlowTest.php`
- `tests/Feature/SidebarProgressServiceTest.php`
- `tests/Feature/MetronicFrontendTest.php`

## Acceptance Criteria

Checkpoint ini dianggap selesai jika:

- Profil final submit tidak bisa menyimpan data inti kosong.
- Profil draft tetap bisa menyimpan data belum lengkap.
- IPM tidak bisa final save tanpa 4 dokumen wajib.
- SDM tidak bisa menyimpan angka negatif, non-integer, atau row tanpa unit.
- Semua alert utama muncul dengan SweetAlert/Metronic helper atau alert danger inline yang konsisten.
- Input, checkbox, upload, dan table memakai reusable component.
- Tidak ada emoji lock di UI.
- Targeted PHPUnit pass.
- `view:cache` pass.
- `npm run build` pass.

Semua acceptance criteria di atas sudah terpenuhi pada checkpoint 19 Mei 2026.

## Batasan Scope

Catatan penting: status "production-ready" di dokumen ini berlaku untuk scope fitur Profil Pesantren, IPM, dan Data SDM pada role pesantren. Ini bukan klaim seluruh aplikasi sudah siap deploy production.

Hal yang tetap perlu dicek sebelum deployment penuh:

- Environment production (`APP_ENV`, `APP_DEBUG`, session, cache, queue, mail).
- Full regression test seluruh suite.
- QA visual seluruh role dan seluruh halaman panjang/detail.
- Backup database dan storage upload.
- Queue worker dan scheduler.
- Monitoring error production.

## Lanjutan Yang Direkomendasikan

1. Lanjutkan pola yang sama ke EDPM role pesantren.
2. Lakukan browser QA manual untuk flow submit akreditasi setelah Profil, IPM, dan SDM lengkap.
3. Tambahkan test untuk kondisi locked/correction pada SDM jika rejection flow akan sering dipakai.
4. Jalankan full `php artisan test --no-ansi` sebelum milestone release.
