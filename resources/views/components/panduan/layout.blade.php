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

<body class="app-default font-sans antialiased text-gray-900" data-bs-theme="light" x-data>

    {{-- Top navbar (mirror app layout) --}}
    <div class="d-flex align-items-center justify-content-between px-6 py-4 bg-white border-bottom">
        <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
            <img src="{{ asset('images/brand/logo.svg') }}" alt="PesantrenMu" style="height: 32px;" />
            <span class="fw-bold fs-4 text-gray-900">PesantrenMu</span>
        </a>
        <span class="badge badge-light-primary fs-6 px-4 py-2">{{ $title }}</span>
    </div>

    <div class="d-flex flex-column flex-xl-row gap-6 p-6 p-xl-10">
        {{-- Sidebar Navigation --}}
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card card-flush sticky-xl-top" style="top: 20px; z-index: 1;">
                <div class="card-header pt-6 px-5">
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
                                            <i class="ki-duotone {{ $section['icon'] ?? 'ki-duotone ki-book' }} fs-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title fw-semibold">{{ $section['title'] }}</span>
                                        <span class="menu-arrow"></span>
                                    </span>
                                    <div class="menu-sub menu-sub-accordion" x-show="open" x-transition.opacity.duration.150ms x-cloak>
                                        @foreach ($section['children'] as $child)
                                            <a href="#{{ $child['id'] }}"
                                               class="menu-link px-7 py-2 scroll-to-section
                                                      {{ $currentSection === $child['id'] ? 'active' : '' }}"
                                               data-section="{{ $child['id'] }}">
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
                                       data-section="{{ $section['id'] }}">
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
        <div class="flex-grow-1 min-w-0">
            <div class="panduan-content">
                {{ $slot }}
            </div>
        </div>
    </div>

    <div class="text-center py-4 text-gray-500 fs-7">
        &copy; {{ date('Y') }} PesantrenMu
    </div>

    <script>
        document.querySelectorAll('.scroll-to-section').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>

</html>
