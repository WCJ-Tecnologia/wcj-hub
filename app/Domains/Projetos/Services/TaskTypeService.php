<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\TaskType;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use App\Platform\Services\Concerns\RefusesDuplicates;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tipos de card do projeto (P18, G2, G7; F3 §11 item 9; banco.md seção 7, 14/10). Todo projeto
 * nasce com os 5 padrão; o gestor cria tipos e edita nome e cor dos seus pela etiqueta do quadro,
 * sem propagar a outros projetos. Rótulo único no projeto, sem diferença de acento e caixa (a
 * colação do banco compara assim), como as áreas. Tipo já usado por card se desativa; só o nunca
 * usado se exclui (G2); os de slug (Melhoria, Suporte, Desenvolvimento) o código procura e por
 * isso não se desativam nem se excluem (CR-1-02 da revisão do B3 PR 1).
 */
class TaskTypeService
{
    use RefusesDuplicates;

    private const LABEL_UNIQUE = 'task_types_project_id_label_unique';

    public const ALREADY_USED = 'Tipo já usado por algum card: desative em vez de excluir';

    public const PROTECTED = 'O sistema procura este tipo pelo nome interno: ele não se desativa nem se exclui.';

    /** Os 5 padrão, na ordem em que aparecem (banco.md seção 7). */
    private const DEFAULTS = [
        [TaskType::IMPROVEMENT, 'Melhoria'],
        [null, 'Correção de bug'],
        [TaskType::SUPPORT, 'Suporte'],
        [null, 'Mudança (GMUD)'],
        [TaskType::DEVELOPMENT, 'Desenvolvimento'],
    ];

    public function __construct(private readonly AuditService $audit) {}

    /** Na criação do projeto, na transação de quem chama (ProjectService::create). */
    public function createDefaults(Project $project): void
    {
        foreach (self::DEFAULTS as [$slug, $label]) {
            $project->taskTypes()->create(['slug' => $slug, 'label' => $label]);
        }
    }

    public function create(Project $project, string $label, ?string $color): TaskType
    {
        return $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($project, $label, $color) {
            $this->refuseDuplicate($project->id, $label, null);
            $type = $project->taskTypes()->create(['label' => $label, 'color' => $color]);
            $this->audit->log(AuditActionEnum::CREATED, $type, null, $this->snapshot($type));

            return $type;
        }), fn (UniqueConstraintViolationException $e) => $this->duplicateRefusal($e, $project->id, $label));
    }

    /** Nome e cor; o slug não muda (o código acha Suporte, Melhoria e Desenvolvimento por ele). */
    public function update(TaskType $type, string $label, ?string $color): TaskType
    {
        return $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($type, $label, $color) {
            $locked = TaskType::query()->lockForUpdate()->findOrFail($type->id);
            $this->refuseDuplicate($locked->project_id, $label, $locked->id);

            $before = $this->snapshot($locked);
            $locked->fill(['label' => $label, 'color' => $color])->save();
            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked));
            }

            return $locked;
        }), fn (UniqueConstraintViolationException $e) => $this->duplicateRefusal($e, $type->project_id, $label));
    }

    /** Sai da lista de escolha do card; os cards que já têm o tipo continuam com ele (G7). */
    public function deactivate(TaskType $type, User $actor): void
    {
        $this->refuseProtected($type);
        DB::transaction(fn () => $type->setActiveLocked(false, $actor, function (TaskType $locked) use ($actor) {
            $this->audit->log(AuditActionEnum::DEACTIVATED, $locked, ['active' => true], ['active' => false], $actor->id);
        }));
    }

    public function reactivate(TaskType $type, User $actor): void
    {
        DB::transaction(fn () => $type->setActiveLocked(true, $actor, function (TaskType $locked) use ($actor) {
            $this->audit->log(AuditActionEnum::REACTIVATED, $locked, ['active' => false], ['active' => true], $actor->id);
        }));
    }

    /** G2: só o tipo que nenhum card usa. */
    public function delete(TaskType $type, User $actor): void
    {
        $this->refuseProtected($type);
        $deleted = $type->deleteIfUnused(function (TaskType $deleted) use ($actor) {
            $this->audit->log(AuditActionEnum::DELETED, $deleted, $this->snapshot($deleted), null, $actor->id);
        });

        if (! $deleted) {
            throw new DomainException(self::ALREADY_USED);
        }
    }

    /** O slug não muda nunca (update não o toca), então a instância recebida decide. */
    private function refuseProtected(TaskType $type): void
    {
        if ($type->slug !== null) {
            throw new DomainException(self::PROTECTED);
        }
    }

    private function refuseDuplicate(int $projectId, string $label, ?int $ignoreId): void
    {
        $existing = $this->duplicate($projectId, $label, $ignoreId);
        if ($existing !== null) {
            throw ValidationException::withMessages(['name' => "Já existe o tipo {$existing} neste projeto."]);
        }
    }

    private function duplicate(int $projectId, string $label, ?int $ignoreId): ?string
    {
        return TaskType::query()
            ->where('project_id', $projectId)
            ->where('label', $label)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->value('label');
    }

    private function duplicateRefusal(UniqueConstraintViolationException $e, int $projectId, string $label): ?ValidationException
    {
        if (! str_contains($e->getMessage(), self::LABEL_UNIQUE)) {
            return null;
        }
        $existing = $this->duplicate($projectId, $label, null) ?? $label;

        return ValidationException::withMessages(['name' => "Já existe o tipo {$existing} neste projeto."]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(TaskType $type): array
    {
        return ['project_id' => $type->project_id, 'slug' => $type->slug, 'label' => $type->label, 'color' => $type->color];
    }
}
