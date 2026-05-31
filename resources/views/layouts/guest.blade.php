<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PesantrenMu') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">

    <!-- Styles -->
    @livewireStyles
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css', 'resources/js/app.js'])
    @livewireScriptConfig
</head>

<body class="font-sans text-gray-900 antialiased spm-auth-body" data-bs-theme="light">
    <div class="d-flex flex-column flex-root min-vh-100 spm-auth-shell">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            {{-- Form Side --}}
            <div class="d-flex flex-column flex-lg-row-fluid justify-content-center align-items-center p-8 p-lg-12 spm-auth-form-pane">
                <div class="w-100 mw-420px">
                    <a href="/" class="d-flex justify-content-center mb-10">
                        <x-application-logo class="h-40px w-auto text-primary" />
                    </a>

                    <div class="spm-auth-card">
                        {{ $slot }}
                    </div>

                    <div class="text-center mt-8">
                        <span class="text-gray-500 fs-7">&copy; {{ date('Y') }} PesantrenMu &middot; LP2M Muhammadiyah</span>
                    </div>
                </div>
            </div>

            {{-- Visual Side --}}
            <div class="d-none d-lg-flex flex-lg-row-fluid spm-auth-aside">
                <div class="spm-auth-aside-content">
                    <div class="spm-auth-aside-badge">PesantrenMu</div>
                    <h1>Semua proses akreditasi, cukup di sini.</h1>
                    <p>Pengajuan, penilaian, sampai penerbitan hasil. Satu alur, satu sistem.</p>

                    <div class="spm-auth-stats">
                        <div class="spm-auth-stat">
                            <strong>7</strong>
                            <span>Tahap akreditasi</span>
                        </div>
                        <div class="spm-auth-stat">
                            <strong>3</strong>
                            <span>Peran pengguna</span>
                        </div>
                        <div class="spm-auth-stat">
                            <strong>1</strong>
                            <span>Alur terpadu</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
