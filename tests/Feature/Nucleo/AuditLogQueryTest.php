<?php

namespace Tests\Feature\Nucleo;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AiAssistant;
use App\Platform\Models\AiAuthorization;
use App\Platform\Models\AuditLog;
use App\Platform\Models\Client;
use App\Platform\Models\Contact;
use App\Platform\Models\User;
use App\Platform\Services\AuditLogQuery;
use App\Platform\Services\ContactService;
use App\Support\LocalTime;
use Database\Seeders\AiAssistantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * B1 T20: a consulta da tela de auditoria (Auditoria AC 3, 5, 6, 10; F1 §03, §05; NUC-12).
 * Cada filtro, o período padrão de 7 dias, o *Histórico* sem limite de período, páginas de 50 do
 * mais recente para o mais antigo e a linha legível.
 */
class AuditLogQueryTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogQuery $query;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AiAssistantSeeder::class);
        config(['app.display_timezone' => 'America/Sao_Paulo']);
        $this->travelTo(Carbon::parse('2026-10-06 12:00:00', 'America/Sao_Paulo'));

        $this->query = app(AuditLogQuery::class);
        $this->admin = User::factory()->admin()->create(['name' => 'Ana Admin']);
    }

    /** Uma linha gravada no instante local informado. */
    private function log(array $attributes = [], ?string $at = null): AuditLog
    {
        if ($at !== null) {
            $this->travelTo(Carbon::parse($at, LocalTime::zone()));
        }

        $log = AuditLog::query()->create([
            'user_id' => $this->admin->id,
            'action' => 'updated',
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'auditable_type' => 'user',
            'auditable_id' => $this->admin->id,
            ...$attributes,
        ]);

        $this->travelTo(Carbon::parse('2026-10-06 12:00:00', LocalTime::zone()));

        return $log;
    }

    /** @return list<int> os ids da primeira página, na ordem da tela */
    private function ids(array $input): array
    {
        return array_column($this->query->paginate($this->query->filters($input))->items(), 'id');
    }

    // AC 5: período padrão, últimos 7 dias (hoje e os seis antes), pelo dia local.
    public function test_periodo_padrao_e_dos_ultimos_7_dias(): void
    {
        $dentro = $this->log(at: '2026-09-30 00:30:00');
        $fora = $this->log(at: '2026-09-29 23:30:00');

        $this->assertSame('2026-09-30', $this->query->filters([])['from']);
        $this->assertSame([$dentro->id], $this->ids([]));
        $this->assertNotContains($fora->id, $this->ids([]));
    }

    // AC 6: o Histórico filtra pelo tipo e id do registro, sem limite de período.
    public function test_historico_filtra_tipo_e_id_sem_limite_de_periodo(): void
    {
        $maria = User::factory()->developer()->create(['name' => 'Maria']);
        $outro = User::factory()->developer()->create();
        $client = Client::factory()->create();
        $antigo = $this->log(['auditable_id' => $maria->id], '2025-01-10 10:00:00');
        $recente = $this->log(['auditable_id' => $maria->id]);
        $this->log(['auditable_id' => $outro->id]);
        $this->log(['auditable_type' => 'client', 'auditable_id' => $maria->id]);
        $this->log(['auditable_type' => 'client', 'auditable_id' => $client->id]);

        $filters = $this->query->filters(['type' => 'user', 'id' => (string) $maria->id]);

        $this->assertNull($filters['from']);
        $this->assertSame([$recente->id, $antigo->id], $this->ids(['type' => 'user', 'id' => (string) $maria->id]));
    }

    // AC 5 e edge case: período escolhido (de/até, dia local), maior que 1 ano, ou sem limite.
    public function test_periodo_escolhido_de_ate_e_sem_limite(): void
    {
        $velho = $this->log(at: '2025-03-01 10:00:00');
        $meio = $this->log(at: '2026-09-10 23:30:00');
        $novo = $this->log(at: '2026-10-05 08:00:00');

        $this->assertSame([$meio->id], $this->ids(['from' => '2026-09-10', 'to' => '2026-09-10']));
        $this->assertSame([$meio->id, $velho->id], $this->ids(['from' => '2025-01-01', 'to' => '2026-09-30']));
        $this->assertSame([$novo->id, $meio->id, $velho->id], $this->ids(['from' => null]));
    }

    // AC 5: Quem.
    public function test_filtro_quem(): void
    {
        $dev = User::factory()->developer()->create();
        $doDev = $this->log(['user_id' => $dev->id]);
        $this->log();

        $this->assertSame([$doDev->id], $this->ids(['user_id' => (string) $dev->id]));
    }

    // AC 3 e 5: Canal (Tela = sem autorização de IA; IA = pelo assistente da autorização).
    public function test_filtro_e_rotulo_do_canal(): void
    {
        $authorization = fn (int $assistant) => AiAuthorization::query()->create([
            'user_id' => $this->admin->id,
            'ai_assistant_id' => $assistant,
            'oauth_client_id' => (string) Str::uuid(),
        ]);
        $tela = $this->log();
        $claude = $this->log(['ai_authorization_id' => $authorization(AiAssistant::CLAUDE)->id]);
        $codex = $this->log(['ai_authorization_id' => $authorization(AiAssistant::CODEX)->id]);

        $this->assertSame([$tela->id], $this->ids(['channel' => 'screen']));
        $this->assertSame([$claude->id], $this->ids(['channel' => 'claude']));
        $this->assertSame([$codex->id], $this->ids(['channel' => 'codex']));

        $rows = collect($this->query->paginate($this->query->filters([]))->items())->keyBy('id');
        $this->assertSame('Tela', $rows[$tela->id]['channel']);
        $this->assertSame('IA: Claude', $rows[$claude->id]['channel']);
        $this->assertSame('IA: Codex', $rows[$codex->id]['channel']);
    }

    // AC 5: Ação, Tipo de registro e Resultado.
    public function test_filtros_acao_tipo_e_resultado(): void
    {
        $client = Client::factory()->create();
        $criado = $this->log(['action' => 'created']);
        $doCliente = $this->log(['auditable_type' => 'client', 'auditable_id' => $client->id]);
        $negado = $this->log(['action' => 'viewed', 'outcome' => AuditLog::OUTCOME_DENIED, 'auditable_id' => null]);

        $this->assertSame([$criado->id], $this->ids(['action' => 'created']));
        $this->assertSame([$doCliente->id], $this->ids(['type' => 'client']));
        $this->assertSame([$negado->id], $this->ids(['outcome' => 'denied']));
        $this->assertSame([$doCliente->id, $criado->id], $this->ids(['outcome' => 'success']));
    }

    // AC 5: busca pela identificação do registro (nome do usuário, do cliente, do contato; CNPJ).
    public function test_busca_pela_identificacao_do_registro(): void
    {
        $maria = User::factory()->developer()->create(['name' => 'Maria Souza']);
        $joao = User::factory()->developer()->create(['name' => 'João Lima']);
        $doces = Client::factory()->create(['name' => 'Maria Doces', 'document' => '12ABC34501DE35']);
        $padaria = Client::factory()->create(['name' => 'Padaria Central']);
        $contato = Contact::factory()->for($padaria)->create(['name' => 'Maria Clara']);

        $doUsuario = $this->log(['auditable_id' => $maria->id]);
        $this->log(['auditable_id' => $joao->id]);
        $doCliente = $this->log(['auditable_type' => 'client', 'auditable_id' => $doces->id]);
        $this->log(['auditable_type' => 'client', 'auditable_id' => $padaria->id]);
        $doContato = $this->log(['auditable_type' => 'contact', 'auditable_id' => $contato->id]);
        // Quem fez não é o registro: a busca não acha a linha pelo autor.
        $this->log(['user_id' => $maria->id, 'auditable_type' => 'client', 'auditable_id' => $padaria->id]);

        $this->assertSame([$doContato->id, $doCliente->id, $doUsuario->id], $this->ids(['search' => 'Maria']));
        $this->assertSame([$doCliente->id], $this->ids(['search' => '12.ABC.345']));
    }

    // AC 3: páginas de 50, do mais recente para o mais antigo.
    public function test_paginas_de_50_do_mais_recente_para_o_mais_antigo(): void
    {
        $primeiro = $this->log(at: '2026-10-01 08:00:00');
        for ($i = 0; $i < 49; $i++) {
            $this->log(at: '2026-10-02 08:00:00');
        }
        $ultimo = $this->log(at: '2026-10-03 08:00:00');

        $page = $this->query->paginate($this->query->filters([]));

        $this->assertSame(50, $page->perPage());
        $this->assertSame(51, $page->total());
        $this->assertCount(50, $page->items());
        $this->assertSame($ultimo->id, $page->items()[0]['id']);
        $this->assertNotContains($primeiro->id, array_column($page->items(), 'id'));
    }

    // AC 3: Data e hora local, Quem, Ação, Registro legível; tipo fora do mapa e registro excluído.
    public function test_linha_legivel(): void
    {
        $maria = User::factory()->developer()->create(['name' => 'Maria']);
        $wcj = Client::factory()->create(['name' => 'WCJ Tecnologia']);
        $joao = Contact::factory()->for($wcj)->create(['name' => 'João']);
        $logs = [
            'usuário' => $this->log(['auditable_id' => $maria->id], '2026-10-05 23:30:00'),
            'cliente' => $this->log(['auditable_type' => 'client', 'auditable_id' => $wcj->id]),
            'contato' => $this->log(['auditable_type' => 'contact', 'auditable_id' => $joao->id]),
            'excluído' => $this->log(['action' => 'deleted', 'auditable_type' => 'client', 'auditable_id' => 999999]),
            'card' => $this->log(['auditable_type' => 'task', 'auditable_id' => 17]),
            'negado sem id' => $this->log(['action' => 'viewed', 'outcome' => AuditLog::OUTCOME_DENIED, 'auditable_id' => null]),
            'tipo antigo' => $this->log(['action' => 'viewed', 'outcome' => AuditLog::OUTCOME_DENIED, 'auditable_type' => 'view-audit-logs', 'auditable_id' => null]),
        ];

        $rows = collect($this->query->paginate($this->query->filters([]))->items())->keyBy('id');
        $record = fn (string $key) => $rows[$logs[$key]->id]['record'];

        $this->assertSame('Usuário Maria', $record('usuário'));
        $this->assertSame('Cliente WCJ Tecnologia', $record('cliente'));
        $this->assertSame('Contato João', $record('contato'));
        $this->assertSame('Cliente #999999', $record('excluído'));
        $this->assertSame('Card #17', $record('card'));
        $this->assertSame('Usuário', $record('negado sem id'));
        $this->assertSame('view-audit-logs', $record('tipo antigo'));

        $usuario = $rows[$logs['usuário']->id];
        $this->assertSame('05/10/2026 23:30:00', $usuario['created_at']);
        $this->assertSame('Ana Admin', $usuario['user']);
        $this->assertSame('Atualizado', $usuario['action_label']);
        $this->assertSame('success', $usuario['outcome']);
        $this->assertSame('Excluído', $rows[$logs['excluído']->id]['action_label']);
        $this->assertSame('denied', $rows[$logs['negado sem id']->id]['outcome']);
    }

    // AC 4: cada campo alterado com rótulo em português e valores legíveis, na ordem do formulário.
    public function test_detalhe_com_rotulo_do_campo_e_valores_legiveis(): void
    {
        $client = Client::factory()->create(['name' => 'Padaria Central']);
        $usuario = $this->log(['changes' => [
            'name' => ['Maria', 'Maria Souza'],
            'role_id' => [RoleEnum::DEVELOPER->value, RoleEnum::PROJECT_MANAGER->value],
            'daily_hours' => ['8.00', '4.50'],
            'ai_write_allowed' => [true, false],
            'active' => [true, false],
        ]]);
        $cliente = $this->log(['auditable_type' => 'client', 'auditable_id' => $client->id, 'changes' => [
            'country' => ['BR', 'NL'],
            'document' => ['12ABC34501DE35', null],
        ]]);
        $contato = $this->log(['auditable_type' => 'contact', 'auditable_id' => 1, 'changes' => [
            'client_id' => [null, $client->id],
            'email' => [null, 'joao@padaria.test'],
            'phone' => ['11999990000', '11988887777'],
        ]]);

        $rows = collect($this->query->paginate($this->query->filters([]))->items())->keyBy('id');

        $this->assertSame([
            ['field' => 'name', 'label' => 'Nome', 'before' => 'Maria', 'after' => 'Maria Souza'],
            ['field' => 'role_id', 'label' => 'Papel', 'before' => 'Desenvolvedor', 'after' => 'Gestor de projetos'],
            ['field' => 'daily_hours', 'label' => 'Carga diária', 'before' => '8,00 h', 'after' => '4,50 h'],
            ['field' => 'ai_write_allowed', 'label' => 'IA pode escrever', 'before' => 'Sim', 'after' => 'Não'],
            ['field' => 'active', 'label' => 'Situação', 'before' => 'Ativo', 'after' => 'Desativado'],
        ], $rows[$usuario->id]['changes']);
        $this->assertSame([
            ['field' => 'country', 'label' => 'País', 'before' => 'Brasil', 'after' => 'Países Baixos'],
            ['field' => 'document', 'label' => 'CNPJ / identificador', 'before' => '12ABC34501DE35', 'after' => null],
        ], $rows[$cliente->id]['changes']);
        $this->assertSame([
            ['field' => 'email', 'label' => 'E-mail', 'before' => null, 'after' => 'joao@padaria.test'],
            ['field' => 'phone', 'label' => 'Telefone', 'before' => '11999990000', 'after' => '11988887777'],
            ['field' => 'client_id', 'label' => 'Cliente', 'before' => null, 'after' => 'Padaria Central'],
        ], $rows[$contato->id]['changes']);
    }

    // N12 e AC 9: o contato anonimizado não devolve dado pessoal pela tela, nem pelo "Registro".
    public function test_contato_anonimizado_nao_aparece_com_dado_pessoal(): void
    {
        $contacts = app(ContactService::class);
        $client = Client::factory()->create(['name' => 'Padaria Central']);
        ['contact' => $contact] = $contacts->create($client, ['name' => 'Joana Prado', 'email' => 'joana@prado.test', 'phone' => '11977776666'], $this->admin);
        $contacts->update($contact, ['name' => 'Joana P. Prado', 'email' => 'joana@prado.test', 'phone' => ''], $this->admin);
        $contacts->anonymize($contact, $this->admin);

        $page = $this->query->paginate($this->query->filters(['type' => 'contact', 'id' => (string) $contact->id]));
        $json = (string) json_encode($page->items(), JSON_UNESCAPED_UNICODE);

        $this->assertCount(3, $page->items());
        $this->assertSame('Contato removido a pedido do titular', $page->items()[0]['record']);
        $this->assertSame('Anonimizado', $page->items()[0]['action_label']);
        foreach (['Joana', 'joana@prado.test', '11977776666'] as $pessoal) {
            $this->assertStringNotContainsString($pessoal, $json);
        }
    }

    // AC 10: a consulta sem linha devolve página vazia (a tela mostra "Nenhum registro com esses filtros").
    public function test_filtro_sem_linha_devolve_pagina_vazia(): void
    {
        $this->log();

        $page = $this->query->paginate($this->query->filters(['action' => 'anonymized']));

        $this->assertSame(0, $page->total());
        $this->assertSame([], $page->items());
    }
}
