import { Head, Link } from '@inertiajs/react';
import SubscriptionNavigation from '@/components/admin/subscription-navigation';
import { create, show } from '@/routes/admin/tenants';

interface Tenant {
    id: string;
    name: string;
    slug: string;
    email: string;
    plan_name: string | null;
    subscription_status: string;
    created_at: string | null;
}

export default function Index({ tenants }: { tenants: Tenant[] }) {
    return (
        <>
            <Head title="Tenant Administration" />
            <div className="space-y-6 p-6">
                <SubscriptionNavigation />
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Tenants</h1>
                    <Link
                        href={create.url()}
                        className="rounded bg-slate-900 px-3 py-2 text-sm text-white"
                    >
                        New tenant
                    </Link>
                </div>

                <div className="rounded border bg-white p-4 shadow-sm">
                    <ul className="divide-y">
                        {tenants.length === 0 && (
                            <li className="py-6 text-sm text-slate-500">
                                No tenants have been created.
                            </li>
                        )}
                        {tenants.map((tenant) => (
                            <li
                                key={tenant.id}
                                className="flex flex-wrap items-center justify-between gap-4 py-4"
                            >
                                <div>
                                    <div className="font-medium">
                                        {tenant.name}
                                    </div>
                                    <div className="text-sm text-slate-500">
                                        {tenant.email} · {tenant.slug}
                                    </div>
                                    <div className="mt-1 text-sm text-slate-600">
                                        {tenant.plan_name ?? 'No plan'} ·{' '}
                                        {tenant.subscription_status}
                                    </div>
                                </div>
                                <Link
                                    href={show.url(tenant.id)}
                                    className="text-sm font-medium text-slate-700"
                                >
                                    Manage subscription
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </>
    );
}
