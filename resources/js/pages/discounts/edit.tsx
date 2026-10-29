import { Form, useForm } from '@inertiajs/react';
import { Checkbox, Input, Label, Select } from '@/components/ui';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

import { Button } from '@/components/ui/button';
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
    discount: Discount;
}

export default function DiscountEditPage({ discount }: Props) {
    const form = useForm({
        name: discount.name,
        type: discount.type,
        value: String(discount.value),
        min_purchase_amount: discount.min_purchase_amount ? String(discount.min_purchase_amount) : '',
        max_discount_amount: discount.max_discount_amount ? String(discount.max_discount_amount) : '',
        starts_at: discount.starts_at ? discount.starts_at.replace('T', ' ').substring(0, 16) : '',
        ends_at: discount.ends_at ? discount.ends_at.replace('T', ' ').substring(0, 16) : '',
        is_active: discount.is_active,
    });

    return (
        <>
            <Head title="Edit discount" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Edit discount"
                    description="Update the discount or promotional pricing rule."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        onSubmit={form.put(route('discounts.update', discount.id))}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        placeholder="e.g. 10% off electronics"
                                        required
                                    />
                                    {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="type">Type</Label>
                                    <Select
                                        id="type"
                                        name="type"
                                        value={form.data.type}
                                        onChange={(e) => form.setData('type', e.target.value)}
                                    >
                                        <option value="percentage">Percentage</option>
                                        <option value="fixed">Fixed Amount</option>
                                    </Select>
                                    {errors.type && <p className="text-sm text-red-500">{errors.type}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="value">Value</Label>
                                    <Input
                                        id="value"
                                        name="value"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={form.data.value}
                                        onChange={(e) => form.setData('value', e.target.value)}
                                        required
                                    />
                                    {errors.value && <p className="text-sm text-red-500">{errors.value}</p>}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="min_purchase_amount">Min Purchase Amount (optional)</Label>
                                        <Input
                                            id="min_purchase_amount"
                                            name="min_purchase_amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={form.data.min_purchase_amount}
                                            onChange={(e) => form.setData('min_purchase_amount', e.target.value)}
                                        />
                                        {errors.min_purchase_amount && (
                                            <p className="text-sm text-red-500">{errors.min_purchase_amount}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="max_discount_amount">Max Discount Amount (optional)</Label>
                                        <Input
                                            id="max_discount_amount"
                                            name="max_discount_amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={form.data.max_discount_amount}
                                            onChange={(e) => form.setData('max_discount_amount', e.target.value)}
                                        />
                                        {errors.max_discount_amount && (
                                            <p className="text-sm text-red-500">{errors.max_discount_amount}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="starts_at">Start Date (optional)</Label>
                                        <Input
                                            id="starts_at"
                                            name="starts_at"
                                            type="datetime-local"
                                            value={form.data.starts_at}
                                            onChange={(e) => form.setData('starts_at', e.target.value)}
                                        />
                                        {errors.starts_at && <p className="text-sm text-red-500">{errors.starts_at}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="ends_at">End Date (optional)</Label>
                                        <Input
                                            id="ends_at"
                                            name="ends_at"
                                            type="datetime-local"
                                            value={form.data.ends_at}
                                            onChange={(e) => form.setData('ends_at', e.target.value)}
                                        />
                                        {errors.ends_at && <p className="text-sm text-red-500">{errors.ends_at}</p>}
                                    </div>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        name="is_active"
                                        checked={form.data.is_active}
                                        onChange={(e) => form.setData('is_active', e.target.checked)}
                                    />
                                    <Label htmlFor="is_active" className="text-sm">
                                        Active
                                    </Label>
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Update Discount
                                    </Button>
                                    <Link
                                        href={route('discounts.index')}
                                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                                    >
                                        Cancel
                                    </Link>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}
