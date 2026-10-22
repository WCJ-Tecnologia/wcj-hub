<?php

namespace App\Http\Responses;

use App\Http\Middleware\HandleInertiaRequests;
use App\Platform\Controllers\Admin\ProfileController;
use App\Platform\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * What the "login" rate limiter answers once the quota is spent. The page is
 * re-rendered with status 429 and the same message used for a wrong password,
 * so the response leaks neither the account's existence nor the limit itself.
 * The Inertia client renders any response carrying the X-Inertia header,
 * whatever the status code.
 */
class ThrottledAuthResponse
{
    /**
     * @var array<string, string>
     */
    private const COMPONENTS = [
        'login.store' => 'auth/login',
        'password.email' => 'auth/forgot-password',
        'password.store' => 'auth/reset-password',
        'password.change.update' => 'auth/change-password',
        'admin.profile.update' => 'admin/profile/index',
    ];

    /**
     * @param  array<string, mixed>  $headers
     */
    public function __invoke(Request $request, array $headers = []): Response
    {
        $message = AuthenticatedSessionController::INVALID_CREDENTIALS;
        $component = self::COMPONENTS[$request->route()?->getName() ?? ''] ?? null;

        if ($component === null || ! $request->inertia()) {
            return response($message, Response::HTTP_TOO_MANY_REQUESTS, $headers);
        }

        // ThrottleRequests sits above HandleInertiaRequests in Laravel's
        // middleware priority, so the shared props (app, auth, flash, errors)
        // have not been registered yet. Share them here or the page breaks.
        $inertia = app(HandleInertiaRequests::class);
        Inertia::version(fn () => $inertia->version($request));
        Inertia::share($inertia->share($request));

        $field = match ($component) {
            'auth/change-password' => 'password',
            'admin/profile/index' => 'current_password',
            default => 'email',
        };

        $response = Inertia::render($component, [
            ...$this->componentProps($component, $request),
            'errors' => [$field => $message],
        ])->toResponse($request);

        $response->setStatusCode(Response::HTTP_TOO_MANY_REQUESTS);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function componentProps(string $component, Request $request): array
    {
        return match ($component) {
            'auth/reset-password' => [
                'email' => is_string($request->input('email')) ? $request->input('email') : '',
                'token' => (string) $request->string('token'),
            ],
            'auth/change-password' => [
                'firstName' => $request->user()?->firstName() ?? '',
            ],
            'admin/profile/index' => ProfileController::pageProps($request->user()),
            default => [],
        };
    }
}
