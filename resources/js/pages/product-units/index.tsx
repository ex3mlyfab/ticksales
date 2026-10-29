import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface ProductUnit {
    id: string;
    name: string;
    abbreviation: string | null;
}

interface Props {
    units: {
        data: ProductUnit[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function ProductUnitIndexPage({ units }: Props) {
    return (
        <>
            <Head title="Product units" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Product units"
                    description="Manage your product units."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Units</h2>
                        <Link
                            href={route('product-units.create')}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Create Unit
                        </Link>
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Browse and manage all product units for your tenant.
                    </p>

                    <div className="mt-4 divide-y">
                        {units.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No product units found.
                            </div>
                        ) : (
                            units.data.map((unit) => (
                                <div
                                    key={unit.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">{unit.name}</p>
                                        {unit.abbreviation && (
                                            <p className="text-sm text-muted-foreground">
                                                {unit.abbreviation}
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex space-x-2">
                                        <Link
                                            href={route('product-units.edit', unit.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            Edit
                                        </Link>
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                e.currentTarget.submit();
                                            }}
                                            action={route('product-units.destroy', unit.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-red-500 hover:text-red-700"
                                                onClick="return confirm('Are you sure you want to delete this unit?')"
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

ProductUnitIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Product units',
            href: '/product-units',
        },
    ],
};