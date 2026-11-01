import { Head, Link } from '@inertiajs/react';
import { Form, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Input, Label } from '@/components/ui';
import { expenses as reportsExpenses } from '@/routes/reports';


interface Branch {
    id: string;
    name: string;
}

interface Expense {
    id: string;
    description: string;
    amount: string;
    expense_date: string;
    branch?: { name: string } | null;
    expense_category?: { name: string } | null;
    payment_method?: { name: string } | null;
}

interface CategoryRow {
    name: string;
    amount: string;
}

interface Props {
    expenses: {
        data: Expense[];
        links: Array<{ url: string; label: string; active: boolean }>;
    };
    summary: {
        total_amount: string;
        count: number;
    };
    by_category: CategoryRow[];
    filters: {
        from: string;
        to: string;
        branch_id: string | null;
    };
    branches: Branch[];
}

export default function ExpensesReportPage({
    expenses,
    summary,
    by_category,
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
            <Head title="Expense report" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Expense report"
                    description="Analyze operating expenses over a period."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        method="get"
                        action={reportsExpenses.url()}
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
                                    href={reportsExpenses.url()}
                                    className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                                >
                                    Reset
                                </Link>
                            </>
                        )}
                    </Form>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="rounded-xl border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">
                            Total expenses
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {Number(summary.total_amount).toFixed(2)}
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">Count</p>
                        <p className="mt-1 text-2xl font-semibold">
                            {summary.count}
                        </p>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="rounded-xl border bg-card p-6 shadow-sm lg:col-span-2">
                        <h2 className="mb-3 text-lg font-semibold">Expenses</h2>
                        {expenses.data.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No expenses in this period.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="pb-2">Date</th>
                                            <th className="pb-2">
                                                Description
                                            </th>
                                            <th className="pb-2">Category</th>
                                            <th className="pb-2">Branch</th>
                                            <th className="pb-2 text-right">
                                                Amount
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {expenses.data.map((e) => (
                                            <tr key={e.id}>
                                                <td className="py-2">
                                                    {e.expense_date}
                                                </td>
                                                <td className="py-2">
                                                    {e.description}
                                                </td>
                                                <td className="py-2 text-muted-foreground">
                                                    {e.expense_category?.name ??
                                                        'Uncategorized'}
                                                </td>
                                                <td className="py-2">
                                                    {e.branch?.name ?? '-'}
                                                </td>
                                                <td className="py-2 text-right">
                                                    {Number(e.amount).toFixed(
                                                        2,
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold">
                            By category
                        </h2>
                        {by_category.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No category data.
                            </p>
                        ) : (
                            <div className="divide-y">
                                {by_category.map((c) => (
                                    <div
                                        key={c.name}
                                        className="flex items-center justify-between py-2"
                                    >
                                        <span>{c.name}</span>
                                        <span className="font-medium">
                                            {Number(c.amount).toFixed(2)}
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

ExpensesReportPage.layout = {
    breadcrumbs: [
        {
            title: 'Reports',
            href: '/reports/expenses',
        },
        {
            title: 'Expenses',
            href: '/reports/expenses',
        },
    ],
};
