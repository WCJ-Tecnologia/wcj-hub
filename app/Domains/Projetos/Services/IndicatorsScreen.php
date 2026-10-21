<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Platform\Models\User;
use App\Support\DiasUteis;

/**
 * O que a tela `/projetos/{id}/indicadores` recebe (F7 §01 a §06): as abas que a pessoa pode ver,
 * os filtros resolvidos, as opções e o conteúdo da aba pedida (as outras vão nulas). O gestor e o
 * Administrador têm as cinco abas e *Minhas tarefas e horas*; o dev, só a dele (P54, F7 §10.2).
 * O conteúdo é do SituationIndicators e do EffortIndicators.
 */
class IndicatorsScreen
{
    public const TABS = ['situacao', 'tempo', 'horas', 'pessoas', 'qualidade', 'minhas'];

    public function __construct(
        private readonly IndicatorsQuery $query,
        private readonly SituationIndicators $situation,
        private readonly EffortIndicators $effort,
    ) {}

    /**
     * @param  array{tab: ?string, search: string, sprint: ?string, developer_id: ?int, type_id: ?int, area_id: ?int, due: ?string, until: ?string}  $filters
     * @return array<string, mixed>
     */
    public function props(Project $project, User $user, array $filters): array
    {
        $project->load('client:id,name');
        $tabs = $user->can('viewIndicators', $project) ? self::TABS : ['minhas'];
        $tab = in_array($filters['tab'], $tabs, true) ? $filters['tab'] : $tabs[0];
        $options = $this->query->options($project);
        $filters = [...$filters, 'tab' => $tab, 'sprint' => $this->resolveSprint($filters['sprint'], $options['sprints'])];
        $today = DiasUteis::today();

        return [
            'project' => [
                'id' => $project->id,
                'key_prefix' => $project->key_prefix,
                'name' => $project->name,
                'client' => $project->client->name,
                'finished' => $project->isFinished(),
            ],
            'tabs' => $tabs,
            'filters' => $filters,
            'options' => $options,
            'today' => $today,
            'situation' => $tab === 'situacao' ? $this->situation->situation($project, $today) : null,
            'stage_time' => $tab === 'tempo' ? $this->situation->stageTime($project, $filters) : null,
            'hours' => $tab === 'horas' ? $this->effort->hours($project, $filters) : null,
            'people' => $tab === 'pessoas' ? $this->effort->people($project, $filters, $today) : null,
            'quality' => $tab === 'qualidade' ? $this->situation->quality($project) : null,
            'mine' => $tab === 'minhas' ? $this->effort->mine($project, $user, $filters, $today) : null,
        ];
    }

    /**
     * O filtro de sprint que vale (F7 §05): o id pedido, se é do projeto; "todas" ou "fora"; sem
     * pedido, a sprint em andamento, e sem sprint aberta, todas.
     *
     * @param  list<array{id: int, name: string, open: bool}>  $sprints
     */
    private function resolveSprint(?string $asked, array $sprints): string
    {
        if ($asked === IndicatorsQuery::ALL_SPRINTS || $asked === IndicatorsQuery::NO_SPRINT) {
            return $asked;
        }
        if ($asked !== null && ctype_digit($asked) && in_array((int) $asked, array_column($sprints, 'id'), true)) {
            return $asked;
        }
        $open = array_values(array_filter($sprints, fn (array $sprint) => $sprint['open']));

        return $open === [] ? IndicatorsQuery::ALL_SPRINTS : (string) $open[0]['id'];
    }
}
