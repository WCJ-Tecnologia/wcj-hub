<?php

namespace App\Platform\Mcp\Tools;

use App\Platform\Enums\AuditActionEnum;
use App\Platform\Mcp\ConexaoAtual;
use App\Platform\Services\AuditService;
use App\Platform\Services\ConexaoIaService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('anotar')]
#[Description('Escrita. Grava uma nota curta (até 200 caracteres) na auditoria do WCJ Hub, em nome do usuário e com o canal deste assistente. Confirme com o usuário antes de chamar.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsIdempotent(false)]
#[IsOpenWorld(false)]
class Anotar extends Tool
{
    public function handle(Request $request, ConexaoAtual $conexao, AuditService $auditoria): Response
    {
        // O servidor confere o escopo: a anotação de escrita é só ergonomia (IA9).
        if (! $conexao->pode(ConexaoIaService::ESCREVER)) {
            return Response::error('escopo insuficiente: esta conexão só lê (falta poc:write)');
        }

        $dados = $request->validate(
            ['texto' => ['required', 'string', 'max:200']],
            ['texto.required' => 'informe o texto da nota', 'texto.max' => 'a nota tem no máximo 200 caracteres'],
        );

        $linha = $auditoria->log(
            AuditActionEnum::POC_NOTE,
            $conexao->autorizacao,
            null,
            ['nota' => $dados['texto']],
            $conexao->user->id,
            $conexao->autorizacao->id,
        );

        return Response::text("Nota gravada na auditoria (registro {$linha->id}, canal ia:{$conexao->autorizacao->aiAssistant?->slug}).");
    }

    /**
     * Com POC_MCP_ESCONDER_ESCRITA=true a ferramenta nem aparece para conexão só de leitura
     * (para o caso de o ChatGPT Pro recusar conector que lista escrita). Padrão: aparece, e o
     * servidor recusa a chamada com "escopo insuficiente".
     */
    public function shouldRegister(): bool
    {
        if (! config('mcp_poc.esconder_escrita')) {
            return true;
        }

        return app()->bound(ConexaoAtual::class) && app(ConexaoAtual::class)->pode(ConexaoIaService::ESCREVER);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'texto' => $schema->string()->description('A nota, até 200 caracteres.')->max(200)->required(),
        ];
    }
}
