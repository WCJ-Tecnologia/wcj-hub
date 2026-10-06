<?php

namespace Database\Seeders;

use App\Platform\Enums\UserTypeEnum;
use App\Platform\Models\UserType;
use Illuminate\Database\Seeder;

class UserTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UserTypeEnum::cases() as $type) {
            UserType::query()->updateOrCreate(
                ['id' => $type->value],
                ['name' => $type->label(), 'slug' => $type->slug()],
            );
        }
    }
}
