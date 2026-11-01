import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { create, edit } from '@/routes/expense-categories';


interface ExpenseCategory {
    id: string;
    name: string;
    expenses_count: number;
}

interface Props {
    categories: {
        data: ExpenseCategory[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function ExpenseCategoryIndexPage({ categories }: Props) {
    return (
        <>
            <Head title="Expense categories" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Expense categories"
                    description="Group your expenses into categories for reporting."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Categories</h2>
                        <Link
                            href={create.url()}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Create Category
                        </Link>
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Browse and manage all expense categories for your
                        tenant.
                    </p>

                    <div className="mt-4 divide-y">
                        {categories.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No expense categories found.
                            </div>
                        ) : (
                            categories.data.map((category) => (
                                <div
                                    key={category.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {category.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {category.expenses_count} expense(s)
                                        </p>
                                    </div>
                                    <Link
                                        href={edit.url(category.id)}
                                        className="text-sm text-muted-foreground hover:text-foreground"
                                    >
                                        Edit
                                    </Link>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

ExpenseCategoryIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Expense categories',
            href: '/expense-categories',
        },
    ],
};
