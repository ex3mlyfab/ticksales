<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    /**
     * Show the public landing page with the subscription plans.
     */
    public function __invoke(): Response
    {
        $plans = SubscriptionPlan::query()
            ->orderBy('price_monthly')
            ->get()
            ->map(fn (SubscriptionPlan $plan) => [
                'name' => $plan->name,
                'display_name' => $plan->display_name,
                'description' => $plan->description,
                'price_monthly' => (float) $plan->price_monthly,
                'max_branches' => (int) $plan->max_branches,
                'features' => $this->normalizeFeatures($plan->features),
            ])
            ->all();

        if ($plans === []) {
            $plans = $this->defaultPlans();
        }

        return Inertia::render('welcome', [
            'plans' => $plans,
        ]);
    }

    /**
     * Normalize the plan features into a list of strings.
     *
     * Seeded plans store the feature list as a JSON encoded string, while
     * other records store it as a JSON array; both should render the same.
     *
     * @return array<int, string>
     */
    protected function normalizeFeatures(mixed $features): array
    {
        $decoded = $features;

        // The seeder double-encodes the JSON, so decode up to twice.
        for ($i = 0; $i < 2 && is_string($decoded); $i++) {
            $decoded = json_decode($decoded, true);
        }

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, is_string(...)));
    }

    /**
     * Fallback plans used when the subscription plans have not been seeded.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function defaultPlans(): array
    {
        return [
            [
                'name' => 'basic',
                'display_name' => 'Basic',
                'description' => 'Core POS features for single-location businesses',
                'price_monthly' => 29.99,
                'max_branches' => 1,
                'features' => ['pos', 'products', 'customers', 'inventory_basic'],
            ],
            [
                'name' => 'pro',
                'display_name' => 'Pro',
                'description' => 'Advanced features for growing businesses',
                'price_monthly' => 79.99,
                'max_branches' => 10,
                'features' => ['pos', 'products', 'customers', 'inventory_advanced', 'reports', 'multi_branch'],
            ],
            [
                'name' => 'enterprise',
                'display_name' => 'Enterprise',
                'description' => 'Full-featured platform for multi-location enterprises',
                'price_monthly' => 199.99,
                'max_branches' => 9999,
                'features' => ['pos', 'products', 'customers', 'inventory_advanced', 'reports', 'multi_branch', 'custom_branding', 'priority_support'],
            ],
        ];
    }
}
