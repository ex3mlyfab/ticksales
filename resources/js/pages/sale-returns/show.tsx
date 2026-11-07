import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { index } from '@/routes/sale-returns';


interface ReturnItem {
    id: string;
    product_name: string | null;
    quantity: number;
    unit_price: number;
    discount_amount: number;
    tax_amount: number;
    line_total: number;
}

interface SaleReturn {
    id: string;
    sale_reference: string | null;
    reference_no: string;
    reason: string | null;
    refund_amount: number;
    refund_method_name: string | null;
    status: string;
    returned_by_name: string | null;
    returned_at: string;
    customer_name: string | null;
    branch_name: string | null;
    items: ReturnItem[];
}

interface Props {
    saleReturn: SaleReturn;
}

export default function SaleReturnShowPage({ saleReturn }: Props) {
    return (
        <>
            <Head title={`Return ${saleReturn.reference_no}`} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={`Return ${saleReturn.reference_no}`}
                    description={`Sale ${saleReturn.sale_reference ?? '-'}`}
                />

                <div className="grid grid-cols-2 gap-4">
                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <h2 className="text-sm font-semibold text-muted-foreground">
                            Customer
                        </h2>
                        <p className="mt-1 text-lg font-medium">
                            {saleReturn.customer_name ?? 'Walk-in'}
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <h2 className="text-sm font-semibold text-muted-foreground">
                            Refund amount
                        </h2>
                        <p className="mt-1 text-lg font-medium">
                            {Number(saleReturn.refund_amount).toFixed(2)}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            via {saleReturn.refund_method_name ?? '-'}
                        </p>
                    </div>
                </div>

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">
                            Returned Items
                        </h2>
                        <span className="rounded-full bg-success/10 px-2.5 py-0.5 text-xs font-medium text-forest dark:text-success">
                            {saleReturn.status}
                        </span>
                    </div>

                    <div className="mt-4 divide-y">
                        {saleReturn.items.map((item) => (
                            <div
                                key={item.id}
                                className="flex items-center justify-between py-3"
                            >
                                <div>
                                    <p className="font-medium">
                                        {item.product_name ?? 'Product'}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {Number(item.quantity).toFixed(2)} x{' '}
                                        {Number(item.unit_price).toFixed(2)}
                                    </p>
                                </div>
                                <span className="font-medium">
                                    {Number(item.line_total).toFixed(2)}
                                </span>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4 flex items-center justify-between border-t pt-4">
                        <span className="text-sm font-medium">
                            Total refund
                        </span>
                        <span className="text-lg font-semibold">
                            {Number(saleReturn.refund_amount).toFixed(2)}
                        </span>
                    </div>
                </div>

                {saleReturn.reason && (
                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <h2 className="text-sm font-semibold text-muted-foreground">
                            Reason
                        </h2>
                        <p className="mt-1 text-sm">{saleReturn.reason}</p>
                    </div>
                )}

                <div className="flex gap-2">
                    <Link
                        href={index.url()}
                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Back to Returns
                    </Link>
                </div>
            </div>
        </>
    );
}

SaleReturnShowPage.layout = {
    breadcrumbs: [
        {
            title: 'Sale returns',
            href: '/sale-returns',
        },
        {
            title: 'View return',
            href: '/sale-returns',
        },
    ],
};
