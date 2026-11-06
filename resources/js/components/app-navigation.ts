import {
    ArrowLeftRight,
    BarChart3,
    Boxes,
    ClipboardList,
    CreditCard,
    Landmark,
    LayoutGrid,
    LineChart,
    Package,
    Percent,
    ReceiptText,
    Ruler,
    Settings,
    ShoppingBag,
    Store,
    Tags,
    Truck,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { index as customersIndex } from '@/routes/customers';
import { index as customerGroupsIndex } from '@/routes/customer-groups';
import { index as discountsIndex } from '@/routes/discounts';
import { index as expenseCategoriesIndex } from '@/routes/expense-categories';
import { index as expensesIndex } from '@/routes/expenses';
import { index as inventoryIndex } from '@/routes/inventory';
import { index as paymentMethodsIndex } from '@/routes/payment-methods';
import { index as posIndex } from '@/routes/pos';
import { index as productBrandsIndex } from '@/routes/product-brands';
import { index as productCategoriesIndex } from '@/routes/product-categories';
import { index as productUnitsIndex } from '@/routes/product-units';
import { index as productsIndex } from '@/routes/products';
import { index as purchaseOrdersIndex } from '@/routes/purchase-orders';
import { index as purchaseReturnsIndex } from '@/routes/purchase-returns';
import { index as purchasesIndex } from '@/routes/purchases';
import {
    balanceSheet as reportsBalanceSheet,
    expenses as reportsExpenses,
    inventory as reportsInventory,
    profitLoss as reportsProfitLoss,
    registerSessions as reportsRegisterSessions,
    sales as reportsSales,
    storefrontAnalytics as reportsStorefrontAnalytics,
} from '@/routes/reports';
import { index as registersIndex } from '@/routes/registers';
import { index as registerSessionsIndex } from '@/routes/register-sessions';
import { index as saleReturnsIndex } from '@/routes/sale-returns';
import { index as stockAdjustmentsIndex } from '@/routes/stock-adjustments';
import { index as stockTransfersIndex } from '@/routes/stock-transfers';
import { index as storefrontOrdersIndex } from '@/routes/tenant/storefront/orders';
import { index as suppliersIndex } from '@/routes/suppliers';
import { index as taxRatesIndex } from '@/routes/tax-rates';
import {
    branding as tenantBranding,
    settings as tenantSettings,
} from '@/routes/tenant';
import type { NavItem } from '@/types';

type AppNavItem = NavItem & {
    permission?: string;
};

export type AppNavSection = {
    title: string;
    items: AppNavItem[];
};

const appNavigation: AppNavSection[] = [
    {
        title: 'Dashboard',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ],
    },
    {
        title: 'Sales',
        items: [
            {
                title: 'Point of sale',
                href: posIndex(),
                icon: Store,
                permission: 'sales.view',
            },
            {
                title: 'Sale returns',
                href: saleReturnsIndex(),
                icon: ReceiptText,
                permission: 'sale-returns.view',
            },
        ],
    },
    {
        title: 'Catalog',
        items: [
            {
                title: 'Products',
                href: productsIndex(),
                icon: Package,
                permission: 'products.view',
            },
            {
                title: 'Categories',
                href: productCategoriesIndex(),
                icon: Tags,
                permission: 'product-categories.view',
            },
            {
                title: 'Brands',
                href: productBrandsIndex(),
                icon: Boxes,
                permission: 'product-brands.view',
            },
            {
                title: 'Units',
                href: productUnitsIndex(),
                icon: Ruler,
                permission: 'product-units.view',
            },
            {
                title: 'Tax rates',
                href: taxRatesIndex(),
                icon: ReceiptText,
                permission: 'tax-rates.view',
            },
            {
                title: 'Discounts',
                href: discountsIndex(),
                icon: Percent,
                permission: 'discounts.view',
            },
        ],
    },
    {
        title: 'Inventory',
        items: [
            {
                title: 'Stock',
                href: inventoryIndex(),
                icon: Boxes,
                permission: 'inventory.view',
            },
            {
                title: 'Stock adjustments',
                href: stockAdjustmentsIndex(),
                icon: ClipboardList,
                permission: 'stock-adjustments.view',
            },
            {
                title: 'Stock transfers',
                href: stockTransfersIndex(),
                icon: ArrowLeftRight,
                permission: 'stock-transfers.view',
            },
        ],
    },
    {
        title: 'Customers',
        items: [
            {
                title: 'Customers',
                href: customersIndex(),
                icon: Users,
                permission: 'customers.view',
            },
            {
                title: 'Customer groups',
                href: customerGroupsIndex(),
                icon: Users,
                permission: 'customer-groups.view',
            },
        ],
    },
    {
        title: 'Purchasing',
        items: [
            {
                title: 'Suppliers',
                href: suppliersIndex(),
                icon: Truck,
                permission: 'suppliers.view',
            },
            {
                title: 'Purchases',
                href: purchasesIndex(),
                icon: ReceiptText,
                permission: 'purchases.view',
            },
            {
                title: 'Purchase orders',
                href: purchaseOrdersIndex(),
                icon: ClipboardList,
                permission: 'purchase-orders.view',
            },
            {
                title: 'Purchase returns',
                href: purchaseReturnsIndex(),
                icon: ReceiptText,
                permission: 'purchase-returns.view',
            },
        ],
    },
    {
        title: 'Operations',
        items: [
            {
                title: 'Registers',
                href: registersIndex(),
                icon: Store,
                permission: 'registers.view',
            },
            {
                title: 'Register sessions',
                href: registerSessionsIndex(),
                icon: ClipboardList,
                permission: 'registers.view',
            },
            {
                title: 'Payment methods',
                href: paymentMethodsIndex(),
                icon: CreditCard,
                permission: 'payment-methods.view',
            },
        ],
    },
    {
        title: 'Expenses',
        items: [
            {
                title: 'Expenses',
                href: expensesIndex(),
                icon: ReceiptText,
                permission: 'expenses.view',
            },
            {
                title: 'Expense categories',
                href: expenseCategoriesIndex(),
                icon: Tags,
                permission: 'expense-categories.view',
            },
        ],
    },
    {
        title: 'Reports',
        items: [
            {
                title: 'Sales report',
                href: reportsSales(),
                icon: BarChart3,
                permission: 'reports.view',
            },
            {
                title: 'Inventory report',
                href: reportsInventory(),
                icon: BarChart3,
                permission: 'reports.view',
            },
            {
                title: 'Register sessions report',
                href: reportsRegisterSessions(),
                icon: BarChart3,
                permission: 'reports.view',
            },
            {
                title: 'Expenses report',
                href: reportsExpenses(),
                icon: BarChart3,
                permission: 'reports.view',
            },
            {
                title: 'Profit & loss report',
                href: reportsProfitLoss(),
                icon: LineChart,
                permission: 'reports.view',
            },
            {
                title: 'Balance sheet report',
                href: reportsBalanceSheet(),
                icon: Landmark,
                permission: 'reports.view',
            },
            {
                title: 'Storefront analytics',
                href: reportsStorefrontAnalytics(),
                icon: BarChart3,
                permission: 'reports.view',
            },
        ],
    },
    {
        title: 'Settings',
        items: [
            {
                title: 'Tenant settings',
                href: tenantSettings(),
                icon: Settings,
                permission: 'settings.view',
            },
            {
                title: 'Branding',
                href: tenantBranding(),
                icon: Settings,
                permission: 'settings.view',
            },
        ],
    },
];

export function getAppNavigation(
    permissions: string[],
    tenantId: string | null = null,
): AppNavSection[] {
    const navigation = appNavigation
        .map((section) => ({
            ...section,
            items: section.items.filter(
                (item) =>
                    item.permission === undefined ||
                    permissions.includes(item.permission),
            ),
        }));
    const salesSection = navigation.find((section) => section.title === 'Sales');

    if (tenantId !== null && permissions.includes('sales.view') && salesSection !== undefined) {
        salesSection.items.splice(1, 0, {
            title: 'Online orders',
            href: storefrontOrdersIndex({ tenant: tenantId }),
            icon: ShoppingBag,
        });
    }

    return navigation.filter((section) => section.items.length > 0);
}
