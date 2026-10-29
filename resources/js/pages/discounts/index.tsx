import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface Discount {
    id: string;
    name: string;
    type: string;
    value: number;
    min_purchase_amount: number | null;
    max_discount_amount: number | null;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
}

interface Props {
    discounts: {
        data: Discount[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function DiscountIndexPage({ discounts }: Props) {
    return (
        <>
            <Head title="Discounts" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Discounts"
                    description="Manage your discounts and promotional pricing."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Discounts</h2>
                        <Link
                            href={route('discounts.create')}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Create Discount
                        </Link>
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Browse and manage all discounts for your tenant.
                    </p>

                    <div className="mt-4 divide-y">
                        {discounts.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No discounts found.
                            </div>
                        ) : (
                            discounts.data.map((discount) => (
                                <div
                                    key={discount.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">{discount.name}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {discount.type} — {discount.value}%
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                discount.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800'
                                            }`}
                                        >
                                            {discount.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                        <Link
                                            href={route('discounts.edit', discount.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            Edit
                                        </Link>
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                e.currentTarget.submit();
                                            }}
                                            action={route('discounts.destroy', discount.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-red-500 hover:text-red-700"
                                                onClick="return confirm('Are you sure you want to delete this discount?')"
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

DiscountIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Discounts',
            href: '/discounts',
        },
    ],
};
