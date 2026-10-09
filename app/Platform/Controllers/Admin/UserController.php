<?php

namespace App\Platform\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\User;
use App\Platform\Requests\Admin\StoreUserRequest;
use App\Platform\Services\UserService;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/** Usuários (F1; N1 a N4, N11). A regra mora no UserService. */
class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $filters = [
            'search' => (string) $request->string('search'),
            'role_id' => $request->integer('role_id') ?: null,
            'show_inactive' => $request->boolean('show_inactive'),
        ];

        $viewer = $request->user();

        return Inertia::render('admin/users/index', [
            'users' => $this->users->paginate($filters)->withQueryString()->through(fn (User $user) => $this->row($user, $viewer)),
            'filters' => $filters,
            'roles' => array_map(fn (RoleEnum $role) => ['id' => $role->value, 'label' => $role->label()], RoleEnum::cases()),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        ['user' => $user, 'mailed' => $mailed] = $this->users->invite($request->validated(), $request->user());

        // A senha provisória vai só por e-mail (N1): nunca na tela.
        return $mailed
            ? back()->with('success', "Convite enviado para {$user->email}. No primeiro acesso, a pessoa troca a senha.")
            : back()->with('error', 'Usuário criado; o e-mail não pôde ser enviado. Use Reenviar convite.');
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        return $this->attempt(fn () => $this->users->update($user, $request->validated(), $request->user()), 'Usuário atualizado.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        return $this->attempt(fn () => $this->users->deactivate($user, $request->user()), "{$user->name} perdeu o acesso.");
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('reactivate', $user);

        return $this->attempt(fn () => $this->users->reactivate($user, $request->user()), "{$user->name} voltou a ter acesso.");
    }

    // Já usado (G2): toast de erro com a mensagem e nada muda, não 403 (D6 do Lucas, 07/10).
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        return $this->attempt(fn () => $this->users->delete($user, $request->user()), 'Usuário excluído.');
    }

    /** Reenviar convite (D4: a rota reset-password do template). */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resendInvite', $user);

        if (! $this->users->resendInvite($user, $request->user())) {
            return back()->with('error', 'Senha provisória gerada; o e-mail não pôde ser enviado. Use Reenviar convite.');
        }

        if ($user->is($request->user())) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('success', "Mandamos uma senha temporária para {$user->email}. Entra com ela e, na hora, escolhe uma só sua.");
        }

        return back()->with('success', "Convite reenviado para {$user->email}. {$user->firstName()} troca a senha no próximo acesso.");
    }

    /**
     * Os botões da linha vêm da UserPolicy, como em Clientes e Contatos (CR-1-08): a tela não
     * repete regra. Excluir só aparece para quem nunca entrou (F1 §04, G2).
     *
     * @return array<string, mixed>
     */
    private function row(User $user, User $viewer): array
    {
        $active = $user->isActive();

        return [
            ...$user->only(['id', 'name', 'email', 'role_id', 'daily_hours', 'ai_write_allowed', 'must_change_password']),
            'role_label' => $user->type()->label(),
            'active' => $active,
            'deactivated_at' => LocalTime::format($user->deactivated_at),
            'deactivated_by' => $user->deactivatedBy?->name,
            'last_login_at' => LocalTime::format($user->last_login_at),
            'can' => [
                'update' => $viewer->can('update', $user),
                'deactivate' => $active && $viewer->can('deactivate', $user),
                'reactivate' => ! $active && $viewer->can('reactivate', $user),
                'delete' => $user->last_login_at === null && $viewer->can('delete', $user),
                'resend_invite' => $viewer->can('resendInvite', $user),
            ],
        ];
    }
}
