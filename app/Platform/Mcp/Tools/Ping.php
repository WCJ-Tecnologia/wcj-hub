<?php

namespace App\Platform\Mcp\Tools;

use App\Platform\Mcp\ConexaoAtual;
use App\Platform\Services\ConexaoIaService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ping')]
#[Description('Leitura. Mostra quem é o usuário dono desta conexão no WCJ Hub (nome e papel), qual assistente o Hub reconheceu, os escopos do token e a hora do servidor.')]
#[IsReadOnly]
#[IsIdempotent]
class Ping extends Tool
{
    public function handle(Request $request, ConexaoAtual $conexao): Response
    {
        if (! $conexao->pode(ConexaoIaService::LER)) {
            return Response::error('escopo insuficiente: esta conexão não tem poc:read');
        }

        $agora = now();

        return Response::json([
            'usuario' => [
                'nome' => $conexao->user->name,
                'papel' => $conexao->user->type()->label(),
            ],
            'assistente' => [
                'reconhecido_como' => $conexao->autorizacao->aiAssistant?->label,
                'canal' => 'ia:'.$conexao->autorizacao->aiAssistant?->slug,
                'nome_declarado_pelo_cliente' => $conexao->nomeDoCliente,
            ],
            'conexao' => $conexao->autorizacao->id,
            'escopos' => $conexao->escopos,
            'hora_do_servidor' => [
                'utc' => $agora->toIso8601String(),
                'sao_paulo' => $agora->copy()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s'),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
