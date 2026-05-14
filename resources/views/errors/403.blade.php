<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
</head>
<body data-bs-theme="light" class="d-flex flex-column min-vh-100 align-items-center justify-content-center bg-light p-6">
    <div class="text-center mw-500px">
        <div class="fw-bolder text-gray-900 mb-4" style="font-size: clamp(5rem, 15vw, 10rem); line-height: 1; letter-spacing: -0.05em; color: #f59e0b;">403</div>
        <h1 class="fw-bold text-gray-900 mb-3 fs-2">Akses Ditolak</h1>
        <p class="text-gray-600 fw-semibold fs-5 mb-8">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
        <a href="{{ url('/dashboard') }}" class="btn btn-primary fw-bold px-6">
            <i class="ki-duotone ki-arrow-left fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
            Kembali ke Dashboard
        </a>
    </div>
</body>
</html>
