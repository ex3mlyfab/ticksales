import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface CustomerGroup {
    id: string;
    name: string;
    description: string | null;
    discount_rate: number;
    is_active: boolean;
    customer_count: number;
}

interface Props {
    groups: {
        data: CustomerGroup[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function CustomerGroupIndexPage({ groups }: Props) {
    return (
        <>
            <Head title="Customer groups" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Customer groups"
                    description="Manage customer groups with discount tiers."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Customer Groups</h2>
                        <Link
                            href={route('customer-groups.create')}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            New Customer Group
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {groups.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No customer groups found.
                            </div>
                        ) : (
                            groups.data.map((group) => (
                                <div
                                    key={group.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">{group.name}</p>
                                        <p className="text-sm text-muted-foreground">
                                            Discount: {group.discount_rate}% • {group.customer_count} customers
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                group.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800'
                                            }`}
                                        >
                                            {group.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                        {group.is_active && (
                                            <form
                                                action={route('customer-groups.destroy', group.id)}
                                                method="POST"
                                            >
                                                <button
                                                    type="submit"
                                                    className="text-sm text-red-500 hover:text-red-700"
                                                    onClick="return confirm('Are you sure you want to delete this customer group?')"
                                                >
                                                    Delete
                                                </button>
                                                <input type="hidden" name="_method" value="DELETE" />
                                            </form>
                                        )}
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

CustomerGroupIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Customer groups',
            href: '/customer-groups',
        },
    ],
};