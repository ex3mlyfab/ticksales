import { Head, usePage } from '@inertiajs/react';
import {
    Banknote,
    Package,
    ReceiptText,
    ShoppingCart,
    TriangleAlert,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { dashboard } from '@/routes';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type DashboardProps = {
    stats: {
        total_sales_today: string;
        orders_today: number;
        items_sold_today: string;
        average_ticket: string;
        inventory_alerts: number;
    };
    topProducts: Array<{
        name: string;
        total_quantity: string;
        total_revenue: string;
    }>;
    lowStock: Array<{
        name: string;
        quantity: string;
        alert_quantity: number;
    }>;
};

type Metric = {
    label: string;
    value: string;
    description: string;
    icon: ReactNode;
    iconClassName: string;
};

function MetricCard({ metric }: { metric: Metric }) {
    return (
        <Card className="gap-4 p-5">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-medium text-muted-foreground">
                    {metric.label}
                </p>
                <span
                    className={`flex size-9 items-center justify-center rounded-lg ${metric.iconClassName}`}
                >
                    {metric.icon}
                </span>
            </div>
            <div>
                <p className="text-2xl font-semibold tracking-tight tabular-nums">
                    {metric.value}
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                    {metric.description}
                </p>
            </div>
        </Card>
    );
}

export default function Dashboard() {
    const { stats, topProducts, lowStock } = usePage<DashboardProps>().props;

    const metrics: Metric[] = [
        {
            label: 'Orders',
            value: stats.orders_today.toLocaleString(),
            description: 'Completed today',
            icon: <ShoppingCart aria-hidden="true" className="size-4" />,
            iconClassName:
                'bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300',
        },
        {
            label: 'Items sold',
            value: Number(stats.items_sold_today).toFixed(2),
            description: 'Units moved today',
            icon: <Package aria-hidden="true" className="size-4" />,
            iconClassName:
                'bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300',
        },
        {
            label: 'Average ticket',
            value: `$${Number(stats.average_ticket).toFixed(2)}`,
            description: 'Sales per order',
            icon: <ReceiptText aria-hidden="true" className="size-4" />,
            iconClassName:
                'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300',
        },
        {
            label: 'Low stock',
            value: stats.inventory_alerts.toLocaleString(),
            description: 'Items need attention',
            icon: <TriangleAlert aria-hidden="true" className="size-4" />,
            iconClassName:
                'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300',
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex min-w-0 flex-1 flex-col gap-6">
                <header className="flex flex-col gap-1">
                    <p className="text-xs font-semibold tracking-[0.16em] text-muted-foreground uppercase">
                        Store pulse
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight text-balance">
                        Dashboard
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Track today&apos;s sales and keep an eye on stock.
                    </p>
                </header>

                <section
                    aria-label="Today's performance"
                    className="grid gap-4 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1.95fr)]"
                >
                    <Card className="justify-between gap-8 border-emerald-200/80 bg-emerald-50 p-6 dark:border-emerald-900/70 dark:bg-emerald-950/30">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <p className="text-sm font-medium text-emerald-900 dark:text-emerald-200">
                                    Sales today
                                </p>
                                <p className="mt-1 text-xs text-emerald-800/75 dark:text-emerald-200/70">
                                    Total recorded revenue
                                </p>
                            </div>
                            <span className="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-900/70 dark:text-emerald-200">
                                <Banknote
                                    aria-hidden="true"
                                    className="size-5"
                                />
                            </span>
                        </div>
                        <p className="text-4xl font-semibold tracking-tight text-emerald-950 tabular-nums dark:text-emerald-100">
                            ${Number(stats.total_sales_today).toFixed(2)}
                        </p>
                    </Card>

                    <div className="grid gap-4 sm:grid-cols-2">
                        {metrics.map((metric) => (
                            <MetricCard key={metric.label} metric={metric} />
                        ))}
                    </div>
                </section>

                <section
                    aria-label="Product and inventory summaries"
                    className="grid gap-4 xl:grid-cols-2"
                >
                    <Card className="gap-0">
                        <CardHeader className="flex-row items-start justify-between gap-4 pb-4">
                            <div className="space-y-1">
                                <CardTitle>Top products</CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Best sellers from today&apos;s orders
                                </p>
                            </div>
                            <span className="flex size-9 items-center justify-center rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300">
                                <Package
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </span>
                        </CardHeader>
                        <CardContent>
                            {topProducts.length === 0 ? (
                                <div className="rounded-lg bg-muted/60 px-4 py-6 text-center">
                                    <p className="text-sm font-medium">
                                        No sales recorded yet
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Product performance will appear here
                                        after the first order.
                                    </p>
                                </div>
                            ) : (
                                <div className="divide-y divide-border">
                                    {topProducts.map((product, index) => (
                                        <div
                                            key={product.name}
                                            className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                                        >
                                            <div className="flex min-w-0 items-center gap-3">
                                                <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold text-muted-foreground tabular-nums">
                                                    {index + 1}
                                                </span>
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium">
                                                        {product.name}
                                                    </p>
                                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                                        {product.total_quantity}{' '}
                                                        units sold
                                                    </p>
                                                </div>
                                            </div>
                                            <p className="shrink-0 text-sm font-semibold tabular-nums">
                                                $
                                                {Number(
                                                    product.total_revenue,
                                                ).toFixed(2)}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="gap-0">
                        <CardHeader className="flex-row items-start justify-between gap-4 pb-4">
                            <div className="space-y-1">
                                <CardTitle>Inventory alerts</CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Stock that is nearing its reorder point
                                </p>
                            </div>
                            <span className="flex size-9 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300">
                                <TriangleAlert
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </span>
                        </CardHeader>
                        <CardContent>
                            {lowStock.length === 0 ? (
                                <div className="rounded-lg bg-emerald-50 px-4 py-6 text-center dark:bg-emerald-950/30">
                                    <p className="text-sm font-medium text-emerald-900 dark:text-emerald-200">
                                        Stock is looking good
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        No products are below their alert
                                        quantity.
                                    </p>
                                </div>
                            ) : (
                                <div className="divide-y divide-border">
                                    {lowStock.map((item) => (
                                        <div
                                            key={item.name}
                                            className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0"
                                        >
                                            <div className="flex min-w-0 items-center gap-3">
                                                <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300">
                                                    <TriangleAlert
                                                        aria-hidden="true"
                                                        className="size-4"
                                                    />
                                                </span>
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium">
                                                        {item.name}
                                                    </p>
                                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                                        Alert at{' '}
                                                        {item.alert_quantity}
                                                    </p>
                                                </div>
                                            </div>
                                            <span className="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 tabular-nums dark:bg-amber-950/70 dark:text-amber-200">
                                                {item.quantity} on hand
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>
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
