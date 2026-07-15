<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Kesalahan Server</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
</head>
<body class="spm-error-page app-blank">
    <main class="spm-error-shell d-flex flex-column align-items-center justify-content-center min-vh-100 px-6 py-10" aria-labelledby="error-title">
        <a href="{{ url('/') }}" class="spm-error-brand" aria-label="PesantrenMu">
            <img class="spm-error-brand-img" src="{{ asset('images/brand/logo-horizontal.svg') }}" alt="PesantrenMu" loading="lazy">
        </a>

        <section class="card card-flush spm-error-card w-100" data-ui-card="metronic">
            <div class="card-body text-center">
                <div class="symbol symbol-60px spm-error-mark spm-error-mark-danger mx-auto">
                    <div class="symbol-label">
                        <span class="spm-error-mark-symbol spm-error-mark-symbol-danger">!</span>
                    </div>
                </div>

                <div class="spm-error-code spm-error-code-danger">500</div>

                <h1 id="error-title" class="spm-error-title">Terjadi Kesalahan</h1>
                <p class="spm-error-message">
                    Sistem sedang mengalami kendala. Silakan coba lagi beberapa saat.
                </p>

                <div class="spm-error-actions">
                    <x-ui.button type="button" onclick="window.location.reload()" class="px-6">
                        Coba Lagi
                    </x-ui.button>
                    <x-ui.button :href="url('/')" variant="light" class="px-6">
                        Ke Beranda
                    </x-ui.button>
                </div>
            </div>
        </section>

        <div class="spm-error-footer">&copy; {{ date('Y') }} PesantrenMu</div>
    </main>
</body>
</html>
