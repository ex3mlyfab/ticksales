<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'platform_admin' => [
                'description' => 'Platform administrator',
            ],
            'tenant_owner' => [
                'description' => 'Tenant owner',
            ],
            'manager' => [
                'description' => 'Tenant manager',
            ],
            'cashier' => [
                'description' => 'Cashiering staff',
            ],
            'inventory_manager' => [
                'description' => 'Inventory manager',
            ],
            'accountant' => [
                'description' => 'Accounting user',
            ],
        ];

        foreach ($roles as $name => $config) {
            $role = Role::query()->firstOrCreate(['name' => $name], $config);

            if ($name === 'platform_admin') {
                $role->permissions()->sync(Permission::query()->pluck('id'));

                continue;
            }

            if ($name === 'tenant_owner') {
                $role->permissions()->sync(Permission::query()->pluck('id'));

                continue;
            }

            if ($name === 'manager') {
                $role->permissions()->sync(Permission::query()->whereNotIn('name', [
                    'roles.delete',
                    'users.delete',
                ])->pluck('id'));

                continue;
            }

            if ($name === 'cashier') {
                $role->permissions()->sync(Permission::query()->whereIn('name', [
                    'dashboard.view',
                    'sales.view',
                    'sales.create',
                    'sales.update',
                    'sales.refund',
                    'sales.void',
                    'customers.view',
                    'customers.create',
                    'customers.update',
                    'registers.view',
                    'settings.view',
                ])->pluck('id'));

                continue;
            }

            if ($name === 'inventory_manager') {
                $role->permissions()->sync(Permission::query()->whereIn('name', [
                    'dashboard.view',
                    'products.view',
                    'products.create',
                    'products.update',
                    'inventory.view',
                    'inventory.adjust',
                    'inventory.transfer',
                    'suppliers.view',
                    'reports.view',
                ])->pluck('id'));

                continue;
            }

            if ($name === 'accountant') {
                $role->permissions()->sync(Permission::query()->whereIn('name', [
                    'dashboard.view',
                    'sales.view',
                    'purchases.view',
                    'expenses.view',
                    'reports.view',
                    'reports.export',
                    'settings.view',
                ])->pluck('id'));
            }
        }
    }
}
