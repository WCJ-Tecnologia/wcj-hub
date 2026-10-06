<?php

namespace App\Http\Middleware\McpPoc;

use App\Platform\Mcp\ConexaoAtual;
use App\Platform\Mcp\Oauth\HubRegistrar;
use App\Platform\Models\User;
use App\Platform\Services\ConexaoIaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Depois do auth:api (o Passport já conferiu assinatura, validade e revogação do token):
 * - o token foi emitido para este servidor (aud contém o recurso canônico, RFC 8707);
 * - a pessoa está ativa (N4);
 * - existe conexão ativa (ai_authorizations) para esse usuário e cliente (IA6).
 * Qualquer falha = 401 "não autorizado" (o laravel/mcp acrescenta o WWW-Authenticate).
 */
class ConexaoDaIa
{
    public function __construct(private readonly ConexaoIaService $conexoes) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();
        $token = $user instanceof User ? $user->token() : null;

        if (! $user instanceof User || $token === null) {
            return $this->negar('sem token válido');
        }

        if (! $this->audienciaConfere((string) $request->bearerToken())) {
            return $this->negar('token emitido para outro recurso');
        }

        if (! $user->isActive()) {
            return $this->negar('usuário desativado');
        }

        $conexao = $this->conexoes->ativa($user->id, (string) $token->oauth_client_id);

        if ($conexao === null) {
            return $this->negar('conexão revogada ou inexistente');
        }

        app()->instance(ConexaoAtual::class, new ConexaoAtual(
            $user,
            $conexao,
            (string) $token->client?->name,
            array_values((array) $token->oauth_scopes),
        ));

        return $next($request);
    }

    private function audienciaConfere(string $jwt): bool
    {
        try {
            $parsed = (new Parser(new JoseEncoder))->parse($jwt);
        } catch (Throwable) {
            return false;
        }

        $aud = $parsed instanceof UnencryptedToken ? (array) $parsed->claims()->get('aud', []) : [];

        return array_intersect($aud, HubRegistrar::recursosAceitos()) !== [];
    }

    private function negar(string $motivo): Response
    {
        Log::channel('mcp')->warning('mcp.negado', ['motivo' => $motivo]);

        return response()->json(['error' => 'invalid_token', 'error_description' => 'não autorizado: '.$motivo], 401);
    }
}
