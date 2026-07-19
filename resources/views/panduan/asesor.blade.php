@php
$sections = [
    ['id' => 'dashboard', 'title' => 'Dashboard', 'icon' => 'ki-element-11'],
    ['id' => 'tugas-akreditasi', 'title' => 'Tugas Akreditasi', 'icon' => 'ki-check-circle', 'children' => [
        ['id' => 'review-substansi', 'title' => 'Review Substansi'],
        ['id' => 'visitasi', 'title' => 'Visitasi'],
        ['id' => 'penilaian-pasca-visitasi', 'title' => 'Penilaian Pasca Visitasi'],
    ]],
    ['id' => 'profil-asesor', 'title' => 'Profil Asesor', 'icon' => 'ki-user'],
];
@endphp

<x-panduan::layout title="Panduan Asesor" :sections="$sections" currentSection="dashboard">

{{-- Screenshot tampilan penuh dashboard asesor --}}
<div class="card card-flush mb-5">
    <div class="card-header border-0 pt-5 pb-0 px-5">
        <h3 class="card-title fw-semibold fs-3 text-gray-900">Tampilan Dashboard Asesor</h3>
    </div>
    <div class="card-body pt-4 px-5 pb-5">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/asesor-full.png') }}"
                 alt="Tampilan lengkap dashboard Asesor"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
    </div>
</div>

<x-panduan::section id="dashboard" title="Dashboard Asesor" subtitle="Halaman utama asesor menampilkan ringkasan tugas, status akreditasi yang sedang ditangani, dan akses cepat.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Login ke Sistem</strong><br/>
                Buka <code>dev-pesantren.muhammadiyah.or.id/login</code>, masukkan email dan password asesor Anda. Sistem akan mengarahkan ke Dashboard Asesor.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Memahami Dashboard</strong><br/>
                Dashboard asesor menampilkan jumlah tugas aktif, status akreditasi yang sedang ditangani, dan notifikasi terbaru.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Navigasi</strong><br/>
                Gunakan sidebar kiri untuk mengakses: <strong>Tugas Akreditasi</strong> (daftar tugas) dan <strong>Profil Asesor</strong> (data diri).
            </div>
        </li>
    </ol>

{{-- Screenshot Dashboard Asesor --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/asesor-dashboard.png') }}"
                 alt="Tampilan halaman Dashboard Asesor"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Dashboard Asesor pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="review-substansi" title="Review Substansi" subtitle="Asesor melakukan review substansi terhadap pengajuan akreditasi yang telah ditugaskan.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Tugas Akreditasi</strong><br/>
                Klik <strong>Tugas Akreditasi</strong> di sidebar. Tabel menampilkan daftar akreditasi yang ditugaskan kepada Anda. Filter status <strong>Review Asesor (4)</strong>.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Review Dokumen Pesantren</strong><br/>
                Klik baris akreditasi untuk membuka detail. Tab <strong>Dokumen</strong> menampilkan semua dokumen yang diunggah pesantren. Baca dan periksa setiap dokumen.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Input Penilaian Awal</strong><br/>
                Pada tab <strong>Instrumen</strong>, input nilai awal (NA1 dan NA2) untuk setiap komponen EDPM/IPR yang tersedia berdasarkan dokumen yang telah direview.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">4</span>
            <div>
                <strong>Selesai Review</strong><br/>
                Setelah semua komponen diisi, klik <strong>Selesai Review</strong>. Status akreditasi berubah ke <strong>Visitasi (3)</strong>. Jika Anda Ketua Kelompok, lanjutkan ke jadwal visitasi.
            </div>
        </li>
    </ol>

{{-- Screenshot Review Substansi (Tugas Akreditasi) --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/asesor-akreditasi.png') }}"
                 alt="Tampilan halaman Review Substansi (Tugas Akreditasi)"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Review Substansi (Tugas Akreditasi) pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="visitasi" title="Visitasi" subtitle="Ketua Kelompok menjadwalkan dan mengelola kunjungan visitasi ke pesantren.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Hanya Ketua Kelompok</strong><br/>
                Fitur visitasi hanya tersedia untuk <strong>Ketua Kelompok</strong> (asesor pertama yang di-assign oleh admin). Asesor anggota tidak memiliki akses jadwal visitasi.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Jadwalkan Visitasi</strong><br/>
                Pada halaman detail akreditasi, klik tombol <strong>Jadwalkan Visitasi</strong>. Tentukan tanggal, waktu, dan lokasi kunjungan ke pesantren.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Konfirmasi Visitasi</strong><br/>
                Setelah visitasi selesai, klik <strong>Konfirmasi Visitasi</strong>. Status berubah ke <strong>Penilaian Pasca Visitasi (2)</strong>. Semua asesor (ketua dan anggota) kini dapat menginput nilai final.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">4</span>
            <div>
                <strong>Cetak Kartu Kendali</strong><br/>
                Sebelum visitasi, Anda dapat mencetak <strong>Kartu Kendali Visitasi</strong> melalui tombol yang tersedia di halaman detail akreditasi.
            </div>
        </li>
    </ol>

{{-- Screenshot Visitasi (Detail Akreditasi) --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/asesor-visitasi.png') }}"
                 alt="Tampilan halaman Visitasi (Detail Akreditasi)"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Visitasi (Detail Akreditasi) pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="penilaian-pasca-visitasi" title="Penilaian Pasca Visitasi" subtitle="Asesor menginput nilai final (NA1, NA2, NK) dan mengunggah laporan pasca visitasi.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Filter Status "Pasca Visitasi"</strong><br/>
                Di menu Akreditasi, filter status <strong>Penilaian Pasca Visitasi (2)</strong>. Buka detail akreditasi yang siap dinilai.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Input Nilai Final</strong><br/>
                Pada tab <strong>Instrumen</strong>, input nilai final <strong>NA1</strong>, <strong>NA2</strong>, dan <strong>NK</strong> berdasarkan temuan visitasi. Setiap komponen EDPM/IPR dinilai secara terpisah.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Upload Laporan Visitasi</strong><br/>
                Pada tab <strong>Laporan</strong>, unggah file laporan visitasi dalam format PDF. Laporan ini akan digunakan admin pada tahap validasi.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">4</span>
            <div>
                <strong>Selesai Penilaian</strong><br/>
                Setelah semua nilai diisi dan laporan diunggah, klik <strong>Selesai Penilaian</strong>. Status berubah ke <strong>Validasi Admin (1)</strong>. Tugas asesor selesai.
            </div>
        </li>
    </ol>

{{-- Screenshot Penilaian Pasca Visitasi --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/asesor-visitasi.png') }}"
                 alt="Tampilan halaman Penilaian Pasca Visitasi"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Penilaian Pasca Visitasi pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

<x-panduan::section id="profil-asesor" title="Profil Asesor" subtitle="Asesor dapat mengelola data diri, foto profil, dan dokumen pribadi.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Profil Asesor</strong><br/>
                Klik <strong>Profil Asesor</strong> di sidebar untuk membuka halaman data diri.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Upload Dokumen</strong><br/>
                Unggah KTP, Ijazah, dan Kartu NBM melalui form yang tersedia. Dokumen ini diperlukan untuk verifikasi asesor oleh admin.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-semibold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Update Foto Profil</strong><br/>
                Klik area foto profil untuk mengunggah foto baru. Foto akan diperbarui secara langsung tanpa reload halaman.
            </div>
        </li>
    </ol>

{{-- Screenshot Profil Asesor --}}
<div class="card card-flush mt-6">
    <div class="card-body p-4">
        <div class="rounded border border-gray-300 overflow-hidden bg-gray-100 text-center">
            <img src="{{ asset('images/panduan/asesor-profile.png') }}"
                 alt="Tampilan halaman Profil Asesor"
                 class="img-fluid w-100"
                 loading="lazy" />
        </div>
        <div class="text-center text-gray-500 mt-2 small">Gambar: Tampilan halaman Profil Asesor pada Sistem PesantrenMu</div>
    </div>
</div>
</x-panduan::section>

</x-panduan::layout>
