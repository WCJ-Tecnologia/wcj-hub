<?php

namespace App\Platform\Mcp\Oauth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Client;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Throwable;

/**
 * Client ID Metadata Document (draft-ietf-oauth-client-id-metadata-document): o client_id é uma
 * URL https do fornecedor que devolve o JSON do cliente. O Passport só aceita client_id uuid de
 * um cliente cadastrado, então a prova traduz: a URL vira um uuid v5 determinístico, e o
 * documento cria ou atualiza o oauth_clients com esse id. Mínimo da prova, não do B8.
 */
class ClienteCimd
{
    public static function ehUrl(?string $clientId): bool
    {
        return is_string($clientId) && str_starts_with($clientId, 'https://');
    }

    public static function idInterno(string $url): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, $url)->toString();
    }

    /**
     * Busca o documento, confere e grava o cliente. Devolve o id interno.
     *
     * @throws RuntimeException com a mensagem que vai no error_description
     */
    public function resolver(string $url): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        // Sem SSRF óbvio: só https, só nome de host (nada de IP literal nem localhost).
        if ($host === '' || $host === 'localhost' || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false) {
            throw new RuntimeException('client_id de metadados precisa ser https com nome de host');
        }

        try {
            $resposta = Http::timeout(5)->connectTimeout(3)->withoutRedirecting()->acceptJson()->get($url);
        } catch (Throwable) {
            throw new RuntimeException('documento de metadados do cliente inacessível');
        }

        $doc = $resposta->successful() && strlen($resposta->body()) <= 10_000 ? $resposta->json() : null;

        if (! is_array($doc) || ($doc['client_id'] ?? null) !== $url) {
            throw new RuntimeException('documento de metadados do cliente inválido (client_id diferente da URL)');
        }

        $redirects = array_values(array_filter((array) ($doc['redirect_uris'] ?? []), 'is_string'));

        if ($redirects === []) {
            throw new RuntimeException('documento de metadados do cliente sem redirect_uris');
        }

        $id = self::idInterno($url);
        $nome = is_string($doc['client_name'] ?? null) ? mb_substr($doc['client_name'], 0, 255) : $host;

        $cliente = Client::query()->find($id) ?? new Client;
        $cliente->forceFill([
            'id' => $id,
            'name' => $nome,
            'redirect_uris' => $redirects,
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ])->save();

        Log::channel('mcp')->info('cliente.registrado', [
            'metodo' => 'cimd',
            'client_id_url' => $url,
            'client_id' => $id,
            'client_name' => $doc['client_name'] ?? null,
            'redirect_uris' => $redirects,
            'client_uri' => $doc['client_uri'] ?? null,
            'logo_uri' => $doc['logo_uri'] ?? null,
            'software_id' => $doc['software_id'] ?? null,
            'token_endpoint_auth_method' => $doc['token_endpoint_auth_method'] ?? null,
            'campos' => array_keys($doc),
        ]);

        return $id;
    }
}
