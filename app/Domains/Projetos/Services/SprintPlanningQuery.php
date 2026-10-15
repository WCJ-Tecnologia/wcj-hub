<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Platform\Models\User;

/**
 * O que o *Montar sprint* do quadro precisa para planejar (D-GER-35; F5 §01 "Horas na montagem" e
 * "Capacidade"; P27, P48, P49): as pessoas ativas alocadas no projeto, que podem ser dev ou QA (P5),
 * com a carga diária (N3); e, com sprint aberta, ela e o painel dela pela CapacityQuery (o que já
 * falta de cada dev). A tela soma ao vivo o plano em cima disso (`planCapacity` em lib/quadro.ts,
 * a mesma conta). Só para quem põe card na sprint: o B3 manda nas props do quadro com
 * `abilities.manage_sprint`. Uma consulta para as pessoas, mais as do painel.
 */
class SprintPlanningQuery
{
    public function __construct(
        private readonly SprintService $sprints,
        private readonly CapacityQuery $capacity,
    ) {}

    /**
     * @return array{
     *     people: list<array{id: int, name: string, daily_hours: ?float, role: string}>,
     *     open_sprint: array{id: int, name: string, starts_on: string, ends_on: string, capacity: array<string, mixed>}|null,
     * }
     */
    public function forProject(Project $project): array
    {
        $people = User::query()
            ->active()
            ->whereIn('id', $project->members()->select('user_id'))
            ->with('role:id,slug')
            ->orderBy('name')
            ->get(['id', 'name', 'daily_hours', 'role_id'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'daily_hours' => $user->daily_hours === null ? null : (float) $user->daily_hours,
                'role' => (string) $user->role->slug,
            ])
            ->all();

        $open = $this->sprints->openSprint($project->id);

        return [
            'people' => $people,
            'open_sprint' => $open === null ? null : $this->openSprint($open),
        ];
    }

    /** @return array{id: int, name: string, starts_on: string, ends_on: string, capacity: array<string, mixed>} */
    private function openSprint(Sprint $sprint): array
    {
        return [
            'id' => $sprint->id,
            'name' => $sprint->name,
            'starts_on' => $sprint->starts_on->toDateString(),
            'ends_on' => $sprint->ends_on->toDateString(),
            'capacity' => $this->capacity->forSprint($sprint),
        ];
    }
}
