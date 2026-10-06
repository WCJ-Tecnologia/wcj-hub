<?php

namespace App\Platform\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Platform\Models\User;
use App\Platform\Services\SessionService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public const INVALID_LINK = 'Esse link já expirou ou não confere. Pede outro em “Esqueci minha senha”.';

    public function __construct(private readonly SessionService $sessions) {}

    public function create(Request $request, string $token): Response
    {
        return Inertia::render('auth/reset-password', [
            'email' => (string) $request->string('email'),
            'token' => $token,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $account = User::query()->where('email', $request->string('email'))->first();

        if (! $account?->isBackofficeUser() || ! $account->isActive()) {
            throw ValidationException::withMessages(['email' => self::INVALID_LINK]);
        }

        // Password::reset() validates the token with hash_equals and honours
        // auth.passwords.users.expire (60 minutes).
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'must_change_password' => false,
                ])->save();

                $this->sessions->terminateOthers($user);

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => self::INVALID_LINK]);
        }

        return redirect()
            ->route('login')
            ->with('success', 'Senha nova pronta. Entra quando quiser — a gente te espera.');
    }
}
