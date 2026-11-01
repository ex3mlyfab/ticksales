import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { index } from '@/routes/pos';


interface SaleItem {
    id: string;
    name: string;
    sku: string | null;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    discount_amount: number;
    total_amount: number;
}

interface SalePayment {
    id: string;
    amount: number;
    payment_method_name: string | null;
    reference: string | null;
}

interface Sale {
    id: string;
    reference_no: string;
    status: string;
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    total_amount: number;
    paid_amount: number;
    change_amount: number;
    outstanding_amount: number;
    note: string | null;
    sold_at: string;
    customer: { name: string } | null;
    branch: { name: string } | null;
    sold_by: { name: string } | null;
    items: SaleItem[];
    payments: SalePayment[];
}

interface Branding {
    business_name: string | null;
    tagline: string | null;
}

interface Props {
    sale: Sale;
    branding: Branding | null;
}

export default function ReceiptPage({ sale, branding }: Props) {
    return (
        <>
            <Head title="Receipt" />

            <div className="mx-auto max-w-md space-y-6">
                <Heading
                    variant="small"
                    title="Sale Receipt"
                    description="Print or download this receipt."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="text-center">
                        <h1 className="text-xl font-bold">
                            {branding?.business_name ?? 'TickSales POS'}
                        </h1>
                        {branding?.tagline && (
                            <p className="text-sm text-muted-foreground">
                                {branding.tagline}
                            </p>
                        )}
                        <p className="mt-2 text-sm">{sale.reference_no}</p>
                        <p className="text-xs text-muted-foreground">
                            {new Date(sale.sold_at).toLocaleString()}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            Sold by {sale.sold_by?.name ?? '—'} •{' '}
                            {sale.branch?.name ?? '—'}
                        </p>
                    </div>

                    <div className="mt-4 border-t pt-4">
                        {sale.items.map((item) => (
                            <div
                                key={item.id}
                                className="flex items-start justify-between py-1"
                            >
                                <div>
                                    <p className="text-sm font-medium">
                                        {item.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {Number(item.quantity).toFixed(4)} ×{' '}
                                        {Number(item.unit_price).toFixed(2)}
                                    </p>
                                </div>
                                <span className="text-sm">
                                    {Number(item.total_amount).toFixed(2)}
                                </span>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4 space-y-1 border-t pt-4 text-sm">
                        <div className="flex justify-between">
                            <span>Subtotal</span>
                            <span>{Number(sale.subtotal).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Discount</span>
                            <span>
                                {Number(sale.discount_amount).toFixed(2)}
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span>Tax</span>
                            <span>{Number(sale.tax_amount).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between border-t pt-1 text-base font-semibold">
                            <span>Total</span>
                            <span>{Number(sale.total_amount).toFixed(2)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Paid</span>
                            <span>{Number(sale.paid_amount).toFixed(2)}</span>
                        </div>
                        {Number(sale.change_amount) > 0 && (
                            <div className="flex justify-between">
                                <span>Change</span>
                                <span>
                                    {Number(sale.change_amount).toFixed(2)}
                                </span>
                            </div>
                        )}
                    </div>

                    <div className="mt-4 border-t pt-4">
                        <h3 className="text-xs font-semibold text-muted-foreground">
                            Payment Methods
                        </h3>
                        {sale.payments.map((payment) => (
                            <div
                                key={payment.id}
                                className="flex justify-between py-1 text-sm"
                            >
                                <span>{payment.payment_method_name}</span>
                                <span>{Number(payment.amount).toFixed(2)}</span>
                            </div>
                        ))}
                    </div>

                    {sale.note && (
                        <p className="mt-4 border-t pt-3 text-xs text-muted-foreground italic">
                            Note: {sale.note}
                        </p>
                    )}
                </div>

                <div className="flex justify-center gap-2">
                    <button
                        type="button"
                        onClick={() => window.print()}
                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Print Receipt
                    </button>
                    <Link
                        href={index.url()}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        New Sale
                    </Link>
                </div>
            </div>
        </>
    );
}

ReceiptPage.layout = {
    breadcrumbs: [
        { title: 'POS', href: '/pos' },
        { title: 'Receipt', href: '/pos/receipt' },
    ],
};
