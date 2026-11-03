import { Head, Link } from '@inertiajs/react';
import { Form, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Input, Label } from '@/components/ui';
import { balanceSheet } from '@/routes/reports';

interface Branch {
    id: string;
    name: string;
}

interface Props {
    summary: {
        cash_on_hand: string;
        accounts_receivable: string;
        inventory_value: string;
        total_assets: string;
        accounts_payable: string;
        total_liabilities: string;
        retained_earnings: string;
        owner_capital: string;
        total_equity: string;
    };
    filters: {
        as_of: string;
        branch_id: string | null;
    };
    branches: Branch[];
}

const SheetRow = ({ label, value }: { label: string; value: string }) => (
    <div className="flex items-center justify-between py-2">
        <span>{label}</span>
        <span className="font-medium">
            {Number(value).toFixed(2)}
        </span>
    </div>
);

export default function BalanceSheetReportPage({
    summary,
    filters,
    branches,
}: Props) {
    const form = useForm({
        as_of: filters.as_of,
        branch_id: filters.branch_id ?? '',
    });

    return (
        <>
            <Head title="Balance sheet report" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Balance sheet report"
                    description="Assets, liabilities and equity snapshot per tenant store."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        method="get"
                        action={balanceSheet.url()}
                        className="flex flex-wrap items-end gap-4"
                    >
                        {({ processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="as_of">As of</Label>
                                    <Input
                                        id="as_of"
                                        name="as_of"
                                        type="date"
                                        value={form.data.as_of}
                                        onChange={(e) =>
                                            form.setData('as_of', e.target.value)
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
                                    href={balanceSheet.url()}
                                    className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                                >
                                    Reset
                                </Link>
                            </>
                        )}
                    </Form>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold">Assets</h2>
                        <div className="divide-y">
                            <SheetRow
                                label="Cash on hand"
                                value={summary.cash_on_hand}
                            />
                            <SheetRow
                                label="Accounts receivable"
                                value={summary.accounts_receivable}
                            />
                            <SheetRow
                                label="Inventory value"
                                value={summary.inventory_value}
                            />
                            <SheetRow
                                label="Total assets"
                                value={summary.total_assets}
                            />
                        </div>
                    </div>

                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold">
                            Liabilities
                        </h2>
                        <div className="divide-y">
                            <SheetRow
                                label="Accounts payable"
                                value={summary.accounts_payable}
                            />
                            <SheetRow
                                label="Total liabilities"
                                value={summary.total_liabilities}
                            />
                        </div>
                    </div>

                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <h2 className="mb-3 text-lg font-semibold">Equity</h2>
                        <div className="divide-y">
                            <SheetRow
                                label="Retained earnings"
                                value={summary.retained_earnings}
                            />
                            <SheetRow
                                label="Owner capital (implied)"
                                value={summary.owner_capital}
                            />
                            <SheetRow
                                label="Total equity"
                                value={summary.total_equity}
                            />
                        </div>
                    </div>
                </div>

                <p className="text-xs text-muted-foreground">
                    The equity section is implied: owner capital balances the
                    sheet so that assets = liabilities + equity, because cash
                    injected into registers has no dedicated capital ledger.
                </p>
            </div>
        </>
    );
}

BalanceSheetReportPage.layout = {
    breadcrumbs: [
        {
            title: 'Reports',
            href: '/reports/balance-sheet',
        },
        {
            title: 'Balance sheet',
            href: '/reports/balance-sheet',
        },
    ],
};
