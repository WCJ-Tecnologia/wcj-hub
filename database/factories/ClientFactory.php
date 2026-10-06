<?php

namespace Database\Factories;

use App\Platform\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'country' => 'BR',
            // CNPJ obrigatório no BR (check do banco); a validação do formato é do Service (B1).
            'document' => fake()->unique()->numerify('##############'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['deactivated_at' => now()]);
    }
}
