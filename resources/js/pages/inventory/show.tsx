import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

interface InventoryRow {
    id: string;
    branch_name: string | null;
    variant_name: string | null;
    quantity: number;
}

interface Product {
    id: string;
    name: string;
}

interface Props {
    product: Product;
    inventory: InventoryRow[];
}

export default function InventoryShowPage({ product, inventory }: Props) {
    return (
        <>
            <Head title={`Stock — ${product.name}`} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={`Stock: ${product.name}`}
                    description="View all stock records for this product across branches."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <h2 className="text-lg font-semibold">Stock Records</h2>

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
                                            No stock records found for this product.
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
    ],
};