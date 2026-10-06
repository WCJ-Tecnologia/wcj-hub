<?php

namespace App\Platform\Services;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\AiAssistant;
use App\Platform\Models\AiAuthorization;
use App\Platform\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

/**
 * Conexão de IA da prova do MCP (D-GER-17; IA3 a IA6; docs/banco.txt, ai_authorizations).
 * Descartável: o B8 refaz com spec. O que a prova quer observar fica no log "mcp".
 */
class ConexaoIaService
{
    public const LER = 'poc:read';

    public const ESCREVER = 'poc:write';

    /** Papéis que escrevem em Projetos pela IA (IA5; F8, "O que cada papel pode autorizar"). */
    private const PAPEIS_QUE_ESCREVEM = [RoleEnum::ADMIN, RoleEnum::PROJECT_MANAGER, RoleEnum::DEVELOPER];

    /**
     * O assistente pelo nome que o cliente declarou no registro (dinâmico ou CIMD). O nome não
     * prova identidade (core/mcp.md, seção 3): é rótulo. Desconhecido = não homologado.
     */
    public function detectarAssistente(?string $nomeDoCliente): ?AiAssistant
    {
        $nome = Str::lower((string) $nomeDoCliente);

        foreach (config('mcp_poc.assistentes', []) as $palavra => $slug) {
            if ($nome !== '' && str_contains($nome, $palavra)) {
                return AiAssistant::query()->where('slug', $slug)->first();
            }
        }

        return null;
    }

    /**
     * O máximo que a conexão recebe, decidido no servidor e não pelo que o cliente pediu (IA4):
     * ler sempre; escrever só se o papel escreve, a pessoa tem "IA pode escrever" e o assistente
     * não é só leitura (ChatGPT, IA3).
     *
     * @return list<string>
     */
    public function escopos(User $user, AiAssistant $assistente): array
    {
        $escopos = [self::LER];

        if ($assistente->write_allowed
            && $user->ai_write_allowed
            && in_array($user->type(), self::PAPEIS_QUE_ESCREVEM, true)) {
            $escopos[] = self::ESCREVER;
        }

        return $escopos;
    }

    /** A conexão ativa por trás de um token: mesmo usuário e mesmo cliente OAuth. */
    public function ativa(int $userId, string $clientId): ?AiAuthorization
    {
        return AiAuthorization::query()
            ->where('user_id', $userId)
            ->where('oauth_client_id', $clientId)
            ->whereNull('revoked_at')
            ->with('aiAssistant')
            ->first();
    }

    /**
     * Nova conexão a cada autorização concedida (troca do código pelo token). Reconectar o mesmo
     * assistente revoga a anterior, com os tokens dela (IA6): é isso que a pergunta 1 da prova
     * observa quando Claude app e Claude Code se registram como clientes diferentes.
     */
    public function conectar(User $user, Client $client, string $tokenAtual): ?AiAuthorization
    {
        $assistente = $this->detectarAssistente($client->name);

        if ($assistente === null) {
            return null;
        }

        return DB::transaction(function () use ($user, $client, $assistente, $tokenAtual) {
            $anterior = AiAuthorization::query()
                ->where('user_id', $user->id)
                ->where('ai_assistant_id', $assistente->id)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->first();

            if ($anterior !== null) {
                $this->revogar($anterior, $tokenAtual);
                Log::channel('mcp')->info('conexao.substituida', [
                    'revogada' => $anterior->id,
                    'cliente_antigo' => $anterior->oauth_client_id,
                    'cliente_novo' => $client->getKey(),
                    'mesmo_cliente' => $anterior->oauth_client_id === $client->getKey(),
                ]);
            }

            $conexao = AiAuthorization::query()->create([
                'user_id' => $user->id,
                'ai_assistant_id' => $assistente->id,
                'oauth_client_id' => $client->getKey(),
            ]);

            Log::channel('mcp')->info('conexao.criada', [
                'ai_authorization_id' => $conexao->id,
                'user_id' => $user->id,
                'assistente' => $assistente->slug,
                'client_id' => $client->getKey(),
                'client_name' => $client->name,
            ]);

            return $conexao;
        });
    }

    /** Revoga a conexão e os tokens do Passport dela, menos o token que acabou de nascer. */
    public function revogar(AiAuthorization $conexao, ?string $exceto = null): void
    {
        $conexao->forceFill(['revoked_at' => now()])->save();

        $tokens = Passport::token()->newQuery()
            ->where('user_id', $conexao->user_id)
            ->where('client_id', $conexao->oauth_client_id)
            ->when($exceto !== null, fn ($q) => $q->whereKeyNot($exceto))
            ->pluck('id');

        Passport::token()->newQuery()->whereIn('id', $tokens)->update(['revoked' => true]);
        Passport::refreshToken()->newQuery()->whereIn('access_token_id', $tokens)->update(['revoked' => true]);
    }
}
