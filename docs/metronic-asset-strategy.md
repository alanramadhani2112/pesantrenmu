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

Jumlah awal:

```text
59 files
15 MB approximately
```

## Why Minimal Copy

Folder asset Metronic penuh berisi sekitar 2.272 file dan 78 MB. Untuk menghindari repo langsung berat dan sulit diaudit, Phase 2 hanya membawa asset dasar yang dibutuhkan untuk shell, komponen umum, icon font, dan base script.

Plugin custom seperti datatables, fullcalendar, formrepeater, tinymce, atau ckeditor tidak dimasukkan dulu. Plugin tambahan hanya boleh ditambahkan ketika halaman benar-benar membutuhkan fitur tersebut.

## Loading Policy

Asset Metronic sudah dimuat pada layout utama dan guest pada Phase 4.

Urutan load aktif:

```
Metronic plugins CSS
Metronic style bundle CSS
Vite app.css
Vite metronic-overrides.css
Vite app.js
Metronic plugins JS
Metronic scripts JS
```

Urutan ini menjaga komponen Metronic tersedia, sementara brand override SPM tetap menang setelah bundle Metronic.

## Livewire Compatibility Rules

- Jangan start Alpine atau Livewire dua kali.
- Metronic init dipanggil ulang dari `resources/js/app.js` setelah `DOMContentLoaded`, `livewire:initialized`, dan `livewire:navigated`.
- Komponen yang bisa dibuat dengan Blade/Livewire native tidak perlu memaksa plugin Metronic JS.
- Modal, dropdown, tooltip, drawer, dan menu harus diuji setelah Livewire update.

## Plugin Addition Rules

Tambahkan plugin Metronic custom hanya jika:

- Ada halaman yang benar-benar membutuhkan fitur tersebut.
- Tidak ada implementasi ringan yang sudah tersedia di project.
- Plugin bisa diload per halaman atau per layout tertentu.
- Sudah diuji tidak bentrok dengan Livewire.

Contoh kebutuhan:

- Datatables: hanya jika tabel client-side Metronic dipakai. Saat ini Livewire pagination lebih sesuai.
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

- Jangan copy HTML Metronic mentah ke tiap halaman Livewire.
- Jangan load semua custom plugin sekaligus.
- Jangan mengganti layout lama secara total sebelum shell baru diuji.
- Jangan mengubah business logic ketika sedang migrasi UI.
- Jangan menulis CSS override spesifik halaman jika bisa dijadikan token atau component variant.
