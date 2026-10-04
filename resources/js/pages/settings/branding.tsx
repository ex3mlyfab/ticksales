import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function TenantBrandingPage() {
    return (
        <>
            <Head title="Branding" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Branding"
                    description="Update your tenant brand colors, logo, and visual theme."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <h2 className="text-lg font-semibold">Brand customisation</h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Configure the logo, accent colors, sidebar styling, and theme preset for your
                        storefront or admin experience.
                    </p>
                </div>
            </div>
        </>
    );
}

TenantBrandingPage.layout = {
    breadcrumbs: [
        {
            title: 'Branding',
            href: '/settings/branding',
        },
    ],
};
