<?php

namespace App\Providers;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Banding;
use App\Models\Document;
use App\Models\Ipm;
use App\Models\Permission;
use App\Models\Pesantren;
use App\Models\SdmPesantren;
use App\Policies\AkreditasiPolicy;
use App\Policies\AsesorPolicy;
use App\Policies\BandingPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\IpmPolicy;
use App\Policies\PesantrenPolicy;
use App\Policies\SdmPesantrenPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Wires multi-tenant policies (audit fix H-2 P0).
 *
 * Super admin (role_id=4) gets unconditional pass via Gate::before.
 * Other roles fall through to per-policy methods.
 *
 * All permissions from the `permissions` table are registered as Gates,
 * enabling `Gate::allows('akreditasi.approve')` checks throughout the app.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Akreditasi::class => AkreditasiPolicy::class,
        Asesor::class => AsesorPolicy::class,
        Banding::class => BandingPolicy::class,
        Document::class => DocumentPolicy::class,
        Ipm::class => IpmPolicy::class,
        Pesantren::class => PesantrenPolicy::class,
        SdmPesantren::class => SdmPesantrenPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Super admin god-mode. Returning null lets the next callback run, so
        // a true super admin always wins; everyone else falls through to the
        // policy method which makes the actual decision.
        Gate::before(function ($user, $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        $this->registerPermissionGates();
    }

    /**
     * Dynamically register a Gate for every permission stored in the database.
     *
     * This allows using `Gate::allows('akreditasi.approve')` or the `@can`
     * Blade directive with any permission key. The callback delegates to
     * `User::hasPermission()` which checks the role_permission pivot.
     *
     * Wrapped in try/catch to handle cases where the permissions table
     * doesn't exist yet (e.g. during fresh migrations or testing).
     */
    private function registerPermissionGates(): void
    {
        try {
            if (! Schema::hasTable('permissions')) {
                return;
            }

            Permission::pluck('key')->each(function (string $permissionKey) {
                Gate::define($permissionKey, function ($user) use ($permissionKey) {
                    return $user->hasPermission($permissionKey);
                });
            });
        } catch (\Throwable $e) {
            // During migrations or when the DB is unavailable, silently skip
            // gate registration. Log for debugging in non-testing environments.
            if (app()->environment('production', 'staging', 'local')) {
                Log::warning('Unable to register permission gates: '.$e->getMessage());
            }
        }
    }
}
