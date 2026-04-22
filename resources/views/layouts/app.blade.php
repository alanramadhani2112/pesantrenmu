<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('icon.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Quill WYSIWYG -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
</head>

<body class="font-sans antialiased text-gray-900 bg-gray-50" x-data>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <livewire:layout.navigation />

        <!-- Main Content Area -->
        <div id="main-content-scroll" class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
            <!-- Top Header -->
            <header class="sticky top-0 z-30 flex items-center justify-between h-16 bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center">
                    <!-- Mobile Sidebar Toggle -->
                    <button @click="$store.sidebar.open = !$store.sidebar.open" class="text-gray-500 lg:hidden focus:outline-none focus:text-gray-700">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Page Title/Breadcrumbs -->
                    <div class="ml-4 lg:ml-0 flex flex-col justify-center h-16">
                        <h2 class="font-extrabold text-xl text-gray-900 tracking-tight leading-none">
                            Dashboard
                        </h2>
                        @if(!request()->routeIs('dashboard'))
                        <div class="flex items-center text-[11px] text-gray-400 mt-1.5 font-bold tracking-wider">
                            <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600 transition-colors">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                </svg>
                            </a>
                            @isset($header)
                            <svg class="w-2.5 h-2.5 mx-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="text-gray-500">{{ $header }}</span>
                            @endisset
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center space-x-4">
                    <livewire:layout.notification-menu />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-grow p-4 md:p-6 lg:p-8">
                {{ $slot }}
            </main>

            <footer class="bg-white border-t border-gray-200 py-4 px-6 text-center text-sm text-gray-500">
                &copy; {{ date('Y') }} Sistem Penjaminan Mutu (SPM) Muhammadiyah
            </footer>
        </div>
    </div>

    <!-- Notification Toast -->
    <!-- Notification Toast -->
    <div x-data="{ 
                show: false, 
                type: 'success',
                title: '', 
                message: '',
                timeout: null,
                init() {
                    window.addEventListener('notification-received', (event) => {
                        this.type = event.detail.type || 'success';
                        this.title = event.detail.title;
                        this.message = event.detail.message;
                        this.show = true;
                        
                        if (this.timeout) clearTimeout(this.timeout);
                        this.timeout = setTimeout(() => { this.show = false }, 5000);
                    });

                    // Handle session flash
                    @if(session('status') || session('success'))
                        setTimeout(() => {
                            this.type = 'success';
                            this.title = 'Berhasil!';
                            this.message = '{{ session('status') ?? session('success') }}';
                            this.show = true;
                            this.timeout = setTimeout(() => { this.show = false }, 5000);
                        }, 500);
                    @endif

                    @if(session('error'))
                        setTimeout(() => {
                            this.type = 'error';
                            this.title = 'Terjadi Kesalahan!';
                            this.message = '{{ session('error') }}';
                            this.show = true;
                            this.timeout = setTimeout(() => { this.show = false }, 5000);
                        }, 500);
                    @endif
                }
            }"
        x-show="show"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="translate-x-full opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="translate-x-full opacity-0"
        class="fixed top-6 right-6 p-4 z-[100] max-w-sm w-full"
        style="display: none;">

        <div :class="{
                'bg-emerald-50 border-emerald-500 text-emerald-900 shadow-emerald-200/50': type === 'success',
                'bg-rose-50 border-rose-500 text-rose-900 shadow-rose-200/50': type === 'error',
                'bg-amber-50 border-amber-500 text-amber-900 shadow-amber-200/50': type === 'warning',
                'bg-white border-indigo-500 text-gray-900 shadow-indigo-200/50': type === 'info'
            }" class="rounded-xl border-l-8 shadow-2xl p-4 flex items-start gap-4 backdrop-blur-md bg-opacity-95 ring-1 ring-black/5 animate-bounce-short">

            <!-- Icon -->
            <div class="flex-shrink-0">
                <template x-if="type === 'success'">
                    <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </template>
                <template x-if="type === 'error'">
                    <div class="h-10 w-10 rounded-full bg-rose-100 flex items-center justify-center text-rose-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </template>
                <template x-if="type === 'warning'">
                    <div class="h-10 w-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </template>
                <template x-if="type === 'info' || !type">
                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </template>
            </div>

            <!-- Content -->
            <div class="flex-1 pt-0.5">
                <p class="text-sm font-black uppercase tracking-wider" x-text="title"></p>
                <p class="mt-1 text-sm font-medium opacity-80" x-text="message"></p>
            </div>

            <!-- Close Button -->
            <button @click="show = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</body>

</html>