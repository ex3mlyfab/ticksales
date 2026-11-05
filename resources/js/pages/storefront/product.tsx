import { Form, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { add as addCartItem, show as showCart } from '@/actions/App/Http/Controllers/Web/StorefrontCartController';
import { show as storefrontShow } from '@/routes/storefront';

interface Store {
    name: string;
    slug: string;
    currency: string;
    logo_url: string | null;
}

interface ProductVariant {
    id: string;
    name: string;
    selling_price: string;
    image_url: string | null;
}

interface Product {
    id: string;
    name: string;
    description: string | null;
    selling_price: string;
    image_url: string | null;
    has_variants: boolean;
    variants: ProductVariant[];
}

interface Props {
    store: Store;
    product: Product;
}

function formatPrice(price: string, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(Number(price));
}

export default function StorefrontProductPage({ store, product }: Props) {
    return (
        <>
            <Head title={`${product.name} · ${store.name}`} />

            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b border-border/70 bg-card">
                    <div className="mx-auto flex max-w-7xl items-center gap-4 px-5 py-5 sm:px-8">
                        {store.logo_url ? (
                            <img
                                src={store.logo_url}
                                alt=""
                                className="size-12 rounded-lg object-cover"
                            />
                        ) : (
                            <div
                                aria-hidden="true"
                                className="flex size-12 items-center justify-center rounded-lg bg-muted text-lg font-semibold text-muted-foreground"
                            >
                                {store.name.slice(0, 1).toUpperCase()}
                            </div>
                        )}
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Online store
                            </p>
                            <p className="font-semibold">{store.name}</p>
                        </div>
                        <Link
                            href={showCart.url({ tenant: store.slug })}
                            className="ml-auto inline-flex min-h-11 items-center rounded-md px-4 text-sm font-medium hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            Cart
                        </Link>
                    </div>
                </header>

                <main className="mx-auto max-w-6xl px-5 py-8 sm:px-8 sm:py-12">
                    <Link
                        href={storefrontShow.url({ tenant: store.slug })}
                        className="inline-flex min-h-11 items-center text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        Back to all products
                    </Link>

                    <article className="mt-6 grid gap-8 md:grid-cols-2 md:gap-14">
                        <div className="aspect-[4/5] overflow-hidden rounded-2xl bg-muted">
                            {product.image_url ? (
                                <img
                                    src={product.image_url}
                                    alt={product.name}
                                    className="size-full object-cover"
                                />
                            ) : (
                                <div
                                    aria-hidden="true"
                                    className="flex size-full items-center justify-center text-sm text-muted-foreground"
                                >
                                    Product image
                                </div>
                            )}
                        </div>

                        <div className="flex flex-col items-start pt-2 md:pt-8">
                            <p className="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                                From {store.name}
                            </p>
                            <h1 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                {product.name}
                            </h1>
                            <p className="mt-4 text-xl font-medium tabular-nums">
                                {formatPrice(
                                    product.selling_price,
                                    store.currency,
                                )}
                            </p>

                            {product.description && (
                                <p className="mt-6 max-w-prose leading-7 text-muted-foreground">
                                    {product.description}
                                </p>
                            )}

                            {product.has_variants && product.variants.length > 0 && (
                                <section
                                    aria-labelledby="variant-heading"
                                    className="mt-8 w-full border-t border-border/70 pt-6"
                                >
                                    <h2
                                        id="variant-heading"
                                        className="text-sm font-semibold"
                                    >
                                        Available options
                                    </h2>
                                    <ul className="mt-3 divide-y divide-border/70">
                                        {product.variants.map((variant) => (
                                            <li
                                                key={variant.id}
                                                className="flex items-center justify-between gap-4 py-3 text-sm"
                                            >
                                                <span>{variant.name}</span>
                                                <span className="font-medium tabular-nums">
                                                    {formatPrice(
                                                        variant.selling_price,
                                                        store.currency,
                                                    )}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            )}

                            {product.has_variants && product.variants.length === 0 ? (
                                <p className="mt-8 rounded-lg bg-muted/60 px-4 py-3 text-sm leading-6 text-muted-foreground">
                                    No options are currently available for this product.
                                </p>
                            ) : (
                                <Form
                                    {...addCartItem.form({ tenant: store.slug })}
                                    className="mt-8 w-full border-t border-border/70 pt-6"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value={product.id}
                                            />
                                            {product.has_variants && (
                                                <label className="block space-y-2 text-sm font-medium">
                                                    Product option
                                                    <select
                                                        name="variant_id"
                                                        required
                                                        defaultValue=""
                                                        className="h-11 w-full rounded-md border border-input bg-background px-3 font-normal focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                    >
                                                        <option value="" disabled>
                                                            Choose an option
                                                        </option>
                                                        {product.variants.map((variant) => (
                                                            <option key={variant.id} value={variant.id}>
                                                                {variant.name} — {formatPrice(variant.selling_price, store.currency)}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    {errors.variant_id && (
                                                        <span className="block font-normal text-destructive">
                                                            {errors.variant_id}
                                                        </span>
                                                    )}
                                                </label>
                                            )}
                                            <label className="mt-4 block space-y-2 text-sm font-medium">
                                                Quantity
                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    min="0.0001"
                                                    max="9999"
                                                    step="0.0001"
                                                    defaultValue="1"
                                                    required
                                                    className="h-11 w-32 rounded-md border border-input bg-background px-3 font-normal tabular-nums focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                />
                                                {errors.quantity && (
                                                    <span className="block font-normal text-destructive">
                                                        {errors.quantity}
                                                    </span>
                                                )}
                                            </label>
                                            {errors.product_id && (
                                                <p role="alert" className="mt-3 text-sm text-destructive">
                                                    {errors.product_id}
                                                </p>
                                            )}
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="mt-5 min-h-11 w-full sm:w-auto"
                                            >
                                                {processing ? 'Adding…' : 'Add to cart'}
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            )}
                        </div>
                    </article>
                </main>
            </div>
        </>
    );
}
