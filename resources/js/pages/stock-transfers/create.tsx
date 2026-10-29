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

export default function StockTransferCreatePage({ branches, products }: Props) {
    const form = useForm({
        from_branch_id: '',
        to_branch_id: '',
        note: '',
        items: [
            {
                product_id: '',
                quantity: '',
                unit_cost: '',
            },
        ],
    });

    const addItem = () => {
        form.data.items.push({
            product_id: '',
            quantity: '',
            unit_cost: '',
        });
    };

    const removeItem = (index: number) => {
        form.data.items.splice(index, 1);
        form.setData('items', form.data.items);
    };

    return (
        <>
            <Head title="New stock transfer" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="New stock transfer"
                    description="Transfer stock between branches."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Form
                        onSubmit={form.post(route('stock-transfers.store'))}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid grid-cols-3 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="from_branch_id">From Branch *</Label>
                                        <select
                                            id="from_branch_id"
                                            name="from_branch_id"
                                            value={form.data.from_branch_id}
                                            onChange={(e) => form.setData('from_branch_id', e.target.value)}
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">Select source</option>
                                            {branches.map((branch) => (
                                                <option key={branch.id} value={branch.id}>
                                                    {branch.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.from_branch_id && (
                                            <p className="text-sm text-red-500">{errors.from_branch_id}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="to_branch_id">To Branch *</Label>
                                        <select
                                            id="to_branch_id"
                                            name="to_branch_id"
                                            value={form.data.to_branch_id}
                                            onChange={(e) => form.setData('to_branch_id', e.target.value)}
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">Select destination</option>
                                            {branches
                                                .filter((b) => b.id !== form.data.from_branch_id)
                                                .map((branch) => (
                                                    <option key={branch.id} value={branch.id}>
                                                        {branch.name}
                                                    </option>
                                                ))}
                                        </select>
                                        {errors.to_branch_id && (
                                            <p className="text-sm text-red-500">{errors.to_branch_id}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="note">Note</Label>
                                        <Input
                                            id="note"
                                            name="note"
                                            value={form.data.note}
                                            onChange={(e) => form.setData('note', e.target.value)}
                                            placeholder="Optional note"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <h3 className="text-sm font-semibold">Items to transfer</h3>

                                    {form.data.items.map((item, index) => (
                                        <div key={index} className="grid grid-cols-4 gap-3 rounded border p-3">
                                            <div className="grid gap-1">
                                                <Label>Product *</Label>
                                                <select
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
                                            </div>

                                            <div className="grid gap-1">
                                                <Label>Quantity *</Label>
                                                <Input
                                                    type="number"
                                                    step="0.0001"
                                                    min="0"
                                                    name={`items[${index}][quantity]`}
                                                    value={item.quantity}
                                                    onChange={(e) => {
                                                        const items = [...form.data.items];
                                                        items[index].quantity = e.target.value;
                                                        form.setData('items', items);
                                                    }}
                                                />
                                            </div>

                                            <div className="grid gap-1">
                                                <Label>Unit Cost</Label>
                                                <Input
                                                    type="number"
                                                    step="0.0001"
                                                    min="0"
                                                    name={`items[${index}][unit_cost]`}
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
                                        Create Transfer
                                    </Button>
                                    <Link
                                        href={route('stock-transfers.index')}
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