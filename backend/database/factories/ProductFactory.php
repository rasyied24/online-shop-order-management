<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_code' => 'P'.fake()->unique()->numerify('####'),
            'product_name' => fake()->unique()->words(2, true),
            'stock' => fake()->numberBetween(10, 100),
            'price' => fake()->numberBetween(5, 100) * 1000,
        ];
    }
}
