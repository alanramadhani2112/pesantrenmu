# Frontend UI/UX Positioning

Dokumen ini menjadi pegangan Phase 1 migrasi frontend SPM ke pola UI Metronic. Fokusnya adalah user centered design: komponen, layout, dan interaksi dipilih berdasarkan pekerjaan utama tiap role, bukan sekadar mengikuti tampilan template.

## Product Context

SPM adalah sistem kerja operasional untuk proses akreditasi pesantren. Pengguna utama tidak datang untuk eksplorasi visual, melainkan untuk menyelesaikan tugas administratif yang berurutan, terdokumentasi, dan sering berulang.

Implikasi desain:

- UI harus padat, mudah dipindai, dan minim distraksi.
- Status proses harus selalu jelas.
- Aksi utama harus mudah ditemukan.
- Form panjang harus dibagi ke section yang logis.
- Tabel harus mendukung pencarian, filter, sort, pagination, dan action menu yang konsisten.
- Feedback sistem harus eksplisit: berhasil, gagal validasi, terkunci, perlu revisi, atau menunggu pihak lain.

## User Roles

### Admin

Tujuan utama:

- Memantau semua pengajuan.
- Memvalidasi pengajuan dan dokumen.
- Menugaskan asesor.
- Menjadwalkan atau memantau visitasi.
- Menetapkan hasil akhir akreditasi.
- Mengelola master data, akun, asesor, dan pesantren.

Kebutuhan UI:

- Dashboard ringkas dengan angka proses aktif.
- Tabel akreditasi dengan status dan tahapan yang sangat jelas.
- Filter cepat berdasarkan status, periode, pesantren, asesor, dan tahapan.
- Action menu yang konsisten untuk validasi, assign, lihat detail, dan finalisasi.
- Detail page berbentuk workspace, bukan halaman laporan statis.

Prioritas desain:

- Scannability.
- Confidence before action.
- Bulk awareness, bukan hanya satu record.

### Pesantren

Tujuan utama:

- Melengkapi profil dan dokumen.
- Mengisi IPM, SDM, dan EDPM.
- Membuat pengajuan akreditasi.
- Memantau status.
- Menindaklanjuti catatan atau penolakan.
- Mengunggah kartu kendali jika diminta.

Kebutuhan UI:

- Progress checklist kelengkapan data.
- State jelas ketika profil terkunci.
- CTA yang muncul sesuai kesiapan data, bukan selalu dipaksa tampil sama.
- Bahasa instruksi yang sederhana dan langsung.
- Catatan revisi ditampilkan dekat dengan hal yang perlu diperbaiki.

Prioritas desain:

- Guided workflow.
- Error prevention.
- Reduce anxiety.

### Asesor

Tujuan utama:

- Melihat daftar tugas assessment dan visitasi.
- Mengisi penilaian EDPM.
- Memberi catatan.
- Mengunggah laporan visitasi.
- Menyelesaikan assessment sesuai jadwal.

Kebutuhan UI:

- Daftar tugas dengan deadline dan status.
- Detail assessment dengan navigasi butir yang efisien.
- Simpan draf dan finalisasi dibedakan jelas.
- Indikator kelengkapan penilaian.
- Upload laporan dan catatan mudah ditemukan.

Prioritas desain:

- Task focus.
- Low-friction data entry.
- Clear completion state.

## UI Principles

### 1. Status First

Status akreditasi harus menjadi elemen visual utama di tabel dan detail page. Label status perlu konsisten:

- Pengajuan
- Assessment
- Visitasi
- Validasi
- Berhasil
- Ditolak

Gunakan badge Metronic, tetapi warna mengikuti token brand SPM.

### 2. Role-Aware Navigation

Sidebar dan header harus disesuaikan dengan role. Hindari menu yang tidak bisa dipakai oleh role tersebut.

Navigasi harus menjawab:

- Apa yang harus saya lakukan sekarang?
- Di mana pekerjaan yang tertunda?
- Di mana riwayat atau dokumen saya?

### 3. Reusable Interaction Patterns

Setiap halaman harus memakai pola yang sama untuk:

- Page toolbar.
- Filter bar.
- Datatable.
- Action menu.
- Confirmation dialog.
- Modal form.
- Empty state.
- Validation feedback.

### 4. Progressive Disclosure

Detail yang panjang tidak tampil sekaligus. Gunakan tabs, sections, accordions, atau stepper ketika data terlalu besar.

Contoh:

- Profil pesantren dipisah dari dokumen.
- EDPM dipisah berdasarkan komponen.
- Detail akreditasi dipisah menjadi ringkasan, dokumen, assessment, visitasi, dan hasil.

### 5. Accessible Operational UI

Minimal requirement:

- Kontras teks aman.
- Target klik jelas.
- Icon diberi label atau tooltip.
- Button destructive selalu butuh confirmation.
- Error message dekat input terkait.
- Keyboard focus tetap terlihat.

## Component Direction

Komponen Blade menjadi design system. Livewire mengatur state dan behavior.

Komponen prioritas:

- `x-ui.page`
- `x-ui.toolbar`
- `x-ui.card`
- `x-ui.stat-card`
- `x-ui.badge`
- `x-ui.button`
- `x-ui.table`
- `x-ui.action-menu`
- `x-ui.modal`
- `x-ui.input`
- `x-ui.select`
- `x-ui.textarea`
- `x-ui.empty-state`
- `x-ui.workflow-step`

Komponen awal yang sudah tersedia:

- `x-ui.page`
- `x-ui.toolbar`
- `x-ui.card`
- `x-ui.stat-card`
- `x-ui.badge`
- `x-ui.button`
- `x-ui.empty-state`
- `x-ui.icon`
- `x-ui.metric-row`

## First Screens To Migrate

Urutan migrasi yang disarankan:

1. App shell: sidebar, header, content wrapper.
2. Dashboard.
3. Datatable reusable.
4. Admin akreditasi list.
5. Pesantren akreditasi list.
6. Detail akreditasi.

Dashboard dipilih sebagai proof of concept karena mencakup layout, cards, chart area, role-aware stats, dan page toolbar.

Login sudah dimigrasi sebagai proof of concept pertama karena langsung memvalidasi asset Metronic, brand override, form control, button, icon, dan flow auth dengan seed lokal.

Dashboard sudah dimigrasi sebagai proof of concept kedua. Fokusnya adalah role-aware page toolbar, stat card reusable, monitoring asesor, chart container, dan empty state saat belum ada data.

## Definition Of Done For A Migrated Page

Satu halaman dianggap selesai jika:

- Build frontend berhasil.
- Test backend tetap hijau jika behavior tersentuh.
- Layout desktop dan mobile tidak overlap.
- Aksi utama terlihat tanpa mencari terlalu lama.
- Status workflow jelas.
- Komponen yang dibuat reusable, bukan hanya copy-paste Metronic.
- Tidak ada script Metronic yang bentrok dengan Livewire navigation.
