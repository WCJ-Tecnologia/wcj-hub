<?php

namespace App\Http\Middleware;

use App\Domains\Projetos\Models\Project;
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
                    'logo' => config('app.brand.logo'),
                ],
            ],
            'auth' => [
                'user' => $user instanceof User ? $this->userPayload($user) : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
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
                'manage_users' => $user->isAdmin(),
                'delete_records' => $user->isAdmin(),
                'view_audit_logs' => $user->isAdmin(),
                'reset_passwords' => $user->isAdmin(),
                // Da ProjectPolicy (F2 §09): o Comercial não entra em Projetos (C14).
                'view_projects' => $user->can('viewAny', Project::class),
                'manage_projects' => $user->can('create', Project::class),
            ],
        ];
    }
}
