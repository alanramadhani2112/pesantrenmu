@props([
    'title' => 'Panduan',
    'sections' => [],
    'currentSection' => '',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} — {{ config('app.name', 'PesantrenMu') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}">

    @livewireStyles
    <link rel="stylesheet" href="{{ asset('vendor/metronic/assets/css/style.bundle.css') }}" media="print" onload="this.media='all'">
    @vite(['resources/css/app.css', 'resources/css/metronic-overrides.css', 'resources/js/app.js'])
    @livewireScriptConfig
</head>

<body class="app-default font-sans antialiased text-gray-900" data-bs-theme="light"
      x-data="{ sidebarOpen: true }"
      x-init="sidebarOpen = window.innerWidth >= 1200"
      x-on:resize.window="sidebarOpen = window.innerWidth >= 1200">

    {{-- Top navbar with mobile hamburger --}}
    <div class="d-flex align-items-center justify-content-between px-4 px-md-6 py-3 py-md-4 bg-white border-bottom">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-icon btn-active-light d-xl-none"
                    x-on:click="sidebarOpen = !sidebarOpen"
                    aria-label="Toggle sidebar">
                <i class="ki-duotone ki-burger-menu fs-2">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                </i>
            </button>
            <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
                <img src="{{ asset('images/brand/logo.svg') }}" alt="PesantrenMu" style="height: 32px;" />
                <span class="fw-bold fs-4 text-gray-900 d-none d-sm-inline">PesantrenMu</span>
            </a>
        </div>
        <span class="badge badge-light-primary fs-6 px-3 px-md-4 py-2">{{ $title }}</span>
    </div>

    <div class="d-flex flex-column flex-xl-row gap-0 gap-xl-6 p-0 p-xl-10">
        {{-- Mobile overlay --}}
        <div class="d-xl-none position-fixed top-0 start-0 w-100 h-100 bg-black bg-opacity-50"
             style="z-index: 1040;"
             x-show="sidebarOpen"
             x-on:click="sidebarOpen = false"
             x-transition.opacity.duration.200ms
             x-cloak></div>

        {{-- Sidebar Navigation --}}
        <div class="flex-shrink-0 position-fixed position-xl-relative top-0 start-0 h-100 h-xl-auto overflow-auto overflow-xl-visible bg-body shadow d-xl-block"
             style="width: 280px; z-index: 1050;"
             x-show="sidebarOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             x-cloak>
            <div class="d-flex align-items-center justify-content-between px-5 pt-6 pb-4 d-xl-none">
                <span class="fw-bold fs-5 text-gray-900">{{ $title }}</span>
                <button class="btn btn-icon btn-active-light"
                        x-on:click="sidebarOpen = false"
                        aria-label="Close sidebar">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
            </div>
            <div class="card card-flush border-0 shadow-none sticky-xl-top" style="top: 20px; z-index: 1;">
                <div class="card-header pt-4 pt-xl-6 px-5 d-none d-xl-block">
                    <h3 class="card-title fw-bold fs-5 text-gray-900">{{ $title }}</h3>
                </div>
                <div class="card-body pt-0 px-0">
                    <div class="menu menu-column menu-fit menu-state-bg menu-state-title-primary menu-rounded">
                        @foreach ($sections as $section)
                            @if (isset($section['children']))
                                {{-- Accordion group --}}
                                <div class="menu-item menu-accordion"
                                     x-data="{ open: @js(in_array($currentSection, Arr::pluck($section['children'], 'id'))) }"
                                     x-bind:class="{ 'show': open }">
                                    <span class="menu-link px-5"
                                          x-on:click="open = !open"
                                          role="button">
                                        <span class="menu-icon">
                                            @if (isset($section['icon']))
                                                <i class="ki-duotone {{ $section['icon'] }} fs-2">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                            @endif
                                        </span>
                                        <span class="menu-title fw-semibold">{{ $section['title'] }}</span>
                                        <span class="menu-arrow"></span>
                                    </span>
                                    <div class="menu-sub menu-sub-accordion" x-show="open" x-transition.opacity.duration.150ms x-cloak>
                                        @foreach ($section['children'] as $child)
                                            <a href="#{{ $child['id'] }}"
                                               class="menu-link px-7 py-2 scroll-to-section
                                                      {{ $currentSection === $child['id'] ? 'active' : '' }}"
                                               data-section="{{ $child['id'] }}"
                                               x-on:click="sidebarOpen = (window.innerWidth >= 1200)">
                                                <span class="menu-bullet">
                                                    <span class="bullet bullet-dot"></span>
                                                </span>
                                                <span class="menu-title">{{ $child['title'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="menu-item">
                                    <a href="#{{ $section['id'] }}"
                                       class="menu-link px-5 scroll-to-section
                                              {{ $currentSection === $section['id'] ? 'active' : '' }}"
                                       data-section="{{ $section['id'] }}"
                                       x-on:click="sidebarOpen = (window.innerWidth >= 1200)">
                                        <span class="menu-icon">
                                            <i class="ki-duotone {{ $section['icon'] ?? 'ki-duotone ki-book' }} fs-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title fw-semibold">{{ $section['title'] }}</span>
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Content Area --}}
        <div class="flex-grow-1 min-w-0 px-4 px-md-6 px-xl-0 pb-10 pt-6 pt-xl-0">
            {{ $slot }}

            {{-- Footer --}}
            <div class="text-center text-muted mt-10 pt-6 border-top">
                <small>&copy; {{ date('Y') }} PesantrenMu — Panduan Sistem Akreditasi Mutu</small>
            </div>
        </div>
    </div>

    @livewireScripts

    <script>
        // Scroll-to-section handler with sidebar close on mobile
        document.addEventListener('click', function(e) {
            const link = e.target.closest('.scroll-to-section');
            if (!link) return;

            e.preventDefault();
            const id = link.getAttribute('data-section');
            const target = document.getElementById(id);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Update URL hash without jump
                history.replaceState(null, '', '#' + id);
            }
        });

        // Highlight current section on scroll
        (function() {
            const links = document.querySelectorAll('.scroll-to-section');
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        links.forEach(function(l) {
                            l.classList.toggle('active', l.getAttribute('data-section') === id);
                        });
                    }
                });
            }, { rootMargin: '-80px 0px -70% 0px' });

            document.querySelectorAll('[id]').forEach(function(el) {
                const hasLink = document.querySelector('.scroll-to-section[data-section="' + el.id + '"]');
                if (hasLink) observer.observe(el);
            });
        })();
    </script>
</body>
</html>
