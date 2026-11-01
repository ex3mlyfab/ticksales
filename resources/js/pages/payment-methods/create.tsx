import { useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Checkbox, Input, Label } from '@/components/ui';
import Link from '@/components/link';
import { store, index } from '@/routes/payment-methods';


export default function PaymentMethodCreatePage() {
    const form = useForm({
        name: '',
        type: 'cash',
        is_active: true,
        sort_order: 0,
    });

    const types = [
        { value: 'cash', label: 'Cash' },
        { value: 'card', label: 'Card' },
        { value: 'transfer', label: 'Bank Transfer' },
        { value: 'mobile_money', label: 'Mobile Money' },
        { value: 'credit', label: 'Credit' },
        { value: 'other', label: 'Other' },
    ];

    return (
        <>
            <Head title="Create payment method" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Create payment method"
                    description="Add a new payment method for your registers."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <form onSubmit={(e) => { e.preventDefault(); form.post(store.url()); }} className="space-y-6">
                        {(() => { const { processing, errors } = form; return (<>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            value={form.data.name}
                                            onChange={(e) =>
                                                form.setData(
                                                    'name',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="e.g. Cash, Visa, MTN MoMo"
                                            required
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-red-500">
                                                {errors.name}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="type">Type *</Label>
                                        <select
                                            id="type"
                                            name="type"
                                            value={form.data.type}
                                            onChange={(e) =>
                                                form.setData(
                                                    'type',
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            {types.map((t) => (
                                                <option
                                                    key={t.value}
                                                    value={t.value}
                                                >
                                                    {t.label}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.type && (
                                            <p className="text-sm text-red-500">
                                                {errors.type}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="sort_order">
                                        Sort Order
                                    </Label>
                                    <Input
                                        id="sort_order"
                                        name="sort_order"
                                        type="number"
                                        min="0"
                                        value={form.data.sort_order}
                                        onChange={(e) =>
                                            form.setData(
                                                'sort_order',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                    {errors.sort_order && (
                                        <p className="text-sm text-red-500">
                                            {errors.sort_order}
                                        </p>
                                    )}
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        name="is_active"
                                        checked={form.data.is_active}
                                        onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                                    />
                                    <Label
                                        htmlFor="is_active"
                                        className="text-sm"
                                    >
                                        Active
                                    </Label>
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Create Method
                                    </Button>
                                    <Link
                                        href={index.url()}
                                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                                    >
                                        Cancel
                                    </Link>
                                </div>
                            </>); })()}
                    </form>
                </div>
            </div>
        </>
    );
}
