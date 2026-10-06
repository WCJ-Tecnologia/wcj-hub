<?php

namespace App\Http\Middleware\McpPoc;

use App\Platform\Mcp\ConexaoAtual;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Toda chamada ao /mcp vai para storage/logs/mcp.log (IA12: só ids e nomes, nunca o token).
 * No "initialize" loga o clientInfo: é como o assistente se apresenta no protocolo (pergunta 2).
 * Roda antes da autenticação, para registrar também o 401 da primeira tentativa.
 */
class RegistraChamadaMcp
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->forgetInstance(ConexaoAtual::class);
        $inicio = microtime(true);
        $corpo = json_decode((string) $request->getContent(), true);
        $metodo = is_array($corpo) ? ($corpo['method'] ?? null) : null;
        $params = is_array($corpo) && is_array($corpo['params'] ?? null) ? $corpo['params'] : [];

        $resposta = $next($request);

        $conexao = app()->bound(ConexaoAtual::class) ? app(ConexaoAtual::class) : null;

        Log::channel('mcp')->info('mcp.chamada', array_filter([
            'metodo_http' => $request->method(),
            'metodo' => $metodo,
            'ferramenta' => $metodo === 'tools/call' ? ($params['name'] ?? null) : null,
            'client_info' => $metodo === 'initialize' ? ($params['clientInfo'] ?? null) : null,
            'protocol_version' => $metodo === 'initialize' ? ($params['protocolVersion'] ?? null) : $request->header('MCP-Protocol-Version'),
            'tem_token' => $request->bearerToken() !== null,
            'user_id' => $conexao?->user->id,
            'ai_authorization_id' => $conexao?->autorizacao->id,
            'assistente' => $conexao?->autorizacao->aiAssistant?->slug,
            'client_name' => $conexao?->nomeDoCliente,
            'status' => $resposta->getStatusCode(),
            'ms' => (int) round((microtime(true) - $inicio) * 1000),
            'user_agent' => $request->userAgent(),
        ], fn ($v) => $v !== null));

        return $resposta;
    }
}
