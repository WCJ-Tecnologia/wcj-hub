<?php

namespace Database\Factories;

use App\Platform\Models\Enterprise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enterprise>
 */
class EnterpriseFactory extends Factory
{
    protected $model = Enterprise::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'nickname' => mb_substr($name, 0, 40),
            // Documents are stored as digits only; masks live in the UI.
            'document' => fake()->unique()->numerify('##############'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['deactivated_at' => now()]);
    }
}
