<?php

namespace App\Platform\Mcp\Oauth;

use App\Platform\Models\User;
use App\Platform\Services\ConexaoIaService;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Bridge\ScopeRepository;
use Laravel\Passport\ClientRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;

/**
 * Quem decide o escopo é o servidor, não o cliente (IA4, IA5; core/mcp.md, seção 3): o pedido
 * do assistente é ignorado e o token recebe o máximo do papel, da marca "IA pode escrever" e do
 * assistente (ChatGPT = só leitura, IA3). Vale na troca do código e no refresh.
 */
class HubScopeRepository extends ScopeRepository
{
    public function __construct(
        ClientRepository $clients,
        private readonly ConexaoIaService $conexoes,
    ) {
        parent::__construct($clients);
    }

    /**
     * Aceita qualquer escopo pedido (o Claude pede o que os metadados anunciam, outro cliente pode
     * pedir "mcp:use" ou "offline_access"): finalizeScopes descarta tudo e decide sozinho.
     */
    public function getScopeEntityByIdentifier(string $identifier): ?ScopeEntityInterface
    {
        return preg_match('/^[\w:.\-]{1,64}$/', $identifier) === 1 ? new Scope($identifier) : null;
    }

    /** @param  ScopeEntityInterface[]  $scopes */
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        ?string $userIdentifier = null,
        ?string $authCodeId = null
    ): array {
        $client = $this->clients->findActive($clientEntity->getIdentifier());
        $user = $userIdentifier !== null ? User::query()->find($userIdentifier) : null;
        $assistente = $this->conexoes->detectarAssistente($client?->name);

        if ($client === null || $user === null || ! $user->isActive()) {
            throw OAuthServerException::accessDenied('não autorizado');
        }

        if ($assistente === null) {
            Log::channel('mcp')->warning('escopo.recusado', ['client_name' => $client->name, 'motivo' => 'assistente não homologado']);

            throw OAuthServerException::accessDenied('assistente não homologado');
        }

        $concedidos = $this->conexoes->escopos($user, $assistente);

        Log::channel('mcp')->info('escopo.decidido', [
            'grant' => $grantType,
            'user_id' => $user->id,
            'client_id' => $client->getKey(),
            'assistente' => $assistente->slug,
            'pedidos' => array_map(fn (ScopeEntityInterface $s) => $s->getIdentifier(), $scopes),
            'concedidos' => $concedidos,
        ]);

        return array_map(fn (string $id) => new Scope($id), $concedidos);
    }
}
