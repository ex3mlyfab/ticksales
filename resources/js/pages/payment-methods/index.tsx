import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { create, edit, destroy } from '@/routes/payment-methods';


interface PaymentMethod {
    id: string;
    name: string;
    type: string;
    is_active: boolean;
    sort_order: number;
}

interface Props {
    methods: {
        data: PaymentMethod[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function PaymentMethodIndexPage({ methods }: Props) {
    return (
        <>
            <Head title="Payment methods" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Payment methods"
                    description="Configure payment types accepted at your registers."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">
                            Payment Methods
                        </h2>
                        <Link
                            href={create.url()}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            New Payment Method
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {methods.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No payment methods found.
                            </div>
                        ) : (
                            methods.data.map((method) => (
                                <div
                                    key={method.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {method.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Type: {method.type}
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                method.is_active
                                                    ? 'bg-success/10 text-forest dark:bg-success/15 dark:text-success'
                                                    : 'bg-secondary text-secondary-foreground'
                                            }`}
                                        >
                                            {method.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </span>
                                        <Link
                                            href={edit.url(method.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            Edit
                                        </Link>
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                e.currentTarget.submit();
                                            }}
                                            action={destroy.url(method.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-destructive hover:text-destructive"
                                                onClick={(e) => { if (!confirm('Are you sure you want to delete this payment method?')) e.preventDefault(); }}
                                            >
                                                Delete
                                            </button>
                                            <input
                                                type="hidden"
                                                name="_method"
                                                value="DELETE"
                                            />
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

PaymentMethodIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Payment methods',
            href: '/payment-methods',
        },
    ],
};
