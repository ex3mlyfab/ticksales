import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function TenantSettingsPage() {
    return (
        <>
            <Head title="Tenant settings" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Tenant settings"
                    description="Adjust the default operating rules for this tenant."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <h2 className="text-lg font-semibold">Configuration</h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Tax defaults, invoice prefixes, stock alert thresholds, and checkout requirements
                        can be configured here.
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
