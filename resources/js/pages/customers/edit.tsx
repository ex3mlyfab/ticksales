import { Head, Form, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { Button } from '@/components/ui/button';
import { Checkbox, Input, Label } from '@/components/ui';

interface Group {
    id: string;
    name: string;
}

interface Customer {
    id: string;
    customer_group_id: string | null;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    credit_limit: number;
    loyalty_points: number;
    is_active: boolean;
}

interface Props {
    customer: Customer;
    groups: Group[];
}

export default function CustomerEditPage({ customer, groups }: Props) {
    const form = useForm({
        customer_group_id: customer.customer_group_id || '',
        name: customer.name,
        email: customer.email || '',
        phone: customer.phone || '',
        address: customer.address || '',
        credit_limit: String(customer.credit_limit),
        loyalty_points: String(customer.loyalty_points),
        is_active: customer.is_active,
    });

    return (
        <>
            <Head title="Edit customer" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Edit customer"
                    description="Update this customer's details."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        onSubmit={form.put(route('customers.update', customer.id))}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name *</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        placeholder="e.g. Jane Doe"
                                        required
                                    />
                                    {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="customer_group_id">Customer group</Label>
                                    <select
                                        id="customer_group_id"
                                        name="customer_group_id"
                                        value={form.data.customer_group_id}
                                        onChange={(e) =>
                                            form.setData('customer_group_id', e.target.value)
                                        }
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="">No group</option>
                                        {groups.map((group) => (
                                            <option key={group.id} value={group.id}>
                                                {group.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.customer_group_id && (
                                        <p className="text-sm text-red-500">{errors.customer_group_id}</p>
                                    )}
                                </div>

                                <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            value={form.data.email}
                                            onChange={(e) => form.setData('email', e.target.value)}
                                            placeholder="e.g. jane@example.com"
                                        />
                                        {errors.email && (
                                            <p className="text-sm text-red-500">{errors.email}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            value={form.data.phone}
                                            onChange={(e) => form.setData('phone', e.target.value)}
                                            placeholder="e.g. +1 555 000 1234"
                                        />
                                        {errors.phone && (
                                            <p className="text-sm text-red-500">{errors.phone}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="address">Address</Label>
                                    <textarea
                                        id="address"
                                        name="address"
                                        value={form.data.address}
                                        onChange={(e) => form.setData('address', e.target.value)}
                                        placeholder="Optional billing address"
                                        rows={2}
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    />
                                    {errors.address && (
                                        <p className="text-sm text-red-500">{errors.address}</p>
                                    )}
                                </div>

                                <div className="grid gap-2 sm:grid-cols-2 sm:gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="credit_limit">Credit limit *</Label>
                                        <Input
                                            id="credit_limit"
                                            name="credit_limit"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={form.data.credit_limit}
                                            onChange={(e) => form.setData('credit_limit', e.target.value)}
                                            required
                                        />
                                        {errors.credit_limit && (
                                            <p className="text-sm text-red-500">{errors.credit_limit}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="loyalty_points">Loyalty points</Label>
                                        <Input
                                            id="loyalty_points"
                                            name="loyalty_points"
                                            type="number"
                                            min="0"
                                            value={form.data.loyalty_points}
                                            onChange={(e) =>
                                                form.setData('loyalty_points', e.target.value)
                                            }
                                        />
                                        {errors.loyalty_points && (
                                            <p className="text-sm text-red-500">{errors.loyalty_points}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="is_active"
                                        name="is_active"
                                        checked={form.data.is_active}
                                        onCheckedChange={(checked) =>
                                            form.setData('is_active', checked === true)
                                        }
                                    />
                                    <Label htmlFor="is_active" className="text-sm">
                                        Active
                                    </Label>
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Update Customer
                                    </Button>
                                    <Link
                                        href={route('customers.index')}
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

CustomerEditPage.layout = {
    breadcrumbs: [
        {
            title: 'Customers',
            href: '/customers',
        },
        {
            title: 'Edit',
        },
    ],
};
