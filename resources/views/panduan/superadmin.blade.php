@php
$sections = [
    ['id' => 'dashboard', 'title' => 'Dashboard', 'icon' => 'ki-element-11'],
    ['id' => 'kelola-pengguna', 'title' => 'Kelola Pengguna', 'children' => [
        ['id' => 'role-permission', 'title' => 'Role & Permission'],
        ['id' => 'akun-pengguna', 'title' => 'Akun Pengguna'],
    ]],
    ['id' => 'master-data', 'title' => 'Master Data', 'children' => [
        ['id' => 'komponen-edpm', 'title' => 'Komponen EDPM/IPR'],
        ['id' => 'kategori-dokumen', 'title' => 'Kategori Dokumen'],
        ['id' => 'dokumen-wajib', 'title' => 'Dokumen Wajib'],
    ]],
    ['id' => 'operasional-akreditasi', 'title' => 'Operasional Akreditasi', 'children' => [
        ['id' => 'verifikasi-berkas', 'title' => 'Verifikasi Berkas'],
        ['id' => 'assign-asesor', 'title' => 'Assign Asesor'],
        ['id' => 'validasi-sk', 'title' => 'Validasi & Penerbitan SK'],
        ['id' => 'kelola-banding', 'title' => 'Kelola Banding'],
    ]],
    ['id' => 'notifikasi-arsip', 'title' => 'Notifikasi & Arsip', 'children' => [
        ['id' => 'notifikasi-gagal', 'title' => 'Notifikasi Gagal'],
        ['id' => 'arsip-trash', 'title' => 'Arsip Akreditasi'],
    ]],
];
@endphp

<x-panduan::layout title="Panduan Super Admin" :sections="$sections" currentSection="dashboard">

{{-- Screenshot tampilan penuh dashboard super admin --}}
<div class="card card-flush mb-8">
    <div class="card-header border-0 pt-6 pb-0 px-6">
        <h3 class="card-title fw-bold fs-3 text-gray-900">Tampilan Dashboard Super Admin</h3>
    </div>
    <div class="card-body pt-4 px-6 pb-6">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/superadmin-full.png') }}"
                 alt="Tampilan lengkap dashboard Super Admin"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
    </div>
</div>

<x-panduan::section id="dashboard" title="Dashboard Super Admin" subtitle="Halaman utama dengan akses penuh ke semua fitur sistem.">
    <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
        <i class="ki-duotone ki-information-4 fs-2 me-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
        <div><strong>Super Admin</strong> memiliki akses ke <strong>semua menu</strong> di sistem, termasuk manajemen pengguna, master data, operasional akreditasi, dan konfigurasi sistem. Gunakan hak akses ini dengan hati-hati.</div>
    </div>
    <p>Dashboard Super Admin menampilkan ringkasan seluruh sistem: total pengguna, akreditasi aktif, notifikasi sistem, dan akses cepat ke menu administrasi.</p>

{{-- Screenshot Dashboard Super Admin --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-dashboard.png') }}"
                 alt="Tampilan halaman Dashboard Super Admin"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Dashboard Super Admin pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="role-permission" title="Role & Permission" subtitle="Mengelola role dan hak akses pengguna sistem.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Role Sistem</strong><br/>
                Klik <strong>Role Sistem</strong> di sidebar. Halaman menampilkan daftar role yang tersedia: Super Admin, Admin, Asesor, Pesantren.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Konfigurasi Permission</strong><br/>
                Buka <strong>Peran & Hak Akses</strong> untuk mengatur permission tiap role. Setiap permission mengontrol akses ke fitur spesifik. <strong>Peringatan:</strong> perubahan di sini langsung berdampak ke seluruh pengguna.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Best Practice</strong><br/>
                Jangan mengubah permission default kecuali diperlukan. Selalu uji di lingkungan development terlebih dahulu sebelum mengubah permission di production.
            </div>
        </li>
    </ol>

{{-- Screenshot Role & Permission --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-role-permission.png') }}"
                 alt="Tampilan halaman Role & Permission"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Role & Permission pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="akun-pengguna" title="Akun Pengguna" subtitle="Mengelola seluruh akun pengguna di sistem.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Akun Pengguna</strong><br/>
                Klik <strong>Akun Pengguna</strong> di sidebar untuk melihat daftar semua pengguna.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>CRUD Akun</strong><br/>
                Super Admin dapat <strong>membuat</strong>, <strong>mengedit</strong>, <strong>menonaktifkan</strong>, dan <strong>mereset password</strong> akun pengguna. Pilih role yang tepat saat membuat akun baru.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Filter & Search</strong><br/>
                Gunakan filter role dan pencarian nama/email untuk menemukan pengguna dengan cepat.
            </div>
        </li>
    </ol>

{{-- Screenshot Akun Pengguna --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-akun-pengguna.png') }}"
                 alt="Tampilan halaman Akun Pengguna"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Akun Pengguna pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="komponen-edpm" title="Komponen EDPM/IPR" subtitle="Mengelola komponen instrumen penilaian akreditasi.">
    <p>Menu <strong>Komponen EDPM/IPR</strong> memungkinkan Super Admin mengelola struktur komponen penilaian yang digunakan asesor dan pesantren dalam proses akreditasi.</p>
    <ul class="mb-0">
        <li class="mb-2"><strong>Tambah Komponen:</strong> Klik tombol tambah, isi nama komponen dan deskripsi.</li>
        <li class="mb-2"><strong>Edit Komponen:</strong> Klik baris komponen untuk mengubah nama atau deskripsi.</li>
        <li><strong>Hapus Komponen:</strong> Hati-hati — menghapus komponen yang sudah digunakan dalam penilaian dapat menyebabkan inkonsistensi data.</li>
    </ul>

{{-- Screenshot Komponen EDPM/IPR --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-komponen-edpm.png') }}"
                 alt="Tampilan halaman Komponen EDPM/IPR"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Komponen EDPM/IPR pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="kategori-dokumen" title="Kategori Dokumen" subtitle="Mengelola kategori dokumen wajib untuk pengajuan akreditasi.">
    <p>Menu <strong>Kategori Dokumen</strong> digunakan untuk mengelompokkan dokumen wajib. Contoh kategori: Administrasi, Kurikulum, Sarana Prasarana, Keuangan.</p>
    <ul class="mb-0">
        <li class="mb-2"><strong>Tambah Kategori:</strong> Klik tombol tambah, isi nama kategori.</li>
        <li class="mb-2"><strong>Edit/Hapus:</strong> Klik aksi pada baris kategori.</li>
        <li><strong>Hubungan dengan Dokumen Wajib:</strong> Setiap dokumen wajib terhubung ke satu kategori.</li>
    </ul>

{{-- Screenshot Kategori Dokumen --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-kategori-dokumen.png') }}"
                 alt="Tampilan halaman Kategori Dokumen"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Kategori Dokumen pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="dokumen-wajib" title="Dokumen Wajib" subtitle="Mengelola daftar dokumen yang wajib diunggah pesantren saat pengajuan.">
    <p>Menu <strong>Dokumen Wajib</strong> menentukan dokumen apa saja yang harus diunggah pesantren saat mengajukan akreditasi.</p>
    <ul class="mb-0">
        <li class="mb-2"><strong>Tambah Dokumen:</strong> Pilih kategori, isi nama dokumen, dan deskripsi singkat.</li>
        <li class="mb-2"><strong>Urutan:</strong> Dokumen dapat diurutkan sesuai prioritas.</li>
        <li><strong>Edit/Hapus:</strong> Perubahan berlaku untuk pengajuan baru. Pengajuan yang sudah ada tidak terpengaruh.</li>
    </ul>

{{-- Screenshot Dokumen Wajib --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-dokumen-wajib.png') }}"
                 alt="Tampilan halaman Dokumen Wajib"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Dokumen Wajib pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="verifikasi-berkas" title="Verifikasi Berkas" subtitle="Super Admin dapat memverifikasi kelengkapan dokumen pengajuan.">
    <p>Proses verifikasi berkas oleh Super Admin sama dengan Admin. Lihat panduan Admin untuk langkah detail.</p>
    <p>Super Admin dapat mengambil alih verifikasi jika diperlukan, namun sebaiknya delegasikan ke Admin untuk pemisahan tugas yang baik.</p>

{{-- Screenshot Verifikasi Berkas --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-verifikasi-berkas.png') }}"
                 alt="Tampilan halaman Verifikasi Berkas"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Verifikasi Berkas pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="assign-asesor" title="Assign Asesor" subtitle="Menugaskan asesor ke pengajuan akreditasi.">
    <p>Super Admin dapat menugaskan asesor ke pengajuan akreditasi setelah berkas diverifikasi. Proses sama dengan Admin.</p>
    <p>Pastikan asesor yang ditugaskan tersedia dan memiliki kompetensi yang sesuai.</p>

{{-- Screenshot Assign Asesor --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-assign-asesor.png') }}"
                 alt="Tampilan halaman Assign Asesor"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Assign Asesor pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="validasi-sk" title="Validasi & Penerbitan SK" subtitle="Tahap akhir: validasi nilai dan penerbitan Surat Keputusan.">
    <p>Super Admin memiliki wewenang penuh untuk memvalidasi hasil akhir dan menerbitkan SK. Proses sama dengan Admin. Pastikan semua nilai NV sudah lengkap sebelum finalisasi.</p>

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

<x-panduan::section id="kelola-banding" title="Kelola Banding" subtitle="Menangani pengajuan banding dari pesantren.">
    <p>Super Admin dapat mereview dan memutuskan banding. Proses sama dengan Admin. Keputusan banding bersifat final.</p>

{{-- Screenshot Kelola Banding --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-kelola-banding.png') }}"
                 alt="Tampilan halaman Kelola Banding"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Kelola Banding pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="notifikasi-gagal" title="Notifikasi Gagal" subtitle="Memantau notifikasi sistem yang gagal terkirim.">
    <p>Menu <strong>Notifikasi Gagal</strong> menampilkan daftar notifikasi yang gagal dikirim ke pengguna. Super Admin dapat melihat detail kegagalan dan melakukan resend notifikasi.</p>

{{-- Screenshot Notifikasi Gagal --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-notifikasi-gagal.png') }}"
                 alt="Tampilan halaman Notifikasi Gagal"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Notifikasi Gagal pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="arsip-trash" title="Arsip Akreditasi" subtitle="Mengelola data akreditasi yang dihapus.">
    <p>Menu <strong>Arsip Akreditasi</strong> berisi data akreditasi yang telah dihapus (soft delete). Super Admin dapat me-restore atau menghapus permanen data di sini.</p>

{{-- Screenshot Arsip Akreditasi --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/sa-arsip-trash.png') }}"
                 alt="Tampilan halaman Arsip Akreditasi"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Arsip Akreditasi pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

</x-panduan::layout>
