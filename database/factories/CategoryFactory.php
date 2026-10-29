<?php

namespace Database\Factories;

use App\Domain\Shared\Color;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state: an active, billable category at the end of the list.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->lexify('Category ????'),
            'color' => Color::Petrol->value,
            'sort_order' => fn () => (Category::query()->orderByDesc('sort_order')->first()->sort_order ?? 0) + 1,
        ];
    }

    public function notBillable(): static
    {
        return $this->state(fn (array $attributes) => ['billable' => false]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['active' => false]);
    }
}
