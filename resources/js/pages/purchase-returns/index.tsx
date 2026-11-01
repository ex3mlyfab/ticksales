import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { create, post, show } from '@/routes/purchase-returns';


interface PurchaseReturn {
    id: string;
    reference_no: string;
    status: string;
    return_date: string;
    reason: string;
    total: number;
    supplier: { name: string } | null;
    branch: { name: string } | null;
}

interface Props {
    returns: {
        data: PurchaseReturn[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function PurchaseReturnIndexPage({ returns }: Props) {
    return (
        <>
            <Head title="Purchase returns" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Purchase returns"
                    description="Return goods to suppliers."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">
                            Purchase Returns
                        </h2>
                        <Link
                            href={create.url()}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            New Return
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {returns.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No purchase returns found.
                            </div>
                        ) : (
                            returns.data.map((purchaseReturn) => (
                                <div
                                    key={purchaseReturn.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {purchaseReturn.reference_no}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {purchaseReturn.supplier?.name} •{' '}
                                            {purchaseReturn.branch?.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {purchaseReturn.reason}
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span className="font-medium">
                                            {Number(
                                                purchaseReturn.total,
                                            ).toFixed(2)}
                                        </span>
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                purchaseReturn.status ===
                                                'posted'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-yellow-100 text-yellow-800'
                                            }`}
                                        >
                                            {purchaseReturn.status}
                                        </span>
                                        {purchaseReturn.status === 'draft' && (
                                            <form
                                                action={post.url(purchaseReturn.id)}
                                                method="POST"
                                            >
                                                <button
                                                    type="submit"
                                                    className="text-sm text-blue-500 hover:text-blue-700"
                                                >
                                                    Post
                                                </button>
                                            </form>
                                        )}
                                        <Link
                                            href={show.url(purchaseReturn.id)}
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

PurchaseReturnIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Purchase returns',
            href: '/purchase-returns',
        },
    ],
};
