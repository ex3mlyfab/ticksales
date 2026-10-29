import { Form, useForm } from '@inertiajs/react';
import { Input, Label } from '@/components/ui';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

import { Button } from '@/components/ui/button';
import Link from '@/components/link';

interface Branch {
    id: string;
    name: string;
}

interface Product {
    id: string;
    name: string;
    sku: string | null;
}

interface Props {
    branches: Branch[];
    products: Product[];
}

export default function StockAdjustmentCreatePage({ branches, products }: Props) {
    const form = useForm({
        branch_id: '',
        reason: '',
        items: [
            {
                product_id: '',
                quantity_adjusted: '',
                unit_cost: '',
            },
        ],
    });

    const addItem = () => {
        form.data.items.push({
            product_id: '',
            quantity_adjusted: '',
            unit_cost: '',
        });
    };

    const removeItem = (index: number) => {
        form.data.items.splice(index, 1);
        form.setData('items', form.data.items);
    };

    return (
        <>
            <Head title="New stock adjustment" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="New stock adjustment"
                    description="Record a stock adjustment for a branch."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        onSubmit={form.post(route('stock-adjustments.store'))}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="branch_id">Branch *</Label>
                                        <select
                                            id="branch_id"
                                            name="branch_id"
                                            value={form.data.branch_id}
                                            onChange={(e) => form.setData('branch_id', e.target.value)}
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">Select branch</option>
                                            {branches.map((branch) => (
                                                <option key={branch.id} value={branch.id}>
                                                    {branch.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.branch_id && (
                                            <p className="text-sm text-red-500">{errors.branch_id}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="reason">Reason</Label>
                                        <Input
                                            id="reason"
                                            name="reason"
                                            value={form.data.reason}
                                            onChange={(e) => form.setData('reason', e.target.value)}
                                            placeholder="e.g. Damaged, expired, correction"
                                        />
                                        {errors.reason && (
                                            <p className="text-sm text-red-500">{errors.reason}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <h3 className="text-sm font-semibold">Items to adjust</h3>

                                    {form.data.items.map((item, index) => (
                                        <div key={index} className="grid grid-cols-4 gap-3 rounded border p-3">
                                            <div className="grid gap-1">
                                                <Label htmlFor={`item_product_${index}`}>Product *</Label>
                                                <select
                                                    id={`item_product_${index}`}
                                                    name={`items[${index}][product_id]`}
                                                    value={item.product_id}
                                                    onChange={(e) => {
                                                        const items = [...form.data.items];
                                                        items[index].product_id = e.target.value;
                                                        form.setData('items', items);
                                                    }}
                                                    className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                                >
                                                    <option value="">Select product</option>
                                                    {products.map((product) => (
                                                        <option key={product.id} value={product.id}>
                                                            {product.name}
                                                        </option>
                                                    ))}
                                                </select>
                                                {errors['items.*.product_id'] && (
                                                    <p className="text-xs text-red-500">Product is required</p>
                                                )}
                                            </div>

                                            <div className="grid gap-1">
                                                <Label htmlFor={`item_qty_${index}`}>Qty (use - for removal)</Label>
                                                <Input
                                                    id={`item_qty_${index}`}
                                                    name={`items[${index}][quantity_adjusted]`}
                                                    type="number"
                                                    step="0.0001"
                                                    value={item.quantity_adjusted}
                                                    onChange={(e) => {
                                                        const items = [...form.data.items];
                                                        items[index].quantity_adjusted = e.target.value;
                                                        form.setData('items', items);
                                                    }}
                                                />
                                                {errors['items.*.quantity_adjusted'] && (
                                                    <p className="text-xs text-red-500">Quantity must not be zero</p>
                                                )}
                                            </div>

                                            <div className="grid gap-1">
                                                <Label htmlFor={`item_cost_${index}`}>Unit Cost</Label>
                                                <Input
                                                    id={`item_cost_${index}`}
                                                    name={`items[${index}][unit_cost]`}
                                                    type="number"
                                                    step="0.0001"
                                                    min="0"
                                                    value={item.unit_cost}
                                                    onChange={(e) => {
                                                        const items = [...form.data.items];
                                                        items[index].unit_cost = e.target.value;
                                                        form.setData('items', items);
                                                    }}
                                                />
                                            </div>

                                            <div className="flex items-end">
                                                <button
                                                    type="button"
                                                    onClick={() => removeItem(index)}
                                                    className="text-sm text-red-500 hover:text-red-700"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    ))}

                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={addItem}
                                    >
                                        + Add Item
                                    </Button>
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Create Adjustment
                                    </Button>
                                    <Link
                                        href={route('stock-adjustments.index')}
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