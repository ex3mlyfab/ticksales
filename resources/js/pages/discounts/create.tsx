import { Form, useForm } from '@inertiajs/react';
import { Checkbox, Input, Label, Select } from '@/components/ui';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

import { Button } from '@/components/ui/button';
import Link from '@/components/link';

export default function DiscountCreatePage() {
    const form = useForm({
        name: '',
        type: 'percentage',
        value: '',
        min_purchase_amount: '',
        max_discount_amount: '',
        starts_at: '',
        ends_at: '',
        is_active: true,
    });

    return (
        <>
            <Head title="Create discount" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Create discount"
                    description="Add a new discount or promotional pricing rule."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        onSubmit={form.post(route('discounts.store'))}
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
                                        placeholder={form.data.type === 'percentage' ? 'e.g. 10.00' : 'e.g. 5.00'}
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
                                        Create Discount
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
