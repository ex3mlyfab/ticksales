import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';

interface TaxRate {
    id: string;
    name: string;
    rate: number;
    is_inclusive: boolean;
    is_active: boolean;
}

interface Props {
    taxRates: {
        data: TaxRate[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function TaxRateIndexPage({ taxRates }: Props) {
    return (
        <>
            <Head title="Tax rates" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Tax rates"
                    description="Manage the tax rates applied to your sales and purchases."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Rates</h2>
                        <Link
                            href={route('tax-rates.create')}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Create Rate
                        </Link>
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Browse and manage all tax rates for your tenant.
                    </p>

                    <div className="mt-4 divide-y">
                        {taxRates.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No tax rates found.
                            </div>
                        ) : (
                            taxRates.data.map((taxRate) => (
                                <div
                                    key={taxRate.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">{taxRate.name}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {taxRate.rate}%{taxRate.is_inclusive ? ' · inclusive' : ' · exclusive'}
                                            {!taxRate.is_active && ' · inactive'}
                                        </p>
                                    </div>
                                    <div className="flex space-x-2">
                                        <Link
                                            href={route('tax-rates.edit', taxRate.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            Edit
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

TaxRateIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Tax rates',
            href: '/tax-rates',
        },
    ],
};
