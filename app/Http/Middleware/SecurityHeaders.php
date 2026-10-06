<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every response carries the security headers from the application itself, so
 * they survive a change of web server. Changing the CSP is an ADR.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Inline scripts (the anti-FOUC theme script and Vite's React refresh
        // preamble in dev) are allowed only through this per-request nonce.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $headers = $response->headers;

        $headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');

        if (app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $dev = app()->environment('local') ? ' http://localhost:5173 http://127.0.0.1:5173' : '';
        $devWs = app()->environment('local') ? ' ws://localhost:5173 ws://127.0.0.1:5173' : '';

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'".$dev,
            "style-src 'self' 'unsafe-inline'".$dev,
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'".$dev.$devWs,
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
        ]);
    }
}
