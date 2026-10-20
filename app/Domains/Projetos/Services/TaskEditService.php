<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAttachment;
use App\Domains\Projetos\Services\Concerns\WritesTasks;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Editar a ficha do card (F3 §02 e §04 "Na ficha"; P17 a P20, P25, P27, P57, P59, R15), o
 * `PATCH /projetos/{id}/cards/{chave}` da ficha e das etiquetas do quadro. Cada campo vai para o
 * seu jeito só: tipo e área pelo TaskTagService, dev, QA e prioridade pelo TaskAssignmentService
 * (que avisa, P22), horas pelo TaskHoursService; título, requisição, tarefa de origem, card pai e
 * capa ficam aqui. Tudo numa transação: um campo recusado desfaz os outros.
 */
class TaskEditService
{
    use WritesTasks;

    private const OWN = ['title', 'story_as', 'story_want', 'story_so_that', 'description', 'origin_task_id', 'parent_task_id', 'cover_attachment_id'];

    public function __construct(
        private readonly AuditService $audit,
        private readonly SprintService $sprints,
        private readonly TaskTagService $tags,
        private readonly TaskAssignmentService $assignments,
        private readonly TaskHoursService $hours,
    ) {}

    /** @param  array<string, mixed>  $changes  só as chaves enviadas mudam; nulo limpa */
    public function update(Task $task, User $actor, array $changes): Task
    {
        return DB::transaction(function () use ($task, $actor, $changes) {
            $this->sprints->lockProject($task->project);
            $locked = $this->lockTask($task);

            $own = array_intersect_key($changes, array_flip(self::OWN));
            if ($own !== []) {
                $this->assertRelations($locked, $own);
                $before = $locked->only(array_keys($own));
                $locked->fill($own)->save();
                if ($locked->wasChanged()) {
                    $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $locked->only(array_keys($own)), $actor->id);
                }
            }

            $tags = array_intersect_key($changes, array_flip(['type_id', 'area_id', 'priority']));
            if ($tags !== []) {
                $locked = $this->tags->updateTags($locked, $actor, $tags);
            }

            $people = array_intersect_key($changes, array_flip(['developer_id', 'qa_id']));
            if ($people !== []) {
                $locked = $this->assignments->update($locked, $people, $actor);
            }

            if (array_key_exists('estimated_hours', $changes)) {
                $hours = $changes['estimated_hours'];
                $locked = $this->hours->estimate($locked, $hours === null ? null : (string) $hours, $actor);
            }

            return $locked->refresh();
        });
    }

    /**
     * R15 e P57: tarefa de origem e card pai do mesmo projeto, nunca o próprio card; o pai não é
     * filho de ninguém e o card que vira filho não tem filhos (um nível só). Capa: anexo de imagem
     * da própria tarefa (P59).
     *
     * @param  array<string, mixed>  $own
     */
    private function assertRelations(Task $task, array $own): void
    {
        foreach (['origin_task_id' => 'origin_task_id', 'parent_task_id' => 'parent_task_id'] as $field) {
            if (! isset($own[$field])) {
                continue;
            }
            $other = Task::query()->with('project')->find($own[$field]);
            if ($other === null || $other->project_id !== $task->project_id || $other->id === $task->id) {
                throw ValidationException::withMessages([$field => 'Escolha outro card deste projeto.']);
            }
            if ($field === 'parent_task_id') {
                if ($other->parent_task_id !== null) {
                    $grandparent = Task::query()->with('project')->findOrFail($other->parent_task_id);
                    throw ValidationException::withMessages([$field => "{$other->key()} já é filho de {$grandparent->key()}."]);
                }
                if ($task->children()->exists()) {
                    throw ValidationException::withMessages([$field => "{$task->key()} já é pai de outros cards; um nível só."]);
                }
            }
        }

        if (isset($own['cover_attachment_id'])) {
            $file = TaskAttachment::query()->find($own['cover_attachment_id']);
            if ($file === null || $file->task_id !== $task->id || ! $file->isImage()) {
                throw ValidationException::withMessages(['cover_attachment_id' => 'A capa é um anexo de imagem deste card.']);
            }
        }
    }
}
