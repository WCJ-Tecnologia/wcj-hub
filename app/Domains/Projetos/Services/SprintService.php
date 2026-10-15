<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\Sprint;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use App\Platform\Services\Concerns\RefusesDuplicates;
use App\Support\DiasUteis;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sprint (P45–P51; docs/banco.md, sprints e "Regras do Service"). Toda escrita trava primeiro a
 * linha do projeto, depois a sprint, depois as tarefas, sempre nessa ordem: é assim que "uma
 * sprint aberta por projeto" (P45) vale mesmo com duas pessoas ao mesmo tempo, sem coluna nem
 * índice no banco. Nada aqui recusa por capacidade, horas, dev ou etapa (P49, premissa do
 * kanban): só por regra de estado, dado inválido e integridade. Toda escrita audita na mesma
 * transação (G3).
 * Pôr e tirar card é do SprintTaskService; a troca de sprint e etapa, do SprintTaskMover.
 */
class SprintService
{
    use RefusesDuplicates;

    public const DESTINATION_NEXT = 'next';

    public const DESTINATION_BACKLOG = 'backlog';

    private const NAME_UNIQUE = 'sprints_project_id_name_unique';

    public function __construct(
        private readonly AuditService $audit,
        private readonly SprintTaskMover $mover,
    ) {}

    /**
     * A sprint que a tela propõe (P46, R5): "Sprint N" (N = sprints que o projeto já teve + 1),
     * início hoje (ou o dia dado) e fim pela duração padrão, contando o início como 1º dia útil.
     *
     * @return array{name: string, goal: string, starts_on: string, ends_on: string}
     */
    public function proposal(Project $project, ?string $startsOn = null, int $offset = 1): array
    {
        $start = $startsOn ?? DiasUteis::today();

        return [
            'name' => 'Sprint '.($project->sprints()->count() + $offset),
            'goal' => '',
            'starts_on' => $start,
            'ends_on' => DiasUteis::add($start, $project->sprint_days),
        ];
    }

    /**
     * A próxima sprint do encerramento (P50): começa no 1º dia útil depois do fim da que fecha.
     *
     * @return array{name: string, goal: string, starts_on: string, ends_on: string}
     */
    public function nextProposal(Sprint $sprint): array
    {
        return $this->proposal($sprint->project, DiasUteis::next($sprint->ends_on->toDateString()));
    }

    /** @param  array{name: string, goal: ?string, starts_on: string, ends_on: string}  $data */
    public function create(Project $project, array $data, User $actor): Sprint
    {
        return $this->writeSprint($data, fn () => DB::transaction(function () use ($project, $data, $actor) {
            $locked = $this->lockProject($project);
            $this->assertNoOtherOpen($locked);

            return $this->insertLocked($locked, $data, $actor);
        }));
    }

    /**
     * Nome, objetivo e datas, aberta ou encerrada (F5 §04). Mudar as datas muda os dias úteis e a
     * capacidade; não mexe em tarefa.
     *
     * @param  array{name: string, goal: ?string, starts_on: string, ends_on: string}  $data
     */
    public function update(Sprint $sprint, array $data, User $actor): Sprint
    {
        return $this->writeSprint($data, fn () => DB::transaction(function () use ($sprint, $data, $actor) {
            $locked = $this->lockSprint($sprint);
            $before = $this->snapshot($locked);
            $locked->fill($this->fields($data))->save();

            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked), $actor->id);
            }

            return $locked;
        }));
    }

    /**
     * Encerrar (P50, P51). As tarefas que não chegaram a etapa de papel done (e não arquivadas,
     * P26) vão para a próxima sprint (padrão) ou voltam ao Backlog, como $destinations diz por id;
     * a que não vier no mapa vai para a próxima. As prontas ficam nesta. A próxima nasce com $next
     * e é obrigatória quando alguma tarefa vai para ela; sem tarefa indo, $next nulo = não criar.
     * Voltar ao Backlog não é retrabalho.
     *
     * @param  array<int, string>  $destinations  id da tarefa => 'next' | 'backlog'
     * @param  array{name: string, goal: ?string, starts_on: string, ends_on: string}|null  $next
     * @return array{next: ?Sprint, next_count: int, backlog_count: int}
     */
    public function close(Sprint $sprint, array $destinations, ?array $next, User $actor): array
    {
        return $this->writeSprint($next ?? [], fn () => DB::transaction(function () use ($sprint, $destinations, $next, $actor) {
            $locked = $this->lockSprint($sprint);
            if ($locked->closed_at !== null) {
                throw new DomainException("A {$locked->name} já foi encerrada.");
            }

            $pending = $this->pendingTasks($locked);
            $toNext = $pending->filter(fn (Task $task) => ($destinations[$task->id] ?? self::DESTINATION_NEXT) !== self::DESTINATION_BACKLOG);
            $toBacklog = $pending->diff($toNext);

            if ($toNext->isNotEmpty() && $next === null) {
                throw ValidationException::withMessages(['next' => 'Tarefas vão para a próxima sprint: preencha a próxima sprint.']);
            }

            $before = $this->snapshot($locked);
            $locked->forceFill(['closed_at' => now()])->save();
            $this->audit->log(AuditActionEnum::FINISHED, $locked, $before, $this->snapshot($locked), $actor->id);

            $nextSprint = $next === null ? null : $this->insertLocked($locked->project, $next, $actor);
            $queue = $this->mover->roleStage($locked->project_id, StageRole::QUEUE);

            foreach ($toNext as $task) {
                $this->mover->relink($task, $nextSprint, null, $actor);
            }
            foreach ($toBacklog as $task) {
                $this->mover->relink($task, null, $this->mover->isSupport($task) ? null : $queue, $actor);
            }

            return ['next' => $nextSprint, 'next_count' => $toNext->count(), 'backlog_count' => $toBacklog->count()];
        }));
    }

    /** Reabrir (P50): só sem outra aberta no projeto (P45); não traz de volta tarefa nenhuma. */
    public function reopen(Sprint $sprint, User $actor): Sprint
    {
        return DB::transaction(function () use ($sprint, $actor) {
            $locked = $this->lockSprint($sprint);
            if ($locked->closed_at === null) {
                return $locked;
            }

            $open = $this->openSprint($locked->project_id);
            if ($open !== null) {
                throw new DomainException("Encerre a {$open->name} antes de reabrir a {$locked->name}.");
            }

            $before = $this->snapshot($locked);
            $locked->forceFill(['closed_at' => null])->save();
            $this->audit->log(AuditActionEnum::REOPENED, $locked, $before, $this->snapshot($locked), $actor->id);

            return $locked;
        });
    }

    /**
     * Exclusão de verdade só da sprint aberta sem tarefa, ex.: criada por engano (G2, F5 §04).
     * Conferido com as linhas travadas.
     */
    public function delete(Sprint $sprint, User $actor): void
    {
        DB::transaction(function () use ($sprint, $actor) {
            $locked = $this->lockSprint($sprint);
            if ($locked->closed_at !== null) {
                throw new DomainException('Só se exclui a sprint aberta.');
            }
            if ($locked->tasks()->exists()) {
                throw new DomainException("A {$locked->name} tem tarefas: não dá para excluir.");
            }

            $before = $this->snapshot($locked);
            $locked->delete();
            $this->audit->log(AuditActionEnum::DELETED, $locked, $before, null, $actor->id);
        });
    }

    /**
     * As tarefas da sprint que não chegaram a etapa de papel done e não estão arquivadas (P26, P50),
     * com a etapa e o dev para o diálogo de encerrar.
     *
     * @return Collection<int, Task>
     */
    public function pendingTasks(Sprint $sprint): Collection
    {
        return Task::query()
            ->where('sprint_id', $sprint->id)
            ->whereNull('archived_at')
            ->whereHas('boardStage', fn ($stage) => $stage->where('stage_role_id', '!=', StageRole::DONE))
            ->with(['boardStage', 'taskType'])
            ->lockForUpdate()
            ->orderBy('number')
            ->get();
    }

    /**
     * Cria com a linha do projeto já travada por quem chama (P45).
     *
     * @param  array{name: string, goal: ?string, starts_on: string, ends_on: string}  $data
     */
    public function insertLocked(Project $project, array $data, User $actor): Sprint
    {
        $sprint = $project->sprints()->create($this->fields($data));
        $this->audit->log(AuditActionEnum::CREATED, $sprint, null, $this->snapshot($sprint), $actor->id);

        return $sprint;
    }

    private function assertNoOtherOpen(Project $project): void
    {
        $open = $this->openSprint($project->id);
        if ($open !== null) {
            throw new DomainException("A {$open->name} está aberta neste projeto: encerre-a antes de criar outra.");
        }
    }

    /** Quem chama já travou a linha do projeto: a leitura vale até o fim da transação (P45). */
    public function openSprint(int $projectId): ?Sprint
    {
        return Sprint::query()->where('project_id', $projectId)->whereNull('closed_at')->first();
    }

    public function lockProject(Project $project): Project
    {
        return Project::query()->lockForUpdate()->findOrFail($project->id);
    }

    /** Projeto antes da sprint: a mesma ordem de trava em toda escrita. */
    private function lockSprint(Sprint $sprint): Sprint
    {
        $project = $this->lockProject($sprint->project);
        $locked = Sprint::query()->lockForUpdate()->findOrFail($sprint->id);
        if ($locked->project_id !== $project->id) {
            throw new DomainException('A sprint não é deste projeto.');
        }
        $locked->setRelation('project', $project);

        return $locked;
    }

    /**
     * O nome repetido no projeto (unique do banco, também na corrida) volta como erro do campo.
     *
     * @template T
     *
     * @param  array<string, mixed>  $data
     * @param  \Closure(): T  $write
     * @return T
     */
    public function writeSprint(array $data, \Closure $write): mixed
    {
        return $this->writeOrRefuseDuplicate($write, fn (UniqueConstraintViolationException $e) => str_contains($e->getMessage(), self::NAME_UNIQUE)
            ? ValidationException::withMessages(['name' => 'Já existe a '.($data['name'] ?? 'sprint').' neste projeto'])
            : null);
    }

    /**
     * @param  array{name: string, goal: ?string, starts_on: string, ends_on: string}  $data
     * @return array<string, mixed>
     */
    private function fields(array $data): array
    {
        $goal = trim((string) ($data['goal'] ?? ''));

        return [
            'name' => trim($data['name']),
            'goal' => $goal === '' ? null : $goal,
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
        ];
    }

    /** @return array<string, mixed> */
    private function snapshot(Sprint $sprint): array
    {
        return [
            'project_id' => $sprint->project_id,
            'name' => $sprint->name,
            'goal' => $sprint->goal,
            'starts_on' => $sprint->starts_on?->toDateString(),
            'ends_on' => $sprint->ends_on?->toDateString(),
            'closed_at' => $sprint->closed_at?->toIso8601String(),
        ];
    }
}
