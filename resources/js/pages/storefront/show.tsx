import { Head, Link } from '@inertiajs/react';
import { show as productShow } from '@/routes/storefront/products';

interface Store {
    name: string;
    slug: string;
    currency: string;
    logo_url: string | null;
}

interface Product {
    id: string;
    name: string;
    description: string | null;
    selling_price: string;
    image_url: string | null;
}

interface Props {
    store: Store;
    products: {
        data: Product[];
        current_page: number;
        last_page: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        total: number;
    };
}

function formatPrice(price: string, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(Number(price));
}

export default function StorefrontPage({ store, products }: Props) {
    return (
        <>
            <Head title={`${store.name} online store`} />

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

                <main className="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
                    <section className="mb-9 flex flex-wrap items-end justify-between gap-4">
                        <div className="space-y-2">
                            <p className="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                                The collection
                            </p>
                            <h1 className="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                Products from {store.name}
                            </h1>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {products.total}{' '}
                            {products.total === 1 ? 'product' : 'products'}
                        </p>
                    </section>

                    {products.data.length === 0 ? (
                        <section className="rounded-2xl border border-border/70 bg-card px-6 py-16 text-center">
                            <h2 className="text-lg font-semibold">
                                This store is getting ready
                            </h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                There are no products available to browse right
                                now.
                            </p>
                        </section>
                    ) : (
                        <div className="grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {products.data.map((product) => (
                                <article key={product.id} className="group">
                                    <Link
                                        href={productShow.url({
                                            tenant: store.slug,
                                            product: product.id,
                                        })}
                                        className="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:outline-none"
                                    >
                                        <div className="aspect-[4/5] overflow-hidden rounded-xl bg-muted">
                                            {product.image_url ? (
                                                <img
                                                    src={product.image_url}
                                                    alt={product.name}
                                                    loading="lazy"
                                                    className="size-full object-cover transition-transform duration-200 group-hover:scale-[1.02]"
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
                                        <div className="flex items-start justify-between gap-4 pt-4">
                                            <div className="min-w-0">
                                                <h2 className="truncate font-medium group-hover:underline">
                                                    {product.name}
                                                </h2>
                                                {product.description && (
                                                    <p className="mt-1 line-clamp-2 text-sm leading-6 text-muted-foreground">
                                                        {product.description}
                                                    </p>
                                                )}
                                            </div>
                                            <p className="shrink-0 text-sm font-medium tabular-nums">
                                                {formatPrice(
                                                    product.selling_price,
                                                    store.currency,
                                                )}
                                            </p>
                                        </div>
                                    </Link>
                                </article>
                            ))}
                        </div>
                    )}

                    {products.last_page > 1 && (
                        <nav
                            aria-label="Product pages"
                            className="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-border/70 pt-5"
                        >
                            <p className="text-sm text-muted-foreground">
                                Page {products.current_page} of{' '}
                                {products.last_page}
                            </p>
                            <div className="flex flex-wrap gap-2">
                                {products.links.map((link, index) =>
                                    link.url ? (
                                        <Link
                                            key={`${link.label}-${index}`}
                                            href={link.url}
                                            aria-current={
                                                link.active ? 'page' : undefined
                                            }
                                            className={`rounded-md px-3 py-2 text-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none ${
                                                link.active
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                            }`}
                                        >
                                            {link.label
                                                .replace('&laquo;', '‹')
                                                .replace('&raquo;', '›')}
                                        </Link>
                                    ) : (
                                        <span
                                            key={`${link.label}-${index}`}
                                            aria-disabled="true"
                                            className="rounded-md px-3 py-2 text-sm text-muted-foreground/50"
                                        >
                                            {link.label
                                                .replace('&laquo;', '‹')
                                                .replace('&raquo;', '›')}
                                        </span>
                                    ),
                                )}
                            </div>
                        </nav>
                    )}
                </main>
            </div>
        </>
    );
}
