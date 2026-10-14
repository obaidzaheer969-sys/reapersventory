<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'category_id' => null,
            'name' => fake()->unique()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'barcode' => fake()->optional()->ean13(),
            'description' => fake()->optional()->sentence(),
            'unit' => 'pcs',
            'cost_price' => 100,
            'selling_price' => 150,
            'reorder_level' => 5,
            'track_inventory' => true,
            'is_active' => true,
        ];
    }
}
