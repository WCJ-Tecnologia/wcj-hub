<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Second gate after `auth`: only an active user with a backoffice role reaches
 * the panel. A session whose account was deactivated is closed on the spot.
 */
class EnsureBackofficeUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isBackofficeUser() || ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403, 'Este acesso é restrito ao Backoffice.');
        }

        return $next($request);
    }
}
