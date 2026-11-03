import { Head, Link, router } from '@inertiajs/react';
import SubscriptionNavigation from '@/components/admin/subscription-navigation';
import { create, destroy, edit } from '@/routes/admin/plans';

interface Plan {
    id: string;
    name: string;
    display_name: string;
    description: string | null;
    price_monthly: string;
    max_branches: number;
    features: string[] | null;
    is_active: boolean;
    tenants_count: number;
    subscription_payments_count: number;
}

export default function PlansIndex({ plans }: { plans: Plan[] }) {
    return (
        <>
            <Head title="Subscription plans" />
            <div className="space-y-6 p-6">
                <SubscriptionNavigation />
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Subscription plans
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage pricing and the features available to each
                            plan.
                        </p>
                    </div>
                    <Link
                        href={create.url()}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                    >
                        Add plan
                    </Link>
                </header>
                <div className="overflow-x-auto rounded-xl border bg-card">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-muted/50 text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3">Plan</th>
                                <th className="px-4 py-3">Monthly price</th>
                                <th className="px-4 py-3">Branches</th>
                                <th className="px-4 py-3">Tenants</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {plans.map((plan) => (
                                <tr key={plan.id}>
                                    <td className="px-4 py-3">
                                        <div className="font-medium">
                                            {plan.display_name}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {plan.name}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        {plan.price_monthly}
                                    </td>
                                    <td className="px-4 py-3">
                                        {plan.max_branches}
                                    </td>
                                    <td className="px-4 py-3">
                                        {plan.tenants_count}
                                    </td>
                                    <td className="px-4 py-3">
                                        {plan.is_active ? 'Active' : 'Inactive'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-3">
                                            <Link
                                                href={edit.url(plan.id)}
                                                className="text-primary hover:underline"
                                            >
                                                Edit
                                            </Link>
                                            {plan.tenants_count === 0 &&
                                                plan.subscription_payments_count ===
                                                    0 && (
                                                    <button
                                                        type="button"
                                                        className="text-destructive hover:underline"
                                                        onClick={() => {
                                                            if (
                                                                window.confirm(
                                                                    `Delete the ${plan.display_name} plan?`,
                                                                )
                                                            ) {
                                                                router.delete(
                                                                    destroy.url(
                                                                        plan.id,
                                                                    ),
                                                                );
                                                            }
                                                        }}
                                                    >
                                                        Delete
                                                    </button>
                                                )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {plans.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No plans have been configured.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
