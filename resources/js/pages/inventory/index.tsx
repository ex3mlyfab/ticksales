import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface InventoryRow {
    id: string;
    product_name: string | null;
    branch_name: string | null;
    variant_name: string | null;
    quantity: number;
}

interface Props {
    inventory: {
        data: InventoryRow[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function InventoryIndexPage({ inventory }: Props) {
    return (
        <>
            <Head title="Inventory" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Inventory"
                    description="View stock levels across your branches and products."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <h2 className="text-lg font-semibold">Stock Levels</h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        All stock changes are managed via adjustments and transfers.
                    </p>

                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b bg-muted/50">
                                <tr>
                                    <th className="px-3 py-2 text-left text-xs font-medium uppercase text-muted-foreground">Product</th>
                                    <th className="px-3 py-2 text-left text-xs font-medium uppercase text-muted-foreground">Branch</th>
                                    <th className="px-3 py-2 text-left text-xs font-medium uppercase text-muted-foreground">Variant</th>
                                    <th className="px-3 py-2 text-right text-xs font-medium uppercase text-muted-foreground">Quantity</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {inventory.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-3 py-8 text-center text-sm text-muted-foreground">
                                            No inventory records found.
                                        </td>
                                    </tr>
                                ) : (
                                    inventory.data.map((row) => (
                                        <tr key={row.id} className="hover:bg-muted/30">
                                            <td className="px-3 py-2 text-sm">
                                                <Link
                                                    href={route('inventory.show', row.product_id)}
                                                    className="font-medium hover:text-foreground"
                                                >
                                                    {row.product_name}
                                                </Link>
                                            </td>
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

InventoryIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Inventory',
            href: '/inventory',
        },
    ],
};