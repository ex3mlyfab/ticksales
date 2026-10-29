import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface Product {
    id: string;
    name: string;
}

interface InventoryRow {
    id: string;
    branch_name: string | null;
    variant_name: string | null;
    quantity: number;
}

interface Props {
    product: Product;
    inventory: InventoryRow[];
}

export default function InventoryShowPage({ product, inventory }: Props) {
    return (
        <>
            <Head title={`Inventory — ${product.name}`} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={product.name}
                    description="Current stock levels for this product across your branches."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Stock by Branch</h2>
                        <Link
                            href="/inventory"
                            className="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Back to inventory
                        </Link>
                    </div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b bg-muted/50">
                                <tr>
                                    <th className="px-3 py-2 text-left text-xs font-medium uppercase text-muted-foreground">Branch</th>
                                    <th className="px-3 py-2 text-left text-xs font-medium uppercase text-muted-foreground">Variant</th>
                                    <th className="px-3 py-2 text-right text-xs font-medium uppercase text-muted-foreground">Quantity</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {inventory.length === 0 ? (
                                    <tr>
                                        <td colSpan={3} className="px-3 py-8 text-center text-sm text-muted-foreground">
                                            No stock recorded for this product yet.
                                        </td>
                                    </tr>
                                ) : (
                                    inventory.map((row) => (
                                        <tr key={row.id} className="hover:bg-muted/30">
                                            <td className="px-3 py-2 text-sm">{row.branch_name}</td>
                                            <td className="px-3 py-2 text-sm">{row.variant_name || '—'}</td>
                                            <td className="px-3 py-2 text-right text-sm font-medium">{row.quantity}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}

InventoryShowPage.layout = {
    breadcrumbs: [
        {
            title: 'Inventory',
            href: '/inventory',
        },
        {
            title: product?.name ?? 'Product',
        },
    ],
};
