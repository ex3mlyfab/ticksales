import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

interface ProductCategory {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    image_path: string | null;
    sort_order: number;
    is_active: boolean;
    parent_id: string | null;
}

interface Props {
    categories: {
        data: ProductCategory[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function ProductCategoryIndexPage({ categories }: Props) {
    return (
        <>
            <Head title="Product categories" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Product categories"
                    description="Manage your product categories."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <h2 className="text-lg font-semibold">Categories</h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Browse and manage all product categories for your tenant.
                    </p>

                    <div className="mt-4 divide-y">
                        {categories.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No product categories found.
                            </div>
                        ) : (
                            categories.data.map((category) => (
                                <div
                                    key={category.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">{category.name}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {category.slug}
                                        </p>
                                    </div>
                                    <span
                                        className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                            category.is_active
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-gray-100 text-gray-800'
                                        }`}
                                    >
                                        {category.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

ProductCategoryIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Product categories',
            href: '/product-categories',
        },
    ],
};
