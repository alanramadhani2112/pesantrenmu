@props([
    'currentUser' => auth()->user(),
    'roleName' => auth()->user()?->role?->name ?? 'user',
])

@php
    $isAdmin = $currentUser?->isAdmin() ?? false;
    $isPesantren = $currentUser?->isPesantren() ?? false;
    $isAsesor = $currentUser?->isAsesor() ?? false;
    $initial = strtoupper(substr($currentUser?->name ?? 'U', 0, 1));

    // Get menu configuration from SidebarMenuService
    $menuService = app(\App\Services\SidebarMenuService::class);
    $sections = $menuService->getMenuForRole($currentUser?->role_id ?? 0);

    // Get progress data for Pesantren users
    $progressData = [];
    if ($isPesantren && $currentUser) {
        try {
            $progressService = app(\App\Services\SidebarProgressService::class);
            $progressData = $progressService->getProgressForUser($currentUser->id);
        } catch (\Throwable $e) {
            $progressData = [];
        }
    }

    // Map progress keys to menu item keys
    $progressKeyMap = [
        'profil_pesantren' => 'profil',
        'ipm' => 'ipm',
        'data_sdm' => 'sdm',
        'edpm_ipr' => 'edpm',
    ];

    // Compute badge counts for items with show_badge=true
    $badgeCounts = [];
    if ($isAdmin || $isAsesor) {
        try {
            if ($isAdmin) {
                $badgeCounts['akreditasi_admin'] = \App\Models\Akreditasi::where('status', 6)->count();
                $badgeCounts['banding'] = \App\Models\Banding::where('status', 'pending')->count();
                $badgeCounts['failed_notifications'] = \App\Models\FailedNotification::where('status', 'pending')->count();
                $badgeCounts['trash'] = \App\Models\Akreditasi::onlyTrashed()->count();
            } elseif ($isAsesor) {
                $asesor = $currentUser->asesor;
                if ($asesor) {
                    $badgeCounts['daftar_tugas'] = \App\Models\Akreditasi::whereIn('status', [4, 5])
                        ->whereHas('assessments', function ($query) use ($asesor) {
                            $query->where('asesor_id', $asesor->id);
                        })
                        ->count();
                }
            }
        } catch (\Throwable $e) {
            $badgeCounts = [];
        }
    }
@endphp

<div class="spm-sidebar-host" style="display: contents;">
    <div
        x-cloak
        x-show="$store.sidebar.open"
        x-transition.opacity.duration.150ms
        class="spm-sidebar-backdrop d-lg-none"
        x-on:click="$store.sidebar.open = false"
    ></div>

    <div
        id="kt_app_sidebar"
        data-ui-sidebar="metronic"
        class="app-sidebar flex-column spm-app-sidebar"
        x-bind:class="{ 'spm-drawer-open': $store.sidebar.open }"
    >
    <div class="app-sidebar-logo flex-shrink-0 d-flex align-items-center justify-content-between px-8" id="kt_app_sidebar_logo">
        <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-3 min-w-0">
            <span class="spm-sidebar-logo-mark d-flex align-items-center justify-content-center p-0 border-0 bg-transparent">
                <img src="{{ asset('images/brand/favicon.svg') }}" class="w-30px h-30px" alt="PesantrenMu" loading="eager" />
            </span>
            <span class="spm-sidebar-brand-title">PesantrenMu</span>
        </a>

        <x-ui.button
            type="button"
            variant="light"
            size="sm"
            class="btn-icon btn-active-color-primary d-lg-none"
            x-on:click="$store.sidebar.open = false"
            aria-label="Tutup menu"
        >
            <x-ui.icon name="cross-circle" class="fs-2" />
        </x-ui.button>
    </div>

    <div class="app-sidebar-menu flex-column-fluid">
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-y">
            <div class="menu menu-column menu-rounded menu-sub-indention menu-state-title-primary fw-semibold px-3" id="kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">

                @foreach($sections as $section)
                    <x-sidebar-section :label="$section['label']" />

                    @foreach($section['items'] as $item)
                        @php
                            $isComingSoon = (bool) ($item['coming_soon'] ?? false);
                            $badgeText = $item['badge_text'] ?? null;

                            // Determine the href (skip route resolution for Soon items).
                            if ($isComingSoon) {
                                $href = '#';
                                $isActive = false;
                            } else {
                                $routeParams = $item['route_params'] ?? [];
                                $routeQuery = $item['route_query'] ?? [];
                                $href = route($item['route'], $routeParams);
                                if (! empty($routeQuery)) {
                                    $href .= '?' . http_build_query($routeQuery);
                                }

                                $activePattern = $item['active_pattern'];
                                if (str_contains($activePattern, 'documents.index.')) {
                                    $routeMatches = request()->fullUrlIs($href);
                                } elseif (str_ends_with($activePattern, '*')) {
                                    $routeMatches = request()->routeIs($activePattern);
                                } else {
                                    $routeMatches = request()->routeIs($activePattern);
                                }

                                if (isset($item['active_query'])) {
                                    $isActive = $routeMatches;
                                    foreach ($item['active_query'] as $queryKey => $queryValue) {
                                        if ((string) request()->query($queryKey, '') !== (string) $queryValue) {
                                            $isActive = false;
                                            break;
                                        }
                                    }
                                } elseif (isset($item['active_query_absent'])) {
                                    $isActive = $routeMatches;
                                    foreach ($item['active_query_absent'] as $queryKey) {
                                        if (request()->query->has($queryKey)) {
                                            $isActive = false;
                                            break;
                                        }
                                    }
                                } else {
                                    $isActive = $routeMatches;
                                }
                            }

                            // Determine progress status (only for Pesantren items with show_progress)
                            $progressStatus = null;
                            if (($item['show_progress'] ?? false) && $isPesantren) {
                                $progressKey = $progressKeyMap[$item['key']] ?? null;
                                if ($progressKey) {
                                    $progressStatus = $progressData[$progressKey] ?? null;
                                }
                            }

                            // Badge count for items with show_badge=true
                            $badgeCount = null;
                            if ($item['show_badge'] ?? false) {
                                $badgeCount = $badgeCounts[$item['key']] ?? null;
                            }
                        @endphp

                        <x-sidebar-link
                            :href="$href"
                            :active="$isActive"
                            :icon="$item['icon']"
                            :progressStatus="$progressStatus"
                            :badgeCount="$badgeCount"
                            :tooltip="$item['tooltip']"
                            :badgeText="$badgeText"
                            :disabled="$isComingSoon"
                        >
                            {{ $item['label'] }}
                        </x-sidebar-link>
                    @endforeach
                @endforeach

            </div>
        </div>
    </div>

    <div class="app-sidebar-footer flex-column-auto px-6 py-6" id="kt_app_sidebar_footer" data-ui-sidebar-user-menu="metronic">
        <a href="{{ route('profile') }}" class="d-flex align-items-center text-decoration-none rounded px-3 py-3 bg-light-primary bg-hover-light spm-sidebar-user-card">
            <span class="symbol symbol-40px me-3">
                <span class="symbol-label bg-primary text-inverse-primary fw-bold">{{ $initial }}</span>
            </span>

            <span class="d-flex flex-column min-w-0 flex-grow-1">
                <span class="fw-bold text-gray-800 fs-7 text-truncate">{{ $currentUser?->name }}</span>
                <span class="badge badge-light-primary fw-bold fs-9 mt-1 align-self-start text-capitalize">{{ $roleName }}</span>
            </span>
        </a>
    </div>
    </div>

    {{-- SidebarBadges Livewire component for dynamic badge count updates --}}
    <livewire:layout.sidebar-badges />
</div>
