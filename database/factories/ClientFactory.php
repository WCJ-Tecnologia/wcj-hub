<?php

namespace Database\Factories;

use App\Platform\Models\Client;
use App\Rules\AlphanumericCnpj;
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
            // CNPJ alfanumérico válido: o ClientService recusa o que não passa no dígito (N5).
            'document' => AlphanumericCnpj::withCheckDigits(fake()->unique()->regexify('[0-9A-Z]{12}')),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['deactivated_at' => now()]);
    }
}
