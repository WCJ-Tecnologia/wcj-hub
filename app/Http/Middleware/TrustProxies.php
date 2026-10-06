<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;

/**
 * Rate limiting by IP only works when the application sees the real client
 * address. The list comes from TRUSTED_PROXIES (comma separated, or "*" behind
 * a load balancer you control). It is read here instead of bootstrap/app.php
 * because .env is not loaded yet when the middleware stack is configured.
 */
class TrustProxies extends Middleware
{
    /**
     * @return array<int, string>|string|null
     */
    protected function proxies()
    {
        $configured = config('app.trusted_proxies');

        if ($configured === null || $configured === '') {
            return null;
        }

        if (is_array($configured)) {
            return $configured;
        }

        $configured = trim((string) $configured);

        if ($configured === '*' || $configured === '**') {
            return $configured;
        }

        return array_values(array_filter(array_map('trim', explode(',', $configured))));
    }
}
