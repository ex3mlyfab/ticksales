import { Head, Link } from '@inertiajs/react';
import SubscriptionNavigation from '@/components/admin/subscription-navigation';
import { create, edit } from '@/routes/admin/subscription-payment-methods';

interface PaymentMethod {
    id: string;
    name: string;
    instructions: string;
    is_active: boolean;
    sort_order: number;
    subscription_payments_count: number;
}

export default function PaymentMethodsIndex({
    methods,
}: {
    methods: PaymentMethod[];
}) {
    return (
        <>
            <Head title="Subscription payment collection" />
            <div className="space-y-6 p-6">
                <SubscriptionNavigation />
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Payment collection
                        </h1>
                        <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                            Configure the offline payment options and
                            instructions admins use when collecting subscription
                            payments.
                        </p>
                    </div>
                    <Link
                        href={create.url()}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                    >
                        Add method
                    </Link>
                </header>
                <div className="divide-y rounded-xl border bg-card px-5">
                    {methods.map((method) => (
                        <div
                            key={method.id}
                            className="flex flex-wrap items-start justify-between gap-4 py-5"
                        >
                            <div className="max-w-3xl space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="font-medium">
                                        {method.name}
                                    </h2>
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-xs ${method.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-muted text-muted-foreground'}`}
                                    >
                                        {method.is_active
                                            ? 'Active'
                                            : 'Inactive'}
                                    </span>
                                </div>
                                <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                    {method.instructions}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Sort order {method.sort_order} ·{' '}
                                    {method.subscription_payments_count}{' '}
                                    recorded payments
                                </p>
                            </div>
                            <Link
                                href={edit.url(method.id)}
                                className="text-sm text-primary hover:underline"
                            >
                                Edit method
                            </Link>
                        </div>
                    ))}
                    {methods.length === 0 && (
                        <p className="py-8 text-sm text-muted-foreground">
                            No methods configured. Add the payment instructions
                            your team should use when collecting subscription
                            payments.
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}
