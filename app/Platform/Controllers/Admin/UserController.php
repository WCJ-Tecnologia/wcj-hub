<?php

namespace App\Platform\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\User;
use App\Platform\Requests\Admin\StoreUserRequest;
use App\Platform\Services\AuditService;
use App\Platform\Services\TemporaryPasswordService;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tela de usuários do template, ajustada à seção 9 do banco.txt (papel e carga diária). A
 * tela completa, com convite, conexões de IA e o resto das regras N1 a N4, é do B1.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly TemporaryPasswordService $passwords,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = (string) $request->string('search');
        $role = $request->integer('role_id');
        $viewer = $request->user();

        $users = User::query()
            ->with('deactivatedBy:id,name')
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($role > 0, fn ($query) => $query->where('role_id', $role))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => $user->isActive(),
                // Who deactivated only reaches whoever can edit (seguranca.md §6).
                ...($viewer->can('update', $user) ? [
                    'deactivated_at' => LocalTime::format($user->deactivated_at),
                    'deactivated_by' => $user->deactivatedBy?->name,
                ] : []),
                'role_id' => $user->role_id,
                'role_label' => $user->type()->label(),
                'daily_hours' => $user->daily_hours,
                'must_change_password' => $user->must_change_password,
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => ['search' => $search, 'role_id' => $role ?: null],
            'roles' => collect(RoleEnum::cases())->map(fn (RoleEnum $r) => [
                'id' => $r->value,
                'label' => $r->label(),
            ]),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $actor = $request->user();

        // users.password é not null (N1): a senha provisória nasce junto com a linha.
        [$user, $plain] = DB::transaction(function () use ($data, $actor) {
            $user = new User([
                'role_id' => $data['role_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'daily_hours' => $data['daily_hours'] ?? null,
            ]);
            $user->setActive((bool) ($data['active'] ?? true), $actor);

            return [$user, $this->passwords->issue($user)];
        });
        $this->passwords->send($user, $plain, isReset: false);

        $this->audit->log(AuditActionEnum::CREATED, $user, null, $this->snapshot($user));

        return back()->with(
            'success',
            'Pronto. Mandamos uma senha temporária para '.$user->email.'. No primeiro acesso, a pessoa troca.',
        );
    }

    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        $before = $this->snapshot($user);
        $active = (bool) ($data['active'] ?? $user->isActive());

        if (! ((int) $data['role_id'] === RoleEnum::ADMIN->value && $active) && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Este é o último administrador ativo. Promova outra pessoa antes de alterar o papel.');
        }

        $user->fill([
            'role_id' => $data['role_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'daily_hours' => $data['daily_hours'] ?? null,
        ]);

        $user->setActive($active, $request->user())->save();

        $this->audit->log(AuditActionEnum::UPDATED, $user, $before, $this->snapshot($user));

        return back()->with('success', 'Usuário atualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Este é o último administrador ativo. Promova outra pessoa antes de excluir.');
        }

        $before = $this->snapshot($user);

        $deleted = $user->deleteIfUnused(function (User $deleted) use ($before) {
            Password::broker()->deleteToken($deleted);
            $this->audit->log(AuditActionEnum::DELETED, $deleted, $before, null);
        });

        if (! $deleted) {
            return back()->with('error', 'Esta pessoa já usou o sistema. Para tirar o acesso, edite e desmarque "Usuário ativo".');
        }

        return back()->with('success', 'Usuário excluído.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('resendInvite', $user);

        $isSelf = $user->is($request->user());

        $this->passwords->send($user, $this->passwords->issue($user), isReset: true);

        $this->audit->log(AuditActionEnum::PASSWORD_RESET, $user, null, [
            'must_change_password' => true,
        ]);

        if ($isSelf) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('success', "Mandamos uma senha temporária para {$user->email}. Entra com ela e, na hora, escolhe uma só sua.");
        }

        return back()->with(
            'success',
            "Mandamos uma senha temporária para {$user->email}. {$user->firstName()} troca ela no próximo acesso.",
        );
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if (! $user->isAdmin() || ! $user->isActive()) {
            return false;
        }

        return ! User::query()
            ->active()
            ->where('role_id', RoleEnum::ADMIN->value)
            ->whereKeyNot($user->getKey())
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(User $user): array
    {
        return [...$user->only(['name', 'email', 'role_id', 'daily_hours']), 'active' => $user->isActive()];
    }
}
