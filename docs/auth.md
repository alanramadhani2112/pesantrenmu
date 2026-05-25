# Authentication & Authorization

## Role IDs

Konstanta di `App\Models\Role`:

```php
Role::ID_ADMIN       = 1   // Admin — kelola akreditasi, asesor, pesantren
Role::ID_ASESOR      = 2   // Asesor — penilaian NA/NK, visitasi
Role::ID_PESANTREN   = 3   // Pesantren — pengajuan, upload dokumen
Role::ID_SUPER_ADMIN = 4   // Super Admin — god mode, semua permission
```

Helper methods di `User` model:
```php
$user->isAdmin()        // role_id === 1
$user->isAsesor()       // role_id === 2
$user->isPesantren()    // role_id === 3
$user->isSuperAdmin()   // role_id === 4
$user->canAccessAdminArea() // isAdmin() || isSuperAdmin()
```

## Route Middleware

Didaftarkan di `bootstrap/app.php`:

```php
'role'       => EnsureUserHasRole::class
'permission' => EnsureUserHasPermission::class
```

Penggunaan di `routes/web.php`:
```php
Route::middleware(['auth', 'verified', 'role:admin'])->group(...)
Route::middleware(['auth', 'verified', 'role:asesor'])->group(...)
Route::middleware(['auth', 'verified', 'role:pesantren'])->group(...)
```

## Granular Permissions

Permission keys disimpan di tabel `permissions`. Didaftarkan sebagai Gate di `AuthServiceProvider::registerPermissionGates()`.

Contoh penggunaan:
```php
Gate::authorize('akreditasi.approve');
$user->hasPermission('notification.retry');
```

Default permission per role (dari `RolePermissionSeeder`):
- **Super Admin**: semua permission
- **Admin**: semua kecuali `master.role`
- **Asesor**: hanya `akreditasi.view`
- **Pesantren**: hanya `akreditasi.view`

## Policy Layer

Policy files di `app/Policies/`:

| Policy | Model | Digunakan untuk |
|--------|-------|-----------------|
| `AkreditasiPolicy` | `Akreditasi` | view, update, delete |
| `PesantrenPolicy` | `Pesantren` | view, update |
| `AsesorPolicy` | `Asesor` | view, update |
| `BandingPolicy` | `Banding` | view, update |
| `DocumentPolicy` | `Document` | view, update, delete |
| `IpmPolicy` | `Ipm` | view, update |
| `SdmPesantrenPolicy` | `SdmPesantren` | view, update |

Didaftarkan di `AuthServiceProvider::$policies`.

Gate::before di `AuthServiceProvider::boot()`:
```php
Gate::before(function ($user, $ability) {
    return $user->isSuperAdmin() ? true : null;
});
```

## SSO Flow (Muhammadiyah ID)

```
1. User klik "Login via Muhammadiyah ID"
   → GET /sso/preflight
   → Redirect ke IdP dengan state, client_id, redirect_uri

2. IdP callback
   → GET /sso/auth?code=xxx&state=yyy
   → Verifikasi state (CSRF protection)
   → Exchange code → access_token via POST ke IdP /oauth/token
   → Simpan token di session (bukan URL)
   → Redirect ke /sso/login

3. Login
   → GET /sso/login
   → Ambil token dari session
   → Fetch user info dari IdP /api/user
   → findOrCreate user di DB
   → Auth::login($user)
   → Redirect ke dashboard
```

Token disimpan encrypted di `profiles.access_token` (Laravel `encrypted` cast).

Role sync: hanya terjadi jika `user.sso_sync_role = true`. Admin bisa override role via halaman accounts.

## Session Security

Konfigurasi production (`.env.example`):
```
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```
