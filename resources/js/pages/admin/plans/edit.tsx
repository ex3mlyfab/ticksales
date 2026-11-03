import { Head, Link, useForm } from '@inertiajs/react';
import SubscriptionNavigation from '@/components/admin/subscription-navigation';
import { Input, Label, Textarea } from '@/components/ui';
import { index, update } from '@/routes/admin/plans';

interface Plan {
    id: string;
    name: string;
    display_name: string;
    description: string | null;
    price_monthly: string;
    max_branches: number;
    features_text: string;
    is_active: boolean;
}

export default function PlanEdit({ plan }: { plan: Plan }) {
    const form = useForm({
        display_name: plan.display_name,
        description: plan.description ?? '',
        price_monthly: plan.price_monthly,
        max_branches: String(plan.max_branches),
        features: plan.features_text,
        is_active: plan.is_active,
    });

    return (
        <>
            <Head title={`Edit ${plan.display_name} plan`} />
            <div className="max-w-3xl space-y-6 p-6">
                <SubscriptionNavigation />
                <div>
                    <h1 className="text-2xl font-semibold">
                        Edit {plan.display_name}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Plan key: {plan.name} (cannot be changed once created).
                    </p>
                </div>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put(update.url(plan.id));
                    }}
                    className="space-y-5 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="display_name">Display name</Label>
                        <Input
                            id="display_name"
                            value={form.data.display_name}
                            onChange={(event) =>
                                form.setData('display_name', event.target.value)
                            }
                        />
                        {form.errors.display_name && (
                            <p className="text-sm text-destructive">
                                {form.errors.display_name}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            rows={3}
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                        {form.errors.description && (
                            <p className="text-sm text-destructive">
                                {form.errors.description}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="price_monthly">Monthly price</Label>
                            <Input
                                id="price_monthly"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.price_monthly}
                                onChange={(event) =>
                                    form.setData(
                                        'price_monthly',
                                        event.target.value,
                                    )
                                }
                            />
                            {form.errors.price_monthly && (
                                <p className="text-sm text-destructive">
                                    {form.errors.price_monthly}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="max_branches">
                                Maximum branches
                            </Label>
                            <Input
                                id="max_branches"
                                type="number"
                                min="1"
                                step="1"
                                value={form.data.max_branches}
                                onChange={(event) =>
                                    form.setData(
                                        'max_branches',
                                        event.target.value,
                                    )
                                }
                            />
                            {form.errors.max_branches && (
                                <p className="text-sm text-destructive">
                                    {form.errors.max_branches}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="features">
                            Features (one per line)
                        </Label>
                        <Textarea
                            id="features"
                            rows={6}
                            value={form.data.features}
                            onChange={(event) =>
                                form.setData('features', event.target.value)
                            }
                        />
                        {form.errors.features && (
                            <p className="text-sm text-destructive">
                                {form.errors.features}
                            </p>
                        )}
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(event) =>
                                form.setData('is_active', event.target.checked)
                            }
                        />
                        Available for assignment
                    </label>
                    {form.errors.is_active && (
                        <p className="text-sm text-destructive">
                            {form.errors.is_active}
                        </p>
                    )}
                    <div className="flex gap-3">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            {form.processing ? 'Saving…' : 'Save plan'}
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
