import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { create, post, destroy } from '@/routes/stock-adjustments';


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
                            href={create.url()}
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
                                <div
                                    key={adj.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {adj.reference_no}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {adj.branch_name} â€¢{' '}
                                            {adj.items.length} item(s)
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                adj.status === 'posted'
                                                    ? 'bg-success/10 text-forest dark:bg-success/15 dark:text-success'
                                                    : 'bg-warning/15 text-forest dark:bg-warning/15 dark:text-warning'
                                            }`}
                                        >
                                            {adj.status}
                                        </span>
                                        {adj.status === 'draft' && (
                                            <form
                                                action={post.url(adj.id)}
                                                method="POST"
                                            >
                                                <button
                                                    type="submit"
                                                    className="text-sm text-info hover:text-info"
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
                                            action={destroy.url(adj.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-destructive hover:text-destructive"
                                                onClick={(e) => { if (!confirm('Are you sure you want to delete this adjustment?')) e.preventDefault(); }}
                                            >
                                                Delete
                                            </button>
                                            <input
                                                type="hidden"
                                                name="_method"
                                                value="DELETE"
                                            />
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
