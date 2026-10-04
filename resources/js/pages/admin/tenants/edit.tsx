import { Head } from '@inertiajs/react';

export default function Edit({ tenant }: { tenant: Record<string, unknown> }) {
    return (
        <>
            <Head title={`Edit ${String(tenant?.name ?? 'tenant')}`} />
            <div className="p-6">
                <h1 className="text-2xl font-semibold">Edit tenant</h1>
            </div>
        </>
    );
}
