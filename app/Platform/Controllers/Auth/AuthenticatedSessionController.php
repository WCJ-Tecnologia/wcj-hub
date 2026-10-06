<?php

namespace App\Platform\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * One message for every failure so the response never reveals whether the
     * account exists, is inactive or is simply being rate limited.
     */
    public const INVALID_CREDENTIALS = 'As credenciais informadas não conferem.';

    public function __construct(private readonly AuditService $audit) {}

    public function create(): Response
    {
        return Inertia::render('auth/login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => self::INVALID_CREDENTIALS]);
        }

        $user = Auth::user();

        if (! $user instanceof User || ! $user->isBackofficeUser() || ! $user->isActive()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages(['email' => self::INVALID_CREDENTIALS]);
        }

        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();
        $this->audit->log(AuditActionEnum::LOGIN, $user, null, null, $user->id);

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
