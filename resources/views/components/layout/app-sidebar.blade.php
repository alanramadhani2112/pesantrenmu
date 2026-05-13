@props([
    'currentUser' => auth()->user(),
    'roleName' => auth()->user()?->role?->name ?? 'user',
])

@php
    $isAdmin = $currentUser?->isAdmin() ?? false;
    $isPesantren = $currentUser?->isPesantren() ?? false;
    $isAsesor = $currentUser?->isAsesor() ?? false;
    $initial = strtoupper(substr($currentUser?->name ?? 'U', 0, 1));
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
                <img src="{{ asset('images/brand/favicon.svg') }}" class="w-30px h-30px" alt="PesantrenMu" />
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
                <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="grid">
                    {{ __('Dashboards') }}
                </x-sidebar-link>

                @if ($isAdmin)
                    <x-ui.sidebar-section>ADMINISTRASI</x-ui.sidebar-section>

                    <x-sidebar-link :href="route('admin.akreditasi')" :active="request()->routeIs('admin.akreditasi*')" icon="shield">
                        {{ __('Akreditasi') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('admin.pesantren.index')" :active="request()->routeIs('admin.pesantren.*')" icon="users">
                        {{ __('Pesantren') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('admin.asesor.index')" :active="request()->routeIs('admin.asesor.*')" icon="user-circle">
                        {{ __('Asesor') }}
                    </x-sidebar-link>

                    <x-ui.sidebar-section :compact="true">MASTER DATA</x-ui.sidebar-section>

                    <x-layout.sidebar-group
                        title="Referensi Data"
                        icon="data"
                        :open="request()->routeIs('admin.master-edpm') || request()->routeIs('admin.master-dokumen')"
                    >
                        <x-sidebar-link :href="route('admin.master-edpm')" :active="request()->routeIs('admin.master-edpm')" icon="none">
                            {{ __('Komponen') }}
                        </x-sidebar-link>

                        <x-sidebar-link :href="route('admin.master-dokumen')" :active="request()->routeIs('admin.master-dokumen')" icon="none">
                            {{ __('Dokumen') }}
                        </x-sidebar-link>
                    </x-layout.sidebar-group>

                    <x-layout.sidebar-group
                        title="Manajemen"
                        icon="setting-2"
                        :open="request()->routeIs('roles.*') || request()->routeIs('accounts.*')"
                    >
                        <x-sidebar-link :href="route('roles.index')" :active="request()->routeIs('roles.*')" icon="none">
                            {{ __('Role') }}
                        </x-sidebar-link>

                        <x-sidebar-link :href="route('accounts.index')" :active="request()->routeIs('accounts.*')" icon="none">
                            {{ __('Accounts') }}
                        </x-sidebar-link>
                    </x-layout.sidebar-group>
                @endif

                @if ($isPesantren)
                    <x-sidebar-link :href="route('pesantren.profile')" :active="request()->routeIs('pesantren.profile')" icon="hat">
                        {{ __('Profil Pesantren') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('pesantren.ipm')" :active="request()->routeIs('pesantren.ipm')" icon="document">
                        {{ __('IPM') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('pesantren.sdm')" :active="request()->routeIs('pesantren.sdm')" icon="users">
                        {{ __('Data SDM') }}
                    </x-sidebar-link>

                    <x-ui.sidebar-section>AKREDITASI</x-ui.sidebar-section>

                    <x-sidebar-link :href="route('pesantren.edpm')" :active="request()->routeIs('pesantren.edpm')" icon="paper">
                        {{ __('EDPM') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('pesantren.akreditasi')" :active="request()->routeIs('pesantren.akreditasi*')" icon="shield-lock">
                        {{ __('Akreditasi') }}
                    </x-sidebar-link>

                    <x-ui.sidebar-section>DOKUMEN</x-ui.sidebar-section>

                    <x-sidebar-link :href="route('documents.index', ['doc' => 'iapm'])" :active="request()->fullUrlIs(route('documents.index', ['doc' => 'iapm']))" icon="document-stack">
                        {{ __('IAPM') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('documents.index', ['doc' => 'kartu_kendali'])" :active="request()->fullUrlIs(route('documents.index', ['doc' => 'kartu_kendali']))" icon="document-stack">
                        {{ __('Kartu Kendali') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('documents.index', ['doc' => 'all'])" :active="request()->fullUrlIs(route('documents.index', ['doc' => 'all']))" icon="document-stack">
                        {{ __('Daftar Dokumen') }}
                    </x-sidebar-link>
                @endif

                @if ($isAsesor)
                    <x-sidebar-link :href="route('asesor.profile')" :active="request()->routeIs('asesor.profile')" icon="users">
                        {{ __('Profil Asesor') }}
                    </x-sidebar-link>

                    <x-ui.sidebar-section>AKREDITASI</x-ui.sidebar-section>

                    <x-sidebar-link :href="route('asesor.akreditasi')" :active="request()->routeIs('asesor.akreditasi*')" icon="shield-lock">
                        {{ __('Akreditasi') }}
                    </x-sidebar-link>

                    <x-ui.sidebar-section>DOKUMEN</x-ui.sidebar-section>

                    <x-sidebar-link :href="route('documents.index', ['doc' => 'iapm'])" :active="request()->fullUrlIs(route('documents.index', ['doc' => 'iapm']))" icon="document-stack">
                        {{ __('IAPM') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('documents.index', ['doc' => 'visitasi'])" :active="request()->fullUrlIs(route('documents.index', ['doc' => 'visitasi']))" icon="document-stack">
                        {{ __('Visitasi') }}
                    </x-sidebar-link>
                @endif
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
</div>
