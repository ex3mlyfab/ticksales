import { Head, Link } from '@inertiajs/react';
import { Form, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Label } from '@/components/ui';
import { inventory as reportsInventory } from '@/routes/reports';


interface Branch {
    id: string;
    name: string;
}

interface InventoryRow {
    product_name: string;
    sku: string | null;
    alert_quantity: number;
    branch_name: string;
    quantity: string;
    stock_value: string;
}

interface Props {
    inventory: {
        data: InventoryRow[];
        links: Array<{ url: string; label: string; active: boolean }>;
    };
    summary: {
        low_stock_count: number;
        total_value: string;
    };
    filters: {
        branch_id: string | null;
    };
    branches: Branch[];
}

export default function InventoryReportPage({
    inventory,
    summary,
    filters,
    branches,
}: Props) {
    const form = useForm({
        branch_id: filters.branch_id ?? '',
    });

    return (
        <>
            <Head title="Inventory report" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Inventory report"
                    description="Current stock levels and valuation."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        method="get"
                        action={reportsInventory.url()}
                        className="flex flex-wrap items-end gap-4"
                    >
                        {({ processing }) => (
                            <>
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
                                    href={reportsInventory.url()}
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
                            Stock value
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {Number(summary.total_value).toFixed(2)}
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-4 shadow-sm">
                        <p className="text-sm text-muted-foreground">
                            Low stock items
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {summary.low_stock_count}
                        </p>
                    </div>
                </div>

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <h2 className="mb-3 text-lg font-semibold">Stock levels</h2>
                    {inventory.data.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No inventory records found.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="pb-2">Product</th>
                                        <th className="pb-2">SKU</th>
                                        <th className="pb-2">Branch</th>
                                        <th className="pb-2 text-right">
                                            Quantity
                                        </th>
                                        <th className="pb-2 text-right">
                                            Value
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {inventory.data.map((row, i) => (
                                        <tr key={i}>
                                            <td className="py-2">
                                                {row.product_name}
                                            </td>
                                            <td className="py-2 text-muted-foreground">
                                                {row.sku ?? '-'}
                                            </td>
                                            <td className="py-2">
                                                {row.branch_name}
                                            </td>
                                            <td className="py-2 text-right">
                                                {Number(row.quantity).toFixed(
                                                    2,
                                                )}
                                            </td>
                                            <td className="py-2 text-right">
                                                {Number(
                                                    row.stock_value,
                                                ).toFixed(2)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

InventoryReportPage.layout = {
    breadcrumbs: [
        {
            title: 'Reports',
            href: '/reports/inventory',
        },
        {
            title: 'Inventory',
            href: '/reports/inventory',
        },
    ],
};
