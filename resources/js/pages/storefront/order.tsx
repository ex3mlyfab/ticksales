import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { show as storefrontShow } from '@/routes/storefront';

interface Store {
    name: string;
    slug: string;
    currency: string;
}

interface OrderItem {
    name: string;
    quantity: string;
    unit_price: string;
    line_total: string;
}

interface Order {
    id: string;
    reference_no: string;
    status: string;
    payment_status: string;
    payment_method: string;
    fulfillment_method: string;
    carrier_name: string | null;
    tracking_number: string | null;
    subtotal: string;
    tax_amount: string;
    delivery_fee: string;
    total_amount: string;
    branch_name: string;
    items: OrderItem[];
    payment_instructions: string | null;
}

interface Props {
    store: Store;
    order: Order;
}

function formatPrice(price: number | string, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(Number(price));
}

function statusLabel(status: string): string {
    return status.replaceAll('_', ' ');
}

export default function StorefrontOrderPage({ store, order }: Props) {
    return (
        <>
            <Head title={`Order ${order.reference_no} · ${store.name}`} />

            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b border-border/70 bg-card">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-5 sm:px-8">
                        <Link
                            href={storefrontShow.url({ tenant: store.slug })}
                            className="font-semibold focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            {store.name}
                        </Link>
                        <span className="text-sm text-muted-foreground">Order status</span>
                    </div>
                </header>

                <main className="mx-auto max-w-3xl px-5 py-8 sm:px-8 sm:py-12">
                    <section className="rounded-2xl border border-border/70 bg-card p-6 sm:p-8">
                        <p className="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                            Order received
                        </p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-balance">
                            Thank you for your order
                        </h1>
                        <p className="mt-3 text-sm leading-6 text-muted-foreground">
                            Keep your reference handy if you contact {store.name}.
                        </p>

                        <div className="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl bg-muted/60 px-4 py-4">
                            <div>
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Order reference
                                </p>
                                <p className="mt-1 font-mono text-lg font-semibold">{order.reference_no}</p>
                            </div>
                            <span className="rounded-full bg-background px-3 py-1.5 text-sm font-medium capitalize">
                                {statusLabel(order.status)}
                            </span>
                        </div>

                        <dl className="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground">Payment</dt>
                                <dd className="mt-1 font-medium capitalize">
                                    {statusLabel(order.payment_status)} · {statusLabel(order.payment_method)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Fulfillment</dt>
                                <dd className="mt-1 font-medium capitalize">
                                    {statusLabel(order.fulfillment_method)} · {order.branch_name}
                                </dd>
                            </div>
                        </dl>

                        {order.fulfillment_method === 'local_delivery'
                            && (order.carrier_name || order.tracking_number) && (
                            <div className="mt-6 rounded-xl bg-muted/60 px-4 py-4 text-sm">
                                <h2 className="font-semibold">Delivery tracking</h2>
                                <dl className="mt-2 space-y-1">
                                    {order.carrier_name && (
                                        <div className="flex gap-2">
                                            <dt className="text-muted-foreground">Carrier:</dt>
                                            <dd className="font-medium">{order.carrier_name}</dd>
                                        </div>
                                    )}
                                    {order.tracking_number && (
                                        <div className="flex gap-2">
                                            <dt className="text-muted-foreground">Tracking number:</dt>
                                            <dd className="font-mono font-medium">{order.tracking_number}</dd>
                                        </div>
                                    )}
                                </dl>
                            </div>
                        )}

                        {order.payment_instructions && (
                            <div className="mt-6 rounded-xl bg-muted/60 px-4 py-4">
                                <h2 className="text-sm font-semibold">Bank transfer instructions</h2>
                                <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">
                                    {order.payment_instructions}
                                </p>
                                <p className="mt-3 text-sm font-medium">
                                    The order is not marked paid until store staff confirms receipt.
                                </p>
                            </div>
                        )}

                        <section aria-labelledby="order-items-heading" className="mt-8 border-t border-border/70 pt-6">
                            <h2 id="order-items-heading" className="text-base font-semibold">
                                Items
                            </h2>
                            <ul className="mt-3 divide-y divide-border/70">
                                {order.items.map((item, index) => (
                                    <li key={`${item.name}-${index}`} className="flex justify-between gap-4 py-3 text-sm">
                                        <span className="text-muted-foreground">
                                            {item.name} <span className="text-foreground">× {item.quantity}</span>
                                        </span>
                                        <span className="shrink-0 font-medium tabular-nums">
                                            {formatPrice(item.line_total, store.currency)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </section>

                        <dl className="mt-4 space-y-3 border-t border-border/70 pt-4 text-sm">
                            <div className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">Products</dt>
                                <dd className="font-medium tabular-nums">
                                    {formatPrice(order.subtotal, store.currency)}
                                </dd>
                            </div>
                            {Number(order.tax_amount) > 0 && (
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">Tax</dt>
                                    <dd className="font-medium tabular-nums">
                                        {formatPrice(order.tax_amount, store.currency)}
                                    </dd>
                                </div>
                            )}
                            {Number(order.delivery_fee) > 0 && (
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">Local delivery</dt>
                                    <dd className="font-medium tabular-nums">
                                        {formatPrice(order.delivery_fee, store.currency)}
                                    </dd>
                                </div>
                            )}
                            <div className="flex justify-between gap-4 pt-1 text-base">
                                <dt className="font-semibold">Total</dt>
                                <dd className="font-semibold tabular-nums">
                                    {formatPrice(order.total_amount, store.currency)}
                                </dd>
                            </div>
                        </dl>

                        <Button asChild variant="outline" className="mt-8 min-h-11">
                            <Link href={storefrontShow.url({ tenant: store.slug })}>
                                Continue shopping
                            </Link>
                        </Button>
                    </section>
                </main>
            </div>
        </>
    );
}
