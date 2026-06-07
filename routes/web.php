<?php

use App\Http\Controllers\Admin\MasterDokumenController;
use App\Http\Controllers\Admin\MasterEdpmController;
use App\Http\Controllers\Admin\MasterKategoriDokumenController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\DashboardController;
use App\Livewire\Pages\Admin\FailedNotificationDashboard;
use App\Livewire\Pages\Asesor\AkreditasiDetail;
use App\Models\Asesor;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('edit');
    Route::put('/info', [\App\Http\Controllers\ProfileController::class, 'updateInfo'])->name('info');
    Route::put('/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password');
    Route::put('/photo', [\App\Http\Controllers\ProfileController::class, 'updatePhoto'])->name('photo');
    Route::delete('/photo', [\App\Http\Controllers\ProfileController::class, 'removePhoto'])->name('photo.remove');
});

Route::middleware(['auth', 'verified', 'permission:master.role'])
    ->prefix('admin/roles')
    ->name('admin.roles.')
    ->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::put('/{id}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
    });

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
        // Master EDPM
        Route::get('master-edpm', [MasterEdpmController::class, 'index'])
            ->middleware('permission:master.edpm')
            ->name('master-edpm');
        Route::post('master-edpm/komponen', [MasterEdpmController::class, 'storeKomponen'])
            ->middleware('permission:master.edpm')
            ->name('master-edpm.komponen.store');
        Route::put('master-edpm/komponen/{id}', [MasterEdpmController::class, 'updateKomponen'])
            ->middleware('permission:master.edpm')
            ->name('master-edpm.komponen.update');
        Route::delete('master-edpm/komponen/{id}', [MasterEdpmController::class, 'destroyKomponen'])
            ->middleware('permission:master.edpm')
            ->name('master-edpm.komponen.destroy');
        Route::post('master-edpm/butir', [MasterEdpmController::class, 'storeButir'])
            ->middleware('permission:master.edpm')
            ->name('master-edpm.butir.store');
        Route::put('master-edpm/butir/{id}', [MasterEdpmController::class, 'updateButir'])
            ->middleware('permission:master.edpm')
            ->name('master-edpm.butir.update');
        Route::delete('master-edpm/butir/{id}', [MasterEdpmController::class, 'destroyButir'])
            ->middleware('permission:master.edpm')
            ->name('master-edpm.butir.destroy');

        // Master Kategori Dokumen
        Route::get('master-kategori-dokumen', [MasterKategoriDokumenController::class, 'index'])
            ->middleware('permission:master.kategori')
            ->name('master-kategori-dokumen.index');
        Route::post('master-kategori-dokumen', [MasterKategoriDokumenController::class, 'store'])
            ->middleware('permission:master.kategori')
            ->name('master-kategori-dokumen.store');
        Route::put('master-kategori-dokumen/{category}', [MasterKategoriDokumenController::class, 'update'])
            ->middleware('permission:master.kategori')
            ->name('master-kategori-dokumen.update');
        Route::delete('master-kategori-dokumen/{category}', [MasterKategoriDokumenController::class, 'destroy'])
            ->middleware('permission:master.kategori')
            ->name('master-kategori-dokumen.destroy');

        // Master Dokumen
        Route::get('master-dokumen', [MasterDokumenController::class, 'index'])
            ->middleware('permission:master.dokumen')
            ->name('master-dokumen.index');
        Route::post('master-dokumen', [MasterDokumenController::class, 'store'])
            ->middleware('permission:master.dokumen')
            ->name('master-dokumen.store');
        Route::put('master-dokumen/{id}', [MasterDokumenController::class, 'update'])
            ->middleware('permission:master.dokumen')
            ->name('master-dokumen.update');
        Route::delete('master-dokumen/{id}', [MasterDokumenController::class, 'destroy'])
            ->middleware('permission:master.dokumen')
            ->name('master-dokumen.destroy');

        // Role Permission Matrix
        Route::get('master-role-permission', [RolePermissionController::class, 'index'])
            ->middleware('permission:master.role')
            ->name('role-permission.index');
        Route::post('master-role-permission', [RolePermissionController::class, 'save'])
            ->middleware('permission:master.role')
            ->name('role-permission.save');

        // Akreditasi
        Route::get('akreditasi', [App\Http\Controllers\Admin\AkreditasiController::class, 'index'])
            ->name('akreditasi');
        Route::post('akreditasi/catatan-modal', [App\Http\Controllers\Admin\AkreditasiController::class, 'catatanModal'])
            ->name('akreditasi.catatan-modal');
        Route::delete('akreditasi', [App\Http\Controllers\Admin\AkreditasiController::class, 'delete'])
            ->name('akreditasi.delete');
        Route::post('akreditasi/export', [App\Http\Controllers\Admin\AkreditasiController::class, 'export'])
            ->name('akreditasi.export');

        Route::get('akreditasi/{uuid}', [App\Http\Controllers\Admin\AkreditasiDetailController::class, 'show'])
            ->name('akreditasi-detail');
        Route::post('akreditasi/{uuid}/reschedule-visitasi', [App\Http\Controllers\Admin\AkreditasiDetailController::class, 'rescheduleVisitasi'])
            ->name('akreditasi-detail.reschedule-visitasi');
        Route::post('akreditasi/{uuid}/reassign-asesor', [App\Http\Controllers\Admin\AkreditasiDetailController::class, 'reassignAsesor'])
            ->name('akreditasi-detail.reassign-asesor');
        Route::post('akreditasi/{uuid}/save-nv', [App\Http\Controllers\Admin\AkreditasiDetailController::class, 'saveAdminNv'])
            ->name('akreditasi-detail.save-nv');
        Route::post('akreditasi/{uuid}/finalize-nv', [App\Http\Controllers\Admin\AkreditasiDetailController::class, 'finalizeAllNv'])
            ->name('akreditasi-detail.finalize-nv');
        Route::post('akreditasi/{uuid}/toggle-lock', [App\Http\Controllers\Admin\AkreditasiDetailController::class, 'toggleLock'])
            ->name('akreditasi-detail.toggle-lock');
        Route::post('akreditasi/{uuid}/approve-berkas', [App\Http\Controllers\Admin\AkreditasiDetailController::class, 'approveBerkas'])
            ->name('akreditasi-detail.approve-berkas');
        Route::post('akreditasi/{uuid}/reject-berkas', [App\Http\Controllers\Admin\AkreditasiDetailController::class, 'rejectBerkas'])
            ->name('akreditasi-detail.reject-berkas');
        Route::post('akreditasi/{uuid}/approve', [App\Http\Controllers\Admin\AkreditasiDetailController::class, 'approve'])
            ->name('akreditasi-detail.approve');
        Route::post('akreditasi/{uuid}/reject', [App\Http\Controllers\Admin\AkreditasiDetailController::class, 'reject'])
            ->name('akreditasi-detail.reject');
        Route::post('akreditasi/{uuid}/open-for-review', [App\Http\Controllers\Admin\AkreditasiDetailController::class, 'openForReview'])
            ->name('akreditasi-detail.open-for-review');

        Route::get('asesor', [App\Http\Controllers\Admin\AsesorController::class, 'index'])
            ->name('asesor.index');
        Route::get('asesor/{uuid}', [App\Http\Controllers\Admin\AsesorController::class, 'show'])
            ->name('asesor.detail');
        Route::post('asesor/toggle-status', [App\Http\Controllers\Admin\AsesorController::class, 'toggleStatus'])
            ->name('asesor.toggle-status');
        Route::post('asesor/export', [App\Http\Controllers\Admin\AsesorController::class, 'export'])
            ->name('asesor.export');

        // Banding
        Route::get('banding', [App\Http\Controllers\Admin\BandingController::class, 'index'])
            ->middleware('permission:banding.view')
            ->name('banding');

        Route::get('banding/{id}', [App\Http\Controllers\Admin\BandingDetailController::class, 'show'])
            ->middleware('permission:banding.view')
            ->name('banding-detail');
        Route::post('banding/{id}/assign-reviewer', [App\Http\Controllers\Admin\BandingDetailController::class, 'assignReviewer'])
            ->middleware('permission:banding.view')
            ->name('banding.assign-reviewer');
        Route::post('banding/{id}/reassign-reviewer', [App\Http\Controllers\Admin\BandingDetailController::class, 'reassignReviewer'])
            ->middleware('permission:banding.view')
            ->name('banding.reassign-reviewer');
        Route::post('banding/{id}/submit-decision', [App\Http\Controllers\Admin\BandingDetailController::class, 'submitDecision'])
            ->middleware('permission:banding.view')
            ->name('banding.submit-decision');

        Route::get('pesantren', [App\Http\Controllers\Admin\PesantrenController::class, 'index'])
            ->middleware('permission:pesantren.view')
            ->name('pesantren.index');
        Route::get('pesantren/{uuid}', [App\Http\Controllers\Admin\PesantrenController::class, 'show'])
            ->middleware('permission:pesantren.view')
            ->name('pesantren.detail');
        Route::post('pesantren/toggle-lock', [App\Http\Controllers\Admin\PesantrenController::class, 'toggleLock'])
            ->middleware('permission:pesantren.lock')
            ->name('pesantren.toggle-lock');
        Route::post('pesantren/export', [App\Http\Controllers\Admin\PesantrenController::class, 'export'])
            ->middleware('permission:pesantren.view')
            ->name('pesantren.export');

        Route::get('failed-notifications', FailedNotificationDashboard::class)
            ->middleware('permission:notification.view')
            ->name('failed-notifications');

        Route::get('trash', [App\Http\Controllers\Admin\TrashController::class, 'index'])
            ->middleware('permission:trash.view')
            ->name('trash');
        Route::get('trash/preview/{id}', [App\Http\Controllers\Admin\TrashController::class, 'restorePreview'])
            ->middleware('permission:trash.view')
            ->name('trash.preview');
        Route::post('trash/restore', [App\Http\Controllers\Admin\TrashController::class, 'restore'])
            ->middleware('permission:trash.view')
            ->name('trash.restore');
        Route::post('trash/force-delete', [App\Http\Controllers\Admin\TrashController::class, 'forceDelete'])
            ->middleware('permission:trash.view')
            ->name('trash.force-delete');
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

/*
|--------------------------------------------------------------------------
| Panduan routes (role-restricted)
|--------------------------------------------------------------------------
| Setiap panduan hanya bisa diakses oleh role yang bersangkutan.
| Middleware EnsureUserHasRole meng-abort(403) bila role tidak cocok.
| Super admin (role=4) bypass semua gate secara otomatis.
*/
Route::get('panduan', function () {
    /** @var \App\Models\User $user */
    $user = auth()->user();

    $route = match (true) {
        $user->isSuperAdmin() => 'panduan.superadmin',
        $user->isAdmin() => 'panduan.admin',
        $user->isAsesor() => 'panduan.asesor',
        $user->isPesantren() => 'panduan.pesantren',
        default => 'dashboard',
    };

    return redirect()->route($route);
})->middleware(['auth', 'verified'])->name('panduan.index');

Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->group(function () {
        Route::view('panduan-superadmin', 'panduan.superadmin')->name('panduan.superadmin');
    });

Route::middleware(['auth', 'verified', 'role:admin'])
    ->group(function () {
        Route::view('panduan-admin', 'panduan.admin')->name('panduan.admin');
    });

Route::middleware(['auth', 'verified', 'role:asesor'])
    ->group(function () {
        Route::view('panduan-asesor', 'panduan.asesor')->name('panduan.asesor');
    });

Route::middleware(['auth', 'verified', 'role:pesantren'])
    ->group(function () {
        Route::view('panduan-pesantren', 'panduan.pesantren')->name('panduan.pesantren');
    });

/*
|--------------------------------------------------------------------------
| Internal API (session-auth, JSON responses)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('_api')->name('api.')->group(function () {
    Route::get('/sidebar-badges', [\App\Http\Controllers\Api\LayoutDataController::class, 'sidebarBadges'])->name('sidebar-badges');
    Route::get('/notifications', [\App\Http\Controllers\Api\LayoutDataController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\LayoutDataController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\LayoutDataController::class, 'markAllNotificationsRead'])->name('notifications.mark-all-read');

    Route::get('/onboarding/status', [\App\Http\Controllers\Api\OnboardingController::class, 'status'])->name('onboarding.status');
    Route::post('/onboarding/navigate', [\App\Http\Controllers\Api\OnboardingController::class, 'navigateToStep'])->name('onboarding.navigate');
    Route::post('/onboarding/skip', [\App\Http\Controllers\Api\OnboardingController::class, 'skip'])->name('onboarding.skip');
    Route::post('/onboarding/complete', [\App\Http\Controllers\Api\OnboardingController::class, 'complete'])->name('onboarding.complete');
});

require __DIR__.'/auth.php';
require __DIR__.'/sso/sso.php';
