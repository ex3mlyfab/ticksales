import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface TransferItem {
    id: string;
    product_name: string | null;
    quantity: number;
}

interface StockTransfer {
    id: string;
    reference_no: string;
    status: string;
    from_branch_name: string | null;
    to_branch_name: string | null;
    note: string | null;
    items: TransferItem[];
    created_at: string;
}

interface Props {
    transfers: {
        data: StockTransfer[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function StockTransferIndexPage({ transfers }: Props) {
    return (
        <>
            <Head title="Stock transfers" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Stock transfers"
                    description="View and manage stock transfers between branches."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Transfers</h2>
                        <Link
                            href={route('stock-transfers.create')}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            New Transfer
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {transfers.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No stock transfers found.
                            </div>
                        ) : (
                            transfers.data.map((transfer) => (
                                <div
                                    key={transfer.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">{transfer.reference_no}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {transfer.from_branch_name} → {transfer.to_branch_name}
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                transfer.status === 'posted'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-yellow-100 text-yellow-800'
                                            }`}
                                        >
                                            {transfer.status}
                                        </span>
                                        {transfer.status === 'pending' && (
                                            <form
                                                action={route('stock-transfers.post', transfer.id)}
                                                method="POST"
                                            >
                                                <button
                                                    type="submit"
                                                    className="text-sm text-blue-500 hover:text-blue-700"
                                                >
                                                    Post
                                                </button>
                                            </form>
                                        )}
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                e.currentTarget.submit();
                                            }}
                                            action={route('stock-transfers.destroy', transfer.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-red-500 hover:text-red-700"
                                                onClick="return confirm('Are you sure you want to delete this transfer?')"
                                            >
                                                Delete
                                            </button>
                                            <input type="hidden" name="_method" value="DELETE" />
                                        </form>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

StockTransferIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Stock transfers',
            href: '/stock-transfers',
        },
    ],
};