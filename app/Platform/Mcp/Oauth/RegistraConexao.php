<?php

namespace App\Platform\Mcp\Oauth;

use App\Platform\Models\User;
use App\Platform\Services\ConexaoIaService;
use Laravel\Passport\Client;
use Laravel\Passport\Events\AccessTokenCreated;

/**
 * Token emitido na troca do código = autorização nova: grava a linha em ai_authorizations
 * (e revoga a anterior do mesmo assistente, IA6). No refresh a conexão já existe e nada muda.
 */
class RegistraConexao
{
    public function __construct(private readonly ConexaoIaService $conexoes) {}

    public function handle(AccessTokenCreated $evento): void
    {
        if (request()->input('grant_type') !== 'authorization_code' || $evento->userId === null) {
            return;
        }

        $user = User::query()->find($evento->userId);
        $client = Client::query()->find($evento->clientId);

        if ($user !== null && $client !== null) {
            $this->conexoes->conectar($user, $client, $evento->tokenId);
        }
    }
}
