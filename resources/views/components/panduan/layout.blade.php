@props([
    'title' => 'Panduan',
    'sections' => [],
    'currentSection' => '',
])

<x-app-layout>
    <x-slot name="header">{{ $title }}</x-slot>

    <div class="d-flex flex-column flex-xl-row gap-6">
        {{-- Sidebar Navigation --}}
        <div class="flex-shrink-0" style="width: 260px;">
            <div class="card card-flush sticky-xl-top" style="top: 86px; z-index: 1;">
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

    @push('scripts')
    <script>
        // Smooth scroll to section on sidebar click
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
    @endpush
</x-app-layout>
