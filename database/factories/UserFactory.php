<?php

namespace Database\Factories;

use App\Platform\Enums\RoleEnum;
use App\Platform\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => RoleEnum::ADMIN->value,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'must_change_password' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role_id' => RoleEnum::ADMIN->value]);
    }

    public function projectManager(): static
    {
        return $this->state(fn () => ['role_id' => RoleEnum::PROJECT_MANAGER->value]);
    }

    public function developer(): static
    {
        return $this->state(fn () => ['role_id' => RoleEnum::DEVELOPER->value, 'daily_hours' => 8]);
    }

    public function sales(): static
    {
        return $this->state(fn () => ['role_id' => RoleEnum::SALES->value]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['deactivated_at' => now()]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn () => ['must_change_password' => true]);
    }
}
