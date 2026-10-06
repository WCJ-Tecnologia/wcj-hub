<?php

namespace Database\Seeders;

use App\Platform\Models\Client;
use Illuminate\Database\Seeder;

/**
 * O cliente WCJ (N7): dona dos projetos internos e dos produtos. Não desativável (regra do
 * ClientService, B1). Idempotente pelo CNPJ.
 */
class WcjClientSeeder extends Seeder
{
    public const CNPJ = '38614817000122';

    public function run(): void
    {
        Client::query()->firstOrCreate(
            ['country' => 'BR', 'document' => self::CNPJ],
            ['name' => 'WCJ Tecnologia'],
        );
    }
}
