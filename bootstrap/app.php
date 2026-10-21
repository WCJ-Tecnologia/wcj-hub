<?php

use App\Http\Middleware\EnsureBackofficeUser;
use App\Http\Middleware\EnsurePasswordIsCurrent;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\UseAppUrl;
use App\Platform\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Proxies come from TRUSTED_PROXIES via config/app.php (see the class).
        $middleware->replace(Illuminate\Http\Middleware\TrustProxies::class, TrustProxies::class);

        // Global, not web-only: a 404 without a route, /up and api/health get the headers too (B9, SEC-01).
        $middleware->append(SecurityHeaders::class);

        // In production only the APP_URL host is accepted (another Host is a 400) and every URL the
        // app generates starts with APP_URL: a forged Host never reaches an e-mail link (B9, SEC-21).
        // An empty list outside production trusts any host, so dev and e2e are unchanged.
        $middleware->trustHosts(at: fn () => app()->isProduction()
            ? ['^'.preg_quote((string) parse_url((string) config('app.url'), PHP_URL_HOST)).'$']
            : [], subdomains: false);
        $middleware->prepend(UseAppUrl::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();

            if ($user instanceof User && $user->must_change_password) {
                return route('password.change');
            }

            return route('admin.dashboard');
        });

        $middleware->alias([
            'backoffice' => EnsureBackofficeUser::class,
            'password.current' => EnsurePasswordIsCurrent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
