import { useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button, Checkbox, Input, Label } from '@/components/ui';
import Link from '@/components/link';
import { store, index } from '@/routes/registers';


interface Branch {
    id: string;
    name: string;
}

interface Props {
    branches: Branch[];
}

export default function RegisterCreatePage({ branches }: Props) {
    const form = useForm({
        name: '',
        branch_id: '',
        is_active: true,
    });

    return (
        <>
            <Head title="Create register" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Create register"
                    description="Add a new POS register to a branch."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <form onSubmit={(e) => { e.preventDefault(); form.post(store.url()); }} className="space-y-6">
                        {(() => { const { processing, errors } = form; return (<>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            value={form.data.name}
                                            onChange={(e) =>
                                                form.setData(
                                                    'name',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="e.g. Front Counter 1"
                                            required
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-destructive">
                                                {errors.name}
                                            </p>
                                        )}
                                    </div>

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
                                            {branches.map((branch) => (
                                                <option
                                                    key={branch.id}
                                                    value={branch.id}
                                                >
                                                    {branch.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.branch_id && (
                                            <p className="text-sm text-destructive">
                                                {errors.branch_id}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        name="is_active"
                                        checked={form.data.is_active}
                                        onCheckedChange={(checked) => form.setData('is_active', checked === true)}
                                    />
                                    <Label
                                        htmlFor="is_active"
                                        className="text-sm"
                                    >
                                        Active
                                    </Label>
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit" disabled={processing}>
                                        Create Register
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
