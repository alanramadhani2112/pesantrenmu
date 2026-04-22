<?php

use App\Http\Controllers\Sso\SsoController;
use \Illuminate\Support\Facades\Route;

Route::middleware('guest')->prefix('sso')->group(function () {
    Route::get('preflight', [SsoController::class, 'preflight'])->name('sso.preflight');
    Route::get('auth', [SsoController::class, 'auth'])->name('sso.callback');
    Route::get('auth2/{token}', [SsoController::class, 'login'])->name('sso.login');
});