import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    accept,
    cancel,
    complete,
    confirmPayment,
    eraseCustomerData,
    markReady,
    reassignBranch,
    updateDeliveryTracking,
} from '@/actions/App/Http/Controllers/Web/StorefrontOrderManagementController';

interface Tenant {
    id: string;
    name: string;
    currency: string;
}

interface Branch {
    id: string;
    name: string;
    local_delivery_fee: string;
}

interface OrderItem {
    name: string;
    quantity: string;
    unit_price: string;
    line_total: string;
}

interface Order {
    id: string;
    reference_no: string;
    status: string;
    payment_status: string;
    payment_method: string;
    payment_reference: string | null;
    fulfillment_method: string;
    carrier_name: string | null;
    tracking_number: string | null;
    customer_name: string;
    customer_phone: string;
    customer_email: string | null;
    delivery_address: string | null;
    subtotal: string;
    tax_amount: string;
    delivery_fee: string;
    total_amount: string;
    branch: {
        id: string;
        name: string;
    };
    items: OrderItem[];
    created_at: string | null;
    personal_data_erased_at: string | null;
}

interface Props {
    tenant: Tenant;
    orders: Order[];
    branches: Branch[];
}

function formatPrice(price: number | string, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(Number(price));
}

function formatStatus(status: string): string {
    return status.replaceAll('_', ' ');
}

export default function StorefrontOrdersPage({ tenant, orders, branches }: Props) {
    const openOrders = orders.filter(
        (order) => !['cancelled', 'completed'].includes(order.status),
    ).length;

    return (
        <>
            <Head title={`Online orders · ${tenant.name}`} />

            <div className="space-y-6">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                            Storefront
                        </p>
                        <h1 className="mt-2 text-3xl font-semibold tracking-tight text-balance">
                            Online orders
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Confirm offline payments, manage fulfillment, and keep reserved stock in sync.
                        </p>
                    </div>
                    <div className="rounded-xl bg-muted/60 px-4 py-3">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Open orders
                        </p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">{openOrders}</p>
                    </div>
                </header>

                {orders.length === 0 ? (
                    <section className="rounded-2xl border border-border/70 bg-card px-6 py-16 text-center">
                        <h2 className="text-lg font-semibold">No online orders yet</h2>
                        <p className="mt-2 text-sm text-muted-foreground">
                            New customer orders will appear here.
                        </p>
                    </section>
                ) : (
                    <section aria-label="Online orders" className="space-y-4">
                        {orders.map((order) => (
                            <article
                                key={order.id}
                                className="rounded-2xl border border-border/70 bg-card p-5 sm:p-6"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p className="font-mono text-sm font-semibold">
                                            {order.reference_no}
                                        </p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {order.customer_name} · {order.customer_phone}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {order.created_at
                                                ? new Date(order.created_at).toLocaleString()
                                                : ''}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-2 text-xs font-medium capitalize">
                                        <span className="rounded-full bg-muted px-3 py-1.5">
                                            {formatStatus(order.status)}
                                        </span>
                                        <span className="rounded-full bg-muted px-3 py-1.5">
                                            {formatStatus(order.payment_status)} · {formatStatus(order.payment_method)}
                                        </span>
                                    </div>
                                </div>

                                <div className="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                                    <div>
                                        <ul className="divide-y divide-border/70">
                                            {order.items.map((item, index) => (
                                                <li
                                                    key={`${item.name}-${index}`}
                                                    className="flex justify-between gap-4 py-3 text-sm first:pt-0"
                                                >
                                                    <span>
                                                        {item.name}
                                                        <span className="ml-2 text-muted-foreground">
                                                            × {item.quantity}
                                                        </span>
                                                    </span>
                                                    <span className="shrink-0 font-medium tabular-nums">
                                                        {formatPrice(item.line_total, tenant.currency)}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>

                                        <div className="mt-4 flex flex-wrap gap-x-6 gap-y-2 border-t border-border/70 pt-4 text-sm">
                                            <span>
                                                <span className="text-muted-foreground">Fulfillment: </span>
                                                <span className="font-medium capitalize">
                                                    {formatStatus(order.fulfillment_method)}
                                                </span>
                                            </span>
                                            <span>
                                                <span className="text-muted-foreground">Branch: </span>
                                                <span className="font-medium">{order.branch.name}</span>
                                            </span>
                                        </div>

                                        {order.delivery_address && (
                                            <p className="mt-3 text-sm leading-6">
                                                <span className="text-muted-foreground">Delivery address: </span>
                                                {order.delivery_address}
                                            </p>
                                        )}
                                        {order.fulfillment_method === 'local_delivery' && !['cancelled', 'completed'].includes(order.status) && (
                                            <Form
                                                {...updateDeliveryTracking.form({ tenant: tenant.id, storefrontOrder: order.id })}
                                                className="mt-4 grid gap-3 rounded-xl border border-border/70 p-4 sm:grid-cols-2"
                                            >
                                                {({ processing }) => (
                                                    <>
                                                        <label className="grid gap-2 text-sm font-medium">
                                                            Carrier
                                                            <input
                                                                name="carrier_name"
                                                                defaultValue={order.carrier_name ?? ''}
                                                                maxLength={255}
                                                                className="h-10 rounded-md border border-input bg-background px-3 font-normal"
                                                            />
                                                        </label>
                                                        <label className="grid gap-2 text-sm font-medium">
                                                            Tracking number
                                                            <input
                                                                name="tracking_number"
                                                                defaultValue={order.tracking_number ?? ''}
                                                                maxLength={255}
                                                                className="h-10 rounded-md border border-input bg-background px-3 font-normal"
                                                            />
                                                        </label>
                                                        <Button type="submit" disabled={processing} size="sm" className="sm:col-span-2 sm:w-fit">
                                                            Save delivery tracking
                                                        </Button>
                                                    </>
                                                )}
                                            </Form>
                                        )}
                                        {order.payment_reference && (
                                            <p className="mt-3 text-sm">
                                                <span className="text-muted-foreground">Payment reference: </span>
                                                {order.payment_reference}
                                            </p>
                                        )}
                                    </div>

                                    <aside className="rounded-xl bg-muted/40 p-4">
                                        <dl className="space-y-2 text-sm">
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-muted-foreground">Products</dt>
                                                <dd className="font-medium tabular-nums">
                                                    {formatPrice(order.subtotal, tenant.currency)}
                                                </dd>
                                            </div>
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-muted-foreground">Tax</dt>
                                                <dd className="font-medium tabular-nums">
                                                    {formatPrice(order.tax_amount, tenant.currency)}
                                                </dd>
                                            </div>
                                            <div className="flex justify-between gap-3">
                                                <dt className="text-muted-foreground">Delivery</dt>
                                                <dd className="font-medium tabular-nums">
                                                    {formatPrice(order.delivery_fee, tenant.currency)}
                                                </dd>
                                            </div>
                                            <div className="flex justify-between gap-3 border-t border-border/70 pt-3 text-base">
                                                <dt className="font-semibold">Total</dt>
                                                <dd className="font-semibold tabular-nums">
                                                    {formatPrice(order.total_amount, tenant.currency)}
                                                </dd>
                                            </div>
                                        </dl>

                                        {order.status === 'pending' && (
                                            <Form
                                                {...accept.form({ tenant: tenant.id, storefrontOrder: order.id })}
                                                className="mt-4"
                                            >
                                                {({ processing }) => (
                                                    <Button type="submit" disabled={processing} className="min-h-11 w-full">
                                                        Accept order
                                                    </Button>
                                                )}
                                            </Form>
                                        )}

                                        {order.status === 'accepted' && (
                                            <Form
                                                {...markReady.form({ tenant: tenant.id, storefrontOrder: order.id })}
                                                className="mt-4"
                                            >
                                                {({ processing }) => (
                                                    <Button type="submit" disabled={processing} className="min-h-11 w-full">
                                                        Mark ready
                                                    </Button>
                                                )}
                                            </Form>
                                        )}

                                        {order.status === 'ready' && order.payment_status === 'unpaid' && (
                                            <Form
                                                {...confirmPayment.form({ tenant: tenant.id, storefrontOrder: order.id })}
                                                className="mt-4 space-y-3"
                                            >
                                                {({ errors, processing }) => (
                                                    <>
                                                        {order.payment_method === 'bank_transfer' && (
                                                            <label className="block space-y-1.5 text-sm font-medium">
                                                                Bank reference
                                                                <input
                                                                    name="payment_reference"
                                                                    required
                                                                    maxLength={255}
                                                                    className="h-11 w-full rounded-md border border-input bg-background px-3 font-normal focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                                />
                                                                {errors.payment_reference && (
                                                                    <span className="block font-normal text-destructive">
                                                                        {errors.payment_reference}
                                                                    </span>
                                                                )}
                                                            </label>
                                                        )}
                                                        <Button type="submit" disabled={processing} className="min-h-11 w-full">
                                                            {order.payment_method === 'cash'
                                                                ? 'Record cash received'
                                                                : 'Confirm bank transfer'}
                                                        </Button>
                                                    </>
                                                )}
                                            </Form>
                                        )}

                                        {order.status === 'ready' && order.payment_status === 'paid' && (
                                            <Form
                                                {...complete.form({ tenant: tenant.id, storefrontOrder: order.id })}
                                                className="mt-4"
                                            >
                                                {({ processing }) => (
                                                    <Button type="submit" disabled={processing} className="min-h-11 w-full">
                                                        Complete order
                                                    </Button>
                                                )}
                                            </Form>
                                        )}

                                        {branches.length > 1
                                            && order.payment_status === 'unpaid'
                                            && !['cancelled', 'completed'].includes(order.status) && (
                                                <Form
                                                    {...reassignBranch.form({ tenant: tenant.id, storefrontOrder: order.id })}
                                                    className="mt-4 space-y-3 border-t border-border/70 pt-4"
                                                >
                                                    {({ errors, processing }) => (
                                                        <>
                                                            <label className="block space-y-1.5 text-sm font-medium">
                                                                Reassign branch
                                                                <select
                                                                    name="branch_id"
                                                                    defaultValue={order.branch.id}
                                                                    className="h-11 w-full rounded-md border border-input bg-background px-3 font-normal focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                                >
                                                                    {branches.map((branch) => (
                                                                        <option key={branch.id} value={branch.id}>
                                                                            {branch.name}
                                                                        </option>
                                                                    ))}
                                                                </select>
                                                                {errors.branch_id && (
                                                                    <span className="block font-normal text-destructive">
                                                                        {errors.branch_id}
                                                                    </span>
                                                                )}
                                                            </label>
                                                            <Button type="submit" variant="outline" disabled={processing} className="min-h-11 w-full">
                                                                Reassign if stock is available
                                                            </Button>
                                                        </>
                                                    )}
                                                </Form>
                                            )}

                                        {!['cancelled', 'completed'].includes(order.status) && (
                                            <details className="mt-4 border-t border-border/70 pt-4">
                                                <summary className="min-h-11 cursor-pointer py-3 text-sm font-medium focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none">
                                                    Cancel order
                                                </summary>
                                                <Form
                                                    {...cancel.form({ tenant: tenant.id, storefrontOrder: order.id })}
                                                    className="space-y-3"
                                                >
                                                    {({ errors, processing }) => (
                                                        <>
                                                            <label className="block space-y-1.5 text-sm font-medium">
                                                                Reason
                                                                <textarea
                                                                    name="cancellation_note"
                                                                    required
                                                                    maxLength={2000}
                                                                    rows={2}
                                                                    className="w-full rounded-md border border-input bg-background px-3 py-2 font-normal focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                                />
                                                                {errors.cancellation_note && (
                                                                    <span className="block font-normal text-destructive">
                                                                        {errors.cancellation_note}
                                                                    </span>
                                                                )}
                                                            </label>
                                                            {order.payment_status === 'paid' && (
                                                                <>
                                                                    <label className="flex min-h-11 items-center gap-3 text-sm">
                                                                        <input
                                                                            type="checkbox"
                                                                            name="refund_handled_externally"
                                                                            value="1"
                                                                            required
                                                                            className="size-4 accent-primary"
                                                                        />
                                                                        External refund has been handled
                                                                    </label>
                                                                    <label className="block space-y-1.5 text-sm font-medium">
                                                                        Refund record
                                                                        <textarea
                                                                            name="refund_note"
                                                                            required
                                                                            maxLength={2000}
                                                                            rows={2}
                                                                            className="w-full rounded-md border border-input bg-background px-3 py-2 font-normal focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                                                        />
                                                                        {errors.refund_note && (
                                                                            <span className="block font-normal text-destructive">
                                                                                {errors.refund_note}
                                                                            </span>
                                                                        )}
                                                                    </label>
                                                                </>
                                                            )}
                                                            {errors.refund_handled_externally && (
                                                                <p role="alert" className="text-sm text-destructive">
                                                                    {errors.refund_handled_externally}
                                                                </p>
                                                            )}
                                                            <Button
                                                                type="submit"
                                                                variant="destructive"
                                                                disabled={processing}
                                                                className="min-h-11 w-full"
                                                            >
                                                                Confirm cancellation
                                                            </Button>
                                                        </>
                                                    )}
                                                </Form>
                                            </details>
                                        )}
                                        {['completed', 'cancelled'].includes(order.status) && order.personal_data_erased_at === null && (
                                            <Form
                                                {...eraseCustomerData.form({ tenant: tenant.id, storefrontOrder: order.id })}
                                                className="mt-4 border-t border-border/70 pt-4"
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        type="submit"
                                                        variant="outline"
                                                        disabled={processing}
                                                        className="min-h-11 w-full"
                                                    >
                                                        Erase customer personal data
                                                    </Button>
                                                )}
                                            </Form>
                                        )}
                                    </aside>
                                </div>
                            </article>
                        ))}
                    </section>
                )}
            </div>
        </>
    );
}
