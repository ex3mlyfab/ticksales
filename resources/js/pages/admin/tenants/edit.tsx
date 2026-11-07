import { Head, Link, useForm } from '@inertiajs/react';
import SubscriptionNavigation from '@/components/admin/subscription-navigation';
import { Input, Label } from '@/components/ui';
import { index, update } from '@/routes/admin/tenants';

interface Tenant {
    id: string;
    name: string;
    slug: string;
    email: string;
    plan: string | null;
}

interface Plan {
    name: string;
    display_name: string;
    is_active: boolean;
}

export default function Edit({
    tenant,
    plans,
}: {
    tenant: Tenant;
    plans: Plan[];
}) {
    const form = useForm({
        name: tenant.name,
        slug: tenant.slug,
        email: tenant.email,
        plan: tenant.plan ?? '',
    });

    return (
        <>
            <Head title={`Edit ${tenant.name}`} />
            <div className="max-w-3xl space-y-6 p-6">
                <SubscriptionNavigation />
                <h1 className="text-2xl font-semibold">Edit {tenant.name}</h1>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put(update.url(tenant.id));
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
                            <p className="text-sm text-destructive">
                                {form.errors.name}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="slug">Slug</Label>
                        <Input
                            id="slug"
                            value={form.data.slug}
                            onChange={(event) =>
                                form.setData('slug', event.target.value)
                            }
                        />
                        {form.errors.slug && (
                            <p className="text-sm text-destructive">
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
                            <p className="text-sm text-destructive">
                                {form.errors.email}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="plan">Subscription plan</Label>
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
                            <p className="text-sm text-destructive">
                                {form.errors.plan}
                            </p>
                        )}
                    </div>
                    <div className="flex gap-3">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            {form.processing ? 'Savingâ€¦' : 'Save changes'}
                        </button>
                        <Link
                            href={index.url()}
                            className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                        >
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </>
    );
}
