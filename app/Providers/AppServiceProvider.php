<?php

namespace App\Providers;

use App\Events\AkreditasiTransitioned;
use App\Events\BandingDecided;
use App\Events\BandingSubmitted;
use App\Events\PerbaikanDeadlineApproaching;
use App\Events\PerbaikanSubmitted;
use App\Events\ScoringCompleted;
use App\Events\SKIssued;
use App\Events\VisitasiScheduled;
use App\Listeners\AkreditasiNotificationListener;
use App\Models\Akreditasi;
use App\Observers\AkreditasiObserver;
use Illuminate\Support\Facades\Event;
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
        // PR-16 fix: paksa HTTPS di production untuk mencegah mixed-content
        // dan broken signed URLs saat di-deploy di belakang reverse proxy.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Akreditasi::observe(AkreditasiObserver::class);

        // =====================================================================
        // Task 12.2: Register akreditasi workflow event → notification listener
        // =====================================================================
        $listener = AkreditasiNotificationListener::class;

        Event::listen(AkreditasiTransitioned::class, [$listener, 'handleAkreditasiTransitioned']);
        Event::listen(PerbaikanSubmitted::class,     [$listener, 'handlePerbaikanSubmitted']);
        Event::listen(VisitasiScheduled::class,      [$listener, 'handleVisitasiScheduled']);
        Event::listen(ScoringCompleted::class,       [$listener, 'handleScoringCompleted']);
        Event::listen(SKIssued::class,               [$listener, 'handleSKIssued']);
        Event::listen(BandingSubmitted::class,       [$listener, 'handleBandingSubmitted']);
        Event::listen(BandingDecided::class,         [$listener, 'handleBandingDecided']);
        Event::listen(PerbaikanDeadlineApproaching::class, [$listener, 'handlePerbaikanDeadlineApproaching']);
    }
}
