import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Input, Label } from '@/components/ui';
import { storefrontAnalytics } from '@/routes/reports';

type ProductPerformance = {
    product_id: string;
    name: string;
    product_views: number;
    cart_additions: number;
    cart_removals: number;
};

type Props = {
    enabled: boolean;
    summary: {
        sessions: number;
        events: number;
        product_views: number;
        checkout_starts: number;
        paid_orders: number;
    };
    funnel: Array<{ name: string; key: string; count: number }>;
    product_performance: ProductPerformance[];
    filters: { from: string; to: string };
    retention_days: number;
};

export default function StorefrontAnalyticsPage({
    enabled,
    summary,
    funnel,
    product_performance,
    filters,
    retention_days,
}: Props) {
    const funnelMax = Math.max(...funnel.map((row) => row.count), 1);

    return (
        <>
            <Head title="Storefront analytics" />
            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Storefront analytics"
                    description="Pseudonymous tenant funnel and product performance."
                />

                {!enabled && (
                    <div className="rounded-xl border border-amber-500/30 bg-amber-500/5 px-4 py-3 text-sm">
                        Analytics collection is currently disabled in Tenant settings.
                    </div>
                )}

                <div className="rounded-xl border bg-card p-5 shadow-sm">
                    <Form
                        method="get"
                        action={storefrontAnalytics.url()}
                        className="flex flex-wrap items-end gap-4"
                    >
                        {({ processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="from">From</Label>
                                    <Input id="from" name="from" type="date" defaultValue={filters.from} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="to">To</Label>
                                    <Input id="to" name="to" type="date" defaultValue={filters.to} />
                                </div>
                                <Button type="submit" disabled={processing}>Apply</Button>
                                <a href={storefrontAnalytics.url()} className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted">
                                    Reset
                                </a>
                            </>
                        )}
                    </Form>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    {[
                        ['Pseudonymous sessions', summary.sessions],
                        ['Events', summary.events],
                        ['Product views', summary.product_views],
                        ['Checkout starts', summary.checkout_starts],
                        ['Paid orders', summary.paid_orders],
                    ].map(([label, value]) => (
                        <div key={String(label)} className="rounded-xl border bg-card p-4 shadow-sm">
                            <p className="text-sm text-muted-foreground">{label}</p>
                            <p className="mt-1 text-2xl font-semibold tabular-nums">{value}</p>
                        </div>
                    ))}
                </div>

                <div className="grid gap-4 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                    <div className="rounded-xl border bg-card p-5 shadow-sm">
                        <h2 className="text-lg font-semibold">Funnel</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Unique pseudonymous sessions reaching each stage.
                        </p>
                        <div className="mt-5 space-y-4">
                            {funnel.map((row) => (
                                <div key={row.key}>
                                    <div className="mb-1 flex justify-between text-sm">
                                        <span>{row.name}</span>
                                        <span className="font-medium tabular-nums">{row.count}</span>
                                    </div>
                                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full rounded-full bg-primary transition-[width] duration-200"
                                            style={{ width: `${Math.max(row.count / funnelMax * 100, row.count > 0 ? 4 : 0)}%` }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-xl border bg-card p-5 shadow-sm">
                        <h2 className="text-lg font-semibold">Product performance</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Top products in this period by product views.
                        </p>
                        {product_performance.length === 0 ? (
                            <p className="mt-6 text-sm text-muted-foreground">No product events in this period.</p>
                        ) : (
                            <div className="mt-4 divide-y">
                                {product_performance.map((row) => (
                                    <div key={row.product_id} className="grid grid-cols-[minmax(0,1fr)_auto_auto_auto] items-center gap-4 py-3 text-sm">
                                        <span className="truncate font-medium">{row.name}</span>
                                        <span className="tabular-nums text-muted-foreground">{row.product_views} views</span>
                                        <span className="tabular-nums text-muted-foreground">{row.cart_additions} adds</span>
                                        <span className="tabular-nums text-muted-foreground">{row.cart_removals} removes</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

                <p className="text-xs text-muted-foreground">
                    Purpose: storefront product and funnel measurement. Visibility: authorized staff in this tenant with reports access.
                    {' '}Retention: analytics events are automatically deleted after {retention_days} days. The report uses aggregates only.
                </p>
            </div>
        </>
    );
}
