<?php

namespace Database\Seeders;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\Role;
use Database\Seeders\Concerns\SemeiaDominioFechado;
use Illuminate\Database\Seeder;

/** roles (N2): os 4 papéis fixos. */
class RoleSeeder extends Seeder
{
    use SemeiaDominioFechado;

    public function run(): void
    {
        $linhas = [];
        foreach (RoleEnum::cases() as $role) {
            $linhas[$role->value] = ['slug' => $role->slug(), 'label' => $role->label()];
        }

        $this->semear(Role::class, $linhas);
    }
}
