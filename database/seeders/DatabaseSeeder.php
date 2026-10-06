<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Domínios fechados, listas cadastráveis iniciais e o cliente WCJ: o que todo ambiente precisa,
 * produção inclusive. Idempotente.
 *
 * Fora daqui, de propósito:
 * - o Administrador: `php artisan app:create-admin`, nunca seeder com senha fixa;
 * - o dado de exemplo: `php artisan db:seed --class=ExemploSeeder` (só dev e demonstração),
 *   apagado por `php artisan app:limpar-exemplo`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Platform
            RoleSeeder::class,
            ContactChannelSeeder::class,
            AiAssistantSeeder::class,
            WcjClientSeeder::class,
            // Projetos
            StageRoleSeeder::class,
            TaskPrioritySeeder::class,
            TestResultSeeder::class,
            ValidationTypeSeeder::class,
            TaskTypeSeeder::class,
            ReworkReasonSeeder::class,
        ]);
    }
}
