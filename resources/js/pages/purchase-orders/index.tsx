import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { create, post, show, destroy } from '@/routes/purchase-orders';


interface PurchaseOrderItem {
    id: string;
    product_name: string | null;
    quantity: number;
    unit_cost: number;
}

interface PurchaseOrder {
    id: string;
    reference_no: string;
    status: string;
    order_date: string;
    expected_date: string | null;
    subtotal: number;
    total: number;
    branch_name: string | null;
    supplier_name: string | null;
    items: PurchaseOrderItem[];
    created_at: string;
}

interface Props {
    orders: {
        data: PurchaseOrder[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function PurchaseOrderIndexPage({ orders }: Props) {
    return (
        <>
            <Head title="Purchase orders" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Purchase orders"
                    description="Manage supplier purchase orders."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">
                            Purchase Orders
                        </h2>
                        <Link
                            href={create.url()}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            New Purchase Order
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {orders.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No purchase orders found.
                            </div>
                        ) : (
                            orders.data.map((order) => (
                                <div
                                    key={order.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {order.reference_no}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {order.supplier_name} â€¢{' '}
                                            {order.branch_name} â€¢{' '}
                                            {order.items.length} item(s)
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                order.status === 'posted'
                                                    ? 'bg-success/10 text-forest dark:bg-success/15 dark:text-success'
                                                    : 'bg-warning/15 text-forest dark:bg-warning/15 dark:text-warning'
                                            }`}
                                        >
                                            {order.status}
                                        </span>
                                        {order.status === 'draft' && (
                                            <form
                                                action={post.url(order.id)}
                                                method="POST"
                                            >
                                                <button
                                                    type="submit"
                                                    className="text-sm text-info hover:text-info"
                                                >
                                                    Receive
                                                </button>
                                            </form>
                                        )}
                                        <Link
                                            href={show.url(order.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            View
                                        </Link>
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                e.currentTarget.submit();
                                            }}
                                            action={destroy.url(order.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-destructive hover:text-destructive"
                                                onClick={(e) => { if (!confirm('Are you sure you want to delete this purchase order?')) e.preventDefault(); }}
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

PurchaseOrderIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Purchase orders',
            href: '/purchase-orders',
        },
    ],
};
