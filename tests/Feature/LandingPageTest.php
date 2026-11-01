<?php

use App\Models\SubscriptionPlan;
use Inertia\Testing\AssertableInertia as Assert;

test('the home page renders the landing page with the seeded plans', function () {
    SubscriptionPlan::query()->delete();

    SubscriptionPlan::query()->create([
        'name' => 'basic',
        'display_name' => 'Basic',
        'description' => 'Core POS features',
        'price_monthly' => 29.99,
        'max_branches' => 1,
        'features' => ['pos', 'products', 'customers', 'inventory_basic'],
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('welcome')
        ->has('plans', 1)
        ->where('plans.0.name', 'basic')
        ->where('plans.0.display_name', 'Basic')
        ->where('plans.0.price_monthly', 29.99)
        ->has('plans.0.features', 4)
        ->where('plans.0.features.0', 'pos')
        ->where('plans.0.features.3', 'inventory_basic')
    );
});

test('the home page normalises features stored as a JSON string', function () {
    SubscriptionPlan::query()->delete();

    // The previous seeder stored a JSON encoded string, which the model's
    // array cast then encoded a second time. The landing page must still
    // render it as a clean list.
    SubscriptionPlan::query()->create([
        'name' => 'basic',
        'display_name' => 'Basic',
        'description' => 'Core POS features',
        'price_monthly' => 29.99,
        'max_branches' => 1,
        'features' => json_encode(['pos', 'customers']),
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('welcome')
        ->has('plans.0.features', 2)
        ->where('plans.0.features.0', 'pos')
        ->where('plans.0.features.1', 'customers')
    );
});

test('the home page falls back to default plans when none are seeded', function () {
    SubscriptionPlan::query()->delete();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('welcome')
        ->has('plans', 3)
        ->where('plans.0.name', 'basic')
        ->where('plans.1.name', 'pro')
        ->where('plans.2.name', 'enterprise')
    );
});
