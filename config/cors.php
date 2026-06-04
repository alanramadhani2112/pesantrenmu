<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
     * Production hardening (CMS-2026-06-04).
     *
     * Tidak ada REST API di aplikasi ini. CORS dikonfigurasi untuk Livewire
     * asset + CSRF cookie saja. allowed_origins diambil dari APP_URL di .env
     * supaya tidak hardcode domain dan tetap berfungsi di environment berbeda
     * (local, staging, production).
     *
     * supports_credentials = true diperlukan untuk Livewire CSRF + session
     * cookie agar dikirim oleh browser di cross-origin redirect flow (SSO).
     */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'livewire/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [env('APP_URL', 'http://localhost')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'X-CSRF-TOKEN', 'X-Livewire', 'Authorization', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
