import { Head, Link } from '@inertiajs/react';
import { Form, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Input, Label } from '@/components/ui';
import { sales } from '@/routes/reports';


interface Branch {
    id: string;
    name: string;
}

interface Daily {
    date: string;
    orders: number;
    total: string;
}

interface TopProduct {
    name: string;
    quantity: string;
    revenue: string;
}

interface Payment {
    name: string;
    amount: string;
}

interface Props {
    summary: {
        total_sales: string;
        orders_count: number;
        items_sold: string;
        average_ticket: string;
    };
    daily: Daily[];
    top_products: TopProduct[];
    payments: Payment[];
    filters: {
        from: string;
        to: string;
        branch_id: string | null;
    };
    branches: Branch[];
}

export default function SalesReportPage({
    summary,
    daily,
    top_products,
    payments,
    filters,
    branches,
}: Props) {
    const form = useForm({
        from: filters.from,
        to: filters.to,
        branch_id: filters.branch_id ?? '',
    });

    return (
        <>
            <Head title="Sales report" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Sales report"
                    description="Analyze sales performance over a date range."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        method="get"
                        action={sales.url()}
                        className="flex flex-wrap items-end gap-4"
                    >
                        {({ processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="from">From</Label>
                                    <Input
                                        id="from"
                                        name="from"
                                        type="date"
                                        value={form.data.from}
                                        onChange={(e) =>
                                            form.setData('from', e.target.value)
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="to">To</Label>
                                    <Input
                                        id="to"
                                        name="to"
                                        type="date"
                                        value={form.data.to}
                                        onChange={(e) =>
                                            form.setData('to', e.target.value)
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="branch_id">Branch</Label>
                                    <select
                                        id="branch_id"
                                        name="branch_id"
                                        value={form.data.branch_id}
                                        onChange={(e) =>
                                            form.setData(
                                                'branch_id',
                                                e.target.value,
                                            )
                                        }
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="">All branches</option>
                                        {branches.map((b) => (
                                            <option key={b.id} value={b.id}>
                                                {b.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <Button type="submit" disabled={processing}>
                                    Apply
                                </Button>
                                <Link
                                    href={sales.url()}
                                    className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                                >
                                    Reset
                                </Link>
                            </>
                        )}
                    </Form>
                </div>

                <div className="grid grid-cols-4 gap-4">
                    <div className="rounded-xl border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">
                            Total sales
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {Number(summary.total_sales).toFixed(2)}
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">Orders</p>
                        <p className="mt-1 text-2xl font-semibold">
                            {summary.orders_count}
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">
                            Items sold
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {Number(summary.items_sold).toFixed(2)}
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">
                            Average ticket
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {Number(summary.average_ticket).toFixed(2)}
                        </p>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold">
                            Daily breakdown
                        </h2>
                        {daily.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No sales in this period.
                            </p>
                        ) : (
                            <div className="divide-y">
                                {daily.map((row) => (
                                    <div
                                        key={row.date}
                                        className="flex items-center justify-between py-2"
                                    >
                                        <span>{row.date}</span>
                                        <span className="text-muted-foreground">
                                            {row.orders} orders
                                        </span>
                                        <span className="font-medium">
                                            {Number(row.total).toFixed(2)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="space-y-4">
                        <div className="rounded-xl border bg-card p-6 shadow-sm">
                            <h2 className="mb-3 text-lg font-semibold">
                                Payment methods
                            </h2>
                            {payments.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No payments recorded.
                                </p>
                            ) : (
                                <div className="divide-y">
                                    {payments.map((p) => (
                                        <div
                                            key={p.name}
                                            className="flex items-center justify-between py-2"
                                        >
                                            <span>{p.name}</span>
                                            <span className="font-medium">
                                                {Number(p.amount).toFixed(2)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="rounded-xl border bg-card p-6 shadow-sm">
                            <h2 className="mb-3 text-lg font-semibold">
                                Top products
                            </h2>
                            {top_products.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No products sold.
                                </p>
                            ) : (
                                <div className="divide-y">
                                    {top_products.map((p) => (
                                        <div
                                            key={p.name}
                                            className="flex items-center justify-between py-2"
                                        >
                                            <div>
                                                <p className="font-medium">
                                                    {p.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {Number(p.quantity).toFixed(
                                                        2,
                                                    )}{' '}
                                                    units
                                                </p>
                                            </div>
                                            <span className="font-medium">
                                                {Number(p.revenue).toFixed(2)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

SalesReportPage.layout = {
    breadcrumbs: [
        {
            title: 'Reports',
            href: '/reports/sales',
        },
        {
            title: 'Sales',
            href: '/reports/sales',
        },
    ],
};
