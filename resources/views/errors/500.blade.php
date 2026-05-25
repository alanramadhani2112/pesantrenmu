<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Kesalahan Server</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
    <style>
        :root { --spm-primary: #005533; }
    </style>
</head>
<body data-bs-theme="light" class="d-flex flex-column min-vh-100 align-items-center justify-content-center bg-body p-6">

    <div class="text-center mb-8">
        <a href="{{ url('/') }}">
            <img src="{{ asset('images/brand/logo-horizontal.svg') }}" alt="SPM" style="height: 36px;" loading="lazy">
        </a>
    </div>

    <div class="card shadow-sm border-0 w-100 mw-450px">
        <div class="card-body p-10 text-center">

            <div class="spm-error-icon-wrap spm-error-icon-wrap-danger">
                <i class="ki-solid ki-warning-2 fs-2tx text-danger">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                </i>
            </div>

            <div class="spm-error-code spm-error-code-danger mb-3">500</div>

            <h1 class="fw-bold text-gray-900 fs-2 mb-3">Kesalahan Server</h1>
            <p class="text-gray-600 fw-semibold fs-6 mb-8">
                Terjadi kesalahan pada server kami.<br>
                Tim teknis sudah diberitahu. Silakan coba lagi beberapa saat.
            </p>

            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="{{ url('/dashboard') }}" class="btn btn-primary fw-bold px-6">
                    <i class="ki-solid ki-home fs-3 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Ke Dashboard
                </a>
                <a href="javascript:location.reload()" class="btn btn-light fw-bold px-6">
                    <i class="ki-solid ki-arrows-circle fs-3 me-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Coba Lagi
                </a>
            </div>

        </div>
    </div>

    <div class="text-center mt-6 text-gray-500 fs-7">
        &copy; {{ date('Y') }} Sistem Penjaminan Mutu &mdash; Muhammadiyah
    </div>

</body>
</html>
