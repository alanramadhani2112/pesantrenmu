# Metronic Asset Strategy

Dokumen ini menjadi pegangan Phase 2 untuk penggunaan Metronic di project SPM.

## Source

Source theme lokal:

```text
C:\laragon\www\dist\dist
```

Versi yang terlihat dari template:

```text
Metronic 8.1.8
```

Pastikan penggunaan theme mengikuti lisensi Metronic/Keenthemes yang valid untuk project ini.

## Target

Asset disiapkan di:

```text
public/vendor/metronic/assets
```

Asset yang sudah disalin pada Phase 2:

```text
public/vendor/metronic/assets/css/style.bundle.css
public/vendor/metronic/assets/js/scripts.bundle.js
public/vendor/metronic/assets/plugins/global
public/vendor/metronic/assets/media/logos
```

Folder full theme lama di `public/assets` sudah dihapus dari project karena tidak direferensikan oleh view/app dan membuat repo membawa sekitar 78 MB asset tidak terpakai.

Jumlah awal:

```text
59 files
15 MB approximately
```

## Why Minimal Copy

Folder asset Metronic penuh berisi sekitar 2.272 file dan 78 MB. Untuk menghindari repo langsung berat dan sulit diaudit, Phase 2 hanya membawa asset dasar yang dibutuhkan untuk shell, komponen umum, icon font, dan base script.

Plugin custom seperti datatables, fullcalendar, formrepeater, tinymce, atau ckeditor tidak dimasukkan dulu. Plugin tambahan hanya boleh ditambahkan ketika halaman benar-benar membutuhkan fitur tersebut.

## Loading Policy

Asset Metronic dimuat pada layout utama dan guest dengan strategi standar Metronic 8.1.8 demo42.

Urutan load aktif:

```
Metronic plugins CSS
Metronic style bundle CSS
Vite app.css
Vite metronic-overrides.css
Metronic plugins JS
Metronic scripts bundle JS
Vite app.js
```

Urutan ini menjaga kontrak demo42 lokal tetap utuh, sementara brand override SPM tetap menang setelah bundle Metronic. Vite tetap memuat adapter aplikasi dan helper yang spesifik untuk SPM.

Catatan trim terbaru:

- `layouts.app` dan `layouts.guest` memuat `plugins.bundle.css`, `style.bundle.css`, `plugins.bundle.js`, dan `scripts.bundle.js` sesuai source lokal Metronic 8.1.8 demo42.
- `welcome.blade.php` dan error pages tetap ringan dan tidak memuat plugin JS global.
- Dropdown/action menu memakai Blade + Alpine dengan class Metronic, sehingga tidak bergantung pada `data-kt-menu` atau inisialisasi `KTMenu`.
- Header menu/dropdown Metronic memakai bundle Metronic; adapter di `resources/js/app.js` tetap defensif untuk route/test tanpa global KT runtime.
- Notifikasi tidak lagi melakukan polling legacy reactive layer setiap 15 detik; daftar notifikasi di-refresh saat tombol notifikasi dibuka.
- Jangan mengembalikan `public/assets`; gunakan `public/vendor/metronic/assets` sebagai satu-satunya lokasi asset Metronic runtime.

## Legacy Reactive Compatibility Rules

- Jangan start Alpine atau legacy reactive layer dua kali.
- Metronic init di `resources/js/app.js` bersifat defensif dan harus idempotent karena bundle Metronic juga tersedia global.
- Chart.js dipakai eksplisit lewat Vite untuk dashboard; Dropzone, autosize, SweetAlert, dan Popper tetap dipakai oleh adapter aplikasi. Jangan tambah plugin custom baru tanpa kebutuhan halaman nyata.
- Komponen yang bisa dibuat dengan Blade/legacy reactive layer native tidak perlu memaksa plugin Metronic JS.
- Modal, dropdown, tooltip, drawer, dan menu harus diuji setelah reactive update lama.

## Plugin Addition Rules

Tambahkan plugin Metronic custom hanya jika:

- Ada halaman yang benar-benar membutuhkan fitur tersebut.
- Tidak ada implementasi ringan yang sudah tersedia di project.
- Plugin bisa diload per halaman atau per layout tertentu.
- Sudah diuji tidak bentrok dengan legacy reactive layer.

Contoh kebutuhan:

- Datatables: hanya jika tabel client-side Metronic dipakai. Saat ini server-side pagination saat ini lebih sesuai.
- FullCalendar: hanya jika ada kalender visitasi.
- Formrepeater: hanya jika form unit/SDM butuh repeater JS yang kompleks.
- Tinymce/CKEditor: hanya jika Quill yang ada tidak cukup.

## Brand Color Guardrail

Brand color SPM tetap menjadi sumber utama. Metronic hanya sumber component style.

Token awal:

```text
primary: #1e3a5f
primary-hover: #162c49
primary-soft: #e8eef5
success: #10b981
warning: #f59e0b
danger: #ef4444
info: #0088ff
```

Override brand akan disiapkan pada Phase 3.

Phase 3 output:

```text
resources/css/metronic-overrides.css
```

File ini sudah didaftarkan sebagai Vite entry terpisah. Layout lama belum memuatnya secara eksplisit, sehingga override baru aktif ketika layout Metronic nanti memanggil entry tersebut.

## Do Not Do

- Jangan copy HTML Metronic mentah ke tiap halaman legacy reactive layer.
- Jangan load semua custom plugin sekaligus.
- Jangan mengganti layout lama secara total sebelum shell baru diuji.
- Jangan mengubah business logic ketika sedang migrasi UI.
- Jangan menulis CSS override spesifik halaman jika bisa dijadikan token atau component variant.

