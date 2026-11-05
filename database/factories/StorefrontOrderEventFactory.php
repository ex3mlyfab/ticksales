<?php

namespace Database\Factories;

use App\Models\StorefrontOrder;
use App\Models\StorefrontOrderEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorefrontOrderEvent>
 */
class StorefrontOrderEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'storefront_order_id' => StorefrontOrder::factory(),
            'tenant_id' => fn (array $attributes): string => StorefrontOrder::query()
                ->findOrFail($attributes['storefront_order_id'])
                ->tenant_id,
            'event' => 'order_placed',
            'from_status' => null,
            'to_status' => 'pending',
            'metadata' => null,
        ];
    }
}
