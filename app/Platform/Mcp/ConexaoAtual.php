<?php

namespace App\Platform\Mcp;

use App\Platform\Models\AiAuthorization;
use App\Platform\Models\User;

/**
 * A conexão de IA por trás da chamada ao /mcp (ConexaoDaIa resolve, as ferramentas leem).
 *
 * @param  list<string>  $escopos  os gravados no token
 */
final class ConexaoAtual
{
    /** @param  list<string>  $escopos */
    public function __construct(
        public readonly User $user,
        public readonly AiAuthorization $autorizacao,
        public readonly string $nomeDoCliente,
        public readonly array $escopos,
    ) {}

    public function pode(string $escopo): bool
    {
        return in_array($escopo, $this->escopos, true);
    }
}
