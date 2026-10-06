<?php

namespace App\Platform\Mcp\Oauth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController;

/**
 * Registro dinâmico (RFC 7591) do laravel/mcp, com duas mudanças da prova: loga o que cada
 * assistente declara (pergunta 2: como ele se identifica) e devolve os escopos da casa.
 * O Passport não guarda client_uri nem logo_uri (não há coluna): só o log os registra.
 */
class RegistroDinamicoController extends OAuthRegisterController
{
    public function __invoke(Request $request): JsonResponse
    {
        $resposta = parent::__invoke($request);
        $dados = $resposta->getData(true);

        Log::channel('mcp')->info('cliente.registrado', [
            'metodo' => 'dcr',
            'status' => $resposta->getStatusCode(),
            'client_id' => $dados['client_id'] ?? null,
            'client_name' => $request->input('client_name') ?? $request->input('name'),
            'redirect_uris' => $request->input('redirect_uris'),
            'client_uri' => $request->input('client_uri'),
            'logo_uri' => $request->input('logo_uri'),
            'software_id' => $request->input('software_id'),
            'software_version' => $request->input('software_version'),
            'scope_pedido' => $request->input('scope'),
            'grant_types' => $request->input('grant_types'),
            'token_endpoint_auth_method' => $request->input('token_endpoint_auth_method'),
            'campos' => array_keys($request->all()),
            'user_agent' => $request->userAgent(),
            'erro' => $dados['error_description'] ?? null,
        ]);

        if ($resposta->getStatusCode() === 201) {
            $dados['scope'] = implode(' ', array_keys(config('mcp_poc.escopos')));
            $resposta->setData($dados);
        }

        return $resposta;
    }
}
