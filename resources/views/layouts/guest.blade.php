<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('icon.png') }}">

    <!-- Styles -->
    @livewireStyles
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css', 'resources/js/app.js'])
    @livewireScriptConfig
</head>

<body class="font-sans text-gray-900 antialiased" data-bs-theme="light">
    <div class="d-flex flex-column flex-root min-vh-100 bg-body">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <div class="d-flex flex-column flex-lg-row-fluid justify-content-center align-items-center p-10 bg-light-primary">
                <div class="w-100 mw-450px">
                    <a href="/" wire:navigate class="d-flex justify-content-center mb-10">
                        <x-application-logo class="h-45px w-auto text-primary" />
                    </a>

                    <div class="card shadow-sm border-0">
                        <div class="card-body p-8 p-lg-10">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-none d-lg-flex flex-lg-row-fluid align-items-center justify-content-center p-10 bg-primary">
                <div class="mw-500px text-white">
                    <x-ui.badge variant="primary" class="bg-white text-primary mb-6">SPM Pesantren</x-ui.badge>
                    <h1 class="fs-2hx fw-bold text-white mb-4">Sistem Penjaminan Mutu</h1>
                    <p class="fs-5 fw-semibold text-white opacity-75 mb-0">Majelis Dikdasmen PP Muhammadiyah</p>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/metronic/assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('vendor/metronic/assets/js/scripts.bundle.js') }}"></script>
</body>

</html>
