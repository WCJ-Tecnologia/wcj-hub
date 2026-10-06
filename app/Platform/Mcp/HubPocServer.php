<?php

namespace App\Platform\Mcp;

use App\Platform\Mcp\Tools\Anotar;
use App\Platform\Mcp\Tools\Ping;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/** Servidor MCP da prova de conceito (D-GER-17). Descartável: o B8 traz as 6 ferramentas. */
#[Name('WCJ Hub (prova de conceito)')]
#[Version('0.0.1-poc')]
#[Instructions('Servidor de teste do WCJ Hub. "ping" mostra quem você é para o Hub e o que a conexão pode; "anotar" grava uma nota curta na auditoria (escrita: peça confirmação ao usuário antes).')]
class HubPocServer extends Server
{
    protected array $tools = [
        Ping::class,
        Anotar::class,
    ];
}
