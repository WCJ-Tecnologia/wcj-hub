<?php

// Violações de arquitetura que já existiam quando a regra entrou (skill wcj-testes).
// Só diminui: corrigiu, tire daqui (o teste avisa a dívida paga). Item novo aqui só com
// aprovação do dev sênior, com o motivo escrito. "teto" é o tamanho congelado: crescer falha.

return [
    'A4' => [
        'app/Platform/Services/AuditService.php usa request()' => ['motivo' => 'o log de auditoria lê o IP da requisição corrente; decidir: exceção da regra ou contexto passado pelo controller', 'teto' => 1],
    ],
    'A6' => [
    ],
];
