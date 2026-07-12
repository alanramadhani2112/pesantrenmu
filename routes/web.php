<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AkreditasiController;
use App\Http\Controllers\Admin\AkreditasiDetailController;
use App\Http\Controllers\Admin\AsesorController;
use App\Http\Controllers\Admin\BandingController;
use App\Http\Controllers\Admin\BandingDetailController;
use App\Http\Controllers\Admin\FailedNotificationController;
use App\Http\Controllers\Admin\MasterDokumenController;
use App\Http\Controllers\Admin\MasterEdpmController;
use App\Http\Controllers\Admin\MasterKategoriDokumenController;
use App\Http\Controllers\Admin\PesantrenController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Api\LayoutDataController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Asesor\ProfileController as AsesorProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PanduanRedirectController;
use App\Http\Controllers\Pesantren\EdpmController;
use App\Http\Controllers\Pesantren\IpmController;
use App\Http\Controllers\Pesantren\SdmController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SecureAsesorDocumentController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('edit');
    Route::put('/info', [ProfileController::class, 'updateInfo'])->name('info');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    Route::put('/photo', [ProfileController::class, 'updatePhoto'])->name('photo');
    Route::delete('/photo', [ProfileController::class, 'removePhoto'])->name('photo.remove');
});

Route::get('roles', [RoleController::class, 'index'])->middleware(['auth', 'verified', 'permission:master.role']);

Route::middleware(['auth', 'verified', 'permission:master.role'])
    ->prefix('admin/roles')
    ->name('admin.roles.')
    ->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::put('/{id}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'verified', 'permission:account.view'])
    ->prefix('accounts')
    ->name('accounts.')
    ->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::post('/', [AccountController::class, 'store'])->name('store');
        Route::put('/{id}', [AccountController::class, 'update'])->name('update');
        Route::delete('/{id}', [AccountController::class, 'destroy'])->name('destroy');
        Route::post('/toggle-status', [AccountController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/unlink-sso', [AccountController::class, 'unlinkSso'])->name('unlink-sso');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])
        ->whereNumber('document')
        ->name('documents.download');

    Route::get('documents/{doc?}', [DocumentController::class, 'index'])
        ->name('documents.index');
});

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
| Group khusus untuk role `admin`. Middleware `role:admin` (alias didaftar
| di bootstrap/app.php) memberikan defense-in-depth lapis pertama, sehingga
 | user dengan role lain langsung di-abort 403 sebelum masuk controller
 | (lihat design.md Stream 3.1).
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
        Route::get('master-document', [MasterDokumenController::class, 'index'])
            ->middleware('permission:master.dokumen');
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
        Route::get('akreditasi', [AkreditasiController::class, 'index'])
            ->name('akreditasi');
        Route::post('akreditasi/catatan-modal', [AkreditasiController::class, 'catatanModal'])
            ->name('akreditasi.catatan-modal');
        Route::delete('akreditasi', [AkreditasiController::class, 'delete'])
            ->name('akreditasi.delete');
        Route::post('akreditasi/export', [AkreditasiController::class, 'export'])
            ->name('akreditasi.export');

        Route::get('akreditasi/{uuid}', [AkreditasiDetailController::class, 'show'])
            ->name('akreditasi-detail');
        Route::post('akreditasi/{uuid}/reschedule-visitasi', [AkreditasiDetailController::class, 'rescheduleVisitasi'])
            ->name('akreditasi-detail.reschedule-visitasi');
        Route::post('akreditasi/{uuid}/reassign-asesor', [AkreditasiDetailController::class, 'reassignAsesor'])
            ->name('akreditasi-detail.reassign-asesor');
        Route::post('akreditasi/{uuid}/save-nv', [AkreditasiDetailController::class, 'saveAdminNv'])
            ->name('akreditasi-detail.save-nv');
        Route::post('akreditasi/{uuid}/finalize-nv', [AkreditasiDetailController::class, 'finalizeAllNv'])
            ->name('akreditasi-detail.finalize-nv');
        Route::post('akreditasi/{uuid}/toggle-lock', [AkreditasiDetailController::class, 'toggleLock'])
            ->name('akreditasi-detail.toggle-lock');
        Route::post('akreditasi/{uuid}/approve-berkas', [AkreditasiDetailController::class, 'approveBerkas'])
            ->name('akreditasi-detail.approve-berkas');
        Route::post('akreditasi/{uuid}/reject-berkas', [AkreditasiDetailController::class, 'rejectBerkas'])
            ->name('akreditasi-detail.reject-berkas');
        Route::post('akreditasi/{uuid}/approve', [AkreditasiDetailController::class, 'approve'])
            ->name('akreditasi-detail.approve');
        Route::post('akreditasi/{uuid}/reject', [AkreditasiDetailController::class, 'reject'])
            ->name('akreditasi-detail.reject');
        Route::post('akreditasi/{uuid}/open-for-review', [AkreditasiDetailController::class, 'openForReview'])
            ->name('akreditasi-detail.open-for-review');

        Route::get('asesor', [AsesorController::class, 'index'])
            ->name('asesor.index');
        Route::get('asesor/{uuid}', [AsesorController::class, 'show'])
            ->name('asesor.detail');
        Route::post('asesor/toggle-status', [AsesorController::class, 'toggleStatus'])
            ->name('asesor.toggle-status');
        Route::post('asesor/export', [AsesorController::class, 'export'])
            ->name('asesor.export');

        // Banding
        Route::get('banding', [BandingController::class, 'index'])
            ->middleware('permission:banding.view')
            ->name('banding');

        Route::get('banding/{id}', [BandingDetailController::class, 'show'])
            ->middleware('permission:banding.view')
            ->name('banding-detail');
        Route::post('banding/{id}/assign-reviewer', [BandingDetailController::class, 'assignReviewer'])
            ->middleware('permission:banding.view')
            ->name('banding.assign-reviewer');
        Route::post('banding/{id}/reassign-reviewer', [BandingDetailController::class, 'reassignReviewer'])
            ->middleware('permission:banding.view')
            ->name('banding.reassign-reviewer');
        Route::post('banding/{id}/submit-decision', [BandingDetailController::class, 'submitDecision'])
            ->middleware('permission:banding.view')
            ->name('banding.submit-decision');

        Route::get('pesantren', [PesantrenController::class, 'index'])
            ->middleware('permission:pesantren.view')
            ->name('pesantren.index');
        Route::get('pesantren/{uuid}', [PesantrenController::class, 'show'])
            ->middleware('permission:pesantren.view')
            ->name('pesantren.detail');
        Route::post('pesantren/toggle-lock', [PesantrenController::class, 'toggleLock'])
            ->middleware('permission:pesantren.lock')
            ->name('pesantren.toggle-lock');
        Route::post('pesantren/export', [PesantrenController::class, 'export'])
            ->middleware('permission:pesantren.view')
            ->name('pesantren.export');

        Route::get('failed-notifications', [FailedNotificationController::class, 'index'])
            ->middleware('permission:notification.view')
            ->name('failed-notifications');
        Route::post('failed-notifications/{id}/retry', [FailedNotificationController::class, 'retry'])
            ->middleware('permission:notification.retry')
            ->name('failed-notifications.retry');
        Route::post('failed-notifications/{id}/dismiss', [FailedNotificationController::class, 'dismiss'])
            ->middleware('permission:notification.dismiss')
            ->name('failed-notifications.dismiss');

        Route::get('trash', [TrashController::class, 'index'])
            ->middleware('permission:trash.view')
            ->name('trash');
        Route::get('trash/preview/{id}', [TrashController::class, 'restorePreview'])
            ->middleware('permission:trash.view')
            ->name('trash.preview');
        Route::post('trash/restore', [TrashController::class, 'restore'])
            ->middleware('permission:trash.restore')
            ->name('trash.restore');
        Route::post('trash/force-delete', [TrashController::class, 'forceDelete'])
            ->middleware('permission:trash.purge')
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
        Route::get('profile', [AsesorProfileController::class, 'show'])
            ->name('profile');
        Route::post('profile', [AsesorProfileController::class, 'update'])
            ->name('profile.update');

        Route::get('akreditasi', [App\Http\Controllers\Asesor\AkreditasiController::class, 'index'])
            ->name('akreditasi');
        Route::get('akreditasi/catatan/{id}', [App\Http\Controllers\Asesor\AkreditasiController::class, 'showCatatan'])
            ->name('akreditasi.catatan');
        Route::post('akreditasi/schedule-visitasi', [App\Http\Controllers\Asesor\AkreditasiController::class, 'scheduleVisitasi'])
            ->name('akreditasi.schedule-visitasi');
        Route::post('akreditasi/reject-document', [App\Http\Controllers\Asesor\AkreditasiController::class, 'rejectDocument'])
            ->name('akreditasi.reject-document');

        Route::get('akreditasi/{uuid}', [App\Http\Controllers\Asesor\AkreditasiController::class, 'show'])
            ->name('akreditasi-detail');

        // Akreditasi Detail actions
        Route::post('akreditasi/save-edpm', [App\Http\Controllers\Asesor\AkreditasiController::class, 'saveEdpm'])
            ->name('akreditasi.save-edpm');
        Route::post('akreditasi/accept-perbaikan', [App\Http\Controllers\Asesor\AkreditasiController::class, 'acceptPerbaikan'])
            ->name('akreditasi.accept-perbaikan');
        Route::post('akreditasi/confirm-visitasi-selesai', [App\Http\Controllers\Asesor\AkreditasiController::class, 'confirmVisitasiSelesai'])
            ->name('akreditasi.confirm-visitasi-selesai');
        Route::post('akreditasi/finalize-scoring', [App\Http\Controllers\Asesor\AkreditasiController::class, 'finalizeScoring'])
            ->name('akreditasi.finalize-scoring');
        Route::post('akreditasi/save-na', [App\Http\Controllers\Asesor\AkreditasiController::class, 'saveNaValue'])
            ->name('akreditasi.save-na');
        Route::post('akreditasi/save-nk', [App\Http\Controllers\Asesor\AkreditasiController::class, 'saveNkValue'])
            ->name('akreditasi.save-nk');
        Route::post('akreditasi/upload-laporan-individu', [App\Http\Controllers\Asesor\AkreditasiController::class, 'uploadLaporanIndividu'])
            ->name('akreditasi.upload-laporan-individu');
        Route::post('akreditasi/upload-laporan-kelompok', [App\Http\Controllers\Asesor\AkreditasiController::class, 'uploadLaporanKelompok'])
            ->name('akreditasi.upload-laporan-kelompok');
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
        Route::get('profile', [App\Http\Controllers\Pesantren\ProfileController::class, 'show'])
            ->name('profile');
        Route::post('profile/draft', [App\Http\Controllers\Pesantren\ProfileController::class, 'saveDraft'])
            ->name('profile.save-draft');
        Route::post('profile', [App\Http\Controllers\Pesantren\ProfileController::class, 'save'])
            ->name('profile.save');

        Route::get('ipm', [IpmController::class, 'show'])
            ->name('ipm');
        Route::post('ipm', [IpmController::class, 'update'])
            ->name('ipm.update');

        Route::get('sdm', [SdmController::class, 'show'])
            ->name('sdm');
        Route::post('sdm', [SdmController::class, 'save'])
            ->name('sdm.save');

        Route::get('edpm', [EdpmController::class, 'show'])
            ->name('edpm');
        Route::post('edpm', [EdpmController::class, 'save'])
            ->name('edpm.save');
        Route::post('edpm/draft', [EdpmController::class, 'saveDraft'])
            ->name('edpm.save-draft');

        Route::get('akreditasi', [App\Http\Controllers\Pesantren\AkreditasiController::class, 'index'])
            ->name('akreditasi');
        Route::get('akreditasi/perbaikan', [App\Http\Controllers\Pesantren\AkreditasiController::class, 'index'])
            ->defaults('focus', 'perbaikan')
            ->name('akreditasi.perbaikan');
        Route::get('akreditasi/kartu-kendali', [App\Http\Controllers\Pesantren\AkreditasiController::class, 'index'])
            ->defaults('focus', 'kartu_kendali')
            ->name('akreditasi.kartu-kendali');
        Route::get('akreditasi/hasil', [App\Http\Controllers\Pesantren\AkreditasiController::class, 'index'])
            ->defaults('focus', 'hasil')
            ->name('akreditasi.hasil');
        Route::post('akreditasi/create', [App\Http\Controllers\Pesantren\AkreditasiController::class, 'create'])
            ->name('akreditasi.create');
        Route::post('akreditasi/delete', [App\Http\Controllers\Pesantren\AkreditasiController::class, 'delete'])
            ->name('akreditasi.delete');
        Route::post('akreditasi/cancel', [App\Http\Controllers\Pesantren\AkreditasiController::class, 'cancel'])
            ->name('akreditasi.cancel');
        Route::post('akreditasi/banding', [App\Http\Controllers\Pesantren\AkreditasiController::class, 'banding'])
            ->name('akreditasi.banding');
        Route::get('akreditasi/catatan/{id}', [App\Http\Controllers\Pesantren\AkreditasiController::class, 'showCatatan'])
            ->name('akreditasi.catatan');

        Route::get('akreditasi/{uuid}', [App\Http\Controllers\Pesantren\AkreditasiDetailController::class, 'show'])
            ->name('akreditasi-detail');
        Route::post('akreditasi/submit-perbaikan', [App\Http\Controllers\Pesantren\AkreditasiDetailController::class, 'submitPerbaikan'])->name('akreditasi.submit-perbaikan');
        Route::post('akreditasi/upload-kartu-kendali', [App\Http\Controllers\Pesantren\AkreditasiDetailController::class, 'uploadKartuKendali'])
            ->name('akreditasi.upload-kartu-kendali');
    });

/*
|--------------------------------------------------------------------------
| Secure private file download
|--------------------------------------------------------------------------
| Serves KTP / ijazah / kartu_nbm from the local (non-public) disk.
| Only the asesor themselves or admin/super-admin may download.
*/
Route::get('secure/asesor-docs/{asesorId}/{field}', SecureAsesorDocumentController::class)
    ->middleware(['auth', 'verified'])
    ->name('secure.asesor-docs');

/*
|--------------------------------------------------------------------------
| Panduan routes (role-restricted)
|--------------------------------------------------------------------------
| Setiap panduan hanya bisa diakses oleh role yang bersangkutan.
| Middleware EnsureUserHasRole meng-abort(403) bila role tidak cocok.
| Super admin (role=4) bypass semua gate secara otomatis.
*/
Route::get('panduan', PanduanRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('panduan.index');

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
    Route::get('/sidebar-badges', [LayoutDataController::class, 'sidebarBadges'])->name('sidebar-badges');
    Route::get('/notifications', [LayoutDataController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{id}/read', [LayoutDataController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [LayoutDataController::class, 'markAllNotificationsRead'])->name('notifications.mark-all-read');

    Route::get('/onboarding/status', [OnboardingController::class, 'status'])->name('onboarding.status');
    Route::post('/onboarding/navigate', [OnboardingController::class, 'navigateToStep'])->name('onboarding.navigate');
    Route::post('/onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
});

require __DIR__.'/auth.php';
require __DIR__.'/sso/sso.php';
