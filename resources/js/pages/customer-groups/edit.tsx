import { Head, Form, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { Button } from '@/components/ui/button';
import { Checkbox, Input, Label } from '@/components/ui';

interface CustomerGroup {
    id: string;
    name: string;
    description: string | null;
    discount_rate: number;
    is_active: boolean;
}

interface Props {
    group: CustomerGroup;
}

export default function CustomerGroupEditPage({ group }: Props) {
    const form = useForm({
        name: group.name,
        description: group.description || '',
        discount_rate: String(group.discount_rate),
        is_active: group.is_active,
    });

    return (
        <>
            <Head title="Edit customer group" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Edit customer group"
                    description="Update this customer group's details."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        onSubmit={form.put(route('customer-groups.update', group.id))}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Group Name *</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        placeholder="e.g. VIP Customers"
                                        required
                                    />
                                    {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description">Description</Label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        value={form.data.description}
                                        onChange={(e) => form.setData('description', e.target.value)}
                                        placeholder="Optional description of the group"
                                        rows={3}
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    />
                                    {errors.description && (
                                        <p className="text-sm text-red-500">{errors.description}</p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="discount_rate">Discount Rate (%) *</Label>
                                    <Input
                                        id="discount_rate"
                                        name="discount_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={form.data.discount_rate}
                                        onChange={(e) => form.setData('discount_rate', e.target.value)}
                                        required
                                    />
                                    {errors.discount_rate && (
                                        <p className="text-sm text-red-500">{errors.discount_rate}</p>
                                    )}
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
                                        Update Group
                                    </Button>
                                    <Link
                                        href={route('customer-groups.index')}
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

CustomerGroupEditPage.layout = {
    breadcrumbs: [
        {
            title: 'Customer groups',
            href: '/customer-groups',
        },
        {
            title: 'Edit',
        },
    ],
};
