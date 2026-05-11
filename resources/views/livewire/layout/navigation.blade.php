<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    public function getIapmDocProperty()
    {
        if (!auth()->user()->isPesantren()) return null;
        return auth()->user()->documents->first(fn($d) => str_contains(strtolower($d->title), 'iapm'));
    }

    public function getKendaliDocProperty()
    {
        if (!auth()->user()->isPesantren()) return null;
        return auth()->user()->documents->first(fn($d) => str_contains(strtolower($d->title), 'kendali'));
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $currentUser = auth()->user();
    $roleName = $currentUser->role?->name ?? 'user';
@endphp

<div class="h-full flex-shrink-0" data-ui-sidebar="metronic">
    <!-- Desktop Sidebar -->
    <aside class="hidden lg:flex lg:flex-shrink-0 h-full">
        <div class="spm-sidebar flex flex-col w-64 bg-white h-full">
            <!-- Logo Section -->
            <div class="spm-sidebar-brand flex items-center px-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="spm-sidebar-logo-mark flex items-center justify-center">
                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 7C9.23858 7 7 9.23858 7 12C7 14.7614 9.23858 17 12 17C14.7614 17 17 14.7614 17 12C17 9.23858 14.7614 7 12 7Z" stroke="currentColor" stroke-width="1.5" />
                            <path d="M12 2V4M12 20V22M4 12H2M22 12H20M5.63605 5.63605L7.05026 7.05026M16.9497 16.9497L18.364 18.364M5.63605 18.364L7.05026 16.9497M16.9497 7.05026L18.364 5.63605" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M12 8.5V15.5M8.5 12H15.5" stroke="currentColor" stroke-width="1" />
                        </svg>
                    </div>
                    <span class="spm-sidebar-brand-title">PesantrenMu</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 flex flex-col pt-4 overflow-y-auto">
                <nav class="spm-sidebar-nav flex-1 px-4">
                    <!-- General Menu -->
                    <div class="space-y-1">
                        <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="grid">
                            {{ __('Dashboards') }}
                        </x-sidebar-link>
                    </div>

                    <!-- Admin Menu -->
                    @php
                    $isAdmin = auth()->user()->isAdmin();
                    @endphp

                    @if ($isAdmin)
                    <x-ui.sidebar-section>ADMINISTRASI</x-ui.sidebar-section>
                    <!-- existing admin links ... -->
                    <div x-data="{ 
                        openMaster: @json(request()->routeIs('admin.master-edpm') || request()->routeIs('admin.master-dokumen')), 
                        openManajemen: @json(request()->routeIs('roles.*') || request()->routeIs('accounts.*')) 
                    }" class="space-y-1">
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

                        <!-- Referensi Data Group -->
                        <div class="space-y-1">
                            <button @click="openMaster = !openMaster" class="spm-sidebar-group-toggle group flex items-center justify-between w-full">
                                <div class="flex items-center min-w-0">
                                    <span class="spm-sidebar-icon spm-sidebar-group-icon">
                                        <x-ui.icon name="data" class="fs-2" />
                                    </span>
                                    <span class="spm-sidebar-title truncate">Referensi Data</span>
                                </div>
                                <x-ui.icon name="down" class="spm-sidebar-group-caret fs-7" x-bind:class="{ 'rotate-180': openMaster }" />
                            </button>
                            <div x-show="openMaster" x-transition x-cloak class="space-y-1 ml-8">
                                <x-sidebar-link :href="route('admin.master-edpm')" :active="request()->routeIs('admin.master-edpm')" icon="none" class="!bg-transparent">
                                    {{ __('Komponen') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('admin.master-dokumen')" :active="request()->routeIs('admin.master-dokumen')" icon="none" class="!bg-transparent">
                                    {{ __('Dokumen') }}
                                </x-sidebar-link>
                            </div>
                        </div>

                        <!-- Manajemen Group -->
                        <div class="space-y-1">
                            <button @click="openManajemen = !openManajemen" class="spm-sidebar-group-toggle group flex items-center justify-between w-full">
                                <div class="flex items-center min-w-0">
                                    <span class="spm-sidebar-icon spm-sidebar-group-icon">
                                        <x-ui.icon name="setting-2" class="fs-2" />
                                    </span>
                                    <span class="spm-sidebar-title truncate">Manajemen</span>
                                </div>
                                <x-ui.icon name="down" class="spm-sidebar-group-caret fs-7" x-bind:class="{ 'rotate-180': openManajemen }" />
                            </button>
                            <div x-show="openManajemen" x-transition x-cloak class="space-y-1 ml-8">
                                <x-sidebar-link :href="route('roles.index')" :active="request()->routeIs('roles.*')" icon="none" class="!bg-transparent">
                                    {{ __('Role') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('accounts.index')" :active="request()->routeIs('accounts.*')" icon="none" class="!bg-transparent">
                                    {{ __('Accounts') }}
                                </x-sidebar-link>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Pesantren Menu -->
                    @if (auth()->user()->isPesantren())
                    <div class="space-y-1">
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
                    </div>
                    @endif

                    <!-- Asesor Menu -->
                    @if (auth()->user()->isAsesor())
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
                </nav>
            </div>

            <!-- Sidebar Footer User Profile (Fixed Bottom) -->
            <div class="spm-sidebar-footer mt-auto flex-shrink-0 p-4" x-data="{ userOpen: false }">
                <div class="relative">
                    <button
                        @mouseenter="userOpen = true"
                        @mouseleave="userOpen = false"
                        @click="userOpen = !userOpen"
                        class="flex group w-full items-center focus:outline-none overflow-hidden rounded-lg p-1 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center">
                            <div class="h-10 w-10 spm-sidebar-avatar flex items-center justify-center text-white font-bold overflow-hidden text-lg uppercase flex-shrink-0">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="ml-3 text-left">
                                <p class="spm-sidebar-sign-label mb-1">Masuk sebagai</p>
                                <p class="text-xs font-bold text-gray-800 truncate w-32 leading-none">{{ auth()->user()->name }}</p>
                            </div>
                        </div>
                    </button>

                    <!-- Profile Dropdown Card (Hover/Click) -->
                    <div
                        x-show="userOpen"
                        @mouseenter="userOpen = true"
                        @mouseleave="userOpen = false"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-4"
                        class="spm-sidebar-user-popover absolute bottom-full left-0 mb-4 w-72 bg-white overflow-hidden z-[60]"
                        style="display: none;">
                        <div class="p-6">
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="h-14 w-14 spm-sidebar-user-popover-avatar flex items-center justify-center overflow-hidden flex-shrink-0 font-bold text-xl uppercase">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-bold text-gray-900 truncate">{{ auth()->user()->name }}</h3>
                                    <p class="text-[10px] text-gray-500 truncate mb-1">{{ auth()->user()->email }}</p>
                                    <span class="spm-sidebar-role-badge">
                                        {{ $roleName }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <a href="{{ route('profile') }}" wire:navigate class="spm-sidebar-user-link">
                                    <x-ui.icon name="profile-user" class="fs-2" />
                                    Pengaturan Profil
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="spm-sidebar-user-link spm-sidebar-user-link-danger w-full">
                                        <x-ui.icon name="exit-right" class="fs-2" />
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile Sidebar Drawer (Portal) -->
    <template x-teleport="body">
        <div x-show="$store.sidebar.open" class="relative z-50 lg:hidden" x-ref="dialog" role="dialog" aria-modal="true" style="display: none;">
            <!-- Backdrop -->
            <div x-show="$store.sidebar.open"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-900 bg-opacity-60 backdrop-blur-sm" @click="$store.sidebar.open = false"></div>

            <div class="fixed inset-0 flex" @click="$store.sidebar.open = false">
                <!-- Sidebar UI -->
                <div x-show="$store.sidebar.open"
                    @click.stop
                    x-transition:enter="transition ease-in-out duration-300 transform"
                    x-transition:enter-start="-translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in-out duration-300 transform"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="-translate-x-full"
                    class="spm-sidebar spm-sidebar-mobile relative flex flex-col max-w-xs w-full bg-white shadow-2xl">

                    <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                        <div class="flex-shrink-0 flex items-center px-6">
                            <div class="flex items-center gap-3">
                                <div class="spm-sidebar-logo-mark flex items-center justify-center">
                                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 7C9.23858 7 7 9.23858 7 12C7 14.7614 9.23858 17 12 17C14.7614 17 17 14.7614 17 12C17 9.23858 14.7614 7 12 7Z" stroke="currentColor" stroke-width="1.5" />
                                        <path d="M12 2V4M12 20V22M4 12H2M22 12H20M5.63605 5.63605L7.05026 7.05026M16.9497 16.9497L18.364 18.364M5.63605 18.364L7.05026 16.9497M16.9497 7.05026L18.364 5.63605" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                        <path d="M12 8.5V15.5M8.5 12H15.5" stroke="currentColor" stroke-width="1" />
                                    </svg>
                                </div>
                                <span class="spm-sidebar-brand-title">PesantrenMu</span>
                            </div>
                        </div>
                        <nav class="spm-sidebar-nav mt-5 px-4" @click="if ($event.target.closest('a')) $store.sidebar.open = false">
                            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="grid">
                                {{ __('Dashboards') }}
                            </x-sidebar-link>

                            <!-- Admin Menu -->
                            @if (auth()->user()->isAdmin())
                            <x-ui.sidebar-section>ADMINISTRASI</x-ui.sidebar-section>
                            <div x-data="{ 
                                openMaster: @json(request()->routeIs('admin.master-edpm') || request()->routeIs('admin.master-dokumen')), 
                                openManajemen: @json(request()->routeIs('roles.*') || request()->routeIs('accounts.*')) 
                            }" class="space-y-1">
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

                                <!-- Referensi Data Group -->
                                <div class="space-y-1">
                                    <button @click="openMaster = !openMaster" class="spm-sidebar-group-toggle group flex items-center justify-between w-full">
                                        <div class="flex items-center min-w-0">
                                            <span class="spm-sidebar-icon spm-sidebar-group-icon">
                                                <x-ui.icon name="data" class="fs-2" />
                                            </span>
                                            <span class="spm-sidebar-title truncate">Referensi Data</span>
                                        </div>
                                        <x-ui.icon name="down" class="spm-sidebar-group-caret fs-7" x-bind:class="{ 'rotate-180': openMaster }" />
                                    </button>
                                    <div x-show="openMaster" x-transition x-cloak class="space-y-1 ml-8">
                                        <x-sidebar-link :href="route('admin.master-edpm')" :active="request()->routeIs('admin.master-edpm')" icon="none" class="!bg-transparent !px-0">
                                            {{ __('Komponen') }}
                                        </x-sidebar-link>
                                        <x-sidebar-link :href="route('admin.master-dokumen')" :active="request()->routeIs('admin.master-dokumen')" icon="none" class="!bg-transparent !px-0">
                                            {{ __('Dokumen') }}
                                        </x-sidebar-link>
                                    </div>
                                </div>

                                <!-- Manajemen Group -->
                                <div class="space-y-1">
                                    <button @click="openManajemen = !openManajemen" class="spm-sidebar-group-toggle group flex items-center justify-between w-full">
                                        <div class="flex items-center min-w-0">
                                            <span class="spm-sidebar-icon spm-sidebar-group-icon">
                                                <x-ui.icon name="setting-2" class="fs-2" />
                                            </span>
                                            <span class="spm-sidebar-title truncate">Manajemen</span>
                                        </div>
                                        <x-ui.icon name="down" class="spm-sidebar-group-caret fs-7" x-bind:class="{ 'rotate-180': openManajemen }" />
                                    </button>
                                    <div x-show="openManajemen" x-transition x-cloak class="space-y-1 ml-8">
                                        <x-sidebar-link :href="route('roles.index')" :active="request()->routeIs('roles.*')" icon="none" class="!bg-transparent !px-0">
                                            {{ __('Role') }}
                                        </x-sidebar-link>
                                        <x-sidebar-link :href="route('accounts.index')" :active="request()->routeIs('accounts.*')" icon="none" class="!bg-transparent !px-0">
                                            {{ __('Accounts') }}
                                        </x-sidebar-link>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Pesantren Menu -->
                            @if (auth()->user()->isPesantren())
                            <div class="space-y-1">
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
                            </div>
                            @endif

                            <!-- Asesor Menu -->
                            @if (auth()->user()->isAsesor())
                            <div class="space-y-1">
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
                            </div>
                            @endif
                        </nav>
                    </div>

                    <!-- Mobile Sidebar Footer User Profile -->
                    <div class="spm-sidebar-footer flex-shrink-0 p-4" x-data="{ userOpenMobile: false }">
                        <div class="relative">
                            <button
                                @click="userOpenMobile = !userOpenMobile"
                                class="flex w-full items-center focus:outline-none p-1 rounded-lg hover:bg-white transition-colors">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 spm-sidebar-avatar flex items-center justify-center text-white font-bold overflow-hidden text-lg uppercase flex-shrink-0">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </div>
                                    <div class="ml-3 text-left">
                                        <p class="spm-sidebar-sign-label mb-1">Masuk sebagai</p>
                                        <p class="text-xs font-bold text-gray-800 truncate w-32 leading-none">{{ auth()->user()->name }}</p>
                                    </div>
                                </div>
                            </button>

                            <!-- Profile Popover (Mobile) -->
                            <div
                                x-show="userOpenMobile"
                                @click.away="userOpenMobile = false"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-4"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-4"
                                class="spm-sidebar-user-popover absolute bottom-full left-0 mb-3 w-64 bg-white overflow-hidden z-[80]"
                                style="display: none;">
                                <div class="p-5">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <div class="h-10 w-10 spm-sidebar-user-popover-avatar flex items-center justify-center font-bold uppercase transition-transform">
                                            {{ substr(auth()->user()->name, 0, 1) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-xs font-bold text-gray-900 truncate">{{ auth()->user()->name }}</h3>
                                            <p class="text-[10px] text-gray-500 truncate">{{ auth()->user()->email }}</p>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <a href="{{ route('profile') }}" wire:navigate class="spm-sidebar-user-link">
                                            <x-ui.icon name="profile-user" class="fs-2" />
                                            Pengaturan Profil
                                        </a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="spm-sidebar-user-link spm-sidebar-user-link-danger w-full">
                                                <x-ui.icon name="exit-right" class="fs-2" />
                                                Keluar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0 w-14"></div>
            </div>
        </div>
    </template>
</div>
