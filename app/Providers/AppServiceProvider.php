<?php

namespace App\Providers;

use App\Http\Responses\ThrottledAuthResponse;
use App\Models\Enterprise;
use App\Models\User;
use App\Policies\EnterprisePolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerRateLimiters();
        $this->registerPasswordPolicy();
    }

    /**
     * "login" guards every credential endpoint: login, forgot, reset and first
     * access. Two keys: IP + account, and a ceiling per IP. Limits live in
     * config/auth.php so a test environment can loosen the per-IP ceiling.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $account = Str::lower(trim((string) $request->input('email', $request->user()?->email ?? '')));
            $response = fn (Request $request, array $headers) => (new ThrottledAuthResponse)($request, $headers);

            return [
                Limit::perMinute((int) config('auth.throttle.per_account', 5))
                    ->by('login:'.$request->ip().'|'.$account)
                    ->response($response),
                Limit::perMinute((int) config('auth.throttle.per_ip', 20))
                    ->by('login-ip:'.$request->ip())
                    ->response($response),
            ];
        });
    }

    private function registerPasswordPolicy(): void
    {
        Password::defaults(fn () => Password::min(10)->letters()->numbers());
    }

    private function registerPolicies(): void
    {
        Gate::policy(Enterprise::class, EnterprisePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('view-audit-logs', fn (User $user) => $user->isAdmin());
    }
}
