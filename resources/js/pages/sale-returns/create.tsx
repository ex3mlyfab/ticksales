import { useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Input, Label } from '@/components/ui';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import Link from '@/components/link';
import { store, index } from '@/routes/sale-returns';


interface ProductRef {
    id: string;
    name: string;
}

interface SaleLine {
    id: string;
    product_id: string;
    variant_id: string | null;
    name: string;
    quantity: number;
    unit_price: number;
    total_amount: number;
    product: ProductRef | null;
}

interface PriorReturnLine {
    product_id: string;
    quantity: number;
}

interface PriorReturn {
    id: string;
    items: PriorReturnLine[];
}

interface Sale {
    id: string;
    reference_no: string;
    status: string;
    customer: { id: string; name: string } | null;
    sold_at: string;
    items: SaleLine[];
    returns: PriorReturn[];
}

interface PaymentMethod {
    id: string;
    name: string;
    type: string;
}

interface Props {
    sales: Sale[];
    paymentMethods: PaymentMethod[];
}

export default function SaleReturnCreatePage({ sales, paymentMethods }: Props) {
    const [selectedSaleId, setSelectedSaleId] = useState('');
    const [quantities, setQuantities] = useState<Record<string, string>>({});

    const selectedSale = useMemo(
        () => sales.find((sale) => sale.id === selectedSaleId) ?? null,
        [sales, selectedSaleId],
    );

    const remainingFor = (line: SaleLine): number => {
        if (!selectedSale) return 0;
        let returned = 0;
        for (const prior of selectedSale.returns) {
            for (const item of prior.items) {
                if (item.product_id === line.product_id)
                    returned += Number(item.quantity);
            }
        }
        return Math.max(0, Number(line.quantity) - returned);
    };

    const totalRefund = useMemo(() => {
        if (!selectedSale) return 0;
        return selectedSale.items.reduce((sum, line) => {
            const qty = Number(quantities[line.id] ?? 0);
            if (qty <= 0) return sum;
            return (
                sum + (Number(line.total_amount) / Number(line.quantity)) * qty
            );
        }, 0);
    }, [selectedSale, quantities]);

    const form = useForm({
        sale_id: '',
        refund_method_id: '',
        reason: '',
        items: [] as Array<{
            product_id: string;
            variant_id: string | null;
            quantity: string;
        }>,
    });

    const handleSaleChange = (saleId: string) => {
        setSelectedSaleId(saleId);
        setQuantities({});
        form.setData('sale_id', saleId);
    };

    const handleQtyChange = (lineId: string, value: string) => {
        setQuantities((prev) => ({ ...prev, [lineId]: value }));
    };

    const submit = () => {
        const items = selectedSale
            ? selectedSale.items
                  .map((line) => ({
                      product_id: line.product_id,
                      variant_id: line.variant_id,
                      quantity: quantities[line.id] ?? '',
                  }))
                  .filter((item) => Number(item.quantity) > 0)
            : [];
        form.setData('items', items);
        form.post(store.url());
    };

    return (
        <>
            <Head title="New sale return" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="New sale return"
                    description="Refund items from an existing sale and restore stock."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <form onSubmit={(e) => { e.preventDefault(); submit(); }} className="space-y-6">
                        {(() => { const { processing, errors } = form; return (<>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="sale_id">Sale *</Label>
                                        <select
                                            id="sale_id"
                                            name="sale_id"
                                            value={form.data.sale_id}
                                            onChange={(e) =>
                                                handleSaleChange(e.target.value)
                                            }
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">
                                                Select a sale
                                            </option>
                                            {sales.map((sale) => (
                                                <option
                                                    key={sale.id}
                                                    value={sale.id}
                                                >
                                                    {sale.reference_no} -{' '}
                                                    {sale.customer?.name ??
                                                        'Walk-in'}{' '}
                                                    ({sale.status})
                                                </option>
                                            ))}
                                        </select>
                                        {errors.sale_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.sale_id}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="refund_method_id">
                                            Refund method
                                        </Label>
                                        <select
                                            id="refund_method_id"
                                            name="refund_method_id"
                                            value={form.data.refund_method_id}
                                            onChange={(e) =>
                                                form.setData(
                                                    'refund_method_id',
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">
                                                Select method
                                            </option>
                                            {paymentMethods.map((method) => (
                                                <option
                                                    key={method.id}
                                                    value={method.id}
                                                >
                                                    {method.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="reason">Reason</Label>
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
                                        placeholder="e.g. Customer changed their mind, damaged item"
                                    />
                                    {errors.reason && (
                                        <p className="text-sm text-destructive">
                                            {errors.reason}
                                        </p>
                                    )}
                                </div>

                                {selectedSale && (
                                    <div className="space-y-3">
                                        <h3 className="text-sm font-semibold">
                                            Items to return
                                        </h3>
                                        <div className="divide-y rounded border">
                                            {selectedSale.items.map((line) => {
                                                const remaining =
                                                    remainingFor(line);
                                                return (
                                                    <div
                                                        key={line.id}
                                                        className="grid grid-cols-1 items-center gap-3 p-3 sm:grid-cols-2 xl:grid-cols-4"
                                                    >
                                                        <div>
                                                            <p className="text-sm font-medium">
                                                                {line.product
                                                                    ?.name ??
                                                                    line.name}
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                Qty sold:{' '}
                                                                {Number(
                                                                    line.quantity,
                                                                ).toFixed(2)}
                                                            </p>
                                                        </div>
                                                        <div className="text-sm text-muted-foreground">
                                                            Unit:{' '}
                                                            {Number(
                                                                line.unit_price,
                                                            ).toFixed(2)}
                                                        </div>
                                                        <div className="grid gap-1">
                                                            <Label
                                                                htmlFor={`qty_${line.id}`}
                                                            >
                                                                Return qty
                                                            </Label>
                                                            <Input
                                                                id={`qty_${line.id}`}
                                                                name={`items[${line.id}][quantity]`}
                                                                type="number"
                                                                min="0"
                                                                max={remaining}
                                                                step="0.0001"
                                                                value={
                                                                    quantities[
                                                                        line.id
                                                                    ] ?? ''
                                                                }
                                                                onChange={(e) =>
                                                                    handleQtyChange(
                                                                        line.id,
                                                                        e.target
                                                                            .value,
                                                                    )
                                                                }
                                                                disabled={
                                                                    remaining <=
                                                                    0
                                                                }
                                                            />
                                                            <p className="text-xs text-muted-foreground">
                                                                Max:{' '}
                                                                {remaining.toFixed(
                                                                    2,
                                                                )}
                                                            </p>
                                                        </div>
                                                        <div className="text-right text-sm font-medium">
                                                            {(() => {
                                                                const qty =
                                                                    Number(
                                                                        quantities[
                                                                            line
                                                                                .id
                                                                        ] ?? 0,
                                                                    );
                                                                if (qty <= 0)
                                                                    return '-';
                                                                return (
                                                                    (Number(
                                                                        line.total_amount,
                                                                    ) /
                                                                        Number(
                                                                            line.quantity,
                                                                        )) *
                                                                    qty
                                                                ).toFixed(2);
                                                            })()}
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {errors['items'] && (
                                    <p className="text-sm text-destructive">
                                        Select at least one item to return.
                                    </p>
                                )}

                                {totalRefund > 0 && (
                                    <div className="flex items-center justify-between rounded border p-4">
                                        <span className="text-sm font-medium">
                                            Refund amount
                                        </span>
                                        <span className="text-lg font-semibold">
                                            {totalRefund.toFixed(2)}
                                        </span>
                                    </div>
                                )}

                                <div className="flex gap-2">
                                    <Button
                                        type="submit"
                                        disabled={
                                            processing || totalRefund <= 0
                                        }
                                    >
                                        Process Refund
                                    </Button>
                                    <Link
                                        href={index.url()}
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

SaleReturnCreatePage.layout = {
    breadcrumbs: [
        {
            title: 'Sale returns',
            href: '/sale-returns',
        },
        {
            title: 'New return',
            href: '/sale-returns/create',
        },
    ],
};
