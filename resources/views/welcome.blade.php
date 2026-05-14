<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PesantrenMu') }} - Sistem Penjaminan Mutu</title>
    <meta name="description" content="Platform akreditasi dan penjaminan mutu pesantren Muhammadiyah secara digital.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">

    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
</head>

<body data-bs-theme="light" class="spm-landing">
    <div class="d-flex flex-column flex-root min-vh-100">

        {{-- Navbar --}}
        <nav class="spm-landing-nav py-4 py-lg-5 px-4 px-lg-10 d-flex align-items-center justify-content-between bg-white border-bottom sticky-top">
            <a href="/" class="d-flex align-items-center gap-3 text-decoration-none">
                <img src="{{ asset('images/brand/logo-horizontal.svg') }}" class="h-30px h-md-35px w-auto" alt="PesantrenMu" />
            </a>
            <div class="d-flex align-items-center gap-2 gap-md-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-sm btn-primary fw-bold">
                            <i class="ki-duotone ki-element-11 fs-5 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-light fw-bold">Masuk</a>
                    @endauth
                @endif
            </div>
        </nav>

        {{-- Hero Section --}}
        <section class="spm-landing-hero d-flex flex-column flex-lg-row align-items-center px-4 px-lg-10 py-10 py-lg-20 gap-10">
            <div class="flex-grow-1 mw-700px">
                <span class="badge badge-light-primary fw-bold fs-7 mb-5 px-4 py-2">
                    <i class="ki-duotone ki-shield-tick fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                    Majelis Dikdasmen PP Muhammadiyah
                </span>
                <h1 class="fw-bolder text-gray-900 mb-5" style="font-size: clamp(2.25rem, 5vw, 3.75rem); line-height: 1.1; letter-spacing: -0.02em;">
                    Sistem Penjaminan Mutu <span style="color: var(--spm-primary);">PesantrenMu</span>
                </h1>
                <p class="fs-4 text-gray-600 fw-semibold mb-8" style="max-width: 580px; line-height: 1.6;">
                    Platform akreditasi terpadu untuk pesantren Muhammadiyah. Kelola pengajuan, visitasi, dan penilaian EDPM dalam satu sistem digital yang transparan.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg fw-bold px-6">
                                Buka Dashboard
                                <i class="ki-duotone ki-arrow-right fs-3 ms-1"><span class="path1"></span><span class="path2"></span></i>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg fw-bold px-6">
                                Masuk Sistem
                                <i class="ki-duotone ki-arrow-right fs-3 ms-1"><span class="path1"></span><span class="path2"></span></i>
                            </a>
                            <a href="#fitur" class="btn btn-light btn-lg fw-bold px-6">
                                Pelajari Lebih Lanjut
                            </a>
                        @endauth
                    @endif
                </div>

                <div class="d-flex flex-wrap gap-6 gap-lg-8 mt-10 pt-5 border-top">
                    <div>
                        <div class="fs-1 fw-bolder text-gray-900">100%</div>
                        <div class="text-muted fw-semibold fs-7">Digital & Transparan</div>
                    </div>
                    <div>
                        <div class="fs-1 fw-bolder text-gray-900">3</div>
                        <div class="text-muted fw-semibold fs-7">Tahap Akreditasi</div>
                    </div>
                    <div>
                        <div class="fs-1 fw-bolder text-gray-900">EDPM</div>
                        <div class="text-muted fw-semibold fs-7">Standar Penilaian</div>
                    </div>
                </div>
            </div>

            <div class="flex-grow-1 d-flex justify-content-center">
                <div class="position-relative" style="max-width: 480px; width: 100%;">
                    <div class="spm-hero-card spm-hero-card-1 card border-0 shadow-lg p-5 mb-4">
                        <div class="d-flex align-items-center gap-4">
                            <div class="symbol symbol-50px">
                                <span class="symbol-label bg-light-success text-success">
                                    <i class="ki-duotone ki-verify fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </div>
                            <div>
                                <div class="fw-bold text-gray-900 fs-6">Akreditasi Disetujui</div>
                                <div class="text-muted fw-semibold fs-7">Peringkat Unggul</div>
                            </div>
                        </div>
                    </div>
                    <div class="spm-hero-card spm-hero-card-2 card border-0 shadow-lg p-5 mb-4">
                        <div class="d-flex align-items-center gap-4">
                            <div class="symbol symbol-50px">
                                <span class="symbol-label bg-light-primary text-primary">
                                    <i class="ki-duotone ki-document fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-gray-900 fs-6">Pengajuan Aktif</div>
                                <div class="progress h-6px mt-2">
                                    <div class="progress-bar bg-primary" style="width: 75%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="spm-hero-card spm-hero-card-3 card border-0 shadow-lg p-5">
                        <div class="d-flex align-items-center gap-4">
                            <div class="symbol symbol-50px">
                                <span class="symbol-label bg-light-warning text-warning">
                                    <i class="ki-duotone ki-chart-simple fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                </span>
                            </div>
                            <div>
                                <div class="fw-bold text-gray-900 fs-6">Monitoring Real-time</div>
                                <div class="text-muted fw-semibold fs-7">Status & Peringkat</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features Section --}}
        <section id="fitur" class="px-4 px-lg-10 py-10 py-lg-15 bg-light-primary">
            <div class="text-center mb-10">
                <span class="badge badge-light-success fw-bold fs-7 mb-3 px-4 py-2">FITUR UTAMA</span>
                <h2 class="fw-bolder text-gray-900 mb-3" style="font-size: clamp(1.75rem, 3vw, 2.5rem);">
                    Semua yang Anda butuhkan dalam satu platform
                </h2>
                <p class="fs-5 text-gray-600 fw-semibold mw-700px mx-auto">
                    Dari pengajuan hingga penerbitan sertifikat akreditasi, semua dilakukan secara digital dengan alur kerja yang jelas.
                </p>
            </div>

            <div class="row g-5 mw-1200px mx-auto">
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 spm-feature-card">
                        <div class="card-body p-6">
                            <div class="symbol symbol-60px mb-5">
                                <span class="symbol-label bg-primary text-white">
                                    <i class="ki-duotone ki-document fs-2x"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </div>
                            <h3 class="fw-bold text-gray-900 mb-3">Pengajuan Akreditasi</h3>
                            <p class="text-gray-600 fw-semibold fs-6 mb-0">
                                Ajukan akreditasi pesantren dengan formulir digital terstruktur. Upload dokumen, isi data EDPM, dan kirim pengajuan tanpa kertas.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 spm-feature-card">
                        <div class="card-body p-6">
                            <div class="symbol symbol-60px mb-5">
                                <span class="symbol-label bg-success text-white">
                                    <i class="ki-duotone ki-verify fs-2x"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </div>
                            <h3 class="fw-bold text-gray-900 mb-3">Visitasi & Penilaian</h3>
                            <p class="text-gray-600 fw-semibold fs-6 mb-0">
                                Asesor melakukan visitasi terjadwal dan mengisi penilaian instrumen langsung melalui sistem. Hasil real-time terdokumentasi otomatis.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 spm-feature-card">
                        <div class="card-body p-6">
                            <div class="symbol symbol-60px mb-5">
                                <span class="symbol-label bg-warning text-white">
                                    <i class="ki-duotone ki-chart-simple fs-2x"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                </span>
                            </div>
                            <h3 class="fw-bold text-gray-900 mb-3">Monitoring & Peringkat</h3>
                            <p class="text-gray-600 fw-semibold fs-6 mb-0">
                                Pantau status akreditasi, riwayat pengajuan, dan peringkat mutu pesantren dengan dashboard interaktif yang mudah dipahami.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 spm-feature-card">
                        <div class="card-body p-6">
                            <div class="symbol symbol-60px mb-5">
                                <span class="symbol-label bg-info text-white">
                                    <i class="ki-duotone ki-people fs-2x"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                </span>
                            </div>
                            <h3 class="fw-bold text-gray-900 mb-3">Manajemen SDM</h3>
                            <p class="text-gray-600 fw-semibold fs-6 mb-0">
                                Catat data ustadz, pamong, musyrif, dan tenaga kependidikan dengan rekapitulasi otomatis per jenjang satuan pendidikan.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 spm-feature-card">
                        <div class="card-body p-6">
                            <div class="symbol symbol-60px mb-5">
                                <span class="symbol-label bg-danger text-white">
                                    <i class="ki-duotone ki-shield-tick fs-2x"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </div>
                            <h3 class="fw-bold text-gray-900 mb-3">Standar EDPM</h3>
                            <p class="text-gray-600 fw-semibold fs-6 mb-0">
                                Penilaian berdasarkan butir komponen EDPM (Evaluasi Diri Pesantren Muhammadiyah) yang dikelola admin secara terpusat.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 spm-feature-card">
                        <div class="card-body p-6">
                            <div class="symbol symbol-60px mb-5">
                                <span class="symbol-label" style="background-color: var(--spm-primary); color: #fff;">
                                    <i class="ki-duotone ki-cloud-download fs-2x"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                            </div>
                            <h3 class="fw-bold text-gray-900 mb-3">Dokumen Digital</h3>
                            <p class="text-gray-600 fw-semibold fs-6 mb-0">
                                Akses panduan IAPM, kartu kendali, dan panduan visitasi langsung dari sistem. Tidak perlu mencari dokumen di tempat lain.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Process Section --}}
        <section class="px-4 px-lg-10 py-10 py-lg-15">
            <div class="text-center mb-10">
                <span class="badge badge-light-primary fw-bold fs-7 mb-3 px-4 py-2">ALUR KERJA</span>
                <h2 class="fw-bolder text-gray-900 mb-3" style="font-size: clamp(1.75rem, 3vw, 2.5rem);">
                    Cara Kerja Akreditasi Digital
                </h2>
                <p class="fs-5 text-gray-600 fw-semibold mw-700px mx-auto">
                    Proses akreditasi dari pengajuan hingga penerbitan sertifikat dalam tiga tahap utama.
                </p>
            </div>

            <div class="row g-5 mw-1200px mx-auto">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="spm-step-circle mx-auto mb-5">1</div>
                        <h3 class="fw-bold text-gray-900 mb-3">Pengajuan Pesantren</h3>
                        <p class="text-gray-600 fw-semibold fs-6">
                            Pesantren mengisi profil, data IPM, SDM, EDPM, dan unggah dokumen pendukung. Admin memverifikasi kelengkapan data.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="spm-step-circle mx-auto mb-5">2</div>
                        <h3 class="fw-bold text-gray-900 mb-3">Penilaian Asesor</h3>
                        <p class="text-gray-600 fw-semibold fs-6">
                            Asesor terjadwal melakukan visitasi ke pesantren dan mengisi penilaian instrumen EDPM. Skor dihitung otomatis sesuai bobot.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-center">
                        <div class="spm-step-circle mx-auto mb-5">3</div>
                        <h3 class="fw-bold text-gray-900 mb-3">Penerbitan Hasil</h3>
                        <p class="text-gray-600 fw-semibold fs-6">
                            Admin menerbitkan SK akreditasi dengan peringkat akhir. Sertifikat dapat diunduh langsung dari sistem oleh pesantren.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA Section --}}
        <section class="px-4 px-lg-10 py-10 py-lg-15">
            <div class="spm-cta-banner mw-1200px mx-auto rounded p-8 p-lg-15 text-center">
                <h2 class="text-white fw-bolder mb-3" style="font-size: clamp(1.5rem, 3vw, 2.25rem);">
                    Siap memulai akreditasi digital?
                </h2>
                <p class="text-white opacity-75 fs-5 fw-semibold mb-6 mw-600px mx-auto">
                    Bergabung dengan ekosistem PesantrenMu dan kelola akreditasi pesantren Anda secara terpusat dan transparan.
                </p>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-light btn-lg fw-bolder px-8">
                            Buka Dashboard
                            <i class="ki-duotone ki-arrow-right fs-3 ms-1"><span class="path1"></span><span class="path2"></span></i>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-light btn-lg fw-bolder px-8">
                            Masuk Sekarang
                            <i class="ki-duotone ki-arrow-right fs-3 ms-1"><span class="path1"></span><span class="path2"></span></i>
                        </a>
                    @endauth
                @endif
            </div>
        </section>

        {{-- Footer --}}
        <footer class="px-4 px-lg-10 py-6 bg-white border-top">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mw-1200px mx-auto">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('images/brand/logo-horizontal.svg') }}" class="h-25px w-auto" alt="PesantrenMu" />
                </div>
                <div class="text-gray-500 fw-semibold fs-7">
                    &copy; {{ date('Y') }} PesantrenMu &middot; Majelis Dikdasmen PP Muhammadiyah
                </div>
            </div>
        </footer>

    </div>

</body>

</html>