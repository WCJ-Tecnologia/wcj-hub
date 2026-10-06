<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Lookup tables only. Administrators are created by `php artisan
 * app:create-admin`, never by a seeder with a fixed password.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserTypeSeeder::class,
        ]);
    }
}
