<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ProjectArea;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use App\Platform\Services\Concerns\RefusesDuplicates;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Áreas do projeto (P18, G7, G2; docs/banco.md, project_areas). Nome único no projeto sem
 * diferença de acento e caixa: a colação do banco compara assim, na consulta e no unique. Área
 * já usada por card se desativa; só a nunca usada se exclui (G2).
 */
class ProjectAreaService
{
    use RefusesDuplicates;

    private const NAME_UNIQUE = 'project_areas_project_id_name_unique';

    public const ALREADY_USED = 'Área já usada por algum card: desative em vez de excluir';

    public function __construct(private readonly AuditService $audit) {}

    public function create(Project $project, string $name): ProjectArea
    {
        return $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($project, $name) {
            $this->refuseDuplicate($project->id, $name, null);
            $area = $project->areas()->create(['name' => $name]);
            $this->audit->log(AuditActionEnum::CREATED, $area, null, $this->snapshot($area));

            return $area;
        }), fn (UniqueConstraintViolationException $e) => $this->duplicateRefusal($e, $project->id, $name));
    }

    public function update(ProjectArea $area, string $name): ProjectArea
    {
        return $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($area, $name) {
            $locked = ProjectArea::query()->lockForUpdate()->findOrFail($area->id);
            $this->refuseDuplicate($locked->project_id, $name, $locked->id);

            $before = $this->snapshot($locked);
            $locked->fill(['name' => $name])->save();
            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked));
            }

            return $locked;
        }), fn (UniqueConstraintViolationException $e) => $this->duplicateRefusal($e, $area->project_id, $name));
    }

    /** Sai da lista de escolha do card; os cards que já têm a área continuam com ela (P8). */
    public function deactivate(ProjectArea $area, User $actor): void
    {
        DB::transaction(fn () => $area->setActiveLocked(false, $actor, function (ProjectArea $locked) use ($actor) {
            $this->audit->log(AuditActionEnum::DEACTIVATED, $locked, ['active' => true], ['active' => false], $actor->id);
        }));
    }

    public function reactivate(ProjectArea $area, User $actor): void
    {
        DB::transaction(fn () => $area->setActiveLocked(true, $actor, function (ProjectArea $locked) use ($actor) {
            $this->audit->log(AuditActionEnum::REACTIVATED, $locked, ['active' => false], ['active' => true], $actor->id);
        }));
    }

    /** G2: só a área que nenhum card usa. */
    public function delete(ProjectArea $area, User $actor): void
    {
        $deleted = $area->deleteIfUnused(function (ProjectArea $deleted) use ($actor) {
            $this->audit->log(AuditActionEnum::DELETED, $deleted, $this->snapshot($deleted), null, $actor->id);
        });

        if (! $deleted) {
            throw new DomainException(self::ALREADY_USED);
        }
    }

    private function refuseDuplicate(int $projectId, string $name, ?int $ignoreId): void
    {
        $existing = $this->duplicate($projectId, $name, $ignoreId);
        if ($existing !== null) {
            throw ValidationException::withMessages(['name' => "Já existe a área {$existing} neste projeto."]);
        }
    }

    private function duplicate(int $projectId, string $name, ?int $ignoreId): ?string
    {
        return ProjectArea::query()
            ->where('project_id', $projectId)
            ->where('name', $name)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->value('name');
    }

    private function duplicateRefusal(UniqueConstraintViolationException $e, int $projectId, string $name): ?ValidationException
    {
        if (! str_contains($e->getMessage(), self::NAME_UNIQUE)) {
            return null;
        }
        $existing = $this->duplicate($projectId, $name, null) ?? $name;

        return ValidationException::withMessages(['name' => "Já existe a área {$existing} neste projeto."]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(ProjectArea $area): array
    {
        return ['project_id' => $area->project_id, 'name' => $area->name];
    }
}
