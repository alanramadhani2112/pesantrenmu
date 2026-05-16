<?php

namespace App\Providers;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Banding;
use App\Models\Document;
use App\Models\Ipm;
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

/**
 * Wires multi-tenant policies (audit fix H-2 P0).
 *
 * Super admin (role_id=4) gets unconditional pass via Gate::before.
 * Other roles fall through to per-policy methods.
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
    }
}
