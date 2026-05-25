<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PesantrenMu') }} - Sistem Akreditasi LP2M</title>
    <meta name="description" content="Sistem akreditasi pesantren Muhammadiyah yang dikembangkan oleh LabMu untuk LP2M.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">

    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
</head>

<body data-bs-theme="light" class="spm-landing spm-landing-v2">
    <div class="spm-landing-page">
        <header class="spm-landing-header">
            <div class="spm-landing-container">
                <nav class="spm-landing-nav">
                    <a href="/" class="spm-landing-brand" aria-label="PesantrenMu">
                        <img src="{{ asset('images/brand/logo-horizontal.svg') }}" alt="PesantrenMu" loading="eager" fetchpriority="high">
                    </a>

                    <div class="spm-landing-links" aria-label="Navigasi utama">
                        <a href="#tentang">Tentang</a>
                        <a href="#keunggulan">Keunggulan</a>
                        <a href="#alur">Alur</a>
                    </div>

                    <div class="spm-landing-actions">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn btn-sm btn-primary fw-bold">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-sm btn-light fw-bold">
                                    Masuk
                                </a>
                            @endauth
                        @endif
                    </div>
                </nav>
            </div>
        </header>

        <main>
            <section class="spm-landing-container spm-landing-hero">
                <div class="spm-landing-hero-panel">
                    <div class="spm-landing-hero-copy">
                        <span class="spm-landing-eyebrow">
                            Dikembangkan oleh LabMu untuk LP2M
                        </span>

                        <h1>Sistem Akreditasi Pesantren Muhammadiyah</h1>

                        <p>
                            PesantrenMu membantu LP2M mengelola pengajuan, review berkas,
                            visitasi, penilaian asesor, validasi akhir, hingga penerbitan
                            hasil akreditasi dalam satu alur kerja digital yang rapi.
                        </p>

                        <div class="spm-landing-hero-actions">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg fw-bold">
                                        Buka Dashboard
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg fw-bold">
                                        Masuk Sistem
                                        <i class="ki-duotone ki-arrow-right fs-3 ms-1"><span class="path1"></span><span class="path2"></span></i>
                                    </a>
                                    <a href="#tentang" class="btn btn-light btn-lg fw-bold">
                                        Lihat Ringkasan
                                    </a>
                                @endauth
                            @endif
                        </div>
                    </div>

                    <div class="spm-landing-product-preview" aria-label="Preview dashboard akreditasi">
                        <div class="spm-preview-topbar">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="spm-preview-header">
                            <div>
                                <span class="spm-preview-label">Status Akreditasi</span>
                                <strong>Validasi Akhir Admin</strong>
                            </div>
                            <span class="badge badge-light-primary">LP2M</span>
                        </div>
                        <div class="spm-preview-grid">
                            <div class="spm-preview-stat">
                                <span>Pengajuan</span>
                                <strong>128</strong>
                            </div>
                            <div class="spm-preview-stat">
                                <span>Visitasi</span>
                                <strong>34</strong>
                            </div>
                            <div class="spm-preview-stat">
                                <span>Selesai</span>
                                <strong>87</strong>
                            </div>
                        </div>
                        <div class="spm-preview-flow">
                            <div class="is-done">Pengajuan</div>
                            <div class="is-done">Review Admin</div>
                            <div class="is-active">Visitasi</div>
                            <div>Validasi</div>
                        </div>
                        <div class="spm-preview-table">
                            <div><span>Pesantren Al-Falah</span><strong>Unggul</strong></div>
                            <div><span>Pesantren Darul Ilmi</span><strong>Baik</strong></div>
                            <div><span>Pesantren Madani</span><strong>Proses</strong></div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="tentang" class="spm-landing-section">
                <div class="spm-landing-container">
                    <div class="spm-section-heading">
                        <span>Tentang Sistem</span>
                        <h2>Dirancang untuk kebutuhan akreditasi LP2M</h2>
                        <p>
                            Sistem ini memusatkan data pesantren, instrumen EDPM/IPR,
                            penugasan asesor, laporan visitasi, kartu kendali, nilai akhir,
                            SK, sertifikat, dan rekomendasi hasil penilaian.
                        </p>
                    </div>

                    <div class="spm-about-grid">
                        <div class="spm-about-block">
                            <span class="spm-about-number">01</span>
                            <h3>LabMu sebagai pengembang</h3>
                            <p>Fokus pada produk digital yang stabil, mudah dirawat, dan selaras dengan proses akreditasi pesantren.</p>
                        </div>
                        <div class="spm-about-block">
                            <span class="spm-about-number">02</span>
                            <h3>LP2M sebagai konteks utama</h3>
                            <p>Istilah, alur, dan output sistem mengikuti kebutuhan Lembaga Pengembangan Pesantren Muhammadiyah.</p>
                        </div>
                        <div class="spm-about-block">
                            <span class="spm-about-number">03</span>
                            <h3>Berpusat pada pengguna</h3>
                            <p>Pesantren, asesor, dan admin mendapatkan tampilan kerja sesuai tugas dan tahap proses masing-masing.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="keunggulan" class="spm-landing-section spm-landing-section-muted">
                <div class="spm-landing-container">
                    <div class="spm-section-heading">
                        <span>Keunggulan</span>
                        <h2>Lebih mudah dikontrol, lebih jelas dipertanggungjawabkan</h2>
                    </div>

                    <div class="spm-advantages-grid">
                        <article class="spm-advantage-card">
                            <i class="ki-duotone ki-shield-tick fs-2x text-primary"><span class="path1"></span><span class="path2"></span></i>
                            <h3>Workflow akreditasi terkunci</h3>
                            <p>Status pengajuan bergerak bertahap dari pengajuan, review, visitasi, pasca visitasi, validasi, sampai hasil akhir.</p>
                        </article>
                        <article class="spm-advantage-card">
                            <i class="ki-duotone ki-document fs-2x text-primary"><span class="path1"></span><span class="path2"></span></i>
                            <h3>Dokumen tersentralisasi</h3>
                            <p>Dokumen pesantren, laporan visitasi, kartu kendali, SK, dan sertifikat tersimpan dalam konteks proses yang sama.</p>
                        </article>
                        <article class="spm-advantage-card">
                            <i class="ki-duotone ki-chart-line-up fs-2x text-primary"><span class="path1"></span><span class="path2"></span></i>
                            <h3>Penilaian lebih transparan</h3>
                            <p>Nilai asesor, nilai kelompok, nilai verifikasi admin, dan rekomendasi dapat ditelusuri sesuai peran pengguna.</p>
                        </article>
                        <article class="spm-advantage-card">
                            <i class="ki-duotone ki-profile-user fs-2x text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                            <h3>Role berbasis tugas</h3>
                            <p>Admin, asesor, pesantren, dan super admin memiliki menu dan aksi yang relevan dengan tanggung jawabnya.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="alur" class="spm-landing-section">
                <div class="spm-landing-container">
                    <div class="spm-section-heading">
                        <span>Alur Kerja</span>
                        <h2>Dari pengajuan sampai hasil resmi</h2>
                    </div>

                    <div class="spm-flow-list">
                        <div class="spm-flow-item">
                            <span>1</span>
                            <div>
                                <h3>Pesantren melengkapi data</h3>
                                <p>Profil, IPM, SDM, EDPM/IPR, dan dokumen pendukung disiapkan sebelum pengajuan.</p>
                            </div>
                        </div>
                        <div class="spm-flow-item">
                            <span>2</span>
                            <div>
                                <h3>Admin dan asesor melakukan review</h3>
                                <p>Admin memeriksa berkas awal, lalu asesor melakukan review substansi dan visitasi.</p>
                            </div>
                        </div>
                        <div class="spm-flow-item">
                            <span>3</span>
                            <div>
                                <h3>Validasi akhir dan penerbitan hasil</h3>
                                <p>Admin memvalidasi nilai, menerbitkan SK, sertifikat, dan rekomendasi hasil penilaian.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="spm-landing-cta-section">
                <div class="spm-landing-container">
                    <div class="spm-landing-cta">
                        <div>
                            <span>PesantrenMu untuk LP2M</span>
                            <h2>Kelola akreditasi pesantren dalam sistem yang lebih tertib.</h2>
                        </div>
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn btn-light btn-lg fw-bold">Buka Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-light btn-lg fw-bold">Masuk Sistem</a>
                            @endauth
                        @endif
                    </div>
                </div>
            </section>
        </main>

        <footer class="spm-landing-footer">
            <div class="spm-landing-container spm-landing-footer-inner">
                <img src="{{ asset('images/brand/logo-horizontal.svg') }}" alt="PesantrenMu" loading="lazy">
                <span>&copy; {{ date('Y') }} PesantrenMu. Dikembangkan oleh LabMu untuk LP2M.</span>
            </div>
        </footer>
    </div>
</body>

</html>
