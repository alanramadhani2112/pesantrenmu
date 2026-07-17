<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PesantrenMu') }} - Sistem Akreditasi Pesantren</title>
    <meta name="description" content="Sistem akreditasi pesantren yang membantu proses berjalan lebih tertata, transparan, dan terpusat.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">

    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
</head>

<body class="spm-landing spm-landing-v3">
    <div class="spm-landing-page">
        {{-- Header --}}
        <header class="spm-landing-header">
            <div class="spm-landing-container">
                <nav class="spm-landing-nav">
                    <a href="/" class="spm-landing-brand" aria-label="PesantrenMu">
                        <img src="{{ asset('images/brand/logo-horizontal.svg') }}" alt="PesantrenMu" loading="eager" fetchpriority="high">
                    </a>

                    <div class="spm-landing-links" aria-label="Navigasi halaman">
                        <a href="#masalah">Manfaat</a>
                        <a href="#alur-kerja">Alur</a>
                        <a href="#keunggulan">Keunggulan</a>
                    </div>

                    <div class="spm-landing-actions">
                        @if (Route::has('login'))
                            @auth
                                <x-ui.button :href="url('/dashboard')" size="sm">Dashboard</x-ui.button>
                            @else
                                <x-ui.button :href="route('login')" size="sm" class="spm-landing-login-btn">Masuk</x-ui.button>
                            @endauth
                        @endif
                    </div>
                </nav>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="spm-hero">
                <div class="spm-landing-container">
                    <div class="spm-hero-layout">
                        <div class="spm-hero-text">
                            <div class="spm-hero-kicker">
                                <x-ui.badge variant="success">Sistem Akreditasi Pesantren</x-ui.badge>
                                <span>Terpusat &bull; Tertata &bull; Mudah Dipantau</span>
                            </div>
                            <h1 class="spm-hero-headline">Sistem akreditasi pesantren yang lebih tertata.</h1>
                            <p class="spm-hero-sub">PesantrenMu membantu proses akreditasi berjalan lebih mudah, transparan, dan terpusat dalam satu sistem.</p>

                            <div class="spm-hero-cta">
                                @if (Route::has('login'))
                                    @auth
                                        <x-ui.button :href="url('/dashboard')" size="lg" icon="arrow-right" icon-position="end">Buka Dashboard</x-ui.button>
                                    @else
                                        <x-ui.button :href="route('login')" size="lg" icon="arrow-right" icon-position="end">Masuk ke Sistem</x-ui.button>
                                        <x-ui.button href="#masalah" variant="light" size="lg">Lihat Manfaat</x-ui.button>
                                    @endauth
                                @endif
                            </div>

                            <div class="spm-hero-proof">
                                <span><x-ui.icon name="check-circle" class="fs-3" />Proses tertata</span>
                                <span><x-ui.icon name="shield-tick" class="fs-3" />Mudah dipantau</span>
                                <span><x-ui.icon name="document" class="fs-3" />Data terpusat</span>
                            </div>
                        </div>

                        <div class="spm-hero-visual">
                            <div class="spm-hero-image-card">
                                <img
                                    src="{{ asset('images/landing/akreditasi-hero.png') }}"
                                    alt="Santri menggunakan laptop di ruang belajar pesantren"
                                    loading="eager"
                                    fetchpriority="high"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Problem --}}
            <section class="spm-problem" id="masalah">
                <div class="spm-landing-container">
                    <div class="spm-problem-layout">
                        <div>
                            <span class="spm-section-eyebrow">Manfaat sistem</span>
                            <h2 class="spm-section-title">Akreditasi pesantren menjadi lebih mudah dikelola.</h2>
                            <p class="spm-problem-text">Dengan satu sistem, proses akreditasi dapat berjalan lebih rapi tanpa banyak koordinasi manual.</p>
                        </div>

                        <div class="spm-problem-list">
                            <div class="spm-problem-item">
                                <x-ui.icon name="document" class="fs-2" />
                                <div>
                                    <h3>Lebih tertata</h3>
                                    <p>Alur akreditasi disusun agar setiap tahapan lebih mudah diikuti.</p>
                                </div>
                            </div>
                            <div class="spm-problem-item">
                                <x-ui.icon name="chart-line-up" class="fs-2" />
                                <div>
                                    <h3>Lebih transparan</h3>
                                    <p>Perkembangan proses dapat dipantau oleh pihak yang berkepentingan.</p>
                                </div>
                            </div>
                            <div class="spm-problem-item">
                                <x-ui.icon name="time" class="fs-2" />
                                <div>
                                    <h3>Lebih efisien</h3>
                                    <p>Pengelolaan data dan komunikasi menjadi lebih ringkas.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Ecosystem / Bento Grid --}}
            <section class="spm-ecosystem" id="alur-kerja">
                <div class="spm-landing-container">
                    <div class="spm-section-heading-center">
                        <span class="spm-section-eyebrow">Fungsi utama</span>
                        <h2 class="spm-section-title">Satu sistem untuk proses akreditasi pesantren.</h2>
                        <p>PesantrenMu membantu pengajuan, pemantauan, penilaian, dan pengelolaan hasil akreditasi berjalan lebih sederhana.</p>
                    </div>

                    <div class="spm-bento">
                        <div class="spm-bento-card spm-bento-large">
                            <div class="spm-bento-icon">
                                <x-ui.icon name="arrows-circle" class="fs-2x" />
                            </div>
                            <h3>Alur akreditasi dalam satu tempat</h3>
                            <p>Membantu proses akreditasi berjalan dari pengajuan hingga hasil akhir secara lebih tertib.</p>
                            <div class="spm-bento-steps">
                                <span class="spm-step-pill">Pengajuan</span>
                                <span class="spm-step-pill">Pemeriksaan</span>
                                <span class="spm-step-pill">Penilaian</span>
                                <span class="spm-step-pill spm-step-pill-active">Hasil</span>
                            </div>
                        </div>

                        <div class="spm-bento-card spm-bento-medium">
                            <div class="spm-bento-icon">
                                <x-ui.icon name="chart-line-up" class="fs-2x" />
                            </div>
                            <h3>Pengajuan lebih mudah</h3>
                            <p>Pesantren dapat memulai proses akreditasi melalui alur yang lebih jelas.</p>
                        </div>

                        <div class="spm-bento-card spm-bento-medium">
                            <div class="spm-bento-icon">
                                <x-ui.icon name="document" class="fs-2x" />
                            </div>
                            <h3>Data lebih terpusat</h3>
                            <p>Informasi akreditasi tersimpan lebih rapi dalam satu sistem.</p>
                        </div>

                        <div class="spm-bento-card spm-bento-small">
                            <div class="spm-bento-icon">
                                <x-ui.icon name="notification-bing" class="fs-1" />
                            </div>
                            <h3>Mudah dipantau</h3>
                            <p>Perkembangan proses dapat dilihat dengan lebih cepat.</p>
                        </div>

                        <div class="spm-bento-card spm-bento-small">
                            <div class="spm-bento-icon">
                                <x-ui.icon name="people" class="fs-1" />
                            </div>
                            <h3>Kolaborasi lebih rapi</h3>
                            <p>Pesantren, asesor, dan admin bekerja dalam alur yang sama.</p>
                        </div>

                        <div class="spm-bento-card spm-bento-small">
                            <div class="spm-bento-icon">
                                <x-ui.icon name="shield-tick" class="fs-1" />
                            </div>
                            <h3>Hasil lebih jelas</h3>
                            <p>Keputusan akreditasi dapat dikelola dan disampaikan dengan lebih tertib.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Why PesantrenMu --}}
            <section class="spm-why" id="keunggulan">
                <div class="spm-landing-container">
                    <div class="spm-section-heading-center">
                        <span class="spm-section-eyebrow">Keunggulan</span>
                        <h2 class="spm-section-title">Dibangun untuk proses akreditasi pesantren.</h2>
                    </div>

                    <div class="spm-why-grid">
                        <div class="spm-why-item">
                            <div class="spm-why-number">01</div>
                            <h3>Terstruktur</h3>
                            <p>Membantu proses akreditasi berjalan melalui tahapan yang lebih jelas.</p>
                        </div>
                        <div class="spm-why-item">
                            <div class="spm-why-number">02</div>
                            <h3>Transparan</h3>
                            <p>Perkembangan proses lebih mudah diketahui oleh pihak terkait.</p>
                        </div>
                        <div class="spm-why-item">
                            <div class="spm-why-number">03</div>
                            <h3>Terhubung</h3>
                            <p>Semua pihak bekerja dalam sistem yang sama untuk tujuan akreditasi.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- CTA --}}
            <section class="spm-cta">
                <div class="spm-landing-container">
                    <div class="spm-cta-content">
                        <span class="spm-cta-kicker">PesantrenMu</span>
                        <h2>Mulai proses akreditasi pesantren dengan lebih tertata.</h2>
                        <p>Masuk ke sistem untuk mengelola proses akreditasi secara lebih mudah.</p>
                        @if (Route::has('login'))
                            @auth
                                <x-ui.button :href="url('/dashboard')" variant="light" size="lg">Buka Dashboard</x-ui.button>
                            @else
                                <x-ui.button :href="route('login')" variant="light" size="lg">Masuk ke Sistem</x-ui.button>
                            @endauth
                        @endif
                    </div>
                </div>
            </section>
        </main>

        <footer class="spm-landing-footer">
            <div class="spm-landing-container spm-landing-footer-inner">
                <img src="{{ asset('images/brand/logo-horizontal.svg') }}" alt="PesantrenMu" loading="lazy">
                <div class="spm-footer-credit">
                    <span>&copy; {{ date('Y') }} PesantrenMu &middot; Dikembangkan oleh</span>
                    <img src="{{ asset('images/brand/labmu-logo.png') }}" alt="LabMu" loading="lazy">
                </div>
            </div>
        </footer>
    </div>
</body>

</html>
