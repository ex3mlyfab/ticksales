import { Head, Link } from '@inertiajs/react';
import { directory as storesDirectory, marketplace } from '@/routes/storefront';
import { show as productShow } from '@/routes/storefront/products';

interface Option { id: string; name: string; slug?: string }
interface Product {
    id: string;
    name: string;
    description: string | null;
    selling_price: string;
    image_url: string | null;
    category: Option | null;
    brand: Option | null;
    store: { name: string; slug: string; currency: string; logo_url: string | null };
}

interface Props {
    products: {
        data: Product[];
        current_page: number;
        last_page: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    categories: Option[];
    brands: Option[];
    stores: Option[];
    filters: {
        q: string | null;
        category: string | null;
        brand: string | null;
        store: string | null;
        min_price: string | number | null;
        max_price: string | number | null;
        sort: string;
    };
}

function formatPrice(price: string, currency: string): string {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(Number(price));
}

export default function Marketplace({ products, categories, brands, stores, filters }: Props) {
    const hasFilters = filters.q || filters.category || filters.brand || filters.store || filters.min_price !== null || filters.max_price !== null || filters.sort !== 'newest';

    return (
        <>
            <Head title="Marketplace Â· Sokora Products" />
            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b border-border bg-card/95">
                    <div className="mx-auto flex max-w-[1280px] items-center justify-between gap-4 px-5 py-4 sm:px-8">
                        <Link href={storesDirectory.url()} className="text-sm font-semibold tracking-[0.16em] uppercase">Sokora</Link>
                        <nav aria-label="Marketplace navigation" className="flex items-center gap-1">
                            <Link href={storesDirectory.url()} className="min-h-11 rounded-md px-3 py-2 text-sm text-muted-foreground hover:text-foreground">Stores</Link>
                            <Link href={marketplace.url()} className="min-h-11 rounded-md px-3 py-2 text-sm font-medium">Products</Link>
                        </nav>
                    </div>
                </header>

                <main className="mx-auto max-w-[1280px] px-5 py-10 sm:px-8 sm:py-14">
                    <section className="border-b border-border pb-8">
                        <p className="text-xs font-semibold tracking-[0.22em] text-forest uppercase">The market catalog</p>
                        <div className="mt-3 flex flex-wrap items-end justify-between gap-4">
                            <h1 className="max-w-4xl text-4xl font-semibold tracking-[-0.04em] text-balance sm:text-6xl">Browse every available product.</h1>
                            <p className="text-sm tabular-nums text-foreground/55">{products.total} items found</p>
                        </div>
                    </section>

                    <form action={marketplace.url()} method="get" className="grid gap-3 border-b border-border py-5 md:grid-cols-2 lg:grid-cols-[minmax(180px,1fr)_150px_150px_150px_90px_90px_150px_auto]">
                        <label className="sr-only" htmlFor="product-search">Search products</label>
                        <input id="product-search" name="q" defaultValue={filters.q ?? ''} placeholder="Search products" className="min-h-11 rounded-md border border-input bg-card px-4 text-sm outline-none focus:border-ring focus:ring-2 focus:ring-ring/15" />
                        <label className="sr-only" htmlFor="product-category">Category</label>
                        <select id="product-category" name="category" defaultValue={filters.category ?? ''} className="min-h-11 rounded-md border border-input bg-card px-3 text-sm">
                            <option value="">All categories</option>
                            {categories.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}
                        </select>
                        <label className="sr-only" htmlFor="product-brand">Brand</label>
                        <select id="product-brand" name="brand" defaultValue={filters.brand ?? ''} className="min-h-11 rounded-md border border-input bg-card px-3 text-sm">
                            <option value="">All brands</option>
                            {brands.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}
                        </select>
                        <label className="sr-only" htmlFor="product-store">Store</label>
                        <select id="product-store" name="store" defaultValue={filters.store ?? ''} className="min-h-11 rounded-md border border-input bg-card px-3 text-sm">
                            <option value="">All stores</option>
                            {stores.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}
                        </select>
                        <label className="sr-only" htmlFor="min-price">Minimum price</label>
                        <input id="min-price" name="min_price" type="number" min="0" step="0.01" defaultValue={filters.min_price ?? ''} placeholder="Min" className="min-h-11 rounded-md border border-input bg-card px-3 text-sm" />
                        <label className="sr-only" htmlFor="max-price">Maximum price</label>
                        <input id="max-price" name="max_price" type="number" min="0" step="0.01" defaultValue={filters.max_price ?? ''} placeholder="Max" className="min-h-11 rounded-md border border-input bg-card px-3 text-sm" />
                        <label className="sr-only" htmlFor="product-sort">Sort</label>
                        <select id="product-sort" name="sort" defaultValue={filters.sort} className="min-h-11 rounded-md border border-input bg-card px-3 text-sm">
                            <option value="newest">Newest</option>
                            <option value="name">Name</option>
                            <option value="price_low">Price: low</option>
                            <option value="price_high">Price: high</option>
                        </select>
                        <button type="submit" className="min-h-11 rounded-md bg-forest px-5 text-sm font-medium text-primary-foreground transition-transform active:scale-[0.97]">Apply</button>
                    </form>

                    <div className="flex min-h-16 items-center justify-between gap-4">
                        {hasFilters ? <p className="text-sm text-foreground/55">Filtered results</p> : <p className="text-sm text-foreground/55">All storefront products</p>}
                        {hasFilters && <Link href={marketplace.url()} className="text-sm font-medium underline underline-offset-4">Clear filters</Link>}
                    </div>

                    {products.data.length === 0 ? (
                        <section className="rounded-lg border border-dashed border-border px-6 py-20 text-center">
                            <h2 className="text-lg font-semibold">No products in this aisle</h2>
                            <p className="mt-2 text-sm text-foreground/55">Try widening your search or removing a filter.</p>
                        </section>
                    ) : (
                        <div className="grid grid-cols-1 gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {products.data.map((product) => (
                                <article key={product.id} className="group min-w-0">
                                    <Link href={productShow.url({ tenant: product.store.slug, product: product.id })} className="block rounded-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:outline-none">
                                        <div className="aspect-[4/5] overflow-hidden rounded-md bg-muted">
                                            {product.image_url ? (
                                                <img src={product.image_url} alt={product.name} loading="lazy" className="size-full object-cover transition-transform duration-200 group-hover:scale-[1.02]" />
                                            ) : (
                                                <div className="flex size-full items-center justify-center text-xs text-foreground/35">No image</div>
                                            )}
                                        </div>
                                        <div className="pt-4">
                                            <p className="truncate text-xs font-medium tracking-[0.08em] text-forest uppercase">{product.store.name}</p>
                                            <div className="mt-1 flex items-start justify-between gap-3">
                                                <h2 className="min-w-0 font-medium leading-6 group-hover:underline">{product.name}</h2>
                                                <p className="shrink-0 text-sm font-semibold tabular-nums">{formatPrice(product.selling_price, product.store.currency)}</p>
                                            </div>
                                            {(product.category || product.brand) && (
                                                <p className="mt-1 truncate text-xs text-foreground/45">{[product.brand?.name, product.category?.name].filter(Boolean).join(' Â· ')}</p>
                                            )}
                                        </div>
                                    </Link>
                                </article>
                            ))}
                        </div>
                    )}

                    {products.last_page > 1 && (
                        <nav aria-label="Product pages" className="mt-12 flex items-center justify-between gap-4 border-t border-border pt-5">
                            <p className="text-sm text-foreground/55">Page {products.current_page} of {products.last_page}</p>
                            <div className="flex gap-2">
                                {products.links.map((link, index) => link.url ? (
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

