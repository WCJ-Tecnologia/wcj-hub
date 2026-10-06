<?php

namespace App\Platform\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Enums\UserTypeEnum;
use App\Platform\Models\Enterprise;
use App\Platform\Models\User;
use App\Platform\Requests\Admin\StoreUserRequest;
use App\Platform\Services\AuditService;
use App\Platform\Services\Scoping;
use App\Platform\Services\TemporaryPasswordService;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Scoping $scoping,
        private readonly TemporaryPasswordService $passwords,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = (string) $request->string('search');
        $type = $request->integer('user_type_id');
        $viewer = $request->user();

        $users = User::query()
            ->with(['enterprises:id,nickname', 'deactivatedBy:id,name'])
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%");
            }))
            ->when($type > 0, fn ($query) => $query->where('user_type_id', $type))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'document' => $user->document,
                'phone' => $user->phone,
                'active' => $user->isActive(),
                // Who deactivated only reaches whoever can edit (seguranca.md §6).
                ...($viewer->can('update', $user) ? [
                    'deactivated_at' => LocalTime::format($user->deactivated_at),
                    'deactivated_by' => $user->deactivatedBy?->name,
                ] : []),
                'user_type_id' => $user->user_type_id,
                'type_label' => $user->type()->label(),
                'last_login_at' => LocalTime::format($user->last_login_at),
                'must_change_password' => $user->must_change_password,
                'enterprises' => $user->enterprises->map(fn (Enterprise $e) => [
                    'id' => $e->id,
                    'nickname' => $e->nickname,
                ]),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => ['search' => $search, 'user_type_id' => $type ?: null],
            'userTypes' => collect(UserTypeEnum::cases())->map(fn (UserTypeEnum $t) => [
                'id' => $t->value,
                'label' => $t->label(),
            ]),
            'enterpriseOptions' => $this->scoping->enterpriseOptions($request->user()),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $actor = $request->user();

        $user = DB::transaction(function () use ($data, $actor) {
            $user = new User([
                'user_type_id' => $data['user_type_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'document' => $data['document'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => null,
            ]);
            $user->setActive((bool) ($data['active'] ?? true), $actor)->save();

            $this->syncEnterprises($user, $data['enterprise_ids'] ?? []);
            $this->passwords->issue($user, isReset: false);

            return $user;
        });

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

        $staysAdmin = (int) $data['user_type_id'] === UserTypeEnum::ADMIN->value && $active;

        if (! $staysAdmin && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Este é o último administrador ativo. Promova outra pessoa antes de alterar o perfil.');
        }

        $user->fill([
            'user_type_id' => $data['user_type_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'document' => $data['document'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        $user->setActive($active, $request->user())->save();

        $this->syncEnterprises($user, $data['enterprise_ids'] ?? []);

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
        $this->authorize('resetPassword', $user);

        $isSelf = $user->is($request->user());

        $this->passwords->issue($user, isReset: true);

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

    /**
     * Only the Financial role is scoped by enterprise.
     *
     * @param  list<int>  $enterpriseIds
     */
    private function syncEnterprises(User $user, array $enterpriseIds): void
    {
        $user->enterprises()->sync($user->isFinancial() ? $enterpriseIds : []);
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if (! $user->isAdmin() || ! $user->isActive()) {
            return false;
        }

        return ! User::query()
            ->active()
            ->where('user_type_id', UserTypeEnum::ADMIN->value)
            ->whereKeyNot($user->getKey())
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(User $user): array
    {
        return [...$user->only(['name', 'email', 'user_type_id']), 'active' => $user->isActive()];
    }
}
