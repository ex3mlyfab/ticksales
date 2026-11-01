import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { index } from '@/routes/purchases';


interface PurchaseItem {
    id: string;
    product: { id: string; name: string } | null;
    quantity: string;
    unit_cost: string;
    tax_rate: string;
    discount_amount: string;
    line_total: string;
}

interface Purchase {
    id: string;
    reference_no: string;
    invoice_no: string | null;
    status: string;
    purchase_date: string;
    subtotal: string;
    tax_amount: string;
    discount_amount: string;
    total: string;
    paid_amount: string;
    notes: string | null;
    branch: { name: string } | null;
    supplier: { name: string } | null;
    purchaseOrder: { reference_no: string } | null;
    items: PurchaseItem[];
}

interface Props {
    purchase: Purchase;
}

export default function PurchaseShowPage({ purchase }: Props) {
    return (
        <>
            <Head title={purchase.reference_no} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={purchase.reference_no}
                    description={`Supplier ${purchase.supplier?.name ?? '-'} • ${purchase.branch?.name ?? '-'}`}
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Items</h2>
                        <span className="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                            {purchase.status}
                        </span>
                    </div>

                    <div className="mt-4 divide-y">
                        {purchase.items.map((item) => (
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
                            <span>{Number(purchase.subtotal).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Tax</span>
                            <span>
                                {Number(purchase.tax_amount).toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Discount
                            </span>
                            <span>
                                -{Number(purchase.discount_amount).toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between text-base font-semibold">
                            <span>Total</span>
                            <span>{Number(purchase.total).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Paid</span>
                            <span>
                                {Number(purchase.paid_amount).toFixed(2)}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="flex gap-2">
                    <Link
                        href={index.url()}
                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Back to Purchases
                    </Link>
                </div>
            </div>
        </>
    );
}

PurchaseShowPage.layout = {
    breadcrumbs: [
        {
            title: 'Purchases',
            href: '/purchases',
        },
        {
            title: 'View',
            href: '/purchases',
        },
    ],
};
