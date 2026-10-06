<?php

/*
| Parâmetros do WCJ Hub (docs/banco.txt, T5): padrão aqui, override pelo .env. Não há tabela
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

];
