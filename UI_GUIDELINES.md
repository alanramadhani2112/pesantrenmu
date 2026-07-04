# UI Guidelines (Metronic Hybrid)

Panduan ini jadi baseline agar tampilan tetap konsisten Metronic sambil tetap aman untuk kasus teknis khusus.

## Tujuan

- Menjaga konsistensi visual dan UX antarmodul.
- Mengurangi markup manual berulang.
- Memastikan pengembangan berikutnya tidak kembali ke campuran style acak.

## Aturan Utama

- Gunakan komponen `x-ui.*` sebagai default.
- Untuk form umum, wajib pakai:
  - `x-ui.form-field`
  - `x-ui.input`
  - `x-ui.select`
  - `x-ui.textarea`
  - `x-ui.button`
- Hindari elemen native manual (`<input>`, `<select>`, `<textarea>`, `<button>`) jika sudah ada padanan komponen.

## Hybrid Rules (Diizinkan Native)

Native element tetap diizinkan untuk kasus berikut:

- `input[type=file]` dengan flow upload khusus berbasis form standar atau bridge Alpine.
- Kontrol interaktif Alpine yang butuh binding spesifik dan belum cocok dimasukkan ke komponen reusable.

Syarat wajib untuk native yang dipertahankan:

- Harus tetap mengikuti kontrak Metronic visual.
- Untuk upload file, gunakan atribut:

```html
data-ui-file-upload="metronic"
```

### Pola Upload Resmi

- Untuk upload sederhana, pakai `x-ui.file-upload model="..."`.
- Untuk upload yang butuh handler custom, pakai `x-ui.file-upload` dengan `change-action="..."`.
- Untuk kasus preview/trigger khusus, isi slot komponen; jangan ulang markup `input[type=file]` manual.
- Jika perlu style khusus, gunakan `label-class` dan `label-style` pada komponen.

### Pola Modal Resmi

- Gunakan `x-ui.modal` sebagai shell modal utama.
- `x-modal` tetap didukung sebagai wrapper legacy yang diproxy ke `x-ui.modal`.
- Untuk isi modal, gunakan `x-ui.modal-header`, `x-ui.modal-body`, dan `x-ui.modal-footer`.

## Konvensi Implementasi

- Gunakan `:error="$errors->get('field')"` pada `x-ui.form-field` untuk menampilkan error.
- Untuk state lokal ringan, gunakan Alpine `x-model`; untuk simpan data, gunakan form Blade/controller biasa.
- Untuk tombol aksi kecil (mis. toggle password), pakai `x-ui.button` + class Metronic yang sesuai (`btn-icon`, dll).

## Checklist Review sebelum Merge

- Tidak ada native form control baru selain exception hybrid.
- Jika ada native exception, sudah diberi kontrak UI yang sesuai.
- Binding `x-model`, event Alpine, dan submit form standar tetap berjalan.
- Jalankan regression UI:

```bash
php artisan test tests/Feature/MetronicFrontendTest.php
```

## Baseline Saat Ini

- Form controls umum sudah dimigrasi ke komponen `x-ui.*`.
- Native controls tersisa hanya pada exception yang benar-benar butuh interaksi khusus dan belum punya komponen reusable.
