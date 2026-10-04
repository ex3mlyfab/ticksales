<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\TaxRate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'category_id' => ProductCategory::factory(),
            'brand_id' => ProductBrand::factory(),
            'unit_id' => ProductUnit::factory(),
            'tax_rate_id' => TaxRate::factory(),
            'name' => fake()->unique()->words(2, true),
            'sku' => fake()->unique()->bothify('SKU-###'),
            'barcode' => fake()->unique()->ean13(),
            'description' => fake()->sentence(),
            'cost_price' => fake()->randomFloat(4, 1, 100),
            'selling_price' => fake()->randomFloat(4, 2, 200),
            'has_variants' => false,
            'track_inventory' => true,
            'alert_quantity' => 10,
            'image_path' => null,
            'is_active' => true,
        ];
    }
}
