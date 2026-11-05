import { Head, Link, Form } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    destroy as removeCartItem,
    update as updateCartItem,
} from '@/actions/App/Http/Controllers/Web/StorefrontCartController';
import { show as showCheckout } from '@/actions/App/Http/Controllers/Web/StorefrontCheckoutController';
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
    line_total: number | null;
    image_url: string | null;
    available: boolean;
}

interface Props {
    store: Store;
    items: CartItem[];
}

function formatPrice(price: number | string, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(Number(price));
}

export default function StorefrontCartPage({ store, items }: Props) {
    const subtotal = items.reduce(
        (total, item) => total + (item.line_total ?? 0),
        0,
    );
    const allItemsAvailable = items.length > 0 && items.every((item) => item.available);

    return (
        <>
            <Head title={`Your cart · ${store.name}`} />

            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b border-border/70 bg-card">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-5 sm:px-8">
                        <Link
                            href={storefrontShow.url({ tenant: store.slug })}
                            className="font-semibold focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            {store.name}
                        </Link>
                        <span className="text-sm text-muted-foreground">Your cart</span>
                    </div>
                </header>

                <main className="mx-auto max-w-6xl px-5 py-8 sm:px-8 sm:py-12">
                    <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                                Before checkout
                            </p>
                            <h1 className="mt-2 text-3xl font-semibold tracking-tight text-balance">
                                Your cart
                            </h1>
                        </div>
                        <Link
                            href={storefrontShow.url({ tenant: store.slug })}
                            className="inline-flex min-h-11 items-center text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            Continue shopping
                        </Link>
                    </div>

                    {items.length === 0 ? (
                        <section className="rounded-2xl border border-border/70 bg-card px-6 py-16 text-center">
                            <h2 className="text-lg font-semibold">Your cart is empty</h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Browse the store and add something you like.
                            </p>
                            <Button asChild className="mt-6 min-h-11">
                                <Link href={storefrontShow.url({ tenant: store.slug })}>
                                    Browse products
                                </Link>
                            </Button>
                        </section>
                    ) : (
                        <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
                            <section aria-label="Cart items" className="divide-y divide-border/70">
                                {items.map((item) => (
                                    <article
                                        key={`${item.product_id}:${item.variant_id ?? ''}`}
                                        className="flex flex-col gap-4 py-6 first:pt-0 sm:flex-row"
                                    >
                                        <div className="size-24 shrink-0 overflow-hidden rounded-xl bg-muted">
                                            {item.image_url ? (
                                                <img
                                                    src={item.image_url}
                                                    alt=""
                                                    className="size-full object-cover"
                                                />
                                            ) : (
                                                <div
                                                    aria-hidden="true"
                                                    className="flex size-full items-center justify-center text-xs text-muted-foreground"
                                                >
                                                    Product
                                                </div>
                                            )}
                                        </div>

                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <h2 className="font-medium">{item.name}</h2>
                                                    {item.available && item.unit_price !== null && (
                                                        <p className="mt-1 text-sm text-muted-foreground tabular-nums">
                                                            {formatPrice(item.unit_price, store.currency)} each
                                                        </p>
                                                    )}
                                                </div>
                                                <p className="font-medium tabular-nums">
                                                    {item.line_total === null
                                                        ? '—'
                                                        : formatPrice(item.line_total, store.currency)}
                                                </p>
                                            </div>

                                            {!item.available && (
                                                <p className="mt-3 text-sm text-destructive">
                                                    This item is no longer available. Remove it to continue.
                                                </p>
                                            )}

                                            <div className="mt-4 flex flex-wrap items-center gap-3">
                                                {item.available && (
                                                    <Form
                                                        {...updateCartItem.form({
                                                            tenant: store.slug,
                                                            product: item.product_id,
                                                        })}
                                                        className="flex min-h-11 items-center gap-2"
                                                    >
                                                        {({ errors, processing }) => (
                                                            <>
                                                                <input
                                                                    type="hidden"
                                                                    name="variant_id"
                                                                    value={item.variant_id ?? ''}
                                                                />
                                                                <label
                                                                    htmlFor={`quantity-${item.product_id}-${item.variant_id ?? 'default'}`}
                                                                    className="text-sm text-muted-foreground"
                                                                >
                                                                    Qty
                                                                </label>
                                                                <input
                                                                    id={`quantity-${item.product_id}-${item.variant_id ?? 'default'}`}
                                                                    type="number"
                                                                    name="quantity"
                                                                    min="0.0001"
                                                                    max="9999"
                                                                    step="0.0001"
                                                                    defaultValue={item.quantity}
                                                                    className="h-11 w-24 rounded-md border border-input bg-background px-3 text-sm tabular-nums focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                                />
                                                                <Button
                                                                    type="submit"
                                                                    variant="outline"
                                                                    disabled={processing}
                                                                >
                                                                    Update
                                                                </Button>
                                                                {errors.quantity && (
                                                                    <span className="text-sm text-destructive">
                                                                        {errors.quantity}
                                                                    </span>
                                                                )}
                                                            </>
                                                        )}
                                                    </Form>
                                                )}
                                                <Form
                                                    {...removeCartItem.form({
                                                        tenant: store.slug,
                                                        product: item.product_id,
                                                    })}
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="variant_id"
                                                        value={item.variant_id ?? ''}
                                                    />
                                                    <Button type="submit" variant="ghost">
                                                        Remove
                                                    </Button>
                                                </Form>
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </section>

                            <aside className="rounded-2xl border border-border/70 bg-card p-5 sm:p-6">
                                <h2 className="text-base font-semibold">Order summary</h2>
                                <div className="mt-5 flex items-center justify-between gap-4 text-sm">
                                    <span className="text-muted-foreground">Products (tax applied)</span>
                                    <span className="font-medium tabular-nums">
                                        {formatPrice(subtotal, store.currency)}
                                    </span>
                                </div>
                                <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                    Tax follows current store settings. Any local delivery fee is shown at checkout.
                                </p>
                                {allItemsAvailable ? (
                                    <Button asChild className="mt-6 min-h-11 w-full">
                                        <Link href={showCheckout.url({ tenant: store.slug })}>
                                            Continue to checkout
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button disabled className="mt-6 min-h-11 w-full">
                                        Continue to checkout
                                    </Button>
                                )}
                            </aside>
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}
