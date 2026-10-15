<?php

namespace App\Http\Middleware;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ReworkReason;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'brand' => [
                    'name' => config('app.brand.name'),
                ],
            ],
            'auth' => [
                'user' => $user instanceof User ? $this->userPayload($user) : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                // Salvou, mas há o que conferir (ex.: e-mail de contato repetido, N8).
                'warning' => fn () => $request->session()->get('warning'),
                // O id do registro que a ação acabou de criar, para a tela que precisa escolhê-lo
                // em seguida (o "Novo cliente" do formulário do projeto, N6).
                'created_id' => fn () => $request->session()->get('created_id'),
            ],
        ];
    }

    /**
     * Abilities are the only thing the front uses to show or hide UI. The real
     * decision lives in the Policies; keep the two in sync in the same commit.
     *
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->type()->slug(),
            'role_label' => $user->type()->label(),
            'must_change_password' => $user->must_change_password,
            'abilities' => [
                'manage_users' => $user->can('viewAny', User::class),
                'delete_records' => $user->isAdmin(),
                'view_audit_logs' => $user->can('viewAny', AuditLog::class),
                'reset_passwords' => $user->isAdmin(),
                // Menu Administração (F1 §09): o gestor vê clientes e os contatos dos clientes dele.
                'view_clients' => $user->can('viewAny', Client::class),
                'view_contacts' => $user->can('viewAny', Contact::class),
                // Da ProjectPolicy (F2 §09): o Comercial não entra em Projetos (C14).
                'view_projects' => $user->can('viewAny', Project::class),
                'manage_projects' => $user->can('create', Project::class),
                // Menu Administração → Listas (F2 §09, G7): só o Administrador edita as listas gerais.
                'manage_lists' => $user->can('viewAny', ReworkReason::class),
            ],
        ];
    }
}
