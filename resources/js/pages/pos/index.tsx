import { useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button, Input, Label } from '@/components/ui';
import { useOfflineSales } from '@/hooks/use-offline-sales';
import { store } from '@/routes/pos/sales';
import { close, store as register_sessionsStore } from '@/routes/register-sessions';
import { index } from '@/routes/pos';


interface Product {
    id: string;
    name: string;
    sku: string | null;
    selling_price: number;
    inventories: Array<{
        stock_level: number;
    }>;
}

interface PaymentMethod {
    id: string;
    name: string;
    type: string;
}

interface Customer {
    id: string;
    name: string;
    phone: string | null;
}

interface RegisterSession {
    id: string;
    branch_id: string;
    register_name: string | null;
    opening_balance: number;
    opened_at: string;
}

interface Register {
    id: string;
    name: string;
    branch_id: string;
}

interface TaxRate {
    id: string;
    name: string;
    rate: number;
}

interface CartItem {
    product_id: string;
    name: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    discount_amount: number;
    stock: number;
}

interface Props {
    openSession: RegisterSession | null;
    products: Product[];
    paymentMethods: PaymentMethod[];
    customers: Customer[];
    registers: Register[];
    taxRates: TaxRate[];
}

export default function PosIndexPage({
    openSession,
    products,
    paymentMethods,
    customers,
    registers,
    taxRates,
}: Props) {
    const [search, setSearch] = useState('');
    const [cart, setCart] = useState<CartItem[]>([]);
    const [discount, setDiscount] = useState(0);
    const [customer, setCustomer] = useState('');
    const [paymentMethod, setPaymentMethod] = useState('');
    const [amountTendered, setAmountTendered] = useState(0);
    const [note, setNote] = useState('');

    const form = useForm({});

    const {
        isOnline,
        pendingCount,
        syncing,
        expiredCount,
        enqueue,
        refreshCount,
    } = useOfflineSales(store.url());

    const stockOf = (p: Product) => {
        const inv = p.inventories.find(
            (i) => typeof i.stock_level === 'number',
        );
        return inv ? Number(inv.stock_level) : 999;
    };

    const addToCart = (product: Product) => {
        setCart((prev) => {
            const existing = prev.find((i) => i.product_id === product.id);
            const currentStock = stockOf(product);
            if (existing) {
                if (existing.quantity >= currentStock) return prev;
                return prev.map((i) =>
                    i.product_id === product.id
                        ? { ...i, quantity: i.quantity + 1 }
                        : i,
                );
            }
            return [
                ...prev,
                {
                    product_id: product.id,
                    name: product.name,
                    quantity: 1,
                    unit_price: Number(product.selling_price) || 0,
                    tax_rate: 0,
                    discount_amount: 0,
                    stock: currentStock,
                },
            ];
        });
    };

    const updateQty = (productId: string, qty: number) => {
        setCart((prev) =>
            prev.map((i) =>
                i.product_id === productId
                    ? { ...i, quantity: Math.max(0, qty) }
                    : i,
            ),
        );
    };

    const updateTaxRate = (productId: string, rate: number) => {
        setCart((prev) =>
            prev.map((i) =>
                i.product_id === productId ? { ...i, tax_rate: rate } : i,
            ),
        );
    };

    const removeItem = (productId: string) => {
        setCart((prev) => prev.filter((i) => i.product_id !== productId));
    };

    const subtotal = cart.reduce(
        (sum, i) => sum + i.quantity * i.unit_price,
        0,
    );
    const taxTotal = cart.reduce(
        (sum, i) => sum + (i.quantity * i.unit_price * i.tax_rate) / 100,
        0,
    );
    const total = subtotal - discount + taxTotal;
    const change = Math.max(0, amountTendered - total);

    const filteredProducts = products.filter(
        (p) =>
            p.name.toLowerCase().includes(search.toLowerCase()) ||
            (p.sku && p.sku.toLowerCase().includes(search.toLowerCase())),
    );

    const canCheckOut =
        !!openSession &&
        cart.length > 0 &&
        paymentMethod !== '' &&
        amountTendered >= total;

    const submit = async () => {
        const salePayload = {
            branch_id: openSession?.branch_id ?? null,
            register_session_id: openSession?.id ?? null,
            customer_id: customer || null,
            items: cart.map((i) => ({
                product_id: i.product_id,
                name: i.name,
                quantity: i.quantity,
                unit_price: i.unit_price,
                tax_rate: i.tax_rate,
                discount_amount: i.discount_amount,
            })),
            payments: [
                {
                    payment_method_id: paymentMethod,
                    amount: amountTendered,
                },
            ],
            discount_amount: discount,
            note: note || null,
        };

        // Queue locally while offline; the hook replays on reconnect.
        if (!isOnline) {
            const queued = await enqueue(salePayload);

            if (queued) {
                setCart([]);
                setDiscount(0);
                setCustomer('');
                setPaymentMethod('');
                setAmountTendered(0);
                setNote('');
            }

            return;
        }

        form.transform(() => salePayload);
        form.post(store.url(), {
            onSuccess: () => {
                setCart([]);
                setDiscount(0);
                setCustomer('');
                setPaymentMethod('');
                setAmountTendered(0);
                setNote('');
                void refreshCount();
            },
        });
    };

    return (
        <>
            <Head title="POS Terminal" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Point of Sale"
                    description="Process sales, manage register and checkout."
                />

                {/* Register Status */}
                <div
                    className={`rounded-xl border p-4 ${
                        openSession
                            ? 'border-success/40 bg-success/10'
                            : 'border-warning/40 bg-warning/10'
                    }`}
                >
                    {openSession ? (
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium">
                                    Register {openSession.register_name} â€” Open
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Opening balance:{' '}
                                    {Number(
                                        openSession.opening_balance,
                                    ).toFixed(2)}
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    form.transform(() => ({
                                        actual_balance: 0,
                                    }));
                                    form.post(close.url(openSession.id));
                                }}
                            >
                                Close Register
                            </Button>
                        </div>
                    ) : (
                        <OpenRegisterForm
                            registers={registers}
                            onSuccess={() =>
                                form.get(index.url(), {
                                    preserveState: true,
                                })
                            }
                        />
                    )}
                </div>

                <div className="grid grid-cols-12 gap-6">
                    {/* Product Picker */}
                    <div className="col-span-7">
                        <div className="rounded-xl border bg-card p-4 shadow-sm">
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold">
                                    Products
                                </h2>
                                <Input
                                    type="text"
                                    placeholder="Search by name or SKU..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="w-64"
                                />
                            </div>

                            <div className="mt-4 max-h-96 overflow-y-auto">
                                {filteredProducts.length === 0 ? (
                                    <div className="py-8 text-center text-sm text-muted-foreground">
                                        No products match your search.
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-2 gap-2">
                                        {filteredProducts.map((product) => (
                                            <button
                                                key={product.id}
                                                type="button"
                                                onClick={() =>
                                                    addToCart(product)
                                                }
                                                className="rounded-lg border p-3 text-left transition hover:bg-muted/50"
                                            >
                                                <p className="text-sm font-medium">
                                                    {product.name}
                                                </p>
                                                {product.sku && (
                                                    <p className="text-xs text-muted-foreground">
                                                        {product.sku}
                                                    </p>
                                                )}
                                                <p className="mt-1 text-sm font-semibold">
                                                    {Number(
                                                        product.selling_price,
                                                    ).toFixed(2)}
                                                </p>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Cart & Checkout */}
                    <div className="col-span-5">
                        <div className="rounded-xl border bg-card p-4 shadow-sm">
                            <h2 className="text-lg font-semibold">Cart</h2>

                            {cart.length === 0 ? (
                                <div className="py-8 text-center text-sm text-muted-foreground">
                                    Cart is empty. Add products to begin.
                                </div>
                            ) : (
                                <div className="mt-4 space-y-3">
                                    {cart.map((item) => (
                                        <div
                                            key={item.product_id}
                                            className="flex items-start justify-between gap-2 border-b pb-2"
                                        >
                                            <div className="flex-1">
                                                <p className="text-sm font-medium">
                                                    {item.name}
                                                </p>
                                                <div className="mt-1 flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            updateQty(
                                                                item.product_id,
                                                                item.quantity -
                                                                    1,
                                                            )
                                                        }
                                                        className="h-6 w-6 rounded-full border text-xs"
                                                    >
                                                        âˆ’
                                                    </button>
                                                    <input
                                                        type="number"
                                                        min={0}
                                                        max={item.stock}
                                                        value={item.quantity}
                                                        onChange={(e) =>
                                                            updateQty(
                                                                item.product_id,
                                                                Number(
                                                                    e.target
                                                                        .value,
                                                                ),
                                                            )
                                                        }
                                                        className="h-6 w-16 rounded border px-1 text-center text-xs"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            updateQty(
                                                                item.product_id,
                                                                item.quantity +
                                                                    1,
                                                            )
                                                        }
                                                        className="h-6 w-6 rounded-full border text-xs"
                                                    >
                                                        +
                                                    </button>
                                                    <span className="text-xs text-muted-foreground">
                                                        stock: {item.stock}
                                                    </span>
                                                </div>
                                                <div className="mt-1 flex items-center gap-2">
                                                    <select
                                                        value={item.tax_rate}
                                                        onChange={(e) =>
                                                            updateTaxRate(
                                                                item.product_id,
                                                                Number(
                                                                    e.target
                                                                        .value,
                                                                ),
                                                            )
                                                        }
                                                        className="h-6 rounded border px-1 text-xs"
                                                    >
                                                        <option value={0}>
                                                            No tax
                                                        </option>
                                                        {taxRates.map((tr) => (
                                                            <option
                                                                key={tr.id}
                                                                value={Number(
                                                                    tr.rate,
                                                                )}
                                                            >
                                                                {tr.name} (
                                                                {Number(
                                                                    tr.rate,
                                                                )}
                                                                %)
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-medium">
                                                    {(
                                                        item.quantity *
                                                        item.unit_price
                                                    ).toFixed(2)}
                                                </p>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        removeItem(
                                                            item.product_id,
                                                        )
                                                    }
                                                    className="text-xs text-destructive hover:text-destructive"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    ))}

                                    {/* Totals */}
                                    <div className="mt-4 space-y-1 text-sm">
                                        <div className="flex justify-between">
                                            <span>Subtotal</span>
                                            <span>{subtotal.toFixed(2)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Tax</span>
                                            <span>{taxTotal.toFixed(2)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Discount</span>
                                            <input
                                                type="number"
                                                min={0}
                                                value={discount}
                                                onChange={(e) =>
                                                    setDiscount(
                                                        Number(e.target.value),
                                                    )
                                                }
                                                className="ml-2 h-6 w-24 rounded border px-1 text-right text-xs"
                                            />
                                        </div>
                                        <div className="flex justify-between border-t pt-1 text-base font-semibold">
                                            <span>Total</span>
                                            <span>{total.toFixed(2)}</span>
                                        </div>
                                    </div>

                                    {/* Customer */}
                                    <div className="mt-4">
                                        <Label className="text-xs">
                                            Customer (optional)
                                        </Label>
                                        <select
                                            value={customer}
                                            onChange={(e) =>
                                                setCustomer(e.target.value)
                                            }
                                            className="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">Walk-in</option>
                                            {customers.map((c) => (
                                                <option key={c.id} value={c.id}>
                                                    {c.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    {/* Payment */}
                                    <div className="mt-4 space-y-2">
                                        <Label className="text-xs">
                                            Payment method
                                        </Label>
                                        <select
                                            value={paymentMethod}
                                            onChange={(e) =>
                                                setPaymentMethod(e.target.value)
                                            }
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">
                                                Select method
                                            </option>
                                            {paymentMethods.map((m) => (
                                                <option key={m.id} value={m.id}>
                                                    {m.name}
                                                </option>
                                            ))}
                                        </select>

                                        <div>
                                            <Label className="text-xs">
                                                Amount tendered
                                            </Label>
                                            <Input
                                                type="number"
                                                min={0}
                                                step="0.01"
                                                value={amountTendered}
                                                onChange={(e) =>
                                                    setAmountTendered(
                                                        Number(e.target.value),
                                                    )
                                                }
                                            />
                                            {amountTendered > 0 && (
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    Change: {change.toFixed(2)}
                                                </p>
                                            )}
                                        </div>

                                        <div>
                                            <Label className="text-xs">
                                                Note
                                            </Label>
                                            <Input
                                                type="text"
                                                value={note}
                                                onChange={(e) =>
                                                    setNote(e.target.value)
                                                }
                                            />
                                        </div>
                                    </div>

                                    <Button
                                        type="button"
                                        disabled={
                                            !canCheckOut || form.processing
                                        }
                                        onClick={submit}
                                        className="mt-4 w-full"
                                    >
                                        {form.processing
                                            ? 'Processing...'
                                            : isOnline
                                              ? 'Complete Sale'
                                              : 'Queue Sale (Offline)'}
                                    </Button>

                                    {!isOnline && (
                                        <p className="mt-2 text-center text-xs text-warning">
                                            Offline â€” {pendingCount} sale(s)
                                            queued for sync.
                                        </p>
                                    )}

                                    {isOnline && pendingCount > 0 && (
                                        <p className="mt-2 text-center text-xs text-commerce">
                                            {pendingCount} queued sale(s) will
                                            sync on reconnect.
                                        </p>
                                    )}

                                    {syncing && (
                                        <p className="mt-2 text-center text-xs text-muted-foreground">
                                            Syncing queued salesâ€¦
                                        </p>
                                    )}

                                    {expiredCount > 0 && (
                                        <p className="mt-2 text-center text-xs text-destructive">
                                            {expiredCount} expired queued
                                            sale(s) were discarded (older than
                                            48h).
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

function OpenRegisterForm({
    registers,
    onSuccess,
}: {
    registers: Register[];
    onSuccess: () => void;
}) {
    const [registerId, setRegisterId] = useState('');
    const [balance, setBalance] = useState(0);
    const form = useForm({
        register_id: '',
        branch_id: '',
        opening_balance: 0,
    });

    const submit = () => {
        const reg = registers.find((r) => r.id === registerId);
        form.transform(() => ({
            register_id: registerId,
            branch_id: reg?.branch_id ?? '',
            opening_balance: balance,
        }));
        form.post(register_sessionsStore.url(), {
            onSuccess,
        });
    };

    return (
        <div className="flex items-end gap-3">
            <div>
                <Label className="text-xs">Select register</Label>
                <select
                    value={registerId}
                    onChange={(e) => setRegisterId(e.target.value)}
                    className="mt-1 w-56 rounded-md border bg-background px-3 py-2 text-sm"
                >
                    <option value="">Select</option>
                    {registers.map((r) => (
                        <option key={r.id} value={r.id}>
                            {r.name}
                        </option>
                    ))}
                </select>
            </div>
            <div>
                <Label className="text-xs">Opening balance</Label>
                <Input
                    type="number"
                    min={0}
                    value={balance}
                    onChange={(e) => setBalance(Number(e.target.value))}
                    className="mt-1 w-32"
                />
            </div>
            <Button
                type="button"
                disabled={!registerId || form.processing}
                onClick={submit}
            >
                Open Register
            </Button>
        </div>
    );
}
