import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { post, edit, index } from '@/routes/purchase-orders';


interface OrderItem {
    id: string;
    product: { id: string; name: string } | null;
    quantity: string;
    unit_cost: string;
    tax_rate: string;
    discount_amount: string;
    line_total: string;
}

interface Order {
    id: string;
    reference_no: string;
    status: string;
    order_date: string;
    expected_date: string | null;
    subtotal: string;
    tax_amount: string;
    discount_amount: string;
    total: string;
    notes: string | null;
    branch: { name: string } | null;
    supplier: { name: string } | null;
    items: OrderItem[];
}

interface Props {
    order: Order;
}

export default function PurchaseOrderShowPage({ order }: Props) {
    return (
        <>
            <Head title={order.reference_no} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={order.reference_no}
                    description={`Supplier ${order.supplier?.name ?? '-'} • ${order.branch?.name ?? '-'}`}
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Items</h2>
                        <div className="flex items-center gap-2">
                            <span
                                className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${order.status === 'posted' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}
                            >
                                {order.status}
                            </span>
                            {order.status === 'draft' && (
                                <>
                                    <form
                                        action={post.url(order.id)}
                                        method="POST"
                                    >
                                        <button
                                            type="submit"
                                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                                        >
                                            Receive
                                        </button>
                                    </form>
                                    <Link
                                        href={edit.url(order.id)}
                                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                                    >
                                        Edit
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="mt-4 divide-y">
                        {order.items.map((item) => (
                            <div
                                key={item.id}
                                className="flex items-center justify-between py-3"
                            >
                                <div>
                                    <p className="font-medium">
                                        {item.product?.name ?? 'Product'}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {Number(item.quantity).toFixed(2)} x{' '}
                                        {Number(item.unit_cost).toFixed(2)}
                                    </p>
                                </div>
                                <span className="font-medium">
                                    {Number(item.line_total).toFixed(2)}
                                </span>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4 space-y-1 border-t pt-4 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Subtotal
                            </span>
                            <span>{Number(order.subtotal).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Tax</span>
                            <span>{Number(order.tax_amount).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Discount
                            </span>
                            <span>
                                -{Number(order.discount_amount).toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between text-base font-semibold">
                            <span>Total</span>
                            <span>{Number(order.total).toFixed(2)}</span>
                        </div>
                    </div>
                </div>

                <div className="flex gap-2">
                    <Link
                        href={index.url()}
                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Back to Purchase Orders
                    </Link>
                </div>
            </div>
        </>
    );
}

PurchaseOrderShowPage.layout = {
    breadcrumbs: [
        {
            title: 'Purchase orders',
            href: '/purchase-orders',
        },
        {
            title: 'View',
            href: '/purchase-orders',
        },
    ],
};
