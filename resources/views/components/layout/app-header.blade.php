@props([
    'pageTitle' => __('Dashboard'),
    'breadcrumbItems' => [],
    'currentUser' => auth()->user(),
    'roleName' => auth()->user()?->role?->name ?? 'user',
])

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
                <h1 class="page-heading d-flex text-gray-900 fw-semibold fs-3 flex-column justify-content-center my-0 spm-header-title">
                    {{ $pageTitle }}
                </h1>

                <x-ui.breadcrumb :items="$breadcrumbItems" />
            </div>
        </div>

        <div class="app-navbar d-flex align-items-stretch flex-shrink-0" id="kt_app_header_navbar">
            <div class="app-navbar-item d-flex align-items-center ms-1 ms-md-3">
                <livewire:layout.notification-menu />
            </div>
        </div>
    </div>
</div>
