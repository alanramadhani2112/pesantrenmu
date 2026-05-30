<?php

use App\Livewire\Home;
use App\Livewire\Pages\Admin\FailedNotificationDashboard;
use App\Livewire\Pages\Asesor\AkreditasiDetail;
use App\Models\Asesor;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::get('dashboard', Home::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Volt::route('roles', 'pages.roles.index')
    ->middleware(['auth', 'verified', 'permission:master.role'])
    ->name('roles.index');

Volt::route('accounts', 'pages.accounts.index')
    ->middleware(['auth', 'verified', 'permission:account.view'])
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
            ->middleware('permission:master.edpm')
            ->name('master-edpm');

        Volt::route('master-kategori-dokumen', 'pages.admin.master.kategori-dokumen')
            ->middleware('permission:master.kategori')
            ->name('master-kategori-dokumen');

        Volt::route('master-document', 'pages.admin.master.dokumen')
            ->middleware('permission:master.dokumen')
            ->name('master-dokumen');

        Volt::route('master-role-permission', 'pages.admin.master.role-permission')
            ->middleware('permission:master.role')
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
            ->middleware('permission:banding.view')
            ->name('banding');

        Volt::route('banding/{id}', 'pages.admin.banding-detail')
            ->middleware('permission:banding.view')
            ->name('banding-detail');

        Volt::route('pesantren', 'pages.admin.pesantren.index')
            ->middleware('permission:pesantren.view')
            ->name('pesantren.index');

        Volt::route('pesantren/{uuid}', 'pages.admin.pesantren.detail')
            ->middleware('permission:pesantren.view')
            ->name('pesantren.detail');

        Route::get('failed-notifications', FailedNotificationDashboard::class)
            ->middleware('permission:notification.view')
            ->name('failed-notifications');

        Volt::route('trash', 'pages.admin.trash')
            ->middleware('permission:trash.view')
            ->name('trash');
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

        Route::get('akreditasi/{uuid}', AkreditasiDetail::class)
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

/*
|--------------------------------------------------------------------------
| Secure private file download
|--------------------------------------------------------------------------
| Serves KTP / ijazah / kartu_nbm from the local (non-public) disk.
| Only the asesor themselves or admin/super-admin may download.
*/
Route::get('secure/asesor-docs/{asesorId}/{field}', function (int $asesorId, string $field) {
    $allowedFields = ['ktp_file', 'ijazah_file', 'kartu_nbm_file'];

    if (! in_array($field, $allowedFields, true)) {
        abort(404);
    }

    $asesor = Asesor::findOrFail($asesorId);

    /** @var User $user */
    $user = auth()->user();

    if ($asesor->user_id !== $user->id && ! $user->canAccessAdminArea()) {
        abort(403);
    }

    $path = $asesor->$field;

    if (! $path || ! Storage::disk('local')->exists($path)) {
        abort(404);
    }

    return Storage::disk('local')->response($path);
})->middleware(['auth', 'verified'])->name('secure.asesor-docs');

require __DIR__.'/auth.php';
require __DIR__.'/sso/sso.php';
