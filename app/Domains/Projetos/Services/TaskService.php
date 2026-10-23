<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskPriority;
use App\Domains\Projetos\Models\TaskType;
use App\Domains\Projetos\Services\Concerns\WritesTasks;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Criar card (escopo B3, PR 1; F3 §01 e §04; P7, P16, P17, P52, P56): o card rápido no Backlog e o
 * card de suporte; e o teste cruzado (B4; F4 §04; P34): marcar e desfazer. Mover é do
 * TaskMoveService (que limpa a marca no retrabalho); etiquetas e participante, do TaskTagService.
 * Recusa só por dado inválido (ValidationException, volta para o campo) e por estado
 * (DomainException, vira toast); permissão é da TaskPolicy.
 *
 * Ordem das travas, igual em toda escrita de card: a linha do projeto, depois a do card. É a mesma
 * linha que o ProjectService trava para o prefixo e para encerrar (P3, P7) e o SprintService para
 * "uma sprint aberta" (P45): com uma ordem só, nenhuma escrita espera a outra em círculo.
 */
class TaskService
{
    use WritesTasks;

    public const FINISHED_PROJECT = 'Projeto encerrado não recebe card novo.';

    public const OWN_CROSS_TEST = 'Você é o dev desta tarefa: o teste cruzado é de outra pessoa.';

    public const NOT_CROSS_TESTED = 'Este card não tem teste cruzado marcado.';

    public const CROSS_TEST_CHANGED = 'A marca de teste cruzado mudou: recarregue a ficha.';

    public function __construct(
        private readonly AuditService $audit,
        private readonly SprintService $sprints,
    ) {}

    /**
     * Card rápido (P17, P56, R4; 14/10): só o título. Nasce no Backlog, sem tipo, área nem
     * prioridade, no fim da ordem manual da coluna. O número sai do contador atômico do projeto
     * (T4), na transação da criação: número de card excluído não volta (P16).
     */
    public function create(Project $project, User $actor, string $title, ?int $aiAuthorizationId = null): Task
    {
        return DB::transaction(function () use ($project, $actor, $title, $aiAuthorizationId) {
            $locked = $this->lockOpenProject($project);
            $queue = $this->stageOfRole($locked, StageRole::QUEUE);

            $task = Task::query()->create([
                'project_id' => $locked->id,
                'number' => $this->nextNumber($locked),
                'board_stage_id' => $queue->id,
                'position' => $this->nextPosition($queue),
                'title' => $title,
            ]);

            // A criação é o primeiro movimento (from nulo): o histórico começa no Backlog (P14).
            $this->recordMovement($task, $actor, null, $queue, aiAuthorizationId: $aiAuthorizationId);
            $this->audit->log(AuditActionEnum::CREATED, $task, null, $this->snapshot($task), $actor->id, $aiAuthorizationId);

            return $task;
        });
    }

    /**
     * Card de suporte (P52; F3 §02 "Card de suporte"): nasce na etapa de papel support, tipo
     * Suporte do projeto, fora da sprint, com quem pediu (contato ativo do cliente do projeto),
     * por onde e quando.
     *
     * @param  array{title: string, requester_contact_id: int, contact_channel_id: int, requested_at: string, priority: string}  $data
     */
    public function createSupport(Project $project, User $actor, array $data): Task
    {
        return DB::transaction(function () use ($project, $actor, $data) {
            $locked = $this->lockOpenProject($project);

            $requester = Contact::query()->active()->where('client_id', $locked->client_id)->find($data['requester_contact_id']);
            if ($requester === null) {
                throw ValidationException::withMessages(['requester_contact_id' => 'Escolha um contato ativo do cliente do projeto.']);
            }

            $support = $this->stageOfRole($locked, StageRole::SUPPORT);
            $task = Task::query()->create([
                'project_id' => $locked->id,
                'number' => $this->nextNumber($locked),
                'board_stage_id' => $support->id,
                'task_type_id' => $this->typeOfSlug($locked, TaskType::SUPPORT),
                'task_priority_id' => TaskPriority::query()->where('slug', $data['priority'])->value('id'),
                'position' => $this->nextPosition($support),
                'title' => $data['title'],
                'requester_contact_id' => $requester->id,
                'contact_channel_id' => $data['contact_channel_id'],
                'requested_at' => $data['requested_at'],
            ]);

            $this->recordMovement($task, $actor, null, $support);
            $this->audit->log(AuditActionEnum::CREATED, $task, null, [
                ...$this->snapshot($task),
                'requester_contact_id' => $task->requester_contact_id,
                'contact_channel_id' => $task->contact_channel_id,
            ], $actor->id);

            return $task;
        });
    }

    /**
     * *Marquei: testado por mim* (P34; F4 §02, §04): grava quem e quando, juntos. Em qualquer etapa
     * (o normal é In test, P35) e com o projeto encerrado (P7 só barra card novo). Com a linha
     * travada: o dev da tarefa não marca, mesmo que tenha virado o dev depois do `authorize()`, e
     * o segundo de dois cliques simultâneos encontra a marca do primeiro e é recusado.
     */
    public function markCrossTested(Task $task, User $actor): Task
    {
        return DB::transaction(function () use ($task, $actor) {
            $this->sprints->lockProject($task->project);
            $locked = $this->lockTask($task);

            if ($locked->developer_id === $actor->id) {
                throw new DomainException(self::OWN_CROSS_TEST);
            }
            if ($locked->cross_tested_by !== null) {
                $name = User::query()->whereKey($locked->cross_tested_by)->value('name');

                throw new DomainException("Teste cruzado já marcado por {$name}: desfaça antes.");
            }

            $before = $this->crossTest($locked);
            $locked->forceFill(['cross_tested_by' => $actor->id, 'cross_tested_at' => now()])->save();
            $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->crossTest($locked), $actor->id);

            return $locked;
        });
    }

    /**
     * Desfazer a marca (F4 §04, §10.2): limpa quem e quando, juntos; o antes fica na auditoria (G3).
     * A Policy olhou a marca de antes da trava: com a linha travada, quem não gere confere de novo
     * que a marca ainda é dele, para não limpar a de outra pessoa feita no meio (Verifier, rodada 1).
     */
    public function undoCrossTested(Task $task, User $actor): Task
    {
        return DB::transaction(function () use ($task, $actor) {
            $this->sprints->lockProject($task->project);
            $locked = $this->lockTask($task);

            if ($locked->cross_tested_by === null) {
                throw new DomainException(self::NOT_CROSS_TESTED);
            }
            if (! $actor->can('undoCrossTest', $locked)) {
                throw new DomainException(self::CROSS_TEST_CHANGED);
            }

            $before = $this->crossTest($locked);
            $locked->forceFill(['cross_tested_by' => null, 'cross_tested_at' => null])->save();
            $this->audit->log(AuditActionEnum::UPDATED, $locked, $before, $this->crossTest($locked), $actor->id);

            return $locked;
        });
    }

    /** @return array{cross_tested_by: ?int, cross_tested_at: ?string} */
    private function crossTest(Task $task): array
    {
        return ['cross_tested_by' => $task->cross_tested_by, 'cross_tested_at' => $task->cross_tested_at?->toIso8601String()];
    }

    private function lockOpenProject(Project $project): Project
    {
        $locked = $this->sprints->lockProject($project);
        if ($locked->isFinished()) {
            throw new DomainException(self::FINISHED_PROJECT);
        }

        return $locked;
    }

    private function typeOfSlug(Project $project, string $slug): int
    {
        return (int) $project->taskTypes()->where('slug', $slug)->valueOrFail('id');
    }

    /** T4: LAST_INSERT_ID(expr) devolve o valor novo na mesma conexão, sem ler max + 1. */
    private function nextNumber(Project $project): int
    {
        DB::update('UPDATE projects SET last_task_number = LAST_INSERT_ID(last_task_number + 1) WHERE id = ?', [$project->id]);

        return (int) DB::selectOne('SELECT LAST_INSERT_ID() AS number')->number;
    }
}
