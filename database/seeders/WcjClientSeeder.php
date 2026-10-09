<?php

namespace Database\Seeders;

use App\Platform\Models\Client;
use App\Support\Paises;
use Illuminate\Database\Seeder;

/**
 * O cliente WCJ (N7): dona dos projetos internos e dos produtos. Não desativável (regra do
 * ClientService, B1). Idempotente pelo CNPJ.
 */
class WcjClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::query()->firstOrCreate(
            ['country' => Paises::BRASIL, 'document' => Client::WCJ_CNPJ],
            ['name' => 'WCJ Tecnologia'],
        );
    }
}
