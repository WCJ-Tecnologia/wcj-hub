<?php

use App\Http\Middleware\McpPoc\OauthDaProva;

/*
| Só o que a prova do MCP (branch poc/mcp) muda; o resto vem do pacote (mergeConfigFrom).
| O middleware roda em todas as rotas /oauth/* do Passport.
*/

return [
    'middleware' => [OauthDaProva::class],
];
