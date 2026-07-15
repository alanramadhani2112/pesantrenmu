<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>429 - Terlalu Banyak Permintaan</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
    <style>
        :root { --spm-primary: #005533; }
        .error-icon-wrap {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: #fde8e8;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .error-code {
            font-size: clamp(4rem, 12vw, 7rem);
            font-weight: 600;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #dc3545;
        }
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

            <div class="error-icon-wrap">
                <i class="ki-solid ki-arrows-circle fs-2tx text-danger"></i>
            </div>

            <div class="error-code mb-3">429</div>

            <h1 class="fw-semibold text-gray-900 fs-2 mb-3">Terlalu Banyak Permintaan</h1>
            <p class="text-gray-600 fw-semibold fs-6 mb-8">
                Anda telah melakukan terlalu banyak permintaan dalam waktu singkat.<br>
                Tunggu sebentar lalu coba lagi.
            </p>

            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="javascript:history.back()" class="btn btn-primary fw-semibold px-6">
                    <i class="ki-solid ki-arrow-left fs-3 me-1"></i>
                    Kembali
                </a>
                <a href="{{ url('/dashboard') }}" class="btn btn-light fw-semibold px-6">
                    <i class="ki-solid ki-home fs-3 me-1"></i>
                    Ke Dashboard
                </a>
            </div>

        </div>
    </div>

    <div class="text-center mt-6 text-gray-500 fs-7">
        &copy; {{ date('Y') }} Sistem Penjaminan Mutu &mdash; Muhammadiyah
    </div>

</body>
</html>
