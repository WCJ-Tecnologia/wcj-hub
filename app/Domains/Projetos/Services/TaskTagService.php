<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\ProjectArea;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskPriority;
use App\Domains\Projetos\Models\TaskType;
use App\Domains\Projetos\Services\Concerns\WritesTasks;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Etiquetas e participante do card pelo quadro (escopo B3, PR 1; F3 §03 e §11; P18, P19, P52,
 * P58, R15): o que o card aponta é do projeto dele e está ativo. Trava a linha do projeto e
 * depois a do card, como toda escrita de card.
 */
class TaskTagService
{
    use WritesTasks;

    public function __construct(
        private readonly AuditService $audit,
        private readonly SprintService $sprints,
    ) {}

    /**
     * Etiquetas do card pelo quadro (F3 §03 e §11 itens 7 a 9; P18, P19): prioridade pelo slug,
     * área e tipo do próprio projeto, ativos. Só as chaves enviadas mudam; nulo limpa.
     *
     * @param  array{priority?: ?string, area_id?: ?int, type_id?: ?int}  $changes
     */
    public function updateTags(Task $task, User $actor, array $changes): Task
    {
        return DB::transaction(function () use ($task, $actor, $changes) {
            $this->sprints->lockProject($task->project);
            $locked = $this->lockTask($task);
            $before = $this->snapshot($locked);

            if (array_key_exists('priority', $changes)) {
                $locked->task_priority_id = $changes['priority'] === null
                    ? null
                    : TaskPriority::query()->where('slug', $changes['priority'])->value('id');
            }

            if (array_key_exists('area_id', $changes)) {
                $locked->project_area_id = $this->area($locked, $changes['area_id']);
            }

            if (array_key_exists('type_id', $changes)) {
                $locked->task_type_id = $this->type($locked, $changes['type_id']);
            }

            $locked->save();
            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked), $actor->id);
            }

            return $locked;
        });
    }

    /**
     * Participante (P58): pessoa alocada no projeto e ativa, que não é o dev nem o QA do card.
     * Sem aviso (P22). Adicionar quem já está e tirar quem não está não muda nada.
     */
    public function setParticipant(Task $task, User $actor, int $userId, bool $add): Task
    {
        return DB::transaction(function () use ($task, $actor, $userId, $add) {
            $project = $this->sprints->lockProject($task->project);
            $locked = $this->lockTask($task);
            $before = $locked->participants()->orderBy('users.id')->pluck('users.id')->all();

            if ($add) {
                $person = User::query()->active()->find($userId);
                if ($person === null || ! $project->hasMember($person)) {
                    throw ValidationException::withMessages(['user_id' => 'Escolha uma pessoa alocada no projeto.']);
                }
                if (in_array($userId, [$locked->developer_id, $locked->qa_id], true)) {
                    throw ValidationException::withMessages(['user_id' => "{$person->name} já está no card como dev ou QA."]);
                }
                $locked->participants()->syncWithoutDetaching([$userId]);
            } else {
                $locked->participants()->detach($userId);
            }

            $after = $locked->participants()->orderBy('users.id')->pluck('users.id')->all();
            if ($after !== $before) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, ['participants' => $before], ['participants' => $after], $actor->id);
            }

            return $locked;
        });
    }

    private function area(Task $task, ?int $areaId): ?int
    {
        if ($areaId === null) {
            return null;
        }
        $area = ProjectArea::query()->find($areaId);
        if ($area === null || $area->project_id !== $task->project_id || ! $area->isActive()) {
            throw ValidationException::withMessages(['area_id' => 'Escolha uma área ativa deste projeto.']);
        }

        return $area->id;
    }

    /**
     * Tipo do projeto, ativo (P18, G7). Tornar um card comum em Suporte pede quem pediu, por onde e
     * quando (P52): isso é da ficha, que tem os campos; pela etiqueta, só o que já é de suporte.
     */
    private function type(Task $task, ?int $typeId): ?int
    {
        if ($typeId === null) {
            return null;
        }
        $type = TaskType::query()->find($typeId);
        if ($type === null || $type->project_id !== $task->project_id || ! $type->isActive()) {
            throw ValidationException::withMessages(['type_id' => 'Escolha um tipo ativo deste projeto.']);
        }
        if ($type->slug === TaskType::SUPPORT && $task->requester_contact_id === null) {
            throw ValidationException::withMessages(['type_id' => 'Card de suporte pede quem pediu, por onde e quando: escolha Suporte na ficha do card.']);
        }

        return $type->id;
    }
}
