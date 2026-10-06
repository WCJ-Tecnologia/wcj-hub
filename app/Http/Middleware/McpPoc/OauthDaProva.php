<?php

namespace App\Http\Middleware\McpPoc;

use App\Platform\Mcp\Oauth\ClienteCimd;
use App\Platform\Mcp\Oauth\HubAccessToken;
use App\Platform\Mcp\Oauth\HubRegistrar;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * O que o Passport não faz e a prova do MCP precisa (D-GER-17), em todas as rotas /oauth/*:
 * - PKCE só S256 (o league aceita "plain");
 * - indicador de recurso (RFC 8707) conferido no authorize e no token;
 * - client_id como URL (CIMD) traduzido para o cliente do Passport;
 * - "iss" na resposta de autorização (RFC 9207, o ChatGPT exige);
 * - a volta do login (Inertia, XHR) para o authorize vira navegação de página inteira;
 * - log de cada passo em storage/logs/mcp.log.
 */
class OauthDaProva
{
    public function __construct(private readonly ClienteCimd $cimd) {}

    public function handle(Request $request, Closure $next): Response
    {
        $nome = $request->route()?->getName();

        if ($nome === 'passport.authorizations.authorize') {
            if ($request->header('X-Inertia')) {
                return Inertia::location($request->fullUrl());
            }

            if ($erro = $this->conferirPedidoDeAutorizacao($request)) {
                return $erro;
            }
        }

        if ($nome === 'passport.token' && ($erro = $this->conferirPedidoDeToken($request))) {
            return $erro;
        }

        $resposta = $next($request);

        if (in_array($nome, ['passport.authorizations.authorize', 'passport.authorizations.approve', 'passport.authorizations.deny'], true)) {
            $this->acrescentarIss($resposta);
        }

        if ($nome === 'passport.token') {
            $corpo = json_decode((string) $resposta->getContent(), true);
            $corpo = $this->acrescentarScope($resposta, $corpo);
            Log::channel('mcp')->info('token.resposta', [
                'grant' => $request->input('grant_type'),
                'status' => $resposta->getStatusCode(),
                'erro' => is_array($corpo) ? ($corpo['error'] ?? null) : null,
                'erro_descricao' => is_array($corpo) ? ($corpo['error_description'] ?? $corpo['hint'] ?? null) : null,
                'scope' => is_array($corpo) ? ($corpo['scope'] ?? null) : null,
            ]);
        }

        return $resposta;
    }

    private function conferirPedidoDeAutorizacao(Request $request): ?Response
    {
        $clientId = $request->query('client_id');

        Log::channel('mcp')->info('authorize.pedido', [
            'client_id' => $clientId,
            'redirect_uri' => $request->query('redirect_uri'),
            'scope' => $request->query('scope'),
            'resource' => $request->query('resource'),
            'code_challenge_method' => $request->query('code_challenge_method'),
            'prompt' => $request->query('prompt'),
            'user_agent' => $request->userAgent(),
        ]);

        if (! $request->filled('code_challenge') || $request->query('code_challenge_method') !== 'S256') {
            return $this->erro('invalid_request', 'PKCE obrigatório com code_challenge_method=S256');
        }

        if ($erro = $this->conferirRecurso($request->query('resource'))) {
            return $erro;
        }

        if (ClienteCimd::ehUrl($clientId)) {
            if (! config('mcp_poc.cimd')) {
                return $this->erro('invalid_client', 'client_id em URL (CIMD) desligado neste servidor: use o registro dinâmico');
            }

            try {
                $request->query->set('client_id', $this->cimd->resolver($clientId));
            } catch (RuntimeException $e) {
                Log::channel('mcp')->warning('cliente.cimd_recusado', ['client_id_url' => $clientId, 'motivo' => $e->getMessage()]);

                return $this->erro('invalid_client', $e->getMessage());
            }
        }

        return null;
    }

    private function conferirPedidoDeToken(Request $request): ?Response
    {
        $clientId = $request->input('client_id');

        Log::channel('mcp')->info('token.pedido', [
            'grant' => $request->input('grant_type'),
            'client_id' => $clientId,
            'resource' => $request->input('resource'),
            'tem_code_verifier' => $request->filled('code_verifier'),
            'content_type' => $request->header('Content-Type'),
        ]);

        if ($erro = $this->conferirRecurso($request->input('resource'))) {
            return $erro;
        }

        $recurso = HubRegistrar::normalizarRecurso($request->input('resource'));
        app()->instance(HubAccessToken::RECURSO, $recurso ?? HubRegistrar::recursoCanonico());

        if (ClienteCimd::ehUrl($clientId)) {
            $request->request->set('client_id', ClienteCimd::idInterno($clientId));
        }

        return null;
    }

    /** RFC 8707: recurso pedido tem de ser este servidor; sem pedido, vale o canônico. */
    private function conferirRecurso(mixed $recurso): ?Response
    {
        if ($recurso === null || $recurso === '') {
            return null;
        }

        if (! is_string($recurso) || ! in_array(HubRegistrar::normalizarRecurso($recurso), HubRegistrar::recursosAceitos(), true)) {
            Log::channel('mcp')->warning('recurso.recusado', ['resource' => $recurso, 'aceitos' => HubRegistrar::recursosAceitos()]);

            return $this->erro('invalid_target', 'resource não é este servidor: '.HubRegistrar::recursoCanonico());
        }

        return null;
    }

    /**
     * O league não devolve "scope" no token; quando o Hub concede menos que o pedido (ChatGPT só
     * lê), o cliente só fica sabendo por aqui (RFC 6749, 5.1). Lido do próprio JWT emitido.
     */
    private function acrescentarScope(Response $resposta, mixed $corpo): mixed
    {
        if ($resposta->getStatusCode() !== 200 || ! is_array($corpo) || ! is_string($corpo['access_token'] ?? null)) {
            return $corpo;
        }

        $partes = explode('.', $corpo['access_token']);
        $claims = json_decode((string) base64_decode(strtr($partes[1] ?? '', '-_', '+/')), true);
        $corpo['scope'] = implode(' ', (array) ($claims['scopes'] ?? []));
        $resposta->setContent((string) json_encode($corpo));

        return $corpo;
    }

    private function acrescentarIss(Response $resposta): void
    {
        $destino = $resposta->headers->get('Location');

        if (! is_string($destino) || ! preg_match('/[?&#](code|error)=/', $destino) || preg_match('/[?&]iss=/', $destino)) {
            return;
        }

        $emissor = config('mcp.authorization_server') ?? url('/');
        [$base, $fragmento] = array_pad(explode('#', $destino, 2), 2, null);
        $base .= (str_contains($base, '?') ? '&' : '?').'iss='.rawurlencode($emissor);

        $resposta->headers->set('Location', $fragmento === null ? $base : $base.'#'.$fragmento);

        Log::channel('mcp')->info('authorize.redirecionado', [
            'destino' => strtok($destino, '?'),
            'resultado' => str_contains($destino, 'code=') ? 'code' : 'error',
        ]);
    }

    private function erro(string $codigo, string $descricao): Response
    {
        return response()->json(['error' => $codigo, 'error_description' => $descricao], 400);
    }
}
