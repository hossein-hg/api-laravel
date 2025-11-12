<?php

namespace Database\Factories\Admin;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin\Product>
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
            'name' => fake()->name(),
            'stars' => 5,
            'url' => fake()->url(),
            'category_id' => 1,
            'price' => mt_rand(2000, 50000000),
            'oldPrice' => mt_rand(2000, 10000000),
            'cover' => fake()->imageUrl(),
            'inventory' => 1,
            'shortDescription' => fake()->text(),
            'salesCount' => mt_rand(2,3),
            'description' => fake()->text(),
            'countdown' => mt_rand(2, 3),
            'warehouseInventory' => mt_rand(2, 3),
            'satisfaction' => mt_rand(2, 3),
            'additionalInformation' => fake()->text(),
        ];
    }
}
