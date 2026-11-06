import { Form, Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import TenantStorefrontSettingsController from '@/actions/App/Http/Controllers/Settings/TenantStorefrontSettingsController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Branch = {
    id: string;
    name: string;
    is_main: boolean;
    local_delivery_fee: string;
};

type Props = {
    currency: string;
    storefront: {
        is_storefront_enabled: boolean;
        is_storefront_analytics_enabled: boolean;
        bank_transfer_instructions: string | null;
        storefront_description: string | null;
        storefront_email: string | null;
        storefront_phone: string | null;
        storefront_policy: string | null;
        storefront_pickup_enabled: boolean;
        storefront_delivery_enabled: boolean;
        paystack_enabled: boolean;
        paystack_configured: boolean;
    };
    branches: Branch[];
    canUpdate: boolean;
};

export default function TenantSettingsPage({
    currency,
    storefront,
    branches,
    canUpdate,
}: Props) {
    const form = useForm({
        is_storefront_enabled: storefront.is_storefront_enabled,
        is_storefront_analytics_enabled:
            storefront.is_storefront_analytics_enabled,
        bank_transfer_instructions:
            storefront.bank_transfer_instructions ?? '',
        storefront_description:
            storefront.storefront_description ?? '',
        storefront_email: storefront.storefront_email ?? '',
        storefront_phone: storefront.storefront_phone ?? '',
        storefront_policy: storefront.storefront_policy ?? '',
        storefront_pickup_enabled: storefront.storefront_pickup_enabled,
        storefront_delivery_enabled: storefront.storefront_delivery_enabled,
        paystack_enabled: storefront.paystack_enabled,
        paystack_secret: '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.put(TenantStorefrontSettingsController.update.url(), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Tenant settings" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Tenant settings"
                    description="Adjust the default operating rules for this tenant."
                />

                <form
                    onSubmit={submit}
                    className="rounded-xl border bg-card p-6 shadow-sm"
                >
                    <div className="space-y-5">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Online storefront
                            </h2>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Control whether visitors can browse published
                                products in this tenant's public store.
                            </p>
                        </div>

                        <div className="flex items-start gap-3">
                            <Checkbox
                                id="is_storefront_enabled"
                                checked={form.data.is_storefront_enabled}
                                onCheckedChange={(checked) =>
                                    form.setData(
                                        'is_storefront_enabled',
                                        checked === true,
                                    )
                                }
                                disabled={!canUpdate || form.processing}
                            />
                            <div className="grid gap-1">
                                <Label htmlFor="is_storefront_enabled">
                                    Storefront is enabled
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    When disabled, the catalog and product
                                    pages are not publicly accessible.
                                </p>
                            </div>
                        </div>

                        <InputError message={form.errors.is_storefront_enabled} />

                        <div className="flex items-start gap-3">
                            <Checkbox
                                id="is_storefront_analytics_enabled"
                                checked={form.data.is_storefront_analytics_enabled}
                                onCheckedChange={(checked) =>
                                    form.setData(
                                        'is_storefront_analytics_enabled',
                                        checked === true,
                                    )
                                }
                                disabled={!canUpdate || form.processing}
                            />
                            <div className="grid gap-1">
                                <Label htmlFor="is_storefront_analytics_enabled">
                                    Storefront analytics
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    Collect pseudonymous funnel and product
                                    events only for this tenant. Used to improve
                                    the store experience; events are removed
                                    after 90 days and never include payment,
                                    email, phone, IP, or form contents. Disabled by default.
                                </p>
                            </div>
                        </div>

                        <InputError
                            message={form.errors.is_storefront_analytics_enabled}
                        />

                        <div className="grid gap-2">
                            <Label htmlFor="bank_transfer_instructions">
                                Bank transfer instructions
                            </Label>
                            <Textarea
                                id="bank_transfer_instructions"
                                value={form.data.bank_transfer_instructions}
                                onChange={(event) =>
                                    form.setData(
                                        'bank_transfer_instructions',
                                        event.target.value,
                                    )
                                }
                                disabled={!canUpdate || form.processing}
                                maxLength={2000}
                                rows={4}
                                placeholder="Add the details customers need to pay by bank transfer."
                            />
                            <InputError
                                message={form.errors.bank_transfer_instructions}
                            />
                            <p className="text-sm text-muted-foreground">
                                Shown to customers who select bank transfer.
                                Do not include passwords or API credentials.
                            </p>
                        </div>

                        <div className="grid gap-4 border-t border-border/70 pt-5">
                            <div>
                                <h3 className="font-semibold">Paystack online checkout</h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Merchant credentials are stored encrypted and are separate from platform subscription billing.
                                </p>
                            </div>
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="paystack_enabled"
                                    checked={form.data.paystack_enabled}
                                    onCheckedChange={(checked) =>
                                        form.setData('paystack_enabled', checked === true)
                                    }
                                    disabled={!canUpdate || form.processing}
                                />
                                <Label htmlFor="paystack_enabled">Accept online Paystack payments</Label>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="paystack_secret">
                                    Paystack secret key
                                    {storefront.paystack_configured ? ' (configured)' : ''}
                                </Label>
                                <Input
                                    id="paystack_secret"
                                    type="password"
                                    value={form.data.paystack_secret}
                                    onChange={(event) =>
                                        form.setData('paystack_secret', event.target.value)
                                    }
                                    disabled={!canUpdate || form.processing}
                                    maxLength={255}
                                    placeholder={storefront.paystack_configured
                                        ? 'Leave blank to keep the current secret'
                                        : 'sk_live_...'}
                                />
                                <InputError message={form.errors.paystack_secret} />
                            </div>
                        </div>

                        <div className="grid gap-5 border-t border-border/70 pt-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="storefront_description">Public description</Label>
                                <Textarea
                                    id="storefront_description"
                                    value={form.data.storefront_description}
                                    onChange={(event) => form.setData('storefront_description', event.target.value)}
                                    disabled={!canUpdate || form.processing}
                                    maxLength={5000}
                                    rows={4}
                                />
                                <InputError message={form.errors.storefront_description} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="storefront_policy">Store policy</Label>
                                <Textarea
                                    id="storefront_policy"
                                    value={form.data.storefront_policy}
                                    onChange={(event) => form.setData('storefront_policy', event.target.value)}
                                    disabled={!canUpdate || form.processing}
                                    maxLength={5000}
                                    rows={4}
                                />
                                <InputError message={form.errors.storefront_policy} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="storefront_email">Public email</Label>
                                <Input
                                    id="storefront_email"
                                    type="email"
                                    value={form.data.storefront_email}
                                    onChange={(event) => form.setData('storefront_email', event.target.value)}
                                    disabled={!canUpdate || form.processing}
                                    maxLength={255}
                                />
                                <InputError message={form.errors.storefront_email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="storefront_phone">Public phone</Label>
                                <Input
                                    id="storefront_phone"
                                    value={form.data.storefront_phone}
                                    onChange={(event) => form.setData('storefront_phone', event.target.value)}
                                    disabled={!canUpdate || form.processing}
                                    maxLength={50}
                                />
                                <InputError message={form.errors.storefront_phone} />
                            </div>
                        </div>

                        <fieldset className="grid gap-3 border-t border-border/70 pt-5">
                            <legend className="text-sm font-medium">Fulfillment methods</legend>
                            <label className="flex items-center gap-3 text-sm">
                                <Checkbox
                                    checked={form.data.storefront_pickup_enabled}
                                    onCheckedChange={(checked) => form.setData('storefront_pickup_enabled', checked === true)}
                                    disabled={!canUpdate || form.processing}
                                />
                                Pickup
                            </label>
                            <label className="flex items-center gap-3 text-sm">
                                <Checkbox
                                    checked={form.data.storefront_delivery_enabled}
                                    onCheckedChange={(checked) => form.setData('storefront_delivery_enabled', checked === true)}
                                    disabled={!canUpdate || form.processing}
                                />
                                Local delivery
                            </label>
                            <InputError message={form.errors.storefront_pickup_enabled} />
                            <InputError message={form.errors.storefront_delivery_enabled} />
                        </fieldset>

                        {canUpdate && (
                            <Button disabled={form.processing}>
                                {form.processing
                                    ? 'Saving...'
                                    : 'Save storefront settings'}
                            </Button>
                        )}
                    </div>
                </form>

                <section className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="space-y-2">
                        <h2 className="text-lg font-semibold">
                            Local delivery fees
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Set a fee for each active branch. Checkout uses the
                            main branch fee; staff may reassign an order only
                            when the new branch has sufficient stock.
                        </p>
                    </div>

                    {branches.length === 0 ? (
                        <p className="mt-5 rounded-lg bg-muted/50 p-4 text-sm text-muted-foreground">
                            Create and activate a branch before configuring
                            local delivery.
                        </p>
                    ) : (
                        <ul className="mt-5 divide-y divide-border/70">
                            {branches.map((branch) => (
                                <li
                                    key={branch.id}
                                    className="flex flex-col gap-3 py-4 sm:flex-row sm:items-end sm:justify-between"
                                >
                                    <div className="space-y-1">
                                        <p className="font-medium">
                                            {branch.name}
                                        </p>
                                        {branch.is_main && (
                                            <p className="text-xs text-muted-foreground">
                                                Main branch
                                            </p>
                                        )}
                                    </div>

                                    {canUpdate ? (
                                        <Form
                                            {...TenantStorefrontSettingsController.updateDeliveryFee.form(
                                                { branch: branch.id },
                                            )}
                                            className="flex flex-wrap items-end gap-3"
                                        >
                                            {({ errors, processing }) => (
                                                <>
                                                    <div className="grid gap-2">
                                                        <Label
                                                            htmlFor={`delivery-fee-${branch.id}`}
                                                        >
                                                            Delivery fee (
                                                            {currency})
                                                        </Label>
                                                        <Input
                                                            id={`delivery-fee-${branch.id}`}
                                                            type="number"
                                                            name="local_delivery_fee"
                                                            min="0"
                                                            step="0.0001"
                                                            defaultValue={
                                                                branch.local_delivery_fee
                                                            }
                                                            required
                                                            className="w-full sm:w-48"
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.local_delivery_fee
                                                            }
                                                        />
                                                    </div>
                                                    <Button
                                                        disabled={processing}
                                                    >
                                                        Save fee
                                                    </Button>
                                                </>
                                            )}
                                        </Form>
                                    ) : (
                                        <p className="text-sm tabular-nums">
                                            {branch.local_delivery_fee}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <h2 className="text-lg font-semibold">Configuration</h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Tax defaults, invoice prefixes, stock alert thresholds,
                        and checkout requirements can be configured here.
                    </p>
                </div>
            </div>
        </>
    );
}

TenantSettingsPage.layout = {
    breadcrumbs: [
        {
            title: 'Tenant settings',
            href: '/settings/tenant',
        },
    ],
};
