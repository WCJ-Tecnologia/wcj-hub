<?php

use App\Http\Middleware\EnsureBackofficeUser;
use App\Http\Middleware\EnsurePasswordIsCurrent;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustProxies;
use App\Platform\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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

        $middleware->web(append: [
            SecurityHeaders::class,
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
        // Prova do MCP: /mcp e o OAuth respondem JSON (401 sem token, nunca 302 para o login).
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('mcp', 'mcp/*', 'oauth/token', 'oauth/register') || $request->expectsJson()
        );
    })->create();
