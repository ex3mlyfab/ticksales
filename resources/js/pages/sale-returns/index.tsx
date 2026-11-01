import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { create, show } from '@/routes/sale-returns';


interface ReturnItem {
    id: string;
    product_name: string | null;
    quantity: number;
    unit_price: number;
    line_total: number;
}

interface SaleReturn {
    id: string;
    sale_reference: string | null;
    reference_no: string;
    customer_name: string | null;
    branch_name: string | null;
    refund_amount: number;
    refund_method_name: string | null;
    status: string;
    returned_by_name: string | null;
    returned_at: string;
    items: ReturnItem[];
}

interface Props {
    returns: {
        data: SaleReturn[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function SaleReturnIndexPage({ returns }: Props) {
    return (
        <>
            <Head title="Sale returns" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Sale returns"
                    description="Process and review customer refunds."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Sale Returns</h2>
                        <Link
                            href={create.url()}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            New Return
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {returns.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No sale returns found.
                            </div>
                        ) : (
                            returns.data.map((saleReturn) => (
                                <div
                                    key={saleReturn.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {saleReturn.reference_no} -{' '}
                                            {saleReturn.customer_name ??
                                                'Walk-in'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {saleReturn.sale_reference} •{' '}
                                            {saleReturn.branch_name ?? ''} •{' '}
                                            {saleReturn.items.length} item(s)
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Returned by{' '}
                                            {saleReturn.returned_by_name ?? '-'}{' '}
                                            at{' '}
                                            {new Date(
                                                saleReturn.returned_at,
                                            ).toLocaleString()}
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span className="font-medium">
                                            {Number(
                                                saleReturn.refund_amount,
                                            ).toFixed(2)}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            {saleReturn.refund_method_name ??
                                                '-'}
                                        </span>
                                        <span className="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                            {saleReturn.status}
                                        </span>
                                        <Link
                                            href={show.url(saleReturn.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            View
                                        </Link>
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

SaleReturnIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Sale returns',
            href: '/sale-returns',
        },
    ],
};
