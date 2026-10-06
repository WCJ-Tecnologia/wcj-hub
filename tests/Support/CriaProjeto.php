<?php

namespace Tests\Support;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\TaskPriority;
use App\Domains\Projetos\Models\TaskType;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use Database\Seeders\StageRoleSeeder;
use Database\Seeders\TaskPrioritySeeder;
use Database\Seeders\TaskTypeSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Projeto mínimo para os testes do Núcleo que dependem de "gestor do cliente" (P1, P5) ou de
 * projeto aberto (N11). O B2 ainda não tem Service de projeto: grava direto na tabela.
 */
trait CriaProjeto
{
    private int $prefixosDeProjeto = 0;

    /** @param  list<User>  $members */
    private function project(Client $client, User $manager, array $members = [], mixed $finishedAt = null): Project
    {
        $project = Project::query()->create([
            'client_id' => $client->id,
            'manager_id' => $manager->id,
            'key_prefix' => 'P'.++$this->prefixosDeProjeto,
            'name' => 'Projeto '.$this->prefixosDeProjeto,
            'starts_on' => now('America/Sao_Paulo')->toDateString(),
            'finished_at' => $finishedAt,
        ]);
        $project->users()->attach(array_map(fn (User $user) => $user->id, $members));

        return $project;
    }

    /**
     * Card de suporte que cita o contato em "quem pediu" (P52): o "já usado" do contato (G2).
     * O B3 ainda não tem Service de card: grava direto na tabela.
     */
    private function supportCard(Contact $requester): int
    {
        $this->seed([StageRoleSeeder::class, TaskTypeSeeder::class, TaskPrioritySeeder::class]);
        $project = $this->project($requester->client, User::factory()->projectManager()->create());
        $stage = DB::table('board_stages')->insertGetId([
            'project_id' => $project->id, 'stage_role_id' => StageRole::SUPPORT, 'name' => 'Suporte', 'position' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('tasks')->insertGetId([
            'project_id' => $project->id, 'number' => 1, 'board_stage_id' => $stage,
            'task_type_id' => TaskType::query()->where('slug', TaskType::SUPPORT)->value('id'),
            'task_priority_id' => TaskPriority::NORMAL, 'title' => 'Pedido do cliente',
            'requester_contact_id' => $requester->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
