@props([
    'pageTitle' => __('Dashboard'),
    'breadcrumbItems' => [],
    'currentUser' => auth()->user(),
    'roleName' => auth()->user()?->role?->name ?? 'user',
])

@php
    $photoPath = $currentUser?->profile_photo_path;
    $photoUrl = $photoPath ? asset('storage/' . $photoPath) : '';
    $userName = $currentUser?->name ?? 'User';
    $parts = explode(' ', trim($userName));
    $initials = count($parts) > 1
        ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
        : strtoupper(substr($parts[0], 0, 2));
@endphp

<div id="kt_app_header" class="app-header spm-app-header"
    x-data="{ avatarUrl: '{{ $photoUrl }}' }"
    x-on:profile-photo-updated.window="
        if ($event.detail && $event.detail.photoUrl !== undefined) {
            avatarUrl = $event.detail.photoUrl ? $event.detail.photoUrl + '?t=' + Date.now() : '';
        }
    ">
    <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
        <div class="d-flex align-items-center flex-grow-1 min-w-0">
            <div class="d-flex align-items-center d-lg-none me-3" title="Buka menu">
                <x-ui.button
                    type="button"
                    id="kt_app_sidebar_mobile_toggle"
                    variant="light"
                    class="btn-icon btn-active-light-primary w-35px h-35px"

                    aria-label="Buka menu"
                >
                    <x-ui.icon name="burger-menu" class="fs-2" />
                </x-ui.button>
            </div>

            <a href="{{ route('dashboard') }}" class="d-flex align-items-center d-lg-none me-4">
                <img src="{{ asset('images/brand/favicon.svg') }}" class="h-30px" alt="PesantrenMu" loading="eager" />
            </a>

            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3 min-w-0">
                <h1 class="page-heading d-flex text-gray-900 fw-semibold fs-3 flex-column justify-content-center my-0 spm-header-title">
                    {{ $pageTitle }}
                </h1>

                <x-ui.breadcrumb :items="$breadcrumbItems" />
            </div>
        </div>

        <div class="app-navbar d-flex align-items-stretch flex-shrink-0" id="kt_app_header_navbar">
            <div class="app-navbar-item d-flex align-items-center ms-1 ms-md-3">
                @include('components.layout.notification-menu')
            </div>

            {{-- User Avatar + Dropdown --}}
            <div class="app-navbar-item d-flex align-items-center ms-2 ms-md-4">
                <div class="d-flex align-items-center cursor-pointer" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                    <div class="symbol symbol-35px symbol-circle me-2">
                        <template x-if="avatarUrl">
                            <img :src="avatarUrl" alt="{{ $userName }}" class="w-35px h-35px object-fit-cover rounded-circle" />
                        </template>
                        <template x-if="!avatarUrl">
                            <span class="symbol-label bg-light-primary text-primary fw-semibold fs-6">
                                {{ $initials }}
                            </span>
                        </template>
                    </div>
                    <div class="d-none d-lg-flex flex-column me-2">
                        <span class="fw-semibold text-gray-900 fs-7 lh-1">{{ $userName }}</span>
                        <span class="text-muted fs-8">{{ ucfirst($roleName) }}</span>
                    </div>
                    <i class="ki-solid ki-down fs-8 text-gray-600 d-none d-lg-block"></i>
                </div>

                {{-- Dropdown Menu --}}
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 w-250px fs-6" data-kt-menu="true">
                    <div class="menu-item px-3">
                        <a href="{{ route('profile.edit') }}" class="menu-link px-3">
                            <span class="menu-icon">
                                <i class="ki-solid ki-setting-2 fs-2"></i>
                            </span>
                            <span class="menu-title">Pengaturan Profil</span>
                        </a>
                    </div>

                    <div class="separator my-2"></div>

                    <div class="menu-item px-3">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="menu-link px-3 border-0 bg-transparent w-100 text-start">
                                <span class="menu-icon">
                                    <i class="ki-solid ki-exit-right fs-2"></i>
                                </span>
                                <span class="menu-title">Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
