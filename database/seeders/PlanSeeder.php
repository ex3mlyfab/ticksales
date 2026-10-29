<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'basic',
                'display_name' => 'Basic',
                'description' => 'Core POS features for single-location businesses',
                'price_monthly' => 29.99,
                'max_branches' => 1,
                'features' => json_encode(['pos', 'products', 'customers', 'inventory_basic']),
            ],
            [
                'name' => 'pro',
                'display_name' => 'Pro',
                'description' => 'Advanced features for growing businesses',
                'price_monthly' => 79.99,
                'max_branches' => 10,
                'features' => json_encode(['pos', 'products', 'customers', 'inventory_advanced', 'reports', 'multi_branch']),
            ],
            [
                'name' => 'enterprise',
                'display_name' => 'Enterprise',
                'description' => 'Full-featured platform for multi-location enterprises',
                'price_monthly' => 199.99,
                'max_branches' => 9999,
                'features' => json_encode(['pos', 'products', 'customers', 'inventory_advanced', 'reports', 'multi_branch', 'custom_branding', 'priority_support']),
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
