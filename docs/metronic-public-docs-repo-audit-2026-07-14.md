# Metronic Public Docs and Repo Audit - 2026-07-14

## Ringkasan

Audit ini melanjutkan task Metronic yang sempat belum selesai. Scope yang dipakai:

- Dokumentasi publik Metronic HTML: https://preview.keenthemes.com/html/metronic/docs/
- Dokumentasi publik Metronic Laravel: https://preview.keenthemes.com/laravel/metronic/docs/
- Implementasi lokal repo SPM: `C:\laragon\www\spm_fix`
- Source theme lokal: `C:\laragon\www\dist\dist`

Kesimpulan utama: repo memakai Metronic `8.1.8 demo42` secara meyakinkan. Dokumentasi publik sekarang berada di HTML `8.3.2` dan Laravel `8.3.1`, jadi docs publik valid sebagai referensi kompatibilitas dan pola komponen, tetapi bukan otoritas exact-version untuk runtime lokal.

Status terbaru: strategi asset sudah diputuskan ke standard Metronic bundle. Layout app/guest, docs internal, performance test, dan Metronic frontend test sudah sinkron ke load order Metronic 8.1.8 demo42. Risiko tersisa bergeser ke komponen KT khusus yang belum lengkap, dependency ownership yang masih dobel di beberapa adapter Vite, dan debt CSS override.

## Bukti Docs Publik

Recrawl dilakukan pada 2026-07-14 dari menu dokumentasi publik berbasis `?page=...`.

| Area | URL | Versi publik | Route live | Stale/weak current recrawl |
| --- | --- | ---: | ---: | ---: |
| HTML docs | `https://preview.keenthemes.com/html/metronic/docs/` | `8.3.2` | 183 | 0 |
| Laravel docs | `https://preview.keenthemes.com/laravel/metronic/docs/` | `8.3.1` | 12 | 0 |

Inventaris HTML docs current recrawl:

| Kategori | Route |
| --- | ---: |
| Base | 37 |
| Charts | 17 |
| Editors | 13 |
| Forms | 24 |
| General | 67 |
| Getting Started | 17 |
| Icons | 5 |
| Index/changelog | 3 |

Inventaris Laravel docs current recrawl:

| Kategori | Route |
| --- | ---: |
| Assets | 1 |
| Changelog | 1 |
| File structure | 1 |
| Getting started | 2 |
| Index | 1 |
| RTL | 1 |
| Settings | 1 |
| Theme API | 1 |
| Updates | 1 |
| Views | 1 |

Catatan penting: hasil task sebelumnya menyebut 197 HTML route, 181 halaman substansial, 5 weak/alias shell, dan 11 stale route pendek. Recrawl terbaru dengan normalisasi query resmi tidak menemukan stale/weak. Untuk keputusan repo, pakai hasil recrawl terbaru; untuk audit historis, simpan selisih ini sebagai indikasi bahwa crawler lama kemungkinan menghitung alias atau URL shell yang tidak lagi keluar dari menu resmi.

## Provenance Lokal

Bukti lokal mengikat repo ke Metronic `8.1.8 demo42`:

- `C:\laragon\www\dist\dist\index.html` dan `landing.html` memuat `Product Version: 8.1.8`.
- Source lokal memakai path dan logo `demo42`.
- Source lokal berisi 2.464 file dengan total 146.048.192 byte.
- Runtime repo di `public/vendor/metronic` berisi 122 file dengan total 30.843.945 byte.
- Empat bundle utama repo memiliki SHA-256 sama persis dengan source lokal:

| Bundle | Repo/source hash |
| --- | --- |
| `assets/css/style.bundle.css` | `6A6BD77ABC321864DB97DDC99DDDBECD98E3695E8626A0494F0530F482882334` |
| `assets/js/scripts.bundle.js` | `645AF847C081E93A59883D03D7C2F80FA4D1E4F8D3E17F7FFE38DD5BBF2DA016` |
| `assets/plugins/global/plugins.bundle.css` | `2BCC6201C1FC40B83C173B779CA55FAB5BC5743A7DA29C78915198BE5DDECBB6` |
| `assets/plugins/global/plugins.bundle.js` | `C303AE6C1998F8114CDC8C2CBEA423BD0416479265075F2ABF247913DF5D42D3` |

Ukuran bundle utama:

| File | Byte |
| --- | ---: |
| `plugins.bundle.js` | 3.812.926 |
| `plugins.bundle.css` | 840.898 |
| `scripts.bundle.js` | 242.042 |
| `style.bundle.css` | 1.464.869 |

## Coverage Repo

Hal yang sudah kuat dan cocok dengan pola Metronic:

- Shell app memakai ID dan struktur Metronic: `kt_app_body`, `kt_app_root`, `kt_app_page`, `kt_app_wrapper`, `kt_app_main`, `kt_app_content`.
- Sidebar memakai kontrak drawer/menu/scroll Metronic, termasuk `data-kt-drawer`, `data-kt-drawer-toggle`, dan `hover-scroll-overlay-y`.
- Komponen Blade reusable cukup matang: 81 Blade component total, 49 di namespace `resources/views/components/ui`.
- Pola UI table, tabs, badge, card, modal, form field, input/select/textarea, file upload, breadcrumb, pagination, metric, empty state sudah dibungkus `x-ui.*`.
- Override CSS sudah modular: 18 file di `resources/css/metronic-overrides`.
- Brand SPM sudah punya token sendiri dan override warna utama.
- Tabel operasional cenderung server-side, lebih cocok untuk data Laravel daripada memaksa DataTables client-side.
- SweetAlert sudah diabstraksi lewat `window.SpmSwal`, bukan inline `Swal.fire` per view.
- Alpine dipakai sebagai interaction layer aplikasi, bukan menyalin HTML Metronic mentah per halaman.

Coverage terhadap docs publik:

| Area docs | Status repo | Catatan |
| --- | --- | --- |
| Getting Started / asset order | Good | Layout app/guest, docs, and tests follow Metronic 8.1.8 demo42 bundle order. |
| Layout / app shell | Good | Struktur Metronic demo42 cukup jelas di layout dan sidebar. |
| Drawer/menu/scroll | Good | Sidebar is KTDrawer-owned; app fallback avoids duplicate KT init when `KTUtil` exists. |
| Forms | Good | Komponen form reusable, autosize aktif lewat `data-kt-autosize`. |
| Alerts/SweetAlert | Good | Ada helper `SpmSwal`; dependency ownership perlu dipilih. |
| Tables | Good | Adapter Blade + server pagination lebih tepat untuk repo ini. |
| Charts | Partial | Dashboard memakai `window.Chart`; sekarang bergantung implisit ke global plugin bundle. |
| Image input | Good | Profile and asesor profile image inputs expose root `data-kt-image-input="true"` plus change/cancel actions. |
| Stepper | Gap | Ada child `data-kt-stepper-element`, tetapi root/init stepper belum konsisten. |
| Editors/Quill | Weak | Bridge Quill ada di `app.js`, tetapi belum jelas dipakai. |
| Custom plugins | Weak | Banyak plugin vendor ada, tetapi tidak direferensikan runtime. |
| Theme mode | Gap | Layout memaksa `data-bs-theme="light"` di body; docs Metronic menulis mode di `html`. |

## Gap Utama

Resolved - asset strategy sudah sinkron.

- `resources/views/layouts/app.blade.php` dan `guest.blade.php` load `plugins.bundle.css`, `style.bundle.css`, `plugins.bundle.js`, dan `scripts.bundle.js`.
- `docs/metronic-asset-strategy.md`, `docs/performance-optimization.md`, `tests/Feature/PerformanceOptimizationTest.php`, dan `tests/Feature/MetronicFrontendTest.php` sudah mengikuti policy standard Metronic bundle.
- `welcome.blade.php` dan error pages tetap ringan tanpa plugin JS global.

P0 - duplicate dependency ownership.

- `plugins.bundle.js` membawa Bootstrap, jQuery, Popper, SweetAlert2, Dropzone, autosize, Chart.js, Quill, Select2, Flatpickr, dan plugin lain.
- Vite juga membawa `@popperjs/core`, `dropzone`, `autosize`, dan `sweetalert2`.
- Akibatnya ada dua sumber kebenaran untuk beberapa dependency runtime.

Resolved - init Metronic tidak dobel saat bundle tersedia.

- `scripts.bundle.js` menjadi owner init KT saat `window.KTUtil` tersedia.
- `resources/js/app.js` tetap punya fallback defensif untuk route/test tanpa KT runtime, tetapi guard `if (window.KTUtil) return;` mencegah re-init global.

P1 - kontrak komponen khusus belum lengkap.

- Image input profile sudah memakai root `data-kt-image-input="true"` dan action `change`/`cancel`.
- Stepper audit trail punya `data-kt-stepper-element="nav"`, tetapi root `data-kt-stepper`/constructor belum jelas.
- Quill bridge ada, tetapi `window.Quill` hanya tersedia bila full plugin bundle tetap global.

P1 - theme mode belum mengikuti kontrak docs.

- Docs publik memakai `document.documentElement.setAttribute("data-bs-theme", themeMode)`.
- Repo banyak memasang `data-bs-theme="light"` di body.
- Keputusan perlu eksplisit: light-only dan hapus sisa dark token, atau implement real theme mode di `html`.

P1 - vendor custom plugin belum punya ownership.

- `public/vendor/metronic/assets/plugins/custom` berisi 63 file, 15.651.144 byte.
- Folder yang ada: `ckeditor`, `cookiealert`, `cropper`, `datatables`, `draggable`, `flotcharts`, `formrepeater`, `fslightbox`, `fullcalendar`, `jkanban`, `jstree`, `leaflet`, `prismjs`, `tinymce`, `typedjs`, `vis-timeline`.
- Search repo tidak menemukan referensi runtime ke plugin custom tersebut.

P2 - override CSS besar.

- Override layer berisi 18 modul, 8.942 line, 238.890 character.
- Ada 903 `!important`.
- Ada 5 selector `:has()`.
- Ini belum otomatis salah, tetapi perlu konsolidasi bertahap agar Metronic upgrade tidak mahal.

## Roadmap

### Resolved P0 - strategi asset dan test kontrak

- Keputusan final: standard Metronic bundle untuk `layouts.app` dan `layouts.guest`.
- Load order final: `plugins.bundle.css`, `style.bundle.css`, Vite CSS, `plugins.bundle.js`, `scripts.bundle.js`, lalu Vite `app.js`.
- Public landing/error pages tetap ringan dan tidak memuat plugin JS global.
- `PerformanceOptimizationTest`, `MetronicFrontendTest`, `docs/metronic-asset-strategy.md`, dan `docs/performance-optimization.md` sudah mengikuti keputusan ini.
- Browser smoke contract sudah ditambahkan di `MetronicFrontendTest` untuk memastikan order asset, KT drawer/menu wiring, dan guard duplicate init.
### P1 - lengkapi komponen KT khusus

- Init KT sudah owned by `scripts.bundle.js`; app fallback hanya jalan ketika `window.KTUtil` tidak tersedia.
- Image input root contract sudah lengkap di profile dan asesor profile.
- Lengkapi stepper root/init atau tandai stepper sebagai visual-only component.
- Putuskan ownership Chart.js: global plugin bundle atau import eksplisit.
- Hapus bridge Quill bila tidak ada halaman pemakai; bila dipakai, load editor hanya di halaman terkait.

### P1 - bersihkan plugin custom

- Karantina atau hapus plugin custom yang tidak dipakai dari public runtime.
- Simpan daftar allowed plugins di docs.
- Tambah test ringan yang memastikan plugin custom berat tidak muncul tanpa referensi.

### P2 - kurangi design debt override

- Prioritaskan modul dengan `!important` paling banyak.
- Ganti override global dengan token atau component variant.
- Tambah manifest versi/hash Metronic agar provenance bisa dicek ulang otomatis.
- Evaluasi upgrade `8.1.8 -> 8.3.x` sebagai proyek terpisah, bukan side quest saat polish.

## Quick Wins

- Pertahankan dokumen asset strategy dan audit ini tetap sinkron setiap kali load order berubah.
- Tambah `docs/metronic-runtime-manifest.json` berisi versi, source path, file hash, dan bundle size.
- Hapus/karantina `plugins/custom` bila tidak dipakai, potensi pengurangan sekitar 15 MB.
- Tambah real browser smoke dengan Playwright/Puppeteer saat dependency tersedia; saat ini kontrak shell runtime dijaga lewat Feature test.
- Tambah komentar singkat di layout yang menjelaskan alasan load order final.

## Hal Yang Dipertahankan

- Jangan copy ulang HTML Metronic mentah per halaman.
- Pertahankan `x-ui.*` sebagai adapter aplikasi.
- Pertahankan server-side pagination dan table adapter.
- Pertahankan token brand SPM sebagai sumber warna utama.
- Pertahankan SweetAlert helper agar view tidak kembali ke inline script acak.
- Pertahankan CSS override modular, tetapi kurangi beban selector secara bertahap.

## Verifikasi

Yang sudah diverifikasi selama audit:

- `git status --short`: ada 11 modified file user-owned sebelum laporan ini dibuat.
- Public docs recrawl: HTML `8.3.2`, Laravel `8.3.1`.
- Source lokal: `C:\laragon\www\dist\dist`, `Product Version: 8.1.8`, demo42.
- Hash 4 bundle runtime repo sama dengan source lokal.
- Asset runtime repo: 122 file, 30.843.945 byte.
- Custom plugin folder: 63 file, 15.651.144 byte.
- Komponen Blade: 81 total, 49 `x-ui.*`.
- Override CSS: 18 modul, 8.942 line, 903 `!important`, 5 `:has()`.
- `git diff --check`: lulus setelah laporan dibuat.
- `npm run build`: lulus pada task audit yang terinterupsi.
- Asesor/sidebar test subset: lulus 36 test, 335 assertion pada task audit yang terinterupsi.
- `php artisan test tests\Feature\PerformanceOptimizationTest.php --no-ansi`: 1 gagal, 1 skipped, 2 lulus, 15 assertion. Gagal karena layout sekarang load plugin bundle global sementara test melarangnya.

## Update Eksekusi P0

Diputuskan dan diterapkan: Opsi A, standard Metronic bundle untuk layout app dan guest.

Perubahan P0:

- `layouts.app` dan `layouts.guest` mengikuti load order source lokal Metronic 8.1.8 demo42: `plugins.bundle.css`, `style.bundle.css`, `plugins.bundle.js`, `scripts.bundle.js`, lalu Vite app.
- `welcome.blade.php` dan error pages tetap ringan; plugin JS global tidak dimuat di halaman publik ringan.
- `docs/metronic-asset-strategy.md`, `docs/performance-optimization.md`, `PerformanceOptimizationTest`, dan `MetronicFrontendTest` disinkronkan ke policy runtime final.
- Sidebar mobile dipindahkan ke satu owner: KTDrawer. Alpine `$store.sidebar` dan custom backdrop dihapus.
- `x-ui.modal` mendapat title/header binding dan `aria-labelledby` ketika title tersedia.
- Asesor/detail dan pesantren/detail dipoles agar memenuhi kontrak Metronic reusable component.

Verifikasi setelah eksekusi:

- `npm run build`: lulus.
- `php artisan test tests/Feature/PerformanceOptimizationTest.php`: 3 lulus, 1 skipped.
- `php artisan test tests/Feature/MetronicFrontendTest.php`: 34 lulus, 1 skipped.

Catatan residual:

- Full Metronic bundle membawa dependency yang juga masih ada di Vite (`Dropzone`, `autosize`, `SweetAlert`, `Popper`, Chart). Ini diterima untuk stabilitas demo42 sekarang; pengurangan duplikasi dependency bisa jadi task performa terpisah setelah smoke browser.
- Plugin custom Metronic di `public/vendor/metronic/assets/plugins/custom` masih perlu audit pemakaian terpisah.