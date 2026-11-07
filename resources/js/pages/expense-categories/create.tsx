import { useForm, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Input, Label } from '@/components/ui';
import Link from '@/components/link';
import { store, index } from '@/routes/expense-categories';


export default function ExpenseCategoryCreatePage() {
    const form = useForm({
        name: '',
    });

    return (
        <>
            <Head title="New expense category" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="New expense category"
                    description="Create a category for organizing expenses."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <form onSubmit={(e) => { e.preventDefault(); form.post(store.url()); }} className="space-y-6">
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
                                        placeholder="e.g. Utilities, Rent, Marketing"
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-destructive">
                                            {errors.name}
                                        </p>
                                    )}
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Create Category
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

ExpenseCategoryCreatePage.layout = {
    breadcrumbs: [
        {
            title: 'Expense categories',
            href: '/expense-categories',
        },
        {
            title: 'New category',
            href: '/expense-categories/create',
        },
    ],
};
