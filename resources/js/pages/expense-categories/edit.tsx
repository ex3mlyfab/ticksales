import { useForm, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Input, Label } from '@/components/ui';
import Link from '@/components/link';
import { update, index } from '@/routes/expense-categories';


interface Category {
    id: string;
    name: string;
}

interface Props {
    category: Category;
}

export default function ExpenseCategoryEditPage({ category }: Props) {
    const form = useForm({
        name: category.name,
    });

    return (
        <>
            <Head title={`Edit ${category.name}`} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={`Edit ${category.name}`}
                    description="Update this expense category."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <form onSubmit={(e) => { e.preventDefault(); form.put(update.url(category.id)); }} className="space-y-6">
                        {(() => { const { processing, errors } = form; return (<>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name *</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        value={form.data.name}
                                        onChange={(e) =>
                                            form.setData('name', e.target.value)
                                        }
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-destructive">
                                            {errors.name}
                                        </p>
                                    )}
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Save Changes
                                    </Button>
                                    <Link
                                        href={index.url()}
                                        className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                                    >
                                        Cancel
                                    </Link>
                                </div>
                            </>); })()}
                    </form>
                </div>
            </div>
        </>
    );
}

ExpenseCategoryEditPage.layout = {
    breadcrumbs: [
        {
            title: 'Expense categories',
            href: '/expense-categories',
        },
        {
            title: 'Edit',
            href: '/expense-categories',
        },
    ],
};
