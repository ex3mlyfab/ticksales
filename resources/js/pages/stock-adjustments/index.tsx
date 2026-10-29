import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface AdjustmentItem {
    id: string;
    product_name: string | null;
    quantity_adjusted: number;
}

interface StockAdjustment {
    id: string;
    reference_no: string;
    branch_name: string | null;
    status: string;
    reason: string | null;
    items: AdjustmentItem[];
    created_at: string;
}

interface Props {
    adjustments: {
        data: StockAdjustment[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function StockAdjustmentIndexPage({ adjustments }: Props) {
    return (
        <>
            <Head title="Stock adjustments" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Stock adjustments"
                    description="View and manage stock adjustments."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Adjustments</h2>
                        <Link
                            href={route('stock-adjustments.create')}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            New Adjustment
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {adjustments.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No stock adjustments found.
                            </div>
                        ) : (
                            adjustments.data.map((adj) => (
                                <div key={adj.id} className="flex items-center justify-between py-3">
                                    <div>
                                        <p className="font-medium">{adj.reference_no}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {adj.branch_name} • {adj.items.length} item(s)
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                adj.status === 'posted'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-yellow-100 text-yellow-800'
                                            }`}
                                        >
                                            {adj.status}
                                        </span>
                                        {adj.status === 'draft' && (
                                            <form
                                                action={route('stock-adjustments.post', adj.id)}
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
                                            action={route('stock-adjustments.destroy', adj.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-red-500 hover:text-red-700"
                                                onClick="return confirm('Are you sure you want to delete this adjustment?')"
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

StockAdjustmentIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Stock adjustments',
            href: '/stock-adjustments',
        },
    ],
};