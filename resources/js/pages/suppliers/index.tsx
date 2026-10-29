import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface Supplier {
    id: string;
    name: string;
    email: string | null;
    phone: string | null;
    tax_number: string | null;
    outstanding_balance: number;
    is_active: boolean;
    purchase_orders_count: number;
}

interface Props {
    suppliers: {
        data: Supplier[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function SupplierIndexPage({ suppliers }: Props) {
    return (
        <>
            <Head title="Suppliers" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Suppliers"
                    description="Manage your supplier base."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Suppliers</h2>
                        <Link
                            href={route('suppliers.create')}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            New Supplier
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {suppliers.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No suppliers found.
                            </div>
                        ) : (
                            suppliers.data.map((supplier) => (
                                <div
                                    key={supplier.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">{supplier.name}</p>
                                        {supplier.tax_number && (
                                            <p className="text-sm text-muted-foreground">
                                                Tax No: {supplier.tax_number}
                                            </p>
                                        )}
                                        {supplier.email && (
                                            <p className="text-sm text-muted-foreground">
                                                {supplier.email}
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                supplier.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800'
                                            }`}
                                        >
                                            {supplier.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                        <Link
                                            href={route('suppliers.edit', supplier.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            Edit
                                        </Link>
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                e.currentTarget.submit();
                                            }}
                                            action={route('suppliers.destroy', supplier.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-red-500 hover:text-red-700"
                                                onClick="return confirm('Are you sure you want to delete this supplier?')"
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

SupplierIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Suppliers',
            href: '/suppliers',
        },
    ],
};