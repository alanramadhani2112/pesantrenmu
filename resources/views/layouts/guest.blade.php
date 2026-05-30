<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PesantrenMu') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @livewireStyles
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css', 'resources/js/app.js'])
    @livewireScriptConfig
</head>

<body class="font-sans text-gray-900 antialiased spm-auth-body" data-bs-theme="light">
    <div class="d-flex flex-column flex-root min-vh-100 spm-auth-shell">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <div class="d-flex flex-column flex-lg-row-fluid justify-content-center align-items-center p-8 p-lg-12 spm-auth-form-pane">
                <div class="w-100 mw-450px">
                    <a href="/" class="d-flex justify-content-center mb-8">
                        <x-application-logo class="h-45px w-auto text-primary" />
                    </a>

                    <div class="card border-0 spm-auth-card">
                        <div class="card-body p-8 p-lg-10">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-none d-lg-flex flex-lg-row-fluid align-items-center justify-content-center p-12 spm-auth-aside">
                <div class="mw-500px spm-auth-aside-card">
                    <div class="spm-auth-aside-kicker">PesantrenMu / LP2M</div>
                    <h1 class="fs-2hx fw-semibold text-gray-900 mb-4">Masuk ke ruang kerja akreditasi</h1>
                    <p class="fs-5 fw-semibold text-gray-600 mb-8">Portal ini memisahkan tugas pesantren, asesor, dan admin supaya setiap tahap akreditasi mudah diawasi.</p>

                    <div class="spm-auth-access-board">
                        <div class="spm-auth-access-item">
                            <span>01</span>
                            <div>
                                <strong>Pesantren</strong>
                                <small>Pengajuan, data mutu, dan kartu kendali.</small>
                            </div>
                        </div>
                        <div class="spm-auth-access-item is-current">
                            <span>02</span>
                            <div>
                                <strong>Asesor</strong>
                                <small>Review berkas, visitasi, nilai, dan rekomendasi.</small>
                            </div>
                        </div>
                        <div class="spm-auth-access-item">
                            <span>03</span>
                            <div>
                                <strong>Admin LP2M</strong>
                                <small>Validasi akhir, SK, sertifikat, dan hasil.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="spm-auth-footer">
            <span>&copy; {{ date('Y') }} PesantrenMu. Dikembangkan oleh LabMu untuk LP2M.</span>
        </footer>
    </div>
</body>

</html>
