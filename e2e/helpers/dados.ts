import { spawnSync } from 'node:child_process';

/**
 * Prepara no banco do e2e o que o spec precisa sem passar pela tela, pelo tinker com
 * APP_ENV=e2e: o mesmo banco que o e2e:prepare monta, nunca o de dev.
 */
function tinker(code: string): string {
    const result = spawnSync('php', ['artisan', 'tinker', '--execute', code], {
        env: { ...process.env, APP_ENV: 'e2e' },
        encoding: 'utf8',
    });

    if (result.status !== 0) throw new Error(`tinker falhou (exit ${result.status}): ${result.stderr || result.stdout}`);

    return result.stdout.trim();
}

/**
 * Cliente de fora do Brasil (documento opcional: N5). Sem projeto: até o B2, quem conta os
 * projetos abertos é a implementação vazia do ClientManagement, que responde zero. Devolve o id.
 */
export function createClient(name: string): number {
    return Number(
        tinker(`
            $client = App\\Platform\\Models\\Client::query()->create(['name' => ${JSON.stringify(name)}, 'country' => 'US', 'document' => null]);
            echo $client->id;
        `),
    );
}

/** Contato ativo no cliente, sem e-mail nem telefone (N8: os dois são opcionais). Devolve o id. */
export function createContact(clientId: number, name: string): number {
    return Number(
        tinker(`
            $contact = App\\Platform\\Models\\Contact::query()->create(['client_id' => ${clientId}, 'name' => ${JSON.stringify(name)}]);
            echo $contact->id;
        `),
    );
}

/** Desativa o contato fora da tela, para o spec começar pelo Reativar. */
export function deactivateContact(id: number): void {
    tinker(`App\\Platform\\Models\\Contact::query()->whereKey(${id})->update(['deactivated_at' => now()]);`);
}

/** Apaga o que o spec criou: os contatos do cliente e o próprio cliente. */
export function deleteClient(id: number): void {
    tinker(`
        App\\Platform\\Models\\Contact::query()->where('client_id', ${id})->delete();
        App\\Platform\\Models\\Client::query()->whereKey(${id})->delete();
    `);
}

/** Desativa o cliente fora da tela, como outro administrador faria com o formulário já aberto. */
export function deactivateClient(id: number): void {
    tinker(`App\\Platform\\Models\\Client::query()->whereKey(${id})->update(['deactivated_at' => now()]);`);
}

/**
 * Apaga o cliente com este documento (sem máscara, como gravado) e os contatos dele. Não faz nada
 * quando não existe: serve de limpeza em finally, depois de o teste já ter excluído pela tela.
 */
export function deleteClientByDocument(document: string): void {
    tinker(`
        $ids = App\\Platform\\Models\\Client::query()->where('document', ${JSON.stringify(document)})->pluck('id');
        App\\Platform\\Models\\Contact::query()->whereIn('client_id', $ids)->delete();
        App\\Platform\\Models\\Client::query()->whereKey($ids)->delete();
    `);
}

/** Apaga os contatos com este nome. Não faz nada quando não existem (limpeza em finally). */
export function deleteContactsNamed(name: string): void {
    tinker(`App\\Platform\\Models\\Contact::query()->where('name', ${JSON.stringify(name)})->delete();`);
}

/**
 * Usuário ativo com o papel (1 admin, 2 gestor, 3 dev, 4 comercial), para alocar em projeto ou
 * entrar com outro papel (B2 passo 2). Sem `password`, nasce com uma aleatória que ninguém usa.
 * Devolve o id; o e-mail é `<slug>@e2e.test`, previsível para o login.
 */
export function createUser(name: string, roleId: 1 | 2 | 3 | 4, password?: string): number {
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '.');
    return Number(
        tinker(`
            $user = App\\Platform\\Models\\User::query()->create([
                'role_id' => ${roleId}, 'name' => ${JSON.stringify(name)}, 'email' => ${JSON.stringify(`${slug}@e2e.test`)},
                'password' => ${password ? JSON.stringify(password) : 'Illuminate\\Support\\Str::password(32)'}, 'must_change_password' => false, 'daily_hours' => 8,
            ]);
            echo $user->id;
        `),
    );
}

/**
 * Projeto do B5 com o quadro padrão e os 5 tipos, o dev alocado e duas tarefas no Backlog (gravadas
 * direto, com o número pelo contador, sem tipo nem prioridade, como o card rápido do B3). Devolve os ids.
 */
export function createSprintProject(prefix: string, devId: number): { projectId: number; taskIds: number[] } {
    const out = tinker(`
        $admin = App\\Platform\\Models\\User::query()->where('email', 'like', '%e2e%')->where('role_id', 1)->orderBy('id')->firstOrFail();
        $client = App\\Platform\\Models\\Client::query()->create(['name' => ${JSON.stringify(`Cliente ${prefix}`)}, 'country' => 'US', 'document' => null]);
        $project = App\\Domains\\Projetos\\Models\\Project::query()->create([
            'client_id' => $client->id, 'manager_id' => $admin->id, 'key_prefix' => ${JSON.stringify(prefix)},
            'name' => ${JSON.stringify(`Sprints ${prefix}`)}, 'starts_on' => now('America/Sao_Paulo')->toDateString(),
        ]);
        app(App\\Domains\\Projetos\\Services\\BoardStageService::class)->createDefaultBoard($project);
        app(App\\Domains\\Projetos\\Services\\TaskTypeService::class)->createDefaults($project);
        $project->members()->create(['user_id' => ${devId}]);
        $queue = $project->boardStages()->where('stage_role_id', 1)->value('id');
        $ids = [];
        foreach ([['Tela de recurso de multa', 16], ['Filtro por placa', null]] as [$title, $hours]) {
            $project->increment('last_task_number');
            $ids[] = App\\Domains\\Projetos\\Models\\Task::query()->create([
                'project_id' => $project->id, 'number' => $project->last_task_number, 'board_stage_id' => $queue,
                'title' => $title, 'developer_id' => ${devId}, 'estimated_hours' => $hours,
            ])->id;
        }
        echo $project->id.','.implode(',', $ids);
    `);
    // O tinker devolve exit 0 mesmo quando o código lança: confere a saída.
    const last = out.split(/\s+/).pop() ?? '';
    if (!/^\d+(,\d+)+$/.test(last)) throw new Error(`createSprintProject falhou: ${out}`);
    const [projectId, ...taskIds] = last.split(',').map(Number);
    return { projectId, taskIds };
}

/**
 * Projeto do B4 com o quadro padrão, o dev alocado, uma sprint aberta e um card em In test, do dev
 * (o admin do e2e não é o dev, então marca o teste cruzado), com o roteiro CA_1, CA_2 (com o subitem
 * CA_2.1) e CA_3, tudo A testar. Devolve o id do projeto.
 */
export function createTestProject(prefix: string, devId: number): number {
    const out = tinker(`
        $admin = App\\Platform\\Models\\User::query()->where('email', 'like', '%e2e%')->where('role_id', 1)->orderBy('id')->firstOrFail();
        $client = App\\Platform\\Models\\Client::query()->create(['name' => ${JSON.stringify(`Cliente ${prefix}`)}, 'country' => 'US', 'document' => null]);
        $project = App\\Domains\\Projetos\\Models\\Project::query()->create([
            'client_id' => $client->id, 'manager_id' => $admin->id, 'key_prefix' => ${JSON.stringify(prefix)},
            'name' => ${JSON.stringify(`Teste ${prefix}`)}, 'starts_on' => now('America/Sao_Paulo')->toDateString(),
        ]);
        app(App\\Domains\\Projetos\\Services\\BoardStageService::class)->createDefaultBoard($project);
        app(App\\Domains\\Projetos\\Services\\TaskTypeService::class)->createDefaults($project);
        $project->members()->create(['user_id' => ${devId}]);
        $sprint = $project->sprints()->create(['name' => 'Sprint 1', 'starts_on' => now('America/Sao_Paulo')->toDateString(), 'ends_on' => now('America/Sao_Paulo')->addDays(9)->toDateString()]);
        $test = $project->boardStages()->where('stage_role_id', 5)->value('id');
        $project->increment('last_task_number');
        $task = App\\Domains\\Projetos\\Models\\Task::query()->create([
            'project_id' => $project->id, 'number' => $project->last_task_number, 'board_stage_id' => $test,
            'sprint_id' => $sprint->id, 'title' => 'Emitir segunda via do boleto', 'developer_id' => ${devId},
        ]);
        App\\Domains\\Projetos\\Models\\TaskStageMovement::query()->create(['task_id' => $task->id, 'moved_by' => $admin->id, 'to_board_stage_id' => $test]);
        $item = fn (int $number, string $text, ?int $parent = null) => $task->acceptanceCriteria()->create(['number' => $number, 'parent_id' => $parent, 'description' => $text, 'test_result_id' => 1]);
        $item(1, 'A segunda via sai com o mesmo código de barras.');
        $erro = $item(2, 'Boleto vencido mostra o valor com juros.');
        $item(1, 'Os juros aparecem separados do valor original.', $erro->id);
        $item(3, 'O download repete o mesmo PDF.');
        echo $project->id;
    `);
    const last = out.split(/\s+/).pop() ?? '';
    if (!/^\d+$/.test(last)) throw new Error(`createTestProject falhou: ${out}`);
    return Number(last);
}

/**
 * Projeto do B7 com o quadro padrão, o dev alocado, uma sprint aberta e cards com movimentos
 * datados para trás (criação, Doing, pausa com motivo, volta de code review com retrabalho, pronto),
 * horas lançadas e um bug de produção ligado ao card pronto. Devolve os ids.
 */
export function createIndicatorsProject(prefix: string, devId: number): { projectId: number; taskIds: number[] } {
    const out = tinker(`
        $admin = App\\Platform\\Models\\User::query()->where('email', 'like', '%e2e%')->where('role_id', 1)->orderBy('id')->firstOrFail();
        $client = App\\Platform\\Models\\Client::query()->create(['name' => ${JSON.stringify(`Cliente ${prefix}`)}, 'country' => 'US', 'document' => null]);
        $project = App\\Domains\\Projetos\\Models\\Project::query()->create([
            'client_id' => $client->id, 'manager_id' => $admin->id, 'key_prefix' => ${JSON.stringify(prefix)},
            'name' => ${JSON.stringify(`Indicadores ${prefix}`)}, 'starts_on' => now('America/Sao_Paulo')->subDays(30)->toDateString(),
        ]);
        app(App\\Domains\\Projetos\\Services\\BoardStageService::class)->createDefaultBoard($project);
        app(App\\Domains\\Projetos\\Services\\TaskTypeService::class)->createDefaults($project);
        $project->members()->create(['user_id' => ${devId}]);
        $sprint = $project->sprints()->create(['name' => 'Sprint 1', 'starts_on' => now('America/Sao_Paulo')->subDays(14)->toDateString(), 'ends_on' => now('America/Sao_Paulo')->addDays(7)->toDateString()]);
        $stage = fn (int $role) => $project->boardStages()->where('stage_role_id', $role)->value('id');
        $at = fn (int $daysAgo) => now()->subDays($daysAgo)->setTime(12, 0);
        $type = $project->taskTypes()->where('slug', 'improvement')->value('id');
        $card = function (string $title, array $attrs, int $createdDaysAgo) use ($project, $stage, $at, $type) {
            $project->increment('last_task_number');
            $task = App\\Domains\\Projetos\\Models\\Task::query()->create([
                'project_id' => $project->id, 'number' => $project->last_task_number, 'board_stage_id' => $stage(1),
                'task_type_id' => $type, 'task_priority_id' => 2, 'title' => $title, ...$attrs,
            ]);
            App\\Domains\\Projetos\\Models\\TaskStageMovement::query()->forceCreate(['task_id' => $task->id, 'moved_by' => $project->manager_id, 'to_board_stage_id' => $stage(1), 'created_at' => $at($createdDaysAgo)]);
            $task->forceFill(['created_at' => $at($createdDaysAgo)])->save();
            return $task;
        };
        $move = function ($task, int $role, int $daysAgo, array $fields = []) use ($stage, $at, $project) {
            App\\Domains\\Projetos\\Models\\TaskStageMovement::query()->forceCreate(['task_id' => $task->id, 'moved_by' => $project->manager_id, 'from_board_stage_id' => $task->board_stage_id, 'to_board_stage_id' => $stage($role), 'created_at' => $at($daysAgo), ...$fields]);
            $task->forceFill(['board_stage_id' => $stage($role)])->save();
        };
        // 1: atrasada, com pausa e volta de code review (retrabalho), 8 h lançadas.
        $t1 = $card('Tela de recurso de multa', ['developer_id' => ${devId}, 'estimated_hours' => 16, 'sprint_id' => $sprint->id, 'due_on' => now('America/Sao_Paulo')->subDays(10)->toDateString()], 20);
        $move($t1, 3, 18); $move($t1, 7, 16, ['reason_text' => 'Aguardando o token do cliente']); $move($t1, 3, 14); $move($t1, 4, 12);
        $reason = App\\Domains\\Projetos\\Models\\ReworkReason::query()->where('requires_text', true)->value('id');
        $move($t1, 3, 10, ['rework_origin_id' => 1, 'rework_reason_id' => $reason, 'reason_text' => 'Faltou o teste do prazo']);
        $t1->timeEntries()->create(['user_id' => ${devId}, 'worked_on' => now('America/Sao_Paulo')->subDays(9)->toDateString(), 'hours' => 8]);
        // 2: no prazo, vence daqui a 3 dias.
        $t2 = $card('Filtro por placa', ['developer_id' => ${devId}, 'estimated_hours' => 8, 'sprint_id' => $sprint->id, 'due_on' => now('America/Sao_Paulo')->addDays(3)->toDateString()], 6);
        $move($t2, 3, 2);
        // 3: em pausa agora, sem dev, entrou em desenvolvimento sem data prevista.
        $t3 = $card('Relatório de medição', ['sprint_id' => $sprint->id], 6);
        $move($t3, 3, 4); $move($t3, 7, 1, ['reason_text' => 'Esperando o layout']);
        // 4: pronta.
        $t4 = $card('Emissão de boleto', ['developer_id' => ${devId}, 'estimated_hours' => 4, 'sprint_id' => $sprint->id, 'due_on' => now('America/Sao_Paulo')->subDays(5)->toDateString()], 15);
        $move($t4, 3, 13); $move($t4, 6, 7);
        // 5: bug de produção da 4, no Backlog.
        $t5 = $card('Boleto sai com valor zerado', ['origin_task_id' => $t4->id], 3);
        echo $project->id.','.implode(',', [$t1->id, $t2->id, $t3->id, $t4->id, $t5->id]);
    `);
    const last = out.split(/\s+/).pop() ?? '';
    if (!/^\d+(,\d+)+$/.test(last)) throw new Error(`createIndicatorsProject falhou: ${out}`);
    const [projectId, ...taskIds] = last.split(',').map(Number);
    return { projectId, taskIds };
}

/**
 * Um aviso não lido para o admin do e2e, como os de Projetos gravam (`text`, `url`, `key`), para o
 * spec do sino começar com algo a marcar. Devolve o id.
 */
export function createNoticeForAdmin(text: string, url: string): string {
    const out = tinker(`
        $admin = App\\Platform\\Models\\User::query()->where('email', 'like', '%e2e%')->where('role_id', 1)->orderBy('id')->firstOrFail();
        $notice = $admin->notifications()->create([
            'id' => (string) Illuminate\\Support\\Str::uuid(),
            'type' => 'App\\\\Domains\\\\Projetos\\\\Notifications\\\\TaskAssigned',
            'data' => ['text' => ${JSON.stringify(text)}, 'url' => ${JSON.stringify(url)}, 'key' => 'E2E-1'],
        ]);
        echo $notice->id;
    `);
    const last = out.split(/\s+/).pop() ?? '';
    if (!/^[0-9a-f-]{36}$/.test(last)) throw new Error(`createNoticeForAdmin falhou: ${out}`);
    return last;
}
