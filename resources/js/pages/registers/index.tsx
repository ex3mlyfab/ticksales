import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import Link from '@/components/link';
import { create, edit, destroy } from '@/routes/registers';


interface Register {
    id: string;
    name: string;
    branch_name: string | null;
    is_active: boolean;
    sessions_count: number;
}

interface Props {
    registers: {
        data: Register[];
        links: Array<{
            url: string;
            label: string;
            active: boolean;
        }>;
    };
}

export default function RegisterIndexPage({ registers }: Props) {
    return (
        <>
            <Head title="Registers" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Registers"
                    description="Manage POS registers and their sessions."
                />

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Registers</h2>
                        <Link
                            href={create.url()}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            New Register
                        </Link>
                    </div>

                    <div className="mt-4 divide-y">
                        {registers.data.length === 0 ? (
                            <div className="py-8 text-center text-sm text-muted-foreground">
                                No registers found.
                            </div>
                        ) : (
                            registers.data.map((register) => (
                                <div
                                    key={register.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {register.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {register.branch_name} â€¢{' '}
                                            {register.sessions_count} session(s)
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                register.is_active
                                                    ? 'bg-success/10 text-forest dark:bg-success/15 dark:text-success'
                                                    : 'bg-secondary text-secondary-foreground'
                                            }`}
                                        >
                                            {register.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </span>
                                        <Link
                                            href={edit.url(register.id)}
                                            className="text-sm text-muted-foreground hover:text-foreground"
                                        >
                                            Edit
                                        </Link>
                                        <form
                                            onSubmit={(e) => {
                                                e.preventDefault();
                                                e.currentTarget.submit();
                                            }}
                                            action={destroy.url(register.id)}
                                            method="POST"
                                        >
                                            <button
                                                type="submit"
                                                className="text-sm text-destructive hover:text-destructive"
                                                onClick={(e) => { if (!confirm('Are you sure you want to delete this register?')) e.preventDefault(); }}
                                            >
                                                Delete
                                            </button>
                                            <input
                                                type="hidden"
                                                name="_method"
                                                value="DELETE"
                                            />
                                        </form>
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

RegisterIndexPage.layout = {
    breadcrumbs: [
        {
            title: 'Registers',
            href: '/registers',
        },
    ],
};
