<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'PesantrenMu') }} - Sistem Penjaminan Mutu</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">

    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/plugins/global/plugins.bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
</head>

<body data-bs-theme="light">
    <div class="d-flex flex-column flex-root min-vh-100">

        {{-- Navbar --}}
        <nav class="py-5 px-6 px-lg-10 d-flex align-items-center justify-content-between bg-white border-bottom">
            <a href="/" class="d-flex align-items-center gap-3">
                <x-application-logo class="h-35px w-auto" />
                <span class="fw-bold fs-4 text-gray-900 d-none d-sm-inline">PesantrenMu</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-sm btn-primary fw-bold">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-light fw-bold">Masuk</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-sm btn-primary fw-bold">Daftar</a>
                        @endif
                    @endauth
                @endif
            </div>
        </nav>

        {{-- Hero --}}
        <div class="d-flex flex-column flex-lg-row flex-grow-1">
            <div class="d-flex flex-column justify-content-center align-items-start p-10 p-lg-20 flex-lg-row-fluid">
                <div class="mw-600px">
                    <x-ui.badge variant="primary" class="mb-6">Majelis Dikdasmen PP Muhammadiyah</x-ui.badge>
                    <h1 class="fw-bolder text-gray-900 mb-5" style="font-size: clamp(2rem, 4vw, 3rem); line-height: 1.2;">
                        Sistem Penjaminan Mutu<br>PesantrenMu
                    </h1>
                    <p class="fs-5 text-gray-600 fw-semibold mb-8" style="max-width: 480px;">
                        Platform akreditasi dan penilaian mutu pesantren secara digital.
                        Kelola pengajuan, visitasi, dan penilaian EDPM dalam satu sistem terpadu.
                    </p>
                    <div class="d-flex flex-wrap gap-4">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg fw-bold px-8">
                                    <i class="ki-duotone ki-element-11 fs-2 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                    Buka Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-primary btn-lg fw-bold px-8">
                                    <i class="ki-duotone ki-entrance-right fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                                    Masuk Sistem
                                </a>
                            @endauth
                        @endif
                    </div>
                </div>
            </div>

            {{-- Feature Cards --}}
            <div class="d-flex flex-column justify-content-center p-10 p-lg-15 bg-light-primary flex-lg-row-fluid">
                <div class="mw-450px mx-auto w-100">
                    <div class="d-flex flex-column gap-5">

                        <div class="card border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center gap-4 p-6">
                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary" style="width:50px;height:50px;min-width:50px;">
                                    <i class="ki-duotone ki-document fs-2 text-white"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-5 text-gray-900">Pengajuan Akreditasi</div>
                                    <div class="text-gray-600 fs-7">Ajukan akreditasi pesantren secara online dengan kelengkapan dokumen digital.</div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center gap-4 p-6">
                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-success" style="width:50px;height:50px;min-width:50px;">
                                    <i class="ki-duotone ki-verify fs-2 text-white"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-5 text-gray-900">Visitasi & Penilaian</div>
                                    <div class="text-gray-600 fs-7">Asesor melakukan visitasi dan penilaian EDPM langsung melalui sistem.</div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center gap-4 p-6">
                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning" style="width:50px;height:50px;min-width:50px;">
                                    <i class="ki-duotone ki-chart-simple fs-2 text-white"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-5 text-gray-900">Monitoring & Peringkat</div>
                                    <div class="text-gray-600 fs-7">Pantau status akreditasi dan peringkat mutu pesantren secara real-time.</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <footer class="py-5 px-6 px-lg-10 bg-white border-top text-center">
            <span class="text-gray-500 fw-semibold fs-7">
                &copy; {{ date('Y') }} PesantrenMu Muhammadiyah &middot; v1.0.0
            </span>
        </footer>

    </div>

</body>

</html>
