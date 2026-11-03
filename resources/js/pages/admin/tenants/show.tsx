import { Head, Link, useForm } from '@inertiajs/react';
import SubscriptionNavigation from '@/components/admin/subscription-navigation';
import { edit, paystackCheckout } from '@/routes/admin/tenants';
import { create as createPaymentMethod } from '@/routes/admin/subscription-payment-methods';
import { store as recordPayment } from '@/routes/admin/tenants/payments';

interface Tenant {
    id: string;
    name: string;
    slug: string;
    email: string;
    currency: string;
    plan: string | null;
    plan_name: string | null;
    subscription_status: string;
    subscription_ends_at: string | null;
}

interface Plan {
    id: string;
    name: string;
    display_name: string;
    price_monthly: string;
    is_active: boolean;
}

interface PaymentMethod {
    id: string;
    name: string;
    instructions: string;
}

interface Payment {
    id: string;
    plan: string | null;
    amount: string;
    currency: string;
    payment_method: string;
    reference: string | null;
    paid_at: string;
    period_ends_at: string;
    notes: string | null;
}

interface PendingCheckout {
    reference: string;
    plan: string | null;
    amount: string;
    currency: string;
    checkout_url: string | null;
    created_at: string | null;
}

interface Props {
    tenant: Tenant;
    plans: Plan[];
    payment_methods: PaymentMethod[];
    payments: Payment[];
    pending_checkouts: PendingCheckout[];
    paystack_enabled: boolean;
    paystack_currency_supported: boolean;
}

export default function Show({
    tenant,
    plans,
    payment_methods,
    payments,
    pending_checkouts,
    paystack_enabled,
    paystack_currency_supported,
}: Props) {
    const activePlans = plans.filter((plan) => plan.is_active);
    const initialPlan =
        activePlans.find((plan) => plan.name === tenant.plan) ?? activePlans[0];
    const form = useForm({
        plan_id: initialPlan?.id ?? '',
        payment_method_id: payment_methods[0]?.id ?? '',
        amount: initialPlan?.price_monthly ?? '',
        paid_at: new Date().toISOString().slice(0, 10),
        reference: '',
        notes: '',
        confirmed: false,
    });
    const checkoutForm = useForm<{
        plan_id: string;
        payment?: string;
    }>({ plan_id: initialPlan?.id ?? '' });
    const selectedMethod = payment_methods.find(
        (method) => method.id === form.data.payment_method_id,
    );

    return (
        <>
            <Head title={`${tenant.name} subscription`} />
            <div className="space-y-6 p-6">
                <SubscriptionNavigation />
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {tenant.name}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {tenant.email} · {tenant.slug}
                        </p>
                    </div>
                    <Link
                        href={edit.url(tenant.id)}
                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Edit tenant and plan
                    </Link>
                </header>

                <section className="grid gap-4 sm:grid-cols-3">
                    <div className="rounded-xl border bg-card p-5">
                        <p className="text-sm text-muted-foreground">
                            Current plan
                        </p>
                        <p className="mt-1 font-semibold">
                            {tenant.plan_name ?? 'No plan assigned'}
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-5">
                        <p className="text-sm text-muted-foreground">
                            Subscription status
                        </p>
                        <p className="mt-1 font-semibold capitalize">
                            {tenant.subscription_status}
                        </p>
                    </div>
                    <div className="rounded-xl border bg-card p-5">
                        <p className="text-sm text-muted-foreground">
                            Paid through
                        </p>
                        <p className="mt-1 font-semibold">
                            {tenant.subscription_ends_at ?? 'Not set'}
                        </p>
                    </div>
                </section>

                <section className="max-w-3xl space-y-4 rounded-xl border bg-card p-5">
                    <div>
                        <h2 className="text-lg font-semibold">
                            Pay online with Paystack
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create a secure hosted checkout for the selected
                            plan. The subscription changes only after Paystack
                            confirms payment.
                        </p>
                    </div>
                    {!paystack_enabled ? (
                        <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                            Paystack is not enabled. Configure{' '}
                            <code>PAYSTACK_ENABLED=true</code> and{' '}
                            <code>PAYSTACK_SECRET_KEY</code> on the server, and
                            set the public webhook URL to{' '}
                            <code>/api/paystack/webhook</code>.
                        </p>
                    ) : !paystack_currency_supported ? (
                        <p className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                            Paystack checkout does not support this tenant
                            currency ({tenant.currency}). Change the tenant
                            currency or use manual collection.
                        </p>
                    ) : activePlans.length === 0 ? (
                        <p className="rounded-lg border border-dashed p-4 text-sm">
                            Create or activate a subscription plan before
                            starting checkout.
                        </p>
                    ) : (
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                checkoutForm.post(
                                    paystackCheckout.url(tenant.id),
                                );
                            }}
                            className="space-y-4"
                        >
                            <div className="grid gap-2">
                                <label
                                    htmlFor="paystack_plan_id"
                                    className="text-sm font-medium"
                                >
                                    Plan to charge
                                </label>
                                <select
                                    id="paystack_plan_id"
                                    value={checkoutForm.data.plan_id}
                                    onChange={(event) =>
                                        checkoutForm.setData(
                                            'plan_id',
                                            event.target.value,
                                        )
                                    }
                                    className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                >
                                    {activePlans.map((plan) => (
                                        <option key={plan.id} value={plan.id}>
                                            {plan.display_name} —{' '}
                                            {tenant.currency}{' '}
                                            {plan.price_monthly}/month
                                        </option>
                                    ))}
                                </select>
                                {checkoutForm.errors.plan_id && (
                                    <p className="text-sm text-destructive">
                                        {checkoutForm.errors.plan_id}
                                    </p>
                                )}
                                {checkoutForm.errors.payment && (
                                    <p className="text-sm text-destructive">
                                        {checkoutForm.errors.payment}
                                    </p>
                                )}
                            </div>
                            <button
                                type="submit"
                                disabled={checkoutForm.processing}
                                className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                            >
                                {checkoutForm.processing
                                    ? 'Starting checkout…'
                                    : 'Continue to secure Paystack checkout'}
                            </button>
                        </form>
                    )}
                    {pending_checkouts.length > 0 && (
                        <div className="space-y-2 border-t pt-4">
                            <h3 className="text-sm font-medium">
                                Pending checkouts
                            </h3>
                            {pending_checkouts.map((checkout) => (
                                <div
                                    key={checkout.reference}
                                    className="flex flex-wrap items-center justify-between gap-3 text-sm"
                                >
                                    <span>
                                        {checkout.plan ?? 'Subscription'} ·{' '}
                                        {checkout.currency} {checkout.amount}
                                        <span className="ml-2 text-muted-foreground">
                                            {checkout.created_at}
                                        </span>
                                    </span>
                                    {checkout.checkout_url && (
                                        <a
                                            href={checkout.checkout_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-primary hover:underline"
                                        >
                                            Resume checkout
                                        </a>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </section>

                <section className="grid gap-6 xl:grid-cols-2">
                    <div className="space-y-4 rounded-xl border bg-card p-5">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Record a subscription payment
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Record a payment only after you have verified it
                                was received. This manual workflow does not
                                charge a customer.
                            </p>
                        </div>
                        {payment_methods.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-4 text-sm">
                                <p>No collection methods are active yet.</p>
                                <Link
                                    href={createPaymentMethod.url()}
                                    className="mt-2 inline-block text-primary hover:underline"
                                >
                                    Configure a payment method
                                </Link>
                            </div>
                        ) : activePlans.length === 0 ? (
                            <p className="rounded-lg border border-dashed p-4 text-sm">
                                Create or activate a subscription plan before
                                recording a payment.
                            </p>
                        ) : (
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    form.post(recordPayment.url(tenant.id));
                                }}
                                className="space-y-4"
                            >
                                <div className="grid gap-2">
                                    <label
                                        htmlFor="plan_id"
                                        className="text-sm font-medium"
                                    >
                                        Plan to apply
                                    </label>
                                    <select
                                        id="plan_id"
                                        value={form.data.plan_id}
                                        onChange={(event) => {
                                            const selectedPlan =
                                                activePlans.find(
                                                    (plan) =>
                                                        plan.id ===
                                                        event.target.value,
                                                );
                                            form.setData({
                                                ...form.data,
                                                plan_id: event.target.value,
                                                amount:
                                                    selectedPlan?.price_monthly ??
                                                    form.data.amount,
                                            });
                                        }}
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    >
                                        {activePlans.map((plan) => (
                                            <option
                                                key={plan.id}
                                                value={plan.id}
                                            >
                                                {plan.display_name} —{' '}
                                                {tenant.currency}{' '}
                                                {plan.price_monthly}/month
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.plan_id && (
                                        <p className="text-sm text-destructive">
                                            {form.errors.plan_id}
                                        </p>
                                    )}
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <label
                                            htmlFor="amount"
                                            className="text-sm font-medium"
                                        >
                                            Amount received ({tenant.currency})
                                        </label>
                                        <input
                                            id="amount"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={form.data.amount}
                                            onChange={(event) =>
                                                form.setData(
                                                    'amount',
                                                    event.target.value,
                                                )
                                            }
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        />
                                        {form.errors.amount && (
                                            <p className="text-sm text-destructive">
                                                {form.errors.amount}
                                            </p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <label
                                            htmlFor="paid_at"
                                            className="text-sm font-medium"
                                        >
                                            Date received
                                        </label>
                                        <input
                                            id="paid_at"
                                            type="date"
                                            value={form.data.paid_at}
                                            onChange={(event) =>
                                                form.setData(
                                                    'paid_at',
                                                    event.target.value,
                                                )
                                            }
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        />
                                        {form.errors.paid_at && (
                                            <p className="text-sm text-destructive">
                                                {form.errors.paid_at}
                                            </p>
                                        )}
                                    </div>
                                </div>
                                <div className="grid gap-2">
                                    <label
                                        htmlFor="payment_method_id"
                                        className="text-sm font-medium"
                                    >
                                        Payment method
                                    </label>
                                    <select
                                        id="payment_method_id"
                                        value={form.data.payment_method_id}
                                        onChange={(event) =>
                                            form.setData(
                                                'payment_method_id',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    >
                                        {payment_methods.map((method) => (
                                            <option
                                                key={method.id}
                                                value={method.id}
                                            >
                                                {method.name}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.payment_method_id && (
                                        <p className="text-sm text-destructive">
                                            {form.errors.payment_method_id}
                                        </p>
                                    )}
                                </div>
                                {selectedMethod && (
                                    <div className="rounded-lg bg-muted/50 p-4">
                                        <p className="text-sm font-medium">
                                            {selectedMethod.name} instructions
                                        </p>
                                        <p className="mt-1 text-sm whitespace-pre-wrap text-muted-foreground">
                                            {selectedMethod.instructions}
                                        </p>
                                    </div>
                                )}
                                <div className="grid gap-2">
                                    <label
                                        htmlFor="reference"
                                        className="text-sm font-medium"
                                    >
                                        Reference (optional)
                                    </label>
                                    <input
                                        id="reference"
                                        value={form.data.reference}
                                        onChange={(event) =>
                                            form.setData(
                                                'reference',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    />
                                    {form.errors.reference && (
                                        <p className="text-sm text-destructive">
                                            {form.errors.reference}
                                        </p>
                                    )}
                                </div>
                                <div className="grid gap-2">
                                    <label
                                        htmlFor="notes"
                                        className="text-sm font-medium"
                                    >
                                        Internal notes (optional)
                                    </label>
                                    <textarea
                                        id="notes"
                                        rows={3}
                                        value={form.data.notes}
                                        onChange={(event) =>
                                            form.setData(
                                                'notes',
                                                event.target.value,
                                            )
                                        }
                                        className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    />
                                    {form.errors.notes && (
                                        <p className="text-sm text-destructive">
                                            {form.errors.notes}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-1">
                                    <label className="flex items-start gap-2 text-sm">
                                        <input
                                            type="checkbox"
                                            checked={form.data.confirmed}
                                            onChange={(event) =>
                                                form.setData(
                                                    'confirmed',
                                                    event.target.checked,
                                                )
                                            }
                                            className="mt-1"
                                        />
                                        I have verified that this payment has
                                        been received.
                                    </label>
                                    {form.errors.confirmed && (
                                        <p className="text-sm text-destructive">
                                            {form.errors.confirmed}
                                        </p>
                                    )}
                                </div>
                                <button
                                    type="submit"
                                    disabled={
                                        form.processing || !form.data.confirmed
                                    }
                                    className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                                >
                                    {form.processing
                                        ? 'Recording…'
                                        : 'Record verified payment'}
                                </button>
                            </form>
                        )}
                    </div>

                    <div className="space-y-4 rounded-xl border bg-card p-5">
                        <h2 className="text-lg font-semibold">
                            Payment history
                        </h2>
                        <div className="divide-y">
                            {payments.map((payment) => (
                                <div
                                    key={payment.id}
                                    className="space-y-1 py-4 first:pt-0 last:pb-0"
                                >
                                    <div className="flex flex-wrap justify-between gap-2">
                                        <p className="font-medium">
                                            {payment.plan ??
                                                'Subscription plan'}
                                        </p>
                                        <p className="font-medium">
                                            {payment.currency} {payment.amount}
                                        </p>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {payment.payment_method} · received{' '}
                                        {payment.paid_at} · paid through{' '}
                                        {payment.period_ends_at}
                                    </p>
                                    {payment.reference && (
                                        <p className="text-sm text-muted-foreground">
                                            Reference: {payment.reference}
                                        </p>
                                    )}
                                    {payment.notes && (
                                        <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                            {payment.notes}
                                        </p>
                                    )}
                                </div>
                            ))}
                            {payments.length === 0 && (
                                <p className="py-6 text-sm text-muted-foreground">
                                    No subscription payments have been recorded.
                                </p>
                            )}
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}
