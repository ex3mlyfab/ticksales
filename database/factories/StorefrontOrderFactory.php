<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\StorefrontOrder;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StorefrontOrder>
 */
class StorefrontOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'branch_id' => fn (array $attributes): string => Branch::factory()
                ->create([
                    'tenant_id' => $attributes['tenant_id'],
                    'is_main' => true,
                ])->id,
            'reference_no' => 'WEB-'.strtoupper(Str::random(10)),
            'idempotency_key' => Str::uuid()->toString(),
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'fulfillment_method' => 'pickup',
            'subtotal' => 0,
            'tax_amount' => 0,
            'delivery_fee' => 0,
            'total_amount' => 0,
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_email' => fake()->optional()->safeEmail(),
            'delivery_address' => null,
            'tracking_token_hash' => hash('sha256', Str::random(64)),
        ];
    }
}
