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
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/plugins/global/plugins.bundle.css') }}">
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
                    <x-ui.badge variant="primary" class="mb-6">PesantrenMu</x-ui.badge>
                    <h1 class="fs-2hx fw-bold text-gray-900 mb-4">Sistem Penjaminan Mutu PesantrenMu</h1>
                    <p class="fs-5 fw-semibold text-gray-600 mb-8">Majelis Dikdasmen PP Muhammadiyah</p>

                    <div class="row g-4">
                        <div class="col-6">
                            <div class="spm-auth-metric">
                                <div class="fs-7 fw-bold text-gray-500 text-uppercase mb-1">Role</div>
                                <div class="fs-5 fw-bolder text-gray-900">3 Aktor</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="spm-auth-metric">
                                <div class="fs-7 fw-bold text-gray-500 text-uppercase mb-1">Workflow</div>
                                <div class="fs-5 fw-bolder text-gray-900">Akreditasi</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
