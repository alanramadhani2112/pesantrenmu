<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

\Livewire\Volt\Volt::route('roles', 'pages.roles.index')
    ->middleware(['auth', 'verified'])
    ->name('roles.index');

\Livewire\Volt\Volt::route('accounts', 'pages.accounts.index')
    ->middleware(['auth', 'verified'])
    ->name('accounts.index');

\Livewire\Volt\Volt::route('pesantren/profile', 'pages.pesantren.profile')
    ->middleware(['auth', 'verified'])
    ->name('pesantren.profile');

\Livewire\Volt\Volt::route('pesantren/ipm', 'pages.pesantren.ipm')
    ->middleware(['auth', 'verified'])
    ->name('pesantren.ipm');

\Livewire\Volt\Volt::route('pesantren/sdm', 'pages.pesantren.sdm')
    ->middleware(['auth', 'verified'])
    ->name('pesantren.sdm');

\Livewire\Volt\Volt::route('pesantren/edpm', 'pages.pesantren.edpm')
    ->middleware(['auth', 'verified'])
    ->name('pesantren.edpm');

\Livewire\Volt\Volt::route('pesantren/akreditasi', 'pages.pesantren.akreditasi')
    ->middleware(['auth', 'verified'])
    ->name('pesantren.akreditasi');

\Livewire\Volt\Volt::route('pesantren/akreditasi/{uuid}', 'pages.pesantren.akreditasi-detail')
    ->middleware(['auth', 'verified'])
    ->name('pesantren.akreditasi-detail');

\Livewire\Volt\Volt::route('asesor/profile', 'pages.asesor.profile')
    ->middleware(['auth', 'verified'])
    ->name('asesor.profile');

\Livewire\Volt\Volt::route('asesor/akreditasi', 'pages.asesor.akreditasi')
    ->middleware(['auth', 'verified'])
    ->name('asesor.akreditasi');

Route::get('asesor/akreditasi/{uuid}', \App\Livewire\Pages\Asesor\AkreditasiDetail::class)
    ->middleware(['auth', 'verified'])
    ->name('asesor.akreditasi-detail');

\Livewire\Volt\Volt::route('admin/master-edpm', 'pages.admin.master-edpm')
    ->middleware(['auth', 'verified'])
    ->name('admin.master-edpm');

\Livewire\Volt\Volt::route('admin/master-document', 'pages.admin.master.dokumen')
    ->middleware(['auth', 'verified'])
    ->name('admin.master-dokumen');

\Livewire\Volt\Volt::route('documents/{doc?}', 'pages.dokumen.index')
    ->middleware(['auth', 'verified'])
    ->name('documents.index');

\Livewire\Volt\Volt::route('admin/akreditasi', 'pages.admin.akreditasi')
    ->middleware(['auth', 'verified'])
    ->name('admin.akreditasi');

\Livewire\Volt\Volt::route('admin/asesor', 'pages.admin.asesor.index')
    ->middleware(['auth', 'verified'])
    ->name('admin.asesor.index');

\Livewire\Volt\Volt::route('admin/asesor/{uuid}', 'pages.admin.asesor.detail')
    ->middleware(['auth', 'verified'])
    ->name('admin.asesor.detail');

\Livewire\Volt\Volt::route('admin/pesantren', 'pages.admin.pesantren.index')
    ->middleware(['auth', 'verified'])
    ->name('admin.pesantren.index');

\Livewire\Volt\Volt::route('admin/pesantren/{uuid}', 'pages.admin.pesantren.detail')
    ->middleware(['auth', 'verified'])
    ->name('admin.pesantren.detail');

\Livewire\Volt\Volt::route('admin/akreditasi/{uuid}', 'pages.admin.akreditasi-detail')
    ->middleware(['auth', 'verified'])
    ->name('admin.akreditasi-detail');

require __DIR__ . '/auth.php';
require __DIR__ . '/sso/sso.php';
