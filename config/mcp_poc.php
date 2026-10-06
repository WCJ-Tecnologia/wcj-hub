<?php

/*
| Prova de conceito do servidor MCP (D-GER-17, branch poc/mcp, descartável).
| O B8 refaz isto com spec; nada aqui é decisão de produto.
*/

return [

    // Caminho do servidor MCP. A URL canônica (indicador de recurso, RFC 8707) é APP_URL + este
    // caminho, sem barra final.
    'path' => 'mcp',

    // Anuncia o Client ID Metadata Document (client_id = URL https do fornecedor) nos metadados
    // do servidor de autorização. Desligado: os assistentes caem no registro dinâmico (RFC 7591).
    'cimd' => (bool) env('POC_MCP_CIMD', false),

    // Esconde "anotar" do tools/list quando a conexão só lê. Desligado: a ferramenta aparece e o
    // servidor recusa a chamada ("escopo insuficiente"), que é o que a prova quer observar.
    'esconder_escrita' => (bool) env('POC_MCP_ESCONDER_ESCRITA', false),

    // Tempo de vida dos tokens. Curto de propósito: a prova também observa o refresh.
    'access_token_minutes' => (int) env('POC_MCP_ACCESS_TOKEN_MINUTES', 60),
    'refresh_token_days' => (int) env('POC_MCP_REFRESH_TOKEN_DAYS', 30),

    // Palavra-chave no client_name → slug de ai_assistants. A ordem importa: o primeiro que
    // casar ganha. "chatgpt" vem antes de "codex" de propósito: um nome com os dois fica com a
    // regra mais restritiva (só leitura).
    'assistentes' => [
        'chatgpt' => 'chatgpt',
        'codex' => 'codex',
        'claude' => 'claude',
    ],

    'escopos' => [
        'poc:read' => 'Prova MCP: ler (ping)',
        'poc:write' => 'Prova MCP: escrever (anotar)',
    ],

];
