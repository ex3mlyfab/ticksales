import { useState } from 'react';
import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { store as placeOrder } from '@/actions/App/Http/Controllers/Web/StorefrontCheckoutController';
import { show as storefrontShow } from '@/routes/storefront';

interface Store {
    name: string;
    slug: string;
    currency: string;
}

interface CartItem {
    product_id: string;
    variant_id: string | null;
    quantity: number;
    name: string;
    unit_price: string | null;
    line_subtotal: number | null;
    tax_amount: number | null;
    line_total: number | null;
    tax_inclusive: boolean | null;
    image_url: string | null;
    available: boolean;
}

interface Props {
    store: Store;
    items: CartItem[];
    idempotencyKey: string;
    mainBranchReady: boolean;
    deliveryFee: string;
    bankTransferEnabled: boolean;
    bankTransferInstructions: string | null;
    pickupEnabled: boolean;
    deliveryEnabled: boolean;
    paystackEnabled: boolean;
}

function formatPrice(price: number | string, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(Number(price));
}

export default function StorefrontCheckoutPage({
    store,
    items,
    idempotencyKey,
    mainBranchReady,
    deliveryFee,
    bankTransferEnabled,
    bankTransferInstructions,
    pickupEnabled,
    deliveryEnabled,
    paystackEnabled,
}: Props) {
    const [fulfillmentMethod, setFulfillmentMethod] = useState(
        pickupEnabled ? 'pickup' : deliveryEnabled ? 'local_delivery' : '',
    );
    const [paymentMethod, setPaymentMethod] = useState('cash');
    const productsTotal = items.reduce(
        (total, item) => total + (item.line_total ?? 0),
        0,
    );
    const taxAmount = items.reduce(
        (total, item) => total + (item.tax_amount ?? 0),
        0,
    );
    const allItemsAvailable = items.length > 0 && items.every((item) => item.available);
    const canSubmit =
        mainBranchReady &&
        allItemsAvailable &&
        (pickupEnabled || deliveryEnabled);
    const displayedDeliveryFee = fulfillmentMethod === 'local_delivery'
        ? Number(deliveryFee)
        : 0;

    return (
        <>
            <Head title={`Checkout · ${store.name}`} />

            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b border-border/70 bg-card">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-5 sm:px-8">
                        <Link
                            href={storefrontShow.url({ tenant: store.slug })}
                            className="font-semibold focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            {store.name}
                        </Link>
                        <span className="text-sm text-muted-foreground">Secure checkout</span>
                    </div>
                </header>

                <main className="mx-auto max-w-6xl px-5 py-8 sm:px-8 sm:py-12">
                    <div className="mb-8">
                        <p className="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                            Your details
                        </p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-balance">
                            Checkout
                        </h1>
                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                            Choose an available payment method, then review your order details.
                        </p>
                    </div>

                    {!mainBranchReady && (
                        <p role="alert" className="mb-6 rounded-xl border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                            This store is not ready to accept online orders. Please contact the store.
                        </p>
                    )}

                    {!allItemsAvailable && items.length > 0 && (
                        <p role="alert" className="mb-6 rounded-xl border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                            One or more cart items are no longer available. Update your cart before placing the order.
                        </p>
                    )}

                    {items.length === 0 ? (
                        <section className="rounded-2xl border border-border/70 bg-card px-6 py-14 text-center">
                            <h2 className="text-lg font-semibold">Your cart is empty</h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Add a product before checking out.
                            </p>
                            <Button asChild className="mt-6 min-h-11">
                                <Link href={storefrontShow.url({ tenant: store.slug })}>
                                    Browse products
                                </Link>
                            </Button>
                        </section>
                    ) : (
                        <Form
                            {...placeOrder.form({ tenant: store.slug })}
                            className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="idempotency_key"
                                        value={idempotencyKey}
                                    />

                                    <div className="space-y-8">
                                        <section className="space-y-5">
                                            <h2 className="text-lg font-semibold">Contact details</h2>
                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <label className="space-y-2 text-sm font-medium">
                                                    Full name
                                                    <input
                                                        name="customer_name"
                                                        autoComplete="name"
                                                        required
                                                        maxLength={255}
                                                        className="h-11 w-full rounded-md border border-input bg-background px-3 font-normal focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                    />
                                                    {errors.customer_name && (
                                                        <span className="block font-normal text-destructive">{errors.customer_name}</span>
                                                    )}
                                                </label>
                                                <label className="space-y-2 text-sm font-medium">
                                                    Phone number
                                                    <input
                                                        name="customer_phone"
                                                        type="tel"
                                                        autoComplete="tel"
                                                        required
                                                        maxLength={50}
                                                        className="h-11 w-full rounded-md border border-input bg-background px-3 font-normal focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                    />
                                                    {errors.customer_phone && (
                                                        <span className="block font-normal text-destructive">{errors.customer_phone}</span>
                                                    )}
                                                </label>
                                                <label className="space-y-2 text-sm font-medium sm:col-span-2">
                                                    Email <span className="font-normal text-muted-foreground">· optional</span>
                                                    <input
                                                        name="customer_email"
                                                        type="email"
                                                        autoComplete="email"
                                                        maxLength={255}
                                                        className="h-11 w-full rounded-md border border-input bg-background px-3 font-normal focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                    />
                                                    {errors.customer_email && (
                                                        <span className="block font-normal text-destructive">{errors.customer_email}</span>
                                                    )}
                                                </label>
                                            </div>
                                        </section>

                                        <section className="space-y-5 border-t border-border/70 pt-6">
                                            <h2 className="text-lg font-semibold">How would you like to receive it?</h2>
                                            <label className="space-y-2 text-sm font-medium">
                                                Fulfillment
                                                <select
                                                    name="fulfillment_method"
                                                    value={fulfillmentMethod}
                                                    onChange={(event) => setFulfillmentMethod(event.target.value)}
                                                    className="h-11 w-full rounded-md border border-input bg-background px-3 font-normal focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:max-w-sm"
                                                >
                                                    {pickupEnabled && <option value="pickup">Pickup</option>}
                                                    {deliveryEnabled && (
                                                        <option value="local_delivery">Local delivery</option>
                                                    )}
                                                </select>
                                                {errors.fulfillment_method && (
                                                    <span className="block font-normal text-destructive">{errors.fulfillment_method}</span>
                                                )}
                                            </label>

                                            {fulfillmentMethod === 'local_delivery' && (
                                                <label className="block space-y-2 text-sm font-medium">
                                                    Delivery address
                                                    <textarea
                                                        name="delivery_address"
                                                        autoComplete="street-address"
                                                        required
                                                        maxLength={2000}
                                                        rows={3}
                                                        className="w-full rounded-md border border-input bg-background px-3 py-2 font-normal focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                    />
                                                    {errors.delivery_address && (
                                                        <span className="block font-normal text-destructive">{errors.delivery_address}</span>
                                                    )}
                                                </label>
                                            )}
                                        </section>

                                        <section className="space-y-5 border-t border-border/70 pt-6">
                                            <h2 className="text-lg font-semibold">Payment method</h2>
                                            <label className="space-y-2 text-sm font-medium">
                                                Payment method
                                                <select
                                                    name="payment_method"
                                                    value={paymentMethod}
                                                    onChange={(event) => setPaymentMethod(event.target.value)}
                                                    className="h-11 w-full rounded-md border border-input bg-background px-3 font-normal focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:max-w-sm"
                                                >
                                                    <option value="cash">Cash at handoff</option>
                                                    {bankTransferEnabled && (
                                                        <option value="bank_transfer">Bank transfer</option>
                                                    )}
                                                    {paystackEnabled && (
                                                        <option value="paystack">Paystack</option>
                                                    )}
                                                </select>
                                                {errors.payment_method && (
                                                    <span className="block font-normal text-destructive">{errors.payment_method}</span>
                                                )}
                                            </label>
                                            {paymentMethod === 'bank_transfer' && bankTransferInstructions && (
                                                <div className="rounded-xl bg-muted/60 px-4 py-4">
                                                    <p className="text-sm font-semibold">Transfer instructions</p>
                                                    <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-muted-foreground">
                                                        {bankTransferInstructions}
                                                    </p>
                                                </div>
                                            )}
                                        </section>
                                    </div>

                                    <aside className="rounded-2xl border border-border/70 bg-card p-5 sm:p-6">
                                        <h2 className="text-base font-semibold">Order summary</h2>
                                        <ul className="mt-4 divide-y divide-border/70">
                                            {items.map((item) => (
                                                <li
                                                    key={`${item.product_id}:${item.variant_id ?? ''}`}
                                                    className="flex justify-between gap-4 py-3 text-sm"
                                                >
                                                    <span className="min-w-0 text-muted-foreground">
                                                        {item.name} <span className="text-foreground">× {item.quantity}</span>
                                                    </span>
                                                    <span className="shrink-0 font-medium tabular-nums">
                                                        {item.line_total === null
                                                            ? '—'
                                                            : formatPrice(item.line_total, store.currency)}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                        <div className="mt-4 space-y-3 border-t border-border/70 pt-4 text-sm">
                                            <div className="flex justify-between gap-4">
                                                <span className="text-muted-foreground">Products (tax applied)</span>
                                                <span className="font-medium tabular-nums">
                                                    {formatPrice(productsTotal, store.currency)}
                                                </span>
                                            </div>
                                            <div className="flex justify-between gap-4">
                                                <span className="text-muted-foreground">Tax (included above)</span>
                                                <span className="font-medium tabular-nums">
                                                    {formatPrice(taxAmount, store.currency)}
                                                </span>
                                            </div>
                                            <div className="flex justify-between gap-4">
                                                <span className="text-muted-foreground">Local delivery</span>
                                                <span className="font-medium tabular-nums">
                                                    {formatPrice(displayedDeliveryFee, store.currency)}
                                                </span>
                                            </div>
                                            <div className="flex justify-between gap-4 border-t border-border/70 pt-3 font-semibold">
                                                <span>Total due</span>
                                                <span className="tabular-nums">
                                                    {formatPrice(productsTotal + displayedDeliveryFee, store.currency)}
                                                </span>
                                            </div>
                                            <p className="text-xs leading-5 text-muted-foreground">
                                                Tax follows the store’s current product and tenant settings. The final amount is confirmed on the order.
                                            </p>
                                        </div>

                                        {errors.items && (
                                            <p role="alert" className="mt-4 text-sm text-destructive">{errors.items}</p>
                                        )}
                                        {errors.checkout && (
                                            <p role="alert" className="mt-4 text-sm text-destructive">{errors.checkout}</p>
                                        )}

                                        <Button
                                            type="submit"
                                            disabled={!canSubmit || processing}
                                            className="mt-6 min-h-11 w-full"
                                        >
                                            {processing ? 'Placing order…' : 'Place order'}
                                        </Button>
                                        <p className="mt-3 text-center text-xs leading-5 text-muted-foreground">
                                            Your stock is reserved when the order is placed and held until store staff confirms payment or cancels it.
                                        </p>
                                    </aside>
                                </>
                            )}
                        </Form>
                    )}
                </main>
            </div>
        </>
    );
}
