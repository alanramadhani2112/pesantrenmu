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

<body class="font-sans text-gray-900 antialiased spm-auth-body" style="background-color:#f5f7fa" data-bs-theme="light">
    <div class="d-flex flex-column flex-root min-vh-100 spm-auth-shell">
        <div class="d-flex flex-column flex-column-fluid justify-content-center align-items-center p-6 p-lg-10 spm-auth-form-pane">
            <div class="spm-auth-panel">
                <a href="/" class="spm-auth-brand" aria-label="PesantrenMu">
                    <x-application-logo class="h-40px w-auto text-primary" />
                </a>

                <div class="spm-auth-card">
                    @hasSection('content')
                        @yield('content')
                    @else
                        {{ $slot ?? '' }}
                    @endif
                </div>

                <div class="text-center mt-7">
                    <span class="text-gray-500 fs-7">&copy; {{ date('Y') }} PesantrenMu</span>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
