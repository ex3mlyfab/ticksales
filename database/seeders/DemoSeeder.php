<?php

namespace Database\Seeders;

use App\Actions\Sales\CreateSaleAction;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Inventory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Register;
use App\Models\RegisterSession;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a realistic demo tenant with branches, catalog, parties, and sales.
     */
    public function run(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
            'email' => 'demo@ticksales.test',
            'currency' => 'USD',
            'plan' => 'enterprise',
        ]);

        $owner = User::query()->create([
            'name' => 'Demo Owner',
            'email' => 'owner@ticksales.test',
            'password' => Hash::make('password'),
        ]);

        $manager = User::query()->create([
            'name' => 'Demo Manager',
            'email' => 'manager@ticksales.test',
            'password' => Hash::make('password'),
        ]);

        $cashier = User::query()->create([
            'name' => 'Demo Cashier',
            'email' => 'cashier@ticksales.test',
            'password' => Hash::make('password'),
        ]);

        $role = Role::query()->where('name', 'owner')->first();

        foreach ([$owner, $manager, $cashier] as $user) {
            TenantUser::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role_id' => $role?->id,
                'is_owner' => $user->is($owner),
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        $branch = Branch::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $categories = collect([
            ['name' => 'Beverages', 'slug' => 'beverages'],
            ['name' => 'Snacks', 'slug' => 'snacks'],
            ['name' => 'Dairy', 'slug' => 'dairy'],
            ['name' => 'Bakery', 'slug' => 'bakery'],
            ['name' => 'Cleaning', 'slug' => 'cleaning'],
        ])->map(fn (array $c) => ProductCategory::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $c['name'],
            'slug' => $c['slug'],
            'is_active' => true,
        ]));

        $products = [
            ['name' => 'Coffee Beans 250g', 'sku' => 'COF-001', 'cost_price' => 4.00, 'selling_price' => 8.50, 'quantity' => 40, 'category' => 0],
            ['name' => 'Green Tea 100g', 'sku' => 'TEA-001', 'cost_price' => 2.50, 'selling_price' => 5.00, 'quantity' => 60, 'category' => 0],
            ['name' => 'Chocolate Bar', 'sku' => 'SNK-001', 'cost_price' => 1.00, 'selling_price' => 2.50, 'quantity' => 100, 'category' => 1],
            ['name' => 'Salted Chips', 'sku' => 'SNK-002', 'cost_price' => 1.20, 'selling_price' => 2.00, 'quantity' => 80, 'category' => 1],
            ['name' => 'Whole Milk 1L', 'sku' => 'DRY-001', 'cost_price' => 1.50, 'selling_price' => 3.00, 'quantity' => 50, 'category' => 2],
            ['name' => 'Cheddar Cheese', 'sku' => 'DRY-002', 'cost_price' => 3.00, 'selling_price' => 6.50, 'quantity' => 30, 'category' => 2],
            ['name' => 'Sourdough Loaf', 'sku' => 'BAK-001', 'cost_price' => 1.80, 'selling_price' => 4.00, 'quantity' => 25, 'category' => 3],
            ['name' => 'All-Purpose Cleaner', 'sku' => 'CLN-001', 'cost_price' => 2.20, 'selling_price' => 5.50, 'quantity' => 45, 'category' => 4],
        ];

        $productModels = collect($products)->map(function (array $p) use ($tenant, $categories) {
            return Product::query()->create([
                'tenant_id' => $tenant->id,
                'category_id' => $categories[$p['category']]->id,
                'name' => $p['name'],
                'sku' => $p['sku'],
                'cost_price' => $p['cost_price'],
                'selling_price' => $p['selling_price'],
                'alert_quantity' => 10,
                'is_active' => true,
            ]);
        });

        foreach ($productModels as $i => $product) {
            Inventory::query()->create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'quantity' => $products[$i]['quantity'],
            ]);
        }

        $group = CustomerGroup::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Walk-in',
            'discount_rate' => 0,
            'is_active' => true,
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'customer_group_id' => $group->id,
            'name' => 'Walk-in Customer',
            'email' => null,
            'phone' => null,
            'credit_limit' => 0,
            'loyalty_points' => 0,
            'is_active' => true,
        ]);

        $supplier = Supplier::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Global Distributors',
            'email' => 'supply@ticksales.test',
            'phone' => '+1000000000',
            'is_active' => true,
        ]);

        $paymentMethods = collect([
            ['name' => 'Cash', 'type' => 'cash', 'sort_order' => 1],
            ['name' => 'Card', 'type' => 'card', 'sort_order' => 2],
            ['name' => 'Mobile Money', 'type' => 'mobile_money', 'sort_order' => 3],
        ])->map(fn (array $pm) => PaymentMethod::query()->create([
            'tenant_id' => $tenant->id,
            ...$pm,
            'is_active' => true,
        ]));

        $register = Register::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Main Counter',
            'is_active' => true,
        ]);

        $openSession = RegisterSession::query()->create([
            'register_id' => $register->id,
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'opened_by' => $cashier->id,
            'opening_balance' => 200.00,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        // Seed a handful of completed sales for reporting/demo.
        for ($i = 0; $i < 6; $i++) {
            $product = $productModels->random();
            $qty = fake()->numberBetween(1, 3);
            $unitPrice = (float) $product->selling_price;

            CreateSaleAction::execute([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'register_session_id' => $openSession->id,
                'customer_id' => $customer->id,
                'sold_by' => $cashier->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'tax_rate' => 0,
                    ],
                ],
                'payments' => [
                    [
                        'payment_method_id' => $paymentMethods->first()->id,
                        'amount' => $qty * $unitPrice,
                    ],
                ],
            ]);
        }

        $expenseCategory = ExpenseCategory::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Utilities',
        ]);

        Expense::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'expense_category_id' => $expenseCategory->id,
            'description' => 'Electricity',
            'amount' => 75.00,
            'expense_date' => now()->toDateString(),
            'recorded_by' => $manager->id,
        ]);
    }
}
