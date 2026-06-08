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
     * Tidak ada REST API publik di aplikasi ini. CORS dipertahankan minimal
     * untuk endpoint framework terkait CSRF/session. allowed_origins diambil
     * dari APP_URL di .env supaya tidak hardcode domain dan tetap berfungsi
     * di environment berbeda (local, staging, production).
     *
     * supports_credentials = true diperlukan agar CSRF + session cookie tetap
     * dapat dikirim browser pada flow redirect lintas-origin (mis. SSO).
     */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [env('APP_URL', 'http://localhost')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'X-CSRF-TOKEN', 'Authorization', 'Accept'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
