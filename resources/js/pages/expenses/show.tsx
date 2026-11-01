import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { edit, index } from '@/routes/expenses';


interface Expense {
    id: string;
    reference_no: string | null;
    description: string;
    amount: string;
    expense_date: string;
    note: string | null;
    attachment_path: string | null;
    branch?: { name: string } | null;
    expense_category?: { name: string } | null;
    payment_method?: { name: string } | null;
    recorded_by?: { name: string } | null;
}

interface Props {
    expense: Expense;
}

export default function ExpenseShowPage({ expense }: Props) {
    return (
        <>
            <Head title={expense.description} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={expense.description}
                    description={`${expense.reference_no ?? 'No reference'} | ${expense.branch?.name ?? '-'}`}
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Expense</h2>
                        <div className="flex gap-2">
                            <Link
                                href={edit.url(expense.id)}
                                className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                            >
                                Edit
                            </Link>
                        </div>
                    </div>

                    <div className="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p className="text-muted-foreground">Amount</p>
                            <p className="text-lg font-semibold">
                                {Number(expense.amount).toFixed(2)}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">Date</p>
                            <p>{expense.expense_date}</p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">Category</p>
                            <p>
                                {expense.expense_category?.name ??
                                    'Uncategorized'}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">
                                Payment method
                            </p>
                            <p>
                                {expense.payment_method?.name ??
                                    'Not specified'}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">Recorded by</p>
                            <p>{expense.recorded_by?.name ?? '-'}</p>
                        </div>
                    </div>

                    {expense.note && (
                        <div className="mt-4 border-t pt-4">
                            <p className="text-sm text-muted-foreground">
                                Note
                            </p>
                            <p className="mt-1 text-sm">{expense.note}</p>
                        </div>
                    )}
                </div>

                <div className="flex gap-2">
                    <Link
                        href={index.url()}
                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Back to Expenses
                    </Link>
                </div>
            </div>
        </>
    );
}

ExpenseShowPage.layout = {
    breadcrumbs: [
        {
            title: 'Expenses',
            href: '/expenses',
        },
        {
            title: 'View',
            href: '/expenses',
        },
    ],
};
