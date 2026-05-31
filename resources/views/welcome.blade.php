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
            <section class="spm-hero">
                <div class="spm-hero-pattern"></div>
                <div class="spm-landing-container">
                    <div class="spm-hero-layout">
                        <div class="spm-hero-text">
                            <h1 class="spm-hero-headline">Infrastruktur digital untuk akreditasi pesantren Muhammadiyah.</h1>
                            <p class="spm-hero-sub">PesantrenMu menyatukan pesantren, asesor, dan LP2M dalam satu alur kerja. Dari pengajuan data sampai penerbitan SK, semuanya berjalan di satu tempat.</p>

                            <div class="spm-hero-cta">
                                @if (Route::has('login'))
                                    @auth
                                        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg fw-semibold">Masuk ke Sistem</a>
                                    @else
                                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg fw-semibold">Masuk ke Sistem</a>
                                        <a href="#alur-kerja" class="btn btn-light btn-lg fw-semibold">Lihat Alur Kerja</a>
                                    @endauth
                                @endif
                            </div>
                        </div>

                        <div class="spm-hero-visual">
                            {{-- Dashboard mockup --}}
                            <div class="spm-mockup">
                                <div class="spm-mockup-header">
                                    <div class="spm-mockup-dots">
                                        <span></span><span></span><span></span>
                                    </div>
                                    <div class="spm-mockup-title">Dashboard PesantrenMu</div>
                                </div>
                                <div class="spm-mockup-body">
                                    <div class="spm-mockup-sidebar">
                                        <div class="spm-mockup-nav-item is-active"></div>
                                        <div class="spm-mockup-nav-item"></div>
                                        <div class="spm-mockup-nav-item"></div>
                                        <div class="spm-mockup-nav-item"></div>
                                    </div>
                                    <div class="spm-mockup-content">
                                        <div class="spm-mockup-stat-row">
                                            <div class="spm-mockup-stat"></div>
                                            <div class="spm-mockup-stat"></div>
                                            <div class="spm-mockup-stat"></div>
                                        </div>
                                        <div class="spm-mockup-table">
                                            <div class="spm-mockup-row"></div>
                                            <div class="spm-mockup-row"></div>
                                            <div class="spm-mockup-row"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Floating role badges --}}
                            <div class="spm-hero-badges">
                                <span class="spm-badge-float spm-badge-pesantren">Pesantren</span>
                                <span class="spm-badge-float spm-badge-asesor">Asesor</span>
                                <span class="spm-badge-float spm-badge-admin">Admin LP2M</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Problem --}}
            <section class="spm-problem">
                <div class="spm-landing-container">
                    <div class="spm-problem-content">
                        <h2 class="spm-section-title">Proses akreditasi yang masih berjalan terpisah-pisah.</h2>
                        <p class="spm-problem-text">Dokumen dikirim lewat email, penilaian dicatat manual, hasil sulit ditelusuri. Pesantren, asesor, dan LP2M bekerja di tempat berbeda tanpa sistem yang menyatukan.</p>
                        <p class="spm-problem-text spm-problem-solution">PesantrenMu hadir untuk menghubungkan semua pihak dalam satu proses yang jelas dan bisa dipertanggungjawabkan.</p>
                    </div>
                </div>
            </section>

            {{-- Ecosystem / Bento Grid --}}
            <section class="spm-ecosystem" id="alur-kerja">
                <div class="spm-landing-container">
                    <h2 class="spm-section-title">Satu platform, seluruh proses akreditasi.</h2>

                    <div class="spm-bento">
                        {{-- Large card: Alur 7 Tahap --}}
                        <div class="spm-bento-card spm-bento-large">
                            <div class="spm-bento-icon">
                                <i class="ki-duotone ki-arrows-circle fs-2x"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                            <h3>Alur 7 Tahap</h3>
                            <p>Dari pengajuan, verifikasi berkas, review asesor, visitasi, penilaian, validasi admin, sampai penerbitan hasil. Setiap tahap terhubung otomatis.</p>
                            <div class="spm-bento-steps">
                                <span class="spm-step-pill">Pengajuan</span>
                                <span class="spm-step-pill">Verifikasi</span>
                                <span class="spm-step-pill">Review</span>
                                <span class="spm-step-pill">Visitasi</span>
                                <span class="spm-step-pill">Penilaian</span>
                                <span class="spm-step-pill">Validasi</span>
                                <span class="spm-step-pill spm-step-pill-active">Hasil</span>
                            </div>
                        </div>

                        {{-- Medium card: Penilaian Transparan --}}
                        <div class="spm-bento-card spm-bento-medium">
                            <div class="spm-bento-icon">
                                <i class="ki-duotone ki-chart-line-up fs-2x"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                            <h3>Penilaian Transparan</h3>
                            <p>Nilai asesor (NA1, NA2), nilai kelompok (NK), dan nilai validasi (NV) tercatat lengkap. Setiap perubahan memiliki audit trail.</p>
                        </div>

                        {{-- Medium card: Dokumen Terpusat --}}
                        <div class="spm-bento-card spm-bento-medium">
                            <div class="spm-bento-icon">
                                <i class="ki-duotone ki-document fs-2x"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                            <h3>Dokumen Terpusat</h3>
                            <p>EDPM, laporan visitasi, kartu kendali, SK, dan sertifikat tersimpan aman. Tidak ada lagi dokumen tercecer di email.</p>
                        </div>

                        {{-- Small card: Notifikasi Otomatis --}}
                        <div class="spm-bento-card spm-bento-small">
                            <div class="spm-bento-icon">
                                <i class="ki-duotone ki-notification-bing fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            </div>
                            <h3>Notifikasi Otomatis</h3>
                            <p>Setiap perubahan status langsung dikirim ke pihak terkait.</p>
                        </div>

                        {{-- Small card: Multi-Role --}}
                        <div class="spm-bento-card spm-bento-small">
                            <div class="spm-bento-icon">
                                <i class="ki-duotone ki-people fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                            </div>
                            <h3>Multi-Role</h3>
                            <p>Pesantren, asesor, dan admin punya ruang kerja masing-masing.</p>
                        </div>

                        {{-- Small card: Audit Trail --}}
                        <div class="spm-bento-card spm-bento-small">
                            <div class="spm-bento-icon">
                                <i class="ki-duotone ki-shield-tick fs-1"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                            <h3>Audit Trail</h3>
                            <p>Setiap aksi tercatat. Siapa, kapan, dan apa yang berubah.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Why PesantrenMu --}}
            <section class="spm-why">
                <div class="spm-landing-container">
                    <h2 class="spm-section-title">Dibangun khusus untuk kebutuhan LP2M.</h2>

                    <div class="spm-why-grid">
                        <div class="spm-why-item">
                            <div class="spm-why-number">01</div>
                            <h3>Terstruktur</h3>
                            <p>Alur akreditasi mengikuti standar LP2M. Setiap tahap punya aturan, deadline, dan validasi yang jelas.</p>
                        </div>
                        <div class="spm-why-item">
                            <div class="spm-why-number">02</div>
                            <h3>Transparan</h3>
                            <p>Semua pihak bisa melihat progress sesuai perannya. Tidak ada proses yang tersembunyi atau sulit dilacak.</p>
                        </div>
                        <div class="spm-why-item">
                            <div class="spm-why-number">03</div>
                            <h3>Terhubung</h3>
                            <p>Pesantren, asesor, dan admin bekerja di satu tempat. Notifikasi otomatis memastikan tidak ada yang terlewat.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- CTA --}}
            <section class="spm-cta">
                <div class="spm-landing-container">
                    <div class="spm-cta-content">
                        <h2>Siap memulai proses akreditasi yang lebih tertata?</h2>
                        <p>Masuk ke sistem untuk mengajukan atau melanjutkan akreditasi pesantren.</p>
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn btn-light btn-lg fw-semibold">Buka Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-light btn-lg fw-semibold">Masuk ke Sistem</a>
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
