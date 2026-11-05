<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\StorefrontOrderItem;
use App\Models\StorefrontStockReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorefrontStockReservation>
 */
class StorefrontStockReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'storefront_order_item_id' => StorefrontOrderItem::factory(),
            'inventory_id' => Inventory::factory(),
            'quantity' => 1,
            'status' => 'active',
        ];
    }
}
