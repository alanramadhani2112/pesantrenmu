@props([
    'title' => 'Panduan',
    'sections' => [],
    'currentSection' => '',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} &mdash; {{ config('app.name', 'PesantrenMu') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">
    <link rel="preload" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}" as="style">

    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}" media="print" onload="this.media='all'">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css', 'resources/js/app.js'])
</head>

<body class="app-default font-sans antialiased text-gray-900">

    {{-- Mobile overlay --}}
    <div class="panduan-sidebar-backdrop d-lg-none d-none" id="kt_panduan_overlay"></div>

    {{-- ===== Metronic shell: app-page > app-header + app-wrapper ===== --}}
    <div class="d-flex flex-column flex-root app-root panduan-shell" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">

            {{-- Header — mirror app.blade.php --}}
            <div id="kt_app_header" class="app-header spm-app-header panduan-header">
                <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
                    <div class="d-flex align-items-center flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center d-lg-none me-3">
                            <button type="button" class="btn btn-icon btn-active-light-primary w-35px h-35px"
                                    id="kt_panduan_sidebar_toggle" aria-label="Buka sidebar">
                                <i class="ki-duotone ki-burger-menu fs-2">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                                </i>
                            </button>
                        </div>

                        <a href="/" class="d-flex align-items-center d-lg-none me-4">
                            <img src="{{ asset('images/brand/favicon.svg') }}" class="h-30px" alt="PesantrenMu" loading="eager" />
                        </a>

                        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3 min-w-0">
                            <h1 class="page-heading d-flex text-gray-900 fw-semibold fs-3 flex-column justify-content-center my-0">{{ $title }}</h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-8 my-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-400 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-gray-700">Panduan</li>
                            </ul>
                        </div>
                    </div>

                    <div class="app-navbar d-flex align-items-stretch flex-shrink-0">
                        <div class="app-navbar-item d-flex align-items-center ms-1 ms-md-3">
                            <x-ui.button :href="route('dashboard')" variant="light-primary" size="sm">
                                <x-ui.icon name="element-11" class="fs-6 me-1" />
                                Dashboard
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Wrapper: sidebar + main --}}
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">

                {{-- Sidebar — Metronic style --}}
                <aside id="kt_app_sidebar" class="app-sidebar bg-body d-flex flex-column panduan-sidebar">
                    <div class="app-sidebar-logo panduan-sidebar-logo px-5 d-flex align-items-center justify-content-between">
                        <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
                            <img src="{{ asset('images/brand/logo-pesantrenmu.svg') }}" alt="PesantrenMu" style="height: 28px;" />
                            <span class="fw-semibold fs-5 text-gray-900 d-none d-sm-inline">PesantrenMu</span>
                        </a>
                        <button class="btn btn-sm btn-icon btn-active-color-primary d-lg-none" id="kt_app_sidebar_close" aria-label="Tutup sidebar">
                            <i class="ki-duotone ki-cross fs-1">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </button>
                    </div>

                    <div class="px-5 pt-5 pb-4">
                        <div class="panduan-sidebar-panel">
                            <x-ui.badge variant="primary" class="mb-3">Panduan Sistem</x-ui.badge>
                            <div class="fw-semibold text-gray-900 fs-5 lh-sm">{{ $title }}</div>
                            <div class="text-gray-500 fs-8 mt-2">{{ count($sections) }} kelompok materi</div>
                        </div>
                    </div>

                    <div class="app-sidebar-menu overflow-hidden flex-column-fluid pb-6">
                        <div class="app-sidebar-wrapper hover-scroll-overlay-y">
                            <div class="menu menu-column menu-rounded menu-sub-indention menu-state-title-primary fw-semibold px-3"
                                 id="kt_panduan_menu">

                                @foreach ($sections as $section)
                                    @if (isset($section['children']))
                                        {{-- Accordion group --}}
                                        <div class="menu-item menu-accordion {{ in_array($currentSection, Arr::pluck($section['children'], 'id')) ? 'show' : '' }}"
                                             data-panduan-accordion="true">
                                            <span class="menu-link px-4">
                                                <span class="menu-icon">
                                                    <i class="ki-duotone {{ $section['icon'] ?? 'ki-book' }} fs-2">
                                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                                                    </i>
                                                </span>
                                                <span class="menu-title min-w-0">{{ $section['title'] }}</span>
                                                <span class="menu-arrow"></span>
                                            </span>
                                            <div class="menu-sub menu-sub-accordion">
                                                @foreach ($section['children'] as $child)
                                                    <div class="menu-item">
                                                        <a href="#{{ $child['id'] }}"
                                                           class="menu-link px-4 panduan-nav-link {{ $currentSection === $child['id'] ? 'active' : '' }}"
                                                           data-panduan-section="{{ $child['id'] }}">
                                                            <span class="menu-bullet">
                                                                <span class="bullet bullet-dot"></span>
                                                            </span>
                                                            <span class="menu-title min-w-0">{{ $child['title'] }}</span>
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <div class="menu-item">
                                            <a href="#{{ $section['id'] }}"
                                               class="menu-link px-4 panduan-nav-link {{ $currentSection === $section['id'] ? 'active' : '' }}"
                                               data-panduan-section="{{ $section['id'] }}">
                                                <span class="menu-icon">
                                                    <i class="ki-duotone {{ $section['icon'] ?? 'ki-book' }} fs-2">
                                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                                                    </i>
                                                </span>
                                                <span class="menu-title min-w-0">{{ $section['title'] }}</span>
                                            </a>
                                        </div>
                                    @endif
                                @endforeach

                            </div>
                        </div>
                    </div>
                </aside>

                {{-- Main content --}}
                <div class="app-main flex-column flex-row-fluid panduan-main" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">
                        <main id="kt_app_content" class="app-content flex-column-fluid">
                            <div id="kt_app_content_container" class="app-container container-fluid panduan-content-container">
                                <div class="panduan-hero card card-flush mb-5">
                                    <div class="card-body p-5">
                                        <div class="d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-4">
                                            <div class="min-w-0">
                                                <x-ui.badge variant="primary" class="mb-3">Dokumentasi Pengguna</x-ui.badge>
                                                <h2 class="fw-semibold text-gray-900 mb-3">{{ $title }}</h2>
                                                <p class="text-gray-600 fs-6 mb-0">Ikuti urutan materi dari menu kiri untuk memahami alur kerja sesuai peran pengguna.</p>
                                            </div>
                                            <div class="d-flex flex-wrap gap-3">
                                                <div class="panduan-hero-metric">
                                                    <span>{{ count($sections) }}</span>
                                                    <small>Kelompok</small>
                                                </div>
                                                <div class="panduan-hero-metric">
                                                    <span>
                                                        {{ collect($sections)->sum(fn ($section) => isset($section['children']) ? count($section['children']) : 1) }}
                                                    </span>
                                                    <small>Topik</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="panduan-content">
                                    {{ $slot }}
                                </div>
                            </div>
                        </main>
                    </div>

                    <footer class="app-footer">
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

    {{-- Scrollspy JS --}}
    <script>
        (function() {
            var current = '{{ $currentSection }}';

            function getActiveSection(scrollY) {
                var allSections = document.querySelectorAll('.panduan-section[id]');
                var best = null;
                allSections.forEach(function(el) {
                    if (el.offsetTop <= scrollY + 150) best = el;
                });
                return best ? best.id : null;
            }

            function highlight(id) {
                if (!id) return;
                document.querySelectorAll('.panduan-nav-link').forEach(function(a) {
                    a.classList.remove('active');
                });
                var link = document.querySelector('[data-panduan-section="' + id + '"]');
                if (link) {
                    link.classList.add('active');
                    var acc = link.closest('[data-panduan-accordion]');
                    if (acc && !acc.classList.contains('show')) acc.classList.add('show');
                }
            }

            window.addEventListener('scroll', function() {
                var id = getActiveSection(window.scrollY);
                if (id) highlight(id);
            }, { passive: true });

            // Accordion toggles
            document.querySelectorAll('[data-panduan-accordion] > .menu-link').forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    toggle.parentElement.classList.toggle('show');
                });
            });

            // Nav link smooth scroll
            document.querySelectorAll('.panduan-nav-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    var target = document.querySelector(link.getAttribute('href'));
                    if (!target) return;

                    // Close mobile sidebar
                    document.getElementById('kt_app_sidebar')?.classList.remove('is-open');
                    document.getElementById('kt_panduan_overlay')?.classList.add('d-none');
                    document.body.classList.remove('overflow-hidden');

                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            // Mobile sidebar toggle
            var toggleBtn = document.getElementById('kt_panduan_sidebar_toggle');
            var sidebar = document.getElementById('kt_app_sidebar');
            var overlay = document.getElementById('kt_panduan_overlay');
            var closeBtn = document.getElementById('kt_app_sidebar_close');

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('is-open');
                    if (overlay) overlay.classList.toggle('d-none');
                    document.body.classList.toggle('overflow-hidden');
                });
            }
            if (closeBtn && sidebar) {
                closeBtn.addEventListener('click', function() {
                    sidebar.classList.remove('is-open');
                    if (overlay) overlay.classList.add('d-none');
                    document.body.classList.remove('overflow-hidden');
                });
            }
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('is-open');
                    overlay.classList.add('d-none');
                    document.body.classList.remove('overflow-hidden');
                });
            }

            // Auto-close mobile sidebar on X resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992 && sidebar) {
                    sidebar.classList.remove('is-open');
                    if (overlay) overlay.classList.add('d-none');
                    document.body.classList.remove('overflow-hidden');
                }
            });

            highlight(current || getActiveSection(window.scrollY));
        })();
    </script>

</body>
</html>
