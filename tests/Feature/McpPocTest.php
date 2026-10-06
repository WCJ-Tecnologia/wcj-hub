<?php

namespace Tests\Feature;

use App\Platform\Models\AiAssistant;
use App\Platform\Models\AiAuthorization;
use App\Platform\Models\AuditLog;
use App\Platform\Models\User;
use Database\Seeders\AiAssistantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Monolog\Handler\NullHandler;
use Tests\TestCase;

/**
 * Prova de conceito do servidor MCP (D-GER-17, branch poc/mcp). O fluxo é o de verdade:
 * registro dinâmico → authorize com PKCE → aprovar → trocar o código → chamar o /mcp com o token.
 */
class McpPocTest extends TestCase
{
    use RefreshDatabase;

    private const CALLBACK_CLAUDE = 'https://claude.ai/api/mcp/auth_callback';

    private const CALLBACK_CHATGPT = 'https://chatgpt.com/connector_platform_oauth_redirect';

    private ?string $ultimoScope = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AiAssistantSeeder::class);

        // O log da prova (storage/logs/mcp.log) é para o teste manual, não para a suíte.
        config(['logging.channels.mcp' => ['driver' => 'monolog', 'handler' => NullHandler::class]]);

        if (! file_exists(Passport::keyPath('oauth-private.key'))) {
            Artisan::call('passport:keys', ['--force' => true]);
        }
    }

    public function test_metadados_anunciam_oauth_com_pkce_registro_e_escopos_da_casa(): void
    {
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('issuer', url('/'))
            ->assertJsonPath('authorization_endpoint', url('/oauth/authorize'))
            ->assertJsonPath('token_endpoint', url('/oauth/token'))
            ->assertJsonPath('registration_endpoint', url('/oauth/register'))
            ->assertJsonPath('code_challenge_methods_supported', ['S256'])
            ->assertJsonPath('scopes_supported', ['poc:read', 'poc:write'])
            ->assertJsonPath('token_endpoint_auth_methods_supported', ['none'])
            ->assertJsonMissingPath('client_id_metadata_document_supported');

        $this->getJson('/.well-known/oauth-protected-resource/mcp')
            ->assertOk()
            ->assertJsonPath('resource', url('/mcp'))
            ->assertJsonPath('authorization_servers', [url('/')]);

        config(['mcp_poc.cimd' => true]);
        $this->getJson('/.well-known/oauth-authorization-server')->assertJsonPath('client_id_metadata_document_supported', true);
    }

    public function test_mcp_sem_token_devolve_401_apontando_os_metadados(): void
    {
        $resposta = $this->postJson('/mcp', $this->rpc('initialize', [
            'protocolVersion' => '2025-06-18', 'capabilities' => [], 'clientInfo' => ['name' => 'teste', 'version' => '1'],
        ]));

        $resposta->assertStatus(401);
        $this->assertStringContainsString(
            'resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp').'"',
            (string) $resposta->headers->get('WWW-Authenticate'),
        );
    }

    public function test_ping_com_token_de_leitura_mostra_usuario_assistente_e_escopos(): void
    {
        $dev = User::factory()->developer()->create(['name' => 'Ana Dev', 'ai_write_allowed' => false]);
        $token = $this->conectar($dev, 'Claude');

        $ping = $this->chamarFerramenta($token, 'ping');

        $ping->assertOk()->assertJsonPath('result.isError', false);
        $dados = json_decode($ping->json('result.content.0.text'), true);
        $this->assertSame('Ana Dev', $dados['usuario']['nome']);
        $this->assertSame('Desenvolvedor', $dados['usuario']['papel']);
        $this->assertSame('ia:claude', $dados['assistente']['canal']);
        $this->assertSame(['poc:read'], $dados['escopos']);
        $this->assertArrayHasKey('utc', $dados['hora_do_servidor']);
    }

    public function test_anotar_com_token_so_de_leitura_recusa_e_nao_grava(): void
    {
        $dev = User::factory()->developer()->create(['ai_write_allowed' => false]);
        $token = $this->conectar($dev, 'Claude');

        $this->chamarFerramenta($token, 'anotar', ['texto' => 'não deveria gravar'])
            ->assertOk()
            ->assertJsonPath('result.isError', true)
            ->assertJsonPath('result.content.0.text', 'escopo insuficiente: esta conexão só lê (falta poc:write)');

        $this->assertSame(0, AuditLog::query()->where('action', 'poc_note')->count());
    }

    public function test_anotar_com_escrita_grava_na_auditoria_com_o_canal(): void
    {
        $dev = User::factory()->developer()->create();
        $token = $this->conectar($dev, 'Claude Code (wcj-hub)', 'http://localhost:33418/callback');
        $conexao = AiAuthorization::query()->sole();

        $this->chamarFerramenta($token, 'anotar', ['texto' => 'primeira nota pela IA'])
            ->assertOk()
            ->assertJsonPath('result.isError', false);

        $linha = AuditLog::query()->where('action', 'poc_note')->sole();
        $this->assertSame($dev->id, $linha->user_id);
        $this->assertSame($conexao->id, $linha->ai_authorization_id);
        $this->assertSame('success', $linha->outcome);
        $this->assertSame(['nota' => [null, 'primeira nota pela IA']], $linha->changes);
        $this->assertSame(AiAssistant::CLAUDE, $conexao->ai_assistant_id);
    }

    public function test_anotar_recusa_texto_acima_de_200_caracteres(): void
    {
        $token = $this->conectar(User::factory()->developer()->create(), 'Codex');

        $this->chamarFerramenta($token, 'anotar', ['texto' => str_repeat('a', 201)])
            ->assertJsonPath('result.isError', true);

        $this->assertSame(0, AuditLog::query()->where('action', 'poc_note')->count());
    }

    public function test_chatgpt_recebe_so_leitura_mesmo_pedindo_escrita(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $this->conectar($admin, 'ChatGPT', self::CALLBACK_CHATGPT, 'poc:read poc:write');

        $this->assertSame(['poc:read'], Passport::token()->newQuery()->sole()->scopes);
        $this->assertSame('poc:read', $this->ultimoScope);
        $this->assertSame(AiAssistant::CHATGPT, AiAuthorization::query()->sole()->ai_assistant_id);

        $this->chamarFerramenta($token, 'anotar', ['texto' => 'x'])
            ->assertJsonPath('result.content.0.text', 'escopo insuficiente: esta conexão só lê (falta poc:write)');
    }

    public function test_com_a_chave_ligada_conexao_so_de_leitura_nao_ve_a_ferramenta_de_escrita(): void
    {
        config(['mcp_poc.esconder_escrita' => true]);
        $leitura = $this->conectar(User::factory()->admin()->create(), 'ChatGPT', self::CALLBACK_CHATGPT);
        $escrita = $this->conectar(User::factory()->developer()->create(), 'Codex');

        $this->assertSame(['ping'], $this->nomesDasFerramentas($leitura));
        $this->assertSame(['ping', 'anotar'], $this->nomesDasFerramentas($escrita));
    }

    public function test_assistente_nao_homologado_e_recusado(): void
    {
        $user = User::factory()->admin()->create();
        $clientId = $this->registrar('Cursor', self::CALLBACK_CLAUDE);
        [$verifier, $desafio] = $this->pkce();

        $this->actingAs($user)->get($this->urlAuthorize($clientId, self::CALLBACK_CLAUDE, $desafio))
            ->assertOk()
            ->assertSee('Assistente não homologado')
            ->assertDontSee('>Autorizar</button>', false);

        // Mesmo forçando a aprovação, a troca do código é negada.
        $codigo = $this->aprovar();
        $this->post('/oauth/token', $this->pedidoDeToken($clientId, self::CALLBACK_CLAUDE, $verifier, $codigo))
            ->assertJsonPath('error', 'access_denied');
        $this->assertSame(0, AiAuthorization::query()->count());
    }

    public function test_reconectar_o_mesmo_assistente_revoga_a_conexao_anterior(): void
    {
        $dev = User::factory()->developer()->create();
        $tokenApp = $this->conectar($dev, 'Claude');
        $tokenCode = $this->conectar($dev, 'Claude Code (wcj-hub)', 'http://127.0.0.1:40000/callback');

        $this->assertSame(1, AiAuthorization::query()->whereNull('revoked_at')->count());
        $this->assertSame(2, AiAuthorization::query()->count());
        $this->chamarFerramenta($tokenApp, 'ping')->assertStatus(401);
        $this->chamarFerramenta($tokenCode, 'ping')->assertOk();
    }

    public function test_usuario_desativado_recebe_nao_autorizado(): void
    {
        $dev = User::factory()->developer()->create();
        $token = $this->conectar($dev, 'Codex');
        $dev->forceFill(['deactivated_at' => now()])->save();

        $this->chamarFerramenta($token, 'ping')->assertStatus(401);
    }

    public function test_token_emitido_para_outro_recurso_e_recusado(): void
    {
        $token = $this->conectar(User::factory()->developer()->create(), 'Claude');

        // Mesmo token, servidor que agora se anuncia em outro caminho: o aud não confere.
        config(['mcp_poc.path' => 'outro-servidor']);

        $this->chamarFerramenta($token, 'ping')
            ->assertStatus(401)
            ->assertJsonPath('error_description', 'não autorizado: token emitido para outro recurso');
    }

    public function test_authorize_exige_pkce_s256_e_recusa_outro_recurso(): void
    {
        $user = User::factory()->admin()->create();
        $clientId = $this->registrar('Claude', self::CALLBACK_CLAUDE);
        [, $desafio] = $this->pkce();

        $this->actingAs($user)->get($this->urlAuthorize($clientId, self::CALLBACK_CLAUDE, $desafio, metodo: 'plain'))
            ->assertStatus(400)->assertJsonPath('error', 'invalid_request');
        $this->actingAs($user)->get($this->urlAuthorize($clientId, self::CALLBACK_CLAUDE, $desafio, recurso: 'https://outro.exemplo.com/mcp'))
            ->assertStatus(400)->assertJsonPath('error', 'invalid_target');
    }

    public function test_resposta_de_autorizacao_traz_iss_e_redirect_loopback_ignora_a_porta(): void
    {
        $user = User::factory()->admin()->create();
        $clientId = $this->registrar('Codex', 'http://localhost:1455/auth/callback');
        [, $desafio] = $this->pkce();

        $this->actingAs($user)->get($this->urlAuthorize($clientId, 'http://localhost:61234/auth/callback', $desafio))->assertOk();
        $destino = $this->post('/oauth/authorize', ['auth_token' => session('authToken')])->headers->get('Location');

        $this->assertStringStartsWith('http://localhost:61234/auth/callback?', $destino);
        $this->assertStringContainsString('iss='.rawurlencode(url('/')), $destino);
    }

    public function test_refresh_invalido_devolve_invalid_grant(): void
    {
        $clientId = $this->registrar('Claude', self::CALLBACK_CLAUDE);

        $this->post('/oauth/token', ['grant_type' => 'refresh_token', 'client_id' => $clientId, 'refresh_token' => 'lixo'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');
    }

    public function test_cimd_cria_o_cliente_a_partir_do_documento_do_fornecedor(): void
    {
        config(['mcp_poc.cimd' => true]);
        $url = 'https://claude.ai/oauth/mcp-oauth-client-metadata';
        Http::fake([$url => Http::response([
            'client_id' => $url, 'client_name' => 'Claude', 'redirect_uris' => [self::CALLBACK_CLAUDE],
            'token_endpoint_auth_method' => 'none',
        ])]);

        $token = $this->conectar(User::factory()->developer()->create(), $url, self::CALLBACK_CLAUDE, registrar: false);

        $this->chamarFerramenta($token, 'ping')->assertOk()->assertJsonPath('result.isError', false);
        $this->assertSame('Claude', Passport::client()->newQuery()->sole()->name);
    }

    // --- fluxo OAuth -------------------------------------------------------------------

    private function conectar(User $user, string $nomeOuUrl, string $redirect = self::CALLBACK_CLAUDE, string $escopo = 'poc:read poc:write', bool $registrar = true): string
    {
        $clientId = $registrar ? $this->registrar($nomeOuUrl, $redirect) : $nomeOuUrl;
        [$verifier, $desafio] = $this->pkce();

        $this->actingAs($user)->get($this->urlAuthorize($clientId, $redirect, $desafio, $escopo))->assertOk();
        $codigo = $this->aprovar();

        $resposta = $this->post('/oauth/token', $this->pedidoDeToken($clientId, $redirect, $verifier, $codigo))->assertOk();
        $token = $resposta->json('access_token');
        $this->ultimoScope = $resposta->json('scope');

        $this->app['auth']->forgetGuards();

        return $token;
    }

    private function registrar(string $nome, string $redirect): string
    {
        return $this->postJson('/oauth/register', ['client_name' => $nome, 'redirect_uris' => [$redirect]])
            ->assertCreated()
            ->assertJsonPath('scope', 'poc:read poc:write')
            ->json('client_id');
    }

    private function aprovar(): string
    {
        $destino = (string) $this->post('/oauth/authorize', ['auth_token' => session('authToken')])
            ->assertRedirect()
            ->headers->get('Location');
        parse_str((string) parse_url($destino, PHP_URL_QUERY), $query);
        $this->assertSame(url('/'), $query['iss'] ?? null);

        return $query['code'];
    }

    /** @return array<string, string> */
    private function pedidoDeToken(string $clientId, string $redirect, string $verifier, string $codigo): array
    {
        return [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'redirect_uri' => $redirect,
            'code_verifier' => $verifier,
            'code' => $codigo,
            'resource' => url('/mcp'),
        ];
    }

    private function urlAuthorize(string $clientId, string $redirect, string $desafio, string $escopo = 'poc:read poc:write', string $metodo = 'S256', ?string $recurso = null): string
    {
        return '/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirect,
            'scope' => $escopo,
            'state' => 'estado-123',
            'code_challenge' => $desafio,
            'code_challenge_method' => $metodo,
            'resource' => $recurso ?? url('/mcp'),
        ]);
    }

    /** @return array{0: string, 1: string} */
    private function pkce(): array
    {
        $verifier = Str::random(64);

        return [$verifier, rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=')];
    }

    // --- MCP ---------------------------------------------------------------------------

    /** @param  array<string, mixed>  $argumentos */
    private function chamarFerramenta(string $token, string $nome, array $argumentos = []): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json, text/event-stream'])
            ->postJson('/mcp', $this->rpc('tools/call', ['name' => $nome, 'arguments' => (object) $argumentos]));
    }

    /** @return list<string> */
    private function nomesDasFerramentas(string $token): array
    {
        $this->app['auth']->forgetGuards();

        return array_column($this->withHeaders(['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json, text/event-stream'])
            ->postJson('/mcp', $this->rpc('tools/list', []))
            ->assertOk()
            ->json('result.tools'), 'name');
    }

    /** @param  array<string, mixed>  $params
     * @return array<string, mixed> */
    private function rpc(string $metodo, array $params): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'method' => $metodo, 'params' => $params];
    }
}
