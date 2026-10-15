<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Services\AuditService;
use App\Platform\Services\Concerns\RefusesDuplicates;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cadastro do projeto (P1–P3, P7, P10; escopo B2). Toda escrita grava a auditoria na mesma
 * transação (G3). O prefixo repetido é recusado pelo unique do banco (a colação ignora a caixa),
 * não por consulta antes: assim duas criações simultâneas também ficam com um só (P3).
 */
class ProjectService
{
    use RefusesDuplicates;

    // O nome do índice unique de projects.key_prefix; a mensagem do MySQL o cita.
    private const PREFIX_UNIQUE = 'projects_key_prefix_unique';

    /** Campos que a edição aceita: o cliente só se define na criação (P1). */
    private const EDITABLE = ['manager_id', 'key_prefix', 'name', 'starts_on', 'due_on'];

    public function __construct(
        private readonly AuditService $audit,
        private readonly BoardStageService $stages,
        private readonly TaskTypeService $types,
    ) {}

    /**
     * Nasce aberto, com o quadro padrão (P10), os 5 tipos padrão (P18, 14/10), sem áreas, sem
     * ninguém alocado e com os padrões do banco: motivo da pausa ligado e sprint de 10 dias úteis
     * (P15, P46).
     *
     * @param  array{client_id: int, manager_id: int, key_prefix: string, name: string, starts_on: string, due_on: ?string}  $data
     */
    public function create(array $data): Project
    {
        return $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($data) {
            $project = Project::query()->create($data);
            $board = $this->stages->createDefaultBoard($project);
            $this->types->createDefaults($project);

            $this->audit->log(AuditActionEnum::CREATED, $project, null, [...$this->snapshot($project), 'board_stages' => $board]);

            return $project;
        }), fn (UniqueConstraintViolationException $e) => $this->prefixTaken($e, $data['key_prefix']));
    }

    /**
     * A linha travada é a mesma que o contador da chave trava (T4): a troca do prefixo e a
     * primeira tarefa não se cruzam (P3).
     *
     * @param  array{manager_id: int, key_prefix: string, name: string, starts_on: string, due_on: ?string}  $data
     */
    public function update(Project $project, array $data): Project
    {
        return $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($project, $data) {
            $locked = Project::query()->lockForUpdate()->findOrFail($project->id);

            if ($data['key_prefix'] !== $locked->key_prefix && $locked->last_task_number > 0) {
                throw ValidationException::withMessages(['key_prefix' => 'O prefixo não muda depois da primeira tarefa do projeto.']);
            }

            $before = $this->snapshot($locked);
            $locked->fill(array_intersect_key($data, array_flip(self::EDITABLE)))->save();

            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked));
            }

            return $locked;
        }), fn (UniqueConstraintViolationException $e) => $this->prefixTaken($e, $data['key_prefix']));
    }

    /**
     * Padrões do projeto (F2 §02, aba Padrões; P15, P46). Valem dali para a frente: desligar o
     * motivo da pausa não apaga os motivos já gravados; mudar a duração não mexe na sprint aberta (P8).
     *
     * @param  array{require_pause_reason: bool, sprint_days: int}  $data
     */
    public function updateDefaults(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $locked = Project::query()->lockForUpdate()->findOrFail($project->id);

            $before = $this->snapshot($locked);
            $locked->fill(['require_pause_reason' => $data['require_pause_reason'], 'sprint_days' => $data['sprint_days']])->save();

            if ($locked->wasChanged()) {
                $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->snapshot($locked));
            }

            return $locked;
        });
    }

    /** Mesmo com tarefa aberta; tarefas, sprints e pacotes ficam como estão (P7). */
    public function finish(Project $project): Project
    {
        return $this->setFinished($project, true);
    }

    public function reopen(Project $project): Project
    {
        return $this->setFinished($project, false);
    }

    /**
     * Exclusão de verdade só do projeto nunca usado: sem tarefa, sprint e pacote (G2, F2 §10.9).
     * Etapas, áreas e alocações saem junto (cascade). Conferido com a linha travada, a mesma
     * que a criação de tarefa trava (banco.md §4). Devolve false sem apagar nada quando já foi usado.
     */
    public function delete(Project $project): bool
    {
        return DB::transaction(function () use ($project) {
            $locked = Project::query()->lockForUpdate()->findOrFail($project->id);

            if ($locked->tasks()->exists() || $locked->sprints()->exists() || $locked->packages()->exists()) {
                return false;
            }

            $before = $this->snapshot($locked);
            $locked->delete();
            $this->audit->log(AuditActionEnum::DELETED, $locked, $before, null);

            return true;
        });
    }

    /** O pedido repetido (encerrar o encerrado) não muda a data original nem grava auditoria. */
    private function setFinished(Project $project, bool $finished): Project
    {
        return DB::transaction(function () use ($project, $finished) {
            $locked = Project::query()->lockForUpdate()->findOrFail($project->id);

            if ($locked->isFinished() === $finished) {
                return $locked;
            }

            $before = $this->snapshot($locked);
            $locked->forceFill(['finished_at' => $finished ? now() : null])->save();
            $this->audit->log($finished ? AuditActionEnum::FINISHED : AuditActionEnum::REOPENED, $locked, $before, $this->snapshot($locked));

            return $locked;
        });
    }

    /**
     * Só o unique do prefixo vira o erro do campo, com o nome do projeto dono (P3); outro unique
     * que ninguém previu sobe como veio (RefusesDuplicates, CR-2-11 do B1).
     */
    private function prefixTaken(UniqueConstraintViolationException $e, string $prefix): ?ValidationException
    {
        if (! str_contains($e->getMessage(), self::PREFIX_UNIQUE)) {
            return null;
        }

        $owner = Project::query()->where('key_prefix', $prefix)->value('name');

        return ValidationException::withMessages(['key_prefix' => "O prefixo {$prefix} já é do projeto {$owner}."]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Project $project): array
    {
        return [
            'client_id' => $project->client_id,
            'manager_id' => $project->manager_id,
            'key_prefix' => $project->key_prefix,
            'name' => $project->name,
            'starts_on' => $project->starts_on?->toDateString(),
            'due_on' => $project->due_on?->toDateString(),
            'finished_at' => $project->finished_at?->toIso8601String(),
            'require_pause_reason' => $project->require_pause_reason,
            'sprint_days' => $project->sprint_days,
        ];
    }
}
