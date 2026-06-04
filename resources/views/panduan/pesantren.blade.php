@php
$sections = [
    ['id' => 'dashboard', 'title' => 'Dashboard', 'icon' => 'ki-element-11'],
    ['id' => 'persiapan-akreditasi', 'title' => 'Persiapan Akreditasi', 'children' => [
        ['id' => 'profil-pesantren', 'title' => 'Profil Pesantren'],
        ['id' => 'input-ipm', 'title' => 'Indikator Pemenuhan Mutlak (IPM)'],
        ['id' => 'data-sdm', 'title' => 'Data SDM'],
        ['id' => 'input-edpm', 'title' => 'EDPM/IPR'],
    ]],
    ['id' => 'pengajuan', 'title' => 'Pengajuan & Status', 'children' => [
        ['id' => 'submit-akreditasi', 'title' => 'Submit Pengajuan'],
        ['id' => 'pantau-status', 'title' => 'Pantau Status'],
        ['id' => 'aksi-banding', 'title' => 'Ajukan Banding'],
    ]],
];
@endphp

<x-panduan::layout title="Panduan Pesantren" :sections="$sections" currentSection="dashboard">

<x-panduan::section id="dashboard" title="Dashboard Pesantren" subtitle="Halaman utama pesantren menampilkan status akreditasi, progres persiapan, dan notifikasi.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Login ke Sistem</strong><br/>
                Buka <code>dev-pesantren.muhammadiyah.or.id/login</code>, masukkan email dan password pesantren Anda. Sistem akan mengarahkan ke Dashboard Pesantren.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Memahami Dashboard</strong><br/>
                Dashboard menampilkan progres persiapan akreditasi: kelengkapan profil, IPM, data SDM, dan EDPM/IPR. Indikator hijau berarti data sudah lengkap.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Langkah Persiapan</strong><br/>
                Sebelum mengajukan akreditasi, lengkapi keempat komponen di sidebar: <strong>Profil Pesantren → IPM → Data SDM → EDPM/IPR</strong>.
            </div>
        </li>
    </ol>
</x-panduan::section>

<x-panduan::section id="profil-pesantren" title="Profil Pesantren" subtitle="Melengkapi data identitas pesantren sebagai langkah pertama persiapan.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Profil Pesantren</strong><br/>
                Klik <strong>Profil Pesantren</strong> di sidebar. Form menampilkan data pesantren: NPSN, nama, alamat, pimpinan, kontak, dan informasi lainnya.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Isi Data Lengkap</strong><br/>
                Isi seluruh field yang tersedia. Data yang akurat membantu proses verifikasi oleh admin. Klik <strong>Simpan</strong> setelah selesai.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Upload Foto Profil</strong><br/>
                Klik area foto untuk mengunggah logo atau foto pesantren. Format JPG/PNG, maksimal 2MB.
            </div>
        </li>
    </ol>
</x-panduan::section>

<x-panduan::section id="input-ipm" title="Indikator Pemenuhan Mutlak (IPM)" subtitle="Mengisi data indikator pemenuhan mutlak sebagai syarat akreditasi.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Menu IPM</strong><br/>
                Klik <strong>IPM</strong> di sidebar. Tabel menampilkan daftar indikator pemenuhan mutlak yang harus diisi.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Isi Setiap Indikator</strong><br/>
                Klik setiap indikator, isi nilai yang sesuai dengan kondisi pesantren Anda. Setiap indikator memiliki kriteria penilaian tertentu.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>IPM Harus Lengkap</strong><br/>
                Semua indikator IPM harus terisi sebelum Anda dapat mengajukan akreditasi. Progres IPM terlihat di dashboard.
            </div>
        </li>
    </ol>
</x-panduan::section>

<x-panduan::section id="data-sdm" title="Data SDM" subtitle="Menginput data Sumber Daya Manusia pesantren (ustadz, tenaga kependidikan, santri).">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Menu SDM</strong><br/>
                Klik <strong>Data SDM</strong> di sidebar. Halaman menampilkan form data SDM pesantren.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Isi Data Tenaga Pendidik</strong><br/>
                Input jumlah dan kualifikasi ustadz/ustadzah: S1, S2, S3, dan yang belum sarjana.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Isi Data Santri</strong><br/>
                Input jumlah santri mukim dan tidak mukim, serta data tenaga kependidikan. Klik <strong>Simpan</strong>.
            </div>
        </li>
    </ol>
</x-panduan::section>

<x-panduan::section id="input-edpm" title="EDPM/IPR" subtitle="Mengisi evaluasi diri pesantren menggunakan instrumen EDPM/IPR.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Menu EDPM/IPR</strong><br/>
                Klik <strong>EDPM/IPR</strong> di sidebar. Tabel menampilkan komponen-komponen EDPM/IPR yang harus dinilai.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Isi Evaluasi Diri</strong><br/>
                Klik setiap komponen, berikan nilai evaluasi diri berdasarkan kondisi aktual pesantren. Jujur dan objektif dalam menilai.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Semua Komponen Wajib</strong><br/>
                Seluruh komponen EDPM/IPR harus terisi sebelum pengajuan akreditasi dapat dilakukan.
            </div>
        </li>
    </ol>
</x-panduan::section>

<x-panduan::section id="submit-akreditasi" title="Submit Pengajuan Akreditasi" subtitle="Mengajukan akreditasi setelah semua data persiapan lengkap.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Menu Akreditasi</strong><br/>
                Klik <strong>Akreditasi</strong> di sidebar. Halaman menampilkan riwayat dan status pengajuan akreditasi Anda.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Klik "Ajukan Akreditasi"</strong><br/>
                Jika semua data persiapan sudah lengkap (profil, IPM, SDM, EDPM), tombol <strong>Ajukan Akreditasi</strong> akan aktif. Klik tombol tersebut.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Upload Dokumen Wajib</strong><br/>
                Sistem akan meminta Anda mengunggah dokumen wajib sesuai kategori yang ditentukan admin. Upload semua dokumen yang diminta.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">4</span>
            <div>
                <strong>Submit</strong><br/>
                Setelah semua dokumen terunggah, klik <strong>Submit</strong>. Status berubah ke <strong>Pengajuan (6)</strong> — menunggu verifikasi admin.
            </div>
        </li>
    </ol>
</x-panduan::section>

<x-panduan::section id="pantau-status" title="Pantau Status Akreditasi" subtitle="Memantau perkembangan status pengajuan dari tahap ke tahap.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Buka Menu Akreditasi</strong><br/>
                Klik <strong>Akreditasi</strong> di sidebar. Tabel menampilkan pengajuan Anda beserta status terkini.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Pahami Alur Status</strong><br/>
                Status berubah sesuai tahapan: <strong>Pengajuan (6) → Verifikasi Berkas (5) → Review Asesor (4) → Visitasi (3) → Penilaian Pasca Visitasi (2) → Validasi Admin (1) → Hasil Akhir</strong>.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Lihat Detail</strong><br/>
                Klik baris pengajuan untuk melihat detail termasuk dokumen yang sudah diverifikasi, catatan admin/asesor, dan riwayat perubahan status.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">4</span>
            <div>
                <strong>Notifikasi</strong><br/>
                Anda akan menerima notifikasi setiap kali status berubah. Klik ikon lonceng di pojok kanan atas untuk melihat semua notifikasi.
            </div>
        </li>
    </ol>
</x-panduan::section>

<x-panduan::section id="aksi-banding" title="Ajukan Banding" subtitle="Jika akreditasi ditolak final, pesantren dapat mengajukan banding.">
    <ol class="list-unstyled mb-0">
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">1</span>
            <div>
                <strong>Status "Ditolak Final"</strong><br/>
                Ketika status pengajuan menjadi <strong>Ditolak Final (-1)</strong>, tombol <strong>Ajukan Banding</strong> akan muncul di halaman detail akreditasi.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3 mb-4">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">2</span>
            <div>
                <strong>Ajukan Banding</strong><br/>
                Klik <strong>Ajukan Banding</strong>. Isi alasan banding secara jelas dan lengkap. Unggah dokumen pendukung jika diperlukan.
            </div>
        </li>
        <li class="d-flex align-items-start gap-3">
            <span class="badge badge-primary rounded-circle fw-bold flex-shrink-0" style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">3</span>
            <div>
                <strong>Tunggu Keputusan</strong><br/>
                Status berubah ke <strong>Banding (-2)</strong>. Admin akan mereview banding Anda. Anda akan menerima notifikasi saat ada keputusan.
            </div>
        </li>
    </ol>
</x-panduan::section>

</x-panduan::layout>
