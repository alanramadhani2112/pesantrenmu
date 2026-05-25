@props([
    'pageTitle' => __('Dashboard'),
    'breadcrumbItems' => [],
    'currentUser' => auth()->user(),
    'roleName' => auth()->user()?->role?->name ?? 'user',
])

@php
    $initial = strtoupper(substr($currentUser?->name ?? 'U', 0, 1));
@endphp

<div id="kt_app_header" class="app-header spm-app-header">
    <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
        <div class="d-flex align-items-center flex-grow-1 min-w-0">
            <div class="d-flex align-items-center d-lg-none me-3" title="Buka menu">
                <x-ui.button
                    type="button"
                    id="kt_app_sidebar_mobile_toggle"
                    variant="light"
                    class="btn-icon btn-active-light-primary w-35px h-35px"
                    x-on:click="$store.sidebar.open = true"
                    aria-label="Buka menu"
                >
                    <x-ui.icon name="burger-menu" class="fs-2" />
                </x-ui.button>
            </div>

            <a href="{{ route('dashboard') }}" class="d-flex align-items-center d-lg-none me-4">
                <img src="{{ asset('images/brand/favicon.svg') }}" class="h-30px" alt="PesantrenMu" loading="eager" />
            </a>

            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3 min-w-0">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0 spm-header-title">
                    {{ $pageTitle }}
                </h1>

                <x-ui.breadcrumb :items="$breadcrumbItems" />
            </div>
        </div>

        <div class="app-navbar d-flex align-items-stretch flex-shrink-0" id="kt_app_header_navbar">
            <div class="app-navbar-item d-flex align-items-center ms-1 ms-md-3">
                <livewire:layout.notification-menu />
            </div>

            <div class="app-navbar-item d-flex align-items-center ms-1 ms-md-3" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false">
            <x-ui.button
                type="button"
                variant="light"
                class="btn-icon btn-active-light-primary w-40px h-40px p-0"
                x-on:click.stop="open = ! open"
                x-bind:aria-expanded="open.toString()"
                aria-controls="kt_app_header_user_menu"
                aria-haspopup="true"
                aria-label="Menu pengguna"
            >
                <span class="symbol symbol-35px">
                    <span class="symbol-label bg-light-primary text-primary fw-bold">{{ $initial }}</span>
                </span>
            </x-ui.button>

                <div
                    id="kt_app_header_user_menu"
                    x-cloak
                    x-show="open"
                    x-transition.opacity.duration.80ms
                    x-on:click="if ($event.target.closest('a, button')) open = false"
                    class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 fs-6 w-275px show position-absolute end-0 mt-2 spm-header-user-menu"
                    role="menu"
                    style="z-index: 1080;"
                >
                    <div class="menu-item px-3">
                        <div class="menu-content d-flex align-items-center px-3">
                            <div class="symbol symbol-50px me-5">
                                <span class="symbol-label bg-light-primary text-primary fw-bold fs-3">{{ $initial }}</span>
                            </div>

                            <div class="d-flex flex-column min-w-0">
                                <div class="fw-bold d-flex align-items-center fs-5 text-truncate">
                                    {{ $currentUser?->name }}
                                </div>
                                <span class="fw-semibold text-muted text-hover-primary fs-7 text-truncate">{{ $currentUser?->email }}</span>
                                <span class="badge badge-light-primary fw-bold fs-8 mt-2 align-self-start text-capitalize">{{ $roleName }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="separator my-2"></div>

                    <div class="menu-item px-5">
                        <a href="{{ route('profile') }}" class="menu-link px-5" role="menuitem">Pengaturan Profil</a>
                    </div>

                    <div class="menu-item px-5">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-ui.button type="submit" variant="link" unstyled class="menu-link px-5 border-0 bg-transparent w-100 text-start text-danger" role="menuitem">
                                Keluar
                            </x-ui.button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
