import { Head } from '@inertiajs/react';

export default function Show({ tenant }: { tenant: Record<string, unknown> }) {
    return (
        <>
            <Head title={String(tenant?.name ?? 'Tenant')} />
            <div className="p-6">
                <h1 className="text-2xl font-semibold">{String(tenant?.name ?? 'Tenant')}</h1>
            </div>
        </>
    );
}
