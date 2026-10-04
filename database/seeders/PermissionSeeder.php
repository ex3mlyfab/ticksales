<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'dashboard.view',
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'inventory.view',
            'inventory.adjust',
            'inventory.transfer',
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',
            'sales.view',
            'sales.create',
            'sales.update',
            'sales.refund',
            'sales.void',
            'purchases.view',
            'purchases.create',
            'purchases.update',
            'purchases.delete',
            'expenses.view',
            'expenses.create',
            'expenses.update',
            'expenses.delete',
            'registers.view',
            'registers.open',
            'registers.close',
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
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission]);
        }
    }
}
