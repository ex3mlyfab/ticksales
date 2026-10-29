import { Form, useForm } from '@inertiajs/react';
import { Checkbox, Input, Label } from '@/components/ui';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

import { Button } from '@/components/ui/button';
import Link from '@/components/link';

export default function SupplierCreatePage() {
    const form = useForm({
        name: '',
        email: '',
        phone: '',
        address: '',
        tax_number: '',
        is_active: true,
    });

    return (
        <>
            <Head title="Create supplier" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Create supplier"
                    description="Add a new supplier to your directory."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        onSubmit={form.post(route('suppliers.store'))}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            value={form.data.name}
                                            onChange={(e) => form.setData('name', e.target.value)}
                                            placeholder="Supplier name"
                                            required
                                        />
                                        {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            value={form.data.email}
                                            onChange={(e) => form.setData('email', e.target.value)}
                                            placeholder="contact@supplier.com"
                                        />
                                        {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            value={form.data.phone}
                                            onChange={(e) => form.setData('phone', e.target.value)}
                                            placeholder="+1 (555) 123-4567"
                                        />
                                        {errors.phone && <p className="text-sm text-red-500">{errors.phone}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="tax_number">Tax Number</Label>
                                        <Input
                                            id="tax_number"
                                            name="tax_number"
                                            value={form.data.tax_number}
                                            onChange={(e) => form.setData('tax_number', e.target.value)}
                                            placeholder="VAT/GST number"
                                        />
                                        {errors.tax_number && <p className="text-sm text-red-500">{errors.tax_number}</p>}
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="address">Address</Label>
                                    <textarea
                                        id="address"
                                        name="address"
                                        value={form.data.address}
                                        onChange={(e) => form.setData('address', e.target.value)}
                                        placeholder="Supplier address"
                                        rows={3}
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    />
                                    {errors.address && (
                                        <p className="text-sm text-red-500">{errors.address}</p>
                                    )}
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
                                        Create Supplier
                                    </Button>
                                    <Link
                                        href={route('suppliers.index')}
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