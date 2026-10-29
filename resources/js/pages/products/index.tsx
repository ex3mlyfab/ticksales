import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface Product {
    id: string;
    name: string;
    sku: string | null;
    barcode: string | null;
    cost_price: number;
    selling_price: number;
    is_active: boolean;
    category_name: string | null;
    brand_name: string | null;
    unit_abbreviation: string | null;
    tax_rate_name: string | null;
}

interface Props {
    products: {
        data: Product[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function ProductIndexPage({ products }: Props) {
    return (
        <>
            <Head title="Products" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Products"
                    description="Manage your products and inventory."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Products</h2>
                        <Link
                            href={route('products.create')}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Create Product
                        </Link>
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Browse and manage all products for your tenant.
                    </p>

                    <div className="mt-4 divide-y">
                        {products.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No products found.
                            </div>
                        ) : (
                            products.data.map((product) => (
                                <div
                                    key={product.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">{product.name}</p>
                                        {product.sku && (
                                            <p className="text-sm text-muted-foreground">
                                                SKU: {product.sku}
                                            </p>
                                        )}
                                        {product.barcode && (
                                            <p className="text-sm text-muted-foreground">
                                                Barcode: {product.barcode}
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                product.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800'
                                            }`}
                                        >
                                            {product.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                        <Link
                                            href={route('products.edit', product.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            Edit
                                        </Link>
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                e.currentTarget.submit();
                                            }}
                                            action={route('products.destroy', product.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-red-500 hover:text-red-700"
                                                onClick="return confirm('Are you sure you want to delete this product?')"
                                            >
                                                Delete
                                            </button>
                                            <input type="hidden" name="_method" value="DELETE" />
                                        </form>
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

ProductIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Products',
            href: '/products',
        },
    ],
};