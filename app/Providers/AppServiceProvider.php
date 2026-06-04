<?php

namespace App\Providers;

use App\Events\AkreditasiTransitioned;
use App\Events\AsesorAssigned;
use App\Events\AsesorPackageSubmitted;
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
use App\Repositories\Contracts\AkreditasiRepositoryInterface;
use App\Repositories\Contracts\AsesorRepositoryInterface;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Repositories\Contracts\EdpmRepositoryInterface;
use App\Repositories\Contracts\IpmRepositoryInterface;
use App\Repositories\Contracts\MasterEdpmRepositoryInterface;
use App\Repositories\Contracts\PesantrenRepositoryInterface;
use App\Repositories\Contracts\RejectionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\SdmRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\AkreditasiRepository;
use App\Repositories\Eloquent\AsesorRepository;
use App\Repositories\Eloquent\DocumentRepository;
use App\Repositories\Eloquent\EdpmRepository;
use App\Repositories\Eloquent\IpmRepository;
use App\Repositories\Eloquent\MasterEdpmRepository;
use App\Repositories\Eloquent\PesantrenRepository;
use App\Repositories\Eloquent\RejectionRepository;
use App\Repositories\Eloquent\RoleRepository;
use App\Repositories\Eloquent\SdmRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );
        $this->app->bind(
            AsesorRepositoryInterface::class,
            AsesorRepository::class
        );
        $this->app->bind(
            EdpmRepositoryInterface::class,
            EdpmRepository::class
        );
        $this->app->bind(
            IpmRepositoryInterface::class,
            IpmRepository::class
        );
        $this->app->bind(
            SdmRepositoryInterface::class,
            SdmRepository::class
        );
        $this->app->bind(
            DocumentRepositoryInterface::class,
            DocumentRepository::class
        );
        $this->app->bind(
            AkreditasiRepositoryInterface::class,
            AkreditasiRepository::class
        );
        $this->app->bind(
            MasterEdpmRepositoryInterface::class,
            MasterEdpmRepository::class
        );
        $this->app->bind(
            PesantrenRepositoryInterface::class,
            PesantrenRepository::class
        );
        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );
        $this->app->bind(
            RejectionRepositoryInterface::class,
            RejectionRepository::class
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

        // =================================================================
        // Production hardening: rate limiting (2026-06-04)
        // =================================================================

        // Livewire state updates — 120 req/min per user (cukup untuk
        // interaksi normal, cukup rendah untuk mencegah brute-force).
        RateLimiter::for('livewire', function (Request $request) {
            return Limit::perMinute(120)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // File upload via Livewire — 20 req/min per user.
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(20)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // Global web — 200 req/min per IP for unauthenticated, 300/min for authenticated.
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(
                $request->user() ? 300 : 200
            )->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        Akreditasi::observe(AkreditasiObserver::class);

        // =====================================================================
        // Task 12.2: Register akreditasi workflow event → notification listener
        // =====================================================================
        $listener = AkreditasiNotificationListener::class;

        Event::listen(AkreditasiTransitioned::class, [$listener, 'handleAkreditasiTransitioned']);
        Event::listen(PerbaikanSubmitted::class, [$listener, 'handlePerbaikanSubmitted']);
        Event::listen(VisitasiScheduled::class, [$listener, 'handleVisitasiScheduled']);
        Event::listen(ScoringCompleted::class, [$listener, 'handleScoringCompleted']);
        Event::listen(SKIssued::class, [$listener, 'handleSKIssued']);
        Event::listen(BandingSubmitted::class, [$listener, 'handleBandingSubmitted']);
        Event::listen(BandingDecided::class, [$listener, 'handleBandingDecided']);
        Event::listen(PerbaikanDeadlineApproaching::class, [$listener, 'handlePerbaikanDeadlineApproaching']);
        Event::listen(AsesorAssigned::class, [$listener, 'handleAsesorAssigned']);
        Event::listen(AsesorPackageSubmitted::class, [$listener, 'handleAsesorPackageSubmitted']);
    }
}
