import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface ProductBrand {
    id: string;
    name: string;
}

interface Props {
    brands: {
        data: ProductBrand[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function ProductBrandIndexPage({ brands }: Props) {
    return (
        <>
            <Head title="Product brands" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Product brands"
                    description="Manage your product brands."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Brands</h2>
                        <Link
                            href={route('product-brands.create')}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Create Brand
                        </Link>
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Browse and manage all product brands for your tenant.
                    </p>

                    <div className="mt-4 divide-y">
                        {brands.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No product brands found.
                            </div>
                        ) : (
                            brands.data.map((brand) => (
                                <div
                                    key={brand.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">{brand.name}</p>
                                    </div>
                                    <div className="flex space-x-2">
                                        <Link
                                            href={route('product-brands.edit', brand.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            Edit
                                        </Link>
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                e.currentTarget.submit();
                                            }}
                                            action={route('product-brands.destroy', brand.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-red-500 hover:text-red-700"
                                                onClick="return confirm('Are you sure you want to delete this brand?')"
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

ProductBrandIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Product brands',
            href: '/product-brands',
        },
    ],
};