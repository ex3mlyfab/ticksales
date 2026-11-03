import { Head, Link } from '@inertiajs/react';
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

                            {product.variants.length > 0 && (
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

                            <p className="mt-8 rounded-lg bg-muted/60 px-4 py-3 text-sm leading-6 text-muted-foreground">
                                Online ordering is not enabled yet.
                            </p>
                        </div>
                    </article>
                </main>
            </div>
        </>
    );
}
