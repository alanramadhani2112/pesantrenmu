<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PesantrenMu') }} - Sistem Akreditasi LP2M</title>
    <meta name="description" content="Platform akreditasi pesantren Muhammadiyah oleh LP2M. Kelola pengajuan, penilaian, dan hasil akreditasi dalam satu sistem.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">

    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
</head>

<body data-bs-theme="light" class="spm-landing spm-landing-v3">
    <div class="spm-landing-page">
        {{-- Header --}}
        <header class="spm-landing-header">
            <div class="spm-landing-container">
                <nav class="spm-landing-nav">
                    <a href="/" class="spm-landing-brand" aria-label="PesantrenMu">
                        <img src="{{ asset('images/brand/logo-horizontal.svg') }}" alt="PesantrenMu" loading="eager" fetchpriority="high">
                    </a>

                    <div class="spm-landing-actions">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn btn-sm btn-primary fw-semibold">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-sm btn-primary fw-semibold spm-landing-login-btn">
                                    Masuk
                                </a>
                            @endauth
                        @endif
                    </div>
                </nav>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="spm-landing-hero">
                <div class="spm-landing-container">
                    <div class="spm-hero-content">
                        <div class="spm-hero-text">
                            <h1>Akreditasi pesantren, satu sistem untuk semua pihak.</h1>
                            <p>PesantrenMu menghubungkan pesantren, asesor, dan LP2M dalam satu alur kerja digital — dari pengajuan hingga penerbitan hasil akreditasi.</p>

                            <div class="spm-hero-cta">
                                @if (Route::has('login'))
                                    @auth
                                        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg fw-semibold">
                                            Buka Dashboard
                                        </a>
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg fw-semibold">
                                            Masuk Sistem
                                        </a>
                                    @endauth
                                @endif
                            </div>
                        </div>

                        <div class="spm-hero-visual">
                            <div class="spm-hero-workflow">
                                <div class="spm-wf-step is-done">
                                    <div class="spm-wf-dot"></div>
                                    <span>Pengajuan</span>
                                </div>
                                <div class="spm-wf-line is-done"></div>
                                <div class="spm-wf-step is-done">
                                    <div class="spm-wf-dot"></div>
                                    <span>Review</span>
                                </div>
                                <div class="spm-wf-line is-done"></div>
                                <div class="spm-wf-step is-active">
                                    <div class="spm-wf-dot"></div>
                                    <span>Visitasi</span>
                                </div>
                                <div class="spm-wf-line"></div>
                                <div class="spm-wf-step">
                                    <div class="spm-wf-dot"></div>
                                    <span>Penilaian</span>
                                </div>
                                <div class="spm-wf-line"></div>
                                <div class="spm-wf-step">
                                    <div class="spm-wf-dot"></div>
                                    <span>Hasil</span>
                                </div>
                            </div>

                            <div class="spm-hero-roles">
                                <div class="spm-role-card">
                                    <div class="spm-role-icon spm-role-pesantren">
                                        <i class="ki-duotone ki-teacher fs-1"><span class="path1"></span><span class="path2"></span></i>
                                    </div>
                                    <strong>Pesantren</strong>
                                    <span>Ajukan & lengkapi data</span>
                                </div>
                                <div class="spm-role-card">
                                    <div class="spm-role-icon spm-role-asesor">
                                        <i class="ki-duotone ki-magnifier fs-1"><span class="path1"></span><span class="path2"></span></i>
                                    </div>
                                    <strong>Asesor</strong>
                                    <span>Review & nilai</span>
                                </div>
                                <div class="spm-role-card">
                                    <div class="spm-role-icon spm-role-admin">
                                        <i class="ki-duotone ki-shield-tick fs-1"><span class="path1"></span><span class="path2"></span></i>
                                    </div>
                                    <strong>Admin LP2M</strong>
                                    <span>Validasi & terbitkan</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Features --}}
            <section class="spm-landing-features">
                <div class="spm-landing-container">
                    <div class="spm-features-grid">
                        <div class="spm-feature">
                            <div class="spm-feature-icon">
                                <i class="ki-duotone ki-shield-tick fs-2x"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                            <h3>Alur terkontrol</h3>
                            <p>Setiap tahap akreditasi berjalan berurutan. Tidak ada langkah yang terlewat atau tumpang tindih.</p>
                        </div>
                        <div class="spm-feature">
                            <div class="spm-feature-icon">
                                <i class="ki-duotone ki-document fs-2x"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                            <h3>Dokumen terpusat</h3>
                            <p>EDPM, laporan visitasi, kartu kendali, SK, dan sertifikat tersimpan dalam satu tempat.</p>
                        </div>
                        <div class="spm-feature">
                            <div class="spm-feature-icon">
                                <i class="ki-duotone ki-people fs-2x"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                            </div>
                            <h3>Peran yang jelas</h3>
                            <p>Pesantren, asesor, dan admin masing-masing punya ruang kerja sesuai tanggung jawabnya.</p>
                        </div>
                        <div class="spm-feature">
                            <div class="spm-feature-icon">
                                <i class="ki-duotone ki-chart-line-up fs-2x"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                            <h3>Hasil terukur</h3>
                            <p>Nilai asesor, nilai kelompok, dan nilai akhir tercatat lengkap dengan rekomendasi perbaikan.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- CTA --}}
            <section class="spm-landing-cta-section">
                <div class="spm-landing-container">
                    <div class="spm-landing-cta">
                        <h2>Siap mengelola akreditasi pesantren?</h2>
                        <p>Masuk ke sistem untuk memulai atau melanjutkan proses akreditasi.</p>
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn btn-light btn-lg fw-semibold">Buka Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-light btn-lg fw-semibold">Masuk Sistem</a>
                            @endauth
                        @endif
                    </div>
                </div>
            </section>
        </main>

        <footer class="spm-landing-footer">
            <div class="spm-landing-container spm-landing-footer-inner">
                <img src="{{ asset('images/brand/logo-horizontal.svg') }}" alt="PesantrenMu" loading="lazy">
                <span>&copy; {{ date('Y') }} PesantrenMu &middot; Dikembangkan oleh LabMu untuk LP2M Muhammadiyah</span>
            </div>
        </footer>
    </div>
</body>

</html>
