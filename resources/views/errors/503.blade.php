<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 - Layanan Tidak Tersedia</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
    <style>
        :root { --spm-primary: #005533; }
        .error-icon-wrap {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: #e6f0eb;
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
            color: #005533;
        }
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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
                <i class="ki-solid ki-setting-2 fs-2tx text-primary pulse"></i>
            </div>

            <div class="error-code mb-3">503</div>

            <h1 class="fw-semibold text-gray-900 fs-2 mb-3">Sedang Dalam Pemeliharaan</h1>
            <p class="text-gray-600 fw-semibold fs-6 mb-8">
                Sistem sedang dalam pemeliharaan terjadwal.<br>
                Kami akan segera kembali. Terima kasih atas kesabaran Anda.
            </p>

            @if(isset($exception) && $exception->getMessage())
                <div class="alert alert-light-info d-flex align-items-center mb-8 text-start">
                    <i class="ki-solid ki-information-5 fs-2hx text-info me-4"></i>
                    <div class="text-gray-700 fw-semibold fs-7">{{ $exception->getMessage() }}</div>
                </div>
            @endif

            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <x-ui.button href="javascript:location.reload()" class="px-6" icon="arrows-circle" icon-class="fs-3 me-1">
                    Coba Lagi
                </x-ui.button>
            </div>

        </div>
    </div>

    <div class="text-center mt-6 text-gray-500 fs-7">
        &copy; {{ date('Y') }} Sistem Penjaminan Mutu &mdash; Muhammadiyah
    </div>

</body>
</html>
