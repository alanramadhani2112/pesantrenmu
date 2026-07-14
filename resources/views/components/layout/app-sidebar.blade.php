@props([
    'currentUser' => auth()->user(),
    'roleName' => auth()->user()?->role?->name ?? 'user',
])

@php
    $isAdmin = $currentUser?->isAdmin() ?? false;
    $isPesantren = $currentUser?->isPesantren() ?? false;
    $isAsesor = $currentUser?->isAsesor() ?? false;
    $canAccessAdminArea = $currentUser?->canAccessAdminArea() ?? false;
    $initial = strtoupper(substr($currentUser?->name ?? 'U', 0, 1));

    // Get menu configuration from SidebarMenuService
    $menuService = app(\App\Services\SidebarMenuService::class);
    $sections = $menuService->getMenuForRole($currentUser?->role_id ?? 0);

    // Compute badge counts for items with show_badge=true
    $badgeCounts = [];
    if ($canAccessAdminArea || $isAsesor) {
        try {
            if ($canAccessAdminArea) {
                $badgeCounts['akreditasi_admin'] = \Illuminate\Support\Facades\Cache::remember('badge:admin:pending_akreditasi', 30, fn () => \App\Models\Akreditasi::where('status', 6)->count());
                $badgeCounts['banding'] = \Illuminate\Support\Facades\Cache::remember('badge:admin:pending_banding', 30, fn () => \App\Models\Banding::where('status', 'pending')->count());
                $badgeCounts['failed_notifications'] = \Illuminate\Support\Facades\Cache::remember('badge:admin:failed_notifications', 30, fn () => \App\Models\FailedNotification::where('status', 'pending')->count());
                $badgeCounts['trash'] = \Illuminate\Support\Facades\Cache::remember('badge:admin:trash', 30, fn () => \App\Models\Akreditasi::onlyTrashed()->count());
            } elseif ($isAsesor) {
                $asesor = $currentUser->asesor;
                if ($asesor) {
                    $badgeCounts['daftar_tugas'] = \Illuminate\Support\Facades\Cache::remember(
                        'badge:asesor:' . $asesor->id . ':active_tasks',
                        30,
                        fn () => \App\Models\Akreditasi::whereIn('status', [4, 5])
                            ->whereHas('assessments', function ($query) use ($asesor) {
                                $query->where('asesor_id', $asesor->id);
                            })
                            ->count()
                    );
                }
            }
        } catch (\Throwable $e) {
            $badgeCounts = [];
        }
    }
@endphp

<div class="spm-sidebar-host" style="display: contents;">
    <div
        id="kt_app_sidebar"
        data-ui-sidebar="metronic"
        data-kt-drawer="true"
        data-kt-drawer-name="app-sidebar"
        data-kt-drawer-activate="{default: true, lg: false}"
        data-kt-drawer-overlay="true"
        data-kt-drawer-width="280px"
        data-kt-drawer-direction="start"
        data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle"
        class="app-sidebar flex-column spm-app-sidebar"
    >
    <div class="app-sidebar-logo flex-shrink-0 d-flex align-items-center justify-content-between px-8" id="kt_app_sidebar_logo">
        <a href="{{ route('dashboard') }}" class="app-sidebar-logo-link d-flex align-items-center gap-3 min-w-0">
            <span class="spm-sidebar-logo-mark d-flex align-items-center justify-content-center p-0 border-0 bg-transparent">
                <img src="{{ asset('images/brand/favicon.svg') }}" class="w-30px h-30px" alt="PesantrenMu" loading="eager" />
            </span>
            <span class="spm-sidebar-brand-title">PesantrenMu</span>
        </a>

        <button
            type="button"
            class="btn btn-icon btn-active-color-primary w-30px h-30px d-lg-none spm-sidebar-mobile-dismiss"
            data-kt-drawer-dismiss="true"
            aria-label="Tutup menu"
        >
            <i class="ki-duotone ki-cross-circle fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </button>
    </div>

    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <div
            id="kt_app_sidebar_menu_wrapper"
            class="app-sidebar-wrapper hover-scroll-overlay-y my-5"
            data-kt-scroll="true"
            data-kt-scroll-activate="true"
            data-kt-scroll-height="auto"
            data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
            data-kt-scroll-wrappers="#kt_app_sidebar_menu"
            data-kt-scroll-offset="5px"
        >
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
                            :badgeCount="$badgeCount"
                            :tooltip="$item['tooltip']"
                            :badgeText="$badgeText"
                            :disabled="$isComingSoon"
                        >
                            {{ $item['label'] }}
                        </x-sidebar-link>
                    @endforeach
                @endforeach

                {{-- Panduan link (role-specific) --}}
                @php
                    $panduanRoutes = [
                        'super_admin' => 'panduan.superadmin',
                        'admin' => 'panduan.admin',
                        'asesor' => 'panduan.asesor',
                        'pesantren' => 'panduan.pesantren',
                    ];
                    $panduanRoute = $panduanRoutes[$roleName] ?? null;
                @endphp
                @if($panduanRoute)
                    <div class="pt-4 mt-4 border-top">
                        <x-sidebar-link
                            :href="route($panduanRoute)"
                            :active="request()->routeIs('panduan.*')"
                            icon="document"
                        >Panduan</x-sidebar-link>
                    </div>
                @endif

            </div>
        </div>
    </div>

    </div>
</div>
