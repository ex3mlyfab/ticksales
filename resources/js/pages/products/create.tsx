import { Form, useForm } from '@inertiajs/react';
import { Checkbox, Input, Label } from '@/components/ui';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

import { Button } from '@/components/ui/button';
import Link from '@/components/link';

interface Props {
    categories: Array<{ id: string; name: string }>;
    brands: Array<{ id: string; name: string }>;
    units: Array<{ id: string; name: string; abbreviation?: string }>;
    taxRates: Array<{ id: string; name: string }>;
}

export default function ProductCreatePage({ categories, brands, units, taxRates }: Props) {
    const form = useForm({
        category_id: '',
        brand_id: '',
        unit_id: '',
        tax_rate_id: '',
        name: '',
        sku: '',
        barcode: '',
        description: '',
        cost_price: '0.00',
        selling_price: '0.00',
        has_variants: false,
        track_inventory: true,
        alert_quantity: 0,
        image_path: '',
        is_active: true,
    });

    return (
        <>
            <Head title="Create product" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Create product"
                    description="Add a new product to your catalogue."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        onSubmit={form.post(route('products.store'))}
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
                                            placeholder="Product name"
                                            required
                                        />
                                        {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="category_id">Category</Label>
                                        <select
                                            id="category_id"
                                            name="category_id"
                                            value={form.data.category_id}
                                            onChange={(e) => form.setData('category_id', e.target.value)}
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">No category</option>
                                            {categories.map((cat) => (
                                                <option key={cat.id} value={cat.id}>
                                                    {cat.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.category_id && (
                                            <p className="text-sm text-red-500">{errors.category_id}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="brand_id">Brand</Label>
                                        <select
                                            id="brand_id"
                                            name="brand_id"
                                            value={form.data.brand_id}
                                            onChange={(e) => form.setData('brand_id', e.target.value)}
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">No brand</option>
                                            {brands.map((brand) => (
                                                <option key={brand.id} value={brand.id}>
                                                    {brand.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.brand_id && (
                                            <p className="text-sm text-red-500">{errors.brand_id}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="unit_id">Unit</Label>
                                        <select
                                            id="unit_id"
                                            name="unit_id"
                                            value={form.data.unit_id}
                                            onChange={(e) => form.setData('unit_id', e.target.value)}
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">No unit</option>
                                            {units.map((unit) => (
                                                <option key={unit.id} value={unit.id}>
                                                    {unit.abbreviation ? `${unit.name} (${unit.abbreviation})` : unit.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.unit_id && (
                                            <p className="text-sm text-red-500">{errors.unit_id}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="tax_rate_id">Tax Rate</Label>
                                    <select
                                        id="tax_rate_id"
                                        name="tax_rate_id"
                                        value={form.data.tax_rate_id}
                                        onChange={(e) => form.setData('tax_rate_id', e.target.value)}
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="">No tax</option>
                                        {taxRates.map((rate) => (
                                            <option key={rate.id} value={rate.id}>
                                                {rate.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.tax_rate_id && (
                                        <p className="text-sm text-red-500">{errors.tax_rate_id}</p>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="sku">SKU</Label>
                                        <Input
                                            id="sku"
                                            name="sku"
                                            value={form.data.sku}
                                            onChange={(e) => form.setData('sku', e.target.value)}
                                            placeholder="Optional"
                                        />
                                        {errors.sku && <p className="text-sm text-red-500">{errors.sku}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="barcode">Barcode</Label>
                                        <Input
                                            id="barcode"
                                            name="barcode"
                                            value={form.data.barcode}
                                            onChange={(e) => form.setData('barcode', e.target.value)}
                                            placeholder="Optional"
                                        />
                                        {errors.barcode && <p className="text-sm text-red-500">{errors.barcode}</p>}
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description">Description</Label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        value={form.data.description}
                                        onChange={(e) => form.setData('description', e.target.value)}
                                        placeholder="Optional product description"
                                        rows={3}
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    />
                                    {errors.description && (
                                        <p className="text-sm text-red-500">{errors.description}</p>
                                    )}
                                </div>

                                <div className="grid grid-cols-3 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="cost_price">Cost Price *</Label>
                                        <Input
                                            id="cost_price"
                                            name="cost_price"
                                            type="number"
                                            step="0.0001"
                                            min="0"
                                            value={form.data.cost_price}
                                            onChange={(e) => form.setData('cost_price', e.target.value)}
                                            required
                                        />
                                        {errors.cost_price && (
                                            <p className="text-sm text-red-500">{errors.cost_price}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="selling_price">Selling Price *</Label>
                                        <Input
                                            id="selling_price"
                                            name="selling_price"
                                            type="number"
                                            step="0.0001"
                                            min="0"
                                            value={form.data.selling_price}
                                            onChange={(e) => form.setData('selling_price', e.target.value)}
                                            required
                                        />
                                        {errors.selling_price && (
                                            <p className="text-sm text-red-500">{errors.selling_price}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="alert_quantity">Alert Quantity</Label>
                                        <Input
                                            id="alert_quantity"
                                            name="alert_quantity"
                                            type="number"
                                            min="0"
                                            value={form.data.alert_quantity}
                                            onChange={(e) => form.setData('alert_quantity', e.target.value)}
                                        />
                                        {errors.alert_quantity && (
                                            <p className="text-sm text-red-500">{errors.alert_quantity}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="flex space-x-4">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="has_variants"
                                            name="has_variants"
                                            checked={form.data.has_variants}
                                            onChange={(e) => form.setData('has_variants', e.target.checked)}
                                        />
                                        <Label htmlFor="has_variants" className="text-sm">
                                            Has variants
                                        </Label>
                                    </div>

                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="track_inventory"
                                            name="track_inventory"
                                            checked={form.data.track_inventory}
                                            onChange={(e) => form.setData('track_inventory', e.target.checked)}
                                        />
                                        <Label htmlFor="track_inventory" className="text-sm">
                                            Track inventory
                                        </Label>
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
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Create Product
                                    </Button>
                                    <Link
                                        href={route('products.index')}
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