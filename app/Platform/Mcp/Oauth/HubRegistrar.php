<?php

namespace App\Platform\Mcp\Oauth;

use Laravel\Mcp\Server\Registrar;

/**
 * Os metadados do laravel/mcp anunciam só o escopo "mcp:use" (o pacote não suporta escopo
 * próprio, laravel.com/docs/mcp, "Using an Existing Passport Installation"). A prova sobrescreve
 * os dois documentos para anunciar poc:read/poc:write, o CIMD e os métodos de autenticação.
 */
class HubRegistrar extends Registrar
{
    /** @return array<string, mixed> */
    protected static function authorizationServerMetadata(string $oauthPrefix): array
    {
        $metadados = parent::authorizationServerMetadata($oauthPrefix);

        $metadados['scopes_supported'] = array_keys(config('mcp_poc.escopos'));
        $metadados['token_endpoint_auth_methods_supported'] = ['none'];
        // RFC 9207: a resposta de autorização traz "iss" (OauthDaProva acrescenta).
        $metadados['authorization_response_iss_parameter_supported'] = true;

        if (config('mcp_poc.cimd')) {
            $metadados['client_id_metadata_document_supported'] = true;
        }

        return $metadados;
    }

    /** @return array<string, mixed> */
    protected static function protectedResourceMetadata(string $path): array
    {
        $metadados = parent::protectedResourceMetadata($path);
        $metadados['resource'] = rtrim((string) $metadados['resource'], '/');
        $metadados['scopes_supported'] = array_keys(config('mcp_poc.escopos'));
        $metadados['bearer_methods_supported'] = ['header'];

        return $metadados;
    }

    /** URL canônica do servidor MCP (RFC 8707): APP_URL + caminho, sem barra final. */
    public static function recursoCanonico(): string
    {
        return rtrim(url(config('mcp_poc.path')), '/');
    }

    /**
     * Indicadores de recurso aceitos no authorize e no token: a URL do servidor MCP ou a origem.
     *
     * @return list<string>
     */
    public static function recursosAceitos(): array
    {
        return [self::recursoCanonico(), rtrim(url('/'), '/')];
    }

    public static function normalizarRecurso(?string $recurso): ?string
    {
        return $recurso === null || $recurso === '' ? null : rtrim($recurso, '/');
    }
}
