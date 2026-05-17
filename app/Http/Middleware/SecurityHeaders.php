<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Production hardening (audit fix PR-6, extended PR-7).
 *
 * Menambahkan security headers dasar ke setiap response HTML.
 * Headers di-skip untuk asset / file binary supaya download kartu kendali,
 * sertifikat PDF, dan upload Livewire tetap berjalan normal.
 *
 * Catatan: Content-Security-Policy SENGAJA tidak ditambahkan di sini.
 * CSP strict bentrok dengan inline scripts Livewire/Volt + Pusher dan
 * butuh konfigurasi nonce per-request. Itu akan ditangani follow-up
 * tersendiri (audit P0 berbeda — C-1 SSO inline script harus dipindah
 * dulu sebelum CSP bisa diaktifkan tanpa breakage).
 *
 * Headers:
 * - Strict-Transport-Security      : paksa HTTPS (hanya untuk request HTTPS)
 * - X-Content-Type-Options         : cegah MIME sniffing
 * - X-Frame-Options                : cegah clickjacking via <iframe>
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
        // Isolasi window/tab dari cross-origin popup (mitigasi Spectre + XS-Leaks)
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
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
}
