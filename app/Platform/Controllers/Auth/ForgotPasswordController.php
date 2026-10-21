<?php

namespace App\Platform\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Platform\Models\User;
use App\Platform\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Timebox;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    /**
     * Shown for every e-mail, existing or not, so the form cannot be used to
     * enumerate accounts.
     */
    public const GENERIC_MESSAGE = 'Se esse e-mail tiver uma conta por aqui, a gente já mandou o caminho. Olha a caixa de entrada — e o spam, por via das dúvidas.';

    public function create(): Response
    {
        return Inertia::render('auth/forgot-password');
    }

    /**
     * Inteira dentro do Timebox: conta ativa, inativa ou inexistente respondem no mesmo tempo
     * mínimo (auth.timebox_duration). Só a conta ativa chama o broker, que custa um bcrypt do
     * token (revisão dirigida do B9, achado 3; SEC-07).
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        return (new Timebox)->call(fn () => $this->sendLinkIfActive($request), (int) config('auth.timebox_duration'));
    }

    private function sendLinkIfActive(ForgotPasswordRequest $request): RedirectResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();

        if ($user?->isBackofficeUser() && $user->isActive() && $user->email) {
            // A new request replaces any pending token (Password broker deletes
            // the previous one). RESET_THROTTLED is swallowed on purpose: it
            // would tell the caller the account exists.
            Password::sendResetLink($request->only('email'));
        }

        return back()->with('success', self::GENERIC_MESSAGE);
    }
}
