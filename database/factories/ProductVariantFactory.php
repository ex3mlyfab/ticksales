<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'product_id' => Product::factory(),
            'name' => fake()->unique()->words(2, true),
            'sku' => fake()->unique()->bothify('VAR-###'),
            'barcode' => fake()->unique()->ean13(),
            'cost_price' => fake()->randomFloat(4, 1, 100),
            'selling_price' => fake()->randomFloat(4, 2, 200),
            'image_path' => null,
            'is_active' => true,
        ];
    }
}
