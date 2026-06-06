<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') — {{ config('app.name', 'PesantrenMu') }}@else{{ config('app.name', 'PesantrenMu') }}@endif</title>
    <meta name="description" content="Sistem Penjaminan Mutu PesantrenMu — Platform akreditasi pesantren Muhammadiyah.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">
    <link rel="preload" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}" as="style">
    <link rel="preconnect" href="{{ url('/') }}" crossorigin>

    <!-- Styles -->
    @livewireStyles
    {{-- Defer non-critical Metronic shell CSS to prevent render blocking. --}}
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}" media="print" onload="this.media='all'">
    {{-- Critical app CSS loaded normally --}}
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css'])
    @livewireScriptConfig
</head>

<body
    id="kt_app_body"
    class="app-default font-sans antialiased text-gray-900"
    data-bs-theme="light"
    data-kt-app-header-fixed="true"
    data-kt-app-header-fixed-mobile="true"
    data-kt-app-sidebar-enabled="true"
    data-kt-app-sidebar-fixed="true"
    data-kt-app-sidebar-push-header="true"
    data-kt-app-sidebar-push-footer="true"
    data-kt-app-page-loading-enabled="false"
    x-data
>
    @php
        $routeName = request()->route()?->getName();
        $docSlug = request()->route('doc');
        $docTitle = match ($docSlug) {
            'all' => __('Semua Dokumen'),
            'iapm' => __('IAPM (Instrumen Akreditasi Penjaminan Mutu)'),
            'kartu_kendali' => __('Kartu Kendali'),
            'visitasi' => __('Laporan Visitasi'),
            default => $docSlug ? str($docSlug)->replace(['-', '_'], ' ')->title()->toString() : __('Dokumen'),
        };

        $routeMeta = [
            'dashboard' => ['title' => __('Dashboard')],
            'profile' => ['title' => __('Pengaturan Profil'), 'section' => __('Akun')],
            'roles.index' => ['title' => __('Role Sistem'), 'section' => __('Manajemen Sistem')],
            'accounts.index' => ['title' => __('Akun Pengguna'), 'section' => __('Manajemen Sistem')],
            'documents.index' => ['title' => $docTitle, 'section' => __('Dokumen')],

            'admin.master-edpm' => ['title' => __('Komponen EDPM/IPR'), 'section' => __('Master Data')],
            'admin.master-kategori-dokumen' => ['title' => __('Kategori Dokumen'), 'section' => __('Master Data')],
            'admin.master-dokumen' => ['title' => __('Dokumen Wajib'), 'section' => __('Master Data')],
            'admin.master-role-permission' => ['title' => __('Peran & Hak Akses'), 'section' => __('Manajemen Sistem')],
            'admin.akreditasi' => ['title' => __('Akreditasi'), 'section' => __('Operasional Akreditasi')],
            'admin.akreditasi-detail' => ['title' => __('Detail Akreditasi'), 'section' => __('Operasional Akreditasi')],
            'admin.asesor.index' => ['title' => __('Asesor'), 'section' => __('Operasional Akreditasi')],
            'admin.asesor.detail' => ['title' => __('Detail Asesor'), 'section' => __('Operasional Akreditasi')],
            'admin.banding' => ['title' => __('Banding'), 'section' => __('Operasional Akreditasi')],
            'admin.banding-detail' => ['title' => __('Detail Banding'), 'section' => __('Operasional Akreditasi')],
            'admin.pesantren.index' => ['title' => __('Pesantren'), 'section' => __('Operasional Akreditasi')],
            'admin.pesantren.detail' => ['title' => __('Detail Pesantren'), 'section' => __('Operasional Akreditasi')],
            'admin.failed-notifications' => ['title' => __('Notifikasi Gagal'), 'section' => __('Administrasi')],
            'admin.trash' => ['title' => __('Arsip Akreditasi'), 'section' => __('Administrasi')],

            'asesor.profile' => ['title' => __('Profil Asesor'), 'section' => __('Profil')],
            'asesor.akreditasi' => ['title' => __('Akreditasi'), 'section' => __('Tugas Akreditasi')],
            'asesor.akreditasi-detail' => ['title' => __('Detail Akreditasi'), 'section' => __('Tugas Akreditasi')],

            'pesantren.profile' => ['title' => __('Profil Pesantren'), 'section' => __('Persiapan Akreditasi')],
            'pesantren.ipm' => ['title' => __('Indikator Pemenuhan Mutlak (IPM)'), 'section' => __('Persiapan Akreditasi')],
            'pesantren.sdm' => ['title' => __('Data SDM Pesantren'), 'section' => __('Persiapan Akreditasi')],
            'pesantren.edpm' => ['title' => __('EDPM/IPR'), 'section' => __('Persiapan Akreditasi')],
            'pesantren.akreditasi' => ['title' => __('Pengajuan Akreditasi'), 'section' => __('Pengajuan')],
            'pesantren.akreditasi-detail' => ['title' => __('Detail Pengajuan Akreditasi'), 'section' => __('Pengajuan')],

            'panduan.superadmin' => ['title' => __('Panduan Super Admin'), 'section' => __('Panduan')],
            'panduan.admin' => ['title' => __('Panduan Admin'), 'section' => __('Panduan')],
            'panduan.asesor' => ['title' => __('Panduan Asesor'), 'section' => __('Panduan')],
            'panduan.pesantren' => ['title' => __('Panduan Pesantren'), 'section' => __('Panduan')],
        ];

        $routeTitle = $routeMeta[$routeName]['title'] ?? null;
        $routeSection = $routeMeta[$routeName]['section'] ?? null;

        if ($routeName === 'pesantren.akreditasi') {
            $akreditasiFocus = request()->query('focus');
            $routeTitle = match ($akreditasiFocus) {
                'perbaikan' => __('Status Perbaikan'),
                'kartu_kendali' => __('Kartu Kendali Visitasi'),
                'hasil', 'sertifikat', 'banding' => __('Hasil Akhir Akreditasi'),
                default => $routeTitle,
            };
        }

        if ($routeName === 'asesor.akreditasi') {
            $asesorFocus = request()->query('focus');
            $routeTitle = match ($asesorFocus) {
                'review' => __('Review Berkas'),
                'jadwal' => __('Atur Jadwal Visitasi'),
                'nilai' => __('Input Nilai Visitasi'),
                'laporan_visitasi' => __('Laporan Visitasi'),
                default => $routeTitle,
            };
        }

        $slotHeaderTitle = isset($header) ? trim(strip_tags((string) $header)) : '';
        $pageTitle = $routeTitle ?: ($slotHeaderTitle !== '' ? $slotHeaderTitle : __('Dashboard'));

        $breadcrumbItems = [
            ['label' => __('Dashboard'), 'url' => route('dashboard')],
        ];

        if (! request()->routeIs('dashboard')) {
            if ($routeSection) {
                $breadcrumbItems[] = ['label' => $routeSection];
            }

            $breadcrumbItems[] = ['label' => $pageTitle];
        }
    @endphp

    <div class="spm-navigate-progress" aria-hidden="true">
        <div class="spm-navigate-progress-bar"></div>
    </div>

    <div class="d-flex flex-column flex-root app-root spm-app-shell" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            <x-layout.app-header
                :page-title="$pageTitle"
                :breadcrumb-items="$breadcrumbItems"
                :current-user="auth()->user()"
                :role-name="auth()->user()?->role?->name ?? 'user'"
            />

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <livewire:layout.navigation />

                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">
                        <main id="kt_app_content" class="app-content flex-column-fluid spm-main-content">
                            <div id="kt_app_content_container" class="app-container container-fluid">
                                {{ $slot }}
                            </div>
                        </main>
                    </div>

                    <footer class="app-footer spm-footer">
                        <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-4">
                            <div class="text-gray-500 fw-semibold fs-7">
                                &copy; {{ date('Y') }} PesantrenMu &middot; Sistem Penjaminan Mutu
                            </div>
                        </div>
                    </footer>
                </div>
            </div>
        </div>
    </div>

    @if(session('status') || session('success') || session('error'))
        <script>
            window.__spmFlashAlert = @js(session('error')
                ? ['type' => 'error', 'title' => 'Terjadi Kesalahan!', 'message' => session('error')]
                : ['type' => 'success', 'title' => 'Berhasil!', 'message' => session('status') ?? session('success')]);
        </script>
    @endif

    {{-- Metronic JS bundle — provides KT components (password meter, menu, drawer, etc.) --}}
    <script src="{{ asset('vendor/metronic/assets/js/scripts.bundle.js') }}"></script>
    {{-- Deferred app JS — loaded after content for faster first paint --}}
    @vite(['resources/js/app.js'])
</body>

</html>
