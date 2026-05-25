<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Audit fix M-4: $proxies='*' memungkinkan IP spoofing via X-Forwarded-For
 * yang mem-bypass throttle login.
 *
 * Baca TRUSTED_PROXIES dari env:
 * - Production: set ke CIDR load balancer, e.g. "10.0.0.0/8" atau "192.168.1.1"
 * - Local dev: biarkan kosong → fallback ke '*' agar Valet/Laragon tetap jalan
 *
 * Contoh .env production:
 *   TRUSTED_PROXIES=10.0.0.0/8
 */
class TrustProxies extends Middleware
{
    /**
     * @var array<int, string>|string|null
     */
    protected $proxies;

    protected $headers = Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO;

    public function __construct()
    {
        // Baca dari env; fallback '*' hanya untuk local dev.
        // Di production, TRUSTED_PROXIES harus di-set ke CIDR LB yang sebenarnya.
        $this->proxies = env('TRUSTED_PROXIES', app()->environment('local') ? '*' : null);
    }
}
