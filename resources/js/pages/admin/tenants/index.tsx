import { Head } from '@inertiajs/react';

export default function Index({ tenants = [] }: { tenants: Array<Record<string, unknown>> }) {
    return (
        <>
            <Head title="Tenant Administration" />
            <div className="space-y-4 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Tenants</h1>
                    <a href="/admin/tenants/create" className="rounded bg-slate-900 px-3 py-2 text-sm text-white">
                        New tenant
                    </a>
                </div>

                <div className="rounded border bg-white p-4 shadow-sm">
                    <ul className="space-y-3">
                        {tenants.map((tenant) => (
                            <li key={String(tenant.id)} className="flex items-center justify-between rounded border p-3">
                                <div>
                                    <div className="font-medium">{String(tenant.name)}</div>
                                    <div className="text-sm text-slate-500">{String(tenant.slug)}</div>
                                </div>
                                <a href={`/admin/tenants/${tenant.id}`} className="text-sm font-medium text-slate-700">
                                    View
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </>
    );
}
