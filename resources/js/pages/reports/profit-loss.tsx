import { Head, Link } from '@inertiajs/react';
import { Form, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Input, Label } from '@/components/ui';
import { profitLoss } from '@/routes/reports';

interface Branch {
    id: string;
    name: string;
}

interface ExpenseByCategory {
    name: string;
    amount: string;
}

interface Props {
    summary: {
        revenue: string;
        returns_amount: string;
        net_revenue: string;
        cogs: string;
        gross_profit: string;
        expenses_by_category: ExpenseByCategory[];
        operating_expenses: string;
        net_profit: string;
        sales_count: number;
    };
    filters: {
        from: string;
        to: string;
        branch_id: string | null;
    };
    branches: Branch[];
}

const PnlRow = ({ label, value }: { label: string; value: string }) => (
    <div className="flex items-center justify-between py-2">
        <span>{label}</span>
        <span className="font-medium">
            {Number(value).toFixed(2)}
        </span>
    </div>
);

export default function ProfitLossReportPage({
    summary,
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
            <Head title="Profit & loss report" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profit & loss report"
                    description="Track profit and loss per tenant store over a date range."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        method="get"
                        action={profitLoss.url()}
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
                                        <option value="">All stores</option>
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
                                    href={profitLoss.url()}
                                    className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                                >
                                    Reset
                                </Link>
                            </>
                        )}
                    </Form>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div className="rounded-xl border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">
                            Net revenue
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {Number(summary.net_revenue).toFixed(2)}
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">
                            Gross profit
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {Number(summary.gross_profit).toFixed(2)}
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">
                            Operating expenses
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {Number(summary.operating_expenses).toFixed(2)}
                        </p>
                    </div>
                    <div
                        className={`rounded-xl border bg-card p-4 shadow-sm ${
                            Number(summary.net_profit) < 0
                                ? 'text-red-600'
                                : ''
                        }`}
                    >
                        <p className="text-sm text-muted-foreground">
                            Net profit
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {Number(summary.net_profit).toFixed(2)}
                        </p>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold">
                            Income statement
                        </h2>
                        <div className="divide-y">
                            <PnlRow
                                label={`Revenue (${summary.sales_count} sales)`}
                                value={summary.revenue}
                            />
                            <PnlRow
                                label="Sales returns"
                                value={summary.returns_amount}
                            />
                            <PnlRow
                                label="Net revenue"
                                value={summary.net_revenue}
                            />
                            <PnlRow
                                label="Cost of goods sold"
                                value={`-${summary.cogs}`}
                            />
                            <PnlRow
                                label="Gross profit"
                                value={summary.gross_profit}
                            />
                            <PnlRow
                                label="Operating expenses"
                                value={`-${summary.operating_expenses}`}
                            />
                            <PnlRow
                                label="Net profit"
                                value={summary.net_profit}
                            />
                        </div>
                    </div>

                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold">
                            Expenses by category
                        </h2>
                        {summary.expenses_by_category.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No expenses in this period.
                            </p>
                        ) : (
                            <div className="divide-y">
                                {summary.expenses_by_category.map((row) => (
                                    <div
                                        key={row.name}
                                        className="flex items-center justify-between py-2"
                                    >
                                        <span>{row.name}</span>
                                        <span className="font-medium">
                                            {Number(row.amount).toFixed(2)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

ProfitLossReportPage.layout = {
    breadcrumbs: [
        {
            title: 'Reports',
            href: '/reports/profit-loss',
        },
        {
            title: 'Profit & loss',
            href: '/reports/profit-loss',
        },
    ],
};
