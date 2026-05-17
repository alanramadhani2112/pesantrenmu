<?php

namespace App\Providers;

use App\Models\Akreditasi;
use App\Observers\AkreditasiObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\Eloquent\UserRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\AsesorRepositoryInterface::class,
            \App\Repositories\Eloquent\AsesorRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\EdpmRepositoryInterface::class,
            \App\Repositories\Eloquent\EdpmRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\IpmRepositoryInterface::class,
            \App\Repositories\Eloquent\IpmRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\SdmRepositoryInterface::class,
            \App\Repositories\Eloquent\SdmRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\DocumentRepositoryInterface::class,
            \App\Repositories\Eloquent\DocumentRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\AkreditasiRepositoryInterface::class,
            \App\Repositories\Eloquent\AkreditasiRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\MasterEdpmRepositoryInterface::class,
            \App\Repositories\Eloquent\MasterEdpmRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\PesantrenRepositoryInterface::class,
            \App\Repositories\Eloquent\PesantrenRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\RoleRepositoryInterface::class,
            \App\Repositories\Eloquent\RoleRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\RejectionRepositoryInterface::class,
            \App\Repositories\Eloquent\RejectionRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // URL::forceScheme('https');

        Akreditasi::observe(AkreditasiObserver::class);
    }
}
