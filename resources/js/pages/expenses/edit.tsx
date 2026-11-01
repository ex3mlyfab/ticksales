import { useForm, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Input, Label, Textarea } from '@/components/ui';
import Link from '@/components/link';
import { update, index } from '@/routes/expenses';


interface Branch {
    id: string;
    name: string;
}

interface Category {
    id: string;
    name: string;
}

interface PaymentMethod {
    id: string;
    name: string;
    type: string;
}

interface ExpenseRecord {
    id: string;
    branch_id: string;
    expense_category_id: string | null;
    reference_no: string | null;
    description: string;
    amount: string;
    expense_date: string;
    payment_method_id: string | null;
    note: string | null;
}

interface Props {
    expense: ExpenseRecord;
    branches: Branch[];
    categories: Category[];
    paymentMethods: PaymentMethod[];
}

export default function ExpenseEditPage({
    expense,
    branches,
    categories,
    paymentMethods,
}: Props) {
    const form = useForm({
        branch_id: expense.branch_id,
        expense_category_id: expense.expense_category_id ?? '',
        reference_no: expense.reference_no ?? '',
        description: expense.description,
        amount: expense.amount,
        expense_date: expense.expense_date.slice(0, 10),
        payment_method_id: expense.payment_method_id ?? '',
        note: expense.note ?? '',
    });

    return (
        <>
            <Head title={`Edit ${expense.reference_no ?? 'expense'}`} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={`Edit ${expense.reference_no ?? 'expense'}`}
                    description="Update this expense."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <form onSubmit={(e) => { e.preventDefault(); form.put(update.url(expense.id)); }} className="space-y-6">
                        {(() => { const { processing, errors } = form; return (<>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="branch_id">
                                            Branch *
                                        </Label>
                                        <select
                                            id="branch_id"
                                            name="branch_id"
                                            value={form.data.branch_id}
                                            onChange={(e) =>
                                                form.setData(
                                                    'branch_id',
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">
                                                Select branch
                                            </option>
                                            {branches.map((b) => (
                                                <option key={b.id} value={b.id}>
                                                    {b.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.branch_id && (
                                            <p className="text-sm text-red-500">
                                                {errors.branch_id}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="expense_category_id">
                                            Category
                                        </Label>
                                        <select
                                            id="expense_category_id"
                                            name="expense_category_id"
                                            value={
                                                form.data.expense_category_id
                                            }
                                            onChange={(e) =>
                                                form.setData(
                                                    'expense_category_id',
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">
                                                Uncategorized
                                            </option>
                                            {categories.map((c) => (
                                                <option key={c.id} value={c.id}>
                                                    {c.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            Description *
                                        </Label>
                                        <Input
                                            id="description"
                                            name="description"
                                            value={form.data.description}
                                            onChange={(e) =>
                                                form.setData(
                                                    'description',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        {errors.description && (
                                            <p className="text-sm text-red-500">
                                                {errors.description}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="amount">Amount *</Label>
                                        <Input
                                            id="amount"
                                            name="amount"
                                            type="number"
                                            step="0.0001"
                                            min="0"
                                            value={form.data.amount}
                                            onChange={(e) =>
                                                form.setData(
                                                    'amount',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        {errors.amount && (
                                            <p className="text-sm text-red-500">
                                                {errors.amount}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="expense_date">
                                            Date *
                                        </Label>
                                        <Input
                                            id="expense_date"
                                            name="expense_date"
                                            type="date"
                                            value={form.data.expense_date}
                                            onChange={(e) =>
                                                form.setData(
                                                    'expense_date',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        {errors.expense_date && (
                                            <p className="text-sm text-red-500">
                                                {errors.expense_date}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="payment_method_id">
                                            Payment method
                                        </Label>
                                        <select
                                            id="payment_method_id"
                                            name="payment_method_id"
                                            value={form.data.payment_method_id}
                                            onChange={(e) =>
                                                form.setData(
                                                    'payment_method_id',
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option value="">
                                                Not specified
                                            </option>
                                            {paymentMethods.map((pm) => (
                                                <option
                                                    key={pm.id}
                                                    value={pm.id}
                                                >
                                                    {pm.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="reference_no">
                                            Reference no.
                                        </Label>
                                        <Input
                                            id="reference_no"
                                            name="reference_no"
                                            value={form.data.reference_no}
                                            onChange={(e) =>
                                                form.setData(
                                                    'reference_no',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="note">Note</Label>
                                    <Textarea
                                        id="note"
                                        name="note"
                                        value={form.data.note}
                                        onChange={(e) =>
                                            form.setData('note', e.target.value)
                                        }
                                    />
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

ExpenseEditPage.layout = {
    breadcrumbs: [
        {
            title: 'Expenses',
            href: '/expenses',
        },
        {
            title: 'Edit',
            href: '/expenses',
        },
    ],
};
