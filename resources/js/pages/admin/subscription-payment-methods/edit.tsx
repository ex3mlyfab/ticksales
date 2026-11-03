import { Head, Link, useForm } from '@inertiajs/react';
import SubscriptionNavigation from '@/components/admin/subscription-navigation';
import { Input, Label, Textarea } from '@/components/ui';
import { index, update } from '@/routes/admin/subscription-payment-methods';

interface PaymentMethod {
    id: string;
    name: string;
    instructions: string;
    is_active: boolean;
    sort_order: number;
}

export default function PaymentMethodEdit({
    method,
}: {
    method: PaymentMethod;
}) {
    const form = useForm({
        name: method.name,
        instructions: method.instructions,
        is_active: method.is_active,
        sort_order: String(method.sort_order),
    });

    return (
        <>
            <Head title={`Edit ${method.name}`} />
            <div className="max-w-3xl space-y-6 p-6">
                <SubscriptionNavigation />
                <h1 className="text-2xl font-semibold">Edit {method.name}</h1>
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put(update.url(method.id));
                    }}
                    className="space-y-5 rounded-xl border bg-card p-6"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Method name</Label>
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
                        <Label htmlFor="instructions">
                            Payment instructions
                        </Label>
                        <Textarea
                            id="instructions"
                            rows={6}
                            value={form.data.instructions}
                            onChange={(event) =>
                                form.setData('instructions', event.target.value)
                            }
                        />
                        {form.errors.instructions && (
                            <p className="text-sm text-destructive">
                                {form.errors.instructions}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="sort_order">Display order</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min="0"
                            value={form.data.sort_order}
                            onChange={(event) =>
                                form.setData('sort_order', event.target.value)
                            }
                        />
                        {form.errors.sort_order && (
                            <p className="text-sm text-destructive">
                                {form.errors.sort_order}
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
                        Available for recording payments
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
                            {form.processing ? 'Saving…' : 'Save changes'}
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
