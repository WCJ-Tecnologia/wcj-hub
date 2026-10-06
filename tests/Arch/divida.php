<?php

// Violações de arquitetura que já existiam quando a regra entrou (skill wcj-testes).
// Só diminui: corrigiu, tire daqui (o teste avisa a dívida paga). Item novo aqui só com
// aprovação do dev sênior, com o motivo escrito. "teto" é o tamanho congelado: crescer falha.

return [
    'A4' => [
        'app/Platform/Services/AuditService.php usa request()' => ['motivo' => 'o log de auditoria lê IP e user agent da requisição corrente; decidir: exceção da regra ou contexto passado pelo controller', 'teto' => 2],
    ],
    'A6' => [
        'app/Platform/Controllers/Admin/AuditLogController.php::index' => ['motivo' => 'filtros da listagem no controller (candidato a query dedicada)', 'teto' => 43],
        'app/Platform/Controllers/Admin/EnterpriseController.php::index' => ['motivo' => 'filtros da listagem no controller (candidato a query dedicada)', 'teto' => 37],
        'app/Platform/Controllers/Admin/ProfileController.php::update' => ['motivo' => 'troca de dados e de senha no mesmo método', 'teto' => 40],
        'app/Platform/Controllers/Admin/UserController.php' => ['motivo' => 'index com filtros e store com senha temporária no controller', 'teto' => 226],
        'app/Platform/Controllers/Admin/UserController.php::index' => ['motivo' => 'filtros e mapeamento da listagem no controller', 'teto' => 51],
        'app/Platform/Controllers/Admin/UserController.php::store' => ['motivo' => 'criação com senha temporária e auditoria na mesma transação', 'teto' => 31],
        'app/Platform/Controllers/Auth/NewPasswordController.php::store' => ['motivo' => 'fluxo do broker de senha do Laravel (código do starter kit)', 'teto' => 39],
    ],
];
