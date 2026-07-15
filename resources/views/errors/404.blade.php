<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Halaman Tidak Ditemukan</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
    <style>
        :root { --spm-primary: #005533; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 align-items-center justify-content-center bg-body p-6">

    <div class="text-center mb-8">
        <a href="{{ url('/') }}">
            <img src="{{ asset('images/brand/logo-horizontal.svg') }}" alt="SPM" style="height: 36px;" loading="lazy">
        </a>
    </div>

    <div class="card border border-dashed border-gray-300 w-100 mw-450px">
        <div class="card-body p-10 text-center">

            <div class="spm-error-icon-wrap spm-error-icon-wrap-primary">
                <i class="ki-solid ki-magnifier fs-2tx text-primary"></i>
            </div>

            <div class="spm-error-code spm-error-code-primary mb-3">404</div>

            <h1 class="fw-semibold text-gray-900 fs-2 mb-3">Halaman Tidak Ditemukan</h1>
            <p class="text-gray-600 fw-semibold fs-6 mb-8">
                Halaman yang Anda cari tidak ada atau telah dipindahkan.<br>
                Periksa kembali URL atau kembali ke dashboard.
            </p>

            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <x-ui.button :href="url('/dashboard')" class="px-6" icon="home" icon-class="fs-3 me-1">
                    Ke Dashboard
                </x-ui.button>
                <x-ui.button href="javascript:history.back()" variant="light" class="px-6" icon="arrow-left" icon-class="fs-3 me-1">
                    Kembali
                </x-ui.button>
            </div>

        </div>
    </div>

    <div class="text-center mt-6 text-gray-500 fs-7">
        &copy; {{ date('Y') }} Sistem Penjaminan Mutu &mdash; Muhammadiyah
    </div>

</body>
</html>
