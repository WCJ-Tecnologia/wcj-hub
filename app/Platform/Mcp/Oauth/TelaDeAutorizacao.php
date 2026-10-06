<?php

namespace App\Platform\Mcp\Oauth;

use App\Platform\Models\AiAuthorization;
use App\Platform\Models\User;
use App\Platform\Services\ConexaoIaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Laravel\Passport\Client;

/**
 * A tela "autorizar <assistente>" do Hub (F8, "Tela de autorização"), mínima. Mostra o que o
 * servidor VAI conceder (não o que o cliente pediu) e recusa assistente não homologado.
 * Libera no CSP o form-action para o redirect do cliente: o Chrome bloqueia o 302 pós-submit
 * para outra origem quando o form-action é só 'self'.
 */
class TelaDeAutorizacao
{
    public function __construct(private readonly ConexaoIaService $conexoes) {}

    /** @param  array<string, mixed>  $parametros  os do Passport: client, user, scopes, request, authToken */
    public function __invoke(array $parametros): View
    {
        /** @var Client $client */
        $client = $parametros['client'];
        /** @var User $user */
        $user = $parametros['user'];
        /** @var Request $request */
        $request = $parametros['request'];

        $assistente = $user->isActive() ? $this->conexoes->detectarAssistente($client->name) : null;
        $escopos = $assistente ? $this->conexoes->escopos($user, $assistente) : [];

        $anterior = $assistente ? AiAuthorization::query()
            ->where('user_id', $user->id)
            ->where('ai_assistant_id', $assistente->id)
            ->whereNull('revoked_at')
            ->first() : null;

        $redirect = (string) $request->query('redirect_uri');
        $origem = parse_url($redirect, PHP_URL_SCHEME).'://'.parse_url($redirect, PHP_URL_HOST)
            .(parse_url($redirect, PHP_URL_PORT) ? ':'.parse_url($redirect, PHP_URL_PORT) : '');
        $request->attributes->set('csp_form_action', [HubClientRepository::loopback($redirect) ? 'http://localhost:* http://127.0.0.1:*' : $origem]);

        return view('mcp-poc.authorize', [
            'client' => $client,
            'user' => $user,
            'assistente' => $assistente,
            'escopos' => array_map(fn ($id) => ['id' => $id, 'descricao' => config('mcp_poc.escopos')[$id] ?? $id], $escopos),
            'anterior' => $anterior,
            'pedidos' => array_map(fn ($s) => $s->id, $parametros['scopes']),
            'redirectHost' => parse_url($redirect, PHP_URL_HOST),
            'authToken' => $parametros['authToken'],
            'state' => $request->query('state'),
        ]);
    }
}
