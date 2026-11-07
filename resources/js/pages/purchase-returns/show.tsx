import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { post, edit, index } from '@/routes/purchase-returns';


interface ReturnItem {
    id: string;
    product: { id: string; name: string } | null;
    quantity: string;
    unit_cost: string;
    tax_rate: string;
    discount_amount: string;
    line_total: string;
}

interface PurchaseReturnRecord {
    id: string;
    reference_no: string;
    status: string;
    return_date: string;
    reason: string;
    notes: string | null;
    subtotal: string;
    tax_amount: string;
    discount_amount: string;
    total: string;
    branch: { name: string } | null;
    supplier: { name: string } | null;
    items: ReturnItem[];
}

interface Props {
    return: PurchaseReturnRecord;
}

export default function PurchaseReturnShowPage({
    return: purchaseReturn,
}: Props) {
    return (
        <>
            <Head title={purchaseReturn.reference_no} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={purchaseReturn.reference_no}
                    description={`Supplier ${purchaseReturn.supplier?.name ?? '-'} - ${purchaseReturn.branch?.name ?? '-'}`}
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">
                            Returned Items
                        </h2>
                        <div className="flex items-center gap-2">
                            <span
                                className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                    purchaseReturn.status === 'posted'
                                        ? 'bg-success/10 text-forest dark:bg-success/15 dark:text-success'
                                        : 'bg-warning/15 text-forest dark:bg-warning/15 dark:text-warning'
                                }`}
                            >
                                {purchaseReturn.status}
                            </span>
                            {purchaseReturn.status === 'draft' && (
                                <>
                                    <form
                                        action={post.url(purchaseReturn.id)}
                                        method="POST"
                                    >
                                        <button
                                            type="submit"
                                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                                        >
                                            Post
                                        </button>
                                    </form>
                                    <Link
                                        href={edit.url(purchaseReturn.id)}
                                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                                    >
                                        Edit
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="mt-4 divide-y">
                        {purchaseReturn.items.map((item) => (
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
                            <span>
                                {Number(purchaseReturn.subtotal).toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Tax</span>
                            <span>
                                {Number(purchaseReturn.tax_amount).toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                Discount
                            </span>
                            <span>
                                -
                                {Number(purchaseReturn.discount_amount).toFixed(
                                    2,
                                )}
                            </span>
                        </div>
                        <div className="flex justify-between text-base font-semibold">
                            <span>Total</span>
                            <span>
                                {Number(purchaseReturn.total).toFixed(2)}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <h2 className="text-sm font-semibold text-muted-foreground">
                        Reason
                    </h2>
                    <p className="mt-1 text-sm">{purchaseReturn.reason}</p>
                    {purchaseReturn.notes && (
                        <p className="mt-2 text-sm text-muted-foreground">
                            {purchaseReturn.notes}
                        </p>
                    )}
                </div>

                <div className="flex gap-2">
                    <Link
                        href={index.url()}
                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Back to Purchase Returns
                    </Link>
                </div>
            </div>
        </>
    );
}

PurchaseReturnShowPage.layout = {
    breadcrumbs: [
        {
            title: 'Purchase returns',
            href: '/purchase-returns',
        },
        {
            title: 'View',
            href: '/purchase-returns',
        },
    ],
};
