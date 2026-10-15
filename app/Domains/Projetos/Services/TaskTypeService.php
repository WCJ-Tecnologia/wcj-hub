<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\TaskType;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Services\AuditService;
use App\Platform\Services\Concerns\RefusesDuplicates;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tipos de card do projeto (P18; F3 §11 item 9; banco.md seção 7, 14/10). Todo projeto nasce com
 * os 5 padrão; o gestor cria tipos e edita nome e cor dos seus pela etiqueta do quadro, sem
 * propagar a outros projetos. Rótulo único no projeto, sem diferença de acento e caixa (a colação
 * do banco compara assim), como as áreas.
 */
class TaskTypeService
{
    use RefusesDuplicates;

    private const LABEL_UNIQUE = 'task_types_project_id_label_unique';

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
        return ['project_id' => $type->project_id, 'label' => $type->label, 'color' => $type->color];
    }
}
