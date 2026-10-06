<?php

use Tests\Support\BancoDeVerdade;

// Bootstrap da suíte (skill wcj-testes, references/banco-real.md).
// Sem DB_TESTE, a suíte roda como antes (o banco do phpunit.xml).
// DB_TESTE=container: sobe o banco da produção num container descartável.

require __DIR__.'/../vendor/autoload.php';

if (getenv('DB_TESTE') === 'container') {
    BancoDeVerdade::subir();
}
