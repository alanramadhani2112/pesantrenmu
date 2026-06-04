@php
$sections = [
    ['id' => 'dashboard', 'title' => 'Dashboard', 'icon' => 'ki-element-11'],
    ['id' => 'manajemen-pengguna', 'title' => 'Manajemen Pengguna', 'icon' => 'ki-user'],
    ['id' => 'manajemen-sistem', 'title' => 'Manajemen Sistem', 'icon' => 'ki-setting-3'],
    ['id' => 'operasional-akreditasi', 'title' => 'Operasional Akreditasi', 'children' => [
        ['id' => 'verifikasi-berkas', 'title' => 'Verifikasi Berkas'],
        ['id' => 'assign-asesor', 'title' => 'Assign Asesor'],
        ['id' => 'validasi-admin', 'title' => 'Validasi & Penerbitan SK'],
        ['id' => 'kelola-banding', 'title' => 'Kelola Banding'],
    ]],
    ['id' => 'arsip-trash', 'title' => 'Arsip & Trash', 'icon' => 'ki-trash'],
];
@endphp

<x-panduan::layout title="Panduan Admin" :sections="$sections" currentSection="dashboard">

{{-- Screenshot tampilan penuh dashboard admin --}}
<div class="card card-flush mb-8">
    <div class="card-header border-0 pt-6 pb-0 px-6">
        <h3 class="card-title fw-bold fs-3 text-gray-900">Tampilan Dashboard Admin</h3>
    </div>
    <div class="card-body pt-4 px-6 pb-6">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/admin-full.png') }}"
                 alt="Tampilan lengkap dashboard Admin SPM"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
    </div>
</div>

<x-panduan::section id="dashboard" title="Dashboard Admin" subtitle="Halaman utama admin menampilkan ringkasan status akreditasi, statistik pesantren, dan akses cepat ke menu utama.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Login ke Sistem</strong><br/>
                Buka <code>dev-pesantren.muhammadiyah.or.id/login</code>, masukkan email dan password admin Anda. Sistem akan mengarahkan ke Dashboard Admin.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Memahami Dashboard</strong><br/>
                Dashboard menampilkan kartu statistik: total akreditasi, status pengajuan baru, jumlah asesor, dan notifikasi sistem. Gunakan sidebar kiri untuk navigasi ke menu lainnya.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Navigasi Cepat</strong><br/>
                Klik menu di sidebar untuk mengakses fitur admin: Master Data, Akreditasi, Asesor, Banding, Pesantren, dan Arsip.
            </div>
        </li>
    </ol>

{{-- Screenshot Dashboard Admin --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/admin-dashboard.png') }}"
                 alt="Tampilan halaman Dashboard Admin"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Dashboard Admin pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="manajemen-pengguna" title="Manajemen Pengguna" subtitle="Admin dapat mengelola akun pengguna sistem melalui menu Akun Pengguna.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Melihat Daftar Akun</strong><br/>
                Klik <strong>Akun Pengguna</strong> di sidebar. Tabel menampilkan semua pengguna dengan kolom: Nama, Email, Role, dan Status.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Menambah Akun Baru</strong><br/>
                Klik tombol <strong>Tambah Akun</strong>, isi nama, email, pilih role (<em>Admin</em>, <em>Asesor</em>, <em>Pesantren</em>), dan klik Simpan.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Edit / Nonaktifkan Akun</strong><br/>
                Klik tombol aksi di baris pengguna untuk mengedit data atau menonaktifkan akun.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">4</span>
            <div>
                <strong>Filter &amp; Pencarian</strong><br/>
                Gunakan kolom pencarian di atas tabel untuk mencari pengguna berdasarkan nama atau email. Filter role membantu menyaring pengguna sesuai jenis akun.
            </div>
        </li>
    </ol>

{{-- Screenshot Manajemen Pengguna --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/admin-accounts.png') }}"
                 alt="Tampilan halaman Manajemen Pengguna (Akun Pengguna)"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Manajemen Pengguna (Akun Pengguna) pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="manajemen-sistem" title="Manajemen Sistem" subtitle="Konfigurasi master data dan hak akses sistem.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Komponen EDPM/IPR</strong><br/>
                Menu <strong>Komponen EDPM/IPR</strong> menampilkan daftar komponen penilaian yang digunakan dalam instrumen akreditasi. Admin dapat menambah, mengedit, atau menghapus komponen.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Kategori Dokumen</strong><br/>
                Menu <strong>Kategori Dokumen</strong> digunakan untuk mengelola kategori dokumen wajib yang harus diunggah pesantren saat pengajuan akreditasi.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Dokumen Wajib</strong><br/>
                Menu <strong>Dokumen Wajib</strong> untuk mengelola daftar dokumen yang wajib diunggah pesantren per kategori.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">4</span>
            <div>
                <strong>Peran &amp; Hak Akses</strong><br/>
                Menu <strong>Peran & Hak Akses</strong> untuk mengelola role dan permission di sistem. <strong>Hati-hati:</strong> perubahan di menu ini berdampak pada seluruh sistem.
            </div>
        </li>
    </ol>

{{-- Screenshot Manajemen Sistem --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/admin-master-kategori.png') }}"
                 alt="Tampilan halaman Manajemen Sistem (Master Kategori Dokumen)"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Manajemen Sistem (Master Kategori Dokumen) pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="verifikasi-berkas" title="Verifikasi Berkas" subtitle="Admin memverifikasi kelengkapan dokumen yang diunggah pesantren sebelum diteruskan ke asesor.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Menu Akreditasi</strong><br/>
                Klik <strong>Akreditasi</strong> di sidebar. Tabel menampilkan daftar pengajuan akreditasi dari pesantren.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Filter Status "Pengajuan"</strong><br/>
                Gunakan filter status di atas tabel, pilih <strong>Pengajuan (6)</strong>. Ini menampilkan pengajuan baru yang menunggu verifikasi berkas.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Review Dokumen</strong><br/>
                Klik baris pengajuan untuk membuka detail. Pada tab <strong>Dokumen</strong>, periksa setiap dokumen yang diunggah pesantren. Setiap jenis dokumen wajib ada.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">4</span>
            <div>
                <strong>Konfirmasi Berkas</strong><br/>
                Jika dokumen lengkap, klik <strong>Berkas Lengkap</strong>. Jika ada dokumen kurang, klik <strong>Revisi Berkas</strong> — pesantren akan diminta mengunggah ulang. Status berubah ke <strong>Review Asesor (4)</strong> jika lengkap.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">5</span>
            <div>
                <strong>Catatan Penting</strong><br/>
                Revisi berkas oleh admin <strong>bukan penolakan final</strong> — pesantren tetap bisa melanjutkan proses setelah melengkapi dokumen yang diminta. Penolakan final hanya terjadi di tahap Validasi Admin.
            </div>
        </li>
    </ol>

{{-- Screenshot Verifikasi Berkas --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/admin-akreditasi.png') }}"
                 alt="Tampilan halaman Verifikasi Berkas (Daftar Akreditasi)"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Verifikasi Berkas (Daftar Akreditasi) pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="assign-asesor" title="Assign Asesor" subtitle="Setelah berkas diverifikasi, admin menugaskan asesor untuk melakukan review.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Menu Asesor</strong><br/>
                Klik <strong>Asesor</strong> di sidebar. Tabel menampilkan daftar semua asesor terdaftar.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Assign Tugas</strong><br/>
                Pada halaman detail akreditasi, bagian <strong>Assign Asesor</strong>, pilih asesor yang tersedia. Anda dapat menugaskan satu atau lebih asesor per akreditasi.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Ketua Kelompok</strong><br/>
                Jika ada lebih dari satu asesor, tentukan <strong>Ketua Kelompok</strong> yang bertanggung jawab menjadwalkan visitasi. Ketua kelompok adalah asesor yang pertama di-assign.
            </div>
        </li>
    </ol>

{{-- Screenshot Assign Asesor --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-assign-asesor.png') }}"
                 alt="Tampilan halaman Assign Asesor (Daftar Asesor)"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Assign Asesor (Daftar Asesor) pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="validasi-admin" title="Validasi & Penerbitan SK" subtitle="Tahap akhir sebelum hasil akreditasi diterbitkan.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Filter Status "Validasi Admin"</strong><br/>
                Pada menu Akreditasi, filter status <strong>Validasi Admin (1)</strong> untuk melihat pengajuan yang siap divalidasi.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Input Nilai NV</strong><br/>
                Buka detail akreditasi, tab <strong>Nilai NV</strong>. Admin menginput nilai verifikasi dari form yang disediakan. Setiap komponen NV harus diisi sebelum finalisasi.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Terbitkan SK</strong><br/>
                Setelah semua nilai NV diinput, klik <strong>Finalisasi & Terbitkan SK</strong>. Sistem akan otomatis menghitung hasil akhir dan menghasilkan nomor SK.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">4</span>
            <div>
                <strong>Hasil Akhir</strong><br/>
                Status berubah menjadi <strong>Terakreditasi (0)</strong> dengan peringkat A/B/C, atau <strong>Ditolak Final (-1)</strong>. Pesantren akan menerima notifikasi hasil.
            </div>
        </li>
    </ol>

{{-- Screenshot Validasi & Penerbitan SK --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-validasi-sk.png') }}"
                 alt="Tampilan halaman Validasi & Penerbitan SK"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Validasi & Penerbitan SK pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="kelola-banding" title="Kelola Banding" subtitle="Admin menangani pengajuan banding dari pesantren yang ditolak final.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Menu Banding</strong><br/>
                Klik <strong>Banding</strong> di sidebar. Tabel menampilkan daftar pengajuan banding dari pesantren.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Review Berkas Banding</strong><br/>
                Klik detail banding. Periksa dokumen banding dan alasan yang diajukan pesantren.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Keputusan Banding</strong><br/>
                Admin dapat <strong>Menerima</strong> banding (status kembali ke proses review) atau <strong>Menolak</strong> banding (keputusan final). Pesantren akan menerima notifikasi.
            </div>
        </li>
    </ol>

{{-- Screenshot Kelola Banding --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/admin-banding.png') }}"
                 alt="Tampilan halaman Kelola Banding"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Kelola Banding pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="arsip-trash" title="Arsip & Trash" subtitle="Mengelola akreditasi yang diarsipkan atau dihapus.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Arsip Akreditasi</strong><br/>
                Klik <strong>Arsip Akreditasi</strong> di sidebar untuk melihat data akreditasi yang telah dihapus (soft delete).
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Restore / Hapus Permanen</strong><br/>
                Data di trash dapat di-<strong>restore</strong> (kembalikan) atau dihapus permanen. Hati-hati: hapus permanen tidak dapat dibatalkan.
            </div>
        </li>
    </ol>

{{-- Screenshot Arsip & Trash --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/admin-trash.png') }}"
                 alt="Tampilan halaman Arsip & Trash"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Arsip & Trash pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

</x-panduan::layout>
