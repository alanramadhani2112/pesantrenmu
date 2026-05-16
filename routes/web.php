<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Volt::route('roles', 'pages.roles.index')
    ->middleware(['auth', 'verified'])
    ->name('roles.index');

Volt::route('accounts', 'pages.accounts.index')
    ->middleware(['auth', 'verified'])
    ->name('accounts.index');

Volt::route('documents/{doc?}', 'pages.dokumen.index')
    ->middleware(['auth', 'verified'])
    ->name('documents.index');

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
| Group khusus untuk role `admin`. Middleware `role:admin` (alias didaftar
| di bootstrap/app.php) memberikan defense-in-depth lapis pertama, sehingga
| user dengan role lain langsung di-abort 403 sebelum masuk komponen
| Livewire (lihat design.md Stream 3.1).
*/
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Volt::route('master-edpm', 'pages.admin.master-edpm')
            ->name('master-edpm');

        Volt::route('master-kategori-dokumen', 'pages.admin.master.kategori-dokumen')
            ->name('master-kategori-dokumen');

        Volt::route('master-document', 'pages.admin.master.dokumen')
            ->name('master-dokumen');

        Volt::route('master-role-permission', 'pages.admin.master.role-permission')
            ->name('master-role-permission');

        Volt::route('akreditasi', 'pages.admin.akreditasi')
            ->name('akreditasi');

        Volt::route('akreditasi/{uuid}', 'pages.admin.akreditasi-detail')
            ->name('akreditasi-detail');

        Volt::route('asesor', 'pages.admin.asesor.index')
            ->name('asesor.index');

        Volt::route('asesor/{uuid}', 'pages.admin.asesor.detail')
            ->name('asesor.detail');

        Volt::route('banding', 'pages.admin.banding')
            ->name('banding');

        Volt::route('banding/{id}', 'pages.admin.banding-detail')
            ->name('banding-detail');

        Volt::route('pesantren', 'pages.admin.pesantren.index')
            ->name('pesantren.index');

        Volt::route('pesantren/{uuid}', 'pages.admin.pesantren.detail')
            ->name('pesantren.detail');
    });

/*
|--------------------------------------------------------------------------
| Asesor routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:asesor'])
    ->prefix('asesor')
    ->name('asesor.')
    ->group(function () {
        Volt::route('profile', 'pages.asesor.profile')
            ->name('profile');

        Volt::route('akreditasi', 'pages.asesor.akreditasi')
            ->name('akreditasi');

        Route::get('akreditasi/{uuid}', \App\Livewire\Pages\Asesor\AkreditasiDetail::class)
            ->name('akreditasi-detail');
    });

/*
|--------------------------------------------------------------------------
| Pesantren routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:pesantren'])
    ->prefix('pesantren')
    ->name('pesantren.')
    ->group(function () {
        Volt::route('profile', 'pages.pesantren.profile')
            ->name('profile');

        Volt::route('ipm', 'pages.pesantren.ipm')
            ->name('ipm');

        Volt::route('sdm', 'pages.pesantren.sdm')
            ->name('sdm');

        Volt::route('edpm', 'pages.pesantren.edpm')
            ->name('edpm');

        Volt::route('akreditasi', 'pages.pesantren.akreditasi')
            ->name('akreditasi');

        Volt::route('akreditasi/{uuid}', 'pages.pesantren.akreditasi-detail')
            ->name('akreditasi-detail');
    });

require __DIR__ . '/auth.php';
require __DIR__ . '/sso/sso.php';
