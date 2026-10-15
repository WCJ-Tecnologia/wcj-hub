<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\ProjectMember;
use App\Domains\Projetos\Models\Task;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use App\Platform\Services\Concerns\RefusesDuplicates;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pessoas do projeto (P5; docs/banco.md, project_members). Alocar dá a visão na hora; tirar
 * deixa sem dev e sem QA as tarefas do projeto em que a pessoa estava, na mesma transação. O
 * que ela lançou, comentou e moveu continua (P8). Toda escrita grava a auditoria (G3).
 */
class ProjectMemberService
{
    use RefusesDuplicates;

    private const MEMBER_UNIQUE = 'project_members_project_id_user_id_unique';

    public function __construct(private readonly AuditService $audit) {}

    /**
     * Quem já está no projeto o FormRequest barra; a corrida (duas alocações juntas) cai no
     * unique e volta como o mesmo erro de campo.
     *
     * @param  list<int>  $userIds
     * @return int quantas pessoas entraram
     */
    public function allocate(Project $project, array $userIds): int
    {
        return $this->writeOrRefuseDuplicate(fn () => DB::transaction(function () use ($project, $userIds) {
            $locked = Project::query()->lockForUpdate()->findOrFail($project->id);

            foreach ($userIds as $userId) {
                $member = $locked->members()->create(['user_id' => $userId]);
                $this->audit->log(AuditActionEnum::CREATED, $member, null, ['project_id' => $locked->id, 'user_id' => $userId]);
            }

            return count($userIds);
        }), fn (UniqueConstraintViolationException $e) => str_contains($e->getMessage(), self::MEMBER_UNIQUE)
            ? ValidationException::withMessages(['user_ids' => 'Alguém da lista já está no projeto.'])
            : null);
    }

    /**
     * @return array{dev: int, qa: int}|null quantas tarefas ficaram sem dev e sem QA; nulo se a pessoa não estava no projeto
     */
    public function remove(Project $project, User $user, User $actor): ?array
    {
        return DB::transaction(function () use ($project, $user, $actor) {
            Project::query()->lockForUpdate()->findOrFail($project->id);
            $member = ProjectMember::query()
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($member === null) {
                return null;
            }

            $counts = ['dev' => $this->clear($project, $user, 'developer_id', $actor), 'qa' => $this->clear($project, $user, 'qa_id', $actor)];

            $member->delete();
            $this->audit->log(AuditActionEnum::DELETED, $member, ['project_id' => $project->id, 'user_id' => $user->id], null, $actor->id);

            return $counts;
        });
    }

    /** As tarefas do projeto em que a pessoa era dev (ou QA) ficam sem (P5), cada uma auditada. */
    private function clear(Project $project, User $user, string $column, User $actor): int
    {
        $tasks = Task::query()
            ->where('project_id', $project->id)
            ->where($column, $user->id)
            ->lockForUpdate()
            ->get();

        foreach ($tasks as $task) {
            $task->forceFill([$column => null])->save();
            $this->audit->log(AuditActionEnum::UPDATED, $task, [$column => $user->id], [$column => null], $actor->id);
        }

        return $tasks->count();
    }

    /**
     * Quem pode entrar (F2 §02): usuário ativo com papel Desenvolvedor, Gestor de projetos ou
     * Administrador que ainda não está no projeto. O Comercial não aparece (C14).
     *
     * @return list<array{id: int, name: string, role_label: string}>
     */
    public function eligible(Project $project): array
    {
        return User::query()
            ->active()
            ->whereIn('role_id', ProjectQuery::MEMBER_ROLES)
            ->whereNotIn('id', $project->members()->select('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'role_id'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'role_label' => $user->type()->label()])
            ->all();
    }
}
