<?php

namespace Database\Factories;

use App\Domain\Shared\Color;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state: a short name only, so the fiscal data is incomplete.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->lexify('Client ????'),
            'color' => Color::Petrol->value,
        ];
    }

    /**
     * Everything the invoice needs, for a company client.
     */
    public function withFiscalData(): static
    {
        return $this->state(fn (array $attributes) => [
            'legal_name' => 'Cliente Exemplo LTDA',
            'document' => '11222333000181',
            'email' => 'financeiro@example.com',
            'address_street' => 'Rua Exemplo',
            'address_number' => '100',
            'address_district' => 'Centro',
            'address_city' => 'Canoas',
            'address_state' => 'RS',
            'address_zip_code' => '92010000',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['active' => false]);
    }
}
