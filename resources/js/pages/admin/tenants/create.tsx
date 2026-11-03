import { Head, Link, useForm } from '@inertiajs/react';
import SubscriptionNavigation from '@/components/admin/subscription-navigation';
import { Input, Label } from '@/components/ui';
import { index, store } from '@/routes/admin/tenants';

interface Plan {
    name: string;
    display_name: string;
    is_active: boolean;
}

export default function Create({ plans }: { plans: Plan[] }) {
    const form = useForm({ name: '', slug: '', email: '', plan: '' });

    return (
        <>
            <Head title="Create tenant" />
            <div className="max-w-3xl space-y-6 p-6">
                <SubscriptionNavigation />
                <div>
                    <h1 className="text-2xl font-semibold">Create tenant</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Assign an active plan now or leave the tenant without a
                        plan.
                    </p>
                </div>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(store.url());
                    }}
                    className="space-y-5 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Business name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                        />
                        {form.errors.name && (
                            <p className="text-sm text-red-600">
                                {form.errors.name}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="slug">Slug (optional)</Label>
                        <Input
                            id="slug"
                            value={form.data.slug}
                            onChange={(event) =>
                                form.setData('slug', event.target.value)
                            }
                        />
                        {form.errors.slug && (
                            <p className="text-sm text-red-600">
                                {form.errors.slug}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="email">Billing email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                        />
                        {form.errors.email && (
                            <p className="text-sm text-red-600">
                                {form.errors.email}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="plan">Initial subscription plan</Label>
                        <select
                            id="plan"
                            value={form.data.plan}
                            onChange={(event) =>
                                form.setData('plan', event.target.value)
                            }
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                            <option value="">No plan</option>
                            {plans.map((plan) => (
                                <option key={plan.name} value={plan.name}>
                                    {plan.display_name}
                                </option>
                            ))}
                        </select>
                        {form.errors.plan && (
                            <p className="text-sm text-red-600">
                                {form.errors.plan}
                            </p>
                        )}
                    </div>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                    >
                        {form.processing ? 'Creating…' : 'Create tenant'}
                    </button>
                    <Link
                        href={index.url()}
                        className="ml-3 text-sm text-primary hover:underline"
                    >
                        Cancel
                    </Link>
                </form>
            </div>
        </>
    );
}
