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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        // Sem "lembrar de mim": users não tem remember_token (docs/banco.md, seção 4).
        if (! Auth::attempt($credentials)) {
            $this->spendTheSameTimeWithoutAccount($credentials['password']);

            throw ValidationException::withMessages(['email' => self::INVALID_CREDENTIALS]);
        }

        $user = Auth::user();

        if (! $user instanceof User || ! $user->isBackofficeUser() || ! $user->isActive()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages(['email' => self::INVALID_CREDENTIALS]);
        }

        $request->session()->regenerate();

        $this->audit->log(AuditActionEnum::LOGIN, $user, null, null, $user->id);

        if ($user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * E-mail sem conta: o provider nem chega ao Hash::check, e a resposta sairia mais rápido que a
     * de senha errada. Confere contra um hash descartável com o custo atual (seguranca.md §3; SEC-06).
     * O hash fica no cache para não custar um Hash::make a cada tentativa.
     */
    private function spendTheSameTimeWithoutAccount(string $password): void
    {
        if (Auth::guard('web')->getLastAttempted() !== null) {
            return;
        }

        $rounds = (int) config('hashing.bcrypt.rounds', 12);
        $dummy = Cache::rememberForever("auth.dummy_hash.bcrypt.{$rounds}", fn () => Hash::make(Str::random(40)));

        Hash::check($password, $dummy);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
