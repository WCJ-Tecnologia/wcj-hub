<?php

namespace Database\Seeders;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;
use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAcceptanceCriterion;
use App\Domains\Projetos\Models\TaskPriority;
use App\Domains\Projetos\Models\TaskStageMovement;
use App\Domains\Projetos\Models\TaskType;
use App\Domains\Projetos\Services\BoardStageService;
use App\Platform\Enums\RoleEnum;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Support\Exemplo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dado de exemplo para quem sobe o projeto do zero ver o quadro com algo dentro. Tudo marcado
 * (App\Support\Exemplo: nome começa com "[Exemplo] ", e-mail em exemplo.wcj.test) e apagado por
 * `php artisan app:limpar-exemplo`. Nunca em produção.
 *
 * Grava direto nas tabelas, mas dentro das regras do banco.md; o quadro vem do BoardStageService
 * (B2), o mesmo da tela: projeto com as 8 etapas padrão (P10), pessoas alocadas (P5), cards no Backlog
 * com tipo 'improvement' e prioridade normal (P17, R4), número pelo contador (P16) e o movimento
 * de criação (P14).
 *
 * As pessoas de exemplo nascem com senha aleatória que ninguém conhece: para entrar com uma
 * delas, o Administrador redefine a senha pela tela de usuários.
 */
class ExemploSeeder extends Seeder
{
    private const CARDS = [
        ['title' => 'Tela de login com o logo do cliente', 'criteria' => ['Mostra o logo na tela de login', 'Funciona no celular (375 px)']],
        ['title' => 'Cardápio por categoria', 'criteria' => ['Lista as categorias na ordem cadastrada']],
        ['title' => 'Pedido pelo WhatsApp', 'criteria' => []],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('ExemploSeeder não roda em produção.');

            return;
        }

        if (Client::query()->where('name', 'like', Exemplo::likeNome())->exists()) {
            $this->command?->info('Dado de exemplo já existe: nada a fazer (apague com app:limpar-exemplo).');

            return;
        }

        DB::transaction(function () {
            $client = Client::query()->create([
                'name' => Exemplo::nome('Padaria Pão Quente'),
                'country' => 'BR',
                'document' => '11222333000181',
            ]);

            Contact::query()->create([
                'client_id' => $client->id,
                'name' => Exemplo::nome('Joaquim Souza'),
                'email' => Exemplo::email('joaquim'),
                'phone' => '(11) 99999-0000',
            ]);

            $gestora = $this->pessoa('Ana Gestora', 'ana', RoleEnum::PROJECT_MANAGER, 8);
            $dev = $this->pessoa('Bruno Dev', 'bruno', RoleEnum::DEVELOPER, 8);
            $qa = $this->pessoa('Carla QA', 'carla', RoleEnum::DEVELOPER, 6);

            $project = Project::query()->create([
                'client_id' => $client->id,
                'manager_id' => $gestora->id,
                'key_prefix' => 'EXE',
                'name' => Exemplo::nome('App de pedidos'),
                'starts_on' => now('America/Sao_Paulo')->toDateString(),
            ]);
            $project->users()->attach([$gestora->id, $dev->id, $qa->id]);

            // O quadro padrão mora no Service do quadro (P10, PR #4): a ordem não fica em dois lugares.
            app(BoardStageService::class)->createDefaultBoard($project);
            $backlog = $project->boardStages()->where('stage_role_id', StageRole::QUEUE)->firstOrFail();

            $melhoria = TaskType::query()->where('slug', TaskType::IMPROVEMENT)->value('id');

            foreach (self::CARDS as $posicao => $card) {
                $task = Task::query()->create([
                    'project_id' => $project->id,
                    'number' => $posicao + 1,
                    'board_stage_id' => $backlog->id,
                    'task_type_id' => $melhoria,
                    'task_priority_id' => TaskPriority::NORMAL,
                    'position' => $posicao + 1,
                    'title' => $card['title'],
                ]);

                TaskStageMovement::query()->create([
                    'task_id' => $task->id,
                    'moved_by' => $gestora->id,
                    'to_board_stage_id' => $backlog->id,
                ]);

                foreach ($card['criteria'] as $numero => $descricao) {
                    TaskAcceptanceCriterion::query()->create([
                        'task_id' => $task->id,
                        'number' => $numero + 1,
                        'description' => $descricao,
                    ]);
                }
            }

            $project->forceFill(['last_task_number' => count(self::CARDS)])->save();
        });

        $this->command?->info('Dado de exemplo criado (cliente, 3 pessoas, projeto EXE com 3 cards).');
    }

    private function pessoa(string $nome, string $email, RoleEnum $papel, int $horas): User
    {
        return User::query()->create([
            'role_id' => $papel->value,
            'name' => Exemplo::nome($nome),
            'email' => Exemplo::email($email),
            'password' => Str::password(32),
            'must_change_password' => true,
            'daily_hours' => $horas,
        ]);
    }
}
