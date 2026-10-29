import { Form, useForm } from '@inertiajs/react';
import { Input, Label } from '@/components/ui';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

import { Button } from '@/components/ui/button';

interface ProductUnit {
    id: string;
    name: string;
    abbreviation: string | null;
}

interface Props {
    unit: ProductUnit;
}

export default function ProductUnitEditPage({ unit }: Props) {
    const form = useForm({
        name: unit.name,
        abbreviation: unit.abbreviation || '',
    });

    return (
        <>
            <Head title="Edit product unit" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Edit product unit"
                    description="Update the unit of measurement for your products."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        onSubmit={form.put(route('product-units.update', unit.id))}
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
                                        placeholder="e.g. Kilogram, Piece, Box"
                                        required
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-red-500">{errors.name}</p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="abbreviation">Abbreviation (optional)</Label>
                                    <Input
                                        id="abbreviation"
                                        name="abbreviation"
                                        value={form.data.abbreviation}
                                        onChange={(e) => form.setData('abbreviation', e.target.value)}
                                        placeholder="e.g. kg, pcs, bx"
                                    />
                                    {errors.abbreviation && (
                                        <p className="text-sm text-red-500">{errors.abbreviation}</p>
                                    )}
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Update Unit
                                    </Button>
                                    <Link
                                        href={route('product-units.index')}
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