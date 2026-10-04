import { Head, usePage } from '@inertiajs/react';
import { dashboard } from '@/routes';

export default function Dashboard() {
    const { stats, topProducts, lowStock } = usePage().props as {
        stats: {
            total_sales_today: string;
            orders_today: number;
            items_sold_today: string;
            average_ticket: string;
            inventory_alerts: number;
        };
        topProducts: Array<{ name: string; total_quantity: string; total_revenue: string }>;
        lowStock: Array<{ name: string; quantity: string; alert_quantity: number }>;
    };

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-4">
                    <div className="rounded-xl border border-border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">Today sales</p>
                        <p className="mt-2 text-2xl font-semibold">${Number(stats.total_sales_today).toFixed(2)}</p>
                    </div>
                    <div className="rounded-xl border border-border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">Orders</p>
                        <p className="mt-2 text-2xl font-semibold">{stats.orders_today}</p>
                    </div>
                    <div className="rounded-xl border border-border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">Items sold</p>
                        <p className="mt-2 text-2xl font-semibold">{Number(stats.items_sold_today).toFixed(2)}</p>
                    </div>
                    <div className="rounded-xl border border-border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">Low stock</p>
                        <p className="mt-2 text-2xl font-semibold">{stats.inventory_alerts}</p>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="rounded-xl border border-border bg-card p-4 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold">Top products</h2>
                        <div className="space-y-3">
                            {topProducts.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No sales recorded today yet.</p>
                            ) : (
                                topProducts.map((product) => (
                                    <div key={product.name} className="flex items-center justify-between border-b border-border pb-2 last:border-none last:pb-0">
                                        <div>
                                            <p className="font-medium">{product.name}</p>
                                            <p className="text-xs text-muted-foreground">{product.total_quantity} units</p>
                                        </div>
                                        <p className="font-medium">${Number(product.total_revenue).toFixed(2)}</p>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>

                    <div className="rounded-xl border border-border bg-card p-4 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold">Inventory alerts</h2>
                        <div className="space-y-3">
                            {lowStock.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Inventory is tracking within safety limits.</p>
                            ) : (
                                lowStock.map((item) => (
                                    <div key={item.name} className="flex items-center justify-between border-b border-border pb-2 last:border-none last:pb-0">
                                        <div>
                                            <p className="font-medium">{item.name}</p>
                                            <p className="text-xs text-muted-foreground">Alert at {item.alert_quantity}</p>
                                        </div>
                                        <p className="font-medium text-amber-600">{item.quantity} on hand</p>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
