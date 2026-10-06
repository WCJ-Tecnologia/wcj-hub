<?php

use App\Http\Middleware\McpPoc\ConexaoDaIa;
use App\Http\Middleware\McpPoc\RegistraChamadaMcp;
use App\Platform\Mcp\HubPocServer;
use Laravel\Mcp\Facades\Mcp;

/*
| Prova de conceito do servidor MCP (D-GER-17, branch poc/mcp). Descartável.
| oauthRoutes: /.well-known/oauth-authorization-server, /.well-known/oauth-protected-resource
| (e as variantes com caminho) e POST /oauth/register (registro dinâmico, RFC 7591).
*/

Mcp::oauthRoutes();

Mcp::web('/'.config('mcp_poc.path'), HubPocServer::class)
    ->middleware([RegistraChamadaMcp::class, 'auth:api', ConexaoDaIa::class]);
