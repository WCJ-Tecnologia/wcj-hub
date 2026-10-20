<?php

/*
| Parâmetros do WCJ Hub (docs/banco.md, T5): padrão aqui, override pelo .env. Não há tabela
| de configurações (escopo §6). Quem lê: o limitador e o log das chamadas da IA (B8).
*/

return [

    'ai' => [
        // Limite de chamadas por autorização de IA, não por usuário (T5, IA7).
        'reads_per_minute' => (int) env('HUB_AI_READS_PER_MINUTE', 60),
        'writes_per_minute' => (int) env('HUB_AI_WRITES_PER_MINUTE', 20),

        // Log em arquivo das chamadas da IA, canal diário (R13, IA12).
        'log_retention_days' => (int) env('HUB_AI_LOG_RETENTION_DAYS', 180),
    ],

    // Anexos do card (P24; F3 §10.11): tamanho por arquivo, quantos por envio e os tipos aceitos,
    // conferidos pelos bytes (seguranca.md §7). Sem SVG nem HTML: podem carregar script.
    'attachments' => [
        'max_kb' => (int) env('HUB_ATTACHMENT_MAX_KB', 20480),
        'max_files' => (int) env('HUB_ATTACHMENT_MAX_FILES', 10),
        'mimes' => 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,csv,txt,zip',
    ],

];
