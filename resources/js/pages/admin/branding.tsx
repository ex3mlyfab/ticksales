import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

import SubscriptionNavigation from '@/components/admin/subscription-navigation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/admin/branding';

type BrandingProps = {
    branding: {
        app_name: string;
        currency: string;
        logo_url: string | null;
    };
};

export default function Branding({ branding }: BrandingProps) {
    const { data, setData, post, processing, errors } = useForm({
        app_name: branding.app_name,
        currency: branding.currency,
        logo: null as File | null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(update.url(), {
            forceFormData: true,
        });
    };

    return (
        <>
            <Head title="Branding" />
            <div className="space-y-6 p-6">
                <SubscriptionNavigation />
                <div>
                    <h1 className="text-2xl font-semibold">Application branding</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Configure the name, logo, and currency used across the application.
                    </p>
                </div>

                <form onSubmit={submit} className="max-w-2xl space-y-6 rounded-xl border bg-card p-6 shadow-sm">
                    <div className="space-y-2">
                        <Label htmlFor="app_name">Application name</Label>
                        <Input
                            id="app_name"
                            value={data.app_name}
                            onChange={(event) => setData('app_name', event.target.value)}
                        />
                        {errors.app_name && <p className="text-sm text-destructive">{errors.app_name}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="currency">Currency</Label>
                        <Input
                            id="currency"
                            value={data.currency}
                            maxLength={3}
                            onChange={(event) => setData('currency', event.target.value.toUpperCase())}
                            placeholder="NGN"
                        />
                        <p className="text-xs text-muted-foreground">
                            A three-letter ISO currency code. Updating this sets the currency for all tenants.
                        </p>
                        {errors.currency && <p className="text-sm text-destructive">{errors.currency}</p>}
                    </div>

                    <div className="space-y-3">
                        <Label htmlFor="logo">Application logo</Label>
                        {branding.logo_url && (
                            <img
                                src={branding.logo_url}
                                alt="Current application logo"
                                className="size-16 rounded-xl border object-contain"
                            />
                        )}
                        <Input
                            id="logo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(event) => setData('logo', event.target.files?.[0] ?? null)}
                        />
                        {errors.logo && <p className="text-sm text-destructive">{errors.logo}</p>}
                    </div>

                    <Button type="submit" disabled={processing}>
                        Save branding
                    </Button>
                </form>
            </div>
        </>
    );
}
