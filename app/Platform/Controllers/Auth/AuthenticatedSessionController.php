<?php

namespace App\Platform\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Requests\Auth\LoginRequest;
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

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        // Sem "lembrar de mim": users não tem remember_token (docs/banco.md, seção 4). A conta
        // desativada ou sem papel de backoffice nem chega a logar: o id da sessão não muda, e a
        // recusa, de qualquer tipo, leva o tempo do Timebox (auth.timebox_duration; B9, SEC-06 e SEC-22).
        $accepted = Auth::attemptWhen(
            $credentials,
            fn (User $user) => $user->isBackofficeUser() && $user->isActive(),
        );

        if (! $accepted) {
            throw ValidationException::withMessages(['email' => self::INVALID_CREDENTIALS]);
        }

        /** @var User $user */
        $user = Auth::user();

        $request->session()->regenerate();

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
