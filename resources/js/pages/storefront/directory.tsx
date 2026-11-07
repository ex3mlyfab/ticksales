import { Head, Link } from '@inertiajs/react';
import { directory as storesDirectory, marketplace } from '@/routes/storefront';
import { show as storefrontShow } from '@/routes/storefront';

interface Store {
    name: string;
    slug: string;
    currency: string;
    logo_url: string | null;
    description: string | null;
    pickup_enabled: boolean;
    delivery_enabled: boolean;
    product_count: number;
    featured_products: Array<{
        id: string;
        name: string;
        selling_price: string;
        image_url: string | null;
    }>;
}

interface Category {
    id: string;
    name: string;
}

interface Props {
    stores: {
        data: Store[];
        current_page: number;
        last_page: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    categories: Category[];
    filters: {
        q: string | null;
        category: string | null;
        fulfillment: string;
    };
}

function formatPrice(price: string, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(Number(price));
}

export default function StoreDirectory({
    stores,
    categories,
    filters,
}: Props) {
    return (
        <>
            <Head title="Stores Â· Sokora Marketplace" />

            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b border-border bg-card/95">
                    <div className="mx-auto flex max-w-[1280px] items-center justify-between gap-4 px-5 py-4 sm:px-8">
                        <Link href={storesDirectory.url()} className="text-sm font-semibold tracking-[0.16em] uppercase">
                            Sokora
                        </Link>
                        <nav aria-label="Marketplace navigation" className="flex items-center gap-1">
                            <Link href={storesDirectory.url()} className="min-h-11 rounded-md px-3 py-2 text-sm font-medium">
                                Stores
                            </Link>
                            <Link href={marketplace.url()} className="min-h-11 rounded-md px-3 py-2 text-sm text-muted-foreground hover:text-foreground">
                                Products
                            </Link>
                        </nav>
                    </div>
                </header>

                <main className="mx-auto max-w-[1280px] px-5 py-10 sm:px-8 sm:py-14">
                    <section className="grid gap-8 border-b border-border pb-10 lg:grid-cols-[1fr_340px] lg:items-end">
                        <div>
                            <p className="text-xs font-semibold tracking-[0.22em] text-forest uppercase">Local market index</p>
                            <h1 className="mt-3 max-w-3xl text-4xl font-semibold tracking-[-0.04em] text-balance sm:text-6xl">
                                Find a store, discover its shelves.
                            </h1>
                        </div>
                        <p className="max-w-sm text-sm leading-6 text-muted-foreground lg:justify-self-end">
                            Browse independently run storefronts, then open a shop to see everything it currently has on offer.
                        </p>
                    </section>

                    <form action={storesDirectory.url()} method="get" className="grid gap-3 border-b border-border py-5 sm:grid-cols-[minmax(0,1fr)_180px_180px_auto]">
                        <label className="sr-only" htmlFor="store-search">Search stores</label>
                        <input
                            id="store-search"
                            name="q"
                            defaultValue={filters.q ?? ''}
                            placeholder="Search stores"
                            className="min-h-11 rounded-md border border-input bg-card px-4 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/15"
                        />
                        <label className="sr-only" htmlFor="store-category">Category</label>
                        <select id="store-category" name="category" defaultValue={filters.category ?? ''} className="min-h-11 rounded-md border border-input bg-card px-3 text-sm">
                            <option value="">All categories</option>
                            {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
                        </select>
                        <label className="sr-only" htmlFor="store-fulfillment">Fulfillment</label>
                        <select id="store-fulfillment" name="fulfillment" defaultValue={filters.fulfillment} className="min-h-11 rounded-md border border-input bg-card px-3 text-sm">
                            <option value="all">Any fulfillment</option>
                            <option value="pickup">Pickup</option>
                            <option value="delivery">Local delivery</option>
                        </select>
                        <button type="submit" className="min-h-11 rounded-md bg-forest px-5 text-sm font-medium text-primary-foreground transition-transform active:scale-[0.97]">
                            Apply
                        </button>
                    </form>

                    <div className="flex items-center justify-between gap-4 py-5">
                        <p className="text-sm text-foreground/55">
                            {stores.total} {stores.total === 1 ? 'store' : 'stores'} in the market
                        </p>
                        {(filters.q || filters.category || filters.fulfillment !== 'all') && (
                            <Link href={storesDirectory.url()} className="text-sm font-medium underline underline-offset-4">
                                Clear filters
                            </Link>
                        )}
                    </div>

                    {stores.data.length === 0 ? (
                        <section className="rounded-lg border border-dashed border-border px-6 py-20 text-center">
                            <h2 className="text-lg font-semibold">No storefronts match this window</h2>
                            <p className="mt-2 text-sm text-foreground/55">Try a broader search or clear the filters.</p>
                        </section>
                    ) : (
                        <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            {stores.data.map((store) => (
                                <article key={store.slug} className="group overflow-hidden rounded-lg border border-border bg-card transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-lg">
                                    <Link href={storefrontShow.url({ tenant: store.slug })} className="block focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none">
                                        <div className="flex h-28 items-end justify-between gap-4 border-b border-border px-5 py-4 [background:linear-gradient(145deg,var(--muted)_0%,var(--card)_72%)]">
                                            <div className="flex items-center gap-3">
                                                {store.logo_url ? (
                                                    <img src={store.logo_url} alt="" className="size-12 rounded-md object-cover" />
                                                ) : (
                                                    <div className="flex size-12 items-center justify-center rounded-md border border-border bg-card text-lg font-semibold">
                                                        {store.name.slice(0, 1).toUpperCase()}
                                                    </div>
                                                )}
                                                <div>
                                                    <h2 className="font-semibold group-hover:underline">{store.name}</h2>
                                                    <p className="mt-1 text-xs text-foreground/50">{store.product_count} products</p>
                                                </div>
                                            </div>
                                            <span className="rounded-sm bg-forest px-2 py-1 text-[10px] font-semibold tracking-[0.12em] text-white uppercase">Open</span>
                                        </div>
                                        {store.featured_products.length > 0 && (
                                            <div className="grid grid-cols-3 gap-px bg-border">
                                                {store.featured_products.map((product) => (
                                                    <div key={product.id} className="min-w-0 bg-card p-3">
                                                        <div className="aspect-[4/3] overflow-hidden rounded-sm bg-muted">
                                                            {product.image_url ? (
                                                                <img src={product.image_url} alt="" loading="lazy" className="size-full object-cover" />
                                                            ) : null}
                                                        </div>
                                                        <p className="mt-2 truncate text-xs font-medium">{product.name}</p>
                                                        <p className="mt-1 text-xs tabular-nums text-forest">{formatPrice(product.selling_price, store.currency)}</p>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                        <div className="px-5 py-5">
                                            {store.description && <p className="line-clamp-2 text-sm leading-6 text-muted-foreground">{store.description}</p>}
                                            <p className="mt-4 text-xs font-medium tracking-[0.08em] text-foreground/45 uppercase">
                                                {[store.pickup_enabled ? 'Pickup' : null, store.delivery_enabled ? 'Local delivery' : null].filter(Boolean).join('  Â·  ') || 'Contact store'}
                                            </p>
                                        </div>
                                    </Link>
                                </article>
                            ))}
                        </div>
                    )}

                    {stores.last_page > 1 && (
                        <nav aria-label="Store pages" className="mt-12 flex items-center justify-between gap-4 border-t border-border pt-5">
                            <p className="text-sm text-foreground/55">Page {stores.current_page} of {stores.last_page}</p>
                            <div className="flex gap-2">
                                {stores.links.map((link, index) => link.url ? (
                                    <Link key={`${link.label}-${index}`} href={link.url} aria-current={link.active ? 'page' : undefined} className={`min-h-11 rounded-md px-3 py-2 text-sm ${link.active ? 'bg-forest text-white' : 'text-foreground/60 hover:bg-accent'}`}>
                                        {link.label.replace('&laquo;', 'â€¹').replace('&raquo;', 'â€º')}
                                    </Link>
                                ) : (
                                    <span key={`${link.label}-${index}`} className="px-3 py-2 text-sm text-foreground/25">{link.label.replace('&laquo;', 'â€¹').replace('&raquo;', 'â€º')}</span>
                                ))}
                            </div>
                        </nav>
                    )}
                </main>
            </div>
        </>
    );
}

