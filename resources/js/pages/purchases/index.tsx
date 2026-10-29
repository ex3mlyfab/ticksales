import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface Purchase {
    id: string;
    reference_no: string;
    status: string;
    purchase_date: string;
    supplier_name: string | null;
    branch_name: string | null;
    total: number;
    items: {
        product_name: string | null;
        quantity: number;
        unit_cost: number;
        line_total: number;
    }[];
    created_at: string;
}

interface Props {
    purchases: {
        data: Purchase[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function PurchaseIndexPage({ purchases }: Props) {
    return (
        <>
            <Head title="Purchases" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Purchases"
                    description="Record and manage supplier purchases."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Purchases</h2>
                        <Link
                            href={route('purchases.create')}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            New Purchase
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {purchases.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No purchases found.
                            </div>
                        ) : (
                            purchases.data.map((purchase) => (
                                <div
                                    key={purchase.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">{purchase.reference_no}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {purchase.supplier_name} • {purchase.branch_name} • {purchase.total} total
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {purchase.purchase_date}
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                purchase.status === 'received'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-yellow-100 text-yellow-800'
                                            }`}
                                        >
                                            {purchase.status}
                                        </span>
                                        <Link
                                            href={route('purchases.show', purchase.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            View
                                        </Link>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

PurchaseIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Purchases',
            href: '/purchases',
        },
    ],
};