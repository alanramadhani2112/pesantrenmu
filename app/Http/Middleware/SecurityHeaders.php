<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Production hardening (audit fix PR-6, extended PR-7, CSP addition PR-8).
 *
 * Menambahkan security headers dasar ke setiap response HTML.
 * Headers di-skip untuk asset / file binary supaya download kartu kendali,
 * sertifikat PDF, dan upload Livewire tetap berjalan normal.
 *
 * Content-Security-Policy:
 * - default-src 'self': semua resource harus dari origin yang sama
 * - script-src: 'unsafe-eval' + 'wasm-unsafe-eval' untuk Alpine.js/Livewire
 *   runtime. Idealnya pakai nonce-based CSP tapi itu butuh integrasi
 *   Laravel CSP middleware nonce generator + Livewire nonce capture,
 *   yang akan dikerjakan di follow-up PR peningkatan ke strict CSP.
 * - object-src 'none': blokir semua plugin (Flash, Java, ActiveX)
 * - frame-ancestors 'self': clickjacking protection (lebih kuat dari X-Frame-Options)
 * - form-action 'self': cegah form hijacking
 *
 * Headers diterapkan:
 * - Content-Security-Policy
 * - Strict-Transport-Security      : paksa HTTPS (hanya untuk request HTTPS)
 * - X-Content-Type-Options         : cegah MIME sniffing
 * - X-Frame-Options                : clickjacking fallback untuk browser lama
 * - X-XSS-Protection               : nonaktifkan XSS auditor lama (modern best practice)
 * - Referrer-Policy                : batasi info referrer ke cross-origin
 * - Permissions-Policy             : matikan API browser yang tidak dipakai
 * - Cross-Origin-Opener-Policy     : isolasi browsing context dari popup cross-origin
 * - Cross-Origin-Resource-Policy   : cegah embedding resource oleh origin lain
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($this->shouldSkip($response)) {
            return $response;
        }

        // HSTS hanya kalau request lewat HTTPS — di local http://spm_fix.test
        // browser akan abaikan, tapi kalau dipasang di http production browser
        // bisa lock site jadi HTTPS-only walau belum siap. Aman by-design.
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Content-Security-Policy: baseline proteksi XSS/data injection.
        // 'unsafe-eval' untuk Alpine.js + Livewire runtime. Upgrade ke strict nonce
        // CSP akan dikerjakan di follow-up PR setelah integrasi CSP middleware.
        // 'unsafe-inline' pada script-src diperlukan untuk:
        // - CloudFlare rocket-loader (inject inline script) — production
        // - Livewire wire:init / x-init inline expressions
        // static.cloudflareinsights.com untuk CloudFlare Web Analytics beacon.
        $csp = "default-src 'self'; ".
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' 'wasm-unsafe-eval' https://static.cloudflareinsights.com; ".
               "style-src 'self' 'unsafe-inline'; ".
               "img-src 'self' data: blob:; ".
               "font-src 'self'; ".
               "connect-src 'self' ws: wss:; ".
               "object-src 'none'; ".
               "frame-ancestors 'self'; ".
               "form-action 'self'; ".
               "base-uri 'self'";
        $response->headers->set('Content-Security-Policy', $csp);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // Nonaktifkan XSS auditor browser lama — modern browsers sudah tidak pakai,
        // dan mode=block justru bisa dieksploitasi untuk data exfiltration.
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), bluetooth=()'
        );
        // Isolasi window/tab dari cross-origin popup (mitigasi Spectre + XS-Leaks).
        // Browser mengabaikan COOP pada origin HTTP yang tidak trustworthy seperti
        // http://spm_fix.test, jadi header ini dibatasi agar console lokal tetap bersih.
        if ($this->shouldSendCrossOriginOpenerPolicy($request)) {
            $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        }
        // Cegah resource di-embed oleh origin lain tanpa izin eksplisit
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        return $response;
    }

    /**
     * Skip untuk binary/file responses supaya tidak meng-overwrite header
     * Content-Disposition / Content-Type yang sudah di-set route lain.
     */
    private function shouldSkip(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        // Hanya append header untuk text/html dan application/json (default Laravel).
        // File download (application/pdf, octet-stream, image/*) di-lewatkan.
        return ! str_starts_with($contentType, 'text/html')
            && ! str_starts_with($contentType, 'application/json')
            && $contentType !== '';
    }

    private function shouldSendCrossOriginOpenerPolicy(Request $request): bool
    {
        return $request->isSecure()
            || in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true);
    }
}
