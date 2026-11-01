import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { create, show } from '@/routes/expenses';


interface Expense {
    id: string;
    reference_no: string | null;
    description: string;
    amount: string;
    expense_date: string;
    branch?: { name: string } | null;
    expense_category?: { name: string } | null;
    payment_method?: { name: string } | null;
    recorded_by?: { name: string } | null;
}

interface Props {
    expenses: {
        data: Expense[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function ExpenseIndexPage({ expenses }: Props) {
    return (
        <>
            <Head title="Expenses" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Expenses"
                    description="Track your operating expenses."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Expenses</h2>
                        <Link
                            href={create.url()}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Record Expense
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {expenses.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No expenses found.
                            </div>
                        ) : (
                            expenses.data.map((expense) => (
                                <div
                                    key={expense.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {expense.description}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {expense.reference_no ??
                                                'No reference'}{' '}
                                            | {expense.branch?.name ?? '-'} |{' '}
                                            {expense.expense_category?.name ??
                                                'Uncategorized'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {expense.expense_date}
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span className="font-medium">
                                            {Number(expense.amount).toFixed(2)}
                                        </span>
                                        <Link
                                            href={show.url(expense.id)}
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

ExpenseIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Expenses',
            href: '/expenses',
        },
    ],
};
