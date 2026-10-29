import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface Customer {
    id: string;
    name: string;
    email: string | null;
    phone: string | null;
    credit_limit: number;
    is_active: boolean;
    group_name: string | null;
}

interface Props {
    customers: {
        data: Customer[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function CustomerIndexPage({ customers }: Props) {
    return (
        <>
            <Head title="Customers" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Customers"
                    description="Manage your customer base."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Customers</h2>
                        <Link
                            href={route('customers.create')}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            New Customer
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {customers.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No customers found.
                            </div>
                        ) : (
                            customers.data.map((customer) => (
                                <div
                                    key={customer.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">{customer.name}</p>
                                        {customer.group_name && (
                                            <p className="text-sm text-muted-foreground">
                                                Group: {customer.group_name}
                                            </p>
                                        )}
                                        {customer.email && (
                                            <p className="text-sm text-muted-foreground">
                                                {customer.email}
                                            </p>
                                        )}
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                customer.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800'
                                            }`}
                                        >
                                            {customer.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                        <Link
                                            href={route('customers.edit', customer.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            Edit
                                        </Link>
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                e.currentTarget.submit();
                                            }}
                                            action={route('customers.destroy', customer.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-red-500 hover:text-red-700"
                                                onClick="return confirm('Are you sure you want to delete this customer?')"
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

CustomerIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Customers',
            href: '/customers',
        },
    ],
};