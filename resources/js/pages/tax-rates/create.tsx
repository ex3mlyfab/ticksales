import { Head, Form, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input, Label } from '@/components/ui';

export default function TaxRateCreatePage() {
    const form = useForm({
        name: '',
        rate: '',
        is_inclusive: false,
        is_active: true,
    });

    return (
        <>
            <Head title="Create tax rate" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Create tax rate"
                    description="Add a new tax rate for your sales and purchases."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        onSubmit={form.post(route('tax-rates.store'))}
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
                                        placeholder="e.g. VAT 15%, Sales Tax"
                                        required
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-red-500">{errors.name}</p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="rate">Rate (%)</Label>
                                    <Input
                                        id="rate"
                                        name="rate"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.0001"
                                        value={form.data.rate}
                                        onChange={(e) => form.setData('rate', e.target.value)}
                                        placeholder="e.g. 15"
                                        required
                                    />
                                    {errors.rate && (
                                        <p className="text-sm text-red-500">{errors.rate}</p>
                                    )}
                                </div>

                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="is_inclusive"
                                        name="is_inclusive"
                                        checked={form.data.is_inclusive}
                                        onCheckedChange={(checked) =>
                                            form.setData('is_inclusive', checked === true)
                                        }
                                    />
                                    <Label htmlFor="is_inclusive">Prices already include this tax</Label>
                                </div>
                                {errors.is_inclusive && (
                                    <p className="text-sm text-red-500">{errors.is_inclusive}</p>
                                )}

                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="is_active"
                                        name="is_active"
                                        checked={form.data.is_active}
                                        onCheckedChange={(checked) =>
                                            form.setData('is_active', checked === true)
                                        }
                                    />
                                    <Label htmlFor="is_active">Active</Label>
                                </div>
                                {errors.is_active && (
                                    <p className="text-sm text-red-500">{errors.is_active}</p>
                                )}

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Create Rate
                                    </Button>
                                    <Link
                                        href={route('tax-rates.index')}
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

TaxRateCreatePage.layout = {
    breadcrumbs: [
        {
            title: 'Tax rates',
            href: '/tax-rates',
        },
        {
            title: 'Create',
        },
    ],
};
