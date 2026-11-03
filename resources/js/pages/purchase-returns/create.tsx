import { useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Input, Label, Textarea } from '@/components/ui';
import Link from '@/components/link';
import { store, index as purchase_returnsIndex } from '@/routes/purchase-returns';


interface Branch {
    id: string;
    name: string;
}

interface Supplier {
    id: string;
    name: string;
}

interface Product {
    id: string;
    name: string;
    sku: string | null;
}

interface LineItem {
    product_id: string;
    variant_id: string;
    quantity: string;
    unit_cost: string;
    tax_rate: string;
    discount_amount: string;
}

interface Props {
    branches: Branch[];
    suppliers: Supplier[];
    products: Product[];
}

export default function PurchaseReturnCreatePage({
    branches,
    suppliers,
    products,
}: Props) {
    const form = useForm({
        branch_id: '',
        supplier_id: '',
        purchase_id: '',
        reason: '',
        return_date: new Date().toISOString().slice(0, 10),
        notes: '',
        items: [] as LineItem[],
    });

    const addItem = () => {
        form.data.items.push({
            product_id: '',
            variant_id: '',
            quantity: '',
            unit_cost: '',
            tax_rate: '',
            discount_amount: '',
        });
        form.setData('items', form.data.items);
    };

    const removeItem = (index: number) => {
        form.data.items.splice(index, 1);
        form.setData('items', form.data.items);
    };

    const updateItem = (
        index: number,
        field: keyof LineItem,
        value: string,
    ) => {
        const items = [...form.data.items];
        items[index] = { ...items[index], [field]: value };
        form.setData('items', items);
    };

    const lineTotal = (item: LineItem) => {
        const qty = Number(item.quantity) || 0;
        const cost = Number(item.unit_cost) || 0;
        const discount = Number(item.discount_amount) || 0;
        const taxRate = Number(item.tax_rate) || 0;
        const taxable = Math.max(0, qty * cost - discount);
        return Number((taxable + (taxable * taxRate) / 100).toFixed(4));
    };

    const grandTotal = form.data.items.reduce(
        (sum, item) => sum + lineTotal(item),
        0,
    );

    return (
        <>
            <Head title="New purchase return" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="New purchase return"
                    description="Return goods to a supplier and remove them from stock."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <form onSubmit={(e) => { e.preventDefault(); form.post(store.url()); }} className="space-y-6">
                        {(() => { const { processing, errors } = form; return (<>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="branch_id">
                                            Branch *
                                        </Label>
                                        <select
                                            id="branch_id"
                                            name="branch_id"
                                            value={form.data.branch_id}
                                            onChange={(e) =>
                                                form.setData(
                                                    'branch_id',
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">
                                                Select branch
                                            </option>
                                            {branches.map((b) => (
                                                <option key={b.id} value={b.id}>
                                                    {b.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.branch_id && (
                                            <p className="text-sm text-red-500">
                                                {errors.branch_id}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="supplier_id">
                                            Supplier *
                                        </Label>
                                        <select
                                            id="supplier_id"
                                            name="supplier_id"
                                            value={form.data.supplier_id}
                                            onChange={(e) =>
                                                form.setData(
                                                    'supplier_id',
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">
                                                Select supplier
                                            </option>
                                            {suppliers.map((s) => (
                                                <option key={s.id} value={s.id}>
                                                    {s.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.supplier_id && (
                                            <p className="text-sm text-red-500">
                                                {errors.supplier_id}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="reason">Reason *</Label>
                                        <Input
                                            id="reason"
                                            name="reason"
                                            value={form.data.reason}
                                            onChange={(e) =>
                                                form.setData(
                                                    'reason',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="e.g. Damaged, short-dated, wrong item"
                                        />
                                        {errors.reason && (
                                            <p className="text-sm text-red-500">
                                                {errors.reason}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="return_date">
                                            Return date *
                                        </Label>
                                        <Input
                                            id="return_date"
                                            name="return_date"
                                            type="date"
                                            value={form.data.return_date}
                                            onChange={(e) =>
                                                form.setData(
                                                    'return_date',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        {errors.return_date && (
                                            <p className="text-sm text-red-500">
                                                {errors.return_date}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="notes">Notes</Label>
                                    <Textarea
                                        id="notes"
                                        name="notes"
                                        value={form.data.notes}
                                        onChange={(e) =>
                                            form.setData(
                                                'notes',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>

                                <div className="space-y-4">
                                    <h3 className="text-sm font-semibold">
                                        Items
                                    </h3>
                                    {form.data.items.length === 0 && (
                                        <p className="text-sm text-muted-foreground">
                                            Add at least one item.
                                        </p>
                                    )}
                                    {form.data.items.map((item, index) => (
                                        <div
                                            key={index}
                                            className="grid grid-cols-1 gap-2 rounded border p-3 sm:grid-cols-2 2xl:grid-cols-6"
                                        >
                                            <div className="grid gap-1 sm:col-span-2 2xl:col-span-2">
                                                <Label
                                                    htmlFor={`pr_product_${index}`}
                                                >
                                                    Product *
                                                </Label>
                                                <select
                                                    id={`pr_product_${index}`}
                                                    name={`items[${index}][product_id]`}
                                                    value={item.product_id}
                                                    onChange={(e) =>
                                                        updateItem(
                                                            index,
                                                            'product_id',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                                >
                                                    <option value="">
                                                        Select product
                                                    </option>
                                                    {products.map((p) => (
                                                        <option
                                                            key={p.id}
                                                            value={p.id}
                                                        >
                                                            {p.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div className="grid gap-1">
                                                <Label
                                                    htmlFor={`pr_qty_${index}`}
                                                >
                                                    Qty *
                                                </Label>
                                                <Input
                                                    id={`pr_qty_${index}`}
                                                    name={`items[${index}][quantity]`}
                                                    type="number"
                                                    step="0.0001"
                                                    min="0"
                                                    value={item.quantity}
                                                    onChange={(e) =>
                                                        updateItem(
                                                            index,
                                                            'quantity',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-1">
                                                <Label
                                                    htmlFor={`pr_cost_${index}`}
                                                >
                                                    Unit cost *
                                                </Label>
                                                <Input
                                                    id={`pr_cost_${index}`}
                                                    name={`items[${index}][unit_cost]`}
                                                    type="number"
                                                    step="0.0001"
                                                    min="0"
                                                    value={item.unit_cost}
                                                    onChange={(e) =>
                                                        updateItem(
                                                            index,
                                                            'unit_cost',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-1">
                                                <Label
                                                    htmlFor={`pr_tax_${index}`}
                                                >
                                                    Tax %
                                                </Label>
                                                <Input
                                                    id={`pr_tax_${index}`}
                                                    name={`items[${index}][tax_rate]`}
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={item.tax_rate}
                                                    onChange={(e) =>
                                                        updateItem(
                                                            index,
                                                            'tax_rate',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                            </div>
                                            <div className="flex items-end justify-between">
                                                <span className="text-sm font-medium">
                                                    {lineTotal(item).toFixed(2)}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        removeItem(index)
                                                    }
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

                                <div className="flex items-center justify-between border-t pt-4">
                                    <span className="text-sm font-medium">
                                        Total
                                    </span>
                                    <span className="text-lg font-semibold">
                                        {grandTotal.toFixed(2)}
                                    </span>
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Create Return
                                    </Button>
                                    <Link
                                        href={purchase_returnsIndex.url()}
                                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                                    >
                                        Cancel
                                    </Link>
                                </div>
                            </>); })()}
                    </form>
                </div>
            </div>
        </>
    );
}

PurchaseReturnCreatePage.layout = {
    breadcrumbs: [
        {
            title: 'Purchase returns',
            href: '/purchase-returns',
        },
        {
            title: 'New return',
            href: '/purchase-returns/create',
        },
    ],
};
