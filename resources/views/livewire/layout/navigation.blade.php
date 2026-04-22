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

<div class="h-full flex-shrink-0">
    <!-- Desktop Sidebar -->
    <aside class="hidden lg:flex lg:flex-shrink-0 h-full">
        <div class="flex flex-col w-64 border-r border-gray-200 bg-white h-full">
            <!-- Logo Section -->
            <div class="flex items-center h-16 px-6 border-b border-gray-200">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-[#1e3a5f] shadow-sm">
                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 7C9.23858 7 7 9.23858 7 12C7 14.7614 9.23858 17 12 17C14.7614 17 17 14.7614 17 12C17 9.23858 14.7614 7 12 7Z" stroke="currentColor" stroke-width="1.5" />
                            <path d="M12 2V4M12 20V22M4 12H2M22 12H20M5.63605 5.63605L7.05026 7.05026M16.9497 16.9497L18.364 18.364M5.63605 18.364L7.05026 16.9497M16.9497 7.05026L18.364 5.63605" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M12 8.5V15.5M8.5 12H15.5" stroke="currentColor" stroke-width="1" />
                        </svg>
                    </div>
                    <span class="text-xl font-black text-[#1e3a5f] tracking-tight">PesantrenMu</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 flex flex-col pt-4 overflow-y-auto">
                <nav class="mt-4 flex-1 px-4 space-y-1">
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
                    <div class="pt-6 pb-2 px-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ADMINISTRASI</span>
                    </div>
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

                        <div class="pt-4 pb-2 px-3">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">MASTER DATA</span>
                        </div>

                        <!-- Referensi Data Group -->
                        <div class="space-y-1">
                            <button @click="openMaster = !openMaster" class="group flex items-center justify-between w-full px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition duration-150">
                                <div class="flex items-center">
                                    <svg class="mr-3 flex-shrink-0 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                    </svg>
                                    <span>Referensi Data</span>
                                </div>
                                <svg class="h-4 w-4 transform transition-transform duration-200" :class="{ 'rotate-180': openMaster }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="openMaster" x-transition x-cloak class="space-y-1 ml-8">
                                <x-sidebar-link :href="route('admin.master-edpm')" :active="request()->routeIs('admin.master-edpm')" icon="document" class="!bg-transparent">
                                    {{ __('Komponen') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('admin.master-dokumen')" :active="request()->routeIs('admin.master-dokumen')" icon="document" class="!bg-transparent">
                                    {{ __('Dokumen') }}
                                </x-sidebar-link>
                            </div>
                        </div>

                        <!-- Manajemen Group -->
                        <div class="space-y-1">
                            <button @click="openManajemen = !openManajemen" class="group flex items-center justify-between w-full px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition duration-150">
                                <div class="flex items-center">
                                    <svg class="mr-3 flex-shrink-0 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Manajemen</span>
                                </div>
                                <svg class="h-4 w-4 transform transition-transform duration-200" :class="{ 'rotate-180': openManajemen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="openManajemen" x-transition x-cloak class="space-y-1 ml-8">
                                <x-sidebar-link :href="route('roles.index')" :active="request()->routeIs('roles.*')" icon="users" class="!bg-transparent">
                                    {{ __('Role') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('accounts.index')" :active="request()->routeIs('accounts.*')" icon="users" class="!bg-transparent">
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

                        <div class="pt-6 pb-2 px-4">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">AKREDITASI</span>
                        </div>
                        <x-sidebar-link :href="route('pesantren.edpm')" :active="request()->routeIs('pesantren.edpm')" icon="paper">
                            {{ __('EDPM') }}
                        </x-sidebar-link>
                        <x-sidebar-link :href="route('pesantren.akreditasi')" :active="request()->routeIs('pesantren.akreditasi*')" icon="shield-lock">
                            {{ __('Akreditasi') }}
                        </x-sidebar-link>

                        <div class="pt-6 pb-2 px-4">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">DOKUMEN</span>
                        </div>
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

                    <div class="pt-6 pb-2 px-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">AKREDITASI</span>
                    </div>
                    <x-sidebar-link :href="route('asesor.akreditasi')" :active="request()->routeIs('asesor.akreditasi*')" icon="shield-lock">
                        {{ __('Akreditasi') }}
                    </x-sidebar-link>

                    <div class="pt-6 pb-2 px-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">DOKUMEN</span>
                    </div>
                    <x-sidebar-link :href="route('documents.index', ['doc' => 'iapm'])" :active="request()->fullUrlIs(route('documents.index', ['doc' => 'iapm']))" icon="document-stack">
                        {{ __('IAPM') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('documents.index', ['doc' => 'visitasi'])" :active="request()->fullUrlIs(route('documents.index', ['doc' => 'iapm']))" icon="document-stack">
                        {{ __('Visitasi') }}
                    </x-sidebar-link>
                    @endif
                </nav>
            </div>

            <!-- Sidebar Footer User Profile (Fixed Bottom) -->
            <div class="mt-auto flex-shrink-0 border-t border-gray-200 bg-white p-4" x-data="{ userOpen: false }">
                <div class="relative">
                    <button
                        @mouseenter="userOpen = true"
                        @mouseleave="userOpen = false"
                        @click="userOpen = !userOpen"
                        class="flex group w-full items-center focus:outline-none overflow-hidden rounded-lg p-1 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center">
                            <div class="ih-10 w-10 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold border-2 border-white shadow-sm overflow-hidden text-lg uppercase flex-shrink-0">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="ml-3 text-left">
                                <p class="text-[10px] font-medium text-gray-400 uppercase tracking-tighter leading-none mb-1">Signed in as</p>
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
                        class="absolute bottom-full left-0 mb-4 w-72 bg-white rounded-2xl shadow-[0_20px_50px_rgba(8,_112,_184,_0.1)] border border-gray-100 overflow-hidden z-[60]"
                        style="display: none;">
                        <div class="p-6">
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="h-14 w-14 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 overflow-hidden shadow-inner border border-indigo-100 flex-shrink-0 font-bold text-xl uppercase">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-bold text-gray-900 truncate">{{ auth()->user()->name }}</h3>
                                    <p class="text-[10px] text-gray-500 truncate mb-1">{{ auth()->user()->email }}</p>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 uppercase tracking-wider">
                                        {{ auth()->user()->role->name }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <a href="{{ route('profile') }}" wire:navigate class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition-all group">
                                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Pengaturan Profil
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full px-4 py-2.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-red-50 hover:text-red-600 transition-all group">
                                        <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
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
                    class="relative flex flex-col max-w-xs w-full bg-white shadow-2xl">

                    <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                        <div class="flex-shrink-0 flex items-center px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-[#1e3a5f] shadow-sm">
                                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 7C9.23858 7 7 9.23858 7 12C7 14.7614 9.23858 17 12 17C14.7614 17 17 14.7614 17 12C17 9.23858 14.7614 7 12 7Z" stroke="currentColor" stroke-width="1.5" />
                                        <path d="M12 2V4M12 20V22M4 12H2M22 12H20M5.63605 5.63605L7.05026 7.05026M16.9497 16.9497L18.364 18.364M5.63605 18.364L7.05026 16.9497M16.9497 7.05026L18.364 5.63605" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                        <path d="M12 8.5V15.5M8.5 12H15.5" stroke="currentColor" stroke-width="1" />
                                    </svg>
                                </div>
                                <span class="text-xl font-black text-[#1e3a5f] tracking-tight">PesantrenMu</span>
                            </div>
                        </div>
                        <nav class="mt-5 px-4 space-y-1" @click="if ($event.target.closest('a')) $store.sidebar.open = false">
                            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="grid">
                                {{ __('Dashboards') }}
                            </x-sidebar-link>

                            <!-- Admin Menu -->
                            @if (auth()->user()->isAdmin())
                            <div class="pt-6 pb-2 px-4">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ADMINISTRASI</span>
                            </div>
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

                                <div class="pt-4 pb-2 px-3">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">MASTER DATA</span>
                                </div>

                                <!-- Referensi Data Group -->
                                <div class="space-y-1">
                                    <button @click="openMaster = !openMaster" class="group flex items-center justify-between w-full px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition duration-150">
                                        <div class="flex items-center">
                                            <svg class="mr-3 flex-shrink-0 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                            </svg>
                                            <span>Referensi Data</span>
                                        </div>
                                        <svg class="h-4 w-4 transform transition-transform duration-200" :class="{ 'rotate-180': openMaster }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
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
                                    <button @click="openManajemen = !openManajemen" class="group flex items-center justify-between w-full px-3 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition duration-150">
                                        <div class="flex items-center">
                                            <svg class="mr-3 flex-shrink-0 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span>Manajemen</span>
                                        </div>
                                        <svg class="h-4 w-4 transform transition-transform duration-200" :class="{ 'rotate-180': openManajemen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
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

                                <div class="pt-6 pb-2 px-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">AKREDITASI</span>
                                </div>
                                <x-sidebar-link :href="route('pesantren.ipm')" :active="request()->routeIs('pesantren.ipm')" icon="paper">
                                    {{ __('IPM') }}
                                </x-sidebar-link>
                                <x-sidebar-link :href="route('pesantren.akreditasi')" :active="request()->routeIs('pesantren.akreditasi*')" icon="shield-lock">
                                    {{ __('Akreditasi') }}
                                </x-sidebar-link>

                                <div class="pt-6 pb-2 px-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">DOKUMEN</span>
                                </div>
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

                                <div class="pt-6 pb-2 px-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">AKREDITASI</span>
                                </div>
                                <x-sidebar-link :href="route('asesor.akreditasi')" :active="request()->routeIs('asesor.akreditasi*')" icon="shield-lock">
                                    {{ __('Akreditasi') }}
                                </x-sidebar-link>

                                <div class="pt-6 pb-2 px-4">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">DOKUMEN</span>
                                </div>
                                <x-sidebar-link :href="route('documents.index', ['doc' => 'iapm'])" :active="request()->fullUrlIs(route('documents.index', ['doc' => 'iapm']))" icon="document-stack">
                                    {{ __('IAPM') }}
                                </x-sidebar-link>
                            </div>
                            @endif
                        </nav>
                    </div>

                    <!-- Mobile Sidebar Footer User Profile -->
                    <div class="flex-shrink-0 border-t border-gray-200 p-4 bg-gray-50/50" x-data="{ userOpenMobile: false }">
                        <div class="relative">
                            <button
                                @click="userOpenMobile = !userOpenMobile"
                                class="flex w-full items-center focus:outline-none p-1 rounded-lg hover:bg-white transition-colors">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold border-2 border-white shadow-sm overflow-hidden text-lg uppercase flex-shrink-0">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </div>
                                    <div class="ml-3 text-left">
                                        <p class="text-[10px] font-medium text-gray-400 uppercase tracking-tighter leading-none mb-1">Signed in as</p>
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
                                class="absolute bottom-full left-0 mb-3 w-64 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-[80]"
                                style="display: none;">
                                <div class="p-5">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold uppercase transition-transform">
                                            {{ substr(auth()->user()->name, 0, 1) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-xs font-bold text-gray-900 truncate">{{ auth()->user()->name }}</h3>
                                            <p class="text-[10px] text-gray-500 truncate">{{ auth()->user()->email }}</p>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <a href="{{ route('profile') }}" wire:navigate class="flex items-center px-3 py-2 text-xs font-medium text-gray-700 rounded-lg hover:bg-indigo-50 transition-all">
                                            Pengaturan Profil
                                        </a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="flex items-center w-full px-3 py-2 text-xs font-medium text-red-600 rounded-lg hover:bg-red-50 transition-all">
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