<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\Client;
use App\Platform\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Leitura da lista de projetos (F2 §03, §05): só o que a pessoa vê (P5), com busca e filtros, e
 * as listas de escolha do formulário.
 */
class ProjectQuery
{
    /**
     * @param  array{search: string, client_id: ?int, manager_id: ?int, type: ?string, finished: bool}  $filters
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $like = '%'.$filters['search'].'%';

        return Project::query()
            ->visibleTo($user)
            ->with(['client:id,name', 'manager:id,name'])
            // Cards fora da etapa de papel Pronto, para a confirmação de encerrar (F2 §04).
            ->withCount(['tasks as open_cards_count' => fn (Builder $tasks) => $tasks
                ->whereNull('archived_at')
                ->whereHas('boardStage', fn (Builder $stage) => $stage->where('stage_role_id', '!=', StageRole::DONE))])
            ->when(! $filters['finished'], fn (Builder $query) => $query->whereNull('finished_at'))
            ->when($filters['search'] !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('key_prefix', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhereHas('client', fn (Builder $client) => $client->where('name', 'like', $like))))
            ->when($filters['client_id'], fn (Builder $query, int $id) => $query->where('client_id', $id))
            ->when($filters['manager_id'], fn (Builder $query, int $id) => $query->where('manager_id', $id))
            ->when($filters['type'] === 'delivery', fn (Builder $query) => $query->whereNotNull('due_on'))
            ->when($filters['type'] === 'continuous', fn (Builder $query) => $query->whereNull('due_on'))
            ->orderBy('name')
            ->paginate(20);
    }

    /**
     * Clientes e gestores dos projetos que a pessoa vê, para os filtros da lista.
     *
     * @return array{clients: list<array{id: int, name: string}>, managers: list<array{id: int, name: string}>}
     */
    public function filterOptions(User $user): array
    {
        $visible = Project::query()->visibleTo($user);

        return [
            'clients' => $this->options(Client::query()->whereIn('id', (clone $visible)->select('client_id'))),
            'managers' => $this->options(User::query()->whereIn('id', (clone $visible)->select('manager_id'))),
        ];
    }

    /**
     * O que o formulário oferece: clientes ativos (N11) e usuários ativos com papel Gestor de
     * projetos ou Administrador (P1). Só nome e id: o gestor não vê e-mail nem carga (F1 §09).
     *
     * @return array{clients: list<array{id: int, name: string}>, managers: list<array{id: int, name: string}>}
     */
    public function formOptions(): array
    {
        return [
            'clients' => $this->options(Client::query()->active()),
            'managers' => $this->options(User::query()->active()->whereIn('role_id', [RoleEnum::ADMIN->value, RoleEnum::PROJECT_MANAGER->value])),
        ];
    }

    /**
     * @param  Builder<covariant Client|User>  $query
     * @return list<array{id: int, name: string}>
     */
    private function options(Builder $query): array
    {
        return $query->orderBy('name')->get(['id', 'name'])
            ->map(fn (Client|User $row) => ['id' => $row->id, 'name' => $row->name])
            ->values()
            ->all();
    }
}
