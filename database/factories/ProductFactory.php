<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
            'category_id' => Category::factory(),
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(1000, 100000),
            'wholesale_price' => fake()->numberBetween(1000, 90000),
            'retail_price' => fake()->numberBetween(1000, 110000),
            'stock' => fake()->numberBetween(1, 100),
            'barcode' => fake()->ean13(),
        ];
    }
}
