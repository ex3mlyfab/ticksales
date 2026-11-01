<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Every permission the application recognises. The tenant-owner and
     * platform-admin roles receive all of these; feature roles receive a subset.
     *
     * @var list<string>
     */
    public const PERMISSIONS = [
        'dashboard.view',

        'products.view',
        'products.create',
        'products.update',
        'products.delete',

        'product-categories.view',
        'product-categories.create',
        'product-categories.update',
        'product-categories.delete',

        'product-brands.view',
        'product-brands.create',
        'product-brands.update',
        'product-brands.delete',

        'product-units.view',
        'product-units.create',
        'product-units.update',
        'product-units.delete',

        'tax-rates.view',
        'tax-rates.create',
        'tax-rates.update',
        'tax-rates.delete',

        'discounts.view',
        'discounts.create',
        'discounts.update',
        'discounts.delete',

        'customers.view',
        'customers.create',
        'customers.update',
        'customers.delete',

        'customer-groups.view',
        'customer-groups.create',
        'customer-groups.update',
        'customer-groups.delete',

        'suppliers.view',
        'suppliers.create',
        'suppliers.update',
        'suppliers.delete',

        'payment-methods.view',
        'payment-methods.create',
        'payment-methods.update',
        'payment-methods.delete',

        'registers.view',
        'registers.create',
        'registers.update',
        'registers.delete',
        'registers.open',
        'registers.close',

        'inventory.view',
        'inventory.adjust',
        'inventory.transfer',

        'stock-adjustments.view',
        'stock-adjustments.create',
        'stock-adjustments.delete',

        'stock-transfers.view',
        'stock-transfers.create',
        'stock-transfers.delete',

        'purchases.view',
        'purchases.create',
        'purchases.update',
        'purchases.delete',

        'purchase-orders.view',
        'purchase-orders.create',
        'purchase-orders.update',
        'purchase-orders.delete',

        'purchase-returns.view',
        'purchase-returns.create',
        'purchase-returns.update',
        'purchase-returns.delete',

        'sale-returns.view',
        'sale-returns.create',

        'sales.view',
        'sales.create',
        'sales.update',
        'sales.refund',
        'sales.void',

        'expenses.view',
        'expenses.create',
        'expenses.update',
        'expenses.delete',

        'expense-categories.view',
        'expense-categories.create',
        'expense-categories.update',
        'expense-categories.delete',

        'reports.view',
        'reports.export',

        'users.view',
        'users.create',
        'users.update',
        'users.delete',

        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',

        'settings.view',
        'settings.update',

        'branches.view',
        'branches.create',
        'branches.update',
        'branches.delete',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission]);
        }
    }
}
