<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\BoardStage;
use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Etapas do quadro (P8, P10–P12; docs/banco.md, board_stages). O quadro padrão mora só aqui: o
 * ProjectService e o ExemploSeeder o criam por este Service, para a ordem não morar em dois
 * lugares (PR #4). Toda escrita do quadro trava a linha do projeto: duas pessoas mexendo nas
 * etapas ao mesmo tempo gravam uma de cada vez, e a asserção "mesmo projeto" (R15) vale sempre.
 */
class BoardStageService
{
    /**
     * As 8 etapas com que todo projeto nasce, na ordem da P10 (decisão de 14/10: suporte
     * primeiro, pausa logo depois do To do), com o papel de cada uma (P11).
     */
    public const DEFAULT_BOARD = [
        [StageRole::SUPPORT, 'Support'],
        [StageRole::QUEUE, 'Backlog'],
        [StageRole::SPRINT, 'To do'],
        [StageRole::PAUSED, 'Paused'],
        [StageRole::DEVELOPMENT, 'Doing'],
        [StageRole::CODE_REVIEW, 'Code-review'],
        [StageRole::TEST, 'In test'],
        [StageRole::DONE, 'Done'],
    ];

    /** Uma ativa de cada por projeto (P11), travada no banco; não se removem nem trocam de papel. */
    public const SINGLE_ROLES = [StageRole::QUEUE, StageRole::SPRINT, StageRole::SUPPORT];

    /** Os papéis que a etapa nova pode ter (F2 §02). */
    public const REPEATABLE_ROLES = [StageRole::DEVELOPMENT, StageRole::CODE_REVIEW, StageRole::TEST, StageRole::DONE, StageRole::PAUSED];

    public const SINGLE_ROLE_LOCKED = 'As etapas de Fila, Sprint e Suporte não se removem nem trocam de papel.';

    public const ROLE_LOCKED_BY_HISTORY = 'O papel não muda depois que um card passou pela etapa.';

    public const LAST_DEVELOPMENT = 'O quadro precisa de pelo menos uma etapa de Desenvolvimento.';

    public const STAGE_ARCHIVED = 'Esta etapa já foi removida do quadro.';

    public function __construct(private readonly AuditService $audit) {}

    /**
     * Não grava auditoria: quem cria o projeto audita a criação inteira, com o quadro junto.
     *
     * @return list<string> os nomes das etapas, na ordem
     */
    public function createDefaultBoard(Project $project): array
    {
        foreach (self::DEFAULT_BOARD as $index => [$role, $name]) {
            $project->boardStages()->create([
                'stage_role_id' => $role,
                'name' => $name,
                'position' => $index + 1,
            ]);
        }

        return array_column(self::DEFAULT_BOARD, 1);
    }

    /**
     * Etapa nova no fim do quadro (F2 §04). O papel único (fila, sprint, suporte) nem chega aqui
     * pelo FormRequest; se chegar, o unique do banco recusa.
     *
     * @param  array{name: string, stage_role_id: int}  $data
     */
    public function create(Project $project, array $data): BoardStage
    {
        return DB::transaction(function () use ($project, $data) {
            $locked = Project::query()->lockForUpdate()->findOrFail($project->id);

            if (in_array($data['stage_role_id'], self::SINGLE_ROLES, true)) {
                throw new DomainException(self::SINGLE_ROLE_LOCKED);
            }

            $stage = $locked->boardStages()->create([
                'stage_role_id' => $data['stage_role_id'],
                'name' => $data['name'],
                'position' => ((int) $locked->boardStages()->max('position')) + 1,
            ]);
            $this->audit->log(AuditActionEnum::CREATED, $stage, null, $this->snapshot($stage));

            return $stage;
        });
    }

    /**
     * Renomeia sempre; troca o papel só enquanto nenhum card passou pela etapa (P8, P11) e
     * nunca nas de papel único.
     *
     * @param  array{name: string, stage_role_id?: int}  $data
     */
    public function update(BoardStage $stage, array $data): BoardStage
    {
        return DB::transaction(function () use ($stage, $data) {
            Project::query()->lockForUpdate()->findOrFail($stage->project_id);
            $locked = BoardStage::query()->lockForUpdate()->findOrFail($stage->id);
            $this->assertSameProject($stage, $locked->project_id);

            if ($locked->archived_at !== null) {
                throw new DomainException(self::STAGE_ARCHIVED);
            }

            $before = $this->snapshot($locked);
            $locked->name = $data['name'];

            $role = $data['stage_role_id'] ?? $locked->stage_role_id;
            if ($role !== $locked->stage_role_id) {
                $this->assertRoleCanChange($locked, $role);
                $locked->stage_role_id = $role;
            }

            $locked->save();
            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked));
            }

            return $locked;
        });
    }

    /**
     * A ordem do quadro inteira de uma vez, inclusive Fila, Sprint e Suporte (P11). A lista
     * precisa ser exatamente as etapas ativas do projeto, cada uma uma vez.
     *
     * @param  list<int>  $ids
     */
    public function reorder(Project $project, array $ids): void
    {
        DB::transaction(function () use ($project, $ids) {
            $locked = Project::query()->lockForUpdate()->findOrFail($project->id);
            $stages = $locked->boardStages()->whereNull('archived_at')->orderBy('position')->get();
            $current = $stages->pluck('id')->map(fn ($id) => (int) $id)->all();
            $wanted = array_map('intval', $ids);

            if (count($wanted) !== count($current) || array_diff($current, $wanted) !== [] || count(array_unique($wanted)) !== count($wanted)) {
                throw ValidationException::withMessages(['ids' => 'A ordem precisa ter todas as etapas ativas do projeto, uma vez cada.']);
            }

            if ($wanted === $current) {
                return;
            }

            $byId = $stages->keyBy('id');
            foreach ($wanted as $index => $id) {
                $byId[$id]->forceFill(['position' => $index + 1])->save();
            }

            $names = fn (array $order) => array_map(fn (int $id) => $byId[$id]->name, $order);
            $this->audit->log(AuditActionEnum::UPDATED, $locked, ['stage_order' => $names($current)], ['stage_order' => $names($wanted)]);
        });
    }

    /**
     * "Remover" arquiva: a etapa segue nos movimentos antigos (P8, P12). Com cards, eles vão
     * para o destino, cada um com o movimento gravado (P14) e sem contar como retrabalho (F2
     * §10.4); destino de papel fila também tira o card da sprint (P45). Cards arquivados não se
     * mexem (P26). Fila, Sprint e Suporte não se removem; a última de Desenvolvimento também não
     * (P11, P43).
     *
     * @return int quantos cards foram movidos
     */
    public function archive(BoardStage $stage, ?int $destinationId, User $actor): int
    {
        return DB::transaction(function () use ($stage, $destinationId, $actor) {
            Project::query()->lockForUpdate()->findOrFail($stage->project_id);
            $locked = BoardStage::query()->lockForUpdate()->findOrFail($stage->id);
            $this->assertSameProject($stage, $locked->project_id);

            if ($locked->archived_at !== null) {
                return 0;
            }
            if (in_array($locked->stage_role_id, self::SINGLE_ROLES, true)) {
                throw new DomainException(self::SINGLE_ROLE_LOCKED);
            }
            if ($locked->stage_role_id === StageRole::DEVELOPMENT && ! $this->hasOtherActiveDevelopment($locked)) {
                throw new DomainException(self::LAST_DEVELOPMENT);
            }

            $tasks = Task::query()->where('board_stage_id', $locked->id)->whereNull('archived_at')->lockForUpdate()->get();
            $moved = 0;
            if ($tasks->isNotEmpty()) {
                $destination = $this->destination($locked, $destinationId);
                foreach ($tasks as $task) {
                    $this->moveTask($task, $locked, $destination, $actor);
                    $moved++;
                }
            }

            $before = $this->snapshot($locked);
            $locked->forceFill(['archived_at' => now()])->save();
            $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked));

            return $moved;
        });
    }

    /** Algum card já passou pela etapa (de onde saiu ou para onde foi): o papel congela (P8, P11). */
    public function hasMovements(BoardStage $stage): bool
    {
        return TaskStageMovement::query()
            ->where(fn ($query) => $query->where('from_board_stage_id', $stage->id)->orWhere('to_board_stage_id', $stage->id))
            ->exists();
    }

    private function assertRoleCanChange(BoardStage $stage, int $newRole): void
    {
        if (in_array($stage->stage_role_id, self::SINGLE_ROLES, true) || in_array($newRole, self::SINGLE_ROLES, true)) {
            throw new DomainException(self::SINGLE_ROLE_LOCKED);
        }
        if ($this->hasMovements($stage)) {
            throw new DomainException(self::ROLE_LOCKED_BY_HISTORY);
        }
        if ($stage->stage_role_id === StageRole::DEVELOPMENT && ! $this->hasOtherActiveDevelopment($stage)) {
            throw new DomainException(self::LAST_DEVELOPMENT);
        }
    }

    private function hasOtherActiveDevelopment(BoardStage $stage): bool
    {
        return BoardStage::query()
            ->where('project_id', $stage->project_id)
            ->where('stage_role_id', StageRole::DEVELOPMENT)
            ->whereNull('archived_at')
            ->whereKeyNot($stage->id)
            ->exists();
    }

    /** O destino dos cards: etapa ativa do mesmo projeto (R15), diferente da que sai (P12). */
    private function destination(BoardStage $stage, ?int $destinationId): BoardStage
    {
        if ($destinationId === null) {
            throw ValidationException::withMessages(['destination_id' => 'Escolha para onde os cards vão.']);
        }

        $destination = BoardStage::query()
            ->where('project_id', $stage->project_id)
            ->whereNull('archived_at')
            ->whereKeyNot($stage->id)
            ->find($destinationId);

        if ($destination === null) {
            throw ValidationException::withMessages(['destination_id' => 'Escolha outra etapa ativa deste projeto.']);
        }

        return $destination;
    }

    private function moveTask(Task $task, BoardStage $from, BoardStage $to, User $actor): void
    {
        $before = $task->only(['board_stage_id', 'sprint_id']);
        $task->board_stage_id = $to->id;
        if ($to->stage_role_id === StageRole::QUEUE) {
            $task->sprint_id = null;
        }
        $task->save();

        TaskStageMovement::query()->create([
            'task_id' => $task->id,
            'moved_by' => $actor->id,
            'from_board_stage_id' => $from->id,
            'to_board_stage_id' => $to->id,
        ]);
        $this->audit->log(AuditActionEnum::UPDATED, $task, $before, $task->only(['board_stage_id', 'sprint_id']), $actor->id);
    }

    /** R15: a etapa recebida pela rota é do projeto que a linha travada diz. */
    private function assertSameProject(BoardStage $stage, int $projectId): void
    {
        if ($stage->project_id !== $projectId) {
            throw new DomainException('A etapa não é deste projeto.');
        }
    }
    /**
     * @return array<string, mixed>
     */
    private function snapshot(BoardStage $stage): array
    {
        return [
            'project_id' => $stage->project_id,
            'name' => $stage->name,
            'stage_role_id' => $stage->stage_role_id,
            'position' => $stage->position,
            'archived_at' => $stage->archived_at?->toIso8601String(),
        ];
    }
}
